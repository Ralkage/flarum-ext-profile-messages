<?php

namespace Ralkage\ProfileMessages\Api\Controller;

use Flarum\Api\Controller\AbstractCreateController;
use Flarum\Formatter\Formatter;
use Flarum\Foundation\ValidationException;
use Flarum\Http\RequestUtil;
use Flarum\Locale\Translator;
use Flarum\Notification\NotificationSyncer;
use Flarum\User\User;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Ralkage\ProfileMessages\Api\Serializer\ProfileMessageSerializer;
use Ralkage\ProfileMessages\Notification\NewProfileMessageBlueprint;
use Ralkage\ProfileMessages\ProfileMessage;
use Tobscure\JsonApi\Document;

class CreateProfileMessageController extends AbstractCreateController
{
    public $serializer = ProfileMessageSerializer::class;

    public $include = ['author', 'user'];

    protected $notifications;
    protected $translator;
    protected $formatter;

    public function __construct(NotificationSyncer $notifications, Translator $translator, Formatter $formatter)
    {
        $this->notifications = $notifications;
        $this->translator = $translator;
        $this->formatter = $formatter;
    }

    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertCan('profileMessage.post');

        $data = Arr::get($request->getParsedBody(), 'data', []);
        $userId = Arr::get($data, 'relationships.user.data.id');
        $rawContent = trim(Arr::get($data, 'attributes.content', ''));
        $parentId = Arr::get($data, 'attributes.parentId');

        if (empty($rawContent)) {
            throw new ValidationException([
                'content' => $this->translator->trans('ralkage-profile-messages.forum.composer.validation_empty'),
            ]);
        }

        if (mb_strlen($rawContent) > 2000) {
            throw new ValidationException([
                'content' => $this->translator->trans('ralkage-profile-messages.forum.composer.validation_too_long'),
            ]);
        }

        $profileOwner = User::findOrFail($userId);

        if ($profileOwner->getPreference('blockProfileMessages')) {
            throw new ValidationException([
                'content' => $this->translator->trans('ralkage-profile-messages.forum.composer.validation_blocked'),
            ]);
        }

        // If replying, verify parent exists and belongs to the same profile
        if ($parentId) {
            $parent = ProfileMessage::findOrFail($parentId);
            // Replies always go on the same profile as the parent
            $profileOwner = User::findOrFail($parent->user_id);
        }

        // Parse content through Flarum's formatter (BBCode, Markdown, emoji, etc.)
        $parsedContent = $this->formatter->parse($rawContent, null, $actor);

        $message = new ProfileMessage();
        $message->user_id = $profileOwner->id;
        $message->author_id = $actor->id;
        $message->parent_id = $parentId ?: null;
        $message->content = $parsedContent;
        $message->save();

        $message->load(['author', 'user']);

        // Notify profile owner (unless they posted on their own profile)
        if ($actor->id !== $profileOwner->id) {
            $this->notifications->sync(
                new NewProfileMessageBlueprint($message, $actor),
                [$profileOwner]
            );
        }

        // Also notify the parent message author if it's a reply and different from profile owner and actor
        if ($parentId) {
            $parent = ProfileMessage::find($parentId);
            if ($parent && $parent->author_id !== $actor->id && $parent->author_id !== $profileOwner->id) {
                $parentAuthor = User::find($parent->author_id);
                if ($parentAuthor) {
                    $this->notifications->sync(
                        new NewProfileMessageBlueprint($message, $actor),
                        [$parentAuthor]
                    );
                }
            }
        }

        return $message;
    }
}

<?php

namespace Ralkage\ProfileMessages\Api\Controller;

use Flarum\Api\Controller\AbstractShowController;
use Flarum\Formatter\Formatter;
use Flarum\Foundation\ValidationException;
use Flarum\Http\RequestUtil;
use Flarum\Locale\Translator;
use Flarum\User\Exception\PermissionDeniedException;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Ralkage\ProfileMessages\Api\Serializer\ProfileMessageSerializer;
use Ralkage\ProfileMessages\ProfileMessage;
use Tobscure\JsonApi\Document;

class EditProfileMessageController extends AbstractShowController
{
    public $serializer = ProfileMessageSerializer::class;

    public $include = ['author', 'user'];

    protected $formatter;
    protected $translator;

    public function __construct(Formatter $formatter, Translator $translator)
    {
        $this->formatter = $formatter;
        $this->translator = $translator;
    }

    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertRegistered();

        $id = Arr::get($request->getQueryParams(), 'id');
        $message = ProfileMessage::findOrFail($id);

        // Permission: author can edit own, profile owner and admins can edit any
        $canEdit = $actor->isAdmin()
            || $actor->hasPermission('profileMessage.editAny')
            || $actor->id === $message->author_id;

        if (! $canEdit) {
            throw new PermissionDeniedException();
        }

        $data = Arr::get($request->getParsedBody(), 'data', []);
        $rawContent = trim(Arr::get($data, 'attributes.content', ''));

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

        $message->content = $this->formatter->parse($rawContent, null, $actor);
        $message->save();

        $message->load(['author', 'user']);

        return $message;
    }
}

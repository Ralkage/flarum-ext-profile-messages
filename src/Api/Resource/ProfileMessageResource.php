<?php

namespace Ralkage\ProfileMessages\Api\Resource;

use Flarum\Api\Context;
use Flarum\Api\Endpoint;
use Flarum\Api\Resource\AbstractDatabaseResource;
use Flarum\Api\Schema;
use Flarum\Api\Sort\SortColumn;
use Flarum\Formatter\Formatter;
use Flarum\Foundation\ValidationException;
use Flarum\Locale\Translator;
use Flarum\Notification\NotificationSyncer;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Ralkage\ProfileMessages\Notification\NewProfileMessageBlueprint;
use Ralkage\ProfileMessages\ProfileMessage;
use Ralkage\ProfileMessages\ProfileMessageReport;
use Tobyz\JsonApiServer\Context as OriginalContext;

/**
 * @extends AbstractDatabaseResource<ProfileMessage>
 */
class ProfileMessageResource extends AbstractDatabaseResource
{
    public function __construct(
        protected Translator $translator,
        protected Formatter $formatter,
        protected NotificationSyncer $notifications,
    ) {
    }

    public function type(): string
    {
        return 'profile-messages';
    }

    public function model(): string
    {
        return ProfileMessage::class;
    }

    public function scope(Builder $query, OriginalContext $context): void
    {
        // All profile messages are publicly readable
    }

    public function endpoints(): array
    {
        return [
            Endpoint\Create::make()
                ->authenticated()
                ->visible(fn (Context $context) => $context->getActor()->can('profileMessage.post'))
                ->defaultInclude(['author', 'user']),

            Endpoint\Update::make()
                ->authenticated()
                ->visible(function (ProfileMessage $message, Context $context) {
                    $actor = $context->getActor();

                    return $actor->isAdmin()
                        || $actor->hasPermission('profileMessage.editAny')
                        || $actor->id === $message->author_id;
                })
                ->defaultInclude(['author', 'user']),

            Endpoint\Delete::make()
                ->authenticated()
                ->visible(function (ProfileMessage $message, Context $context) {
                    $actor = $context->getActor();

                    return $actor->isAdmin()
                        || $actor->id === $message->author_id
                        || $actor->id === $message->user_id;
                }),

            Endpoint\Show::make()
                ->defaultInclude(['author', 'user']),

            Endpoint\Index::make()
                ->defaultInclude(['author', 'user'])
                ->defaultSort('-createdAt')
                ->paginate(),
        ];
    }

    public function fields(): array
    {
        return [
            Schema\Str::make('content')
                ->requiredOnCreate()
                ->writable()
                ->get(function (ProfileMessage $message) {
                    $content = $message->content;

                    if (! empty($content) && $content[0] === '<') {
                        return $this->formatter->unparse($content);
                    }

                    return $content;
                })
                ->set(function (ProfileMessage $message, string $value, Context $context) {
                    $rawContent = trim($value);

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

                    $message->content = $this->formatter->parse($rawContent, null, $context->getActor());
                }),

            Schema\Str::make('contentHtml')
                ->get(fn (ProfileMessage $message) => $message->formatContent()),

            Schema\Number::make('parentId')
                ->writableOnCreate()
                ->get(fn (ProfileMessage $message) => $message->parent_id),

            Schema\Number::make('replyCount')
                ->get(fn (ProfileMessage $message) => $message->replies()->count()),

            Schema\DateTime::make('createdAt'),

            Schema\Boolean::make('canDelete')
                ->get(function (ProfileMessage $message, Context $context) {
                    $actor = $context->getActor();

                    return $actor->isAdmin()
                        || $actor->id === $message->author_id
                        || $actor->id === $message->user_id;
                }),

            Schema\Boolean::make('canEdit')
                ->get(function (ProfileMessage $message, Context $context) {
                    $actor = $context->getActor();

                    return $actor->isAdmin()
                        || $actor->id === $message->author_id
                        || $actor->hasPermission('profileMessage.editAny');
                }),

            Schema\Boolean::make('canReply')
                ->get(fn (ProfileMessage $message, Context $context) => $context->getActor()->can('profileMessage.post')),

            Schema\Boolean::make('canReport')
                ->get(function (ProfileMessage $message, Context $context) {
                    $actor = $context->getActor();

                    return ! $actor->isGuest() && $actor->id !== $message->author_id;
                }),

            Schema\Number::make('reportCount')
                ->get(function (ProfileMessage $message, Context $context) {
                    if ($context->getActor()->isAdmin()) {
                        return ProfileMessageReport::where('message_id', $message->id)->count();
                    }

                    return null;
                }),

            Schema\Relationship\ToOne::make('user')
                ->type('users')
                ->includable()
                ->writableOnCreate(),

            Schema\Relationship\ToOne::make('author')
                ->type('users')
                ->includable(),
        ];
    }

    public function sorts(): array
    {
        return [
            SortColumn::make('createdAt'),
        ];
    }

    public function creating(object $model, OriginalContext $context): ?object
    {
        $actor = $context->getActor();
        $data = $context->body()['data'] ?? [];

        $userId = Arr::get($data, 'relationships.user.data.id');
        $parentId = Arr::get($data, 'attributes.parentId');

        if ($parentId) {
            $parent = ProfileMessage::findOrFail($parentId);
            $userId = $parent->user_id;
        }

        $profileOwner = User::findOrFail($userId);

        if ($profileOwner->getPreference('blockProfileMessages')) {
            throw new ValidationException([
                'content' => $this->translator->trans('ralkage-profile-messages.forum.composer.validation_blocked'),
            ]);
        }

        $model->user_id = $profileOwner->id;
        $model->author_id = $actor->id;
        $model->parent_id = $parentId ?: null;

        return parent::creating($model, $context);
    }

    public function created(object $model, OriginalContext $context): ?object
    {
        $actor = $context->getActor();
        $model->load(['author', 'user']);

        $profileOwner = $model->user;

        // Notify profile owner
        if ($actor->id !== $profileOwner->id) {
            $this->notifications->sync(
                new NewProfileMessageBlueprint($model, $actor),
                [$profileOwner]
            );
        }

        // Notify parent message author if it's a reply
        if ($model->parent_id) {
            $parent = ProfileMessage::find($model->parent_id);

            if ($parent && $parent->author_id !== $actor->id && $parent->author_id !== $profileOwner->id) {
                $parentAuthor = User::find($parent->author_id);

                if ($parentAuthor) {
                    $this->notifications->sync(
                        new NewProfileMessageBlueprint($model, $actor),
                        [$parentAuthor]
                    );
                }
            }
        }

        return parent::created($model, $context);
    }
}

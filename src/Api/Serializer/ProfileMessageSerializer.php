<?php

namespace Ralkage\ProfileMessages\Api\Serializer;

use Flarum\Api\Serializer\AbstractSerializer;
use Flarum\Api\Serializer\BasicUserSerializer;
use Flarum\Formatter\Formatter;
use Ralkage\ProfileMessages\ProfileMessageReport;

class ProfileMessageSerializer extends AbstractSerializer
{
    protected $type = 'profile-messages';

    protected $formatter;

    public function __construct(Formatter $formatter)
    {
        $this->formatter = $formatter;
    }

    protected function getDefaultAttributes($message)
    {
        $actor = $this->getActor();

        $isAuthor = $actor->id === $message->author_id;
        $isProfileOwner = $actor->id === $message->user_id;
        $isAdmin = $actor->isAdmin();

        // Unparse XML back to original markdown/BBCode for editing
        $contentPlain = $message->content;
        if (! empty($contentPlain) && $contentPlain[0] === '<') {
            $contentPlain = $this->formatter->unparse($contentPlain);
        }

        return [
            'content' => $contentPlain,
            'contentHtml' => $message->formatContent(),
            'parentId' => $message->parent_id,
            'replyCount' => $message->replies()->count(),
            'createdAt' => $this->formatDate($message->created_at),
            'canDelete' => $isAdmin || $isAuthor || $isProfileOwner,
            'canEdit' => $isAdmin || $isAuthor || $actor->hasPermission('profileMessage.editAny'),
            'canReply' => $actor->can('profileMessage.post'),
            'canReport' => ! $actor->isGuest() && ! $isAuthor,
            'reportCount' => $isAdmin
                ? ProfileMessageReport::where('message_id', $message->id)->count()
                : null,
        ];
    }

    protected function user($message)
    {
        return $this->hasOne($message, BasicUserSerializer::class);
    }

    protected function author($message)
    {
        return $this->hasOne($message, BasicUserSerializer::class);
    }
}

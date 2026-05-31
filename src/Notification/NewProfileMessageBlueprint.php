<?php

namespace Ralkage\ProfileMessages\Notification;

use Flarum\Notification\Blueprint\BlueprintInterface;
use Flarum\User\User;
use Ralkage\ProfileMessages\ProfileMessage;

class NewProfileMessageBlueprint implements BlueprintInterface
{
    protected $message;
    protected $fromUser;

    public function __construct(ProfileMessage $message, User $fromUser)
    {
        $this->message = $message;
        $this->fromUser = $fromUser;
    }

    public function getSubject()
    {
        return $this->message;
    }

    public function getFromUser()
    {
        return $this->fromUser;
    }

    public function getData()
    {
        $profileOwner = $this->message->user;

        return [
            'messageId' => $this->message->id,
            'isReply' => ! empty($this->message->parent_id),
            'profileOwnerId' => $profileOwner ? $profileOwner->id : null,
            'profileOwnerUsername' => $profileOwner ? $profileOwner->username : null,
            'profileOwnerDisplayName' => $profileOwner ? $profileOwner->display_name : null,
        ];
    }

    public static function getType()
    {
        return 'newProfileMessage';
    }

    public static function getSubjectModel()
    {
        return ProfileMessage::class;
    }
}

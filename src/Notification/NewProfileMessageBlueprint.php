<?php

namespace Ralkage\ProfileMessages\Notification;

use Flarum\Database\AbstractModel;
use Flarum\Notification\AlertableInterface;
use Flarum\Notification\Blueprint\BlueprintInterface;
use Flarum\User\User;
use Ralkage\ProfileMessages\ProfileMessage;

class NewProfileMessageBlueprint implements BlueprintInterface, AlertableInterface
{
    public function __construct(
        protected ProfileMessage $message,
        protected User $fromUser
    ) {
    }

    public function getSubject(): ?AbstractModel
    {
        return $this->message;
    }

    public function getFromUser(): ?User
    {
        return $this->fromUser;
    }

    public function getData(): mixed
    {
        return [
            'messageId' => $this->message->id,
        ];
    }

    public static function getType(): string
    {
        return 'newProfileMessage';
    }

    public static function getSubjectModel(): string
    {
        return ProfileMessage::class;
    }
}

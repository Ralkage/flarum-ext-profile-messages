<?php

namespace Ralkage\ProfileMessages;

use Flarum\Database\AbstractModel;
use Flarum\User\User;

class ProfileMessageReport extends AbstractModel
{
    protected $table = 'profile_message_reports';

    public $timestamps = true;

    public function message()
    {
        return $this->belongsTo(ProfileMessage::class, 'message_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

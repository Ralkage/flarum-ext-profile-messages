<?php

namespace Ralkage\ProfileMessages;

use Flarum\Database\AbstractModel;
use Flarum\Formatter\Formatter;
use Flarum\User\User;

class ProfileMessage extends AbstractModel
{
    protected $table = 'profile_messages';

    public $timestamps = true;

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function replies()
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('created_at', 'asc');
    }

    public function formatContent()
    {
        $content = $this->content;

        // If content is not XML (legacy plain text), escape and wrap it
        if (! empty($content) && $content[0] !== '<') {
            return '<p>' . e($content) . '</p>';
        }

        $formatter = resolve(Formatter::class);

        return $formatter->render($content, $this);
    }
}

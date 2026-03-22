<?php

namespace Ralkage\ProfileMessages\Search;

use Flarum\Search\Database\AbstractSearcher;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;
use Ralkage\ProfileMessages\ProfileMessage;

class ProfileMessageSearcher extends AbstractSearcher
{
    public function getQuery(User $actor): Builder
    {
        return ProfileMessage::query();
    }
}

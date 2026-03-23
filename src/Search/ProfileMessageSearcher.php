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
        // Default to top-level messages only.
        // ParentFilter will remove this constraint when filter[parent] is set.
        return ProfileMessage::query()->whereNull('parent_id');
    }
}

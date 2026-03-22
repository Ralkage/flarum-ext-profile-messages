<?php

namespace Ralkage\ProfileMessages\Search\Filter;

use Flarum\Search\Database\DatabaseSearchState;
use Flarum\Search\Filter\FilterInterface;
use Flarum\Search\SearchState;

/**
 * @implements FilterInterface<DatabaseSearchState>
 */
class ParentFilter implements FilterInterface
{
    public function getFilterKey(): string
    {
        return 'parent';
    }

    public function filter(SearchState $state, string|array $value, bool $negate): void
    {
        if ($value) {
            $state->getQuery()->where('parent_id', $negate ? '!=' : '=', $value);
        } else {
            $state->getQuery()->whereNull('parent_id');
        }
    }
}

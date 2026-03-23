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
        $query = $state->getQuery();

        // Remove the default whereNull('parent_id') from the searcher
        $query->getQuery()->wheres = array_values(array_filter(
            $query->getQuery()->wheres,
            fn ($where) => ($where['column'] ?? '') !== 'parent_id'
        ));

        if ($value) {
            $query->where('parent_id', $negate ? '!=' : '=', $value);
        } else {
            $query->whereNull('parent_id');
        }
    }
}

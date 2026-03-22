<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        $schema->table('profile_messages', function (Blueprint $table) {
            $table->unsignedInteger('parent_id')->nullable()->after('author_id');
            $table->index('parent_id');
            $table->foreign('parent_id')->references('id')->on('profile_messages')->onDelete('cascade');
        });
    },
    'down' => function (Builder $schema) {
        $schema->table('profile_messages', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn('parent_id');
        });
    },
];

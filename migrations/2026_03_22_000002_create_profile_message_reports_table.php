<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        $schema->create('profile_message_reports', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('message_id');
            $table->unsignedInteger('user_id');
            $table->string('reason', 50);
            $table->text('reason_detail')->nullable();
            $table->timestamps();

            $table->index('message_id');
            $table->index('user_id');

            $table->foreign('message_id')->references('id')->on('profile_messages')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    },
    'down' => function (Builder $schema) {
        $schema->dropIfExists('profile_message_reports');
    },
];

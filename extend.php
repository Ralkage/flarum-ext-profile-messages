<?php

use Flarum\Api\Context;
use Flarum\Api\Resource;
use Flarum\Api\Schema;
use Flarum\Extend;
use Flarum\User\User;
use Ralkage\ProfileMessages\Api\Controller\CreateProfileMessageController;
use Ralkage\ProfileMessages\Api\Controller\DeleteProfileMessageController;
use Ralkage\ProfileMessages\Api\Controller\EditProfileMessageController;
use Ralkage\ProfileMessages\Api\Controller\ListProfileMessagesController;
use Ralkage\ProfileMessages\Api\Controller\PreviewProfileMessageController;
use Ralkage\ProfileMessages\Api\Controller\ReportProfileMessageController;
use Ralkage\ProfileMessages\Api\Controller\ShowProfileMessageController;
use Ralkage\ProfileMessages\Notification\NewProfileMessageBlueprint;

return [
    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js'),

    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/less/forum.less'),

    new Extend\Locales(__DIR__.'/locale'),

    (new Extend\Routes('api'))
        ->get('/profile-messages', 'profile-messages.index', ListProfileMessagesController::class)
        ->get('/profile-messages/{id}', 'profile-messages.show', ShowProfileMessageController::class)
        ->post('/profile-messages', 'profile-messages.create', CreateProfileMessageController::class)
        ->patch('/profile-messages/{id}', 'profile-messages.update', EditProfileMessageController::class)
        ->delete('/profile-messages/{id}', 'profile-messages.delete', DeleteProfileMessageController::class)
        ->post('/profile-messages/preview', 'profile-messages.preview', PreviewProfileMessageController::class)
        ->post('/profile-message-reports', 'profile-message-reports.create', ReportProfileMessageController::class),

    (new Extend\Notification())
        ->type(NewProfileMessageBlueprint::class, ['alert']),

    (new Extend\User())
        ->registerPreference('blockProfileMessages', 'boolval', false)
        ->registerPreference('profileMessagesDefault', 'boolval', false),

    (new Extend\ApiResource(Resource\UserResource::class))
        ->fields(fn () => [
            Schema\Boolean::make('canPostProfileMessages')
                ->get(fn ($user, Context $context) => $context->getActor()->can('profileMessage.post')),
            Schema\Boolean::make('blockProfileMessages')
                ->get(fn ($user, Context $context) => (bool) $user->getPreference('blockProfileMessages')),
            Schema\Boolean::make('profileMessagesDefault')
                ->get(fn ($user, Context $context) => (bool) $user->getPreference('profileMessagesDefault')),
        ]),
];

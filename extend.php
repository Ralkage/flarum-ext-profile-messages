<?php

use Flarum\Api\Serializer\BasicUserSerializer;
use Flarum\Extend;
use Ralkage\ProfileMessages\Api\Controller\CreateProfileMessageController;
use Ralkage\ProfileMessages\Api\Controller\DeleteProfileMessageController;
use Ralkage\ProfileMessages\Api\Controller\EditProfileMessageController;
use Ralkage\ProfileMessages\Api\Controller\ListProfileMessagesController;
use Ralkage\ProfileMessages\Api\Controller\PreviewProfileMessageController;
use Ralkage\ProfileMessages\Api\Controller\ReportProfileMessageController;
use Ralkage\ProfileMessages\Api\Controller\ShowProfileMessageController;
use Ralkage\ProfileMessages\Api\Serializer\ProfileMessageSerializer;
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
        ->type(NewProfileMessageBlueprint::class, ProfileMessageSerializer::class, ['alert']),

    (new Extend\User())
        ->registerPreference('blockProfileMessages', 'boolval', false)
        ->registerPreference('profileMessagesDefault', 'boolval', false),

    (new Extend\ApiSerializer(BasicUserSerializer::class))
        ->attribute('canPostProfileMessages', function ($serializer, $user) {
            return $serializer->getActor()->can('profileMessage.post');
        })
        ->attribute('blockProfileMessages', function ($serializer, $user) {
            return (bool) $user->getPreference('blockProfileMessages');
        })
        ->attribute('profileMessagesDefault', function ($serializer, $user) {
            return (bool) $user->getPreference('profileMessagesDefault');
        }),
];

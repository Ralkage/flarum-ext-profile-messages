<?php

use Flarum\Api\Context;
use Flarum\Api\Resource;
use Flarum\Api\Schema;
use Flarum\Extend;
use Flarum\Search\Database\DatabaseSearchDriver;
use Ralkage\ProfileMessages\Api\Controller\PreviewProfileMessageController;
use Ralkage\ProfileMessages\Api\Controller\ReportProfileMessageController;
use Ralkage\ProfileMessages\Api\Resource\ProfileMessageResource;
use Ralkage\ProfileMessages\Notification\NewProfileMessageBlueprint;
use Ralkage\ProfileMessages\ProfileMessage;
use Ralkage\ProfileMessages\Search\Filter\ParentFilter;
use Ralkage\ProfileMessages\Search\Filter\UserFilter;
use Ralkage\ProfileMessages\Search\ProfileMessageSearcher;

return [
    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js'),

    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/less/forum.less'),

    new Extend\Locales(__DIR__.'/locale'),

    // Register the ProfileMessage API resource (handles index, show, create, update, delete)
    (new Extend\ApiResource(ProfileMessageResource::class)),

    // These custom endpoints don't fit the resource CRUD pattern
    (new Extend\Routes('api'))
        ->post('/profile-messages/preview', 'profile-messages.preview', PreviewProfileMessageController::class)
        ->post('/profile-message-reports', 'profile-message-reports.create', ReportProfileMessageController::class),

    (new Extend\Notification())
        ->type(NewProfileMessageBlueprint::class, ['alert']),

    (new Extend\User())
        ->registerPreference('blockProfileMessages', 'boolval', false)
        ->registerPreference('profileMessagesDefault', 'boolval', false),

    (new Extend\SearchDriver(DatabaseSearchDriver::class))
        ->addSearcher(ProfileMessage::class, ProfileMessageSearcher::class)
        ->addFilter(ProfileMessageSearcher::class, UserFilter::class)
        ->addFilter(ProfileMessageSearcher::class, ParentFilter::class),

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

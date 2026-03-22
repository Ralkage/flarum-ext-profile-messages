<?php

namespace Ralkage\ProfileMessages\Api\Controller;

use Flarum\Api\Controller\AbstractDeleteController;
use Flarum\Http\RequestUtil;
use Flarum\User\Exception\PermissionDeniedException;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Ralkage\ProfileMessages\ProfileMessage;

class DeleteProfileMessageController extends AbstractDeleteController
{
    protected function delete(ServerRequestInterface $request)
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertRegistered();

        $id = Arr::get($request->getQueryParams(), 'id');
        $message = ProfileMessage::findOrFail($id);

        $canDelete = $actor->isAdmin()
            || $actor->id === $message->author_id
            || $actor->id === $message->user_id;

        if (! $canDelete) {
            throw new PermissionDeniedException();
        }

        $message->delete();
    }
}

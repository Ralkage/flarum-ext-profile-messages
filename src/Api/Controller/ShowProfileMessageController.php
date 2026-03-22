<?php

namespace Ralkage\ProfileMessages\Api\Controller;

use Flarum\Api\Controller\AbstractShowController;
use Flarum\Http\RequestUtil;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Ralkage\ProfileMessages\Api\Serializer\ProfileMessageSerializer;
use Ralkage\ProfileMessages\ProfileMessage;
use Tobscure\JsonApi\Document;

class ShowProfileMessageController extends AbstractShowController
{
    public $serializer = ProfileMessageSerializer::class;

    public $include = ['author', 'user'];

    protected function data(ServerRequestInterface $request, Document $document)
    {
        $id = Arr::get($request->getQueryParams(), 'id');

        return ProfileMessage::findOrFail($id);
    }
}

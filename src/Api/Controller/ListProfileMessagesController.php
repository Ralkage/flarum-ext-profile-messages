<?php

namespace Ralkage\ProfileMessages\Api\Controller;

use Flarum\Api\Controller\AbstractListController;
use Flarum\Http\RequestUtil;
use Flarum\Http\UrlGenerator;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Ralkage\ProfileMessages\Api\Serializer\ProfileMessageSerializer;
use Ralkage\ProfileMessages\ProfileMessage;
use Tobscure\JsonApi\Document;

class ListProfileMessagesController extends AbstractListController
{
    public $serializer = ProfileMessageSerializer::class;

    public $include = ['author', 'user'];

    public $sort = ['createdAt' => 'desc'];

    public $sortFields = ['createdAt'];

    protected $url;

    public function __construct(UrlGenerator $url)
    {
        $this->url = $url;
    }

    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = RequestUtil::getActor($request);

        $userId = Arr::get($request->getQueryParams(), 'filter.user');
        $parentId = Arr::get($request->getQueryParams(), 'filter.parent');
        $limit = $this->extractLimit($request);
        $offset = $this->extractOffset($request);

        $query = ProfileMessage::where('user_id', $userId);

        if ($parentId) {
            // Get replies for a specific message
            $query->where('parent_id', $parentId);
            $query->orderBy('created_at', 'asc');
        } else {
            // Get only top-level messages (no parent)
            $query->whereNull('parent_id');
            $query->orderBy('created_at', 'desc');
        }

        $total = $query->count();

        $messages = $query->skip($offset)->take($limit)->get();

        $document->addPaginationLinks(
            $this->url->to('api')->route('profile-messages.index'),
            $request->getQueryParams(),
            $offset,
            $limit,
            $total
        );

        $document->setMeta(['total' => $total]);

        return $messages;
    }
}

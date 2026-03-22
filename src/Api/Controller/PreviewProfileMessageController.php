<?php

namespace Ralkage\ProfileMessages\Api\Controller;

use Flarum\Formatter\Formatter;
use Flarum\Http\RequestUtil;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class PreviewProfileMessageController implements RequestHandlerInterface
{
    protected $formatter;

    public function __construct(Formatter $formatter)
    {
        $this->formatter = $formatter;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertRegistered();

        $content = Arr::get($request->getParsedBody(), 'content', '');

        $parsed = $this->formatter->parse($content, null, $actor);
        $html = $this->formatter->render($parsed);

        return new JsonResponse([
            'contentHtml' => $html,
        ]);
    }
}

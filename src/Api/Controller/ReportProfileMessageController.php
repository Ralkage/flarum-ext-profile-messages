<?php

namespace Ralkage\ProfileMessages\Api\Controller;

use Flarum\Foundation\ValidationException;
use Flarum\Http\RequestUtil;
use Flarum\Locale\Translator;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Ralkage\ProfileMessages\ProfileMessage;
use Ralkage\ProfileMessages\ProfileMessageReport;

class ReportProfileMessageController implements RequestHandlerInterface
{
    public function __construct(
        protected Translator $translator,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertRegistered();

        $data = Arr::get($request->getParsedBody(), 'data', []);
        $messageId = Arr::get($data, 'attributes.messageId');
        $reason = Arr::get($data, 'attributes.reason', '');
        $reasonDetail = trim(Arr::get($data, 'attributes.reasonDetail', ''));

        $message = ProfileMessage::findOrFail($messageId);

        if ($message->author_id === $actor->id) {
            throw new ValidationException([
                'message' => $this->translator->trans('ralkage-profile-messages.forum.report.cannot_report_own'),
            ]);
        }

        $existing = ProfileMessageReport::where('message_id', $messageId)
            ->where('user_id', $actor->id)
            ->first();

        if ($existing) {
            throw new ValidationException([
                'message' => $this->translator->trans('ralkage-profile-messages.forum.report.already_reported'),
            ]);
        }

        $report = new ProfileMessageReport();
        $report->message_id = $messageId;
        $report->user_id = $actor->id;
        $report->reason = $reason ?: 'other';
        $report->reason_detail = $reasonDetail ?: null;
        $report->save();

        return new JsonResponse(['success' => true], 201);
    }
}

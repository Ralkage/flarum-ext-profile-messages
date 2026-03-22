<?php

namespace Ralkage\ProfileMessages\Api\Controller;

use Flarum\Api\Controller\AbstractCreateController;
use Flarum\Foundation\ValidationException;
use Flarum\Http\RequestUtil;
use Flarum\Locale\Translator;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Ralkage\ProfileMessages\ProfileMessage;
use Ralkage\ProfileMessages\ProfileMessageReport;
use Tobscure\JsonApi\Document;

class ReportProfileMessageController extends AbstractCreateController
{
    public $serializer = \Flarum\Api\Serializer\AbstractSerializer::class;

    protected $translator;

    public function __construct(Translator $translator)
    {
        $this->translator = $translator;
    }

    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertRegistered();

        $data = Arr::get($request->getParsedBody(), 'data', []);
        $messageId = Arr::get($data, 'attributes.messageId');
        $reason = Arr::get($data, 'attributes.reason', '');
        $reasonDetail = trim(Arr::get($data, 'attributes.reasonDetail', ''));

        $message = ProfileMessage::findOrFail($messageId);

        // Can't report your own messages
        if ($message->author_id === $actor->id) {
            throw new ValidationException([
                'message' => $this->translator->trans('ralkage-profile-messages.forum.report.cannot_report_own'),
            ]);
        }

        // Check for duplicate reports
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

        return $report;
    }
}

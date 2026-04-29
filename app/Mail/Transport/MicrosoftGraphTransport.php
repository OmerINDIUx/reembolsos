<?php

namespace App\Mail\Transport;

use App\Services\GraphMailService;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\MessageConverter;
use Symfony\Component\Mime\Part\DataPart;

class MicrosoftGraphTransport extends AbstractTransport
{
    protected $graphService;

    public function __construct(GraphMailService $graphService)
    {
        parent::__construct();
        $this->graphService = $graphService;
    }

    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());
        
        $to = collect($email->getTo())->map(function ($address) {
            return $address->getAddress();
        })->toArray();

        $subject = $email->getSubject();
        $html = $email->getHtmlBody();
        $text = $email->getTextBody();
        $content = $html ?: $text;

        $attachments = [];
        foreach ($email->getAttachments() as $attachment) {
            /** @var DataPart $attachment */
            $cid = $attachment->getContentId();
            $attachments[] = [
                'name' => $attachment->getPreparedHeaders()->getHeaderParameter('Content-Disposition', 'filename') ?: $attachment->getFilename(),
                'content' => $attachment->getBody(),
                'contentType' => $attachment->getMediaType() . '/' . $attachment->getMediaSubtype(),
                'isInline' => !empty($cid),
                'contentId' => $cid,
            ];
        }

        $this->graphService->send($to, $subject, $content, $attachments);
    }

    public function __toString(): string
    {
        return 'microsoft_graph';
    }
}

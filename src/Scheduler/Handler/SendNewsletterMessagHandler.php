<?php
namespace App\MessageHandler;

use App\Message\SendNewsletterMessage;
use App\Service\NewsletterService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class SendNewsletterMessageHandler
{
    private NewsletterService $newsletterService;

    public function __construct(NewsletterService $newsletterService)
    {
        $this->newsletterService = $newsletterService;
    }

    public function __invoke(SendNewsletterMessage $message)
    {
        $this->newsletterService->sendNewsletter($message->getRecipientEmail());
    }
}

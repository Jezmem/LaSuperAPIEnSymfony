<?php
namespace App\Scheduler\Handler;

use App\Scheduler\Message\SendEmailMessage;
use App\Service\MailerService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]

class SendEmailMessageHandler
{
    public function __construct(private MailerService $mailerService)
    {
    }

    public function __invoke(SendEmailMessage $message): void
    {
        $this->mailerService->sendEmail(
            to:'tomzarb98@gmail.com',
            subject: "T'es tout beau",
            content: "Wallah t'es beau",
        );
    }
}
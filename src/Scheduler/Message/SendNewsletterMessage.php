<?php
namespace App\Message;

class SendNewsletterMessage
{
    private string $recipientEmail;

    public function __construct(string $recipientEmail)
    {
        $this->recipientEmail = $recipientEmail;
    }

    public function getRecipientEmail(): string
    {
        return $this->recipientEmail;
    }
}

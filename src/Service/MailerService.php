<?php
class MailerService
{
    private MailerInterface $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    public function sendEmail(string $to, string $subject, string $content): void 
    {
        $email = (new Email())
            ->from(__address: 'no-reply@example.com')
            ->to($to)
            ->subject($subject)
            ->text($content)
            ->html(body: "<p>$ontent</p>");
            
        $this->mailer->send($email);
    }
}
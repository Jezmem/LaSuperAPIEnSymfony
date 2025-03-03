<?php
// src/Service/NewsletterService.php
namespace App\Service;

use App\Repository\VideoGameRepository;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class NewsletterService
{
    private VideoGameRepository $videoGameRepository;
    private MailerInterface $mailer;

    public function __construct(VideoGameRepository $videoGameRepository, MailerInterface $mailer)
    {
        $this->videoGameRepository = $videoGameRepository;
        $this->mailer = $mailer;
    }

    public function sendNewsletter(string $recipientEmail): void
    {
        // Récupérer les jeux à venir
        $upcomingGames = $this->videoGameRepository->findUpcomingGames();

        // Vérifier si des jeux sont disponibles avant d'envoyer l'email
        if (empty($upcomingGames)) {
            return;
        }

        // Créer l'email avec Twig
        $email = (new TemplatedEmail())
            ->from(new Address('noreply@votre-site.com', 'Gaming News'))
            ->to($recipientEmail)
            ->subject('Découvrez les sorties de jeux vidéo de la semaine !')
            ->htmlTemplate('emails/newsletter.html.twig')
            ->context([
                'games' => $upcomingGames,
            ]);

        // Envoyer l'email
        $this->mailer->send($email);
    }
}

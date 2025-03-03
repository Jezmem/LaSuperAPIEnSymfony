<?php
namespace App\Command;

use App\Message\SendNewsletterMessage;
use App\Repository\UserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Scheduler\Attribute\AsCronTask;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(
    name: 'app:send-newsletter',
    description: 'Envoie la newsletter aux abonnés'
)]
#[AsCronTask('30 8 * * 1')] // Tout les lundi à 08h30
class SendNewsletterCommand extends Command
{
    private MessageBusInterface $messageBus;
    private UserRepository $userRepository;

    public function __construct(MessageBusInterface $messageBus, UserRepository $userRepository)
    {
        parent::__construct();
        $this->messageBus = $messageBus;
        $this->userRepository = $userRepository;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Tout les utilisateurs qui ont souscrit a la newsletter
        $subscribedUsers = $this->userRepository->findBy(['subcription_to_newsletter' => true]);

        if (empty($subscribedUsers)) {
            $output->writeln('Aucun utilisateur abonné à la newsletter.');
            return Command::SUCCESS;
        }

        foreach ($subscribedUsers as $user) {
            $this->messageBus->dispatch(new SendNewsletterMessage($user->getEmail()));
        }

        $output->writeln(count($subscribedUsers) . ' newsletters envoyées.');
        return Command::SUCCESS;
    }
}

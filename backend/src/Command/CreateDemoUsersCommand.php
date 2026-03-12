<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-demo-users',
    description: 'Creates demo users for the Eventer application',
)]
class CreateDemoUsersCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        // no arguments
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $user = new User();
        $user->setEmail('user@example.com');
        $user->setRoles([]);
        $user->setPassword(password_hash('userpass', PASSWORD_BCRYPT));

        $agent = new User();
        $agent->setEmail('agent@example.com');
        $agent->setRoles(['ROLE_HELPDESK']);
        $agent->setPassword(password_hash('agentpass', PASSWORD_BCRYPT));

        $this->em->persist($user);
        $this->em->persist($agent);
        $this->em->flush();

        $io->success('Created demo users: user@example.com/userpass and agent@example.com/agentpass');

        return Command::SUCCESS;
    }
}

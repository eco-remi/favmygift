<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Creates a new admin user or promotes an existing one',
)]
class CreateAdminCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $userPasswordHasher
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'The email of the user')
            ->addArgument('password', InputArgument::OPTIONAL, 'The password (only for new users)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = $input->getArgument('email');
        $password = $input->getArgument('password');

        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->findOneBy(['email' => $email]);

        if ($user) {
            $roles = $user->getRoles();
            if (in_array('ROLE_ADMIN', $roles)) {
                $io->warning(sprintf('User %s is already an admin.', $email));
                return Command::SUCCESS;
            }

            $roles[] = 'ROLE_ADMIN';
            $user->setRoles(array_unique($roles));
            $io->success(sprintf('User %s has been promoted to admin.', $email));
        } else {
            if (!$password) {
                $io->error('Password is required for new users.');
                return Command::FAILURE;
            }

            $user = new User();
            $user->setEmail($email);
            $user->setRoles(['ROLE_ADMIN']);
            $user->setPassword(
                $this->userPasswordHasher->hashPassword($user, $password)
            );

            $this->entityManager->persist($user);
            $io->success(sprintf('Admin user %s created successfully.', $email));
        }

        $this->entityManager->flush();

        return Command::SUCCESS;
    }
}

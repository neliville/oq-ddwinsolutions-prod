<?php

namespace App\Command;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:reset-password',
    description: 'Réinitialiser le mot de passe d\'un utilisateur',
)]
class ResetPasswordCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Email de l\'utilisateur')
            ->addArgument('password', InputArgument::OPTIONAL, 'Nouveau mot de passe (si non fourni, un mot de passe aléatoire sera généré)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = $input->getArgument('email');
        $password = $input->getArgument('password');

        // Rechercher l'utilisateur
        $user = $this->userRepository->findOneBy(['email' => $email]);

        if (!$user) {
            $io->error(sprintf('Utilisateur avec l\'email "%s" introuvable.', $email));
            return Command::FAILURE;
        }

        // Générer un mot de passe si non fourni
        if (!$password) {
            $password = bin2hex(random_bytes(8)); // Génère un mot de passe aléatoire de 16 caractères
            $io->note('Aucun mot de passe fourni. Un mot de passe aléatoire sera généré.');
        }

        // Hasher et définir le nouveau mot de passe
        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);
        
        $this->entityManager->flush();

        $io->success(sprintf('Mot de passe réinitialisé pour l\'utilisateur "%s".', $email));
        $io->note(sprintf('Nouveau mot de passe : %s', $password));
        $io->warning('⚠️  Notez ce mot de passe, il ne sera plus affiché !');

        return Command::SUCCESS;
    }
}

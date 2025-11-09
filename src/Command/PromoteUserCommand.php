<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:promote-user',
    description: 'Promouvoir un utilisateur en ajoutant un rôle (ex: ROLE_ADMIN)',
)]
class PromoteUserCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Email de l\'utilisateur à promouvoir')
            ->addArgument('role', InputArgument::REQUIRED, 'Rôle à ajouter (ex: ROLE_ADMIN)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = $input->getArgument('email');
        $role = strtoupper($input->getArgument('role'));

        // Vérifier que le rôle commence par ROLE_
        if (!str_starts_with($role, 'ROLE_')) {
            $io->error('Le rôle doit commencer par "ROLE_" (ex: ROLE_ADMIN)');
            return Command::FAILURE;
        }

        // Rechercher l'utilisateur
        $user = $this->userRepository->findOneBy(['email' => $email]);

        if (!$user) {
            $io->error(sprintf('Utilisateur avec l\'email "%s" introuvable.', $email));
            return Command::FAILURE;
        }

        // Récupérer les rôles actuels
        $roles = $user->getRoles();
        
        // Retirer ROLE_USER de la liste pour ne garder que les rôles personnalisés
        $roles = array_filter($roles, fn($r) => $r !== 'ROLE_USER');

        // Vérifier si le rôle existe déjà
        if (in_array($role, $roles, true)) {
            $io->warning(sprintf('L\'utilisateur "%s" possède déjà le rôle "%s".', $email, $role));
            return Command::SUCCESS;
        }

        // Ajouter le nouveau rôle
        $roles[] = $role;
        $user->setRoles(array_values($roles));
        
        $this->entityManager->flush();

        $io->success(sprintf('Rôle "%s" ajouté à l\'utilisateur "%s".', $role, $email));
        $io->note(sprintf('Rôles actuels : %s', implode(', ', $user->getRoles())));

        return Command::SUCCESS;
    }
}

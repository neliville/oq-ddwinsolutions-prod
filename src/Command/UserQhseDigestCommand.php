<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\UserPreferencesRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:user:qhse-digest',
    description: 'Liste les utilisateurs éligibles au digest QHSE hebdomadaire (dry-run par défaut, aucun email).',
)]
final class UserQhseDigestCommand extends Command
{
    public function __construct(
        private readonly UserPreferencesRepository $userPreferencesRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Ne rien envoyer (comportement actuel ; seul mode supporté).');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $users = $this->userPreferencesRepository->findUsersEligibleForWeeklyDigest();

        $io->title('Digest QHSE hebdomadaire');
        if (!$input->getOption('dry-run')) {
            $io->warning('Précisez --dry-run (recommandé) : aucun envoi n’est implémenté pour l’instant.');
        }
        $io->note('Dry-run : aucun email n’est envoyé. Utiliser MailerService::sendWeeklyQhseDigest() depuis un job planifié une fois le contenu métier prêt.');

        if ($users === []) {
            $io->success('Aucun utilisateur éligible (synthèse hebdo + fréquence « hebdomadaire »).');

            return Command::SUCCESS;
        }

        $io->listing(array_map(static fn ($u) => $u->getEmail() ?? '(sans email)', $users));
        $io->success(sprintf('%d utilisateur(s) éligible(s).', count($users)));

        return Command::SUCCESS;
    }
}

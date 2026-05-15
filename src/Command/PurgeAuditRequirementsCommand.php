<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Qse\AuditRequirement;
use App\Entity\Qse\AuditStandard;
use App\Repository\Qse\AuditStandardRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:qse:purge-audit-requirements',
    description: 'Supprime toutes les exigences audit pour les référentiels indiqués (évaluations liées supprimées en cascade). N’affecte pas les audits eux-mêmes.',
)]
final class PurgeAuditRequirementsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AuditStandardRepository $auditStandardRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'standards',
            null,
            InputOption::VALUE_REQUIRED,
            'Codes référentiels séparés par des virgules (ex. iso_14001,iso_45001)',
            'iso_14001,iso_45001',
        );
        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'Confirmer la suppression sans interaction');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $codes = array_values(array_filter(array_map('trim', explode(',', (string) $input->getOption('standards')))));
        if ($codes === []) {
            $io->error('Option --standards vide.');

            return Command::FAILURE;
        }

        if (\in_array('iso_9001', $codes, true)) {
            $io->error('La purge de iso_9001 est interdite par cette commande. Retirez iso_9001 de --standards.');

            return Command::FAILURE;
        }

        $standards = [];
        foreach ($codes as $code) {
            $std = $this->auditStandardRepository->findOneByCode($code);
            if (!$std instanceof AuditStandard) {
                $io->error('Référentiel introuvable : ' . $code);

                return Command::FAILURE;
            }
            $standards[] = $std;
        }

        $repo = $this->entityManager->getRepository(AuditRequirement::class);
        $total = 0;
        foreach ($standards as $std) {
            $total += (int) $repo->count(['auditStandard' => $std]);
        }

        if ($total === 0) {
            $io->success('Aucune exigence à supprimer pour : ' . implode(', ', $codes));

            return Command::SUCCESS;
        }

        $io->warning(sprintf(
            '%d exigence(s) seront supprimées pour : %s (évaluations d’audit associées incluses).',
            $total,
            implode(', ', $codes),
        ));

        if (!$input->getOption('force') && !$io->confirm('Continuer ?', false)) {
            return Command::SUCCESS;
        }

        foreach ($standards as $std) {
            $qb = $this->entityManager->createQueryBuilder()
                ->delete(AuditRequirement::class, 'r')
                ->where('r.auditStandard = :std')
                ->setParameter('std', $std);
            $deleted = $qb->getQuery()->execute();
            $io->writeln(sprintf('  %s : %d ligne(s) supprimée(s).', $std->getCode(), $deleted));
        }

        $this->entityManager->clear();
        $io->success('Purge terminée.');

        return Command::SUCCESS;
    }
}

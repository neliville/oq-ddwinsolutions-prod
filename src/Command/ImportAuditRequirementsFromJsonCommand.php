<?php

declare(strict_types=1);

namespace App\Command;

use App\Qse\Import\AuditRequirementUpserter;
use App\Repository\Qse\AuditStandardRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:qse:import-audit-requirements-json',
    description: 'Importe des exigences audit depuis un fichier JSON (par code de référentiel, ex. iso_14001).',
)]
final class ImportAuditRequirementsFromJsonCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AuditStandardRepository $auditStandardRepository,
        private readonly AuditRequirementUpserter $upserter,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('file', InputArgument::REQUIRED, 'Chemin du fichier JSON');
        $this->addOption('standard', null, InputOption::VALUE_REQUIRED, 'Code référentiel (ex. iso_14001)', 'iso_14001');
        $this->addOption('source-version', null, InputOption::VALUE_OPTIONAL, 'Libellé de version source', null);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $code = (string) $input->getOption('standard');
        $standard = $this->auditStandardRepository->findOneByCode($code);
        if ($standard === null) {
            $io->error('Référentiel inconnu : ' . $code);

            return Command::FAILURE;
        }
        $path = (string) $input->getArgument('file');
        if (!is_readable($path)) {
            $io->error('Fichier illisible : ' . $path);

            return Command::FAILURE;
        }
        $json = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        if (!\is_array($json)) {
            $io->error('JSON invalide : objet racine attendu.');

            return Command::FAILURE;
        }
        if (isset($json['rows']) && \is_array($json['rows'])) {
            $rows = $json['rows'];
        } elseif (array_is_list($json)) {
            $rows = $json;
        } else {
            $io->error('JSON invalide : tableau de lignes ou clé "rows" attendue.');

            return Command::FAILURE;
        }
        $sourceVersion = $input->getOption('source-version');
        $sourceVersion = \is_string($sourceVersion) && $sourceVersion !== '' ? $sourceVersion : null;
        $counts = $this->upserter->upsertRows($standard, $rows, $sourceVersion);
        $io->success(sprintf('Import %s : %d créations, %d mises à jour.', $code, $counts['inserted'], $counts['updated']));

        return Command::SUCCESS;
    }
}

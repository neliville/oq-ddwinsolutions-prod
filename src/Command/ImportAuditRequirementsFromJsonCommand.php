<?php

declare(strict_types=1);

namespace App\Command;

use App\Qse\Import\AuditRequirementsJsonDocumentParser;
use App\Qse\Import\AuditRequirementUpserter;
use App\Repository\Qse\AuditStandardRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:qse:import-audit-requirements-json',
    description: 'Importe des exigences audit depuis un fichier JSON (formats : lignes, DDWin exigences[], chapitres ISO 9001).',
)]
final class ImportAuditRequirementsFromJsonCommand extends Command
{
    public function __construct(
        private readonly AuditStandardRepository $auditStandardRepository,
        private readonly AuditRequirementsJsonDocumentParser $documentParser,
        private readonly AuditRequirementUpserter $upserter,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('file', InputArgument::REQUIRED, 'Chemin du fichier JSON');
        $this->addOption(
            'standard',
            null,
            InputOption::VALUE_OPTIONAL,
            'Code référentiel (ex. iso_14001). Optionnel si le JSON contient « onglet » (14001/45001).',
            null,
        );
        $this->addOption('source-version', null, InputOption::VALUE_OPTIONAL, 'Libellé de version source', null);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $path = (string) $input->getArgument('file');
        if (!is_readable($path)) {
            $io->error('Fichier illisible : ' . $path);

            return Command::FAILURE;
        }

        $standardOpt = $input->getOption('standard');
        $standardOverride = \is_string($standardOpt) && $standardOpt !== '' ? $standardOpt : null;

        try {
            $json = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
            if (!\is_array($json)) {
                throw new \InvalidArgumentException('JSON invalide : objet racine attendu.');
            }
            $parsed = $this->documentParser->parse($json, $standardOverride);
        } catch (\Throwable $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        $code = $parsed->standardCode;
        if ($code === 'iso_9001' && $standardOverride !== null && $standardOverride !== 'iso_9001') {
            $io->error('Ce fichier est destiné à iso_9001 ; utilisez app:qse:import-iso9001-requirements pour éviter les écrasements involontaires.');

            return Command::FAILURE;
        }

        $standard = $this->auditStandardRepository->findOneByCode($code);
        if ($standard === null) {
            $io->error('Référentiel inconnu : ' . $code);

            return Command::FAILURE;
        }

        $sourceVersion = $input->getOption('source-version');
        $sourceVersion = \is_string($sourceVersion) && $sourceVersion !== '' ? $sourceVersion : basename($path);

        $counts = $this->upserter->upsertRows($standard, $parsed->rows, $sourceVersion);
        $io->success(sprintf(
            'Import %s : %d lignes, %d créations, %d mises à jour.',
            $code,
            \count($parsed->rows),
            $counts['inserted'],
            $counts['updated'],
        ));

        return Command::SUCCESS;
    }
}

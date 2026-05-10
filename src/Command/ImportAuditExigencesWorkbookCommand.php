<?php

declare(strict_types=1);

namespace App\Command;

use App\Qse\Import\AuditExigencesWorkbookImporter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'app:qse:import-audit-exigences-workbook',
    description: 'Importe les exigences depuis le classeur Excel (onglets 9001 / 14001 / 45001). Par défaut : 14001 et 45001 uniquement (ISO 9001 : commande app:qse:import-iso9001-requirements).',
)]
final class ImportAuditExigencesWorkbookCommand extends Command
{
    public function __construct(
        private readonly AuditExigencesWorkbookImporter $workbookImporter,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $defaultFile = $this->projectDir . '/' . AuditExigencesWorkbookImporter::DEFAULT_FILENAME;
        $this->addOption('file', null, InputOption::VALUE_OPTIONAL, 'Chemin absolu du fichier .xlsx', $defaultFile);
        $this->addOption(
            'tabs',
            null,
            InputOption::VALUE_OPTIONAL,
            'Onglets à importer, séparés par des virgules (9001, 14001, 45001). Défaut : 14001,45001.',
            '14001,45001',
        );
        $this->addOption('source-version', null, InputOption::VALUE_OPTIONAL, 'Libellé de version source (sinon : nom du fichier)', null);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $path = (string) $input->getOption('file');
        if (!is_readable($path)) {
            $io->error('Fichier introuvable ou illisible : ' . $path);

            return Command::FAILURE;
        }
        $tabsRaw = (string) $input->getOption('tabs');
        $tabs = array_values(array_filter(array_map('trim', explode(',', $tabsRaw))));
        if ($tabs === []) {
            $io->error('Option --tabs vide : indiquez au moins un onglet (9001, 14001 ou 45001).');

            return Command::FAILURE;
        }
        $sourceVersion = $input->getOption('source-version');
        $sourceVersion = \is_string($sourceVersion) && $sourceVersion !== '' ? $sourceVersion : null;

        try {
            $results = $this->workbookImporter->import($path, $tabs, $sourceVersion);
        } catch (\Throwable $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        foreach ($results as $tab => $stats) {
            $io->writeln(sprintf(
                'Onglet %s : %d lignes lues, %d créations, %d mises à jour.',
                $tab,
                (int) ($stats['rows'] ?? 0),
                (int) ($stats['inserted'] ?? 0),
                (int) ($stats['updated'] ?? 0),
            ));
        }
        $io->success('Import classeur terminé.');

        return Command::SUCCESS;
    }
}

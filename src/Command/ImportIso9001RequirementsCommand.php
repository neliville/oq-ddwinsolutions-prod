<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Qse\AuditRequirement;
use App\Qse\Iso9001\ChapitresDataExtractor;
use App\Repository\Qse\AuditStandardRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(
    name: 'app:qse:import-iso9001-requirements',
    description: 'Importe ou met à jour le référentiel ISO 9001 (exigences) depuis le HTML de référence ou un JSON extrait.',
)]
final class ImportIso9001RequirementsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AuditStandardRepository $auditStandardRepository,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('html-path', null, InputOption::VALUE_OPTIONAL, 'Chemin du fichier HTML', null);
        $this->addOption('json-out', null, InputOption::VALUE_OPTIONAL, 'Écrit le JSON extrait vers ce fichier', null);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $standard = $this->auditStandardRepository->findOneByCode('iso_9001');
        if ($standard === null) {
            $io->error('Référentiel iso_9001 introuvable. Exécutez les migrations puis vérifiez qse_audit_standard.');

            return Command::FAILURE;
        }
        $htmlPath = $input->getOption('html-path')
            ?: $this->projectDir . '/Audit_ISO9001_COMPLET_.html';
        if (!is_readable($htmlPath)) {
            $io->error('Fichier introuvable ou illisible : ' . $htmlPath);

            return Command::FAILURE;
        }
        $html = (string) file_get_contents($htmlPath);
        try {
            $chapters = ChapitresDataExtractor::extractFromHtml($html);
        } catch (\Throwable $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        $jsonOut = $input->getOption('json-out')
            ?: $this->projectDir . '/data/fixtures/iso9001_requirements.json';
        $fs = new Filesystem();
        $fs->mkdir(\dirname($jsonOut));
        file_put_contents($jsonOut, json_encode($chapters, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $io->note('JSON écrit : ' . $jsonOut);

        $repo = $this->entityManager->getRepository(AuditRequirement::class);
        $inserted = 0;
        $updated = 0;
        foreach ($chapters as $chapterName => $rows) {
            $order = 0;
            foreach ($rows as $row) {
                ++$order;
                $legacyKey = $row['id'];
                $req = $repo->findOneBy(['auditStandard' => $standard, 'legacyKey' => $legacyKey]);
                if (!$req instanceof AuditRequirement) {
                    $req = new AuditRequirement();
                    $req->setLegacyKey($legacyKey);
                    $req->setAuditStandard($standard);
                    $this->entityManager->persist($req);
                    ++$inserted;
                } else {
                    ++$updated;
                }
                $req->setChapter($chapterName);
                $req->setIsoArticle($row['article']);
                $req->setRequirementText($row['exigence']);
                $req->setIsoComment($row['commentaire'] ?? null);
                $req->setDisplayOrder($row['numero'] > 0 ? $row['numero'] : $order);
                $req->setActive(true);
                $req->setRequirementUpdatedAt(new \DateTimeImmutable());
            }
        }
        $this->entityManager->flush();
        $io->success(sprintf('Import terminé : %d créations, %d mises à jour (clés legacy par référentiel).', $inserted, $updated));

        return Command::SUCCESS;
    }
}

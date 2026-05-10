<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Qse\AuditStandard;
use App\Qse\Import\AuditExigencesWorkbookImporter;
use App\Qse\Import\AuditRequirementExcelReader;
use App\Qse\Import\AuditRequirementImportPreviewer;
use App\Qse\Import\AuditRequirementRowNormalizer;
use App\Qse\Import\AuditRequirementUpserter;
use App\Repository\Qse\AuditStandardRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/qse/import/requirements', name: 'app_admin_qse_req_import_')]
#[IsGranted('ROLE_ADMIN')]
final class AdminQseAuditRequirementImportController extends AbstractController
{
    private const MAX_BYTES = 5_242_880;

    public function __construct(
        private readonly AuditStandardRepository $auditStandardRepository,
        private readonly AuditExigencesWorkbookImporter $workbookImporter,
        private readonly AuditRequirementExcelReader $excelReader,
        private readonly AuditRequirementImportPreviewer $previewService,
        private readonly AuditRequirementRowNormalizer $normalizer,
        private readonly AuditRequirementUpserter $upserter,
        private readonly EntityManagerInterface $entityManager,
        #[Autowire('%kernel.cache_dir%')]
        private readonly string $cacheDir,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('admin_qse_req_import_upload', (string) $request->request->get('_token'))) {
                throw $this->createAccessDeniedException('Jeton CSRF invalide.');
            }
            $standardId = $request->request->getInt('standard_id');
            $standard = $standardId > 0 ? $this->auditStandardRepository->find($standardId) : null;
            if (!$standard instanceof AuditStandard) {
                $this->addFlash('danger', 'Référentiel invalide.');

                return $this->redirectToRoute('app_admin_qse_req_import_index');
            }
            $file = $request->files->get('import_file');
            if ($file === null || !method_exists($file, 'getPathname')) {
                $this->addFlash('danger', 'Fichier manquant.');

                return $this->redirectToRoute('app_admin_qse_req_import_index');
            }
            $size = $file->getSize();
            if ($size !== false && $size > self::MAX_BYTES) {
                $this->addFlash('danger', 'Fichier trop volumineux (max 5 Mo).');

                return $this->redirectToRoute('app_admin_qse_req_import_index');
            }
            $path = $file->getPathname();
            $orig = (string) $file->getClientOriginalName();
            $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
            try {
                $rows = match ($ext) {
                    'json' => $this->decodeJsonFile($path),
                    'xlsx', 'xls' => $this->readSpreadsheetRows($path, $standard),
                    default => throw new \InvalidArgumentException('Extension acceptée : .json, .xlsx, .xls.'),
                };
            } catch (\Throwable $e) {
                $this->addFlash('danger', 'Lecture impossible : ' . $e->getMessage());

                return $this->redirectToRoute('app_admin_qse_req_import_index');
            }
            $sourceVersion = $request->request->getString('source_version');
            $sourceVersion = $sourceVersion !== '' ? $sourceVersion : null;
            $token = bin2hex(random_bytes(16));
            $payloadPath = $this->cacheDir . '/qse_req_import_' . $token . '.json';
            file_put_contents($payloadPath, json_encode([
                'standard_id' => $standard->getId(),
                'rows' => $rows,
                'source_version' => $sourceVersion,
                'filename' => $orig,
            ], JSON_THROW_ON_ERROR));

            return $this->redirectToRoute('app_admin_qse_req_import_preview', ['token' => $token]);
        }

        return $this->render('admin/qse/audit_requirements/import.html.twig', [
            'standards' => $this->auditStandardRepository->findBy([], ['displayOrder' => 'ASC']),
        ]);
    }

    #[Route('/preview/{token}', name: 'preview', requirements: ['token' => '[a-f0-9]{32}'], methods: ['GET'])]
    public function preview(string $token): Response
    {
        $data = $this->loadPayload($token);
        if ($data === null) {
            throw $this->createNotFoundException();
        }
        $standard = $this->auditStandardRepository->find((int) $data['standard_id']);
        if (!$standard instanceof AuditStandard) {
            throw $this->createNotFoundException();
        }
        /** @var list<array<string, mixed>> $rows */
        $rows = $data['rows'];
        $preview = $this->previewService->preview($standard, $rows);

        return $this->render('admin/qse/audit_requirements/import_preview.html.twig', [
            'token' => $token,
            'standard' => $standard,
            'filename' => (string) ($data['filename'] ?? ''),
            'preview' => $preview,
            'rowCount' => \count($rows),
        ]);
    }

    #[Route('/apply/{token}', name: 'apply', requirements: ['token' => '[a-f0-9]{32}'], methods: ['POST'])]
    public function apply(Request $request, string $token): Response
    {
        if (!$this->isCsrfTokenValid('admin_qse_req_import_apply', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }
        $data = $this->loadPayload($token);
        if ($data === null) {
            throw $this->createNotFoundException();
        }
        $standard = $this->auditStandardRepository->find((int) $data['standard_id']);
        if (!$standard instanceof AuditStandard) {
            throw $this->createNotFoundException();
        }
        /** @var list<array<string, mixed>> $rows */
        $rows = $data['rows'];
        $sourceVersion = isset($data['source_version']) && \is_string($data['source_version']) ? $data['source_version'] : null;

        foreach ($rows as $i => $raw) {
            if (!\is_array($raw)) {
                $this->addFlash('danger', sprintf('Ligne %d invalide.', $i + 1));

                return $this->redirectToRoute('app_admin_qse_req_import_preview', ['token' => $token]);
            }
            try {
                $this->normalizer->normalize($raw);
            } catch (\InvalidArgumentException $e) {
                $this->addFlash('danger', sprintf('Ligne %d : %s', $i + 1, $e->getMessage()));

                return $this->redirectToRoute('app_admin_qse_req_import_preview', ['token' => $token]);
            }
        }

        $this->entityManager->wrapInTransaction(function () use ($standard, $rows, $sourceVersion): void {
            $this->upserter->upsertRows($standard, $rows, $sourceVersion);
        });

        $path = $this->payloadPath($token);
        if (is_file($path)) {
            unlink($path);
        }
        $this->addFlash('success', 'Import appliqué en base.');

        return $this->redirectToRoute('app_admin_qse_audit_requirements_index', [
            'standard' => $standard->getId(),
        ]);
    }

    /**
     * @return ?array{standard_id: int, rows: list<array<string, mixed>>, source_version: ?string, filename: ?string}
     */
    private function loadPayload(string $token): ?array
    {
        $path = $this->payloadPath($token);
        if (!is_readable($path)) {
            return null;
        }
        $raw = json_decode((string) file_get_contents($path), true);
        if (!\is_array($raw) || !isset($raw['standard_id'], $raw['rows']) || !\is_array($raw['rows'])) {
            return null;
        }

        return [
            'standard_id' => (int) $raw['standard_id'],
            'rows' => $raw['rows'],
            'source_version' => isset($raw['source_version']) && \is_string($raw['source_version']) ? $raw['source_version'] : null,
            'filename' => isset($raw['filename']) ? (string) $raw['filename'] : null,
        ];
    }

    private function payloadPath(string $token): string
    {
        return $this->cacheDir . '/qse_req_import_' . $token . '.json';
    }

    /**
     * @return list<array<string, mixed>>
     */
    /**
     * Classeur « Exigences… » multi-onglets : lecture de l’onglet aligné sur le référentiel choisi (clés legacy générées).
     *
     * @return list<array<string, mixed>>
     */
    private function readSpreadsheetRows(string $absolutePath, AuditStandard $standard): array
    {
        $fromWorkbook = $this->workbookImporter->tryReadDdwinWorkbookRows($absolutePath, $standard);
        if ($fromWorkbook !== null) {
            return $fromWorkbook;
        }

        return $this->excelReader->readRows($absolutePath);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function decodeJsonFile(string $path): array
    {
        $json = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        if (!\is_array($json)) {
            throw new \InvalidArgumentException('JSON invalide.');
        }
        if (isset($json['rows']) && \is_array($json['rows'])) {
            return $json['rows'];
        }
        if (array_is_list($json)) {
            return $json;
        }

        throw new \InvalidArgumentException('JSON : tableau de lignes ou clé "rows" attendue.');
    }
}

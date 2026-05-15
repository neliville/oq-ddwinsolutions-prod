<?php

declare(strict_types=1);

namespace App\Controller\Qse;

use App\Application\Analytics\TrackingEventRecorder;
use App\Application\Analytics\TrackingEventType;
use App\Qse\Enum\AuditExecutionStatus;
use App\Entity\User;
use App\Entity\Qse\Audit;
use App\Entity\Qse\AuditEvaluation;
use App\Entity\Qse\AuditStandard;
use App\Qse\Audit\ViewModel\AuditCockpitViewModelFactory;
use App\Qse\Enum\AuditVerdict;
use App\Qse\Event\AuditEvaluationSavedEvent;
use App\Qse\Export\AuditDocumentExporter;
use App\Qse\Export\AuditSpreadsheetExporter;
use App\Qse\Service\AuditComplianceCalculator;
use App\Qse\Service\AuditEvaluationCapaFactory;
use App\Qse\Service\AuditEvaluationVerdictHelper;
use App\Repository\Qse\AuditEvaluationRepository;
use App\Repository\Qse\AuditRepository;
use App\Repository\Qse\CAPAActionRepository;
use App\Repository\Qse\AuditRequirementRepository;
use App\Repository\Qse\AuditStandardRepository;
use App\Repository\UserPreferencesRepository;
use App\Service\Export\ExportBrandingResolver;
use App\Service\Onboarding\OnboardingActivationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[Route('/dashboard/qse/audit', name: 'app_qse_audit_')]
#[IsGranted('ROLE_USER')]
final class QseAuditController extends AbstractController
{
    public function __construct(
        private readonly AuditRepository $auditRepository,
        private readonly CAPAActionRepository $capaActionRepository,
        private readonly AuditRequirementRepository $requirementRepository,
        private readonly AuditEvaluationRepository $evaluationRepository,
        private readonly AuditStandardRepository $auditStandardRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly AuditComplianceCalculator $complianceCalculator,
        private readonly AuditEvaluationCapaFactory $capaFactory,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly TrackingEventRecorder $trackingEventRecorder,
        private readonly UserPreferencesRepository $userPreferencesRepository,
        private readonly OnboardingActivationService $onboardingActivationService,
        private readonly ExportBrandingResolver $exportBrandingResolver,
        private readonly AuditCockpitViewModelFactory $auditCockpitViewModelFactory,
        private readonly AuditSpreadsheetExporter $auditSpreadsheetExporter,
        private readonly AuditDocumentExporter $auditDocumentExporter,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        $user = $this->getUser();
        if (!\is_object($user)) {
            throw $this->createAccessDeniedException();
        }

        $audits = $this->auditRepository->findByOwner($user);
        $ids = array_values(array_filter(array_map(static fn (Audit $a): ?int => $a->getId(), $audits)));
        $auditCapaCounts = $this->capaActionRepository->countLinkedToAuditIds($ids);

        return $this->render('qse/audit/index.html.twig', [
            'audits' => $audits,
            'audit_capa_counts' => $auditCapaCounts,
        ]);
    }

    #[Route('/referentiel', name: 'pick_standard', methods: ['GET'])]
    public function pickStandard(): Response
    {
        $user = $this->getUser();
        if (!\is_object($user)) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('qse/audit/pick_standard.html.twig', [
            'standards' => $this->auditStandardRepository->findVisibleOrdered(),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $user = $this->getUser();
        if (!\is_object($user)) {
            throw $this->createAccessDeniedException();
        }

        $standardId = $request->query->getInt('standard');
        if ($request->isMethod('GET') && $standardId <= 0) {
            return $this->redirectToRoute('app_qse_audit_pick_standard');
        }
        $standard = $standardId > 0 ? $this->auditStandardRepository->find($standardId) : null;
        if (!$standard instanceof AuditStandard || !$standard->isActive() || !$standard->isVisible()) {
            $this->addFlash('danger', 'Référentiel invalide ou indisponible.');

            return $this->redirectToRoute('app_qse_audit_pick_standard');
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('qse_audit_new', (string) $request->request->get('_token'))) {
                throw $this->createAccessDeniedException('Jeton CSRF invalide.');
            }
            $postedStandardId = $request->request->getInt('audit_standard_id');
            $postedStandard = $postedStandardId > 0 ? $this->auditStandardRepository->find($postedStandardId) : null;
            if (!$postedStandard instanceof AuditStandard || $postedStandard->getId() !== $standard->getId()) {
                throw $this->createAccessDeniedException('Référentiel incohérent.');
            }
            $audit = new Audit();
            $audit->setOwner($user);
            $audit->setAuditStandard($postedStandard);
            $audit->setCompanyName($request->request->getString('companyName') ?: null);
            $audit->setMainAuditor($request->request->getString('mainAuditor') ?: null);
            $audit->setAuditedAt(new \DateTimeImmutable($request->request->getString('auditedAt') ?: 'today'));
            $audit->setAuditVersion($request->request->getString('auditVersion') ?: '1.0');
            $audit->setScope($request->request->getString('scope') ?: null);
            $audit->setObjective($request->request->getString('objective') ?: null);
            $audit->setConcernedSite($request->request->getString('concernedSite') ?: null);
            $audit->setConcernedProcess($request->request->getString('concernedProcess') ?: null);
            $this->entityManager->persist($audit);
            $this->entityManager->flush();

            $this->trackingEventRecorder->record(
                TrackingEventType::AUDIT_CREATED,
                [
                    'audit_id' => $audit->getId(),
                    'standard_id' => $postedStandard->getId(),
                    'standard_code' => $postedStandard->getCode(),
                ],
                $user,
                null,
                'create',
                'web',
            );

            if ($this->isOnboardingOrigin($request) && $user instanceof User) {
                $preferences = $this->userPreferencesRepository->getOrCreateForUser($user);
                $this->onboardingActivationService->markFirstActionCompleted($preferences);
                $this->entityManager->flush();

                return $this->redirectToRoute('app_dashboard_index', ['activation' => 'audit_created']);
            }

            return $this->redirectToRoute('app_qse_audit_show', ['id' => $audit->getId()]);
        }

        return $this->render('qse/audit/new.html.twig', [
            'standard' => $standard,
        ]);
    }

    #[Route('/{id}/export.xlsx', name: 'export_xlsx', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function exportXlsx(int $id): Response
    {
        $user = $this->getUser();
        if (!\is_object($user)) {
            throw $this->createAccessDeniedException();
        }
        $audit = $this->auditRepository->findOneOwnedBy($id, $user);
        if (!$audit instanceof Audit) {
            throw $this->createNotFoundException();
        }
        $evaluationsByReqId = [];
        foreach ($audit->getEvaluations() as $ev) {
            $rid = $ev->getRequirement()?->getId();
            if ($rid !== null) {
                $evaluationsByReqId[$rid] = $ev;
            }
        }
        $metrics = $this->auditCockpitViewModelFactory->build($audit);
        $spreadsheet = $this->auditSpreadsheetExporter->build($audit, $metrics, $evaluationsByReqId);

        $this->trackingEventRecorder->record(
            TrackingEventType::EXPORT_TRIGGERED,
            [
                'resource_type' => 'qse_audit',
                'resource_id' => $audit->getId(),
                'format' => 'xlsx',
            ],
            $user,
            'qse_audit',
            'export_xlsx',
            'web',
        );

        $filename = 'audit-' . $audit->getId() . '.xlsx';

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $buffer = fopen('php://temp', 'r+');
        if ($buffer === false) {
            throw new \RuntimeException('Buffer mémoire indisponible pour l’export XLSX.');
        }
        $writer->save($buffer);
        rewind($buffer);
        $content = stream_get_contents($buffer) ?: '';
        fclose($buffer);

        return new Response($content, Response::HTTP_OK, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    #[Route('/{id}/export.docx', name: 'export_docx', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function exportDocx(int $id, Request $request): Response
    {
        $user = $this->getUser();
        if (!\is_object($user)) {
            throw $this->createAccessDeniedException();
        }
        $audit = $this->auditRepository->findOneOwnedBy($id, $user);
        if (!$audit instanceof Audit) {
            throw $this->createNotFoundException();
        }
        $variant = $request->query->getString('variant');
        if (!\in_array($variant, ['direction', 'terrain', 'certification'], true)) {
            $variant = 'direction';
        }
        $evaluationsByReqId = [];
        foreach ($audit->getEvaluations() as $ev) {
            $rid = $ev->getRequirement()?->getId();
            if ($rid !== null) {
                $evaluationsByReqId[$rid] = $ev;
            }
        }
        $metrics = $this->auditCockpitViewModelFactory->build($audit);
        $branding = $this->exportBrandingResolver->resolveForUser($user instanceof User ? $user : null);
        $binary = $this->auditDocumentExporter->writeDocxToString($audit, $metrics, $evaluationsByReqId, $branding, $variant);

        $this->trackingEventRecorder->record(
            TrackingEventType::EXPORT_TRIGGERED,
            [
                'resource_type' => 'qse_audit',
                'resource_id' => $audit->getId(),
                'format' => 'docx',
                'variant' => $variant,
            ],
            $user,
            'qse_audit',
            'export_docx',
            'web',
        );

        $filename = sprintf('audit-%d-%s.docx', $audit->getId(), $variant);

        return new Response($binary, Response::HTTP_OK, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(int $id, Request $request): Response
    {
        $user = $this->getUser();
        if (!\is_object($user)) {
            throw $this->createAccessDeniedException();
        }
        $audit = $this->auditRepository->findOneOwnedBy($id, $user);
        if (!$audit instanceof Audit) {
            throw $this->createNotFoundException();
        }
        $std = $audit->getAuditStandard();
        if (!$std instanceof AuditStandard) {
            throw $this->createNotFoundException('Référentiel manquant.');
        }
        $chapters = $this->requirementRepository->findDistinctChaptersForStandard($std);
        $chapter = $request->query->getString('chapter');
        if ($chapter === '' && $chapters !== []) {
            $chapter = $chapters[0];
        }
        if ($chapter !== '' && !$this->requirementRepository->chapterExistsForStandard($chapter, $std)) {
            $chapter = $chapters[0] ?? '';
        }

        $requirements = $chapter !== ''
            ? $this->requirementRepository->findByChapterOrderedForStandard($chapter, $std)
            : [];
        $evaluationsByReqId = [];
        foreach ($audit->getEvaluations() as $ev) {
            $rid = $ev->getRequirement()?->getId();
            if ($rid !== null) {
                $evaluationsByReqId[$rid] = $ev;
            }
        }

        $cockpitMetrics = $this->auditCockpitViewModelFactory->build($audit);
        $chartConfigJson = json_encode($cockpitMetrics->chartConfig, \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_UNICODE);

        if (in_array($audit->getStatus(), [AuditExecutionStatus::TERMINE, AuditExecutionStatus::VALIDE], true)) {
            $this->trackingEventRecorder->record(
                TrackingEventType::AUDIT_COMPLETED,
                ['audit_id' => $audit->getId()],
                $user,
                'qse_audit',
                'show_completed',
                'web',
            );
        }

        return $this->render('qse/audit/show.html.twig', [
            'audit' => $audit,
            'chapters' => $chapters,
            'currentChapter' => $chapter,
            'showRequirementsForm' => $chapter !== '',
            'requirements' => $requirements,
            'evaluationsByReqId' => $evaluationsByReqId,
            'cockpitMetrics' => $cockpitMetrics,
            'chartConfigJson' => $chartConfigJson,
        ]);
    }

    #[Route('/{id}/chapter', name: 'save_chapter', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function saveChapter(int $id, Request $request): Response
    {
        $user = $this->getUser();
        if (!\is_object($user)) {
            throw $this->createAccessDeniedException();
        }
        $audit = $this->auditRepository->findOneOwnedBy($id, $user);
        if (!$audit instanceof Audit) {
            throw $this->createNotFoundException();
        }
        if (!$this->isCsrfTokenValid('qse_audit_chapter', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }
        $std = $audit->getAuditStandard();
        if (!$std instanceof AuditStandard) {
            throw $this->createNotFoundException();
        }
        $chapter = $request->request->getString('chapter');
        if (!$this->requirementRepository->chapterExistsForStandard($chapter, $std)) {
            $this->addFlash('danger', 'Chapitre invalide pour ce référentiel.');

            return $this->redirectToRoute('app_qse_audit_show', ['id' => $audit->getId()]);
        }
        $all = $request->request->all();
        $rows = $all['eval'] ?? [];
        if (!\is_array($rows)) {
            $rows = [];
        }
        foreach ($rows as $reqId => $payload) {
            if (!\is_array($payload)) {
                continue;
            }
            $reqIdInt = (int) $reqId;
            $req = $this->requirementRepository->find($reqIdInt);
            if ($req === null || $req->getChapter() !== $chapter || $req->getAuditStandard()?->getId() !== $std->getId()) {
                continue;
            }
            $verdictRaw = isset($payload['verdict']) ? trim((string) $payload['verdict']) : '';
            $verdict = $verdictRaw !== '' ? AuditVerdict::tryFrom($verdictRaw) : AuditVerdict::NOT_EVALUATED;
            if ($verdictRaw !== '' && $verdict === null) {
                continue;
            }
            $ev = $this->evaluationRepository->findOneByAuditAndRequirement($audit, $req);
            if (!$ev instanceof AuditEvaluation) {
                $ev = new AuditEvaluation();
                $ev->setAudit($audit);
                $ev->setRequirement($req);
                $ev->setOwner($user);
                $this->entityManager->persist($ev);
            }
            if ($verdict !== null) {
                $ev->setVerdict($verdict);
                AuditEvaluationVerdictHelper::syncLegacyScore($ev);
            }
            $ev->setAuditComment(isset($payload['comment']) ? (string) $payload['comment'] : null);
            $ev->setEvidence(isset($payload['evidence']) ? (string) $payload['evidence'] : null);
            $fieldObs = isset($payload['field_observation']) ? trim((string) $payload['field_observation']) : '';
            $ev->setFieldObservation($fieldObs !== '' ? $fieldObs : null);
            $crit = isset($payload['criticality']) ? trim((string) $payload['criticality']) : '';
            if (mb_strlen($crit) > 50) {
                $crit = mb_substr($crit, 0, 50);
            }
            $ev->setCriticality($crit !== '' ? $crit : null);
            $this->eventDispatcher->dispatch(new AuditEvaluationSavedEvent($ev));
        }
        $this->complianceCalculator->recalculate($audit);
        $this->entityManager->flush();

        $this->addFlash('success', 'Évaluations du chapitre enregistrées.');

        return $this->redirectToRoute('app_qse_audit_show', [
            'id' => $audit->getId(),
            'chapter' => $chapter,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, int $id): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }
        if (!$this->isCsrfTokenValid('delete_qse_audit_' . $id, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }
        $audit = $this->auditRepository->findOneOwnedBy($id, $user);
        if (!$audit instanceof Audit) {
            throw $this->createNotFoundException();
        }
        $this->entityManager->remove($audit);
        $this->entityManager->flush();

        if ($request->isXmlHttpRequest()) {
            return $this->json([
                'success' => true,
                'message' => 'L’audit a été supprimé.',
                'redirect' => $this->generateUrl('app_qse_audit_index'),
            ]);
        }

        $this->addFlash('success', 'L’audit a été supprimé.');

        return $this->redirectToRoute('app_qse_audit_index');
    }

    #[Route('/{id}/suggest-capa/{evaluationId}', name: 'suggest_capa', requirements: ['id' => '\d+', 'evaluationId' => '\d+'], methods: ['GET'])]
    public function suggestCapa(int $id, int $evaluationId): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }
        $audit = $this->auditRepository->findOneOwnedBy($id, $user);
        if (!$audit instanceof Audit) {
            throw $this->createNotFoundException();
        }
        $ev = $this->evaluationRepository->find($evaluationId);
        if (!$ev instanceof AuditEvaluation || $ev->getAudit()?->getId() !== $audit->getId() || $ev->getOwner()?->getId() !== $user->getId()) {
            throw $this->createNotFoundException();
        }
        $capa = $this->capaFactory->createDraftFromEvaluation($ev, $user);
        $this->entityManager->persist($capa);
        $this->entityManager->flush();
        $this->addFlash('success', 'Brouillon CAPA créé depuis cette exigence. Complétez la vérification d’efficacité avant clôture.');

        return $this->redirectToRoute('app_qse_capa_show', ['id' => $capa->getId()]);
    }

    private function isOnboardingOrigin(Request $request): bool
    {
        if ($request->query->getString('origin') === 'onboarding') {
            return true;
        }

        return $request->request->getString('origin') === 'onboarding';
    }
}

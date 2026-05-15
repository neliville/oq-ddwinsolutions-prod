<?php

declare(strict_types=1);

namespace App\Controller\Collaboration;

use App\Application\Analytics\TrackingEventRecorder;
use App\Application\Analytics\TrackingEventType;
use App\Collaboration\SharedAccessService;
use App\Collaboration\SharedResourceType;
use App\Qse\Audit\ViewModel\AuditCockpitViewModelFactory;
use App\Repository\Qse\AuditRepository;
use App\Repository\Qse\AuditRequirementRepository;
use App\Repository\Qse\CAPAActionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PublicSharedQseController extends AbstractController
{
    public function __construct(
        private readonly SharedAccessService $sharedAccessService,
        private readonly AuditRepository $auditRepository,
        private readonly CAPAActionRepository $capaRepository,
        private readonly AuditRequirementRepository $requirementRepository,
        private readonly TrackingEventRecorder $trackingEventRecorder,
        private readonly AuditCockpitViewModelFactory $auditCockpitViewModelFactory,
    ) {
    }

    #[Route('/share/qse/{type}/{token}', name: 'app_share_qse_view', requirements: ['type' => 'audit|capa', 'token' => '[A-Za-z0-9_-]+'], methods: ['GET'])]
    public function view(string $type, string $token): Response
    {
        $access = $this->sharedAccessService->resolveGuestAccess($token);
        if ($access === null) {
            throw $this->createNotFoundException('Lien invalide, expiré ou révoqué.');
        }
        $expected = SharedResourceType::tryFrom($type);
        if ($expected === null || $access->getTargetType() !== $expected) {
            throw $this->createNotFoundException();
        }

        $this->trackingEventRecorder->record(
            TrackingEventType::SHARED_ACCESS_OPENED,
            [
                'target_type' => $access->getTargetType()->value,
                'target_id' => $access->getTargetId(),
            ],
            null,
            'qse_share',
            'view',
            'web',
        );

        if ($access->getTargetType() === SharedResourceType::AUDIT) {
            return $this->renderAuditGuest($access->getTargetId(), $access->getOwner()?->getId() ?? 0);
        }

        return $this->renderCapaGuest($access->getTargetId(), $access->getOwner()?->getId() ?? 0);
    }

    private function renderAuditGuest(int $auditId, int $ownerId): Response
    {
        $audit = $this->auditRepository->find($auditId);
        if ($audit === null || $audit->getOwner()?->getId() !== $ownerId) {
            throw $this->createNotFoundException();
        }
        $std = $audit->getAuditStandard();
        if ($std === null) {
            throw $this->createNotFoundException();
        }
        $chapters = $this->requirementRepository->findDistinctChaptersForStandard($std);
        $evaluationsByReqId = [];
        foreach ($audit->getEvaluations() as $ev) {
            $rid = $ev->getRequirement()?->getId();
            if ($rid !== null) {
                $evaluationsByReqId[$rid] = $ev;
            }
        }
        $chapterBlocks = [];
        foreach ($chapters as $chapter) {
            $requirements = $this->requirementRepository->findByChapterOrderedForStandard($chapter, $std);
            if ($requirements !== []) {
                $chapterBlocks[] = [
                    'chapter' => $chapter,
                    'requirements' => $requirements,
                ];
            }
        }

        $cockpitMetrics = $this->auditCockpitViewModelFactory->build($audit);
        $chartConfigJson = json_encode($cockpitMetrics->chartConfig, \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_UNICODE);

        return $this->render('collaboration/guest_audit_show.html.twig', [
            'audit' => $audit,
            'chapterBlocks' => $chapterBlocks,
            'evaluationsByReqId' => $evaluationsByReqId,
            'accessLevel' => 'lecture_seule',
            'cockpitMetrics' => $cockpitMetrics,
            'chartConfigJson' => $chartConfigJson,
        ]);
    }

    private function renderCapaGuest(int $capaId, int $ownerId): Response
    {
        $capa = $this->capaRepository->find($capaId);
        if ($capa === null || $capa->getOwner()?->getId() !== $ownerId) {
            throw $this->createNotFoundException();
        }

        return $this->render('collaboration/guest_capa_show.html.twig', [
            'capa' => $capa,
            'accessLevel' => 'lecture_seule',
        ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Controller\Qse;

use App\Entity\User;
use App\Qse\Enum\CapaStatus;
use App\Repository\Qse\AuditPlanRepository;
use App\Repository\Qse\AuditRepository;
use App\Repository\Qse\CAPAActionRepository;
use App\Repository\Qse\CockpitMetricsRepository;
use App\Repository\Qse\RiskMatrixEntryRepository;
use App\Repository\AnalyticsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/dashboard/qse/pdca', name: 'app_qse_pdca_')]
#[IsGranted('ROLE_USER')]
final class QsePdcaController extends AbstractController
{
    public function __construct(
        private readonly AnalyticsRepository $analyticsRepository,
        private readonly AuditPlanRepository $auditPlanRepository,
        private readonly AuditRepository $auditRepository,
        private readonly CAPAActionRepository $capaRepository,
        private readonly RiskMatrixEntryRepository $riskRepository,
        private readonly CockpitMetricsRepository $cockpitMetricsRepository,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }
        $uid = $user->getId();

        $toolCounts = $this->analyticsRepository->getUserToolCounts($uid);

        $openCapa = (int) $this->capaRepository->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.owner = :owner')
            ->andWhere('c.status NOT IN (:closed)')
            ->setParameter('owner', $user)
            ->setParameter('closed', CapaStatus::terminalStatuses())
            ->getQuery()
            ->getSingleScalarResult();

        $auditsByStandard = $this->auditRepository->countGroupedByAuditStandardForOwner($user);
        $cockpit = $this->cockpitMetricsRepository->getMetrics($user);

        return $this->render('qse/pdca/index.html.twig', [
            'plan' => [
                'qqoqccp' => $toolCounts['qqoqccpRecords'],
                'amdec' => $toolCounts['amdecRecords'],
                'audit_plans' => \count($this->auditPlanRepository->findByOwner($user)),
                'risks' => \count($this->riskRepository->findByOwner($user, 500)),
            ],
            'do' => [
                'capa_open' => $openCapa,
            ],
            'check' => [
                'audits' => \count($this->auditRepository->findByOwner($user)),
                'audits_by_standard' => $auditsByStandard,
                'pareto' => $toolCounts['paretoRecords'],
            ],
            'act' => [
                'ishikawa' => $toolCounts['ishikawaRecords'],
                'five_why' => $toolCounts['fiveWhyRecords'],
                'eight_d' => $toolCounts['eightDRecords'],
            ],
            'cockpit' => $cockpit,
        ]);
    }
}

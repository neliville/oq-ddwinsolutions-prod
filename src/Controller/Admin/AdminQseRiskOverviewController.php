<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Repository\Qse\RiskMatrixEntryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/qse/risks', name: 'app_admin_qse_risk_')]
#[IsGranted('ROLE_ADMIN')]
final class AdminQseRiskOverviewController extends AbstractController
{
    public function __construct(
        private readonly RiskMatrixEntryRepository $riskMatrixEntryRepository,
    ) {
    }

    #[Route('/overview', name: 'overview', methods: ['GET'])]
    public function overview(): Response
    {
        return $this->render('admin/qse/risk_overview.html.twig', [
            'openNonClosed' => $this->riskMatrixEntryRepository->adminCountOpenNonClosed(),
            'criticalOrHighScore' => $this->riskMatrixEntryRepository->adminCountCriticalOrHighScore(),
            'recentOpen' => $this->riskMatrixEntryRepository->adminFindRecentOpen(40),
        ]);
    }
}

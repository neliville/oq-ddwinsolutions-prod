<?php

namespace App\Controller\Admin;

use App\Repository\AnalyticsRepository;
use App\Repository\ExportLogRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/analytics/tools', name: 'app_admin_tool_usage_')]
#[IsGranted('ROLE_ADMIN')]
final class ToolUsageAnalyticsController extends AbstractController
{
    public function __construct(
        private readonly AnalyticsRepository $analyticsRepository,
        private readonly ExportLogRepository $exportLogRepository,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        $creationByTool = $this->analyticsRepository->getGlobalCreationCountsByTool();
        $totalCreations = array_sum(array_column($creationByTool, 'count'));

        $exportsByTool = $this->exportLogRepository->countByTool();
        $totalExports = array_sum(array_map(static fn (array $r) => (int) $r['total'], $exportsByTool));

        return $this->render('admin/analytics/tools.html.twig', [
            'creationByTool' => $creationByTool,
            'totalCreations' => $totalCreations,
            'exportsByTool' => $exportsByTool,
            'totalExports' => $totalExports,
        ]);
    }
}

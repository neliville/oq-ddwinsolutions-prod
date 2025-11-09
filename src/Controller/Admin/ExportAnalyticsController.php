<?php

namespace App\Controller\Admin;

use App\Repository\ExportLogRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/analytics/exports', name: 'app_admin_export_analytics_')]
#[IsGranted('ROLE_ADMIN')]
class ExportAnalyticsController extends AbstractController
{
    public function __construct(private readonly ExportLogRepository $repository)
    {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('admin/analytics/exports.html.twig', [
            'totalsByFormat' => $this->repository->countByFormat(),
            'totalsByTool' => $this->repository->countByTool(),
            'topUsers' => $this->repository->countByUser(),
            'recentExports' => $this->repository->findRecent(),
        ]);
    }
}

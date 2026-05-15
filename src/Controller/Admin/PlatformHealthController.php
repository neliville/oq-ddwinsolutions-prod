<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Admin\Dashboard\PlatformIntegrationSummaryProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/platform', name: 'app_admin_platform_health_')]
#[IsGranted('ROLE_ADMIN')]
final class PlatformHealthController extends AbstractController
{
    public function __construct(
        private readonly PlatformIntegrationSummaryProvider $platformIntegrationSummaryProvider,
    ) {
    }

    #[Route('/health', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('admin/platform_health/index.html.twig', [
            'summary' => $this->platformIntegrationSummaryProvider->summarize(),
        ]);
    }
}

<?php

namespace App\Controller\Admin;

use App\Admin\Dashboard\AdminDashboardMetricsProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin', name: 'app_admin_')]
#[IsGranted('ROLE_ADMIN')]
final class DashboardController extends AbstractController
{
    public function __construct(
        private readonly AdminDashboardMetricsProvider $adminDashboardMetricsProvider,
    ) {
    }

    #[Route('/dashboard', name: 'dashboard_index')]
    public function index(): Response
    {
        return $this->render('admin/dashboard/index.html.twig', $this->adminDashboardMetricsProvider->buildViewModel());
    }
}

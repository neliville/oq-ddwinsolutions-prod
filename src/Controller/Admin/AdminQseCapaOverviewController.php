<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Repository\Qse\CAPAActionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/qse/capa', name: 'app_admin_qse_capa_')]
#[IsGranted('ROLE_ADMIN')]
final class AdminQseCapaOverviewController extends AbstractController
{
    public function __construct(
        private readonly CAPAActionRepository $capaActionRepository,
    ) {
    }

    #[Route('/overview', name: 'overview', methods: ['GET'])]
    public function overview(): Response
    {
        $todayStart = (new \DateTimeImmutable())->setTime(0, 0, 0);

        return $this->render('admin/qse/capa_overview.html.twig', [
            'openCritical' => $this->capaActionRepository->adminCountOpenHighCriticality(),
            'openOverdue' => $this->capaActionRepository->adminCountOpenOverdue($todayStart),
            'recentOpen' => $this->capaActionRepository->adminFindRecentOpen(40),
        ]);
    }
}

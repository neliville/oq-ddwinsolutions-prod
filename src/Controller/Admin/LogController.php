<?php

namespace App\Controller\Admin;

use App\Repository\AdminLogRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/logs', name: 'app_admin_logs_')]
#[IsGranted('ROLE_ADMIN')]
final class LogController extends AbstractController
{
    public function __construct(
        private readonly AdminLogRepository $adminLogRepository,
        private readonly UserRepository $userRepository,
        private readonly RequestStack $requestStack,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $action = $request->query->get('action');
        $entityType = $request->query->get('entityType');
        $userId = $request->query->getInt('user');
        $period = $request->query->get('period', '30days');
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = 50;

        // Calculer les dates selon la période
        $now = new \DateTimeImmutable();
        $start = match ($period) {
            'today' => $now->setTime(0, 0, 0),
            'week' => $now->modify('-7 days')->setTime(0, 0, 0),
            'month' => $now->modify('-30 days')->setTime(0, 0, 0),
            'year' => $now->modify('-365 days')->setTime(0, 0, 0),
            default => $now->modify('-30 days')->setTime(0, 0, 0),
        };

        $logs = $this->adminLogRepository->findByFilters(
            $action,
            $entityType,
            $userId ?: null,
            $start,
            $now,
            $page,
            $limit
        );

        $total = $this->adminLogRepository->countByFilters(
            $action,
            $entityType,
            $userId ?: null,
            $start,
            $now
        );

        $pages = ceil($total / $limit);

        // Liste des actions et entités pour les filtres
        $actions = $this->adminLogRepository->findActionsList();
        $entityTypes = $this->adminLogRepository->findEntityTypesList();
        $users = $this->userRepository->findAll();

        return $this->render('admin/logs/index.html.twig', [
            'logs' => $logs,
            'actions' => $actions,
            'entityTypes' => $entityTypes,
            'users' => $users,
            'currentAction' => $action,
            'currentEntityType' => $entityType,
            'currentUserId' => $userId,
            'currentPeriod' => $period,
            'currentPage' => $page,
            'totalPages' => $pages,
            'total' => $total,
        ]);
    }

    #[Route('/export', name: 'export', methods: ['GET'])]
    public function export(Request $request): Response
    {
        $action = $request->query->get('action');
        $entityType = $request->query->get('entityType');
        $userId = $request->query->getInt('user');
        $period = $request->query->get('period', '30days');

        $now = new \DateTimeImmutable();
        $start = match ($period) {
            'today' => $now->setTime(0, 0, 0),
            'week' => $now->modify('-7 days')->setTime(0, 0, 0),
            'month' => $now->modify('-30 days')->setTime(0, 0, 0),
            'year' => $now->modify('-365 days')->setTime(0, 0, 0),
            default => $now->modify('-30 days')->setTime(0, 0, 0),
        };

        $logs = $this->adminLogRepository->findByFilters(
            $action,
            $entityType,
            $userId ?: null,
            $start,
            $now,
            1,
            10000
        );

        // Générer le CSV
        $csv = "Date;Utilisateur;Action;Entité;ID;Description;IP\n";
        foreach ($logs as $log) {
            $csv .= sprintf(
                "%s;%s;%s;%s;%s;%s;%s\n",
                $log->getCreatedAt()->format('Y-m-d H:i:s'),
                $log->getUser() ? $log->getUser()->getEmail() : 'N/A',
                $log->getAction(),
                $log->getEntityType() ?? 'N/A',
                $log->getEntityId() ?? 'N/A',
                str_replace(';', ',', $log->getDescription() ?? ''),
                $log->getIpAddress() ?? 'N/A'
            );
        }

        $response = new Response($csv);
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'admin_logs_' . date('Y-m-d') . '.csv'
        ));

        return $response;
    }
}


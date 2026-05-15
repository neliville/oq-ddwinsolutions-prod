<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Dashboard\DashboardLayout;
use App\Dashboard\DashboardWidgetId;
use App\Entity\User;
use App\Repository\UserPreferencesRepository;
use App\Service\DashboardPreferencesService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/preferences/dashboard-layout', name: 'app_api_preferences_dashboard_layout_')]
#[IsGranted('ROLE_USER')]
final class DashboardLayoutController extends AbstractController
{
    public function __construct(
        private readonly UserPreferencesRepository $userPreferencesRepository,
        private readonly DashboardPreferencesService $dashboardPreferencesService,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('', name: 'update', methods: ['PATCH'])]
    public function update(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['ok' => false, 'message' => 'Non authentifié.'], Response::HTTP_UNAUTHORIZED);
        }

        if (!$this->isCsrfTokenValid('dashboard_layout', $request->headers->get('X-CSRF-TOKEN') ?? '')) {
            return new JsonResponse(['ok' => false, 'message' => 'Jeton CSRF invalide.'], Response::HTTP_FORBIDDEN);
        }

        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload) || !isset($payload['widgets']) || !is_array($payload['widgets'])) {
            return new JsonResponse(['ok' => false, 'message' => 'Payload invalide.'], Response::HTTP_BAD_REQUEST);
        }

        $entries = [];
        foreach ($payload['widgets'] as $widget) {
            if (!is_array($widget)) {
                continue;
            }
            $id = $widget['id'] ?? null;
            if (!is_string($id) || !DashboardWidgetId::isKnown($id)) {
                continue;
            }
            $entries[] = [
                'id' => $id,
                'visible' => (bool) ($widget['visible'] ?? false),
            ];
        }

        if ($entries === []) {
            return new JsonResponse(['ok' => false, 'message' => 'Aucun widget valide.'], Response::HTTP_BAD_REQUEST);
        }

        $visibleCount = count(array_filter($entries, static fn (array $w): bool => $w['visible']));
        if ($visibleCount < 1) {
            return new JsonResponse(['ok' => false, 'message' => 'Au moins un bloc doit rester visible.'], Response::HTTP_BAD_REQUEST);
        }

        $prefs = $this->userPreferencesRepository->getOrCreateForUser($user);
        $this->dashboardPreferencesService->applyDashboardLayout($prefs, DashboardLayout::fromWidgetEntries($entries));
        $prefs->touchUpdatedAt();
        $this->entityManager->flush();

        return new JsonResponse([
            'ok' => true,
            'message' => 'Affichage du tableau de bord mis à jour.',
        ]);
    }
}

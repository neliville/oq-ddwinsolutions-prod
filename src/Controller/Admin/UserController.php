<?php

namespace App\Controller\Admin;

use App\Entity\AdminLog;
use App\Entity\User;
use App\Repository\AdminLogRepository;
use App\Repository\AnalyticsRepository;
use App\Repository\ExportLogRepository;
use App\Repository\RecordRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/users', name: 'app_admin_users_')]
#[IsGranted('ROLE_ADMIN')]
final class UserController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly AdminLogRepository $adminLogRepository,
        private readonly RecordRepository $recordRepository,
        private readonly ExportLogRepository $exportLogRepository,
        private readonly AnalyticsRepository $analyticsRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly RequestStack $requestStack,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $filter = $request->query->get('filter', 'all');
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = 20;

        $users = $this->userRepository->findByFilter($filter, $page, $limit);
        $total = $this->userRepository->countByFilter($filter);
        $pages = ceil($total / $limit);

        $allCount = $this->userRepository->countByFilter('all');
        $adminCount = $this->userRepository->countByFilter('admin');
        $userCount = $this->userRepository->countByFilter('user');

        return $this->render('admin/users/index.html.twig', [
            'users' => $users,
            'currentFilter' => $filter,
            'currentPage' => $page,
            'totalPages' => $pages,
            'total' => $total,
            'allCount' => $allCount,
            'adminCount' => $adminCount,
            'userCount' => $userCount,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(User $user): Response
    {
        $userId = (int) $user->getId();

        $adminAuditLogs = $this->adminLogRepository->findByFilters(
            null,
            null,
            $userId,
            null,
            null,
            1,
            15
        );

        $toolUsage = $this->analyticsRepository->getUserToolCounts($userId);
        $toolBreakdown = $this->analyticsRepository->getPerUserToolBreakdown($userId);
        $recentAnalyses = $this->analyticsRepository->getRecentAnalysesForUser($userId, 40);
        $recentExports = $this->exportLogRepository->findRecentByUser($user, 50);
        $recordStatsByType = $this->recordRepository->countGroupedByTypeForUser($userId);
        $exportStatsByTool = $this->exportLogRepository->countByToolForUser($user);
        $exportStatsByToolAndFormat = $this->exportLogRepository->countByToolAndFormatForUser($user);

        return $this->render('admin/users/show.html.twig', [
            'user' => $user,
            'adminAuditLogs' => $adminAuditLogs,
            'toolUsage' => $toolUsage,
            'toolBreakdown' => $toolBreakdown,
            'recentAnalyses' => $recentAnalyses,
            'recentExports' => $recentExports,
            'recordStatsByType' => $recordStatsByType,
            'exportStatsByTool' => $exportStatsByTool,
            'exportStatsByToolAndFormat' => $exportStatsByToolAndFormat,
            'analysisTotalCount' => $toolUsage['totalRecords'],
            'recordTotalCount' => $this->recordRepository->countByUserAndType($userId),
            'exportTotalCount' => $this->exportLogRepository->countForUser($user),
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(User $user, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            if ($this->isCsrfTokenValid('edit_user_' . $user->getId(), $request->request->get('_token'))) {
                $email = $request->request->get('email');
                $roles = $request->request->all('roles') ?? [];
                
                // Ne pas permettre de supprimer le dernier admin
                $currentUser = $this->getUser();
                if ($currentUser instanceof User && $user->getId() === $currentUser->getId() && !in_array('ROLE_ADMIN', $roles)) {
                    $this->addFlash('error', 'Vous ne pouvez pas retirer votre propre rôle admin.');
                    return $this->redirectToRoute('app_admin_users_edit', ['id' => $user->getId()]);
                }

                $user->setEmail($email);
                $user->setRoles($roles);

                // Gérer le changement de mot de passe si fourni
                $newPassword = $request->request->get('password');
                if ($newPassword && !empty($newPassword)) {
                    $hashedPassword = $this->passwordHasher->hashPassword($user, $newPassword);
                    $user->setPassword($hashedPassword);
                }

                $this->entityManager->flush();

                // Logger l'action
                $userId = $user->getId();
                $this->logAction('UPDATE', User::class, $userId, "Utilisateur modifié : {$email}");

                $this->addFlash('success', 'Utilisateur modifié avec succès.');

                return $this->redirectToRoute('app_admin_users_show', ['id' => $user->getId()]);
            }
        }

        return $this->render('admin/users/edit.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(User $user, Request $request): Response
    {
        if ($this->isCsrfTokenValid('delete_user_' . $user->getId(), $request->request->get('_token'))) {
            // Ne pas permettre de supprimer l'utilisateur actuel
            $currentUser = $this->getUser();
            if ($currentUser instanceof User && $user->getId() === $currentUser->getId()) {
                $this->addFlash('error', 'Vous ne pouvez pas supprimer votre propre compte.');
                return $this->redirectToRoute('app_admin_users_index');
            }

            $email = $user->getEmail();
            $userId = $user->getId();
            $userClass = User::class;

            $this->entityManager->remove($user);
            $this->entityManager->flush();

            // Logger l'action
            $this->logAction('DELETE', $userClass, $userId, "Utilisateur supprimé : {$email}");

            $this->addFlash('success', 'Utilisateur supprimé avec succès.');
        }

        return $this->redirectToRoute('app_admin_users_index');
    }

    #[Route('/bulk', name: 'bulk', methods: ['POST'])]
    public function bulk(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('bulk_users', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_admin_users_index');
        }

        $action = (string) $request->request->get('action', '');
        $selectedIds = array_values(array_unique(array_map(
            static fn (mixed $id): int => (int) $id,
            $request->request->all('selected_ids')
        )));
        $selectedIds = array_values(array_filter($selectedIds, static fn (int $id): bool => $id > 0));

        if ([] === $selectedIds) {
            $this->addFlash('error', 'Sélectionnez au moins un utilisateur.');
            return $this->redirectToRoute('app_admin_users_index');
        }

        $users = $this->userRepository->findBy(['id' => $selectedIds]);
        if ([] === $users) {
            $this->addFlash('error', 'Aucun utilisateur valide sélectionné.');
            return $this->redirectToRoute('app_admin_users_index');
        }

        return match ($action) {
            'export_csv' => $this->buildCsvExportResponse($users),
            'export_json' => $this->buildJsonExportResponse($users),
            'export_emails_txt' => $this->buildEmailsTxtExportResponse($users),
            'grant_admin' => $this->bulkGrantAdmin($users),
            'revoke_admin' => $this->bulkRevokeAdmin($users),
            default => $this->redirectAfterUnsupportedBulkAction(),
        };
    }

    private function logAction(string $action, string $entityType, ?int $entityId, string $description): void
    {
        $adminLog = new AdminLog();
        $adminLog->setUser($this->getUser());
        $adminLog->setAction($action);
        $adminLog->setEntityType($entityType);
        $adminLog->setEntityId($entityId);
        $adminLog->setDescription($description);
        $adminLog->setIpAddress($this->getClientIp());

        $this->entityManager->persist($adminLog);
        $this->entityManager->flush();
    }

    private function getClientIp(): ?string
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return null;
        }

        return $request->getClientIp();
    }

    /**
     * @param User[] $users
     */
    private function buildCsvExportResponse(array $users): StreamedResponse
    {
        $filename = sprintf('users-export-%s.csv', (new \DateTimeImmutable())->format('Ymd-His'));
        $response = new StreamedResponse(function () use ($users): void {
            $handle = fopen('php://output', 'wb');
            if (false === $handle) {
                return;
            }

            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, ['id', 'email', 'roles', 'created_at', 'last_login_at', 'last_activity_at']);

            foreach ($users as $user) {
                fputcsv($handle, [
                    $user->getId(),
                    $user->getEmail(),
                    implode('|', $user->getRoles()),
                    $user->getCreatedAt()?->format(\DateTimeInterface::ATOM) ?? '',
                    $user->getLastLoginAt()?->format(\DateTimeInterface::ATOM) ?? '',
                    $user->getLastActivityAt()?->format(\DateTimeInterface::ATOM) ?? '',
                ]);
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', sprintf('attachment; filename="%s"', $filename));

        return $response;
    }

    /**
     * @param User[] $users
     */
    private function buildJsonExportResponse(array $users): Response
    {
        $filename = sprintf('users-export-%s.json', (new \DateTimeImmutable())->format('Ymd-His'));
        $payload = array_map(static function (User $user): array {
            return [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
                'created_at' => $user->getCreatedAt()?->format(\DateTimeInterface::ATOM),
                'last_login_at' => $user->getLastLoginAt()?->format(\DateTimeInterface::ATOM),
                'last_activity_at' => $user->getLastActivityAt()?->format(\DateTimeInterface::ATOM),
            ];
        }, $users);

        $response = $this->json($payload);
        $response->headers->set('Content-Type', 'application/json; charset=utf-8');
        $response->headers->set('Content-Disposition', sprintf('attachment; filename="%s"', $filename));

        return $response;
    }

    /**
     * @param User[] $users
     */
    private function buildEmailsTxtExportResponse(array $users): Response
    {
        $filename = sprintf('users-emails-%s.txt', (new \DateTimeImmutable())->format('Ymd-His'));
        $emails = array_values(array_unique(array_map(
            static fn (User $user): string => (string) $user->getEmail(),
            $users
        )));

        $response = new Response(implode(PHP_EOL, $emails) . PHP_EOL);
        $response->headers->set('Content-Type', 'text/plain; charset=utf-8');
        $response->headers->set('Content-Disposition', sprintf('attachment; filename="%s"', $filename));

        return $response;
    }

    /**
     * @param User[] $users
     */
    private function bulkGrantAdmin(array $users): Response
    {
        $updated = 0;
        foreach ($users as $user) {
            $roles = $user->getRoles();
            if (!in_array('ROLE_ADMIN', $roles, true)) {
                $roles[] = 'ROLE_ADMIN';
                $user->setRoles($this->normalizeRoles($roles));
                ++$updated;
            }
        }

        if ($updated > 0) {
            $this->entityManager->flush();
        }

        $this->addFlash('success', sprintf('%d utilisateur(s) promu(s) administrateur.', $updated));
        return $this->redirectToRoute('app_admin_users_index');
    }

    /**
     * @param User[] $users
     */
    private function bulkRevokeAdmin(array $users): Response
    {
        $currentUser = $this->getUser();
        $updated = 0;

        foreach ($users as $user) {
            if ($currentUser instanceof User && $user->getId() === $currentUser->getId()) {
                continue;
            }

            $roles = array_values(array_filter(
                $user->getRoles(),
                static fn (string $role): bool => $role !== 'ROLE_ADMIN'
            ));

            $normalizedRoles = $this->normalizeRoles($roles);
            if ($normalizedRoles !== $user->getRoles()) {
                $user->setRoles($normalizedRoles);
                ++$updated;
            }
        }

        if ($updated > 0) {
            $this->entityManager->flush();
        }

        $this->addFlash('success', sprintf('%d utilisateur(s) rétrogradé(s) administrateur.', $updated));
        return $this->redirectToRoute('app_admin_users_index');
    }

    private function redirectAfterUnsupportedBulkAction(): Response
    {
        $this->addFlash('error', 'Action groupée non supportée.');
        return $this->redirectToRoute('app_admin_users_index');
    }

    /**
     * @param string[] $roles
     * @return string[]
     */
    private function normalizeRoles(array $roles): array
    {
        $roles[] = 'ROLE_USER';
        $roles = array_values(array_unique($roles));
        sort($roles);

        return $roles;
    }
}

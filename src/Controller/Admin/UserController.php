<?php

namespace App\Controller\Admin;

use App\Entity\AdminLog;
use App\Entity\User;
use App\Repository\AdminLogRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
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
        // Récupérer les logs de l'utilisateur
        $userLogs = $this->adminLogRepository->findByFilters(
            null,
            null,
            $user->getId(),
            null,
            null,
            1,
            10
        );

        return $this->render('admin/users/show.html.twig', [
            'user' => $user,
            'userLogs' => $userLogs,
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
}

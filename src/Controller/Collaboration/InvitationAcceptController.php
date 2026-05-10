<?php

declare(strict_types=1);

namespace App\Controller\Collaboration;

use App\Collaboration\CollaborationSession;
use App\Collaboration\CollaborationToken;
use App\Collaboration\InvitationStatus;
use App\Collaboration\UserInvitationService;
use App\Entity\User;
use App\Repository\UserInvitationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class InvitationAcceptController extends AbstractController
{
    public function __construct(
        private readonly UserInvitationRepository $userInvitationRepository,
        private readonly UserInvitationService $userInvitationService,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/collaboration/invitation/accept', name: 'app_collaboration_invitation_accept', methods: ['GET'])]
    public function accept(Request $request): Response
    {
        $plain = $request->query->getString('t');
        if ($plain === '') {
            throw $this->createNotFoundException();
        }
        $hash = CollaborationToken::hashPlain($plain);
        $inv = $this->userInvitationRepository->findByTokenHash($hash);
        if ($inv === null) {
            throw $this->createNotFoundException();
        }
        $now = new \DateTimeImmutable();
        if ($inv->getExpiresAt() < $now) {
            if ($inv->getStatus() === InvitationStatus::ENVOYEE) {
                $inv->setStatus(InvitationStatus::EXPIREE);
                $this->entityManager->flush();
            }

            return $this->render('collaboration/invitation_expired.html.twig', [
                'invitation' => $inv,
            ], new Response(null, Response::HTTP_GONE));
        }
        if ($inv->getStatus() !== InvitationStatus::ENVOYEE) {
            return $this->render('collaboration/invitation_unavailable.html.twig', [
                'invitation' => $inv,
            ]);
        }

        $user = $this->getUser();
        if ($user instanceof User) {
            if (mb_strtolower((string) $user->getEmail()) === $inv->getEmail()) {
                if ($this->userInvitationService->tryAccept($user, $plain)) {
                    $this->addFlash('success', 'Invitation acceptée. Bienvenue sur le pilotage partagé.');

                    return $this->redirectToRoute('app_dashboard_index');
                }
            }
            $this->addFlash('warning', 'Connectez-vous avec l’adresse e-mail à laquelle l’invitation a été envoyée.');

            return $this->redirectToRoute('app_login');
        }

        if ($request->hasSession()) {
            $request->getSession()->set(CollaborationSession::INVITATION_PLAIN_TOKEN, $plain);
        }

        return $this->render('collaboration/invitation_landing.html.twig', [
            'invitation' => $inv,
            'register_url' => $this->generateUrl('app_register', ['invitation' => $plain]),
            'login_url' => $this->generateUrl('app_login'),
        ]);
    }
}

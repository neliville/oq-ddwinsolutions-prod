<?php

namespace App\Controller\Admin;

use App\Entity\AdminLog;
use App\Entity\ContactMessage;
use App\Repository\AdminLogRepository;
use App\Repository\ContactMessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/contact', name: 'app_admin_contact_')]
#[IsGranted('ROLE_ADMIN')]
final class ContactController extends AbstractController
{
    public function __construct(
        private readonly ContactMessageRepository $contactMessageRepository,
        private readonly AdminLogRepository $adminLogRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly RequestStack $requestStack,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $filter = $request->query->get('filter', 'all');
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = 20;

        $messages = $this->contactMessageRepository->findByFilter($filter, $page, $limit);
        $total = $this->contactMessageRepository->countByFilter($filter);
        $pages = ceil($total / $limit);

        $unreadCount = $this->contactMessageRepository->countUnread();
        $readCount = $this->contactMessageRepository->countByFilter('read');
        $repliedCount = $this->contactMessageRepository->countByFilter('replied');
        $unrepliedCount = $this->contactMessageRepository->countByFilter('unreplied');

        return $this->render('admin/contact/index.html.twig', [
            'messages' => $messages,
            'currentFilter' => $filter,
            'currentPage' => $page,
            'totalPages' => $pages,
            'total' => $total,
            'unreadCount' => $unreadCount,
            'readCount' => $readCount,
            'repliedCount' => $repliedCount,
            'unrepliedCount' => $unrepliedCount,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(ContactMessage $message): Response
    {
        // Marquer comme lu automatiquement
        if (!$message->isRead()) {
            $message->setRead(true);
            $this->entityManager->flush();

            // Logger l'action
            $this->logAction('UPDATE', ContactMessage::class, $message->getId(), 'Message marqué comme lu');
        }

        return $this->render('admin/contact/show.html.twig', [
            'message' => $message,
        ]);
    }

    #[Route('/{id}/mark-read', name: 'mark_read', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function markRead(ContactMessage $message, Request $request): Response
    {
        if ($this->isCsrfTokenValid('mark_read_' . $message->getId(), $request->request->get('_token'))) {
            $message->setRead(true);
            $this->entityManager->flush();

            // Logger l'action
            $this->logAction('UPDATE', ContactMessage::class, $message->getId(), 'Message marqué comme lu');

            $this->addFlash('success', 'Message marqué comme lu.');
        }

        return $this->redirectToRoute('app_admin_contact_index');
    }

    #[Route('/{id}/mark-unread', name: 'mark_unread', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function markUnread(ContactMessage $message, Request $request): Response
    {
        if ($this->isCsrfTokenValid('mark_unread_' . $message->getId(), $request->request->get('_token'))) {
            $message->setRead(false);
            $this->entityManager->flush();

            // Logger l'action
            $this->logAction('UPDATE', ContactMessage::class, $message->getId(), 'Message marqué comme non lu');

            $this->addFlash('success', 'Message marqué comme non lu.');
        }

        return $this->redirectToRoute('app_admin_contact_index');
    }

    #[Route('/{id}/reply', name: 'reply', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function reply(ContactMessage $message, Request $request): Response
    {
        if ($this->isCsrfTokenValid('reply_' . $message->getId(), $request->request->get('_token'))) {
            $replyMessage = $request->request->get('reply_message');

            if ($replyMessage) {
                // Marquer comme répondu
                $message->setReplied(true);
                $message->setRead(true);
                $this->entityManager->flush();

                // TODO: Envoyer l'email de réponse
                // $this->mailer->sendReply($message, $replyMessage);

                // Logger l'action
                $this->logAction('UPDATE', ContactMessage::class, $message->getId(), 'Réponse envoyée au message');

                $this->addFlash('success', 'Réponse envoyée avec succès.');
            } else {
                $this->addFlash('error', 'Le message de réponse ne peut pas être vide.');
            }
        }

        return $this->redirectToRoute('app_admin_contact_show', ['id' => $message->getId()]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(ContactMessage $message, Request $request): Response
    {
        if ($this->isCsrfTokenValid('delete_' . $message->getId(), $request->request->get('_token'))) {
            $messageId = $message->getId();
            $messageEmail = $message->getEmail();

            $this->entityManager->remove($message);
            $this->entityManager->flush();

            // Logger l'action
            $this->logAction('DELETE', ContactMessage::class, $messageId, "Message supprimé (de {$messageEmail})");

            $this->addFlash('success', 'Message supprimé avec succès.');
        }

        return $this->redirectToRoute('app_admin_contact_index');
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

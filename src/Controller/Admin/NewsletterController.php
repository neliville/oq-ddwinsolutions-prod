<?php

namespace App\Controller\Admin;

use App\Entity\AdminLog;
use App\Entity\NewsletterSubscriber;
use App\Repository\AdminLogRepository;
use App\Repository\NewsletterSubscriberRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/newsletter', name: 'app_admin_newsletter_')]
#[IsGranted('ROLE_ADMIN')]
final class NewsletterController extends AbstractController
{
    public function __construct(
        private readonly NewsletterSubscriberRepository $newsletterSubscriberRepository,
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

        $subscribers = $this->newsletterSubscriberRepository->findByFilter($filter, $page, $limit);
        $total = $this->newsletterSubscriberRepository->countByFilter($filter);
        $pages = ceil($total / $limit);

        $activeCount = $this->newsletterSubscriberRepository->countActive();
        $inactiveCount = $this->newsletterSubscriberRepository->countByFilter('inactive');

        return $this->render('admin/newsletter/index.html.twig', [
            'subscribers' => $subscribers,
            'currentFilter' => $filter,
            'currentPage' => $page,
            'totalPages' => $pages,
            'total' => $total,
            'activeCount' => $activeCount,
            'inactiveCount' => $inactiveCount,
        ]);
    }

    #[Route('/export', name: 'export', methods: ['GET'])]
    public function export(string $filter = 'active'): Response
    {
        if ($filter === 'active') {
            $subscribers = $this->newsletterSubscriberRepository->findAllActive();
        } else {
            $subscribers = $this->newsletterSubscriberRepository->findByFilter($filter, 1, 10000);
        }

        // Générer le CSV
        $csv = "Email;Date d'inscription;Statut;Source\n";
        foreach ($subscribers as $subscriber) {
            $csv .= sprintf(
                "%s;%s;%s;%s\n",
                $subscriber->getEmail(),
                $subscriber->getSubscribedAt()->format('Y-m-d H:i:s'),
                $subscriber->isActive() ? 'Actif' : 'Inactif',
                $subscriber->getSource() ?? 'N/A'
            );
        }

        $response = new Response($csv);
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'newsletter_abonnes_' . date('Y-m-d') . '.csv'
        ));

        return $response;
    }

    #[Route('/{id}/unsubscribe', name: 'unsubscribe', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function unsubscribe(NewsletterSubscriber $subscriber, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('unsubscribe_' . $subscriber->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token de sécurité invalide. Veuillez réessayer.');
            return $this->redirectToRoute('app_admin_newsletter_index');
        }

        $email = $subscriber->getEmail();
        $subscriber->unsubscribe();
        $this->entityManager->flush();

        $this->logAction('UPDATE', NewsletterSubscriber::class, $subscriber->getId(), "Désabonnement de {$email}");
        $this->addFlash('success', 'Abonné désabonné avec succès.');

        return $this->redirectToRoute('app_admin_newsletter_index');
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(NewsletterSubscriber $subscriber, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('delete_' . $subscriber->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token de sécurité invalide. Veuillez réessayer.');
            return $this->redirectToRoute('app_admin_newsletter_index');
        }

        $email = $subscriber->getEmail();
        $subscriberId = $subscriber->getId();

        $this->entityManager->remove($subscriber);
        $this->entityManager->flush();

        $this->logAction('DELETE', NewsletterSubscriber::class, $subscriberId, "Abonné supprimé : {$email}");
        $this->addFlash('success', 'Abonné supprimé avec succès.');

        return $this->redirectToRoute('app_admin_newsletter_index');
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

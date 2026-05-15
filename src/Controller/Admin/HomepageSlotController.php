<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\AdminLog;
use App\Entity\HomepageContentSlot;
use App\Form\HomepageContentSlotFormType;
use App\Repository\HomepageContentSlotRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/homepage-slots', name: 'app_admin_homepage_slots_')]
#[IsGranted('ROLE_ADMIN')]
final class HomepageSlotController extends AbstractController
{
    public function __construct(
        private readonly HomepageContentSlotRepository $homepageContentSlotRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly RequestStack $requestStack,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('admin/homepage_slots/index.html.twig', [
            'slots' => $this->homepageContentSlotRepository->findAllOrdered(),
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(HomepageContentSlot $slot, Request $request): Response
    {
        $form = $this->createForm(HomepageContentSlotFormType::class, $slot);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->logAction('UPDATE', HomepageContentSlot::class, (int) $slot->getId(), sprintf('Slot homepage `%s` mis à jour', $slot->getSlotKey()));
            $this->addFlash('success', 'Slot enregistré.');

            return $this->redirectToRoute('app_admin_homepage_slots_index');
        }

        return $this->render('admin/homepage_slots/edit.html.twig', [
            'slot' => $slot,
            'form' => $form,
        ]);
    }

    private function logAction(string $action, string $entityType, ?int $entityId, string $description): void
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            return;
        }

        $adminLog = new AdminLog();
        $adminLog->setUser($user);
        $adminLog->setAction($action);
        $adminLog->setEntityType($entityType);
        $adminLog->setEntityId($entityId);
        $adminLog->setDescription($description);
        $adminLog->setIpAddress($this->requestStack->getCurrentRequest()?->getClientIp());

        $this->entityManager->persist($adminLog);
        $this->entityManager->flush();
    }
}

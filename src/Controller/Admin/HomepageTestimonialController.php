<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\AdminLog;
use App\Entity\HomepageTestimonial;
use App\Form\HomepageTestimonialFormType;
use App\Repository\HomepageTestimonialRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/homepage-testimonials', name: 'app_admin_homepage_testimonials_')]
#[IsGranted('ROLE_ADMIN')]
final class HomepageTestimonialController extends AbstractController
{
    public function __construct(
        private readonly HomepageTestimonialRepository $homepageTestimonialRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly RequestStack $requestStack,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        $testimonials = $this->homepageTestimonialRepository->findAllOrdered();
        $activeCount = count(array_filter($testimonials, static fn (HomepageTestimonial $t): bool => $t->isActive()));

        return $this->render('admin/homepage_testimonials/index.html.twig', [
            'testimonials' => $testimonials,
            'activeCount' => $activeCount,
            'total' => count($testimonials),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $testimonial = new HomepageTestimonial();
        $form = $this->createForm(HomepageTestimonialFormType::class, $testimonial);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($testimonial);
            $this->entityManager->flush();

            $this->logAction('CREATE', HomepageTestimonial::class, $testimonial->getId(), sprintf('Témoignage créé : %s', $testimonial->getFullName()));
            $this->addFlash('success', 'Témoignage enregistré avec succès.');

            return $this->redirectToRoute('app_admin_homepage_testimonials_index');
        }

        return $this->render('admin/homepage_testimonials/form.html.twig', [
            'form' => $form,
            'testimonial' => $testimonial,
            'is_edit' => false,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(HomepageTestimonial $testimonial, Request $request): Response
    {
        $form = $this->createForm(HomepageTestimonialFormType::class, $testimonial);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->logAction('UPDATE', HomepageTestimonial::class, $testimonial->getId(), sprintf('Témoignage modifié : %s', $testimonial->getFullName()));
            $this->addFlash('success', 'Témoignage mis à jour avec succès.');

            return $this->redirectToRoute('app_admin_homepage_testimonials_index');
        }

        return $this->render('admin/homepage_testimonials/form.html.twig', [
            'form' => $form,
            'testimonial' => $testimonial,
            'is_edit' => true,
        ]);
    }

    #[Route('/{id}/toggle', name: 'toggle', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggle(HomepageTestimonial $testimonial, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('toggle_testimonial_' . $testimonial->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token de sécurité invalide.');

            return $this->redirectToRoute('app_admin_homepage_testimonials_index');
        }

        $testimonial->setIsActive(!$testimonial->isActive());
        $this->entityManager->flush();

        $status = $testimonial->isActive() ? 'activé' : 'désactivé';
        $this->logAction('UPDATE', HomepageTestimonial::class, $testimonial->getId(), sprintf('Témoignage %s : %s', $status, $testimonial->getFullName()));
        $this->addFlash('success', sprintf('Témoignage %s sur la homepage.', $status));

        return $this->redirectToRoute('app_admin_homepage_testimonials_index');
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(HomepageTestimonial $testimonial, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('delete_testimonial_' . $testimonial->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token de sécurité invalide.');

            return $this->redirectToRoute('app_admin_homepage_testimonials_index');
        }

        $name = $testimonial->getFullName();
        $id = $testimonial->getId();

        $this->entityManager->remove($testimonial);
        $this->entityManager->flush();

        $this->logAction('DELETE', HomepageTestimonial::class, $id, sprintf('Témoignage supprimé : %s', $name));
        $this->addFlash('success', 'Témoignage supprimé.');

        return $this->redirectToRoute('app_admin_homepage_testimonials_index');
    }

    private function logAction(string $action, string $entityType, ?int $entityId, string $description): void
    {
        $adminLog = new AdminLog();
        $adminLog->setUser($this->getUser());
        $adminLog->setAction($action);
        $adminLog->setEntityType($entityType);
        $adminLog->setEntityId($entityId);
        $adminLog->setDescription($description);
        $adminLog->setIpAddress($this->requestStack->getCurrentRequest()?->getClientIp());

        $this->entityManager->persist($adminLog);
        $this->entityManager->flush();
    }
}

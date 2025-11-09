<?php

namespace App\Controller\Admin;

use App\Entity\AdminLog;
use App\Entity\Tag;
use App\Form\TagFormType;
use App\Repository\AdminLogRepository;
use App\Repository\TagRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin/tags', name: 'app_admin_tags_')]
#[IsGranted('ROLE_ADMIN')]
final class TagController extends AbstractController
{
    public function __construct(
        private readonly TagRepository $tagRepository,
        private readonly AdminLogRepository $adminLogRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly RequestStack $requestStack,
        private readonly SluggerInterface $slugger,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        $tags = $this->tagRepository->findBy([], ['name' => 'ASC']);

        return $this->render('admin/tags/index.html.twig', [
            'tags' => $tags,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $tag = new Tag();
        $form = $this->createForm(TagFormType::class, $tag);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Générer le slug automatiquement s'il est vide
            if (empty($tag->getSlug())) {
                $slug = strtolower($this->slugger->slug($tag->getName())->toString());
                $tag->setSlug($slug);
            }

            // Vérifier l'unicité du slug
            $slugExists = $this->tagRepository->findOneBy(['slug' => $tag->getSlug()]);
            if ($slugExists) {
                $tag->setSlug($tag->getSlug() . '-' . time());
            }

            $this->entityManager->persist($tag);
            $this->entityManager->flush();

            // Logger l'action
            $this->logAction('CREATE', Tag::class, $tag->getId(), "Tag créé : {$tag->getName()}");

            $this->addFlash('success', 'Tag créé avec succès.');

            return $this->redirectToRoute('app_admin_tags_index');
        }

        return $this->render('admin/tags/new.html.twig', [
            'form' => $form,
            'tag' => $tag,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Tag $tag, Request $request): Response
    {
        $form = $this->createForm(TagFormType::class, $tag);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Vérifier l'unicité du slug (sauf pour le tag actuel)
            $slugExists = $this->tagRepository->findOneBy(['slug' => $tag->getSlug()]);
            if ($slugExists && $slugExists->getId() !== $tag->getId()) {
                $tag->setSlug($tag->getSlug() . '-' . time());
            }

            $this->entityManager->flush();

            // Logger l'action
            $this->logAction('UPDATE', Tag::class, $tag->getId(), "Tag modifié : {$tag->getName()}");

            $this->addFlash('success', 'Tag modifié avec succès.');

            return $this->redirectToRoute('app_admin_tags_index');
        }

        return $this->render('admin/tags/edit.html.twig', [
            'form' => $form,
            'tag' => $tag,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Tag $tag, Request $request): Response
    {
        if ($this->isCsrfTokenValid('delete_tag_' . $tag->getId(), $request->request->get('_token'))) {
            // Vérifier si le tag est utilisé
            if ($tag->getBlogPosts()->count() > 0) {
                $this->addFlash('error', 'Impossible de supprimer ce tag car il est utilisé par ' . $tag->getBlogPosts()->count() . ' article(s).');
                return $this->redirectToRoute('app_admin_tags_index');
            }

            $name = $tag->getName();
            $tagId = $tag->getId();

            $this->entityManager->remove($tag);
            $this->entityManager->flush();

            // Logger l'action
            $this->logAction('DELETE', Tag::class, $tagId, "Tag supprimé : {$name}");

            $this->addFlash('success', 'Tag supprimé avec succès.');
        }

        return $this->redirectToRoute('app_admin_tags_index');
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


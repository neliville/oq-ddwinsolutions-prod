<?php

namespace App\Controller\Admin;

use App\Entity\AdminLog;
use App\Entity\Category;
use App\Form\CategoryFormType;
use App\Repository\AdminLogRepository;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin/categories', name: 'app_admin_categories_')]
#[IsGranted('ROLE_ADMIN')]
final class CategoryController extends AbstractController
{
    public function __construct(
        private readonly CategoryRepository $categoryRepository,
        private readonly AdminLogRepository $adminLogRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly RequestStack $requestStack,
        private readonly SluggerInterface $slugger,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        $categories = $this->categoryRepository->findBy([], ['order' => 'ASC', 'name' => 'ASC']);

        return $this->render('admin/categories/index.html.twig', [
            'categories' => $categories,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $category = new Category();
        $form = $this->createForm(CategoryFormType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Générer le slug automatiquement s'il est vide
            if (empty($category->getSlug())) {
                $slug = strtolower($this->slugger->slug($category->getName())->toString());
                $category->setSlug($slug);
            }

            // Vérifier l'unicité du slug
            $slugExists = $this->categoryRepository->findOneBy(['slug' => $category->getSlug()]);
            if ($slugExists) {
                $category->setSlug($category->getSlug() . '-' . time());
            }

            $this->entityManager->persist($category);
            $this->entityManager->flush();

            // Logger l'action
            $this->logAction('CREATE', Category::class, $category->getId(), "Catégorie créée : {$category->getName()}");

            $this->addFlash('success', 'Catégorie créée avec succès.');

            return $this->redirectToRoute('app_admin_categories_index');
        }

        return $this->render('admin/categories/new.html.twig', [
            'form' => $form,
            'category' => $category,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Category $category, Request $request): Response
    {
        $form = $this->createForm(CategoryFormType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Vérifier l'unicité du slug (sauf pour la catégorie actuelle)
            $slugExists = $this->categoryRepository->findOneBy(['slug' => $category->getSlug()]);
            if ($slugExists && $slugExists->getId() !== $category->getId()) {
                $category->setSlug($category->getSlug() . '-' . time());
            }

            $this->entityManager->flush();

            // Logger l'action
            $this->logAction('UPDATE', Category::class, $category->getId(), "Catégorie modifiée : {$category->getName()}");

            $this->addFlash('success', 'Catégorie modifiée avec succès.');

            return $this->redirectToRoute('app_admin_categories_index');
        }

        return $this->render('admin/categories/edit.html.twig', [
            'form' => $form,
            'category' => $category,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Category $category, Request $request): Response
    {
        if ($this->isCsrfTokenValid('delete_category_' . $category->getId(), $request->request->get('_token'))) {
            // Vérifier si la catégorie est utilisée
            if ($category->getBlogPosts()->count() > 0) {
                $this->addFlash('error', 'Impossible de supprimer cette catégorie car elle est utilisée par ' . $category->getBlogPosts()->count() . ' article(s).');
                return $this->redirectToRoute('app_admin_categories_index');
            }

            $name = $category->getName();
            $categoryId = $category->getId();

            $this->entityManager->remove($category);
            $this->entityManager->flush();

            // Logger l'action
            $this->logAction('DELETE', Category::class, $categoryId, "Catégorie supprimée : {$name}");

            $this->addFlash('success', 'Catégorie supprimée avec succès.');
        }

        return $this->redirectToRoute('app_admin_categories_index');
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


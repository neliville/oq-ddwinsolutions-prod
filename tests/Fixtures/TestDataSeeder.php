<?php

namespace App\Tests\Fixtures;

use App\Entity\BlogPost;
use App\Entity\Category;
use App\Entity\CmsPage;
use Doctrine\ORM\EntityManagerInterface;

final class TestDataSeeder
{
    public static function seed(EntityManagerInterface $entityManager): void
    {
        self::ensureCmsPages($entityManager);
        self::ensureBlogContent($entityManager);
    }

    private static function ensureCmsPages(EntityManagerInterface $entityManager): void
    {
        $repository = $entityManager->getRepository(CmsPage::class);
        $pages = [
            'legal-notice' => 'Mentions légales',
            'privacy-policy' => 'Politique de confidentialité',
        ];

        $created = false;
        foreach ($pages as $slug => $title) {
            if ($repository->findOneBy(['slug' => $slug]) instanceof CmsPage) {
                continue;
            }

            $page = new CmsPage();
            $page->setTitle($title);
            $page->setSlug($slug);
            $page->setContent(sprintf('<h2>%s</h2><p>Contenu de test pour les environnements automatisés.</p>', $title));
            $entityManager->persist($page);
            $created = true;
        }

        if ($created) {
            $entityManager->flush();
        }
    }

    private static function ensureBlogContent(EntityManagerInterface $entityManager): void
    {
        $categoryRepository = $entityManager->getRepository(Category::class);
        $category = $categoryRepository->findOneBy(['slug' => 'qualite']);

        if (!$category instanceof Category) {
            $category = new Category();
            $category->setSlug('qualite');
            $category->setName('Qualité');
            $category->setDescription('Articles de test générés pour les scénarios fonctionnels.');
            $category->setColor('#4f46e5');
            $category->setIcon('bookmark');
            $category->setOrder(1);
            $entityManager->persist($category);
        }

        $blogRepository = $entityManager->getRepository(BlogPost::class);
        if ($blogRepository->findOneBy(['slug' => 'outil-qualite-de-test']) instanceof BlogPost) {
            $entityManager->flush();
            return;
        }

        $post = new BlogPost();
        $post->setTitle('Outil qualité de test');
        $post->setSlug('outil-qualite-de-test');
        $post->setExcerpt('Un article automatique pour valider les pages du blog.');
        $post->setContent('<p>Contenu généré pour les tests automatisés du blog.</p>');
        $post->setCategory($category);
        $post->setReadTime('4 min');
        $post->setPublishedAt(new \DateTimeImmutable('-1 day'));
        $post->setFeatured(false);
        $post->setViews(0);
        $entityManager->persist($post);

        $entityManager->flush();
    }
}

<?php

namespace App\Tests\Functional\Admin;

use App\Entity\BlogPost;
use App\Entity\Category;
use App\Tests\Functional\FixturesTrait;
use App\Tests\TestCase\WebTestCaseWithDatabase;

class BlogControllerTest extends WebTestCaseWithDatabase
{
    use FixturesTrait;

    public function testBlogListIsAccessible(): void
    {
        $entityManager = $this->entityManager;
        $entityManager->createQuery('DELETE FROM App\\Entity\\BlogPost b')->execute();
        $entityManager->createQuery('DELETE FROM App\\Entity\\Category c')->execute();
        $entityManager->createQuery('DELETE FROM App\\Entity\\User u')->execute();
        $this->loadAdminUser($entityManager);

        $category = new Category();
        $category->setName('Actualités');
        $category->setSlug('actualites');
        $category->setColor('#FF6B6B');
        $entityManager->persist($category);
        $entityManager->flush();

        $blogPost = new BlogPost();
        $blogPost->setTitle('Test Blog Post');
        $blogPost->setExcerpt('Résumé de test');
        $blogPost->setContent('Contenu de test');
        $blogPost->setSlug('test-blog-post');
        $blogPost->setReadTime('3 min');
        $blogPost->setCategory($category);
        $entityManager->persist($blogPost);
        $entityManager->flush();

        $this->client->loginUser($this->getAdminUser($entityManager));
        $this->client->request('GET', '/admin/blog');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Gestion');
    }
}

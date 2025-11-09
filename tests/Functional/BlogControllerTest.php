<?php

namespace App\Tests\Functional;

use App\Entity\BlogPost;
use App\Entity\Category;
use App\Repository\BlogPostRepository;
use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class BlogControllerTest extends WebTestCase
{
    public function testBlogIndexPageIsAccessible(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();
        
        
        $client->request('GET', '/blog');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Blog');
    }

    public function testBlogArticlePageIsAccessible(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();
        
        // Créer un article de test avec slug unique
        $uniqueSlug = 'test-article-' . uniqid();
        $categorySlug = 'test-category-' . uniqid();
        
        $category = new Category();
        $category->setName('Test Category');
        $category->setSlug($categorySlug);
        $category->setColor('#667eea');
        $category->setIcon('test-icon');
        $entityManager->persist($category);
        
        $article = new BlogPost();
        $article->setTitle('Test Article');
        $article->setSlug($uniqueSlug);
        $article->setExcerpt('Test excerpt');
        $article->setContent('Test content');
        $article->setCategory($category);
        $article->setReadTime('5 min');
        $article->setPublishedAt(new \DateTimeImmutable());
        $entityManager->persist($article);
        $entityManager->flush();

        $client->request('GET', '/blog/' . $categorySlug . '/' . $uniqueSlug);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Test Article');
    }

    public function testBlogArticleNotFound(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();
        
        
        $client->request('GET', '/blog/non-existent/test-slug');

        $this->assertResponseStatusCodeSame(404);
    }
}


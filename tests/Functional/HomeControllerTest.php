<?php

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class HomeControllerTest extends WebTestCase
{
    public function testHomePageIsAccessible(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Vos outils qualité professionnels');
    }

    public function testHomePageContainsExpectedSections(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('nav');
        $this->assertSelectorExists('footer');
        $this->assertGreaterThan(0, $crawler->filter('#outils')->count());
        $this->assertGreaterThan(0, $crawler->filter('#fonctionnalites')->count());
        $this->assertGreaterThan(0, $crawler->filter('#newsletter')->count());
    }
}

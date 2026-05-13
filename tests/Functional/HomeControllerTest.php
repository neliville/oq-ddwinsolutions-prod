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
        $this->assertSelectorTextContains('h1', 'Pilotez vos analyses, audits et actions QHSE au même endroit');
    }

    public function testHomePageContainsExpectedSections(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');
        $siteFooter = $crawler->filter('footer')->reduce(
            static fn ($node): bool => str_contains($node->text(), "Outils d'Analyse Gratuits")
        )->first();

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('nav');
        $this->assertSelectorExists('footer');
        $this->assertGreaterThan(0, $siteFooter->count());
        $this->assertStringContainsString("Outils d'Analyse Gratuits", $siteFooter->text());
        $this->assertGreaterThan(0, $crawler->filter('#outils')->count());
        $this->assertGreaterThan(0, $crawler->filter('#fonctionnalites')->count());
        $this->assertGreaterThan(0, $crawler->filter('#newsletter')->count());
    }

    public function testHomePageHasKeyContentAndOutToolsLink(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('#home-dashboard-heading', 'Votre cockpit QHSE quotidien');
        $this->assertGreaterThan(0, $crawler->selectLink('Explorer tous les outils')->count());
    }
}

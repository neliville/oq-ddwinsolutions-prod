<?php

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class FiveWhyControllerTest extends WebTestCase
{
    public function testFiveWhyPageIsAccessible(): void
    {
        $client = static::createClient();
        $client->followRedirects();
        $client->request('GET', '/5pourquoi/');

        $this->assertResponseIsSuccessful();
        // La page peut avoir un formulaire ou d'autres éléments, vérifier juste qu'elle est accessible
        $this->assertSelectorExists('body');
    }

    public function testFiveWhyPageIsAccessibleWithoutAuthentication(): void
    {
        $client = static::createClient();
        $client->followRedirects();
        $client->request('GET', '/5pourquoi/');

        $this->assertResponseIsSuccessful();
        // La page doit être accessible même sans authentification
    }
}

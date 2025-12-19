<?php

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class OutilsControllerTest extends WebTestCase
{
    public function testOutilsPageIsAccessible(): void
    {
        $client = static::createClient();
        $client->request('GET', '/outils');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('body');
    }

    public function testOutilsPageIsAccessibleWithoutAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/outils');

        $this->assertResponseIsSuccessful();
        // La page doit être accessible même sans authentification
    }
}


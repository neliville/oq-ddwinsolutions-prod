<?php

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AmdecControllerTest extends WebTestCase
{
    public function testAmdecPageIsAccessible(): void
    {
        $client = static::createClient();
        $client->request('GET', '/amdec');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('body');
    }

    public function testAmdecPageIsAccessibleWithoutAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/amdec');

        $this->assertResponseIsSuccessful();
        // La page doit être accessible même sans authentification
    }

    public function testAmdecPageWithLoadParameter(): void
    {
        $client = static::createClient();
        $client->request('GET', '/amdec?load=123');

        $this->assertResponseIsSuccessful();
    }
}


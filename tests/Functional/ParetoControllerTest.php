<?php

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ParetoControllerTest extends WebTestCase
{
    public function testParetoPageIsAccessible(): void
    {
        $client = static::createClient();
        $client->request('GET', '/pareto');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('body');
    }

    public function testParetoPageIsAccessibleWithoutAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/pareto');

        $this->assertResponseIsSuccessful();
        // La page doit être accessible même sans authentification
    }

    public function testParetoPageWithLoadParameter(): void
    {
        $client = static::createClient();
        $client->request('GET', '/pareto?load=123');

        $this->assertResponseIsSuccessful();
    }
}


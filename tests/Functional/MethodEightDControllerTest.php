<?php

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class MethodEightDControllerTest extends WebTestCase
{
    public function testMethod8DPageIsAccessible(): void
    {
        $client = static::createClient();
        $client->request('GET', '/methode-8d');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('body');
    }

    public function testMethod8DPageIsAccessibleWithoutAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/methode-8d');

        $this->assertResponseIsSuccessful();
        // La page doit être accessible même sans authentification
    }

    public function testMethod8DPageWithLoadParameter(): void
    {
        $client = static::createClient();
        $client->request('GET', '/methode-8d?load=123');

        $this->assertResponseIsSuccessful();
    }
}


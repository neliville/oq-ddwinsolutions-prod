<?php

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class QqoqccpControllerTest extends WebTestCase
{
    public function testQqoqccpPageIsAccessible(): void
    {
        $client = static::createClient();
        $client->request('GET', '/qqoqccp');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('body');
    }

    public function testQqoqccpPageIsAccessibleWithoutAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/qqoqccp');

        $this->assertResponseIsSuccessful();
        // La page doit être accessible même sans authentification
    }

    public function testQqoqccpPageWithLoadParameter(): void
    {
        $client = static::createClient();
        $client->request('GET', '/qqoqccp?load=123');

        $this->assertResponseIsSuccessful();
    }
}


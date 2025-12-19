<?php

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class IshikawaControllerTest extends WebTestCase
{
    public function testIshikawaPageIsAccessible(): void
    {
        $client = static::createClient();
        $client->followRedirects();
        $client->request('GET', '/ishikawa/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.ishikawa-page');
    }

    public function testIshikawaPageIsAccessibleWithoutAuthentication(): void
    {
        $client = static::createClient();
        $client->followRedirects();
        $client->request('GET', '/ishikawa/');

        $this->assertResponseIsSuccessful();
        // La page doit être accessible même sans authentification
    }
}

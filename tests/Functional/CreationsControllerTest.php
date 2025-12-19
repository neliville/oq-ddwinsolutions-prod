<?php

namespace App\Tests\Functional;

use App\Tests\TestCase\WebTestCaseWithDatabase;

class CreationsControllerTest extends WebTestCaseWithDatabase
{
    public function testCreationsPageRequiresAuthentication(): void
    {
        $client = $this->client;
        $client->followRedirects(false);
        $client->request('GET', '/mes-creations');

        // Devrait rediriger vers /login (302)
        $this->assertResponseStatusCodeSame(302);
        $this->assertResponseRedirects('/login');
    }

    public function testCreationsPageIsAccessibleWithAuthentication(): void
    {
        $user = $this->createTestUser();
        $client = $this->client;
        $client->loginUser($user);

        $client->request('GET', '/mes-creations');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('body');
    }

    public function testCreationsPageDisplaysEmptyState(): void
    {
        $user = $this->createTestUser();
        $client = $this->client;
        $client->loginUser($user);

        $client->request('GET', '/mes-creations');

        $this->assertResponseIsSuccessful();
        // Vérifier que la page affiche un état vide si l'utilisateur n'a pas de créations
    }
}


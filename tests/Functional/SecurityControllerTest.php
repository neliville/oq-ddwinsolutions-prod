<?php

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Tests du contrôleur de sécurité
 * 
 * Avec DAMADoctrineTestBundle, le schéma de base de données est créé une fois pour toutes
 * et chaque test est isolé dans une transaction qui est rollback après le test.
 */
class SecurityControllerTest extends WebTestCase
{
    public function testLoginPageIsAccessible(): void
    {
        $client = static::createClient();
        
        $client->request('GET', '/login');

        $this->assertResponseIsSuccessful();
        // Vérifier que la page contient le formulaire de connexion
        $this->assertSelectorExists('form');
        // Vérifier que la page contient "Connexion" quelque part (h1, h2 ou autre)
        $this->assertSelectorTextContains('body', 'Connexion');
    }

    public function testLoginWithInvalidCredentials(): void
    {
        $client = static::createClient();
        $client->followRedirects(false); // Ne pas suivre automatiquement les redirections
        
        $crawler = $client->request('GET', '/login');

        $form = $crawler->selectButton('Se connecter')->form([
            '_username' => 'invalid@example.com',
            '_password' => 'wrongpassword',
        ]);
        
        $client->submit($form);

        // Vérifier directement la réponse sans accéder à la DB via les assertions Symfony
        // Selon la doc Symfony, utiliser getResponse() pour éviter les accès DB implicites
        $response = $client->getResponse();
        $this->assertSame(302, $response->getStatusCode());
        $location = $response->headers->get('Location');
        $this->assertStringContainsString('/login', $location);
    }
}

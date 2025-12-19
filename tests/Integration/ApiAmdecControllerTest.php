<?php

namespace App\Tests\Integration;

use App\Tests\TestCase\WebTestCaseWithDatabase;

class ApiAmdecControllerTest extends WebTestCaseWithDatabase
{
    public function testApiAmdecSaveRequiresAuthentication(): void
    {
        $client = $this->client;
        $client->followRedirects(false);
        $client->request('POST', '/api/amdec/save', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'title' => 'Test AMDEC',
            'content' => [],
        ]));

        // Devrait rediriger vers /login (302) ou retourner 401
        $this->assertTrue(
            in_array($client->getResponse()->getStatusCode(), [302, 401], true),
            'Devrait rediriger vers login (302) ou retourner 401'
        );
    }

    public function testApiAmdecSaveWithAuthentication(): void
    {
        $user = $this->createTestUser();
        $client = $this->client;
        $client->loginUser($user);

        $client->request('POST', '/api/amdec/save', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'title' => 'Test AMDEC Analysis',
            'content' => ['subject' => 'Test', 'entries' => []],
        ]));

        $this->assertResponseStatusCodeSame(201);
        $this->assertJson($client->getResponse()->getContent());
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($response['success'] ?? false);
        $this->assertSame('Test AMDEC Analysis', $response['data']['title'] ?? null);
        $this->assertNotEmpty($response['data']['id'] ?? null);
    }

    public function testApiAmdecListWithAuthentication(): void
    {
        $user = $this->createTestUser();
        $client = $this->client;
        $client->loginUser($user);

        $client->request('GET', '/api/amdec/list');

        $this->assertResponseIsSuccessful();
        $this->assertJson($client->getResponse()->getContent());
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('data', $response);
    }

    public function testApiAmdecGetWithAuthentication(): void
    {
        $user = $this->createTestUser();
        $client = $this->client;
        $client->loginUser($user);

        // Créer d'abord une analyse
        $client->request('POST', '/api/amdec/save', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'title' => 'Test AMDEC Get',
            'content' => ['subject' => 'Test', 'entries' => []],
        ]));

        $saveResponse = json_decode($client->getResponse()->getContent(), true);
        $analysisId = $saveResponse['data']['id'] ?? null;

        $this->assertNotEmpty($analysisId);

        // Récupérer l'analyse
        $client->request('GET', "/api/amdec/{$analysisId}");

        $this->assertResponseIsSuccessful();
        $this->assertJson($client->getResponse()->getContent());
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($response['success'] ?? false);
        $this->assertSame($analysisId, $response['data']['id'] ?? null);
    }

    public function testApiAmdecDeleteWithAuthentication(): void
    {
        $user = $this->createTestUser();
        $client = $this->client;
        $client->loginUser($user);

        // Créer d'abord une analyse
        $client->request('POST', '/api/amdec/save', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'title' => 'Test AMDEC Delete',
            'content' => ['subject' => 'Test', 'entries' => []],
        ]));

        $saveResponse = json_decode($client->getResponse()->getContent(), true);
        $analysisId = $saveResponse['data']['id'] ?? null;

        $this->assertNotEmpty($analysisId);

        // Supprimer l'analyse
        $client->request('DELETE', "/api/amdec/{$analysisId}");

        $this->assertResponseIsSuccessful();
        $this->assertJson($client->getResponse()->getContent());
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($response['success'] ?? false);
    }
}


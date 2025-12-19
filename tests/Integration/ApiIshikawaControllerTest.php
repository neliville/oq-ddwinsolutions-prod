<?php

namespace App\Tests\Integration;

use App\Tests\TestCase\WebTestCaseWithDatabase;

class ApiIshikawaControllerTest extends WebTestCaseWithDatabase
{

    public function testApiIshikawaSaveRequiresAuthentication(): void
    {
        $client = $this->client;
        $client->followRedirects(false);
        $client->request('POST', '/api/ishikawa/save', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'title' => 'Test Ishikawa',
            'content' => [],
        ]));

        // Devrait rediriger vers /login (302) ou retourner 401
        $this->assertTrue(
            in_array($client->getResponse()->getStatusCode(), [302, 401], true),
            'Devrait rediriger vers login (302) ou retourner 401'
        );
    }

    public function testApiIshikawaSaveWithAuthentication(): void
    {
        $user = $this->createTestUser();
        $client = $this->client;
        $client->loginUser($user);

        $client->request('POST', '/api/ishikawa/save', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'title' => 'Test Ishikawa Diagram',
            'content' => ['categories' => []],
        ]));

        $this->assertResponseStatusCodeSame(201);
        $this->assertJson($client->getResponse()->getContent());
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($response['success'] ?? false);
        $this->assertSame('Test Ishikawa Diagram', $response['data']['title'] ?? null);
        $this->assertNotEmpty($response['data']['id'] ?? null);
    }

    public function testApiIshikawaListWithAuthentication(): void
    {
        $user = $this->createTestUser();
        $client = $this->client;
        $client->loginUser($user);

        $client->request('GET', '/api/ishikawa/list');

        $this->assertResponseIsSuccessful();
        $this->assertJson($client->getResponse()->getContent());
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('data', $response);
    }

    public function testApiIshikawaGetWithAuthentication(): void
    {
        $user = $this->createTestUser();
        $client = $this->client;
        $client->loginUser($user);

        // Créer d'abord une analyse
        $client->request('POST', '/api/ishikawa/save', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'title' => 'Test Ishikawa Get',
            'content' => ['categories' => []],
        ]));

        $saveResponse = json_decode($client->getResponse()->getContent(), true);
        $analysisId = $saveResponse['data']['id'] ?? null;

        $this->assertNotEmpty($analysisId);

        // Récupérer l'analyse
        $client->request('GET', "/api/ishikawa/{$analysisId}");

        $this->assertResponseIsSuccessful();
        $this->assertJson($client->getResponse()->getContent());
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($response['success'] ?? false);
        $this->assertSame($analysisId, $response['data']['id'] ?? null);
    }

    public function testApiIshikawaUpdateWithAuthentication(): void
    {
        $user = $this->createTestUser();
        $client = $this->client;
        $client->loginUser($user);

        // Créer d'abord une analyse
        $client->request('POST', '/api/ishikawa/save', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'title' => 'Test Ishikawa Original',
            'content' => ['categories' => []],
        ]));

        $saveResponse = json_decode($client->getResponse()->getContent(), true);
        $analysisId = $saveResponse['data']['id'] ?? null;

        $this->assertNotEmpty($analysisId);

        // Mettre à jour l'analyse
        $client->request('POST', '/api/ishikawa/save', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'id' => $analysisId,
            'title' => 'Test Ishikawa Updated',
            'content' => ['categories' => []],
        ]));

        $this->assertResponseStatusCodeSame(200);
        $this->assertJson($client->getResponse()->getContent());
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($response['success'] ?? false);
        $this->assertSame('Test Ishikawa Updated', $response['data']['title'] ?? null);
    }

    public function testApiIshikawaDeleteWithAuthentication(): void
    {
        $user = $this->createTestUser();
        $client = $this->client;
        $client->loginUser($user);

        // Créer d'abord une analyse
        $client->request('POST', '/api/ishikawa/save', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'title' => 'Test Ishikawa Delete',
            'content' => ['categories' => []],
        ]));

        $saveResponse = json_decode($client->getResponse()->getContent(), true);
        $analysisId = $saveResponse['data']['id'] ?? null;

        $this->assertNotEmpty($analysisId);

        // Supprimer l'analyse
        $client->request('DELETE', "/api/ishikawa/{$analysisId}");

        $this->assertResponseIsSuccessful();
        $this->assertJson($client->getResponse()->getContent());
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($response['success'] ?? false);
    }
}

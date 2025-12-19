<?php

namespace App\Tests\Integration;

use App\Tests\TestCase\WebTestCaseWithDatabase;

class ApiEightDControllerTest extends WebTestCaseWithDatabase
{
    public function testApiEightDSaveRequiresAuthentication(): void
    {
        $client = $this->client;
        $client->followRedirects(false);
        $client->request('POST', '/api/eightd/save', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'title' => 'Test 8D',
            'content' => [],
        ]));

        // Devrait rediriger vers /login (302) ou retourner 401
        $this->assertTrue(
            in_array($client->getResponse()->getStatusCode(), [302, 401], true),
            'Devrait rediriger vers login (302) ou retourner 401'
        );
    }

    public function testApiEightDSaveWithAuthentication(): void
    {
        $user = $this->createTestUser();
        $client = $this->client;
        $client->loginUser($user);

        $client->request('POST', '/api/eightd/save', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'title' => 'Test 8D Analysis',
            'content' => ['problem' => 'Test'],
        ]));

        $this->assertResponseStatusCodeSame(201);
        $this->assertJson($client->getResponse()->getContent());
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($response['success'] ?? false);
        $this->assertSame('Test 8D Analysis', $response['data']['title'] ?? null);
        $this->assertNotEmpty($response['data']['id'] ?? null);
    }

    public function testApiEightDListWithAuthentication(): void
    {
        $user = $this->createTestUser();
        $client = $this->client;
        $client->loginUser($user);

        $client->request('GET', '/api/eightd/list');

        $this->assertResponseIsSuccessful();
        $this->assertJson($client->getResponse()->getContent());
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('data', $response);
    }

    public function testApiEightDGetWithAuthentication(): void
    {
        $user = $this->createTestUser();
        $client = $this->client;
        $client->loginUser($user);

        // Créer d'abord une analyse
        $client->request('POST', '/api/eightd/save', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'title' => 'Test 8D Get',
            'content' => ['problem' => 'Test'],
        ]));

        $saveResponse = json_decode($client->getResponse()->getContent(), true);
        $analysisId = $saveResponse['data']['id'] ?? null;

        $this->assertNotEmpty($analysisId);

        // Récupérer l'analyse
        $client->request('GET', "/api/eightd/{$analysisId}");

        $this->assertResponseIsSuccessful();
        $this->assertJson($client->getResponse()->getContent());
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($response['success'] ?? false);
        $this->assertSame($analysisId, $response['data']['id'] ?? null);
    }

    public function testApiEightDDeleteWithAuthentication(): void
    {
        $user = $this->createTestUser();
        $client = $this->client;
        $client->loginUser($user);

        // Créer d'abord une analyse
        $client->request('POST', '/api/eightd/save', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'title' => 'Test 8D Delete',
            'content' => ['problem' => 'Test'],
        ]));

        $saveResponse = json_decode($client->getResponse()->getContent(), true);
        $analysisId = $saveResponse['data']['id'] ?? null;

        $this->assertNotEmpty($analysisId);

        // Supprimer l'analyse
        $client->request('DELETE', "/api/eightd/{$analysisId}");

        $this->assertResponseIsSuccessful();
        $this->assertJson($client->getResponse()->getContent());
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($response['success'] ?? false);
    }
}


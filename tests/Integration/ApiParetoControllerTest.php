<?php

namespace App\Tests\Integration;

use App\Tests\TestCase\WebTestCaseWithDatabase;

class ApiParetoControllerTest extends WebTestCaseWithDatabase
{
    public function testApiParetoSaveRequiresAuthentication(): void
    {
        $client = $this->client;
        $client->followRedirects(false);
        $client->request('POST', '/api/pareto/save', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'title' => 'Test Pareto',
            'content' => [],
        ]));

        // Devrait rediriger vers /login (302) ou retourner 401
        $this->assertTrue(
            in_array($client->getResponse()->getStatusCode(), [302, 401], true),
            'Devrait rediriger vers login (302) ou retourner 401'
        );
    }

    public function testApiParetoSaveWithAuthentication(): void
    {
        $user = $this->createTestUser();
        $client = $this->client;
        $client->loginUser($user);

        $client->request('POST', '/api/pareto/save', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'title' => 'Test Pareto Analysis',
            'content' => ['entries' => []],
        ]));

        $this->assertResponseStatusCodeSame(201);
        $this->assertJson($client->getResponse()->getContent());
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($response['success'] ?? false);
        $this->assertSame('Test Pareto Analysis', $response['data']['title'] ?? null);
        $this->assertNotEmpty($response['data']['id'] ?? null);
    }

    public function testApiParetoListWithAuthentication(): void
    {
        $user = $this->createTestUser();
        $client = $this->client;
        $client->loginUser($user);

        $client->request('GET', '/api/pareto/list');

        $this->assertResponseIsSuccessful();
        $this->assertJson($client->getResponse()->getContent());
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('data', $response);
    }

    public function testApiParetoGetWithAuthentication(): void
    {
        $user = $this->createTestUser();
        $client = $this->client;
        $client->loginUser($user);

        // Créer d'abord une analyse
        $client->request('POST', '/api/pareto/save', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'title' => 'Test Pareto Get',
            'content' => ['entries' => []],
        ]));

        $saveResponse = json_decode($client->getResponse()->getContent(), true);
        $analysisId = $saveResponse['data']['id'] ?? null;

        $this->assertNotEmpty($analysisId);

        // Récupérer l'analyse
        $client->request('GET', "/api/pareto/{$analysisId}");

        $this->assertResponseIsSuccessful();
        $this->assertJson($client->getResponse()->getContent());
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($response['success'] ?? false);
        $this->assertSame($analysisId, $response['data']['id'] ?? null);
    }

    public function testApiParetoDeleteWithAuthentication(): void
    {
        $user = $this->createTestUser();
        $client = $this->client;
        $client->loginUser($user);

        // Créer d'abord une analyse
        $client->request('POST', '/api/pareto/save', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'title' => 'Test Pareto Delete',
            'content' => ['entries' => []],
        ]));

        $saveResponse = json_decode($client->getResponse()->getContent(), true);
        $analysisId = $saveResponse['data']['id'] ?? null;

        $this->assertNotEmpty($analysisId);

        // Supprimer l'analyse
        $client->request('DELETE', "/api/pareto/{$analysisId}");

        $this->assertResponseIsSuccessful();
        $this->assertJson($client->getResponse()->getContent());
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($response['success'] ?? false);
    }
}


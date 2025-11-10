<?php

namespace App\Tests\Integration;

use App\Tests\TestCase\WebTestCaseWithDatabase;

class ApiRecordControllerTest extends WebTestCaseWithDatabase
{

    public function testApiRecordsListRequiresAuthentication(): void
    {
        $client = $this->client;
        $client->followRedirects(false);
        $client->request('GET', '/api/records');

        // Devrait rediriger vers /login (302) ou retourner 401
        $this->assertTrue(
            in_array($client->getResponse()->getStatusCode(), [302, 401], true),
            'Devrait rediriger vers login (302) ou retourner 401'
        );
    }

    public function testApiRecordsListWithAuthentication(): void
    {
        $user = $this->createTestUser();
        $client = $this->client;
        $client->loginUser($user);

        $client->request('GET', '/api/records');

        $this->assertResponseIsSuccessful();
        $this->assertJson($client->getResponse()->getContent());
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('data', $response);
    }

    public function testApiRecordsCreateWithAuthentication(): void
    {
        $user = $this->createTestUser();
        $client = $this->client;
        $client->loginUser($user);

        $client->request('POST', '/api/records', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'title' => 'Test Record',
            'type' => 'test',
            'content' => ['test' => 'data'],
        ]));

        $this->assertResponseStatusCodeSame(201);
        $this->assertJson($client->getResponse()->getContent());
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($response['success'] ?? false);
        $this->assertArrayHasKey('data', $response);
    }

    public function testApiRecordsCreateWithInvalidData(): void
    {
        $user = $this->createTestUser();
        $client = $this->client;
        $client->loginUser($user);

        $client->request('POST', '/api/records', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'title' => 'Test Record',
            // Missing type and content
        ]));

        $this->assertResponseStatusCodeSame(400);
        $this->assertJson($client->getResponse()->getContent());
    }
}

<?php

namespace App\Tests\Functional\Api;

use App\Repository\ExportLogRepository;
use App\Tests\TestCase\WebTestCaseWithDatabase;
use Symfony\Component\HttpFoundation\Response;

class ExportTrackingControllerTest extends WebTestCaseWithDatabase
{
    public function testTrackExportRequiresAuthentication(): void
    {
        $this->client->followRedirects(false);
        $this->client->request(
            'POST',
            '/analytics/track-export',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['tool' => 'ishikawa', 'format' => 'pdf'], JSON_THROW_ON_ERROR)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
        $this->assertResponseRedirects();
        $this->assertStringContainsString('/login', $this->client->getResponse()->headers->get('Location') ?? '');
    }

    public function testTrackExportPersistsLog(): void
    {
        $user = $this->createTestUser();
        $this->client->loginUser($user);

        $payload = [
            'tool' => 'fivewhy',
            'format' => 'pdf',
            'metadata' => [
                'problem' => 'Test root cause',
            ],
        ];

        $this->client->request(
            'POST',
            '/analytics/track-export',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload, JSON_THROW_ON_ERROR)
        );

        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('ok', $responseData['status']);

        /** @var ExportLogRepository $repository */
        $repository = static::getContainer()->get(ExportLogRepository::class);
        $logs = $repository->findAll();

        $this->assertCount(1, $logs);
        $log = $logs[0];

        $this->assertSame('fivewhy', $log->getTool());
        $this->assertSame('pdf', $log->getFormat());
        $this->assertSame('Test root cause', $log->getMetadata()['problem'] ?? null);
    }

    public function testTrackExportValidatesPayload(): void
    {
        $user = $this->createTestUser();
        $this->client->loginUser($user);

        $this->client->request(
            'POST',
            '/analytics/track-export',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['tool' => 'ishikawa'], JSON_THROW_ON_ERROR)
        );

        $this->assertResponseStatusCodeSame(400);

        $responseData = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('error', $responseData['status']);
        $this->assertStringContainsString('Missing', $responseData['message']);
    }
}



<?php

namespace App\Tests\Functional\Api;

use App\Entity\ExportLog;
use App\Entity\User;
use App\Repository\ExportLogRepository;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ExportTrackingControllerTest extends WebTestCase
{
    public function testTrackExportPersistsLog(): void
    {
        $client = static::createClient();
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $schemaTool = new SchemaTool($entityManager);
        $classes = [
            $entityManager->getClassMetadata(ExportLog::class),
            $entityManager->getClassMetadata(User::class),
        ];
        try {
            $schemaTool->dropSchema($classes);
        } catch (\Exception $exception) {
        }
        $schemaTool->createSchema($classes);

        $payload = [
            'tool' => 'fivewhy',
            'format' => 'pdf',
            'metadata' => [
                'problem' => 'Test root cause',
            ],
        ];

        $client->request(
            'POST',
            '/analytics/track-export',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload, JSON_THROW_ON_ERROR)
        );

        $this->assertResponseIsSuccessful();

        $responseData = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
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
        $client = static::createClient();
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $schemaTool = new SchemaTool($entityManager);
        $classes = [
            $entityManager->getClassMetadata(ExportLog::class),
            $entityManager->getClassMetadata(User::class),
        ];
        try {
            $schemaTool->dropSchema($classes);
        } catch (\Exception $exception) {
        }
        $schemaTool->createSchema($classes);

        $client->request(
            'POST',
            '/analytics/track-export',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['tool' => 'ishikawa'], JSON_THROW_ON_ERROR)
        );

        $this->assertResponseStatusCodeSame(400);

        $responseData = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('error', $responseData['status']);
        $this->assertStringContainsString('Missing', $responseData['message']);
    }
}



<?php

namespace App\Tests\Functional\Admin;

use App\Entity\ExportLog;
use App\Tests\Functional\FixturesTrait;
use App\Tests\TestCase\WebTestCaseWithDatabase;

class ExportControllerTest extends WebTestCaseWithDatabase
{
    use FixturesTrait;

    public function testExportLogsAreAccessible(): void
    {
        $entityManager = $this->entityManager;
        $entityManager->createQuery('DELETE FROM App\\Entity\\ExportLog e')->execute();
        $entityManager->createQuery('DELETE FROM App\\Entity\\User u')->execute();
        $this->loadAdminUser($entityManager);

        $exportLog = new ExportLog();
        $exportLog->setUser($this->getAdminUser($entityManager));
        $exportLog->setTool('ishikawa');
        $exportLog->setFormat('pdf');
        $exportLog->setMetadata(['filename' => 'rapport.pdf']);
        $entityManager->persist($exportLog);
        $entityManager->flush();

        $this->client->loginUser($this->getAdminUser($entityManager));
        $this->client->request('GET', '/admin/analytics/exports');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'exports');
    }
}

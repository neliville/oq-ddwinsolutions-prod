<?php

namespace App\Tests\Functional\Admin;

use App\Tests\Functional\FixturesTrait;
use App\Tests\TestCase\WebTestCaseWithDatabase;

class UsersControllerTest extends WebTestCaseWithDatabase
{
    use FixturesTrait;

    public function testUsersListIsAccessible(): void
    {
        $entityManager = $this->entityManager;
        $entityManager->createQuery('DELETE FROM App\\Entity\\User u')->execute();
        $this->loadAdminUser($entityManager);
        $this->loadSampleUsers($entityManager);

        $this->client->loginUser($this->getAdminUser($entityManager));
        $this->client->request('GET', '/admin/users');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Gestion des utilisateurs');
    }
}

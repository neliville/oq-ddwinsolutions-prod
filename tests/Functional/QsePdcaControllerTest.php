<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Tests\TestCase\WebTestCaseWithDatabase;

final class QsePdcaControllerTest extends WebTestCaseWithDatabase
{
    public function testPdcaPageLoadsWithCockpitLinks(): void
    {
        $user = $this->createTestUser('pdca-test-' . uniqid() . '@example.com', 'Test123456!', ['ROLE_USER']);
        $this->client->loginUser($user);
        $this->client->request('GET', '/dashboard/qse/pdca');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Cockpit PDCA');
        $this->assertSelectorTextContains('body', 'CAPA en retard');
        $this->assertSelectorExists('a[href*="qse/capa"]');
    }
}

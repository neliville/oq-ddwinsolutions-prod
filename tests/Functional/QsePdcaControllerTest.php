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
        $this->assertSelectorTextContains('body', 'Score QHSE');
        $this->assertSelectorTextContains('body', 'À traiter aujourd’hui');
        $this->assertSelectorTextContains('body', 'Cycle PDCA');
        $this->assertSelectorTextContains('body', 'Activité récente');
        $this->assertSelectorExists('a[href*="qse/capa"]');
        $this->assertSelectorExists('.pdca-phase-card__surface');
        $this->assertSelectorExists('.pdca-priority-panel');
        $content = (string) $this->client->getResponse()->getContent();
        $priorityPos = strpos($content, 'pdca-priority-panel');
        $phasesPos = strpos($content, 'pdca-phases-heading');
        $this->assertNotFalse($priorityPos);
        $this->assertNotFalse($phasesPos);
        $this->assertGreaterThan($phasesPos, $priorityPos, '« À traiter aujourd’hui » doit apparaître sous le cycle PDCA.');
    }
}

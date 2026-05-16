<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Qse\CAPAAction;
use App\Entity\Qse\CapaOrigin;
use App\Entity\Qse\RiskMatrixEntry;
use App\Qse\Enum\RiskEntryStatus;
use App\Tests\TestCase\WebTestCaseWithDatabase;

final class QseRiskFunctionalTest extends WebTestCaseWithDatabase
{
    public function testCriticalRiskUnderSurveillanceWithoutCapaShowsError(): void
    {
        $user = $this->createTestUser('risk-crit-' . uniqid() . '@example.com', 'Test123456!');
        $this->client->loginUser($user);
        $crawler = $this->client->request('GET', '/dashboard/qse/risque/new');
        $this->assertResponseIsSuccessful();
        $csrf = (string) $crawler->filter('input[name="_token"]')->attr('value');

        $this->client->request('POST', '/dashboard/qse/risque/new', [
            '_token' => $csrf,
            'identified_risk' => 'Risque critique test',
            'description' => '',
            'concerned_process' => '',
            'risk_category' => 'Q',
            'severity' => '3',
            'probability' => '3',
            'detection' => '2',
            'existing_actions' => '',
            'responsible' => '',
            'status' => 'sous_surveillance',
        ]);
        $this->assertResponseIsSuccessful();
        $html = (string) $this->client->getResponse()->getContent();
        self::assertTrue(
            str_contains($html, 'Un risque critique doit')
            || str_contains($html, 'risque&#x20;critique&#x20;doit'),
            'Le message de validation CAPA obligatoire doit être visible (flash toast ou formulaire).',
        );
    }

    public function testRiskShowLinksCapa(): void
    {
        $user = $this->createTestUser('risk-link-' . uniqid() . '@example.com', 'Test123456!');
        $origin = $this->entityManager->getRepository(CapaOrigin::class)->findOneBy(['slug' => 'pareto']);
        self::assertInstanceOf(CapaOrigin::class, $origin);
        $capa = new CAPAAction();
        $capa->setOwner($user);
        $capa->setTitle('CAPA pour lien risque');
        $capa->setOrigin($origin);
        $this->entityManager->persist($capa);

        $risk = new RiskMatrixEntry();
        $risk->setOwner($user);
        $risk->setIdentifiedRisk('Risque simple');
        $risk->setStatus(RiskEntryStatus::IDENTIFIE);
        $this->entityManager->persist($risk);
        $this->entityManager->flush();

        $rid = (int) $risk->getId();
        $cid = (int) $capa->getId();

        $this->client->loginUser($user);
        $crawler = $this->client->request('GET', '/dashboard/qse/risque/' . $rid);
        $this->assertResponseIsSuccessful();
        $csrf = (string) $crawler->filter('input[name="_token"]')->first()->attr('value');

        $this->client->request('POST', '/dashboard/qse/risque/' . $rid, [
            '_token' => $csrf,
            'link_capa_id' => (string) $cid,
        ]);
        $this->assertResponseRedirects('/dashboard/qse/risque/' . $rid);
        $this->client->followRedirect();
        self::assertStringContainsString('CAPA pour lien risque', (string) $this->client->getResponse()->getContent());
    }
}

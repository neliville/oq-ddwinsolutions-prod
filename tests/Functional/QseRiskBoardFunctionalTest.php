<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Qse\RiskMatrixEntry;
use App\Qse\Enum\RiskEntryStatus;
use App\Tests\TestCase\WebTestCaseWithDatabase;

final class QseRiskBoardFunctionalTest extends WebTestCaseWithDatabase
{
    public function testRiskIndexRendersControlCenter(): void
    {
        $user = $this->createTestUser('risk-board-' . uniqid() . '@example.com', 'Test123456!');

        $risk = new RiskMatrixEntry();
        $risk->setOwner($user);
        $risk->setIdentifiedRisk('Risque fournisseur qualité');
        $risk->setSeverity(4);
        $risk->setProbability(3);
        $risk->setDetection(2);
        $risk->setCriticalityScore(24);
        $risk->setStatus(RiskEntryStatus::EN_ANALYSE);
        $risk->setResponsible('Responsable QSE');
        $this->entityManager->persist($risk);
        $this->entityManager->flush();

        $this->client->loginUser($user);
        $crawler = $this->client->request('GET', '/dashboard/qse/risque');
        $this->assertResponseIsSuccessful();

        $html = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('Gestion des Risques QSE', $html);
        self::assertStringContainsString('Risk Control Center', $html);
        self::assertStringContainsString('Risques ouverts', $html);
        self::assertStringContainsString('Matrice Probabilité × Gravité', $html);
        self::assertStringContainsString('À traiter aujourd’hui', $html);
        self::assertStringContainsString('Dernières mises à jour', $html);
        self::assertStringContainsString('Registre des risques', $html);
        self::assertStringContainsString('Risque fournisseur qualité', $html);

        self::assertGreaterThan(0, $crawler->filter('.risk-kpi-strip')->count());
        self::assertGreaterThan(0, $crawler->filter('#risk-matrix-preview')->count());
        self::assertGreaterThan(0, $crawler->filter('.risk-card-row, .risk-board__list')->count());
    }

    public function testRiskIndexEmptyStateWhenNoEntries(): void
    {
        $user = $this->createTestUser('risk-empty-' . uniqid() . '@example.com', 'Test123456!');
        $this->client->loginUser($user);
        $this->client->request('GET', '/dashboard/qse/risque');
        $this->assertResponseIsSuccessful();

        $html = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('Votre matrice de risques est vide', $html);
        self::assertStringContainsString('Créer mon premier risque', $html);
        self::assertStringContainsString('Votre matrice apparaîtra ici', $html);
    }
}

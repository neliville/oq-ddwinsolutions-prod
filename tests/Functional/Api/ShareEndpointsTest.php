<?php

namespace App\Tests\Functional\Api;

use App\Entity\AmdecAnalysis;
use App\Entity\AmdecShare;
use App\Entity\FiveWhyAnalysis;
use App\Entity\FiveWhyShare;
use App\Entity\ParetoAnalysis;
use App\Entity\ParetoShare;
use App\Entity\QqoqccpAnalysis;
use App\Entity\QqoqccpShare;
use App\Entity\EightDAnalysis;
use App\Entity\EightDShare;
use App\Entity\User;
use App\Tests\TestCase\WebTestCaseWithDatabase;
use JsonException;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Response;

final class ShareEndpointsTest extends WebTestCaseWithDatabase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = $this->createTestUser('share-user@outils-qualite.com', 'share-user');
    }

    #[DataProvider('shareEndpointProvider')]
    public function testShareEndpointRequiresAuthentication(string $analysisFactory, string $endpoint, string $shareClass): void
    {
        $analysis = $this->{$analysisFactory}($this->user);

        $this->client->jsonRequest('POST', $endpoint, ['id' => $analysis->getId()]);

        $response = $this->client->getResponse();
        $this->assertTrue(
            $response->isRedirection() || $response->getStatusCode() === Response::HTTP_UNAUTHORIZED,
            'La route doit exiger une authentification.'
        );

        $share = $this->entityManager->getRepository($shareClass)->findOneBy(['analysis' => $analysis]);
        $this->assertNull($share, 'Aucun lien de partage ne doit être créé sans authentification.');
    }

    #[DataProvider('shareEndpointProvider')]
    public function testShareEndpointCreatesLink(string $analysisFactory, string $endpoint, string $shareClass): void
    {
        $analysis = $this->{$analysisFactory}($this->user);

        $this->client->loginUser($this->user);
        $this->client->jsonRequest('POST', $endpoint, ['id' => $analysis->getId()]);

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $payload = $this->getJsonResponseData();
        $this->assertTrue($payload['success'] ?? false);
        $this->assertArrayHasKey('data', $payload);
        $this->assertArrayHasKey('url', $payload['data']);
        $this->assertNotEmpty($payload['data']['url']);
        $this->assertNotEmpty($payload['data']['expiresAt']);

        $share = $this->entityManager->getRepository($shareClass)->findOneBy(['analysis' => $analysis]);
        $this->assertNotNull($share, 'Un lien de partage doit être enregistré dans la base.');
        $this->assertNotEmpty($share->getToken());
        $this->assertGreaterThan(new \DateTimeImmutable(), $share->getExpiresAt());
    }

    public static function shareEndpointProvider(): array
    {
        return [
            'five_why' => ['analysisFactory' => 'createFiveWhyAnalysis', 'endpoint' => '/api/fivewhy/share', 'shareClass' => FiveWhyShare::class],
            'pareto' => ['analysisFactory' => 'createParetoAnalysis', 'endpoint' => '/api/pareto/share', 'shareClass' => ParetoShare::class],
            'qqoqccp' => ['analysisFactory' => 'createQqoqccpAnalysis', 'endpoint' => '/api/qqoqccp/share', 'shareClass' => QqoqccpShare::class],
            'amdec' => ['analysisFactory' => 'createAmdecAnalysis', 'endpoint' => '/api/amdec/share', 'shareClass' => AmdecShare::class],
            'eightd' => ['analysisFactory' => 'createEightDAnalysis', 'endpoint' => '/api/eightd/share', 'shareClass' => EightDShare::class],
        ];
    }

    private function createFiveWhyAnalysis(User $user): FiveWhyAnalysis
    {
        $analysis = new FiveWhyAnalysis();
        $analysis->setUser($user);
        $analysis->setTitle('Analyse 5 Pourquoi');
        $analysis->setProblem('Problème exemple');
        $analysis->setData(json_encode([
            'problemStatement' => 'Problème exemple',
            'whySteps' => [
                ['question' => 'Pourquoi 1 ?', 'answer' => 'Parce que...'],
            ],
        ], JSON_THROW_ON_ERROR));

        $this->entityManager->persist($analysis);
        $this->entityManager->flush();

        return $analysis;
    }

    private function createParetoAnalysis(User $user): ParetoAnalysis
    {
        $analysis = new ParetoAnalysis();
        $analysis->setUser($user);
        $analysis->setTitle('Analyse Pareto');
        $analysis->setDescription('Description de test');
        $analysis->setData(json_encode([
            'entries' => [
                ['name' => 'Cause A', 'value' => 10],
                ['name' => 'Cause B', 'value' => 5],
            ],
        ], JSON_THROW_ON_ERROR));

        $this->entityManager->persist($analysis);
        $this->entityManager->flush();

        return $analysis;
    }

    private function createQqoqccpAnalysis(User $user): QqoqccpAnalysis
    {
        $analysis = new QqoqccpAnalysis();
        $analysis->setUser($user);
        $analysis->setTitle('Analyse QQOQCCP');
        $analysis->setSubject('Sujet test');
        $analysis->setDescription('Description test');
        $analysis->setData(json_encode([
            'subject' => 'Sujet test',
            'qui' => 'Equipe A',
            'quoi' => 'Processus B',
        ], JSON_THROW_ON_ERROR));

        $this->entityManager->persist($analysis);
        $this->entityManager->flush();

        return $analysis;
    }

    private function createAmdecAnalysis(User $user): AmdecAnalysis
    {
        $analysis = new AmdecAnalysis();
        $analysis->setUser($user);
        $analysis->setTitle('Analyse AMDEC');
        $analysis->setSubject('Machine X');
        $analysis->setDescription('Description AMDEC');
        $analysis->setData(json_encode([
            'subject' => 'Machine X',
            'items' => [
                ['mode' => 'Panne', 'gravity' => 8, 'occurrence' => 5, 'detection' => 4],
            ],
        ], JSON_THROW_ON_ERROR));

        $this->entityManager->persist($analysis);
        $this->entityManager->flush();

        return $analysis;
    }

    private function createEightDAnalysis(User $user): EightDAnalysis
    {
        $analysis = new EightDAnalysis();
        $analysis->setUser($user);
        $analysis->setTitle('Analyse 8D');
        $analysis->setDescription('Description 8D');
        $analysis->setData(json_encode([
            'team' => ['Membre 1', 'Membre 2'],
            'steps' => [
                ['title' => 'D1', 'summary' => 'Former l\'équipe'],
            ],
        ], JSON_THROW_ON_ERROR));

        $this->entityManager->persist($analysis);
        $this->entityManager->flush();

        return $analysis;
    }

    private function getJsonResponseData(): array
    {
        try {
            return json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            $this->fail('Réponse JSON invalide : ' . $exception->getMessage());
        }

        return [];
    }
}

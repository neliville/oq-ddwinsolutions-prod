<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Qse\CAPAAction;
use App\Entity\User;
use App\Tests\TestCase\WebTestCaseWithDatabase;
use Symfony\Component\HttpFoundation\Response;

final class QseCapaManualCreateFunctionalTest extends WebTestCaseWithDatabase
{
    public function testCapaIndexShowsNewCapaButton(): void
    {
        $user = $this->createTestUser('capa-manual-ui-' . uniqid() . '@example.com', 'Test123456!');
        $this->client->loginUser($user);
        $crawler = $this->client->request('GET', '/dashboard/qse/capa');
        $this->assertResponseIsSuccessful();
        $this->assertGreaterThan(0, $crawler->filter('form[action*="/dashboard/qse/capa/new"]')->count());
        $this->assertStringContainsString('CAPA Management Board', $this->client->getResponse()->getContent());
    }

    public function testNewManualCapaCreatesDraftWithAutreOrigin(): void
    {
        $user = $this->createTestUser('capa-manual-' . uniqid() . '@example.com', 'Test123456!');
        $this->client->loginUser($user);
        $crawler = $this->client->request('GET', '/dashboard/qse/capa');
        $token = $crawler->filter('form[action*="/dashboard/qse/capa/new"] input[name="_token"]')->first()->attr('value');
        self::assertNotEmpty($token);

        $this->client->request('POST', '/dashboard/qse/capa/new', ['_token' => $token]);
        $this->assertResponseRedirects();
        $this->client->followRedirect();
        $this->assertStringContainsString('/dashboard/qse/capa/', $this->client->getRequest()->getPathInfo());

        $this->entityManager->clear();
        $userReloaded = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $user->getEmail()]);
        self::assertNotNull($userReloaded);
        $capa = $this->entityManager->getRepository(CAPAAction::class)->findOneBy(['owner' => $userReloaded]);
        self::assertInstanceOf(CAPAAction::class, $capa);
        self::assertSame('autre', $capa->getOrigin()?->getSlug());
        self::assertSame('manual', $capa->getMetadata()['source'] ?? null);
        self::assertNull($capa->getSourceAuditEvaluation());
        self::assertNull($capa->getSourceTool());
    }

    public function testNewManualRejectsInvalidCsrf(): void
    {
        $user = $this->createTestUser('capa-manual-csrf-' . uniqid() . '@example.com', 'Test123456!');
        $this->client->loginUser($user);
        $this->client->request('POST', '/dashboard/qse/capa/new', ['_token' => 'invalid']);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }
}

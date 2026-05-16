<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\IshikawaAnalysis;
use App\Entity\Qse\Audit;
use App\Entity\Qse\AuditEvaluation;
use App\Entity\Qse\AuditRequirement;
use App\Entity\Qse\AuditStandard;
use App\Entity\Qse\CAPAAction;
use App\Entity\User;
use App\Entity\UserPreferences;
use App\Tests\TestCase\WebTestCaseWithDatabase;

class QseCockpitFunctionalTest extends WebTestCaseWithDatabase
{
    public function testQseAuditRequiresAuthentication(): void
    {
        $this->client->request('GET', '/dashboard/qse/audit');

        $this->assertResponseRedirects('/login');
    }

    public function testQseAuditOwnershipAndCapaSuggestion(): void
    {
        $user = $this->createTestUser('qse-test-' . uniqid() . '@example.com', 'Test123456!');
        $this->seedOneRequirement();

        $this->client->loginUser($user);
        $std = $this->entityManager->getRepository(AuditStandard::class)->findOneBy(['code' => 'iso_9001']);
        $this->assertInstanceOf(AuditStandard::class, $std);
        $sid = (int) $std->getId();
        $crawler = $this->client->request('GET', '/dashboard/qse/audit/new?standard=' . $sid);
        $this->assertResponseIsSuccessful();
        $csrf = $crawler->filter('input[name="_token"]')->attr('value');
        $this->assertNotEmpty($csrf);

        $this->client->request('POST', '/dashboard/qse/audit/new?standard=' . $sid, [
            '_token' => $csrf,
            'audit_standard_id' => (string) $sid,
            'companyName' => 'ACME',
            'mainAuditor' => 'Alice',
            'auditedAt' => '2026-05-01',
            'auditVersion' => '1.0',
        ]);
        $this->assertResponseRedirects();
        $this->client->followRedirect();
        $this->entityManager->clear();
        $userReloaded = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $user->getEmail()]);
        $this->assertNotNull($userReloaded);
        $audit = $this->entityManager->getRepository(Audit::class)->findOneBy(['owner' => $userReloaded]);
        $this->assertInstanceOf(Audit::class, $audit);

        $req = $this->entityManager->getRepository(AuditRequirement::class)->findOneBy([]);
        $this->assertNotNull($req);
        $chapter = $req->getChapter();

        $crawlerCh = $this->client->request('GET', '/dashboard/qse/audit/' . $audit->getId() . '?chapter=' . rawurlencode($chapter));
        $this->assertResponseIsSuccessful();
        $html = $this->client->getResponse()->getContent();
        $this->assertCount(1, $crawlerCh->filter('#audit-chapter-form'));
        $this->assertStringContainsString('form="audit-chapter-form"', $html, 'Le bouton d’enregistrement doit référencer le formulaire via l’attribut HTML form.');
        $this->assertGreaterThan(0, $crawlerCh->filter('.audit-req-card')->count());
        $csrfCh = $crawlerCh->filter('form input[name="_token"]')->first()->attr('value');
        $this->assertNotEmpty($csrfCh);
        $this->client->request('POST', '/dashboard/qse/audit/' . $audit->getId() . '/chapter', [
            '_token' => $csrfCh,
            'chapter' => $chapter,
            'eval' => [
                (string) $req->getId() => [
                    'verdict' => 'observation',
                    'comment' => 'Écart test',
                    'evidence' => 'DOC-1',
                ],
            ],
        ]);
        $this->assertResponseRedirects();

        $auditId = $audit->getId();
        $reqId = $req->getId();
        $this->entityManager->clear();
        $auditReloaded = $this->entityManager->getRepository(Audit::class)->find($auditId);
        $reqReloaded = $this->entityManager->getRepository(AuditRequirement::class)->find($reqId);
        $ev = $this->entityManager->getRepository(AuditEvaluation::class)->findOneBy([
            'audit' => $auditReloaded,
            'requirement' => $reqReloaded,
        ]);
        $this->assertInstanceOf(AuditEvaluation::class, $ev);

        $this->client->request('GET', '/dashboard/qse/audit/' . $audit->getId() . '/suggest-capa/' . $ev->getId());
        $this->assertResponseRedirects();
        $this->client->followRedirect();
        $this->assertStringContainsString('/dashboard/qse/capa/', $this->client->getRequest()->getPathInfo());
    }

    public function testAuditCreationWithOnboardingOriginRedirectsToDashboard(): void
    {
        $user = $this->createTestUser('qse-onb-audit-' . uniqid() . '@example.com', 'Test123456!');
        $this->client->loginUser($user);
        $std = $this->entityManager->getRepository(AuditStandard::class)->findOneBy(['code' => 'iso_9001']);
        $this->assertInstanceOf(AuditStandard::class, $std);
        $standardId = (int) $std->getId();

        $crawler = $this->client->request('GET', '/dashboard/qse/audit/new?standard=' . $standardId . '&origin=onboarding');
        $this->assertResponseIsSuccessful();
        $csrf = $crawler->filter('input[name="_token"]')->attr('value');
        $this->assertNotEmpty($csrf);

        $this->client->request('POST', '/dashboard/qse/audit/new?standard=' . $standardId . '&origin=onboarding', [
            '_token' => $csrf,
            'audit_standard_id' => (string) $standardId,
            'companyName' => 'ACME',
            'mainAuditor' => 'Alice',
            'auditedAt' => '2026-05-01',
            'auditVersion' => '1.0',
        ]);

        $this->assertResponseRedirects();
        $location = (string) $this->client->getResponse()->headers->get('Location');
        $this->assertStringContainsString('/dashboard', $location);
        $this->assertStringContainsString('activation=audit_created', $location);

        $this->entityManager->clear();
        $userReloaded = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $user->getEmail()]);
        $this->assertNotNull($userReloaded);
        $prefs = $this->entityManager->getRepository(UserPreferences::class)->findOneBy(['user' => $userReloaded]);
        $this->assertInstanceOf(UserPreferences::class, $prefs);
        $this->assertNotEmpty($prefs->getActivationState()['first_action_completed_at'] ?? null);
    }

    public function testAuditCreationWithoutOnboardingOriginRedirectsToAuditShow(): void
    {
        $user = $this->createTestUser('qse-audit-std-' . uniqid() . '@example.com', 'Test123456!');
        $this->client->loginUser($user);
        $std = $this->entityManager->getRepository(AuditStandard::class)->findOneBy(['code' => 'iso_9001']);
        $this->assertInstanceOf(AuditStandard::class, $std);
        $standardId = (int) $std->getId();

        $crawler = $this->client->request('GET', '/dashboard/qse/audit/new?standard=' . $standardId);
        $this->assertResponseIsSuccessful();
        $csrf = $crawler->filter('input[name="_token"]')->attr('value');
        $this->assertNotEmpty($csrf);

        $this->client->request('POST', '/dashboard/qse/audit/new?standard=' . $standardId, [
            '_token' => $csrf,
            'audit_standard_id' => (string) $standardId,
            'companyName' => 'ACME',
            'mainAuditor' => 'Alice',
            'auditedAt' => '2026-05-01',
            'auditVersion' => '1.0',
        ]);

        $this->assertResponseRedirects();
        $location = (string) $this->client->getResponse()->headers->get('Location');
        $this->assertStringContainsString('/dashboard/qse/audit/', $location);
        $this->assertStringNotContainsString('activation=audit_created', $location);
    }

    public function testRiskCreationWithOnboardingOriginRedirectsToDashboard(): void
    {
        $user = $this->createTestUser('qse-onb-risk-' . uniqid() . '@example.com', 'Test123456!');
        $this->client->loginUser($user);

        $crawler = $this->client->request('GET', '/dashboard/qse/risque/new?origin=onboarding');
        $this->assertResponseIsSuccessful();
        $csrf = $crawler->filter('input[name="_token"]')->attr('value');
        $this->assertNotEmpty($csrf);

        $this->client->request('POST', '/dashboard/qse/risque/new?origin=onboarding', [
            '_token' => $csrf,
            'identified_risk' => 'Risque onboarding test',
            'risk_category' => 'Q',
            'status' => 'identifie',
        ]);

        $this->assertResponseRedirects();
        $location = (string) $this->client->getResponse()->headers->get('Location');
        $this->assertStringContainsString('/dashboard', $location);
        $this->assertStringContainsString('activation=risk_created', $location);

        $this->entityManager->clear();
        $userReloaded = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $user->getEmail()]);
        $this->assertNotNull($userReloaded);
        $prefs = $this->entityManager->getRepository(UserPreferences::class)->findOneBy(['user' => $userReloaded]);
        $this->assertInstanceOf(UserPreferences::class, $prefs);
        $this->assertNotEmpty($prefs->getActivationState()['first_action_completed_at'] ?? null);
    }

    public function testRiskCreationWithoutOnboardingOriginRedirectsToRiskShow(): void
    {
        $user = $this->createTestUser('qse-risk-std-' . uniqid() . '@example.com', 'Test123456!');
        $this->client->loginUser($user);

        $crawler = $this->client->request('GET', '/dashboard/qse/risque/new');
        $this->assertResponseIsSuccessful();
        $csrf = $crawler->filter('input[name="_token"]')->attr('value');
        $this->assertNotEmpty($csrf);

        $this->client->request('POST', '/dashboard/qse/risque/new', [
            '_token' => $csrf,
            'identified_risk' => 'Risque standard test',
            'risk_category' => 'Q',
            'status' => 'identifie',
        ]);

        $this->assertResponseRedirects();
        $location = (string) $this->client->getResponse()->headers->get('Location');
        $this->assertStringContainsString('/dashboard/qse/risque/', $location);
        $this->assertStringNotContainsString('activation=risk_created', $location);
    }

    public function testCapaPrefillFromIshikawaSetsOriginAndSource(): void
    {
        $user = $this->createTestUser('qse-ish-' . uniqid() . '@example.com', 'Test123456!');
        $ish = new IshikawaAnalysis();
        $ish->setTitle('Analyse test');
        $ish->setData('{}');
        $ish->setUser($user);
        $this->entityManager->persist($ish);
        $this->entityManager->flush();
        $ishId = $ish->getId();
        $this->assertNotNull($ishId);

        $this->client->loginUser($user);
        $this->client->request('GET', '/dashboard/qse/capa/prefill/ishikawa/corrective?entity=' . $ishId);
        $this->assertResponseRedirects();
        $this->client->followRedirect();
        $this->assertStringContainsString('/dashboard/qse/capa/', $this->client->getRequest()->getPathInfo());
        $this->entityManager->clear();
        $userReloaded = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $user->getEmail()]);
        $this->assertNotNull($userReloaded);
        $list = $this->entityManager->getRepository(CAPAAction::class)->findBy(['owner' => $userReloaded], ['id' => 'DESC'], 1);
        $capa = $list[0] ?? null;
        $this->assertInstanceOf(CAPAAction::class, $capa);
        $this->assertSame('ishikawa', $capa->getOrigin()?->getSlug());
        $this->assertSame('ishikawa', $capa->getSourceTool());
        $this->assertSame($ishId, $capa->getSourceToolEntityId());
    }

    public function testOtherUserCannotAccessAudit(): void
    {
        $u1 = $this->createTestUser('qse-a-' . uniqid() . '@example.com', 'Test123456!');
        $u2 = $this->createTestUser('qse-b-' . uniqid() . '@example.com', 'Test123456!');
        $this->seedOneRequirement();

        $std = $this->entityManager->getRepository(AuditStandard::class)->findOneBy(['code' => 'iso_9001']);
        $this->assertInstanceOf(AuditStandard::class, $std);
        $audit = new Audit();
        $audit->setOwner($u1);
        $audit->setAuditStandard($std);
        $audit->setAuditedAt(new \DateTimeImmutable('2026-01-01'));
        $audit->setCompanyName('Société 1');
        $this->entityManager->persist($audit);
        $this->entityManager->flush();
        $id = $audit->getId();

        $this->client->loginUser($u2);
        $this->client->request('GET', '/dashboard/qse/audit/' . $id);
        $this->assertResponseStatusCodeSame(404);
    }

    private function seedOneRequirement(): void
    {
        $std = $this->entityManager->getRepository(AuditStandard::class)->findOneBy(['code' => 'iso_9001']);
        if (!$std instanceof AuditStandard) {
            throw new \RuntimeException('AuditStandard iso_9001 manquant (bootstrap tests).');
        }
        $r = new AuditRequirement();
        $r->setAuditStandard($std);
        $r->setChapter('4. Contexte');
        $r->setIsoArticle('4.1');
        $r->setRequirementText('Test exigence');
        $r->setDisplayOrder(1);
        $r->setLegacyKey('exig_test_' . uniqid());
        $r->setActive(true);
        $this->entityManager->persist($r);
        $this->entityManager->flush();
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Unit\Qse\Service;

use App\Entity\Qse\Audit;
use App\Entity\Qse\AuditEvaluation;
use App\Entity\Qse\AuditRequirement;
use App\Entity\Qse\AuditStandard;
use App\Entity\User;
use App\Qse\Enum\AuditVerdict;
use App\Qse\Service\AuditEvaluationCapaFactory;
use App\Qse\Service\AuditEvaluationVerdictHelper;
use App\Qse\Service\CapaSystemOriginSeeder;
use App\Repository\Qse\CapaOriginRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class AuditEvaluationCapaFactoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropDatabase();
        if ($metadata !== []) {
            $schemaTool->createSchema($metadata);
        }
        (new CapaSystemOriginSeeder())->seed($this->entityManager);
    }

    public function testRejectsConformVerdict(): void
    {
        $evaluation = $this->createEvaluation(AuditVerdict::CONFORM);
        $factory = self::getContainer()->get(AuditEvaluationCapaFactory::class);
        $user = $evaluation->getOwner();
        self::assertNotNull($user);

        $this->expectException(\InvalidArgumentException::class);
        $factory->createDraftFromEvaluation($evaluation, $user);
    }

    public function testCreatesDraftForMinorNc(): void
    {
        $evaluation = $this->createEvaluation(AuditVerdict::MINOR_NC);
        $factory = self::getContainer()->get(AuditEvaluationCapaFactory::class);
        $user = $evaluation->getOwner();
        self::assertNotNull($user);

        $capa = $factory->createDraftFromEvaluation($evaluation, $user);
        self::assertSame('audit-interne', $capa->getOrigin()?->getSlug());
        self::assertSame($evaluation, $capa->getSourceAuditEvaluation());
        self::assertSame('audit_evaluation', $capa->getMetadata()['source'] ?? null);
    }

    private function createEvaluation(AuditVerdict $verdict): AuditEvaluation
    {
        $user = new User();
        $user->setEmail('factory-' . uniqid() . '@example.com');
        $user->setPassword('hash');
        $user->setRoles(['ROLE_USER']);
        $this->entityManager->persist($user);

        $std = new AuditStandard();
        $std->setCode('iso_test_' . uniqid());
        $std->setName('Test');
        $this->entityManager->persist($std);

        $req = new AuditRequirement();
        $req->setAuditStandard($std);
        $req->setChapter('4. Test');
        $req->setIsoArticle('4.1');
        $req->setRequirementText('Exigence test');
        $req->setDisplayOrder(1);
        $req->setLegacyKey('key_' . uniqid());
        $req->setActive(true);
        $this->entityManager->persist($req);

        $audit = new Audit();
        $audit->setOwner($user);
        $audit->setAuditStandard($std);
        $audit->setAuditedAt(new \DateTimeImmutable());
        $audit->setCompanyName('TestCo');
        $this->entityManager->persist($audit);

        $ev = new AuditEvaluation();
        $ev->setAudit($audit);
        $ev->setRequirement($req);
        $ev->setOwner($user);
        $ev->setVerdict($verdict);
        AuditEvaluationVerdictHelper::syncLegacyScore($ev);
        $this->entityManager->persist($ev);
        $this->entityManager->flush();

        return $ev;
    }
}

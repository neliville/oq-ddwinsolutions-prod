<?php

declare(strict_types=1);

namespace App\Tests\Unit\Collaboration;

use App\Collaboration\SharedAccessService;
use App\Collaboration\SharedResourceType;
use App\Entity\User;
use App\Repository\Qse\AuditRepository;
use App\Repository\Qse\CAPAActionRepository;
use App\Repository\SharedAccessRepository;
use App\Service\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class SharedAccessServiceTest extends TestCase
{
    public function testAssertOwnerHasResourceThrowsWhenAuditMissing(): void
    {
        $auditRepo = $this->createMock(AuditRepository::class);
        $auditRepo->method('findOneOwnedBy')->with(99, $this->isInstanceOf(User::class))->willReturn(null);

        $capaRepo = $this->createStub(CAPAActionRepository::class);
        $service = $this->makeService($auditRepo, $capaRepo);

        $owner = new User();
        $owner->setEmail('o@example.com');
        $owner->setPassword('x');
        $owner->setRoles(['ROLE_USER']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Audit');
        $service->assertOwnerHasResource($owner, SharedResourceType::AUDIT, 99);
    }

    public function testAssertOwnerHasResourceThrowsWhenCapaMissing(): void
    {
        $auditRepo = $this->createStub(AuditRepository::class);
        $capaRepo = $this->createMock(CAPAActionRepository::class);
        $capaRepo->method('findOneBy')->willReturn(null);

        $service = $this->makeService($auditRepo, $capaRepo);

        $owner = new User();
        $owner->setEmail('o@example.com');
        $owner->setPassword('x');
        $owner->setRoles(['ROLE_USER']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('CAPA');
        $service->assertOwnerHasResource($owner, SharedResourceType::CAPA, 42);
    }

    private function makeService(AuditRepository $auditRepo, CAPAActionRepository $capaRepo): SharedAccessService
    {
        return new SharedAccessService(
            $this->createStub(EntityManagerInterface::class),
            $this->createStub(SharedAccessRepository::class),
            $auditRepo,
            $capaRepo,
            $this->createStub(MailerService::class),
            $this->createStub(UrlGeneratorInterface::class),
            14,
        );
    }
}

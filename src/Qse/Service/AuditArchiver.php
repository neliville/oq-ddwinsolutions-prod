<?php

declare(strict_types=1);

namespace App\Qse\Service;

use App\Entity\Qse\Audit;
use App\Entity\Qse\AuditActivityLog;
use App\Entity\User;
use App\Qse\Enum\AuditExecutionStatus;
use Doctrine\ORM\EntityManagerInterface;

final class AuditArchiver
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function archive(Audit $audit, User $owner): void
    {
        $this->assertOwner($audit, $owner);
        $audit->setStatus(AuditExecutionStatus::ARCHIVE);
        $audit->setUpdatedAt(new \DateTimeImmutable());
        $this->log($audit, $owner, 'audit_archived');
        $this->entityManager->flush();
    }

    public function unarchive(Audit $audit, User $owner): void
    {
        $this->assertOwner($audit, $owner);
        $audit->setStatus(AuditExecutionStatus::EN_COURS);
        $audit->setUpdatedAt(new \DateTimeImmutable());
        $this->log($audit, $owner, 'audit_reopened');
        $this->entityManager->flush();
    }

    private function assertOwner(Audit $audit, User $owner): void
    {
        if ($audit->getOwner()?->getId() !== $owner->getId()) {
            throw new \InvalidArgumentException('Audit non autorisé.');
        }
    }

    private function log(Audit $audit, User $owner, string $action): void
    {
        $log = new AuditActivityLog();
        $log->setAudit($audit);
        $log->setActor($owner);
        $log->setAction($action);
        $this->entityManager->persist($log);
    }
}

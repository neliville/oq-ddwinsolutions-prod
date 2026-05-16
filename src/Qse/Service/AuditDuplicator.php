<?php

declare(strict_types=1);

namespace App\Qse\Service;

use App\Entity\Qse\Audit;
use App\Entity\Qse\AuditActivityLog;
use App\Entity\User;
use App\Qse\Enum\AuditExecutionStatus;
use Doctrine\ORM\EntityManagerInterface;

final class AuditDuplicator
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function duplicate(Audit $source, User $owner): Audit
    {
        if ($source->getOwner()?->getId() !== $owner->getId()) {
            throw new \InvalidArgumentException('Audit non autorisé.');
        }

        $clone = new Audit();
        $clone->setOwner($owner);
        $clone->setAuditStandard($source->getAuditStandard());
        $clone->setStatus(AuditExecutionStatus::BROUILLON);
        $clone->setCompanyName($this->suffixCompanyName($source->getCompanyName()));
        $clone->setMainAuditor($source->getMainAuditor());
        $clone->setConcernedSite($source->getConcernedSite());
        $clone->setConcernedProcess($source->getConcernedProcess());
        $clone->setAuditedParties($source->getAuditedParties());
        $clone->setAuditedAt(new \DateTimeImmutable());
        $clone->setObjective($source->getObjective());
        $clone->setScope($source->getScope());
        $clone->setAuditVersion($source->getAuditVersion() ?? '1.0');
        $clone->setGlobalComplianceRate(null);
        $clone->setGlobalScore(null);

        $this->entityManager->persist($clone);
        $this->entityManager->flush();

        $log = new AuditActivityLog();
        $log->setAudit($clone);
        $log->setActor($owner);
        $log->setAction('audit_duplicated');
        $log->setPayload([
            'source_audit_id' => $source->getId(),
        ]);
        $this->entityManager->persist($log);
        $this->entityManager->flush();

        return $clone;
    }

    private function suffixCompanyName(?string $name): ?string
    {
        if ($name === null || trim($name) === '') {
            return 'Copie d’audit';
        }

        return $name . ' (copie)';
    }
}

<?php

declare(strict_types=1);

namespace App\Qse\Service;

use App\Entity\Qse\CAPAAction;
use App\Qse\Enum\CapaStatus;

/**
 * Règle métier : une CAPA n’est clôturée qu’après vérification d’efficacité documentée.
 */
final class CapaWorkflowValidator
{
    public function assertCanClose(CAPAAction $capa): void
    {
        if ($capa->getStatus() !== CapaStatus::EN_ATTENTE_DE_VERIFICATION) {
            throw new \InvalidArgumentException('La CAPA doit être en « attente de vérification d’efficacité » avant clôture.');
        }
        $v = $capa->getEffectivenessVerification();
        if ($v === null || trim($v) === '') {
            throw new \InvalidArgumentException('La vérification d’efficacité est obligatoire pour clôturer.');
        }
    }

    public function markImplementationDone(CAPAAction $capa): void
    {
        if (!\in_array($capa->getStatus(), [CapaStatus::VALIDEE, CapaStatus::EN_COURS, CapaStatus::REOUVERTE], true)) {
            throw new \InvalidArgumentException('Statut incompatible pour marquer la mise en œuvre.');
        }
        $capa->setImplementationDoneAt(new \DateTimeImmutable());
        $capa->setStatus(CapaStatus::EN_ATTENTE_DE_VERIFICATION);
        $capa->setUpdatedAt(new \DateTimeImmutable());
    }

    public function closeAfterVerification(CAPAAction $capa): void
    {
        $this->assertCanClose($capa);
        $capa->setClosedAt(new \DateTimeImmutable());
        $capa->setStatus(CapaStatus::CLOTUREE);
        $capa->setUpdatedAt(new \DateTimeImmutable());
    }
}

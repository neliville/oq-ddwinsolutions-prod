<?php

declare(strict_types=1);

namespace App\Qse\Service;

use App\Entity\Qse\CAPAAction;
use App\Entity\Qse\AuditEvaluation;
use App\Entity\User;
use App\Qse\Enum\AuditVerdict;
use App\Qse\Enum\CapaStatus;
use App\Qse\Enum\CapaType;
use App\Qse\Enum\PdcaPhase;
use App\Repository\Qse\CapaOriginRepository;

/**
 * Brouillon CAPA depuis une évaluation d’audit (écarts : NC, observation, à revoir).
 */
final class AuditEvaluationCapaFactory
{
    public function __construct(
        private readonly CapaOriginRepository $capaOriginRepository,
    ) {
    }

    public function createDraftFromEvaluation(AuditEvaluation $evaluation, User $owner): CAPAAction
    {
        $verdict = AuditEvaluationVerdictHelper::effectiveVerdict($evaluation);
        if ($verdict === null || !$verdict->suggestsCapa()) {
            throw new \InvalidArgumentException('Suggestion CAPA réservée aux écarts (NC, observation, à revoir).');
        }
        $req = $evaluation->getRequirement();
        if ($req === null) {
            throw new \InvalidArgumentException('Exigence manquante.');
        }
        $audit = $evaluation->getAudit();
        if ($audit === null) {
            throw new \InvalidArgumentException('Audit manquant.');
        }

        $priority = match ($verdict) {
            AuditVerdict::MAJOR_NC => 'Haute',
            AuditVerdict::MINOR_NC => 'Haute',
            AuditVerdict::OBSERVATION, AuditVerdict::TO_REVIEW => 'Moyenne',
            default => 'Moyenne',
        };
        $criticality = match ($verdict) {
            AuditVerdict::MAJOR_NC, AuditVerdict::MINOR_NC => 'high',
            default => 'medium',
        };

        $title = sprintf('Action corrective — %s %s', $req->getIsoArticle(), $req->getChapter());
        $descriptionParts = [
            $req->getRequirementText(),
        ];
        if ($evaluation->getAuditComment()) {
            $descriptionParts[] = 'Écart / commentaire audit : ' . $evaluation->getAuditComment();
        }
        $description = implode("\n\n", $descriptionParts);

        $origin = $this->capaOriginRepository->findOneSystemBySlug('audit-interne');
        if ($origin === null) {
            throw new \InvalidArgumentException('Origine « audit interne » introuvable. Exécutez app:qse:seed-capa-origins.');
        }

        $capa = new CAPAAction();
        $capa->setOwner($owner);
        $capa->setTitle($title);
        $capa->setDescription($description);
        $capa->setCapaType(CapaType::CORRECTIVE);
        $capa->setOrigin($origin);
        $capa->setPriority($priority);
        $capa->setCriticality($criticality);
        $capa->setStatus(CapaStatus::BROUILLON);
        $capa->setSourceAuditEvaluation($evaluation);
        $capa->setSourceTool('audit');
        if ($audit->getId() !== null) {
            $capa->setSourceToolEntityId($audit->getId());
        }
        $capa->setPdcaPhase(PdcaPhase::DO);
        $meta = [
            '_schema' => 1,
            'source' => 'audit_evaluation',
            'legacy_requirement_key' => $req->getLegacyKey(),
            'audit_id' => $audit->getId(),
        ];
        $std = $audit->getAuditStandard();
        if ($std !== null) {
            $meta['audit_standard_code'] = $std->getCode();
        }
        $capa->setMetadata($meta);

        return $capa;
    }
}

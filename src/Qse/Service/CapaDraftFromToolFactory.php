<?php

declare(strict_types=1);

namespace App\Qse\Service;

use App\Entity\Qse\CAPAAction;
use App\Entity\Qse\CapaOrigin;
use App\Entity\User;
use App\Qse\Enum\CapaType;
use App\Qse\Enum\CapaStatus;
use App\Qse\Enum\PdcaPhase;
use App\Repository\AmdecAnalysisRepository;
use App\Repository\EightDAnalysisRepository;
use App\Repository\FiveWhyAnalysisRepository;
use App\Repository\IshikawaAnalysisRepository;
use App\Repository\ParetoAnalysisRepository;
use App\Repository\QqoqccpAnalysisRepository;
use App\Repository\Qse\CapaOriginRepository;
use App\Repository\Qse\RiskMatrixEntryRepository;

/**
 * Brouillon CAPA depuis un outil QSE (vérifie la possession de l’entité liée si un id est fourni).
 */
final class CapaDraftFromToolFactory
{
    /** @var array<string, string> outil (param) → slug d’origine système */
    private const TOOL_TO_ORIGIN_SLUG = [
        'ishikawa' => 'ishikawa',
        'five_why' => 'cinq-pourquoi',
        'amdec' => 'amdec',
        'eight_d' => '8d',
        'qqoqccp' => 'qqoqccp',
        'pareto' => 'pareto',
        'risk' => 'matrice-risques',
    ];

    public function __construct(
        private readonly CapaOriginRepository $capaOriginRepository,
        private readonly IshikawaAnalysisRepository $ishikawaAnalysisRepository,
        private readonly FiveWhyAnalysisRepository $fiveWhyAnalysisRepository,
        private readonly AmdecAnalysisRepository $amdecAnalysisRepository,
        private readonly EightDAnalysisRepository $eightDAnalysisRepository,
        private readonly QqoqccpAnalysisRepository $qqoqccpAnalysisRepository,
        private readonly ParetoAnalysisRepository $paretoAnalysisRepository,
        private readonly RiskMatrixEntryRepository $riskMatrixEntryRepository,
    ) {
    }

    public function createDraft(User $owner, string $tool, ?int $entityId, CapaType $capaType, ?CapaOrigin $originOverride = null): CAPAAction
    {
        $tool = strtolower(trim($tool));
        if (!isset(self::TOOL_TO_ORIGIN_SLUG[$tool])) {
            throw new \InvalidArgumentException('Outil inconnu pour la préparation CAPA.');
        }
        $slug = self::TOOL_TO_ORIGIN_SLUG[$tool];
        $origin = $originOverride ?? $this->capaOriginRepository->findOneSystemBySlug($slug);
        if (!$origin instanceof CapaOrigin) {
            throw new \InvalidArgumentException('Origine système introuvable. Exécutez app:qse:seed-capa-origins.');
        }

        if ($entityId !== null) {
            $this->assertOwnership($owner, $tool, $entityId);
        }

        $capa = new CAPAAction();
        $capa->setOwner($owner);
        $capa->setTitle($this->defaultTitle($tool));
        $capa->setDescription(null);
        $capa->setCapaType($capaType);
        $capa->setOrigin($origin);
        $capa->setStatus(CapaStatus::BROUILLON);
        $capa->setPdcaPhase(PdcaPhase::DO);
        $capa->setSourceTool($tool);
        $capa->setSourceToolEntityId($entityId);
        $capa->setMetadata([
            '_schema' => 1,
            'source' => 'tool_prefill',
            'tool' => $tool,
        ]);

        return $capa;
    }

    private function defaultTitle(string $tool): string
    {
        return match ($tool) {
            'ishikawa' => 'Action depuis diagramme Ishikawa',
            'five_why' => 'Action depuis 5 Pourquoi',
            'amdec' => 'Action depuis AMDEC',
            'eight_d' => 'Action depuis 8D',
            'qqoqccp' => 'Action depuis QQOQCCP',
            'pareto' => 'Action depuis Pareto',
            'risk' => 'Action depuis matrice des risques',
            default => 'Nouvelle action CAPA',
        };
    }

    private function assertOwnership(User $owner, string $tool, int $entityId): void
    {
        $ok = match ($tool) {
            'ishikawa' => $this->ishikawaAnalysisRepository->findOneBy(['id' => $entityId, 'user' => $owner]) !== null,
            'five_why' => $this->fiveWhyAnalysisRepository->findOneBy(['id' => $entityId, 'user' => $owner]) !== null,
            'amdec' => $this->amdecAnalysisRepository->findOneBy(['id' => $entityId, 'user' => $owner]) !== null,
            'eight_d' => $this->eightDAnalysisRepository->findOneBy(['id' => $entityId, 'user' => $owner]) !== null,
            'qqoqccp' => $this->qqoqccpAnalysisRepository->findOneBy(['id' => $entityId, 'user' => $owner]) !== null,
            'pareto' => $this->paretoAnalysisRepository->findOneBy(['id' => $entityId, 'user' => $owner]) !== null,
            'risk' => $this->riskMatrixEntryRepository->findOneBy(['id' => $entityId, 'owner' => $owner]) !== null,
            default => false,
        };
        if (!$ok) {
            throw new \InvalidArgumentException('Accès refusé ou ressource introuvable pour cet outil.');
        }
    }
}

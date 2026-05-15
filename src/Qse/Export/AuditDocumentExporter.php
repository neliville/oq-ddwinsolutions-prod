<?php

declare(strict_types=1);

namespace App\Qse\Export;

use App\Entity\Qse\Audit;
use App\Export\Dto\ExportBrandingView;
use App\Qse\Audit\ViewModel\AuditCockpitMetrics;
use App\Qse\Service\AuditEvaluationVerdictHelper;
use App\Repository\Qse\AuditRequirementRepository;
use App\Repository\Qse\CAPAActionRepository;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;

final class AuditDocumentExporter
{
    public function __construct(
        private readonly AuditRequirementRepository $requirementRepository,
        private readonly CAPAActionRepository $capaActionRepository,
    ) {
    }

    /**
     * @param array<int, \App\Entity\Qse\AuditEvaluation> $evaluationsByReqId
     */
    public function buildDocx(
        Audit $audit,
        AuditCockpitMetrics $metrics,
        array $evaluationsByReqId,
        ExportBrandingView $branding,
        string $variant,
    ): PhpWord {
        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Calibri');
        $phpWord->setDefaultFontSize(11);

        $section = $phpWord->addSection();
        $title = match ($variant) {
            'terrain' => 'Rapport d’audit — version terrain',
            'certification' => 'Rapport d’audit — version certification',
            default => 'Rapport d’audit — version direction',
        };
        $section->addText($title, ['bold' => true, 'size' => 22]);
        $section->addTextBreak(1);
        $footer = $branding->profileDisplayName ?? 'OUTILS-QUALITÉ';
        $section->addText('Émis par : ' . $footer, ['italic' => true, 'color' => '666666']);
        $section->addTextBreak(2);

        $section->addText('Synthèse', ['bold' => true, 'size' => 14]);
        $section->addText(sprintf('Entité : %s', $audit->getCompanyName() ?? '—'));
        $section->addText(sprintf('Référentiel : %s', $audit->getAuditStandard()?->getName() ?? '—'));
        $section->addText(sprintf('Date : %s', $audit->getAuditedAt()?->format('d/m/Y') ?? '—'));
        $section->addText(sprintf('Conformité globale : %s', $metrics->globalComplianceRate !== null ? $metrics->globalComplianceRate . ' %' : '—'));
        $section->addText(sprintf('NC majeures : %d · NC mineures : %d · Observations : %d', $metrics->majorNc, $metrics->minorNc, $metrics->observations));
        $section->addTextBreak(2);

        if ($variant !== 'terrain') {
            $section->addText('Détail par exigence', ['bold' => true, 'size' => 14]);
        } else {
            $section->addText('Synthèse terrain (extraits)', ['bold' => true, 'size' => 14]);
        }

        $std = $audit->getAuditStandard();
        if ($std !== null) {
            foreach ($this->requirementRepository->findDistinctChaptersForStandard($std) as $chapter) {
                $section->addText($chapter, ['bold' => true, 'size' => 12]);
                foreach ($this->requirementRepository->findByChapterOrderedForStandard($chapter, $std) as $req) {
                    $ev = $evaluationsByReqId[$req->getId()] ?? null;
                    $v = $ev !== null ? AuditEvaluationVerdictHelper::effectiveVerdict($ev) : null;
                    $line = sprintf('%s — %s : %s', $req->getIsoArticle(), $v?->label() ?? 'Non évalué', mb_substr($req->getRequirementText(), 0, 200));
                    $section->addText($line, ['size' => 10]);
                    if ($variant !== 'terrain' && $ev?->getAuditComment()) {
                        $section->addText('Commentaire : ' . $ev->getAuditComment(), ['italic' => true, 'size' => 10]);
                    }
                    if ($variant === 'certification' && $ev?->getEvidence()) {
                        $section->addText('Preuve : ' . $ev->getEvidence(), ['size' => 10]);
                    }
                }
                $section->addTextBreak(1);
            }
        }

        if ($variant !== 'terrain') {
            $section->addPageBreak();
            $section->addText('Plans d’action liés', ['bold' => true, 'size' => 14]);
            foreach ($this->capaActionRepository->findLinkedToAudit($audit) as $capa) {
                $section->addText($capa->getTitle(), ['bold' => true]);
                $section->addText(sprintf('Statut %s · échéance %s', $capa->getStatus()->value, $capa->getDueAt()?->format('d/m/Y') ?? '—'));
                if ($capa->getDescription()) {
                    $section->addText($capa->getDescription(), ['size' => 10]);
                }
                $section->addTextBreak(1);
            }
        }

        return $phpWord;
    }

    /**
     * @param array<int, \App\Entity\Qse\AuditEvaluation> $evaluationsByReqId
     */
    public function writeDocxToString(
        Audit $audit,
        AuditCockpitMetrics $metrics,
        array $evaluationsByReqId,
        ExportBrandingView $branding,
        string $variant,
    ): string {
        $word = $this->buildDocx($audit, $metrics, $evaluationsByReqId, $branding, $variant);
        $writer = IOFactory::createWriter($word, 'Word2007');
        $path = tempnam(sys_get_temp_dir(), 'oq_audit_');
        if ($path === false) {
            throw new \RuntimeException('Impossible de créer un fichier temporaire pour l’export Word.');
        }
        try {
            $writer->save($path);
            $content = file_get_contents($path);
            if ($content === false) {
                throw new \RuntimeException('Lecture du DOCX temporaire impossible.');
            }

            return $content;
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }
}

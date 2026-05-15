<?php

declare(strict_types=1);

namespace App\Qse\Export;

use App\Entity\Qse\Audit;
use App\Qse\Audit\ViewModel\AuditCockpitMetrics;
use App\Qse\Service\AuditEvaluationVerdictHelper;
use App\Repository\Qse\AuditRequirementRepository;
use App\Repository\Qse\CAPAActionRepository;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

final class AuditSpreadsheetExporter
{
    public function __construct(
        private readonly AuditRequirementRepository $requirementRepository,
        private readonly CAPAActionRepository $capaActionRepository,
    ) {
    }

    public function build(Audit $audit, AuditCockpitMetrics $metrics, array $evaluationsByReqId): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setTitle('Audit QSE')
            ->setSubject('Export audit');

        $this->fillSynthese($spreadsheet->getActiveSheet(), $audit, $metrics);
        $exSheet = $spreadsheet->createSheet();
        $exSheet->setTitle('Exigences');
        $this->fillExigences($exSheet, $audit, $evaluationsByReqId);

        $capaSheet = $spreadsheet->createSheet();
        $capaSheet->setTitle('Plans_action');
        $this->fillCapas($capaSheet, $audit);

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    private function fillSynthese(Worksheet $sheet, Audit $audit, AuditCockpitMetrics $metrics): void
    {
        $sheet->setTitle('Synthèse');
        $rows = [
            ['Rapport d’audit QSE', ''],
            ['Entité auditée', (string) ($audit->getCompanyName() ?? '')],
            ['Référentiel', $audit->getAuditStandard()?->getName() ?? ''],
            ['Date d’audit', $audit->getAuditedAt()?->format('d/m/Y') ?? ''],
            ['Auditeur principal', (string) ($audit->getMainAuditor() ?? '')],
            ['Conformité globale (%)', $metrics->globalComplianceRate !== null ? (string) $metrics->globalComplianceRate : '—'],
            ['Exigences (total)', (string) $metrics->totalRequirements],
            ['Exigences évaluées (hors « non évalué »)', (string) $metrics->answeredRequirements],
            ['NC majeures', (string) $metrics->majorNc],
            ['NC mineures', (string) $metrics->minorNc],
            ['Observations', (string) $metrics->observations],
            ['À revoir', (string) $metrics->toReview],
            ['Conformes', (string) $metrics->conform],
            ['N/A', (string) $metrics->notApplicable],
            ['Non évaluées', (string) $metrics->notEvaluated],
            ['Preuves manquantes (écarts)', (string) $metrics->evidenceMissing],
        ];
        $sheet->fromArray($rows, null, 'A1');
        $sheet->getStyle('A1:B1')->getFont()->setBold(true)->setSize(14);
        $sheet->getColumnDimension('A')->setWidth(42);
        $sheet->getColumnDimension('B')->setWidth(48);
    }

    /**
     * @param array<int, \App\Entity\Qse\AuditEvaluation> $evaluationsByReqId
     */
    private function fillExigences(Worksheet $sheet, Audit $audit, array $evaluationsByReqId): void
    {
        $headers = ['Chapitre', 'Article', 'Exigence', 'Verdict', 'Commentaire audit', 'Observation terrain', 'Criticité', 'Preuves'];
        $sheet->fromArray([$headers], null, 'A1');
        $sheet->getStyle('A1:H1')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E2E8F0'],
            ],
        ]);
        $row = 2;
        $std = $audit->getAuditStandard();
        if ($std === null) {
            return;
        }
        foreach ($this->requirementRepository->findDistinctChaptersForStandard($std) as $chapter) {
            foreach ($this->requirementRepository->findByChapterOrderedForStandard($chapter, $std) as $req) {
                $ev = $evaluationsByReqId[$req->getId()] ?? null;
                $v = $ev !== null ? AuditEvaluationVerdictHelper::effectiveVerdict($ev) : null;
                $verdictLabel = $v?->label() ?? '—';
                $sheet->setCellValue("A{$row}", $chapter);
                $sheet->setCellValue("B{$row}", $req->getIsoArticle());
                $sheet->setCellValue("C{$row}", $req->getRequirementText());
                $sheet->setCellValue("D{$row}", $verdictLabel);
                $sheet->setCellValue("E{$row}", $ev?->getAuditComment() ?? '');
                $sheet->setCellValue("F{$row}", $ev?->getFieldObservation() ?? '');
                $sheet->setCellValue("G{$row}", $ev?->getCriticality() ?? '');
                $sheet->setCellValue("H{$row}", $ev?->getEvidence() ?? '');
                ++$row;
            }
        }
        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $sheet->freezePane('A2');
    }

    private function fillCapas(Worksheet $sheet, Audit $audit): void
    {
        $headers = ['Titre', 'Statut', 'Priorité', 'Responsable', 'Échéance', 'Description'];
        $sheet->fromArray([$headers], null, 'A1');
        $sheet->getStyle('A1:F1')->getFont()->setBold(true);
        $row = 2;
        foreach ($this->capaActionRepository->findLinkedToAudit($audit) as $capa) {
            $sheet->setCellValue("A{$row}", $capa->getTitle());
            $sheet->setCellValue("B{$row}", $capa->getStatus()->value);
            $sheet->setCellValue("C{$row}", (string) ($capa->getPriority() ?? ''));
            $sheet->setCellValue("D{$row}", (string) ($capa->getResponsible() ?? ''));
            $sheet->setCellValue("E{$row}", $capa->getDueAt()?->format('d/m/Y') ?? '');
            $sheet->setCellValue("F{$row}", (string) ($capa->getDescription() ?? ''));
            ++$row;
        }
        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }
}

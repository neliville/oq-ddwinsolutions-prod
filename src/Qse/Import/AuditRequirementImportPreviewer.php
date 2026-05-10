<?php

declare(strict_types=1);

namespace App\Qse\Import;

use App\Entity\Qse\AuditRequirement;
use App\Entity\Qse\AuditStandard;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Compare des lignes brutes (JSON/Excel) à la base sans écrire.
 */
final class AuditRequirementImportPreviewer
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AuditRequirementRowNormalizer $normalizer,
    ) {
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return array{
     *   new: list<array{legacy_key: string, chapter: string, article: string}>,
     *   modified: list<array{legacy_key: string, summary: string}>,
     *   unchanged: int,
     *   errors: list<string>
     * }
     */
    public function preview(AuditStandard $standard, array $rows): array
    {
        $repo = $this->entityManager->getRepository(AuditRequirement::class);
        $new = [];
        $modified = [];
        $unchanged = 0;
        $errors = [];

        foreach ($rows as $i => $raw) {
            if (!\is_array($raw)) {
                $errors[] = sprintf('Ligne %d : objet attendu.', $i + 1);

                continue;
            }
            try {
                $n = $this->normalizer->normalize($raw);
            } catch (\InvalidArgumentException $e) {
                $errors[] = sprintf('Ligne %d : %s', $i + 1, $e->getMessage());

                continue;
            }
            $req = $repo->findOneBy(['auditStandard' => $standard, 'legacyKey' => $n['legacy_key']]);
            if (!$req instanceof AuditRequirement) {
                $new[] = [
                    'legacy_key' => $n['legacy_key'],
                    'chapter' => $n['chapter'],
                    'article' => $n['article'],
                ];

                continue;
            }
            if ($this->isSameAsExisting($req, $n)) {
                ++$unchanged;
            } else {
                $modified[] = [
                    'legacy_key' => $n['legacy_key'],
                    'summary' => $this->diffSummary($req, $n),
                ];
            }
        }

        return ['new' => $new, 'modified' => $modified, 'unchanged' => $unchanged, 'errors' => $errors];
    }

    /**
     * @param array{
     *   legacy_key: string,
     *   chapter: string,
     *   sub_chapter: ?string,
     *   article: string,
     *   requirement_text: string,
     *   iso_comment: ?string,
     *   business_link: ?string,
     *   pdca_phase: ?\App\Qse\Enum\PdcaPhase,
     *   display_order: int
     * } $n
     */
    private function isSameAsExisting(AuditRequirement $req, array $n): bool
    {
        $order = $n['display_order'] > 0 ? $n['display_order'] : $req->getDisplayOrder();

        return $req->getChapter() === $n['chapter']
            && ($req->getSubChapter() ?? '') === ($n['sub_chapter'] ?? '')
            && $req->getIsoArticle() === $n['article']
            && $req->getRequirementText() === $n['requirement_text']
            && ($req->getIsoComment() ?? '') === ($n['iso_comment'] ?? '')
            && ($req->getBusinessLink() ?? '') === ($n['business_link'] ?? '')
            && ($req->getPdcaPhase()?->value ?? '') === ($n['pdca_phase']?->value ?? '')
            && $req->getDisplayOrder() === $order;
    }

    /**
     * @param array<string, mixed> $n
     */
    private function diffSummary(AuditRequirement $req, array $n): string
    {
        $parts = [];
        if ($req->getChapter() !== $n['chapter']) {
            $parts[] = 'chapitre';
        }
        if (($req->getSubChapter() ?? '') !== ($n['sub_chapter'] ?? '')) {
            $parts[] = 'sous-chapitre';
        }
        if ($req->getIsoArticle() !== $n['article']) {
            $parts[] = 'article';
        }
        if ($req->getRequirementText() !== $n['requirement_text']) {
            $parts[] = 'texte';
        }
        if (($req->getIsoComment() ?? '') !== ($n['iso_comment'] ?? '')) {
            $parts[] = 'commentaire';
        }
        if (($req->getBusinessLink() ?? '') !== ($n['business_link'] ?? '')) {
            $parts[] = 'lien';
        }
        if (($req->getPdcaPhase()?->value ?? '') !== ($n['pdca_phase']?->value ?? '')) {
            $parts[] = 'PDCA';
        }
        $order = $n['display_order'] > 0 ? $n['display_order'] : $req->getDisplayOrder();
        if ($req->getDisplayOrder() !== $order) {
            $parts[] = 'ordre';
        }

        return $parts === [] ? 'changement mineur' : implode(', ', $parts);
    }
}

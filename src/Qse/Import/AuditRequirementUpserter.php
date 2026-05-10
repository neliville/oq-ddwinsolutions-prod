<?php

declare(strict_types=1);

namespace App\Qse\Import;

use App\Entity\Qse\AuditRequirement;
use App\Entity\Qse\AuditStandard;
use Doctrine\ORM\EntityManagerInterface;

final class AuditRequirementUpserter
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AuditRequirementRowNormalizer $normalizer,
    ) {
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return array{inserted: int, updated: int}
     */
    public function upsertRows(AuditStandard $standard, array $rows, ?string $sourceVersion = null): array
    {
        $repo = $this->entityManager->getRepository(AuditRequirement::class);
        $inserted = 0;
        $updated = 0;
        $now = new \DateTimeImmutable();
        foreach ($rows as $raw) {
            $n = $this->normalizer->normalize($raw);
            $req = $repo->findOneBy(['auditStandard' => $standard, 'legacyKey' => $n['legacy_key']]);
            if (!$req instanceof AuditRequirement) {
                $req = new AuditRequirement();
                $req->setAuditStandard($standard);
                $req->setLegacyKey($n['legacy_key']);
                $this->entityManager->persist($req);
                ++$inserted;
            } else {
                ++$updated;
            }
            $req->setChapter($n['chapter']);
            $req->setSubChapter($n['sub_chapter']);
            $req->setIsoArticle($n['article']);
            $req->setRequirementText($n['requirement_text']);
            $req->setIsoComment($n['iso_comment']);
            $req->setBusinessLink($n['business_link']);
            $req->setPdcaPhase($n['pdca_phase']);
            if ($n['display_order'] > 0) {
                $req->setDisplayOrder($n['display_order']);
            }
            $req->setActive(true);
            $req->setSourceVersion($sourceVersion);
            $req->setRequirementUpdatedAt($now);
        }
        $this->entityManager->flush();

        return ['inserted' => $inserted, 'updated' => $updated];
    }
}

<?php

declare(strict_types=1);

namespace App\Qse\Service;

use App\Entity\Qse\AuditStandard;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Garantit les 3 référentiels ISO système (owner null) — idempotent.
 */
final class AuditStandardBootstrap
{
    /** @var list<array{name: string, code: string, version: string, description: string, displayOrder: int}> */
    private const STANDARDS = [
        ['name' => 'ISO 9001 — Management de la qualité', 'code' => 'iso_9001', 'version' => '2015', 'description' => 'Référentiel qualité', 'displayOrder' => 1],
        ['name' => 'ISO 14001 — Management environnemental', 'code' => 'iso_14001', 'version' => '2015', 'description' => 'Référentiel environnement', 'displayOrder' => 2],
        ['name' => 'ISO 45001 — Santé et sécurité au travail', 'code' => 'iso_45001', 'version' => '2018', 'description' => 'Référentiel SST', 'displayOrder' => 3],
    ];

    public function ensure(EntityManagerInterface $em): void
    {
        foreach (self::STANDARDS as $row) {
            $existing = $em->createQueryBuilder()
                ->select('s')
                ->from(AuditStandard::class, 's')
                ->where('s.code = :code')
                ->setParameter('code', $row['code'])
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();
            if ($existing instanceof AuditStandard) {
                $existing->setName($row['name']);
                $existing->setVersion($row['version']);
                $existing->setDescription($row['description']);
                $existing->setDisplayOrder($row['displayOrder']);
                $existing->setActive(true);
                $existing->setVisible(true);
                continue;
            }
            $s = new AuditStandard();
            $s->setName($row['name']);
            $s->setCode($row['code']);
            $s->setVersion($row['version']);
            $s->setDescription($row['description']);
            $s->setDisplayOrder($row['displayOrder']);
            $s->setActive(true);
            $s->setVisible(true);
            $s->setOwner(null);
            $em->persist($s);
        }
        $em->flush();
    }
}

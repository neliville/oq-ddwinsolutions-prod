<?php

declare(strict_types=1);

namespace App\Qse\Service;

use App\Entity\Qse\CapaOrigin;
use App\Qse\Enum\CapaOriginKind;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Insère ou met à jour les origines système (slug unique, sans propriétaire).
 */
final class CapaSystemOriginSeeder
{
    /** @var list<array{name: string, slug: string}> */
    private const ROWS = [
        ['name' => 'Ishikawa', 'slug' => 'ishikawa'],
        ['name' => '5 Pourquoi', 'slug' => 'cinq-pourquoi'],
        ['name' => 'AMDEC', 'slug' => 'amdec'],
        ['name' => '8D', 'slug' => '8d'],
        ['name' => 'QQOQCCP', 'slug' => 'qqoqccp'],
        ['name' => 'Pareto', 'slug' => 'pareto'],
        ['name' => 'Audit interne', 'slug' => 'audit-interne'],
        ['name' => 'Matrice des risques', 'slug' => 'matrice-risques'],
        ['name' => 'Onboarding cockpit', 'slug' => 'onboarding-cockpit'],
        ['name' => 'Autre / Saisie libre', 'slug' => 'autre'],
    ];

    public function seed(EntityManagerInterface $em): int
    {
        $inserted = 0;
        foreach (self::ROWS as $row) {
            $existing = $em->getRepository(CapaOrigin::class)->createQueryBuilder('o')
                ->where('o.slug = :slug')
                ->andWhere('o.owner IS NULL')
                ->setParameter('slug', $row['slug'])
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();
            if ($existing instanceof CapaOrigin) {
                if ($existing->getName() !== $row['name'] || $existing->getKind() !== CapaOriginKind::SYSTEM) {
                    $existing->setName($row['name']);
                    $existing->setKind(CapaOriginKind::SYSTEM);
                    $existing->setActive(true);
                }
                continue;
            }
            $o = new CapaOrigin();
            $o->setName($row['name']);
            $o->setSlug($row['slug']);
            $o->setKind(CapaOriginKind::SYSTEM);
            $o->setActive(true);
            $o->setOwner(null);
            $em->persist($o);
            ++$inserted;
        }
        $em->flush();

        return $inserted;
    }
}

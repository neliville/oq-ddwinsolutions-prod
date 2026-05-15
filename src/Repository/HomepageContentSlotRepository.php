<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\HomepageContentSlot;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<HomepageContentSlot>
 */
class HomepageContentSlotRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HomepageContentSlot::class);
    }

    /**
     * @return list<HomepageContentSlot>
     */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('h')
            ->orderBy('h.slotKey', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Slots actifs avec contenu non vide — pour la homepage publique.
     *
     * @return array<string, string> slot_key => content
     */
    public function getActiveNonEmptyContentMap(): array
    {
        $rows = $this->createQueryBuilder('h')
            ->select('h.slotKey', 'h.content')
            ->where('h.active = true')
            ->getQuery()
            ->getArrayResult();

        $map = [];
        foreach ($rows as $row) {
            $key = (string) ($row['slotKey'] ?? '');
            $content = trim((string) ($row['content'] ?? ''));
            if ('' !== $key && '' !== $content) {
                $map[$key] = $content;
            }
        }

        return $map;
    }
}

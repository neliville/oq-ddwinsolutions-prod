<?php

declare(strict_types=1);

namespace App\Download\Infrastructure\Persistence;

use App\Download\Domain\Repository\DownloadRequestRepositoryInterface;
use App\Entity\DownloadRequest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * Adapter Doctrine pour DownloadRequestRepositoryInterface.
 *
 * @extends ServiceEntityRepository<DownloadRequest>
 */
final class DoctrineDownloadRequestRepository extends ServiceEntityRepository implements DownloadRequestRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DownloadRequest::class);
    }

    public function findByUuid(Uuid $uuid): ?DownloadRequest
    {
        return $this->find($uuid);
    }

    public function findOneByToken(string $token): ?DownloadRequest
    {
        return $this->findOneBy(
            ['token' => $token],
            ['createdAt' => 'DESC']
        );
    }

    public function save(DownloadRequest $entity): void
    {
        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();
    }
}

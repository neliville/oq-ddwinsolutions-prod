<?php

declare(strict_types=1);

namespace App\Download\Domain\Repository;

use App\Entity\DownloadRequest;
use Symfony\Component\Uid\Uuid;

/**
 * Port : contrat de persistance pour DownloadRequest.
 */
interface DownloadRequestRepositoryInterface
{
    public function findByUuid(Uuid $uuid): ?DownloadRequest;

    public function findOneByToken(string $token): ?DownloadRequest;

    public function save(DownloadRequest $entity): void;
}

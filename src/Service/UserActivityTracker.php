<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

final class UserActivityTracker
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function recordSuccessfulLogin(User $user): void
    {
        $now = new \DateTimeImmutable();
        $user->setLastLoginAt($now);
        $user->setLastActivityAt($now);
        $this->entityManager->flush();
    }

    public function recordProductActivity(User $user): void
    {
        $user->setLastActivityAt(new \DateTimeImmutable());
        $this->entityManager->flush();
    }
}

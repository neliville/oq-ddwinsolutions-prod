<?php

declare(strict_types=1);

namespace App\Collaboration;

use App\Entity\Qse\Audit;
use App\Entity\Qse\CAPAAction;
use App\Entity\SharedAccess;
use App\Entity\User;
use App\Repository\Qse\AuditRepository;
use App\Repository\Qse\CAPAActionRepository;
use App\Repository\SharedAccessRepository;
use App\Service\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class SharedAccessService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SharedAccessRepository $sharedAccessRepository,
        private readonly AuditRepository $auditRepository,
        private readonly CAPAActionRepository $capaRepository,
        private readonly MailerService $mailerService,
        private readonly UrlGeneratorInterface $urlGenerator,
        #[Autowire('%app.collaboration.share_ttl_days_default%')]
        private readonly int $shareTtlDaysDefault,
    ) {
    }

    public function getDefaultShareTtlDays(): int
    {
        return $this->shareTtlDaysDefault;
    }

    public function assertOwnerHasResource(User $owner, SharedResourceType $type, int $targetId): void
    {
        if ($type === SharedResourceType::AUDIT) {
            $a = $this->auditRepository->findOneOwnedBy($targetId, $owner);
            if (!$a instanceof Audit) {
                throw new \InvalidArgumentException('Audit introuvable ou non autorisé.');
            }

            return;
        }
        $c = $this->capaRepository->findOneBy(['id' => $targetId, 'owner' => $owner]);
        if (!$c instanceof CAPAAction) {
            throw new \InvalidArgumentException('CAPA introuvable ou non autorisée.');
        }
    }

    /**
     * @return array{entity: SharedAccess, plainToken: string, shareUrl: string}
     */
    public function createShare(
        User $owner,
        SharedResourceType $type,
        int $targetId,
        SharedAccessLevel $level,
        int $ttlDays,
        ?string $invitedEmail,
        bool $sendEmail,
    ): array {
        $this->assertOwnerHasResource($owner, $type, $targetId);

        foreach ($this->sharedAccessRepository->findActiveByOwnerAndTarget($owner, $type, $targetId) as $existing) {
            $existing->setStatus(SharedAccessStatus::REVOQUE);
            $existing->setRevokedAt(new \DateTimeImmutable());
        }

        $plain = CollaborationToken::generatePlain();
        $row = new SharedAccess();
        $row->setOwner($owner);
        $row->setTargetType($type);
        $row->setTargetId($targetId);
        $row->setTokenHash(CollaborationToken::hashPlain($plain));
        $row->setAccessLevel($level);
        $row->setInvitedEmail($invitedEmail);
        $row->setExpiresAt(new \DateTimeImmutable('+'.$ttlDays.' days'));
        $row->setStatus(SharedAccessStatus::ACTIF);

        $this->entityManager->persist($row);
        $this->entityManager->flush();

        $shareUrl = $this->urlGenerator->generate(
            'app_share_qse_view',
            ['type' => $type->value, 'token' => $plain],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        if ($sendEmail && $invitedEmail !== null && $invitedEmail !== '') {
            if ($type === SharedResourceType::AUDIT) {
                $audit = $this->auditRepository->findOneOwnedBy($targetId, $owner);
                $this->mailerService->sendSharedAuditAccessEmail($row, $audit, $shareUrl);
            } else {
                $capa = $this->capaRepository->findOneBy(['id' => $targetId, 'owner' => $owner]);
                $this->mailerService->sendSharedCapaAccessEmail($row, $capa, $shareUrl);
            }
        }

        return ['entity' => $row, 'plainToken' => $plain, 'shareUrl' => $shareUrl];
    }

    public function revoke(User $owner, int $sharedAccessId): bool
    {
        $row = $this->sharedAccessRepository->find($sharedAccessId);
        if (!$row instanceof SharedAccess || $row->getOwner()?->getId() !== $owner->getId()) {
            return false;
        }
        if ($row->getStatus() !== SharedAccessStatus::ACTIF) {
            return false;
        }
        $row->setStatus(SharedAccessStatus::REVOQUE);
        $row->setRevokedAt(new \DateTimeImmutable());
        $this->entityManager->flush();

        return true;
    }

    public function resolveGuestAccess(string $plainToken): ?SharedAccess
    {
        $row = $this->sharedAccessRepository->findActiveByPlainToken($plainToken);
        if (!$row instanceof SharedAccess) {
            return null;
        }
        $now = new \DateTimeImmutable();
        if ($row->getExpiresAt() < $now) {
            $row->setStatus(SharedAccessStatus::EXPIRE);
            $this->entityManager->flush();

            return null;
        }

        return $row;
    }
}

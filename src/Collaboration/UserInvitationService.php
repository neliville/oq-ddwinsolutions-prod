<?php

declare(strict_types=1);

namespace App\Collaboration;

use App\Entity\User;
use App\Entity\UserInvitation;
use App\Repository\UserInvitationRepository;
use App\Service\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class UserInvitationService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserInvitationRepository $invitationRepository,
        private readonly MailerService $mailerService,
        private readonly UrlGeneratorInterface $urlGenerator,
        #[Autowire('%app.collaboration.invitation_ttl_days%')]
        private readonly int $invitationTtlDays,
    ) {
    }

    /**
     * @param array<string, mixed>|null $context
     *
     * @return array{invitation: UserInvitation, plainToken: string, acceptUrl: string}
     */
    public function createAndPersist(
        User $owner,
        string $email,
        ?string $firstName,
        CollaboratorInvitationRole $role,
        InvitationType $type,
        ?array $context,
    ): array {
        $plain = CollaborationToken::generatePlain();
        $inv = new UserInvitation();
        $inv->setOwner($owner);
        $inv->setEmail($email);
        $inv->setFirstName($firstName !== null && $firstName !== '' ? $firstName : null);
        $inv->setRole($role);
        $inv->setTokenHash(CollaborationToken::hashPlain($plain));
        $inv->setStatus(InvitationStatus::ENVOYEE);
        $inv->setInvitationType($type);
        $inv->setContext($context);
        $inv->setExpiresAt(new \DateTimeImmutable('+'.$this->invitationTtlDays.' days'));

        $this->entityManager->persist($inv);
        $this->entityManager->flush();

        $acceptUrl = $this->urlGenerator->generate(
            'app_collaboration_invitation_accept',
            ['t' => $plain],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        return ['invitation' => $inv, 'plainToken' => $plain, 'acceptUrl' => $acceptUrl];
    }

    public function sendInvitationEmail(UserInvitation $invitation, string $acceptUrl): void
    {
        $this->mailerService->sendCollaboratorInvitationEmail($invitation, $acceptUrl);
    }

    public function findPendingByPlainToken(string $plainToken): ?UserInvitation
    {
        $inv = $this->invitationRepository->findPendingByPlainToken($plainToken);
        if (!$inv instanceof UserInvitation) {
            return null;
        }
        if ($inv->getExpiresAt() < new \DateTimeImmutable()) {
            $inv->setStatus(InvitationStatus::EXPIREE);
            $this->entityManager->flush();

            return null;
        }

        return $inv;
    }

    public function tryAccept(User $user, string $plainToken): bool
    {
        $inv = $this->findPendingByPlainToken($plainToken);
        if (!$inv instanceof UserInvitation) {
            return false;
        }
        if (mb_strtolower((string) $user->getEmail()) !== $inv->getEmail()) {
            return false;
        }
        $inv->setStatus(InvitationStatus::ACCEPTEE);
        $inv->setAcceptedAt(new \DateTimeImmutable());
        $inv->setAcceptedBy($user);
        $this->entityManager->flush();
        $this->mailerService->sendInvitationAcceptedToOwnerEmail($inv);

        return true;
    }

    public function cancel(User $owner, int $invitationId): bool
    {
        $inv = $this->invitationRepository->find($invitationId);
        if (!$inv instanceof UserInvitation || $inv->getOwner()?->getId() !== $owner->getId()) {
            return false;
        }
        if ($inv->getStatus() !== InvitationStatus::ENVOYEE) {
            return false;
        }
        $inv->setStatus(InvitationStatus::ANNULEE);
        $this->entityManager->flush();

        return true;
    }
}

<?php

declare(strict_types=1);

namespace App\Entity;

use App\Collaboration\CollaboratorInvitationRole;
use App\Collaboration\InvitationStatus;
use App\Collaboration\InvitationType;
use App\Repository\UserInvitationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserInvitationRepository::class)]
#[ORM\Table(name: 'user_invitation')]
#[ORM\Index(name: 'idx_user_invitation_owner_email_status', columns: ['owner_id', 'email', 'status'])]
#[ORM\Index(name: 'idx_user_invitation_expires_at', columns: ['expires_at'])]
class UserInvitation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'owner_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?User $owner = null;

    #[ORM\Column(length: 180)]
    private string $email = '';

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $firstName = null;

    #[ORM\Column(length: 32, enumType: CollaboratorInvitationRole::class)]
    private CollaboratorInvitationRole $role = CollaboratorInvitationRole::LECTEUR;

    #[ORM\Column(length: 64)]
    private string $tokenHash = '';

    #[ORM\Column(length: 32, enumType: InvitationStatus::class)]
    private InvitationStatus $status = InvitationStatus::ENVOYEE;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $expiresAt = null;

    #[ORM\Column(length: 32, enumType: InvitationType::class)]
    private InvitationType $invitationType = InvitationType::GENERALE;

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $context = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $acceptedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'accepted_by_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $acceptedBy = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): static
    {
        $this->owner = $owner;

        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = mb_strtolower(trim($email));

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getRole(): CollaboratorInvitationRole
    {
        return $this->role;
    }

    public function setRole(CollaboratorInvitationRole $role): static
    {
        $this->role = $role;

        return $this;
    }

    public function getTokenHash(): string
    {
        return $this->tokenHash;
    }

    public function setTokenHash(string $tokenHash): static
    {
        $this->tokenHash = $tokenHash;

        return $this;
    }

    public function getStatus(): InvitationStatus
    {
        return $this->status;
    }

    public function setStatus(InvitationStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getExpiresAt(): ?\DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(\DateTimeImmutable $expiresAt): static
    {
        $this->expiresAt = $expiresAt;

        return $this;
    }

    public function getInvitationType(): InvitationType
    {
        return $this->invitationType;
    }

    public function setInvitationType(InvitationType $invitationType): static
    {
        $this->invitationType = $invitationType;

        return $this;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getContext(): ?array
    {
        return $this->context;
    }

    /**
     * @param array<string, mixed>|null $context
     */
    public function setContext(?array $context): static
    {
        $this->context = $context;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getAcceptedAt(): ?\DateTimeImmutable
    {
        return $this->acceptedAt;
    }

    public function setAcceptedAt(?\DateTimeImmutable $acceptedAt): static
    {
        $this->acceptedAt = $acceptedAt;

        return $this;
    }

    public function getAcceptedBy(): ?User
    {
        return $this->acceptedBy;
    }

    public function setAcceptedBy(?User $acceptedBy): static
    {
        $this->acceptedBy = $acceptedBy;

        return $this;
    }
}

<?php

declare(strict_types=1);

namespace App\Entity;

use App\Collaboration\SharedAccessLevel;
use App\Collaboration\SharedAccessStatus;
use App\Collaboration\SharedResourceType;
use App\Repository\SharedAccessRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SharedAccessRepository::class)]
#[ORM\Table(name: 'shared_access')]
#[ORM\UniqueConstraint(name: 'uniq_shared_access_token_hash', fields: ['tokenHash'])]
#[ORM\Index(name: 'idx_shared_access_owner_target', columns: ['owner_id', 'target_type', 'target_id', 'status'])]
class SharedAccess
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'owner_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?User $owner = null;

    #[ORM\Column(length: 16, enumType: SharedResourceType::class)]
    private ?SharedResourceType $targetType = null;

    #[ORM\Column]
    private int $targetId = 0;

    #[ORM\Column(length: 64)]
    private string $tokenHash = '';

    #[ORM\Column(length: 32, enumType: SharedAccessLevel::class)]
    private SharedAccessLevel $accessLevel = SharedAccessLevel::LECTURE_SEULE;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $invitedEmail = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $expiresAt = null;

    #[ORM\Column(length: 16, enumType: SharedAccessStatus::class)]
    private SharedAccessStatus $status = SharedAccessStatus::ACTIF;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $revokedAt = null;

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

    public function getTargetType(): ?SharedResourceType
    {
        return $this->targetType;
    }

    public function setTargetType(SharedResourceType $targetType): static
    {
        $this->targetType = $targetType;

        return $this;
    }

    public function getTargetId(): int
    {
        return $this->targetId;
    }

    public function setTargetId(int $targetId): static
    {
        $this->targetId = $targetId;

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

    public function getAccessLevel(): SharedAccessLevel
    {
        return $this->accessLevel;
    }

    public function setAccessLevel(SharedAccessLevel $accessLevel): static
    {
        $this->accessLevel = $accessLevel;

        return $this;
    }

    public function getInvitedEmail(): ?string
    {
        return $this->invitedEmail;
    }

    public function setInvitedEmail(?string $invitedEmail): static
    {
        $this->invitedEmail = $invitedEmail !== null ? mb_strtolower(trim($invitedEmail)) : null;

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

    public function getStatus(): SharedAccessStatus
    {
        return $this->status;
    }

    public function setStatus(SharedAccessStatus $status): static
    {
        $this->status = $status;

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

    public function getRevokedAt(): ?\DateTimeImmutable
    {
        return $this->revokedAt;
    }

    public function setRevokedAt(?\DateTimeImmutable $revokedAt): static
    {
        $this->revokedAt = $revokedAt;

        return $this;
    }
}

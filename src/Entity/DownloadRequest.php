<?php

declare(strict_types=1);

namespace App\Entity;

use App\Download\Infrastructure\Persistence\DoctrineDownloadRequestRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: DoctrineDownloadRequestRepository::class)]
#[ORM\Index(columns: ['status'], name: 'idx_download_request_status')]
#[ORM\Index(columns: ['token'], name: 'idx_download_request_token')]
#[ORM\HasLifecycleCallbacks]
class DownloadRequest
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_AUTHORIZED = 'authorized';
    public const STATUS_EXPIRED = 'expired';

    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\Column(length: 255)]
    private string $email;

    #[ORM\Column(length: 100)]
    private string $resourceSlug;

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $token = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $expiresAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $accessedAt = null;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private int $accessCount = 0;

    public function __construct(string $email, string $resourceSlug)
    {
        $this->id = Uuid::v4();
        $this->email = $email;
        $this->resourceSlug = $resourceSlug;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getResourceSlug(): string
    {
        return $this->resourceSlug;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(?string $token): static
    {
        $this->token = $token;
        return $this;
    }

    public function getExpiresAt(): ?\DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(?\DateTimeImmutable $expiresAt): static
    {
        $this->expiresAt = $expiresAt;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getAccessedAt(): ?\DateTimeImmutable
    {
        return $this->accessedAt;
    }

    public function recordAccess(): static
    {
        $this->accessedAt = new \DateTimeImmutable();
        $this->accessCount++;
        return $this;
    }

    public function getAccessCount(): int
    {
        return $this->accessCount;
    }

    public function isAuthorized(): bool
    {
        return $this->status === self::STATUS_AUTHORIZED;
    }

    public function isExpired(): bool
    {
        return $this->expiresAt !== null && $this->expiresAt < new \DateTimeImmutable();
    }
}

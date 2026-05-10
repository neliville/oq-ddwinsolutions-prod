<?php

declare(strict_types=1);

namespace App\Entity;

use App\Application\Analytics\TrackingEventType;
use App\Repository\TrackingEventRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TrackingEventRepository::class)]
#[ORM\Table(name: 'tracking_event')]
#[ORM\Index(name: 'idx_tracking_event_type_created', columns: ['event_type', 'created_at'])]
#[ORM\Index(name: 'idx_tracking_event_user_created', columns: ['user_id', 'created_at'])]
#[ORM\Index(name: 'idx_tracking_event_created_at', columns: ['created_at'])]
class TrackingEvent
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $user = null;

    #[ORM\Column(name: 'event_type', length: 64, enumType: TrackingEventType::class)]
    private ?TrackingEventType $eventType = null;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $tool = null;

    #[ORM\Column(length: 128, nullable: true)]
    private ?string $action = null;

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $source = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $context = null;

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(name: 'ip_hash', length: 64, nullable: true)]
    private ?string $ipHash = null;

    #[ORM\Column(name: 'session_key', length: 64, nullable: true)]
    private ?string $sessionKey = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getEventType(): ?TrackingEventType
    {
        return $this->eventType;
    }

    public function setEventType(TrackingEventType $eventType): static
    {
        $this->eventType = $eventType;

        return $this;
    }

    public function getTool(): ?string
    {
        return $this->tool;
    }

    public function setTool(?string $tool): static
    {
        $this->tool = $tool;

        return $this;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function setAction(?string $action): static
    {
        $this->action = $action;

        return $this;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(?string $source): static
    {
        $this->source = $source;

        return $this;
    }

    public function getContext(): ?string
    {
        return $this->context;
    }

    public function setContext(?string $context): static
    {
        $this->context = $context;

        return $this;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    /**
     * @param array<string, mixed>|null $metadata
     */
    public function setMetadata(?array $metadata): static
    {
        $this->metadata = $metadata;

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

    public function getIpHash(): ?string
    {
        return $this->ipHash;
    }

    public function setIpHash(?string $ipHash): static
    {
        $this->ipHash = $ipHash;

        return $this;
    }

    public function getSessionKey(): ?string
    {
        return $this->sessionKey;
    }

    public function setSessionKey(?string $sessionKey): static
    {
        $this->sessionKey = $sessionKey;

        return $this;
    }
}

<?php

namespace App\Entity;

use App\Repository\LeadRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entité Doctrine pour persister les leads
 */
#[ORM\Entity(repositoryClass: LeadRepository::class)]
#[ORM\Index(columns: ['email'], name: 'idx_lead_email')]
#[ORM\Index(columns: ['created_at'], name: 'idx_lead_created_at')]
#[ORM\Index(columns: ['source'], name: 'idx_lead_source')]
#[ORM\Index(columns: ['tool'], name: 'idx_lead_tool')]
#[ORM\Index(columns: ['type'], name: 'idx_lead_type')]
#[ORM\Table(name: '`lead`')]
class Lead
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $source = null; // 'newsletter', 'contact', 'tool', 'demo-request'

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $tool = null; // 'ishikawa', 'fivewhy', 'amdec', etc.

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $utmSource = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $utmMedium = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $utmCampaign = null;

    #[ORM\Column(length: 45, nullable: true)]
    private ?string $ipAddress = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $userAgent = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $sessionId = null;

    #[ORM\Column]
    private bool $gdprConsent = false;

    #[ORM\Column(nullable: true)]
    private ?int $score = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $type = null; // 'B2B' | 'B2C'

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne]
    private ?User $user = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;
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

    public function getTool(): ?string
    {
        return $this->tool;
    }

    public function setTool(?string $tool): static
    {
        $this->tool = $tool;
        return $this;
    }

    public function getUtmSource(): ?string
    {
        return $this->utmSource;
    }

    public function setUtmSource(?string $utmSource): static
    {
        $this->utmSource = $utmSource;
        return $this;
    }

    public function getUtmMedium(): ?string
    {
        return $this->utmMedium;
    }

    public function setUtmMedium(?string $utmMedium): static
    {
        $this->utmMedium = $utmMedium;
        return $this;
    }

    public function getUtmCampaign(): ?string
    {
        return $this->utmCampaign;
    }

    public function setUtmCampaign(?string $utmCampaign): static
    {
        $this->utmCampaign = $utmCampaign;
        return $this;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(?string $ipAddress): static
    {
        $this->ipAddress = $ipAddress;
        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): static
    {
        $this->userAgent = $userAgent;
        return $this;
    }

    public function getSessionId(): ?string
    {
        return $this->sessionId;
    }

    public function setSessionId(?string $sessionId): static
    {
        $this->sessionId = $sessionId;
        return $this;
    }

    public function hasGdprConsent(): bool
    {
        return $this->gdprConsent;
    }

    public function setGdprConsent(bool $gdprConsent): static
    {
        $this->gdprConsent = $gdprConsent;
        return $this;
    }

    public function getScore(): ?int
    {
        return $this->score;
    }

    public function setScore(?int $score): static
    {
        $this->score = $score;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;
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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }
}


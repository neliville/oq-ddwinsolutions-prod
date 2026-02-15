<?php

namespace App\Domain\Lead;

/**
 * Entité métier Lead (Domain)
 * Représente un lead dans le domaine métier
 */
class Lead
{
    public function __construct(
        private ?string $email = null,
        private ?string $name = null,
        private ?string $source = null,
        private ?string $tool = null,
        private ?string $utmSource = null,
        private ?string $utmMedium = null,
        private ?string $utmCampaign = null,
        private ?string $ipAddress = null,
        private ?string $userAgent = null,
        private ?string $sessionId = null,
        private bool $gdprConsent = false,
        private ?int $score = null,
        private ?string $type = null, // 'B2B' | 'B2C'
        private ?\DateTimeImmutable $createdAt = null,
    ) {
        if ($this->createdAt === null) {
            $this->createdAt = new \DateTimeImmutable();
        }
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(?string $source): self
    {
        $this->source = $source;
        return $this;
    }

    public function getTool(): ?string
    {
        return $this->tool;
    }

    public function setTool(?string $tool): self
    {
        $this->tool = $tool;
        return $this;
    }

    public function getUtmSource(): ?string
    {
        return $this->utmSource;
    }

    public function setUtmSource(?string $utmSource): self
    {
        $this->utmSource = $utmSource;
        return $this;
    }

    public function getUtmMedium(): ?string
    {
        return $this->utmMedium;
    }

    public function setUtmMedium(?string $utmMedium): self
    {
        $this->utmMedium = $utmMedium;
        return $this;
    }

    public function getUtmCampaign(): ?string
    {
        return $this->utmCampaign;
    }

    public function setUtmCampaign(?string $utmCampaign): self
    {
        $this->utmCampaign = $utmCampaign;
        return $this;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(?string $ipAddress): self
    {
        $this->ipAddress = $ipAddress;
        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): self
    {
        $this->userAgent = $userAgent;
        return $this;
    }

    public function getSessionId(): ?string
    {
        return $this->sessionId;
    }

    public function setSessionId(?string $sessionId): self
    {
        $this->sessionId = $sessionId;
        return $this;
    }

    public function hasGdprConsent(): bool
    {
        return $this->gdprConsent;
    }

    public function setGdprConsent(bool $gdprConsent): self
    {
        $this->gdprConsent = $gdprConsent;
        return $this;
    }

    public function getScore(): ?int
    {
        return $this->score;
    }

    public function setScore(?int $score): self
    {
        $this->score = $score;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }
}


<?php

namespace App\Entity;

use App\Repository\NewsletterSubscriberRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NewsletterSubscriberRepository::class)]
#[ORM\Index(columns: ['active'], name: 'idx_newsletter_active')]
#[ORM\Index(columns: ['subscribed_at'], name: 'idx_newsletter_subscribed_at')]
class NewsletterSubscriber
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $email = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $subscribedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $unsubscribedAt = null;

    #[ORM\Column(length: 255, unique: true, nullable: true)]
    private ?string $unsubscribeToken = null;

    #[ORM\Column]
    private bool $active = true;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $source = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $unsubscribeReasons = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $unsubscribeComment = null;

    public function __construct()
    {
        $this->subscribedAt = new \DateTimeImmutable();
        $this->unsubscribeToken = bin2hex(random_bytes(32));
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getSubscribedAt(): ?\DateTimeImmutable
    {
        return $this->subscribedAt;
    }

    public function setSubscribedAt(\DateTimeImmutable $subscribedAt): static
    {
        $this->subscribedAt = $subscribedAt;

        return $this;
    }

    public function getUnsubscribedAt(): ?\DateTimeImmutable
    {
        return $this->unsubscribedAt;
    }

    public function setUnsubscribedAt(?\DateTimeImmutable $unsubscribedAt): static
    {
        $this->unsubscribedAt = $unsubscribedAt;

        return $this;
    }

    public function getUnsubscribeToken(): ?string
    {
        return $this->unsubscribeToken;
    }

    public function setUnsubscribeToken(?string $unsubscribeToken): static
    {
        $this->unsubscribeToken = $unsubscribeToken;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;

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

    public function getUnsubscribeReasons(): ?array
    {
        return $this->unsubscribeReasons;
    }

    public function setUnsubscribeReasons(?array $unsubscribeReasons): static
    {
        $this->unsubscribeReasons = $unsubscribeReasons;

        return $this;
    }

    public function getUnsubscribeComment(): ?string
    {
        return $this->unsubscribeComment;
    }

    public function setUnsubscribeComment(?string $unsubscribeComment): static
    {
        $this->unsubscribeComment = $unsubscribeComment;

        return $this;
    }

    public function unsubscribe(?array $reasons = null, ?string $comment = null): static
    {
        $this->active = false;
        $this->unsubscribedAt = new \DateTimeImmutable();
        $this->unsubscribeReasons = $reasons;
        $this->unsubscribeComment = $comment;

        return $this;
    }
}


<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\HomepageTestimonialRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: HomepageTestimonialRepository::class)]
#[ORM\Table(name: 'homepage_testimonial')]
#[ORM\Index(columns: ['is_active', 'display_order'], name: 'idx_homepage_testimonial_active_order')]
#[ORM\HasLifecycleCallbacks]
class HomepageTestimonial
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $fullName = null;

    #[ORM\Column(length: 255)]
    private ?string $jobTitle = null;

    #[ORM\Column(length: 255)]
    private ?string $company = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $quote = null;

    #[ORM\Column]
    private int $rating = 5;

    #[ORM\Column(length: 10)]
    private ?string $initials = null;

    #[ORM\Column(name: 'display_order')]
    private int $displayOrder = 0;

    #[ORM\Column]
    private bool $isActive = true;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFullName(): ?string
    {
        return $this->fullName;
    }

    public function setFullName(string $fullName): static
    {
        $this->fullName = $fullName;

        return $this;
    }

    public function getJobTitle(): ?string
    {
        return $this->jobTitle;
    }

    public function setJobTitle(string $jobTitle): static
    {
        $this->jobTitle = $jobTitle;

        return $this;
    }

    public function getCompany(): ?string
    {
        return $this->company;
    }

    public function setCompany(string $company): static
    {
        $this->company = $company;

        return $this;
    }

    public function getQuote(): ?string
    {
        return $this->quote;
    }

    public function setQuote(string $quote): static
    {
        $this->quote = $quote;

        return $this;
    }

    public function getRating(): int
    {
        return $this->rating;
    }

    public function setRating(int $rating): static
    {
        $this->rating = max(1, min(5, $rating));

        return $this;
    }

    public function getInitials(): ?string
    {
        return $this->initials;
    }

    public function setInitials(string $initials): static
    {
        $this->initials = mb_strtoupper(mb_substr(trim($initials), 0, 10));

        return $this;
    }

    public function getDisplayOrder(): int
    {
        return $this->displayOrder;
    }

    public function setDisplayOrder(int $displayOrder): static
    {
        $this->displayOrder = $displayOrder;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

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

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getRoleLine(): string
    {
        $parts = array_values(array_filter(
            [$this->jobTitle, $this->company],
            static fn (?string $value): bool => $value !== null && trim($value) !== ''
        ));

        return implode(' · ', $parts);
    }

    public function getDisplayInitials(): string
    {
        if ($this->initials !== null && $this->initials !== '') {
            return $this->initials;
        }

        $parts = preg_split('/\s+/', trim((string) $this->fullName)) ?: [];
        $initials = '';
        foreach ($parts as $part) {
            if ($part !== '') {
                $initials .= mb_strtoupper(mb_substr($part, 0, 1));
            }
        }

        return mb_substr($initials, 0, 2) ?: '?';
    }

    public function __toString(): string
    {
        return (string) ($this->fullName ?? 'Témoignage');
    }
}

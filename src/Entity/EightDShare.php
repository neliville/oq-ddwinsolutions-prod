<?php

namespace App\Entity;

use App\Repository\EightDShareRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EightDShareRepository::class)]
#[ORM\UniqueConstraint(columns: ['token'])]
class EightDShare
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 64)]
    private ?string $token = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $expiresAt = null;

    #[ORM\ManyToOne(targetEntity: EightDAnalysis::class, inversedBy: 'shares')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?EightDAnalysis $analysis = null;

    #[ORM\OneToMany(mappedBy: 'share', targetEntity: EightDShareVisit::class, orphanRemoval: true)]
    private Collection $visits;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->visits = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(string $token): self
    {
        $this->token = $token;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getExpiresAt(): ?\DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(\DateTimeImmutable $expiresAt): self
    {
        $this->expiresAt = $expiresAt;

        return $this;
    }

    public function getAnalysis(): ?EightDAnalysis
    {
        return $this->analysis;
    }

    public function setAnalysis(EightDAnalysis $analysis): self
    {
        $this->analysis = $analysis;

        return $this;
    }

    public function isExpired(): bool
    {
        return $this->expiresAt !== null && $this->expiresAt < new \DateTimeImmutable();
    }

    /**
     * @return Collection<int, EightDShareVisit>
     */
    public function getVisits(): Collection
    {
        return $this->visits;
    }

    public function addVisit(EightDShareVisit $visit): self
    {
        if (!$this->visits->contains($visit)) {
            $this->visits->add($visit);
            $visit->setShare($this);
        }

        return $this;
    }

    public function removeVisit(EightDShareVisit $visit): self
    {
        $this->visits->removeElement($visit);

        return $this;
    }
}

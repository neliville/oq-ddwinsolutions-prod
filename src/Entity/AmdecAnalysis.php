<?php

namespace App\Entity;

use App\Repository\AmdecAnalysisRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AmdecAnalysisRepository::class)]
#[ORM\Index(columns: ['user_id'], name: 'idx_amdec_user')]
#[ORM\Index(columns: ['created_at'], name: 'idx_amdec_created_at')]
class AmdecAnalysis
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $subject = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $data = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, name: 'user_id')]
    private ?User $user = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\OneToMany(mappedBy: 'analysis', targetEntity: AmdecShare::class, orphanRemoval: true)]
    private Collection $shares;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->shares = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(?string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    public function getData(): ?string
    {
        return $this->data;
    }

    public function setData(string $data): self
    {
        $this->data = $data;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

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

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return Collection<int, AmdecShare>
     */
    public function getShares(): Collection
    {
        return $this->shares;
    }

    public function addShare(AmdecShare $share): self
    {
        if (!$this->shares->contains($share)) {
            $this->shares->add($share);
            $share->setAnalysis($this);
        }

        return $this;
    }

    public function removeShare(AmdecShare $share): self
    {
        $this->shares->removeElement($share);

        return $this;
    }
}



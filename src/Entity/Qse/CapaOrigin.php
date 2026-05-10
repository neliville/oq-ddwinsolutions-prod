<?php

declare(strict_types=1);

namespace App\Entity\Qse;

use App\Entity\User;
use App\Qse\Enum\CapaOriginKind;
use App\Repository\Qse\CapaOriginRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CapaOriginRepository::class)]
#[ORM\Table(name: 'qse_capa_origin')]
#[ORM\UniqueConstraint(name: 'uniq_qse_capa_origin_slug', columns: ['slug'])]
#[ORM\Index(columns: ['owner_id'], name: 'idx_qse_capa_origin_owner')]
class CapaOrigin
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $name = '';

    #[ORM\Column(length: 120)]
    private string $slug = '';

    #[ORM\Column(length: 16, enumType: CapaOriginKind::class)]
    private CapaOriginKind $kind = CapaOriginKind::SYSTEM;

    #[ORM\Column(options: ['default' => true])]
    private bool $active = true;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?User $owner = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function getKind(): CapaOriginKind
    {
        return $this->kind;
    }

    public function setKind(CapaOriginKind $kind): static
    {
        $this->kind = $kind;

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

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): static
    {
        $this->owner = $owner;

        return $this;
    }
}

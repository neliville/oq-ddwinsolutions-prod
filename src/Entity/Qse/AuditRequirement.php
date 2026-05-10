<?php

declare(strict_types=1);

namespace App\Entity\Qse;

use App\Qse\Enum\PdcaPhase;
use App\Repository\Qse\AuditRequirementRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AuditRequirementRepository::class)]
#[ORM\Table(name: 'qse_audit_requirement')]
#[ORM\UniqueConstraint(name: 'uniq_qse_audit_req_standard_legacy', columns: ['audit_standard_id', 'legacy_key'])]
#[ORM\Index(columns: ['audit_standard_id', 'chapter', 'display_order'], name: 'idx_qse_audit_req_std_chapter')]
class AuditRequirement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: AuditStandard::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?AuditStandard $auditStandard = null;

    #[ORM\Column(length: 120)]
    private string $chapter = '';

    #[ORM\Column(length: 160, nullable: true)]
    private ?string $subChapter = null;

    #[ORM\Column(length: 40)]
    private string $isoArticle = '';

    #[ORM\Column(type: Types::TEXT)]
    private string $requirementText = '';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $isoComment = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $businessLink = null;

    #[ORM\Column(length: 16, nullable: true, enumType: PdcaPhase::class)]
    private ?PdcaPhase $pdcaPhase = null;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $sourceVersion = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $requirementUpdatedAt = null;

    #[ORM\Column]
    private int $displayOrder = 0;

    #[ORM\Column(options: ['default' => true])]
    private bool $active = true;

    /** Identifiant stable par référentiel (ex. exig_9001_42). */
    #[ORM\Column(length: 64)]
    private string $legacyKey = '';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAuditStandard(): ?AuditStandard
    {
        return $this->auditStandard;
    }

    public function setAuditStandard(?AuditStandard $auditStandard): static
    {
        $this->auditStandard = $auditStandard;

        return $this;
    }

    public function getChapter(): string
    {
        return $this->chapter;
    }

    public function setChapter(string $chapter): static
    {
        $this->chapter = $chapter;

        return $this;
    }

    public function getSubChapter(): ?string
    {
        return $this->subChapter;
    }

    public function setSubChapter(?string $subChapter): static
    {
        $this->subChapter = $subChapter;

        return $this;
    }

    public function getIsoArticle(): string
    {
        return $this->isoArticle;
    }

    public function setIsoArticle(string $isoArticle): static
    {
        $this->isoArticle = $isoArticle;

        return $this;
    }

    public function getRequirementText(): string
    {
        return $this->requirementText;
    }

    public function setRequirementText(string $requirementText): static
    {
        $this->requirementText = $requirementText;

        return $this;
    }

    public function getIsoComment(): ?string
    {
        return $this->isoComment;
    }

    public function setIsoComment(?string $isoComment): static
    {
        $this->isoComment = $isoComment;

        return $this;
    }

    public function getBusinessLink(): ?string
    {
        return $this->businessLink;
    }

    public function setBusinessLink(?string $businessLink): static
    {
        $this->businessLink = $businessLink;

        return $this;
    }

    public function getPdcaPhase(): ?PdcaPhase
    {
        return $this->pdcaPhase;
    }

    public function setPdcaPhase(?PdcaPhase $pdcaPhase): static
    {
        $this->pdcaPhase = $pdcaPhase;

        return $this;
    }

    public function getSourceVersion(): ?string
    {
        return $this->sourceVersion;
    }

    public function setSourceVersion(?string $sourceVersion): static
    {
        $this->sourceVersion = $sourceVersion;

        return $this;
    }

    public function getRequirementUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->requirementUpdatedAt;
    }

    public function setRequirementUpdatedAt(?\DateTimeImmutable $requirementUpdatedAt): static
    {
        $this->requirementUpdatedAt = $requirementUpdatedAt;

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
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;

        return $this;
    }

    public function getLegacyKey(): string
    {
        return $this->legacyKey;
    }

    public function setLegacyKey(string $legacyKey): static
    {
        $this->legacyKey = $legacyKey;

        return $this;
    }
}

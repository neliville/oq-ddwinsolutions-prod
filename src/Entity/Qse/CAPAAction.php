<?php

declare(strict_types=1);

namespace App\Entity\Qse;

use App\Entity\User;
use App\Qse\Enum\CapaStatus;
use App\Qse\Enum\CapaType;
use App\Qse\Enum\PdcaPhase;
use App\Repository\Qse\CAPAActionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CAPAActionRepository::class)]
#[ORM\Table(name: 'qse_capa_action')]
#[ORM\Index(columns: ['owner_id', 'status'], name: 'idx_qse_capa_owner_status')]
#[ORM\Index(columns: ['due_at'], name: 'idx_qse_capa_due_at')]
class CAPAAction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $title = '';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'capa_type', length: 32, enumType: CapaType::class)]
    private CapaType $capaType = CapaType::CORRECTIVE;

    #[ORM\ManyToOne(targetEntity: CapaOrigin::class)]
    #[ORM\JoinColumn(name: 'origin_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    private ?CapaOrigin $origin = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $priority = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $criticality = null;

    #[ORM\Column(length: 40, enumType: CapaStatus::class)]
    private CapaStatus $status = CapaStatus::BROUILLON;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $responsible = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $dueAt = null;

    /** Date à laquelle l’action a été réellement menée (≠ clôture). */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $implementationDoneAt = null;

    /** Clôture définitive après vérification d’efficacité. */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $closedAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $closureProof = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $effectivenessVerification = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $effectivenessComment = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $owner = null;

    #[ORM\ManyToOne(targetEntity: AuditEvaluation::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?AuditEvaluation $sourceAuditEvaluation = null;

    #[ORM\ManyToOne(targetEntity: AuditFinding::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?AuditFinding $sourceAuditFinding = null;

    #[ORM\Column(length: 32, nullable: true, enumType: PdcaPhase::class)]
    private ?PdcaPhase $pdcaPhase = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $sourceTool = null;

    #[ORM\Column(nullable: true)]
    private ?int $sourceToolEntityId = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    /** @var Collection<int, RiskMatrixEntry> */
    #[ORM\ManyToMany(targetEntity: RiskMatrixEntry::class, mappedBy: 'linkedCapas')]
    private Collection $riskEntries;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->riskEntries = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getCapaType(): CapaType
    {
        return $this->capaType;
    }

    public function setCapaType(CapaType $capaType): static
    {
        $this->capaType = $capaType;

        return $this;
    }

    public function getOrigin(): ?CapaOrigin
    {
        return $this->origin;
    }

    public function setOrigin(?CapaOrigin $origin): static
    {
        $this->origin = $origin;

        return $this;
    }

    public function getPriority(): ?string
    {
        return $this->priority;
    }

    public function setPriority(?string $priority): static
    {
        $this->priority = $priority;

        return $this;
    }

    public function getCriticality(): ?string
    {
        return $this->criticality;
    }

    public function setCriticality(?string $criticality): static
    {
        $this->criticality = $criticality;

        return $this;
    }

    public function getStatus(): CapaStatus
    {
        return $this->status;
    }

    public function setStatus(CapaStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getResponsible(): ?string
    {
        return $this->responsible;
    }

    public function setResponsible(?string $responsible): static
    {
        $this->responsible = $responsible;

        return $this;
    }

    public function getDueAt(): ?\DateTimeImmutable
    {
        return $this->dueAt;
    }

    public function setDueAt(?\DateTimeImmutable $dueAt): static
    {
        $this->dueAt = $dueAt;

        return $this;
    }

    public function getImplementationDoneAt(): ?\DateTimeImmutable
    {
        return $this->implementationDoneAt;
    }

    public function setImplementationDoneAt(?\DateTimeImmutable $implementationDoneAt): static
    {
        $this->implementationDoneAt = $implementationDoneAt;

        return $this;
    }

    public function getClosedAt(): ?\DateTimeImmutable
    {
        return $this->closedAt;
    }

    public function setClosedAt(?\DateTimeImmutable $closedAt): static
    {
        $this->closedAt = $closedAt;

        return $this;
    }

    public function getClosureProof(): ?string
    {
        return $this->closureProof;
    }

    public function setClosureProof(?string $closureProof): static
    {
        $this->closureProof = $closureProof;

        return $this;
    }

    public function getEffectivenessVerification(): ?string
    {
        return $this->effectivenessVerification;
    }

    public function setEffectivenessVerification(?string $effectivenessVerification): static
    {
        $this->effectivenessVerification = $effectivenessVerification;

        return $this;
    }

    public function getEffectivenessComment(): ?string
    {
        return $this->effectivenessComment;
    }

    public function setEffectivenessComment(?string $effectivenessComment): static
    {
        $this->effectivenessComment = $effectivenessComment;

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

    public function getSourceAuditEvaluation(): ?AuditEvaluation
    {
        return $this->sourceAuditEvaluation;
    }

    public function setSourceAuditEvaluation(?AuditEvaluation $sourceAuditEvaluation): static
    {
        $this->sourceAuditEvaluation = $sourceAuditEvaluation;

        return $this;
    }

    public function getSourceAuditFinding(): ?AuditFinding
    {
        return $this->sourceAuditFinding;
    }

    public function setSourceAuditFinding(?AuditFinding $sourceAuditFinding): static
    {
        $this->sourceAuditFinding = $sourceAuditFinding;

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

    public function getSourceTool(): ?string
    {
        return $this->sourceTool;
    }

    public function setSourceTool(?string $sourceTool): static
    {
        $this->sourceTool = $sourceTool;

        return $this;
    }

    public function getSourceToolEntityId(): ?int
    {
        return $this->sourceToolEntityId;
    }

    public function setSourceToolEntityId(?int $sourceToolEntityId): static
    {
        $this->sourceToolEntityId = $sourceToolEntityId;

        return $this;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function setMetadata(?array $metadata): static
    {
        $this->metadata = $metadata;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
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

    /**
     * @return Collection<int, RiskMatrixEntry>
     */
    public function getRiskEntries(): Collection
    {
        return $this->riskEntries;
    }
}

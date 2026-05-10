<?php

declare(strict_types=1);

namespace App\Entity\Qse;

use App\Entity\User;
use App\Qse\Enum\AuditExecutionStatus;
use App\Repository\Qse\AuditRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AuditRepository::class)]
#[ORM\Table(name: 'qse_audit')]
#[ORM\Index(columns: ['owner_id'], name: 'idx_qse_audit_owner')]
#[ORM\Index(columns: ['audited_at'], name: 'idx_qse_audit_audited_at')]
class Audit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: AuditPlan::class, inversedBy: 'audits')]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?AuditPlan $auditPlan = null;

    #[ORM\ManyToOne(targetEntity: AuditStandard::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private ?AuditStandard $auditStandard = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $mainAuditor = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $concernedSite = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $concernedProcess = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $auditedParties = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $auditedAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $objective = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $scope = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $generalConclusion = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    private ?float $globalComplianceRate = null;

    #[ORM\Column(nullable: true)]
    private ?int $globalScore = null;

    #[ORM\Column(length: 32, enumType: AuditExecutionStatus::class)]
    private AuditExecutionStatus $status = AuditExecutionStatus::BROUILLON;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $owner = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $companyName = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $auditVersion = null;

    /**
     * Schéma réservé extensions / IA : { "version": 1, "hints": [] }
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    /** @var Collection<int, AuditEvaluation> */
    #[ORM\OneToMany(targetEntity: AuditEvaluation::class, mappedBy: 'audit', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $evaluations;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->evaluations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAuditPlan(): ?AuditPlan
    {
        return $this->auditPlan;
    }

    public function setAuditPlan(?AuditPlan $auditPlan): static
    {
        $this->auditPlan = $auditPlan;

        return $this;
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

    public function getConcernedSite(): ?string
    {
        return $this->concernedSite;
    }

    public function setConcernedSite(?string $concernedSite): static
    {
        $this->concernedSite = $concernedSite;

        return $this;
    }

    public function getConcernedProcess(): ?string
    {
        return $this->concernedProcess;
    }

    public function setConcernedProcess(?string $concernedProcess): static
    {
        $this->concernedProcess = $concernedProcess;

        return $this;
    }

    public function getMainAuditor(): ?string
    {
        return $this->mainAuditor;
    }

    public function setMainAuditor(?string $mainAuditor): static
    {
        $this->mainAuditor = $mainAuditor;

        return $this;
    }

    public function getAuditedParties(): ?string
    {
        return $this->auditedParties;
    }

    public function setAuditedParties(?string $auditedParties): static
    {
        $this->auditedParties = $auditedParties;

        return $this;
    }

    public function getAuditedAt(): ?\DateTimeImmutable
    {
        return $this->auditedAt;
    }

    public function setAuditedAt(?\DateTimeImmutable $auditedAt): static
    {
        $this->auditedAt = $auditedAt;

        return $this;
    }

    public function getObjective(): ?string
    {
        return $this->objective;
    }

    public function setObjective(?string $objective): static
    {
        $this->objective = $objective;

        return $this;
    }

    public function getScope(): ?string
    {
        return $this->scope;
    }

    public function setScope(?string $scope): static
    {
        $this->scope = $scope;

        return $this;
    }

    public function getGeneralConclusion(): ?string
    {
        return $this->generalConclusion;
    }

    public function setGeneralConclusion(?string $generalConclusion): static
    {
        $this->generalConclusion = $generalConclusion;

        return $this;
    }

    public function getGlobalComplianceRate(): ?float
    {
        return $this->globalComplianceRate;
    }

    public function setGlobalComplianceRate(?float $globalComplianceRate): static
    {
        $this->globalComplianceRate = $globalComplianceRate;

        return $this;
    }

    public function getGlobalScore(): ?int
    {
        return $this->globalScore;
    }

    public function setGlobalScore(?int $globalScore): static
    {
        $this->globalScore = $globalScore;

        return $this;
    }

    public function getStatus(): AuditExecutionStatus
    {
        return $this->status;
    }

    public function setStatus(AuditExecutionStatus $status): static
    {
        $this->status = $status;

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

    public function getCompanyName(): ?string
    {
        return $this->companyName;
    }

    public function setCompanyName(?string $companyName): static
    {
        $this->companyName = $companyName;

        return $this;
    }

    public function getAuditVersion(): ?string
    {
        return $this->auditVersion;
    }

    public function setAuditVersion(?string $auditVersion): static
    {
        $this->auditVersion = $auditVersion;

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
     * @return Collection<int, AuditEvaluation>
     */
    public function getEvaluations(): Collection
    {
        return $this->evaluations;
    }

    public function addEvaluation(AuditEvaluation $evaluation): static
    {
        if (!$this->evaluations->contains($evaluation)) {
            $this->evaluations->add($evaluation);
            $evaluation->setAudit($this);
        }

        return $this;
    }

    public function removeEvaluation(AuditEvaluation $evaluation): static
    {
        $this->evaluations->removeElement($evaluation);

        return $this;
    }
}

<?php

declare(strict_types=1);

namespace App\Entity\Qse;

use App\Entity\User;
use App\Qse\Enum\AuditPlanStatus;
use App\Qse\Enum\AuditScheduledType;
use App\Repository\Qse\AuditPlanRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AuditPlanRepository::class)]
#[ORM\Table(name: 'qse_audit_plan')]
#[ORM\Index(columns: ['owner_id'], name: 'idx_qse_audit_plan_owner')]
#[ORM\Index(columns: ['planned_at'], name: 'idx_qse_audit_plan_planned_at')]
class AuditPlan
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $title = '';

    #[ORM\Column(length: 32, enumType: AuditScheduledType::class)]
    private AuditScheduledType $auditType = AuditScheduledType::INTERNAL;

    #[ORM\ManyToOne(targetEntity: AuditStandard::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private ?AuditStandard $auditStandard = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $scope = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $concernedProcess = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $concernedSite = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $frequency = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $plannedAt = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $performedAt = null;

    #[ORM\Column(length: 32, enumType: AuditPlanStatus::class)]
    private AuditPlanStatus $status = AuditPlanStatus::BROUILLON;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $owner = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    /** @var Collection<int, Audit> */
    #[ORM\OneToMany(targetEntity: Audit::class, mappedBy: 'auditPlan', cascade: ['persist'], orphanRemoval: false)]
    private Collection $audits;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->audits = new ArrayCollection();
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

    public function getAuditType(): AuditScheduledType
    {
        return $this->auditType;
    }

    public function setAuditType(AuditScheduledType $auditType): static
    {
        $this->auditType = $auditType;

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

    public function getScope(): ?string
    {
        return $this->scope;
    }

    public function setScope(?string $scope): static
    {
        $this->scope = $scope;

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

    public function getConcernedSite(): ?string
    {
        return $this->concernedSite;
    }

    public function setConcernedSite(?string $concernedSite): static
    {
        $this->concernedSite = $concernedSite;

        return $this;
    }

    public function getFrequency(): ?string
    {
        return $this->frequency;
    }

    public function setFrequency(?string $frequency): static
    {
        $this->frequency = $frequency;

        return $this;
    }

    public function getPlannedAt(): ?\DateTimeImmutable
    {
        return $this->plannedAt;
    }

    public function setPlannedAt(?\DateTimeImmutable $plannedAt): static
    {
        $this->plannedAt = $plannedAt;

        return $this;
    }

    public function getPerformedAt(): ?\DateTimeImmutable
    {
        return $this->performedAt;
    }

    public function setPerformedAt(?\DateTimeImmutable $performedAt): static
    {
        $this->performedAt = $performedAt;

        return $this;
    }

    public function getStatus(): AuditPlanStatus
    {
        return $this->status;
    }

    public function setStatus(AuditPlanStatus $status): static
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
     * @return Collection<int, Audit>
     */
    public function getAudits(): Collection
    {
        return $this->audits;
    }

    public function addAudit(Audit $audit): static
    {
        if (!$this->audits->contains($audit)) {
            $this->audits->add($audit);
            $audit->setAuditPlan($this);
        }

        return $this;
    }

    public function removeAudit(Audit $audit): static
    {
        if ($this->audits->removeElement($audit) && $audit->getAuditPlan() === $this) {
            $audit->setAuditPlan(null);
        }

        return $this;
    }
}

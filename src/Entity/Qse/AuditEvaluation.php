<?php

declare(strict_types=1);

namespace App\Entity\Qse;

use App\Entity\User;
use App\Repository\Qse\AuditEvaluationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AuditEvaluationRepository::class)]
#[ORM\Table(name: 'qse_audit_evaluation')]
#[ORM\UniqueConstraint(name: 'uniq_qse_audit_eval_audit_req', columns: ['audit_id', 'requirement_id'])]
#[ORM\Index(columns: ['audit_id'], name: 'idx_qse_audit_eval_audit')]
class AuditEvaluation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Audit::class, inversedBy: 'evaluations')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Audit $audit = null;

    #[ORM\ManyToOne(targetEntity: AuditRequirement::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?AuditRequirement $requirement = null;

    /** Note 0–3 (0 = N/A, 1 = NC, 2 = partiel, 3 = conforme). */
    #[ORM\Column(nullable: true)]
    private ?int $score = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $auditComment = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $evidence = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $fieldObservation = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $criticality = null;

    #[ORM\Column(options: ['default' => true])]
    private bool $mandatory = true;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $owner = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $metadata = null;

    /** @var Collection<int, AuditFinding> */
    #[ORM\OneToMany(targetEntity: AuditFinding::class, mappedBy: 'auditEvaluation', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $findings;

    public function __construct()
    {
        $this->findings = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAudit(): ?Audit
    {
        return $this->audit;
    }

    public function setAudit(?Audit $audit): static
    {
        $this->audit = $audit;

        return $this;
    }

    public function getRequirement(): ?AuditRequirement
    {
        return $this->requirement;
    }

    public function setRequirement(?AuditRequirement $requirement): static
    {
        $this->requirement = $requirement;

        return $this;
    }

    public function getScore(): ?int
    {
        return $this->score;
    }

    public function setScore(?int $score): static
    {
        $this->score = $score;

        return $this;
    }

    public function getAuditComment(): ?string
    {
        return $this->auditComment;
    }

    public function setAuditComment(?string $auditComment): static
    {
        $this->auditComment = $auditComment;

        return $this;
    }

    public function getEvidence(): ?string
    {
        return $this->evidence;
    }

    public function setEvidence(?string $evidence): static
    {
        $this->evidence = $evidence;

        return $this;
    }

    public function getFieldObservation(): ?string
    {
        return $this->fieldObservation;
    }

    public function setFieldObservation(?string $fieldObservation): static
    {
        $this->fieldObservation = $fieldObservation;

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

    public function isMandatory(): bool
    {
        return $this->mandatory;
    }

    public function setMandatory(bool $mandatory): static
    {
        $this->mandatory = $mandatory;

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

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function setMetadata(?array $metadata): static
    {
        $this->metadata = $metadata;

        return $this;
    }

    /**
     * @return Collection<int, AuditFinding>
     */
    public function getFindings(): Collection
    {
        return $this->findings;
    }

    public function addFinding(AuditFinding $finding): static
    {
        if (!$this->findings->contains($finding)) {
            $this->findings->add($finding);
            $finding->setAuditEvaluation($this);
        }

        return $this;
    }

    public function removeFinding(AuditFinding $finding): static
    {
        $this->findings->removeElement($finding);

        return $this;
    }
}

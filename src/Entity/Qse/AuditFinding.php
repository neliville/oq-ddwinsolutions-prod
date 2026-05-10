<?php

declare(strict_types=1);

namespace App\Entity\Qse;

use App\Entity\User;
use App\Qse\Enum\AuditFindingType;
use App\Repository\Qse\AuditFindingRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AuditFindingRepository::class)]
#[ORM\Table(name: 'qse_audit_finding')]
#[ORM\Index(columns: ['audit_evaluation_id'], name: 'idx_qse_audit_finding_eval')]
class AuditFinding
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: AuditEvaluation::class, inversedBy: 'findings')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?AuditEvaluation $auditEvaluation = null;

    #[ORM\Column(length: 32, enumType: AuditFindingType::class)]
    private AuditFindingType $findingType = AuditFindingType::OBSERVATION;

    #[ORM\Column(type: Types::TEXT)]
    private string $description = '';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $probableCause = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $criticality = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $impact = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $owner = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAuditEvaluation(): ?AuditEvaluation
    {
        return $this->auditEvaluation;
    }

    public function setAuditEvaluation(?AuditEvaluation $auditEvaluation): static
    {
        $this->auditEvaluation = $auditEvaluation;

        return $this;
    }

    public function getFindingType(): AuditFindingType
    {
        return $this->findingType;
    }

    public function setFindingType(AuditFindingType $findingType): static
    {
        $this->findingType = $findingType;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getProbableCause(): ?string
    {
        return $this->probableCause;
    }

    public function setProbableCause(?string $probableCause): static
    {
        $this->probableCause = $probableCause;

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

    public function getImpact(): ?string
    {
        return $this->impact;
    }

    public function setImpact(?string $impact): static
    {
        $this->impact = $impact;

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
}

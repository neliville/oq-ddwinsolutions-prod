<?php

declare(strict_types=1);

namespace App\Entity\Qse;

use App\Entity\User;
use App\Qse\Enum\RiskCategory;
use App\Qse\Enum\RiskEntryStatus;
use App\Repository\Qse\RiskMatrixEntryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RiskMatrixEntryRepository::class)]
#[ORM\Table(name: 'qse_risk_matrix_entry')]
#[ORM\Index(columns: ['owner_id'], name: 'idx_qse_risk_owner')]
class RiskMatrixEntry
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $identifiedRisk = '';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $concernedProcess = null;

    #[ORM\Column(length: 32, enumType: RiskCategory::class)]
    private RiskCategory $riskCategory = RiskCategory::QUALITY;

    #[ORM\Column(nullable: true)]
    private ?int $severity = null;

    #[ORM\Column(nullable: true)]
    private ?int $probability = null;

    #[ORM\Column(nullable: true)]
    private ?int $detection = null;

    #[ORM\Column(nullable: true)]
    private ?int $criticalityScore = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $riskLevel = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $existingActions = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $responsible = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $reviewAt = null;

    #[ORM\Column(length: 32, enumType: RiskEntryStatus::class)]
    private RiskEntryStatus $status = RiskEntryStatus::IDENTIFIE;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $owner = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    /** @var Collection<int, CAPAAction> */
    #[ORM\ManyToMany(targetEntity: CAPAAction::class, inversedBy: 'riskEntries')]
    #[ORM\JoinTable(name: 'qse_risk_matrix_entry_capa_action')]
    private Collection $linkedCapas;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->linkedCapas = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdentifiedRisk(): string
    {
        return $this->identifiedRisk;
    }

    public function setIdentifiedRisk(string $identifiedRisk): static
    {
        $this->identifiedRisk = $identifiedRisk;

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

    public function getConcernedProcess(): ?string
    {
        return $this->concernedProcess;
    }

    public function setConcernedProcess(?string $concernedProcess): static
    {
        $this->concernedProcess = $concernedProcess;

        return $this;
    }

    public function getRiskCategory(): RiskCategory
    {
        return $this->riskCategory;
    }

    public function setRiskCategory(RiskCategory $riskCategory): static
    {
        $this->riskCategory = $riskCategory;

        return $this;
    }

    public function getSeverity(): ?int
    {
        return $this->severity;
    }

    public function setSeverity(?int $severity): static
    {
        $this->severity = $severity;

        return $this;
    }

    public function getProbability(): ?int
    {
        return $this->probability;
    }

    public function setProbability(?int $probability): static
    {
        $this->probability = $probability;

        return $this;
    }

    public function getDetection(): ?int
    {
        return $this->detection;
    }

    public function setDetection(?int $detection): static
    {
        $this->detection = $detection;

        return $this;
    }

    public function getCriticalityScore(): ?int
    {
        return $this->criticalityScore;
    }

    public function setCriticalityScore(?int $criticalityScore): static
    {
        $this->criticalityScore = $criticalityScore;

        return $this;
    }

    public function getRiskLevel(): ?string
    {
        return $this->riskLevel;
    }

    public function setRiskLevel(?string $riskLevel): static
    {
        $this->riskLevel = $riskLevel;

        return $this;
    }

    public function getExistingActions(): ?string
    {
        return $this->existingActions;
    }

    public function setExistingActions(?string $existingActions): static
    {
        $this->existingActions = $existingActions;

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

    public function getReviewAt(): ?\DateTimeImmutable
    {
        return $this->reviewAt;
    }

    public function setReviewAt(?\DateTimeImmutable $reviewAt): static
    {
        $this->reviewAt = $reviewAt;

        return $this;
    }

    public function getStatus(): RiskEntryStatus
    {
        return $this->status;
    }

    public function setStatus(RiskEntryStatus $status): static
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
     * @return Collection<int, CAPAAction>
     */
    public function getLinkedCapas(): Collection
    {
        return $this->linkedCapas;
    }

    public function addLinkedCapa(CAPAAction $capa): static
    {
        if (!$this->linkedCapas->contains($capa)) {
            $this->linkedCapas->add($capa);
        }

        return $this;
    }

    public function removeLinkedCapa(CAPAAction $capa): static
    {
        $this->linkedCapas->removeElement($capa);

        return $this;
    }
}

<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserPreferencesRepository;
use App\UserPreferences\AcquisitionSource;
use App\UserPreferences\CompanySize;
use App\UserPreferences\JobFunction;
use App\UserPreferences\MainActivity;
use App\UserPreferences\NotificationFrequency;
use App\UserPreferences\PilotingFocus;
use App\UserPreferences\PrimaryStandard;
use App\UserPreferences\QhsePriority;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserPreferencesRepository::class)]
#[ORM\Table(name: 'user_preferences')]
#[ORM\UniqueConstraint(name: 'uniq_user_preferences_user', fields: ['user'])]
class UserPreferences
{
    private const DASHBOARD_KEYS = ['deadlines', 'capa', 'risks', 'audits', 'pdca', 'anomalies', 'kpi'];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'preferences', targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $firstName = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $lastName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $companyName = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $jobTitle = null;

    #[ORM\Column(length: 32, nullable: true, enumType: JobFunction::class)]
    private ?JobFunction $jobFunction = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $sector = null;

    #[ORM\Column(length: 32, nullable: true, enumType: CompanySize::class)]
    private ?CompanySize $companySize = null;

    #[ORM\Column(length: 32, nullable: true, enumType: MainActivity::class)]
    private ?MainActivity $mainActivity = null;

    #[ORM\Column(length: 32, enumType: PrimaryStandard::class)]
    private PrimaryStandard $primaryStandard = PrimaryStandard::ISO_9001;

    #[ORM\Column(length: 32, enumType: QhsePriority::class)]
    private QhsePriority $qhsePriority = QhsePriority::QUALITY;

    #[ORM\Column(length: 32, enumType: PilotingFocus::class)]
    private PilotingFocus $pilotingFocus = PilotingFocus::PDCA;

    #[ORM\Column(length: 32, nullable: true, enumType: AcquisitionSource::class)]
    private ?AcquisitionSource $acquisitionSource = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $profileOnboardingCompleted = false;

    #[ORM\Column(options: ['default' => true])]
    private bool $notifyOverdueActions = true;

    #[ORM\Column(options: ['default' => true])]
    private bool $notifyAuditsToPrepare = true;

    #[ORM\Column(options: ['default' => true])]
    private bool $notifyCapaVerification = true;

    #[ORM\Column(options: ['default' => true])]
    private bool $notifyCriticalRisks = true;

    #[ORM\Column(options: ['default' => false])]
    private bool $notifyWeeklyDigest = false;

    #[ORM\Column(length: 32, enumType: NotificationFrequency::class)]
    private NotificationFrequency $notificationFrequency = NotificationFrequency::IMMEDIATE;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $exportDisplayName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $exportJobTitle = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $exportCompanyName = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $exportPdfFooter = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $exportLogoFilename = null;

    /**
     * @var array<string, bool>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $dashboardVisibility = null;

    /**
     * État UI collaboration : dismiss / cooldown des suggestions (clés + dates ISO8601).
     *
     * @var array<string, mixed>|null
     */
    #[ORM\Column(name: 'collaboration_ui_state', type: Types::JSON, nullable: true)]
    private ?array $collaborationUiState = null;

    /**
     * Parcours d'activation onboarding (clés versionnées, dates ISO8601).
     *
     * @var array<string, mixed>|null
     */
    #[ORM\Column(name: 'activation_state', type: Types::JSON, nullable: true)]
    private ?array $activationState = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->updatedAt = new \DateTimeImmutable();
        $this->profileOnboardingCompleted = false;
    }

    public function isProfileOnboardingCompleted(): bool
    {
        return $this->profileOnboardingCompleted;
    }

    public function setProfileOnboardingCompleted(bool $profileOnboardingCompleted): static
    {
        $this->profileOnboardingCompleted = $profileOnboardingCompleted;

        return $this;
    }

    public function requiresProfileOnboarding(): bool
    {
        return !$this->profileOnboardingCompleted;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): static
    {
        $this->lastName = $lastName;

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

    public function getJobTitle(): ?string
    {
        return $this->jobTitle;
    }

    public function setJobTitle(?string $jobTitle): static
    {
        $this->jobTitle = $jobTitle;

        return $this;
    }

    public function getJobFunction(): ?JobFunction
    {
        return $this->jobFunction;
    }

    public function setJobFunction(?JobFunction $jobFunction): static
    {
        $this->jobFunction = $jobFunction;
        if ($jobFunction !== null) {
            $this->jobTitle = $jobFunction->label();
        }

        return $this;
    }

    public function getAcquisitionSource(): ?AcquisitionSource
    {
        return $this->acquisitionSource;
    }

    public function setAcquisitionSource(?AcquisitionSource $acquisitionSource): static
    {
        $this->acquisitionSource = $acquisitionSource;

        return $this;
    }

    public function getSector(): ?string
    {
        return $this->sector;
    }

    public function setSector(?string $sector): static
    {
        $this->sector = $sector;

        return $this;
    }

    public function getCompanySize(): ?CompanySize
    {
        return $this->companySize;
    }

    public function setCompanySize(?CompanySize $companySize): static
    {
        $this->companySize = $companySize;

        return $this;
    }

    public function getMainActivity(): ?MainActivity
    {
        return $this->mainActivity;
    }

    public function setMainActivity(?MainActivity $mainActivity): static
    {
        $this->mainActivity = $mainActivity;

        return $this;
    }

    public function getPrimaryStandard(): PrimaryStandard
    {
        return $this->primaryStandard;
    }

    public function setPrimaryStandard(PrimaryStandard $primaryStandard): static
    {
        $this->primaryStandard = $primaryStandard;

        return $this;
    }

    public function getQhsePriority(): QhsePriority
    {
        return $this->qhsePriority;
    }

    public function setQhsePriority(QhsePriority $qhsePriority): static
    {
        $this->qhsePriority = $qhsePriority;

        return $this;
    }

    public function getPilotingFocus(): PilotingFocus
    {
        return $this->pilotingFocus;
    }

    public function setPilotingFocus(PilotingFocus $pilotingFocus): static
    {
        $this->pilotingFocus = $pilotingFocus;

        return $this;
    }

    public function isNotifyOverdueActions(): bool
    {
        return $this->notifyOverdueActions;
    }

    public function setNotifyOverdueActions(bool $notifyOverdueActions): static
    {
        $this->notifyOverdueActions = $notifyOverdueActions;

        return $this;
    }

    public function isNotifyAuditsToPrepare(): bool
    {
        return $this->notifyAuditsToPrepare;
    }

    public function setNotifyAuditsToPrepare(bool $notifyAuditsToPrepare): static
    {
        $this->notifyAuditsToPrepare = $notifyAuditsToPrepare;

        return $this;
    }

    public function isNotifyCapaVerification(): bool
    {
        return $this->notifyCapaVerification;
    }

    public function setNotifyCapaVerification(bool $notifyCapaVerification): static
    {
        $this->notifyCapaVerification = $notifyCapaVerification;

        return $this;
    }

    public function isNotifyCriticalRisks(): bool
    {
        return $this->notifyCriticalRisks;
    }

    public function setNotifyCriticalRisks(bool $notifyCriticalRisks): static
    {
        $this->notifyCriticalRisks = $notifyCriticalRisks;

        return $this;
    }

    public function isNotifyWeeklyDigest(): bool
    {
        return $this->notifyWeeklyDigest;
    }

    public function setNotifyWeeklyDigest(bool $notifyWeeklyDigest): static
    {
        $this->notifyWeeklyDigest = $notifyWeeklyDigest;

        return $this;
    }

    public function getNotificationFrequency(): NotificationFrequency
    {
        return $this->notificationFrequency;
    }

    public function setNotificationFrequency(NotificationFrequency $notificationFrequency): static
    {
        $this->notificationFrequency = $notificationFrequency;

        return $this;
    }

    public function getExportDisplayName(): ?string
    {
        return $this->exportDisplayName;
    }

    public function setExportDisplayName(?string $exportDisplayName): static
    {
        $this->exportDisplayName = $exportDisplayName;

        return $this;
    }

    public function getExportJobTitle(): ?string
    {
        return $this->exportJobTitle;
    }

    public function setExportJobTitle(?string $exportJobTitle): static
    {
        $this->exportJobTitle = $exportJobTitle;

        return $this;
    }

    public function getExportCompanyName(): ?string
    {
        return $this->exportCompanyName;
    }

    public function setExportCompanyName(?string $exportCompanyName): static
    {
        $this->exportCompanyName = $exportCompanyName;

        return $this;
    }

    public function getExportPdfFooter(): ?string
    {
        return $this->exportPdfFooter;
    }

    public function setExportPdfFooter(?string $exportPdfFooter): static
    {
        $this->exportPdfFooter = $exportPdfFooter;

        return $this;
    }

    public function getExportLogoFilename(): ?string
    {
        return $this->exportLogoFilename;
    }

    public function setExportLogoFilename(?string $exportLogoFilename): static
    {
        $this->exportLogoFilename = $exportLogoFilename;

        return $this;
    }

    /**
     * @return array<string, bool>|null
     */
    public function getDashboardVisibility(): ?array
    {
        return $this->dashboardVisibility;
    }

    /**
     * @param array<string, bool>|null $dashboardVisibility
     */
    public function setDashboardVisibility(?array $dashboardVisibility): static
    {
        $this->dashboardVisibility = $dashboardVisibility;

        return $this;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getCollaborationUiState(): ?array
    {
        return $this->collaborationUiState;
    }

    /**
     * @param array<string, mixed>|null $collaborationUiState
     */
    public function setCollaborationUiState(?array $collaborationUiState): static
    {
        $this->collaborationUiState = $collaborationUiState;

        return $this;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getActivationState(): ?array
    {
        return $this->activationState;
    }

    /**
     * @param array<string, mixed>|null $activationState
     */
    public function setActivationState(?array $activationState): static
    {
        $this->activationState = $activationState;

        return $this;
    }

    public function isActivationCompleted(): bool
    {
        return ($this->activationState['status'] ?? null) === 'completed';
    }

    public function hasActivationPendingAction(): bool
    {
        return ($this->activationState['status'] ?? null) === 'action_pending';
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function touchUpdatedAt(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function isDashboardSectionVisible(string $key): bool
    {
        if (!in_array($key, self::DASHBOARD_KEYS, true)) {
            return true;
        }
        $m = $this->dashboardVisibility;
        if (!is_array($m) || $m === []) {
            return true;
        }

        return (bool) ($m[$key] ?? true);
    }

    /**
     * @return list<string>
     */
    public static function dashboardSectionKeys(): array
    {
        return self::DASHBOARD_KEYS;
    }

    public function getProfileDisplayName(): string
    {
        $parts = array_filter([$this->firstName, $this->lastName], static fn (?string $s) => $s !== null && $s !== '');

        if ($parts !== []) {
            return implode(' ', $parts);
        }

        return (string) ($this->user?->getEmail() ?? '');
    }

    public function getQhsePriorityLabel(): string
    {
        return match ($this->qhsePriority) {
            QhsePriority::QUALITY => 'Qualité',
            QhsePriority::SAFETY => 'Sécurité',
            QhsePriority::ENVIRONMENT => 'Environnement',
            QhsePriority::COMPLIANCE => 'Conformité',
        };
    }

    public function getPilotingFocusLabel(): string
    {
        return $this->pilotingFocus->label();
    }
}

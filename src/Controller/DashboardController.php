<?php

namespace App\Controller;

use App\Application\Analytics\TrackingEventRecorder;
use App\Application\Analytics\TrackingEventType;
use App\Collaboration\CollaborationSuggestionEngine;
use App\Collaboration\InvitationStatus;
use App\Entity\User;
use App\Repository\AnalyticsRepository;
use App\Repository\Qse\CockpitMetricsRepository;
use App\Repository\SharedAccessRepository;
use App\Repository\UserInvitationRepository;
use App\Repository\UserPreferencesRepository;
use App\UserPreferences\OnboardingWizardChoices;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/dashboard', name: 'app_dashboard_')]
#[IsGranted('ROLE_USER')]
final class DashboardController extends AbstractController
{
    public function __construct(
        private readonly AnalyticsRepository $analyticsRepository,
        private readonly CockpitMetricsRepository $cockpitMetricsRepository,
        private readonly TrackingEventRecorder $trackingEventRecorder,
        private readonly UserPreferencesRepository $userPreferencesRepository,
        private readonly UserInvitationRepository $userInvitationRepository,
        private readonly SharedAccessRepository $sharedAccessRepository,
        private readonly CollaborationSuggestionEngine $collaborationSuggestionEngine,
    ) {
    }

    #[Route('', name: 'index')]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $this->trackingEventRecorder->record(TrackingEventType::DASHBOARD_OPENED, [], $user);

        $toolData = $this->analyticsRepository->getUserToolCounts($user->getId());
        $managerDashboard = $this->cockpitMetricsRepository->buildManagerDashboard($user);
        $cockpit = $managerDashboard['metrics'];
        $cockpitCapas = $this->cockpitMetricsRepository->findRecentCapasForKanban($user, 8);
        $cockpitAudits = $this->cockpitMetricsRepository->findRecentAudits($user, 4);
        $userPreferences = $this->userPreferencesRepository->getOrCreateForUser($user);
        $showOnboardingWizard = !$userPreferences->isProfileOnboardingCompleted() && !$this->isGranted('ROLE_ADMIN');

        $collaborationSummary = [
            'invitationsPending' => $this->userInvitationRepository->countByOwnerAndStatus($user, InvitationStatus::ENVOYEE),
            'invitationsAccepted' => $this->userInvitationRepository->countByOwnerAndStatus($user, InvitationStatus::ACCEPTEE),
            'activeShares' => $this->sharedAccessRepository->countActiveForOwner($user),
        ];
        $collaborationSuggestion = $this->collaborationSuggestionEngine->evaluate($user, $userPreferences);
        if ($collaborationSuggestion !== null) {
            $this->collaborationSuggestionEngine->markShown($user, $collaborationSuggestion['suggestion_key']);
        }

        return $this->render('dashboard/index.html.twig', array_merge($toolData, [
            'show_onboarding_wizard' => $showOnboardingWizard,
            'onboarding_wizard_steps' => OnboardingWizardChoices::steps(),
            'onboarding_wizard_must_open' => $showOnboardingWizard,
            'cockpit' => $cockpit,
            'managerDashboard' => $managerDashboard,
            'cockpitCapas' => $cockpitCapas,
            'cockpitAudits' => $cockpitAudits,
            'user_preferences' => $userPreferences,
            'dashboard_welcome_name' => $userPreferences->getProfileDisplayName(),
            'qhse_priority_label' => $userPreferences->getQhsePriorityLabel(),
            'piloting_focus_label' => $userPreferences->getPilotingFocusLabel(),
            'collaboration_summary' => $collaborationSummary,
            'collaboration_suggestion' => $collaborationSuggestion,
        ]));
    }
}

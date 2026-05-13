<?php

namespace App\Controller;

use App\Application\Analytics\TrackingEventRecorder;
use App\Application\Analytics\TrackingEventType;
use App\Collaboration\CollaborationSuggestionEngine;
use App\Collaboration\InvitationStatus;
use App\Entity\User;
use App\Entity\UserPreferences;
use App\Repository\AnalyticsRepository;
use App\Repository\Qse\CockpitMetricsRepository;
use App\Repository\SharedAccessRepository;
use App\Repository\UserInvitationRepository;
use App\Repository\UserPreferencesRepository;
use App\Service\Onboarding\OnboardingActivationService;
use App\UserPreferences\OnboardingActivationChoices;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
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
        private readonly OnboardingActivationService $onboardingActivationService,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
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
        $isAdmin = $this->isGranted('ROLE_ADMIN');

        if ($request->isMethod('POST') && $request->request->getString('activation_action') === 'complete_aha') {
            if (!$this->isCsrfTokenValid('activation_onboarding_aha', $request->request->getString('_token'))) {
                throw $this->createAccessDeniedException('Jeton CSRF invalide.');
            }

            if ($this->onboardingActivationService->shouldShowAhaBanner($userPreferences, null, $isAdmin)) {
                $this->onboardingActivationService->markAhaSeen($userPreferences);
                $this->entityManager->flush();
            }

            return $this->redirectToRoute('app_dashboard_index');
        }

        $activationNotice = $request->query->getString('activation');
        if ($activationNotice === '') {
            $activationNotice = null;
        }

        $showActivationAhaBanner = $this->onboardingActivationService->shouldShowAhaBanner(
            $userPreferences,
            $activationNotice,
            $isAdmin,
        );
        $showActivationNudgeBanner = !$showActivationAhaBanner
            && $this->onboardingActivationService->shouldShowNudgeBanner($userPreferences, $isAdmin);
        $activationHighlight = $showActivationAhaBanner
            ? $this->resolveActivationHighlight($activationNotice, $userPreferences)
            : null;
        $recommendedAction = $this->onboardingActivationService->resolveRecommendedAction($userPreferences);
        $showOnboardingWizard = $this->onboardingActivationService->shouldShowModal($userPreferences, $isAdmin);
        $activationState = $userPreferences->getActivationState();
        $initialStep = is_array($activationState) ? (string) ($activationState['current_step'] ?? OnboardingActivationChoices::STEP_CONTEXT) : OnboardingActivationChoices::STEP_CONTEXT;

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
            'onboarding_wizard_steps' => $this->buildActivationWizardSteps(),
            'onboarding_wizard_must_open' => $showOnboardingWizard,
            'onboarding_wizard_context_options' => OnboardingActivationChoices::contextOptions(),
            'onboarding_wizard_goal_options' => OnboardingActivationChoices::goalOptions(),
            'onboarding_wizard_initial_step' => $initialStep,
            'onboarding_wizard_recommended_action_urls' => [
                'start_audit' => $this->generateUrl('app_qse_audit_pick_standard'),
                'create_risk' => $this->generateUrl('app_qse_risk_new'),
                'create_capa_draft' => $this->generateUrl('app_dashboard_index'),
                'open_cockpit' => $this->generateUrl('app_dashboard_index'),
            ],
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
            'show_activation_aha_banner' => $showActivationAhaBanner,
            'show_activation_nudge_banner' => $showActivationNudgeBanner,
            'activation_highlight' => $activationHighlight,
            'activation_nudge_cta_url' => $this->resolveActivationNudgeCtaUrl($recommendedAction),
            'activation_nudge_cta_label' => $this->resolveActivationNudgeCtaLabel($recommendedAction),
        ]));
    }

    private function resolveActivationHighlight(?string $activationNotice, UserPreferences $userPreferences): ?string
    {
        $noticeMap = [
            'audit_created' => 'audit',
            'risk_created' => 'risk',
            'capa_created' => 'capa',
        ];
        if ($activationNotice !== null && isset($noticeMap[$activationNotice])) {
            return $noticeMap[$activationNotice];
        }

        $state = $userPreferences->getActivationState();
        if (!is_array($state)) {
            return null;
        }

        $goal = $state['goal'] ?? null;
        if (!is_string($goal) || $goal === '') {
            return null;
        }

        return match ($goal) {
            'audit', 'compliance', 'certification_prep' => 'audit',
            'risk' => 'risk',
            'capa' => 'capa',
            default => null,
        };
    }

    private function resolveActivationNudgeCtaUrl(?string $recommendedAction): ?string
    {
        return match ($recommendedAction) {
            'start_audit' => $this->generateUrl('app_qse_audit_pick_standard'),
            'create_risk' => $this->generateUrl('app_qse_risk_new'),
            'create_capa_draft' => $this->generateUrl('app_dashboard_index'),
            'open_cockpit' => $this->generateUrl('app_dashboard_index'),
            default => null,
        };
    }

    private function resolveActivationNudgeCtaLabel(?string $recommendedAction): string
    {
        return match ($recommendedAction) {
            'start_audit' => 'Lancer un audit',
            'create_risk' => 'Créer un risque',
            'create_capa_draft' => 'Ouvrir une CAPA brouillon',
            'open_cockpit' => 'Ouvrir le cockpit',
            default => 'Reprendre l’activation',
        };
    }

    /**
     * @return list<array{step: string, title: string, kind: string, description: string}>
     */
    private function buildActivationWizardSteps(): array
    {
        return [
            [
                'step' => OnboardingActivationChoices::STEP_CONTEXT,
                'title' => 'Précisez votre contexte QHSE',
                'kind' => OnboardingActivationChoices::STEP_CONTEXT,
                'description' => 'Ces repères orientent la première action utile à lancer dans votre cockpit.',
            ],
            [
                'step' => OnboardingActivationChoices::STEP_GOAL,
                'title' => 'Choisissez votre priorité de pilotage',
                'kind' => OnboardingActivationChoices::STEP_GOAL,
                'description' => 'Nous recommandons une action concrète pour démarrer sans repartir de zéro.',
            ],
            [
                'step' => OnboardingActivationChoices::STEP_GUIDED_ACTION,
                'title' => 'Lancez votre première action utile',
                'kind' => OnboardingActivationChoices::STEP_GUIDED_ACTION,
                'description' => 'Une action simple suffit pour alimenter votre cockpit et structurer la suite.',
            ],
            [
                'step' => 'aha',
                'title' => 'Votre cockpit prend vie',
                'kind' => 'aha',
                'description' => 'Après la première action, votre tableau de bord devient la scène de preuve de votre pilotage.',
            ],
            [
                'step' => 'return_reason',
                'title' => 'Revenez au tableau de bord',
                'kind' => 'return_reason',
                'description' => 'Vous pourrez reprendre l’activation plus tard depuis le tableau de bord si besoin.',
            ],
        ];
    }
}

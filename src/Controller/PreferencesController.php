<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Entity\UserPreferences;
use App\Form\Preference\UserDashboardPreferencesFormType;
use App\Form\Preference\UserExportPreferencesFormType;
use App\Form\Preference\UserNotificationPreferencesFormType;
use App\Form\Preference\UserProfessionalPreferencesFormType;
use App\Form\Preference\UserQhsePreferencesFormType;
use App\Repository\UserPreferencesRepository;
use App\Service\DashboardPreferencesService;
use App\Service\PasswordResetRequestOutcome;
use App\Service\PasswordResetRequestProcessor;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/preferences', name: 'app_preferences_')]
#[IsGranted('ROLE_USER')]
final class PreferencesController extends AbstractController
{
    public function __construct(
        private readonly UserPreferencesRepository $userPreferencesRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly PasswordResetRequestProcessor $passwordResetRequestProcessor,
        private readonly DashboardPreferencesService $dashboardPreferencesService,
    ) {
    }

    /**
     * Même logique d’envoi que {@see ResetPasswordController::request} pour l’email du compte connecté (dialogue préférences).
     */
    #[Route('/request-password-reset', name: 'request_password_reset', methods: ['POST'])]
    public function requestPasswordReset(Request $request, MailerInterface $mailer): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['ok' => false, 'message' => 'Non authentifié.'], Response::HTTP_UNAUTHORIZED);
        }

        $token = $request->request->getString('_token');
        if (!$this->isCsrfTokenValid('preferences_password_reset', $token)) {
            return new JsonResponse(['ok' => false, 'message' => 'Jeton de sécurité invalide ou expiré. Rechargez la page.'], Response::HTTP_FORBIDDEN);
        }

        $email = (string) $user->getEmail();
        $result = $this->passwordResetRequestProcessor->process($email, $mailer);

        return match ($result->outcome) {
            PasswordResetRequestOutcome::SENT => new JsonResponse([
                'ok' => true,
                'message' => 'Un email contenant un lien sécurisé vient d’être envoyé à votre adresse. Le lien expire dans une heure. Pensez à vérifier vos courriers indésirables.',
            ]),
            PasswordResetRequestOutcome::THROTTLED => new JsonResponse([
                'ok' => false,
                'message' => 'Une demande a déjà été envoyée récemment. Patientez avant de réessayer.',
            ], Response::HTTP_TOO_MANY_REQUESTS),
            PasswordResetRequestOutcome::MAIL_FAILED => new JsonResponse([
                'ok' => false,
                'message' => $result->errorMessage ?? 'L’envoi de l’email a échoué.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR),
            PasswordResetRequestOutcome::USER_NOT_FOUND => new JsonResponse([
                'ok' => false,
                'message' => 'Aucun compte associé à cette adresse.',
            ], Response::HTTP_NOT_FOUND),
        };
    }

    #[Route('', name: 'index', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $prefs = $this->userPreferencesRepository->getOrCreateForUser($user);
        $activeTab = (string) $request->query->get('tab', 'profile');
        $allowedTabs = ['profile', 'qhse', 'notifications', 'exports', 'dashboard', 'security'];
        if (!in_array($activeTab, $allowedTabs, true)) {
            $activeTab = 'profile';
        }

        $professionalForm = $this->createForm(UserProfessionalPreferencesFormType::class, $prefs);
        $qhseForm = $this->createForm(UserQhsePreferencesFormType::class, $prefs);
        $notificationForm = $this->createForm(UserNotificationPreferencesFormType::class, $prefs);
        $exportForm = $this->createForm(UserExportPreferencesFormType::class, $prefs);
        $dashboardForm = $this->createForm(UserDashboardPreferencesFormType::class, null, [
            'preferences' => $prefs,
        ]);

        if ($request->isMethod('POST')) {
            [$redirect, $activeTab] = $this->processPost(
                $request,
                $prefs,
                $professionalForm,
                $qhseForm,
                $notificationForm,
                $exportForm,
                $dashboardForm,
                $activeTab,
            );
            if ($redirect instanceof Response) {
                return $redirect;
            }
        }

        return $this->render('preferences/index.html.twig', [
            'preferences' => $prefs,
            'active_tab' => $activeTab,
            'professional_form' => $professionalForm->createView(),
            'qhse_form' => $qhseForm->createView(),
            'notification_form' => $notificationForm->createView(),
            'export_form' => $exportForm->createView(),
            'dashboard_form' => $dashboardForm->createView(),
            'dashboard_widget_entries' => $this->dashboardPreferencesService->getWidgetEntriesForUi($prefs),
        ]);
    }

    /**
     * @param FormInterface<UserPreferences>|FormInterface<null> $dashboardForm
     *
     * @return array{0: ?Response, 1: string}
     */
    private function processPost(
        Request $request,
        UserPreferences $prefs,
        FormInterface $professionalForm,
        FormInterface $qhseForm,
        FormInterface $notificationForm,
        FormInterface $exportForm,
        FormInterface $dashboardForm,
        string $activeTab,
    ): array {
        $data = $request->request->all();

        if (isset($data['user_professional_preferences'])) {
            $professionalForm->handleRequest($request);
            if ($professionalForm->isSubmitted() && $professionalForm->isValid()) {
                $prefs->touchUpdatedAt();
                $this->entityManager->flush();
                $this->addFlash('success', 'Profil professionnel enregistré.');

                return [$this->redirectToRoute('app_preferences_index', ['tab' => 'profile']), $activeTab];
            }

            return [null, 'profile'];
        }

        if (isset($data['user_qhse_preferences'])) {
            $qhseForm->handleRequest($request);
            if ($qhseForm->isSubmitted() && $qhseForm->isValid()) {
                $prefs->touchUpdatedAt();
                $this->entityManager->flush();
                $this->addFlash('success', 'Préférences QHSE enregistrées.');

                return [$this->redirectToRoute('app_preferences_index', ['tab' => 'qhse']), $activeTab];
            }

            return [null, 'qhse'];
        }

        if (isset($data['user_notification_preferences'])) {
            $notificationForm->handleRequest($request);
            if ($notificationForm->isSubmitted() && $notificationForm->isValid()) {
                $prefs->touchUpdatedAt();
                $this->entityManager->flush();
                $this->addFlash('success', 'Préférences de notification enregistrées.');

                return [$this->redirectToRoute('app_preferences_index', ['tab' => 'notifications']), $activeTab];
            }

            return [null, 'notifications'];
        }

        if (isset($data['user_export_preferences'])) {
            $exportForm->handleRequest($request);
            if ($exportForm->isSubmitted() && $exportForm->isValid()) {
                $prefs->touchUpdatedAt();
                $this->entityManager->flush();
                $this->addFlash('success', 'Paramètres d’export enregistrés.');

                return [$this->redirectToRoute('app_preferences_index', ['tab' => 'exports']), $activeTab];
            }

            return [null, 'exports'];
        }

        if (isset($data['user_dashboard_preferences'])) {
            $dashboardForm->handleRequest($request);
            if ($dashboardForm->isSubmitted() && $dashboardForm->isValid()) {
                $this->dashboardPreferencesService->applyVisibilityFromSubmittedForm($prefs, $dashboardForm);
                $prefs->touchUpdatedAt();
                $this->entityManager->flush();
                $this->addFlash('success', 'Affichage du tableau de bord mis à jour.');

                return [$this->redirectToRoute('app_preferences_index', ['tab' => 'dashboard']), $activeTab];
            }

            return [null, 'dashboard'];
        }

        return [null, $activeTab];
    }
}

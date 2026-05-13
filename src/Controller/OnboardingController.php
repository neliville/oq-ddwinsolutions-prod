<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Entity\UserPreferences;
use App\Service\Onboarding\OnboardingActivationService;
use App\Service\Onboarding\OnboardingProfileWriter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/preferences/onboarding', name: 'app_preferences_onboarding_')]
#[IsGranted('ROLE_USER')]
final class OnboardingController extends AbstractController
{
    public function __construct(
        private readonly OnboardingProfileWriter $onboardingProfileWriter,
        private readonly OnboardingActivationService $onboardingActivationService,
    ) {
    }

    #[Route('/step', name: 'step', methods: ['POST'])]
    public function step(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['ok' => false, 'message' => 'Non authentifié.'], Response::HTTP_UNAUTHORIZED);
        }

        $token = $request->request->getString('_token');
        if (!$this->isCsrfTokenValid('onboarding_profile', $token)) {
            return new JsonResponse(['ok' => false, 'message' => 'Jeton CSRF invalide.'], Response::HTTP_FORBIDDEN);
        }

        $activationStep = trim($request->request->getString('activation_step'));

        try {
            if ($activationStep !== '') {
                $prefs = $this->onboardingProfileWriter->applyActivationStep($user, $activationStep, $request->request->all());
            } else {
                $step = (int) $request->request->get('step', 0);
                $value = (string) $request->request->get('value', '');
                $prefs = $this->onboardingProfileWriter->applyStep($user, $step, $value);
            }
        } catch (BadRequestHttpException $e) {
            return new JsonResponse(['ok' => false, 'message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->buildPreferencesResponse($prefs);
    }

    #[Route('/skip', name: 'skip', methods: ['POST'])]
    public function skip(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['ok' => false, 'message' => 'Non authentifié.'], Response::HTTP_UNAUTHORIZED);
        }

        $token = $request->request->getString('_token');
        if (!$this->isCsrfTokenValid('onboarding_profile', $token)) {
            return new JsonResponse(['ok' => false, 'message' => 'Jeton CSRF invalide.'], Response::HTTP_FORBIDDEN);
        }

        $source = trim($request->request->getString('source'));
        if ($source === '') {
            $source = 'modal';
        }

        try {
            $prefs = $this->onboardingProfileWriter->skip($user, $source);
        } catch (BadRequestHttpException $e) {
            return new JsonResponse(['ok' => false, 'message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->buildPreferencesResponse($prefs);
    }

    private function buildPreferencesResponse(UserPreferences $prefs): JsonResponse
    {
        $state = $prefs->getActivationState();

        return new JsonResponse([
            'ok' => true,
            'completed' => $prefs->isProfileOnboardingCompleted(),
            'current_step' => is_array($state) ? ($state['current_step'] ?? null) : null,
            'recommended_action' => $this->onboardingActivationService->resolveRecommendedAction($prefs),
        ]);
    }
}

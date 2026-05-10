<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
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

        $step = (int) $request->request->get('step', 0);
        $value = (string) $request->request->get('value', '');

        try {
            $prefs = $this->onboardingProfileWriter->applyStep($user, $step, $value);
        } catch (BadRequestHttpException $e) {
            return new JsonResponse(['ok' => false, 'message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse([
            'ok' => true,
            'completed' => $prefs->isProfileOnboardingCompleted(),
        ]);
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

        $prefs = $this->onboardingProfileWriter->skipForLater($user);

        return new JsonResponse([
            'ok' => true,
            'completed' => $prefs->isProfileOnboardingCompleted(),
        ]);
    }
}

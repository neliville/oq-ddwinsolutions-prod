<?php

declare(strict_types=1);

namespace App\Controller\Tool;

use App\Entity\IshikawaAnalysis;
use App\Entity\User;
use App\Repository\UserPreferencesRepository;
use App\Service\Onboarding\OnboardingActivationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class IshikawaOnboardingDraftController extends AbstractController
{
    private const DEFAULT_TITLE = 'Nouvelle analyse des causes';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPreferencesRepository $userPreferencesRepository,
        private readonly OnboardingActivationService $onboardingActivationService,
    ) {
    }

    #[Route('/ishikawa/onboarding-draft', name: 'app_ishikawa_onboarding_draft', methods: ['POST'])]
    public function newDraft(Request $request): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('qse_ishikawa_new_draft', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $analysis = new IshikawaAnalysis();
        $analysis->setUser($user);
        $analysis->setTitle(self::DEFAULT_TITLE);
        $analysis->setData(json_encode(['categories' => []], \JSON_THROW_ON_ERROR));

        $this->entityManager->persist($analysis);

        $preferences = $this->userPreferencesRepository->getOrCreateForUser($user);
        $this->onboardingActivationService->markFirstActionCompleted($preferences);

        $this->entityManager->flush();

        return $this->redirectToRoute('app_dashboard_index', ['activation' => 'ishikawa_created']);
    }
}

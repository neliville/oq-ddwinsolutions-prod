<?php

declare(strict_types=1);

namespace App\Controller\Qse;

use App\Entity\Qse\CAPAAction;
use App\Entity\User;
use App\Qse\Enum\CapaStatus;
use App\Qse\Enum\CapaType;
use App\Qse\Enum\PdcaPhase;
use App\Repository\Qse\CapaOriginRepository;
use App\Repository\UserPreferencesRepository;
use App\Service\Onboarding\OnboardingActivationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class QseCapaDraftController extends AbstractController
{
    public function __construct(
        private readonly CapaOriginRepository $capaOriginRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPreferencesRepository $userPreferencesRepository,
        private readonly OnboardingActivationService $onboardingActivationService,
    ) {
    }

    #[Route('/dashboard/qse/capa/new-draft', name: 'app_qse_capa_new_draft', methods: ['POST'])]
    public function newDraft(Request $request): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('qse_capa_new_draft', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $origin = $this->capaOriginRepository->findOneSystemBySlug('onboarding-cockpit');
        if ($origin === null) {
            throw $this->createNotFoundException('Origine CAPA onboarding introuvable.');
        }

        $capa = new CAPAAction();
        $capa->setOwner($user);
        $capa->setTitle('Nouvelle action corrective');
        $capa->setCapaType(CapaType::CORRECTIVE);
        $capa->setStatus(CapaStatus::BROUILLON);
        $capa->setPdcaPhase(PdcaPhase::DO);
        $capa->setOrigin($origin);
        $capa->setMetadata([
            '_schema' => 1,
            'source' => 'activation_onboarding',
        ]);

        $this->entityManager->persist($capa);

        $preferences = $this->userPreferencesRepository->getOrCreateForUser($user);
        $this->onboardingActivationService->markFirstActionCompleted($preferences);

        $this->entityManager->flush();

        return $this->redirectToRoute('app_dashboard_index', ['activation' => 'capa_created']);
    }
}

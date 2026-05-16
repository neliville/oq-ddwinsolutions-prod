<?php

declare(strict_types=1);

namespace App\Controller\Qse;

use App\Application\Analytics\TrackingEventRecorder;
use App\Application\Analytics\TrackingEventType;
use App\Entity\Qse\CAPAAction;
use App\Entity\Qse\RiskMatrixEntry;
use App\Entity\User;
use App\Form\Qse\RiskMatrixEntryFormType;
use App\Qse\Enum\RiskEntryStatus;
use App\Qse\Service\RiskCapaPolicy;
use App\Qse\Service\RiskCriticalityCalculator;
use App\Repository\Qse\CAPAActionRepository;
use App\Qse\Risk\ViewModel\RiskBoardViewModelFactory;
use App\Repository\Qse\RiskMatrixEntryRepository;
use App\Repository\UserPreferencesRepository;
use App\Service\Onboarding\OnboardingActivationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/dashboard/qse/risque', name: 'app_qse_risk_')]
#[IsGranted('ROLE_USER')]
final class QseRiskController extends AbstractController
{
    public function __construct(
        private readonly RiskMatrixEntryRepository $riskRepository,
        private readonly CAPAActionRepository $capaRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly RiskCapaPolicy $riskCapaPolicy,
        private readonly RiskCriticalityCalculator $riskCriticalityCalculator,
        private readonly TrackingEventRecorder $trackingEventRecorder,
        private readonly UserPreferencesRepository $userPreferencesRepository,
        private readonly OnboardingActivationService $onboardingActivationService,
        private readonly RiskBoardViewModelFactory $riskBoardViewModelFactory,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        $user = $this->getUser();
        if (!\is_object($user)) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('qse/risk/index.html.twig', [
            'shell' => $this->riskBoardViewModelFactory->buildCockpitShell($user),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $user = $this->getUser();
        if (!\is_object($user)) {
            throw $this->createAccessDeniedException();
        }

        $entry = new RiskMatrixEntry();
        $entry->setOwner($user);

        $origin = $this->resolveOnboardingOrigin($request);
        $form = $this->createForm(RiskMatrixEntryFormType::class, $entry, [
            'action' => $request->getUri(),
        ]);
        if ($origin !== '') {
            $form->get('origin')->setData($origin);
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->riskCriticalityCalculator->applyToEntry($entry);

            try {
                if ($entry->getStatus() === RiskEntryStatus::SOUS_SURVEILLANCE) {
                    $this->riskCapaPolicy->assertCanActivateCriticalRisk($entry);
                }
            } catch (\InvalidArgumentException $e) {
                $this->addFlash('danger', $e->getMessage());

                return $this->render('qse/risk/new.html.twig', [
                    'form' => $form,
                    'calculator' => $this->riskCriticalityCalculator,
                ]);
            }

            $this->entityManager->persist($entry);
            $this->entityManager->flush();

            $this->trackingEventRecorder->record(
                TrackingEventType::RISK_CREATED,
                ['risk_id' => $entry->getId()],
                $user,
                null,
                'create',
                'web',
            );

            $action = (string) $form->get('action')->getData();
            $continueAfterSave = $action === 'save_continue';

            if ($this->isOnboardingOrigin($request) && $user instanceof User) {
                $preferences = $this->userPreferencesRepository->getOrCreateForUser($user);
                $this->onboardingActivationService->markFirstActionCompleted($preferences);
                $this->entityManager->flush();

                return $this->redirectToRoute('app_dashboard_index', ['activation' => 'risk_created']);
            }

            if ($continueAfterSave) {
                $this->addFlash('success', 'Risque enregistré. Vous pouvez en créer un autre.');

                return $this->redirectToRoute('app_qse_risk_new');
            }

            return $this->redirectToRoute('app_qse_risk_show', ['id' => $entry->getId()]);
        }

        return $this->render('qse/risk/new.html.twig', [
            'form' => $form,
            'calculator' => $this->riskCriticalityCalculator,
        ]);
    }

    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function show(int $id, Request $request): Response
    {
        $user = $this->getUser();
        if (!\is_object($user)) {
            throw $this->createAccessDeniedException();
        }
        $entry = $this->riskRepository->findOneBy(['id' => $id, 'owner' => $user]);
        if (!$entry instanceof RiskMatrixEntry) {
            throw $this->createNotFoundException();
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('qse_risk_edit', (string) $request->request->get('_token'))) {
                throw $this->createAccessDeniedException();
            }
            $capaId = $request->request->getInt('link_capa_id');
            if ($capaId > 0) {
                $capa = $this->capaRepository->findOneBy(['id' => $capaId, 'owner' => $user]);
                if ($capa instanceof CAPAAction) {
                    $entry->addLinkedCapa($capa);
                    $this->entityManager->flush();
                    $this->addFlash('success', 'CAPA liée au risque.');
                }
            }

            return $this->redirectToRoute('app_qse_risk_show', ['id' => $entry->getId()]);
        }

        $capas = $this->capaRepository->findByOwner($user, 100);

        return $this->render('qse/risk/show.html.twig', [
            'risk' => $entry,
            'capas' => $capas,
            'requiresCapa' => $this->riskCapaPolicy->requiresLinkedCapa($entry),
        ]);
    }

    private function resolveOnboardingOrigin(Request $request): string
    {
        if ($request->query->getString('origin') === 'onboarding') {
            return 'onboarding';
        }

        return $request->request->getString('origin');
    }

    private function isOnboardingOrigin(Request $request): bool
    {
        return $this->resolveOnboardingOrigin($request) === 'onboarding';
    }
}

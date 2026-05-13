<?php

declare(strict_types=1);

namespace App\Controller\Qse;

use App\Application\Analytics\TrackingEventRecorder;
use App\Application\Analytics\TrackingEventType;
use App\Entity\Qse\CAPAAction;
use App\Entity\Qse\RiskMatrixEntry;
use App\Entity\User;
use App\Qse\Enum\RiskCategory;
use App\Qse\Enum\RiskEntryStatus;
use App\Qse\Service\RiskCapaPolicy;
use App\Repository\Qse\CAPAActionRepository;
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
        private readonly TrackingEventRecorder $trackingEventRecorder,
        private readonly UserPreferencesRepository $userPreferencesRepository,
        private readonly OnboardingActivationService $onboardingActivationService,
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
            'risks' => $this->riskRepository->findByOwner($user),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $user = $this->getUser();
        if (!\is_object($user)) {
            throw $this->createAccessDeniedException();
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('qse_risk_new', (string) $request->request->get('_token'))) {
                throw $this->createAccessDeniedException();
            }
            $entry = new RiskMatrixEntry();
            $entry->setOwner($user);
            $entry->setIdentifiedRisk($request->request->getString('identified_risk'));
            $entry->setDescription($request->request->getString('description') ?: null);
            $entry->setConcernedProcess($request->request->getString('concerned_process') ?: null);
            $entry->setRiskCategory(RiskCategory::tryFrom($request->request->getString('risk_category')) ?? RiskCategory::QUALITY);
            $entry->setSeverity($this->nullableInt($request->request->get('severity')));
            $entry->setProbability($this->nullableInt($request->request->get('probability')));
            $entry->setDetection($this->nullableInt($request->request->get('detection')));
            $s = $entry->getSeverity();
            $p = $entry->getProbability();
            $d = $entry->getDetection();
            if ($s !== null && $p !== null && $d !== null) {
                $entry->setCriticalityScore($s * $p * $d);
            }
            $entry->setRiskLevel($request->request->getString('risk_level') ?: null);
            $entry->setExistingActions($request->request->getString('existing_actions') ?: null);
            $entry->setResponsible($request->request->getString('responsible') ?: null);
            $entry->setStatus(RiskEntryStatus::tryFrom($request->request->getString('status')) ?? RiskEntryStatus::IDENTIFIE);
            try {
                if ($entry->getStatus() === RiskEntryStatus::SOUS_SURVEILLANCE) {
                    $this->riskCapaPolicy->assertCanActivateCriticalRisk($entry);
                }
            } catch (\InvalidArgumentException $e) {
                $this->addFlash('danger', $e->getMessage());

                return $this->render('qse/risk/new.html.twig', ['last' => $request->request->all()]);
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

            if ($this->isOnboardingOrigin($request) && $user instanceof User) {
                $preferences = $this->userPreferencesRepository->getOrCreateForUser($user);
                $this->onboardingActivationService->markFirstActionCompleted($preferences);
                $this->entityManager->flush();

                return $this->redirectToRoute('app_dashboard_index', ['activation' => 'risk_created']);
            }

            return $this->redirectToRoute('app_qse_risk_show', ['id' => $entry->getId()]);
        }

        return $this->render('qse/risk/new.html.twig', ['last' => []]);
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

    private function nullableInt(mixed $v): ?int
    {
        if ($v === null || $v === '') {
            return null;
        }

        return (int) $v;
    }

    private function isOnboardingOrigin(Request $request): bool
    {
        if ($request->query->getString('origin') === 'onboarding') {
            return true;
        }

        return $request->request->getString('origin') === 'onboarding';
    }
}

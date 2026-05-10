<?php

declare(strict_types=1);

namespace App\Controller\Qse;

use App\Entity\Qse\CAPAAction;
use App\Entity\Qse\CapaOrigin;
use App\Entity\User;
use App\Qse\Enum\CapaStatus;
use App\Qse\Service\CapaWorkflowValidator;
use App\Repository\Qse\CAPAActionRepository;
use App\Repository\Qse\CapaOriginRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/dashboard/qse/capa', name: 'app_qse_capa_')]
#[IsGranted('ROLE_USER')]
final class QseCapaController extends AbstractController
{
    public function __construct(
        private readonly CAPAActionRepository $capaRepository,
        private readonly CapaOriginRepository $capaOriginRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly CapaWorkflowValidator $capaWorkflowValidator,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        $user = $this->getUser();
        if (!\is_object($user)) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('qse/capa/index.html.twig', [
            'capas' => $this->capaRepository->findByOwner($user),
        ]);
    }

    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function show(int $id, Request $request): Response
    {
        $user = $this->getUser();
        if (!\is_object($user)) {
            throw $this->createAccessDeniedException();
        }
        $capa = $this->capaRepository->findOneBy(['id' => $id, 'owner' => $user]);
        if (!$capa instanceof CAPAAction) {
            throw $this->createNotFoundException();
        }

        $origins = $this->capaOriginRepository->findSelectableOriginsForUser($user);

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('qse_capa_edit', (string) $request->request->get('_token'))) {
                throw $this->createAccessDeniedException('Jeton CSRF invalide.');
            }
            $capa->setTitle($request->request->getString('title') ?: $capa->getTitle());
            $capa->setDescription($request->request->getString('description') ?: null);
            $capa->setResponsible($request->request->getString('responsible') ?: null);
            $due = $request->request->getString('due_at');
            $capa->setDueAt($due !== '' ? new \DateTimeImmutable($due) : null);
            $capa->setClosureProof($request->request->getString('closure_proof') ?: null);
            $capa->setUpdatedAt(new \DateTimeImmutable());

            $originId = $request->request->getInt('origin_id');
            if ($originId > 0) {
                $chosen = $this->resolveAllowedOrigin($user, $origins, $originId);
                if ($chosen instanceof CapaOrigin) {
                    $capa->setOrigin($chosen);
                }
            }

            $action = $request->request->get('workflow_action');
            $action = \is_string($action) ? $action : '';
            try {
                if ($action !== '') {
                    if ($action === 'submit_validation') {
                        if ($capa->getStatus() !== CapaStatus::BROUILLON) {
                            throw new \InvalidArgumentException('Soumission réservée au statut « brouillon ».');
                        }
                        $capa->setStatus(CapaStatus::A_VALIDER);
                    } elseif ($action === 'validate') {
                        if (!\in_array($capa->getStatus(), [CapaStatus::BROUILLON, CapaStatus::A_VALIDER, CapaStatus::REOUVERTE], true)) {
                            throw new \InvalidArgumentException('Validation impossible depuis ce statut.');
                        }
                        $capa->setStatus(CapaStatus::VALIDEE);
                    } elseif ($action === 'start') {
                        if ($capa->getStatus() !== CapaStatus::VALIDEE) {
                            throw new \InvalidArgumentException('Le démarrage nécessite le statut « validée ».');
                        }
                        $capa->setStatus(CapaStatus::EN_COURS);
                    } elseif ($action === 'implementation_done') {
                        $this->capaWorkflowValidator->markImplementationDone($capa);
                    } elseif ($action === 'close') {
                        $capa->setEffectivenessVerification($request->request->getString('effectiveness_verification'));
                        $capa->setEffectivenessComment($request->request->getString('effectiveness_comment') ?: null);
                        $this->capaWorkflowValidator->closeAfterVerification($capa);
                    } elseif ($action === 'reopen') {
                        if ($capa->getStatus() !== CapaStatus::CLOTUREE) {
                            throw new \InvalidArgumentException('Rouverture réservée aux CAPA clôturées.');
                        }
                        $capa->setStatus(CapaStatus::REOUVERTE);
                        $capa->setClosedAt(null);
                    } elseif ($action === 'cancel') {
                        if (!\in_array($capa->getStatus(), [CapaStatus::BROUILLON, CapaStatus::A_VALIDER, CapaStatus::VALIDEE, CapaStatus::EN_COURS], true)) {
                            throw new \InvalidArgumentException('Annulation impossible depuis ce statut.');
                        }
                        $capa->setStatus(CapaStatus::ANNULEE);
                    }
                }
                $this->entityManager->flush();
                $this->addFlash('success', 'CAPA mise à jour.');
            } catch (\InvalidArgumentException $e) {
                $this->addFlash('danger', $e->getMessage());
            }

            return $this->redirectToRoute('app_qse_capa_show', ['id' => $capa->getId()]);
        }

        return $this->render('qse/capa/show.html.twig', [
            'capa' => $capa,
            'origins' => $origins,
        ]);
    }

    /**
     * @param list<CapaOrigin> $allowed
     */
    private function resolveAllowedOrigin(User $user, array $allowed, int $originId): ?CapaOrigin
    {
        foreach ($allowed as $o) {
            if ($o->getId() === $originId) {
                return $o;
            }
        }

        return null;
    }
}

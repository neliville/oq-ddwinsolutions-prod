<?php

declare(strict_types=1);

namespace App\Controller\Qse;

use App\Application\Analytics\TrackingEventRecorder;
use App\Application\Analytics\TrackingEventType;
use App\Entity\User;
use App\Qse\Enum\CapaType;
use App\Qse\Service\CapaDraftFromToolFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/dashboard/qse/capa')]
#[IsGranted('ROLE_USER')]
final class QseCapaPrefillController extends AbstractController
{
    public function __construct(
        private readonly CapaDraftFromToolFactory $capaDraftFromToolFactory,
        private readonly EntityManagerInterface $entityManager,
        private readonly TrackingEventRecorder $trackingEventRecorder,
    ) {
    }

    #[Route('/prefill/{tool}/{kind}', name: 'app_qse_capa_prefill', requirements: ['tool' => '[a-z0-9_]+', 'kind' => 'corrective|preventive|maitrise'], methods: ['GET'])]
    public function prefill(Request $request, string $tool, string $kind): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }
        $entityParam = $request->query->get('entity');
        $entityId = null;
        if ($entityParam !== null && $entityParam !== '') {
            $entityId = (int) $entityParam;
            if ($entityId <= 0) {
                $entityId = null;
            }
        }

        $type = match ($kind) {
            'preventive' => CapaType::PREVENTIVE,
            'maitrise' => CapaType::MAITRISE,
            default => CapaType::CORRECTIVE,
        };

        try {
            $capa = $this->capaDraftFromToolFactory->createDraft($user, $tool, $entityId, $type);
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('danger', $e->getMessage());

            return $this->redirectToRoute('app_qse_capa_index');
        }

        $this->entityManager->persist($capa);
        $this->entityManager->flush();

        $this->trackingEventRecorder->record(
            TrackingEventType::CAPA_CREATED,
            ['capa_id' => $capa->getId(), 'prefill_tool' => $tool, 'kind' => $kind],
            $user,
            $tool,
            'prefill',
            'web',
        );

        $this->addFlash('success', 'Brouillon CAPA créé. Complétez la fiche puis le workflow jusqu’à la clôture.');

        return $this->redirectToRoute('app_qse_capa_show', ['id' => $capa->getId()]);
    }
}

<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Qse\AuditRequirement;
use App\Entity\Qse\AuditStandard;
use App\Qse\Enum\PdcaPhase;
use App\Repository\Qse\AuditRequirementRepository;
use App\Repository\Qse\AuditStandardRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/qse/audit-requirements', name: 'app_admin_qse_audit_requirements_')]
#[IsGranted('ROLE_ADMIN')]
final class AdminQseAuditRequirementController extends AbstractController
{
    public function __construct(
        private readonly AuditStandardRepository $auditStandardRepository,
        private readonly AuditRequirementRepository $requirementRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $standardId = $request->query->getInt('standard');
        $standard = $standardId > 0 ? $this->auditStandardRepository->find($standardId) : null;
        $requirements = [];
        if ($standard instanceof AuditStandard) {
            $requirements = $this->requirementRepository->findAdminListingForStandard($standard);
        }

        return $this->render('admin/qse/audit_requirements/index.html.twig', [
            'standards' => $this->auditStandardRepository->findBy([], ['displayOrder' => 'ASC']),
            'selectedStandard' => $standard,
            'requirements' => $requirements,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id): Response
    {
        $req = $this->requirementRepository->find($id);
        if (!$req instanceof AuditRequirement) {
            throw $this->createNotFoundException();
        }
        $std = $req->getAuditStandard();
        if (!$std instanceof AuditStandard) {
            throw $this->createNotFoundException();
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('admin_qse_audit_requirement_edit', (string) $request->request->get('_token'))) {
                throw $this->createAccessDeniedException('Jeton CSRF invalide.');
            }
            $req->setChapter($request->request->getString('chapter'));
            $req->setSubChapter($request->request->getString('subChapter') ?: null);
            $req->setIsoArticle($request->request->getString('isoArticle'));
            $req->setRequirementText($request->request->getString('requirementText'));
            $req->setIsoComment($request->request->getString('isoComment') ?: null);
            $req->setBusinessLink($request->request->getString('businessLink') ?: null);
            $pdca = $request->request->getString('pdcaPhase');
            $req->setPdcaPhase($pdca !== '' ? PdcaPhase::tryFrom(strtolower($pdca)) : null);
            $req->setDisplayOrder($request->request->getInt('displayOrder'));
            $req->setActive($request->request->getBoolean('active'));
            $this->entityManager->flush();
            $this->addFlash('success', 'Exigence enregistrée.');

            return $this->redirectToRoute('app_admin_qse_audit_requirements_index', [
                'standard' => $std->getId(),
            ]);
        }

        return $this->render('admin/qse/audit_requirements/edit.html.twig', [
            'requirement' => $req,
            'standard' => $std,
            'pdcaPhases' => PdcaPhase::cases(),
        ]);
    }

    #[Route('/{id}/reorder', name: 'reorder', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function reorder(Request $request, int $id): Response
    {
        if (!$this->isCsrfTokenValid('admin_qse_audit_requirement_reorder', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }
        $req = $this->requirementRepository->find($id);
        if (!$req instanceof AuditRequirement) {
            throw $this->createNotFoundException();
        }
        $std = $req->getAuditStandard();
        if (!$std instanceof AuditStandard) {
            throw $this->createNotFoundException();
        }
        $direction = $request->request->getString('direction');
        $list = $this->requirementRepository->findAdminListingForStandard($std, 10000);
        $ids = array_map(static fn (AuditRequirement $r): int => (int) $r->getId(), $list);
        $idx = array_search($req->getId(), $ids, true);
        if ($idx === false) {
            return $this->redirectToRoute('app_admin_qse_audit_requirements_index', ['standard' => $std->getId()]);
        }
        $idx = (int) $idx;
        if ($direction === 'up' && $idx > 0) {
            $other = $list[$idx - 1];
            $this->swapDisplayOrder($req, $other);
        } elseif ($direction === 'down' && $idx < \count($list) - 1) {
            $other = $list[$idx + 1];
            $this->swapDisplayOrder($req, $other);
        }
        $this->entityManager->flush();

        return $this->redirectToRoute('app_admin_qse_audit_requirements_index', ['standard' => $std->getId()]);
    }

    private function swapDisplayOrder(AuditRequirement $a, AuditRequirement $b): void
    {
        $oa = $a->getDisplayOrder();
        $ob = $b->getDisplayOrder();
        $a->setDisplayOrder($ob);
        $b->setDisplayOrder($oa);
    }
}

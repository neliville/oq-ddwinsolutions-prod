<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Qse\AuditStandard;
use App\Repository\Qse\AuditStandardRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/qse/audit-standards', name: 'app_admin_qse_audit_standards_')]
#[IsGranted('ROLE_ADMIN')]
final class AdminQseAuditStandardController extends AbstractController
{
    public function __construct(
        private readonly AuditStandardRepository $auditStandardRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        $items = $this->auditStandardRepository->findBy([], ['displayOrder' => 'ASC', 'id' => 'ASC']);

        return $this->render('admin/qse/audit_standards/index.html.twig', [
            'standards' => $items,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id): Response
    {
        $standard = $this->auditStandardRepository->find($id);
        if (!$standard instanceof AuditStandard) {
            throw $this->createNotFoundException();
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('admin_qse_audit_standard_edit', (string) $request->request->get('_token'))) {
                throw $this->createAccessDeniedException('Jeton CSRF invalide.');
            }
            $standard->setName($request->request->getString('name'));
            $standard->setVersion($request->request->getString('version') ?: null);
            $standard->setDescription($request->request->getString('description') ?: null);
            $standard->setActive($request->request->getBoolean('active'));
            $standard->setVisible($request->request->getBoolean('visible'));
            $standard->setDisplayOrder($request->request->getInt('displayOrder'));
            $this->entityManager->flush();
            $this->addFlash('success', 'Référentiel enregistré.');

            return $this->redirectToRoute('app_admin_qse_audit_standards_index');
        }

        return $this->render('admin/qse/audit_standards/edit.html.twig', [
            'standard' => $standard,
        ]);
    }
}

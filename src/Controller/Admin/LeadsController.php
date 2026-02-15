<?php

namespace App\Controller\Admin;

use App\Repository\LeadRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/leads', name: 'app_admin_leads_')]
#[IsGranted('ROLE_ADMIN')]
final class LeadsController extends AbstractController
{
    public function __construct(
        private readonly LeadRepository $leadRepository,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        $leads = $this->leadRepository->findRecent(100);
        $total = $this->leadRepository->count([]);
        $bySource = [
            'tool' => $this->leadRepository->countBySource('tool'),
            'newsletter' => $this->leadRepository->countBySource('newsletter'),
            'contact' => $this->leadRepository->countBySource('contact'),
        ];
        $byTool = [
            'ishikawa' => $this->leadRepository->countByTool('ishikawa'),
            'fivewhy' => $this->leadRepository->countByTool('fivewhy'),
        ];

        return $this->render('admin/leads/index.html.twig', [
            'leads' => $leads,
            'total' => $total,
            'bySource' => $bySource,
            'byTool' => $byTool,
        ]);
    }
}

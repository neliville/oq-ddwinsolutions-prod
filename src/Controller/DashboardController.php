<?php

namespace App\Controller;

use App\Repository\RecordRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/dashboard', name: 'app_dashboard_')]
#[IsGranted('ROLE_USER')]
final class DashboardController extends AbstractController
{
    public function __construct(
        private readonly RecordRepository $recordRepository
    ) {
    }

    #[Route('', name: 'index')]
    public function index(): Response
    {
        $user = $this->getUser();
        
        // Récupérer les statistiques de l'utilisateur
        $records = $this->recordRepository->findBy(
            ['user' => $user],
            ['createdAt' => 'DESC']
        );
        
        $ishikawaRecords = array_filter($records, fn($r) => $r->getType() === 'ishikawa');
        $fiveWhyRecords = array_filter($records, fn($r) => $r->getType() === 'fivewhy');
        $totalRecords = count($records);
        
        // Dernières créations (limitées à 5)
        $recentRecords = array_slice($records, 0, 5);

        return $this->render('dashboard/index.html.twig', [
            'totalRecords' => $totalRecords,
            'ishikawaRecords' => count($ishikawaRecords),
            'fiveWhyRecords' => count($fiveWhyRecords),
            'recentRecords' => $recentRecords,
        ]);
    }
}

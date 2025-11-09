<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\IshikawaAnalysisRepository;
use App\Repository\FiveWhyAnalysisRepository;
use App\Repository\QqoqccpAnalysisRepository;
use App\Repository\AmdecAnalysisRepository;
use App\Repository\ParetoAnalysisRepository;
use App\Repository\EightDAnalysisRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/dashboard', name: 'app_dashboard_')]
#[IsGranted('ROLE_USER')]
final class DashboardController extends AbstractController
{
    public function __construct(
        private readonly IshikawaAnalysisRepository $ishikawaRepository,
        private readonly FiveWhyAnalysisRepository $fiveWhyRepository,
        private readonly QqoqccpAnalysisRepository $qqoqccpRepository,
        private readonly AmdecAnalysisRepository $amdecRepository,
        private readonly ParetoAnalysisRepository $paretoRepository,
        private readonly EightDAnalysisRepository $eightDRepository,
    ) {
    }

    #[Route('', name: 'index')]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Utiliser les repositories spécifiques
        $ishikawaCount = $this->ishikawaRepository->countByUser($user->getId());
        $fiveWhyCount = $this->fiveWhyRepository->countByUser($user->getId());
        $qqoqccpCount = $this->qqoqccpRepository->countByUser($user->getId());
        $amdecCount = $this->amdecRepository->countByUser($user->getId());
        $paretoCount = $this->paretoRepository->countByUser($user->getId());
        $eightDCount = $this->eightDRepository->countByUser($user->getId());
        $totalRecords = $ishikawaCount + $fiveWhyCount + $qqoqccpCount + $amdecCount + $paretoCount + $eightDCount;
        
        // Dernières créations (limitées à 5 pour chaque type, puis fusionnées et triées)
        $ishikawaRecords = $this->ishikawaRepository->findByUser($user->getId());
        $fiveWhyRecords = $this->fiveWhyRepository->findByUser($user->getId());
        $qqoqccpRecords = $this->qqoqccpRepository->findByUser($user->getId());
        $amdecRecords = $this->amdecRepository->findByUser($user->getId());
        $paretoRecords = $this->paretoRepository->findByUser($user->getId());
        $eightDRecords = $this->eightDRepository->findByUser($user->getId());

        // Fusionner et trier par date
        $recentRecords = array_merge($ishikawaRecords, $fiveWhyRecords, $qqoqccpRecords, $amdecRecords, $paretoRecords, $eightDRecords);
        usort($recentRecords, function($a, $b) {
            return $b->getCreatedAt() <=> $a->getCreatedAt();
        });
        $recentRecords = array_slice($recentRecords, 0, 5);
        
        // Enrichir les records avec un champ type pour faciliter la vérification dans Twig
        $enrichedRecords = array_map(function($record) {
            $type = match (true) {
                $record instanceof \App\Entity\IshikawaAnalysis => 'ishikawa',
                $record instanceof \App\Entity\FiveWhyAnalysis => 'fivewhy',
                $record instanceof \App\Entity\QqoqccpAnalysis => 'qqoqccp',
                $record instanceof \App\Entity\AmdecAnalysis => 'amdec',
                $record instanceof \App\Entity\ParetoAnalysis => 'pareto',
                $record instanceof \App\Entity\EightDAnalysis => 'eightd',
                default => 'unknown',
            };

            return [
                'record' => $record,
                'type' => $type,
            ];
        }, $recentRecords);

        return $this->render('dashboard/index.html.twig', [
            'totalRecords' => $totalRecords,
            'ishikawaRecords' => $ishikawaCount,
            'fiveWhyRecords' => $fiveWhyCount,
            'qqoqccpRecords' => $qqoqccpCount,
            'amdecRecords' => $amdecCount,
            'paretoRecords' => $paretoCount,
            'eightDRecords' => $eightDCount,
            'recentRecords' => $enrichedRecords,
        ]);
    }
}

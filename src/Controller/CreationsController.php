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

#[Route('/mes-creations', name: 'app_creations_')]
#[IsGranted('ROLE_USER')]
final class CreationsController extends AbstractController
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
        $ishikawaRecords = $this->ishikawaRepository->findByUser($user->getId());
        $fiveWhyRecords = $this->fiveWhyRepository->findByUser($user->getId());
        $qqoqccpRecords = $this->qqoqccpRepository->findByUser($user->getId());
        $amdecRecords = $this->amdecRepository->findByUser($user->getId());
        $paretoRecords = $this->paretoRepository->findByUser($user->getId());
        $eightDRecords = $this->eightDRepository->findByUser($user->getId());
        $totalRecords = count($ishikawaRecords) + count($fiveWhyRecords) + count($qqoqccpRecords) + count($amdecRecords) + count($paretoRecords) + count($eightDRecords);

        return $this->render('creations/index.html.twig', [
            'ishikawaRecords' => $ishikawaRecords,
            'fiveWhyRecords' => $fiveWhyRecords,
            'qqoqccpRecords' => $qqoqccpRecords,
            'amdecRecords' => $amdecRecords,
            'paretoRecords' => $paretoRecords,
            'eightDRecords' => $eightDRecords,
            'totalRecords' => $totalRecords,
        ]);
    }
}

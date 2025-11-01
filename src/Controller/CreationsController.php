<?php

namespace App\Controller;

use App\Repository\RecordRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/mes-creations', name: 'app_creations_')]
#[IsGranted('ROLE_USER')]
final class CreationsController extends AbstractController
{
    public function __construct(
        private readonly RecordRepository $recordRepository
    ) {
    }

    #[Route('', name: 'index')]
    public function index(): Response
    {
        $user = $this->getUser();
        $records = $this->recordRepository->findBy(
            ['user' => $user],
            ['createdAt' => 'DESC']
        );

        // Séparer par type
        $ishikawaRecords = array_filter($records, fn($r) => $r->getType() === 'ishikawa');
        $fiveWhyRecords = array_filter($records, fn($r) => $r->getType() === 'fivewhy');

        return $this->render('creations/index.html.twig', [
            'ishikawaRecords' => $ishikawaRecords,
            'fiveWhyRecords' => $fiveWhyRecords,
            'totalRecords' => count($records),
        ]);
    }
}

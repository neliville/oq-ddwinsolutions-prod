<?php

namespace App\Controller\Api;

use App\Entity\Record;
use App\Repository\RecordRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/fivewhy')]
#[IsGranted('ROLE_USER')]
final class FiveWhyController extends AbstractController
{
    #[Route('/save', name: 'app_api_fivewhy_save', methods: ['POST'])]
    public function save(
        Request $request,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['title']) || !isset($data['content'])) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Les champs title et content sont requis.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $record = new Record();
        $record->setTitle($data['title']);
        $record->setType('fivewhy');
        $record->setContent(json_encode($data['content']));
        $record->setUser($this->getUser());

        $entityManager->persist($record);
        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Analyse 5 Pourquoi sauvegardée avec succès.',
            'data' => [
                'id' => $record->getId(),
                'title' => $record->getTitle(),
                'type' => $record->getType(),
                'createdAt' => $record->getCreatedAt()->format('Y-m-d H:i:s'),
            ],
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'app_api_fivewhy_get', methods: ['GET'])]
    public function get(int $id, RecordRepository $recordRepository): JsonResponse
    {
        $user = $this->getUser();
        $record = $recordRepository->findOneBy(['id' => $id, 'user' => $user, 'type' => 'fivewhy']);

        if (!$record) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Analyse 5 Pourquoi non trouvée.',
            ], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse([
            'success' => true,
            'data' => [
                'id' => $record->getId(),
                'title' => $record->getTitle(),
                'type' => $record->getType(),
                'content' => json_decode($record->getContent(), true),
                'createdAt' => $record->getCreatedAt()->format('Y-m-d H:i:s'),
            ],
        ], Response::HTTP_OK);
    }

    #[Route('/list', name: 'app_api_fivewhy_list', methods: ['GET'])]
    public function list(RecordRepository $recordRepository): JsonResponse
    {
        $user = $this->getUser();
        $records = $recordRepository->findBy(
            ['user' => $user, 'type' => 'fivewhy'],
            ['createdAt' => 'DESC']
        );

        $data = array_map(function (Record $record) {
            return [
                'id' => $record->getId(),
                'title' => $record->getTitle(),
                'content' => json_decode($record->getContent(), true),
                'createdAt' => $record->getCreatedAt()->format('Y-m-d H:i:s'),
            ];
        }, $records);

        return new JsonResponse(['data' => $data], Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'app_api_fivewhy_delete', methods: ['DELETE'])]
    public function delete(int $id, RecordRepository $recordRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $this->getUser();
        $record = $recordRepository->findOneBy(['id' => $id, 'user' => $user, 'type' => 'fivewhy']);

        if (!$record) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Analyse 5 Pourquoi non trouvée.',
            ], Response::HTTP_NOT_FOUND);
        }

        $entityManager->remove($record);
        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Analyse 5 Pourquoi supprimée avec succès.',
        ], Response::HTTP_OK);
    }
}


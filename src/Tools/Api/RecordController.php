<?php

namespace App\Tools\Api;

use App\Entity\Record;
use App\Repository\RecordRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/records')]
#[IsGranted('ROLE_USER')]
final class RecordController extends AbstractController
{
    #[Route('', name: 'app_api_records_list', methods: ['GET'])]
    public function list(RecordRepository $recordRepository): JsonResponse
    {
        $user = $this->getUser();
        $records = $recordRepository->findBy(['user' => $user], ['createdAt' => 'DESC']);

        $data = array_map(function (Record $record) {
            return [
                'id' => $record->getId(),
                'title' => $record->getTitle(),
                'type' => $record->getType(),
                'content' => json_decode($record->getContent(), true),
                'createdAt' => $record->getCreatedAt()->format('Y-m-d H:i:s'),
            ];
        }, $records);

        return new JsonResponse(['data' => $data], Response::HTTP_OK);
    }

    #[Route('', name: 'app_api_records_create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['title']) || !isset($data['type']) || !isset($data['content'])) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Les champs title, type et content sont requis.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $record = new Record();
        $record->setTitle($data['title']);
        $record->setType($data['type']);
        $record->setContent(json_encode($data['content']));
        $record->setUser($this->getUser());

        $entityManager->persist($record);
        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Enregistrement créé avec succès.',
            'data' => [
                'id' => $record->getId(),
                'title' => $record->getTitle(),
                'type' => $record->getType(),
                'createdAt' => $record->getCreatedAt()->format('Y-m-d H:i:s'),
            ],
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'app_api_records_get', methods: ['GET'])]
    public function get(int $id, RecordRepository $recordRepository): JsonResponse
    {
        $user = $this->getUser();
        $record = $recordRepository->findOneBy(['id' => $id, 'user' => $user]);

        if (!$record) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Enregistrement non trouvé.',
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

    #[Route('/{id}', name: 'app_api_records_update', methods: ['PUT'])]
    public function update(
        int $id,
        Request $request,
        RecordRepository $recordRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $user = $this->getUser();
        $record = $recordRepository->findOneBy(['id' => $id, 'user' => $user]);

        if (!$record) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Enregistrement non trouvé.',
            ], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['title'])) {
            $record->setTitle($data['title']);
        }
        if (isset($data['content'])) {
            $record->setContent(json_encode($data['content']));
        }

        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Enregistrement mis à jour avec succès.',
            'data' => [
                'id' => $record->getId(),
                'title' => $record->getTitle(),
                'type' => $record->getType(),
                'createdAt' => $record->getCreatedAt()->format('Y-m-d H:i:s'),
            ],
        ], Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'app_api_records_delete', methods: ['DELETE'])]
    public function delete(
        int $id,
        RecordRepository $recordRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $user = $this->getUser();
        $record = $recordRepository->findOneBy(['id' => $id, 'user' => $user]);

        if (!$record) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Enregistrement non trouvé.',
            ], Response::HTTP_NOT_FOUND);
        }

        $entityManager->remove($record);
        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Enregistrement supprimé avec succès.',
        ], Response::HTTP_OK);
    }
}

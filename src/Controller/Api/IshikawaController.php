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

#[Route('/api/ishikawa')]
#[IsGranted('ROLE_USER')]
final class IshikawaController extends AbstractController
{
    #[Route('/save', name: 'app_api_ishikawa_save', methods: ['POST'])]
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
        $record->setType('ishikawa');
        $record->setContent(json_encode($data['content']));
        $record->setUser($this->getUser());

        $entityManager->persist($record);
        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Diagramme Ishikawa sauvegardé avec succès.',
            'data' => [
                'id' => $record->getId(),
                'title' => $record->getTitle(),
                'type' => $record->getType(),
                'createdAt' => $record->getCreatedAt()->format('Y-m-d H:i:s'),
            ],
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'app_api_ishikawa_get', methods: ['GET'])]
    public function get(int $id, RecordRepository $recordRepository): JsonResponse
    {
        $user = $this->getUser();
        $record = $recordRepository->findOneBy(['id' => $id, 'user' => $user, 'type' => 'ishikawa']);

        if (!$record) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Diagramme Ishikawa non trouvé.',
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

    #[Route('/list', name: 'app_api_ishikawa_list', methods: ['GET'])]
    public function list(RecordRepository $recordRepository): JsonResponse
    {
        $user = $this->getUser();
        $records = $recordRepository->findBy(
            ['user' => $user, 'type' => 'ishikawa'],
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

    #[Route('/{id}', name: 'app_api_ishikawa_delete', methods: ['DELETE'])]
    public function delete(int $id, RecordRepository $recordRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $this->getUser();
        $record = $recordRepository->findOneBy(['id' => $id, 'user' => $user, 'type' => 'ishikawa']);

        if (!$record) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Diagramme Ishikawa non trouvé.',
            ], Response::HTTP_NOT_FOUND);
        }

        $entityManager->remove($record);
        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Diagramme Ishikawa supprimé avec succès.',
        ], Response::HTTP_OK);
    }
}

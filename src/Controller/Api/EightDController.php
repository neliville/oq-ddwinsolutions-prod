<?php

namespace App\Controller\Api;

use App\Entity\EightDAnalysis;
use App\Entity\User;
use App\Repository\EightDAnalysisRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/eightd')]
#[IsGranted('ROLE_USER')]
final class EightDController extends AbstractController
{
    #[Route('/save', name: 'app_api_eightd_save', methods: ['POST'])]
    public function save(
        Request $request,
        EntityManagerInterface $entityManager,
        EightDAnalysisRepository $repository
    ): JsonResponse {
        $payload = json_decode($request->getContent(), true);

        if (!isset($payload['title']) || !isset($payload['content'])) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Les champs title et content sont requis.',
            ], Response::HTTP_BAD_REQUEST);
        }

        /** @var User $user */
        $user = $this->getUser();
        $analysis = null;
        $isUpdate = false;

        if (!empty($payload['id'])) {
            $analysis = $repository->findOneBy([
                'id' => (int) $payload['id'],
                'user' => $user,
            ]);
            if ($analysis) {
                $isUpdate = true;
            }
        }

        if (!$analysis) {
            $analysis = new EightDAnalysis();
            $analysis->setUser($user);
            $entityManager->persist($analysis);
        }

        $analysis->setTitle($payload['title']);
        $analysis->setDescription($payload['description'] ?? null);
        $analysis->setData(json_encode($payload['content']));
        $analysis->setUpdatedAt(new \DateTimeImmutable());

        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => $isUpdate
                ? 'Analyse 8D mise à jour avec succès.'
                : 'Analyse 8D sauvegardée avec succès.',
            'data' => [
                'id' => $analysis->getId(),
                'title' => $analysis->getTitle(),
                'description' => $analysis->getDescription(),
                'createdAt' => $analysis->getCreatedAt()->format('Y-m-d H:i:s'),
                'updatedAt' => $analysis->getUpdatedAt()?->format('Y-m-d H:i:s'),
            ],
        ], $isUpdate ? Response::HTTP_OK : Response::HTTP_CREATED);
    }

    #[Route('/{id<\d+>}', name: 'app_api_eightd_get', methods: ['GET'])]
    public function get(int $id, EightDAnalysisRepository $repository): JsonResponse
    {
        $user = $this->getUser();
        $analysis = $repository->findOneBy(['id' => $id, 'user' => $user]);

        if (!$analysis) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Analyse 8D non trouvée.',
            ], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse([
            'success' => true,
            'data' => [
                'id' => $analysis->getId(),
                'title' => $analysis->getTitle(),
                'description' => $analysis->getDescription(),
                'content' => json_decode($analysis->getData(), true),
                'createdAt' => $analysis->getCreatedAt()->format('Y-m-d H:i:s'),
                'updatedAt' => $analysis->getUpdatedAt()?->format('Y-m-d H:i:s'),
            ],
        ], Response::HTTP_OK);
    }

    #[Route('/list', name: 'app_api_eightd_list', methods: ['GET'])]
    public function list(EightDAnalysisRepository $repository): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $analyses = $repository->findByUser($user->getId());

        $data = array_map(static function (EightDAnalysis $analysis) {
            return [
                'id' => $analysis->getId(),
                'title' => $analysis->getTitle(),
                'description' => $analysis->getDescription(),
                'content' => json_decode($analysis->getData(), true),
                'createdAt' => $analysis->getCreatedAt()->format('Y-m-d H:i:s'),
                'updatedAt' => $analysis->getUpdatedAt()?->format('Y-m-d H:i:s'),
            ];
        }, $analyses);

        return new JsonResponse(['data' => $data], Response::HTTP_OK);
    }

    #[Route('/{id<\d+>}', name: 'app_api_eightd_delete', methods: ['DELETE'])]
    public function delete(int $id, EightDAnalysisRepository $repository, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $this->getUser();
        $analysis = $repository->findOneBy(['id' => $id, 'user' => $user]);

        if (!$analysis) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Analyse 8D non trouvée.',
            ], Response::HTTP_NOT_FOUND);
        }

        $entityManager->remove($analysis);
        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Analyse 8D supprimée avec succès.',
        ], Response::HTTP_OK);
    }
}



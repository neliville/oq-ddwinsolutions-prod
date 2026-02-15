<?php

namespace App\Tools\Api;

use App\Entity\FiveWhyAnalysis;
use App\Entity\FiveWhyShare;
use App\Entity\User;
use App\Repository\FiveWhyAnalysisRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/fivewhy')]
#[IsGranted('ROLE_USER')]
final class FiveWhyController extends AbstractController
{
    #[Route('/save', name: 'app_api_fivewhy_save', methods: ['POST'])]
    public function save(
        Request $request,
        EntityManagerInterface $entityManager,
        FiveWhyAnalysisRepository $repository
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['title']) || !isset($data['content'])) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Les champs title et content sont requis.',
            ], Response::HTTP_BAD_REQUEST);
        }

        /** @var User $user */
        $user = $this->getUser();
        $analysis = null;
        $isUpdate = false;

        if (!empty($data['id'])) {
            $analysis = $repository->findOneBy([
                'id' => (int) $data['id'],
                'user' => $user,
            ]);
            if ($analysis) {
                $isUpdate = true;
            }
        }

        if (!$analysis) {
            $analysis = new FiveWhyAnalysis();
            $analysis->setUser($user);
            $entityManager->persist($analysis);
        }

        $analysis->setTitle($data['title']);
        $analysis->setProblem($data['problem'] ?? null);
        $analysis->setData(json_encode($data['content']));
        $analysis->setUpdatedAt(new \DateTimeImmutable());

        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => $isUpdate
                ? 'Analyse 5 Pourquoi mise à jour avec succès.'
                : 'Analyse 5 Pourquoi sauvegardée avec succès.',
            'data' => [
                'id' => $analysis->getId(),
                'title' => $analysis->getTitle(),
                'createdAt' => $analysis->getCreatedAt()->format('Y-m-d H:i:s'),
                'updatedAt' => $analysis->getUpdatedAt()?->format('Y-m-d H:i:s'),
            ],
        ], $isUpdate ? Response::HTTP_OK : Response::HTTP_CREATED);
    }

    #[Route('/share', name: 'app_api_fivewhy_share', methods: ['POST'])]
    public function share(
        Request $request,
        FiveWhyAnalysisRepository $repository,
        EntityManagerInterface $entityManager,
        UrlGeneratorInterface $urlGenerator
    ): JsonResponse {
        $payload = json_decode($request->getContent(), true);

        if (!isset($payload['id'])) {
            return new JsonResponse([
                'success' => false,
                'message' => 'L\'identifiant de l\'analyse est requis pour créer un partage.',
            ], Response::HTTP_BAD_REQUEST);
        }

        /** @var User $user */
        $user = $this->getUser();
        $analysis = $repository->findOneBy([
            'id' => (int) $payload['id'],
            'user' => $user,
        ]);

        if (!$analysis) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Analyse introuvable ou accès non autorisé.',
            ], Response::HTTP_FORBIDDEN);
        }

        $share = new FiveWhyShare();
        $share->setAnalysis($analysis);
        $share->setToken(bin2hex(random_bytes(24)));
        $share->setExpiresAt(new \DateTimeImmutable('+1 month'));

        $entityManager->persist($share);
        $entityManager->flush();

        $shareUrl = $urlGenerator->generate(
            'app_fivewhy_share_view',
            ['token' => $share->getToken()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        return new JsonResponse([
            'success' => true,
            'message' => 'Lien de partage généré.',
            'data' => [
                'url' => $shareUrl,
                'expiresAt' => $share->getExpiresAt()->format('Y-m-d H:i:s'),
            ],
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id<\\d+>}', name: 'app_api_fivewhy_get', methods: ['GET'])]
    public function get(int $id, FiveWhyAnalysisRepository $repository): JsonResponse
    {
        $user = $this->getUser();
        $analysis = $repository->findOneBy(['id' => $id, 'user' => $user]);

        if (!$analysis) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Analyse 5 Pourquoi non trouvée.',
            ], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse([
            'success' => true,
            'data' => [
                'id' => $analysis->getId(),
                'title' => $analysis->getTitle(),
                'problem' => $analysis->getProblem(),
                'content' => json_decode($analysis->getData(), true),
                'createdAt' => $analysis->getCreatedAt()->format('Y-m-d H:i:s'),
            ],
        ], Response::HTTP_OK);
    }

    #[Route('/list', name: 'app_api_fivewhy_list', methods: ['GET'])]
    public function list(FiveWhyAnalysisRepository $repository): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $analyses = $repository->findByUser($user->getId());

        $data = array_map(function (FiveWhyAnalysis $analysis) {
            return [
                'id' => $analysis->getId(),
                'title' => $analysis->getTitle(),
                'problem' => $analysis->getProblem(),
                'content' => json_decode($analysis->getData(), true),
                'createdAt' => $analysis->getCreatedAt()->format('Y-m-d H:i:s'),
                'updatedAt' => $analysis->getUpdatedAt()?->format('Y-m-d H:i:s'),
            ];
        }, $analyses);

        return new JsonResponse(['data' => $data], Response::HTTP_OK);
    }

    #[Route('/{id<\\d+>}', name: 'app_api_fivewhy_delete', methods: ['DELETE'])]
    public function delete(int $id, FiveWhyAnalysisRepository $repository, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $this->getUser();
        $analysis = $repository->findOneBy(['id' => $id, 'user' => $user]);

        if (!$analysis) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Analyse 5 Pourquoi non trouvée.',
            ], Response::HTTP_NOT_FOUND);
        }

        $entityManager->remove($analysis);
        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Analyse 5 Pourquoi supprimée avec succès.',
        ], Response::HTTP_OK);
    }
}

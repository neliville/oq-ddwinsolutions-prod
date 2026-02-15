<?php

namespace App\Tools\Api;

use App\Entity\IshikawaAnalysis;
use App\Entity\IshikawaShare;
use App\Entity\User;
use App\Repository\IshikawaAnalysisRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/ishikawa')]
#[IsGranted('ROLE_USER')]
final class IshikawaController extends AbstractController
{
    #[Route('/save', name: 'app_api_ishikawa_save', methods: ['POST'])]
    public function save(
        Request $request,
        IshikawaAnalysisRepository $repository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['title']) || !isset($data['content'])) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Les champs title et content sont requis.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->getUser();
        $analysis = null;

        $status = Response::HTTP_OK;

        if (isset($data['id'])) {
            $analysis = $repository->find($data['id']);

            if (!$analysis || $analysis->getUser() !== $user) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Diagramme introuvable ou accès non autorisé.',
                ], Response::HTTP_FORBIDDEN);
            }

            $analysis->setUpdatedAt(new \DateTimeImmutable());
        } else {
            $analysis = new IshikawaAnalysis();
            $analysis->setUser($user);
            $status = Response::HTTP_CREATED;
        }

        $analysis->setTitle($data['title']);
        $analysis->setProblem($data['problem'] ?? null);
        $analysis->setData(json_encode($data['content']));

        $entityManager->persist($analysis);
        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Diagramme Ishikawa sauvegardé avec succès.',
            'data' => [
                'id' => $analysis->getId(),
                'title' => $analysis->getTitle(),
                'createdAt' => $analysis->getCreatedAt()->format('Y-m-d H:i:s'),
                'updatedAt' => $analysis->getUpdatedAt()?->format('Y-m-d H:i:s'),
            ],
        ], $status);
    }

    #[Route('/share', name: 'app_api_ishikawa_share', methods: ['POST'])]
    public function share(
        Request $request,
        IshikawaAnalysisRepository $analysisRepository,
        EntityManagerInterface $entityManager,
        UrlGeneratorInterface $urlGenerator
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['id'])) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Le diagramme à partager est requis.',
            ], Response::HTTP_BAD_REQUEST);
        }

        /** @var User $user */
        $user = $this->getUser();
        $analysis = $analysisRepository->find($data['id']);

        if (!$analysis || $analysis->getUser() !== $user) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Diagramme introuvable ou accès refusé.',
            ], Response::HTTP_FORBIDDEN);
        }

        $share = new IshikawaShare();
        $share->setToken(bin2hex(random_bytes(24)));
        $share->setAnalysis($analysis);
        $share->setExpiresAt(new \DateTimeImmutable('+1 month'));

        $entityManager->persist($share);
        $entityManager->flush();

        $shareUrl = $urlGenerator->generate(
            'app_ishikawa_share_view',
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

    #[Route('/{id<\d+>}', name: 'app_api_ishikawa_get', methods: ['GET'])]
    public function get(int $id, IshikawaAnalysisRepository $repository): JsonResponse
    {
        $user = $this->getUser();
        $analysis = $repository->findOneBy(['id' => $id, 'user' => $user]);

        if (!$analysis) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Diagramme Ishikawa non trouvé.',
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

    #[Route('/list', name: 'app_api_ishikawa_list', methods: ['GET'])]
    public function list(IshikawaAnalysisRepository $repository): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $analyses = $repository->findByUser($user->getId());

        $data = array_map(function (IshikawaAnalysis $analysis) {
            return [
                'id' => $analysis->getId(),
                'title' => $analysis->getTitle(),
                'problem' => $analysis->getProblem(),
                'content' => json_decode($analysis->getData(), true),
                'createdAt' => $analysis->getCreatedAt()->format('Y-m-d H:i:s'),
            ];
        }, $analyses);

        return new JsonResponse(['data' => $data], Response::HTTP_OK);
    }

    #[Route('/{id<\d+>}', name: 'app_api_ishikawa_delete', methods: ['DELETE'])]
    public function delete(int $id, IshikawaAnalysisRepository $repository, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $this->getUser();
        $analysis = $repository->findOneBy(['id' => $id, 'user' => $user]);

        if (!$analysis) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Diagramme Ishikawa non trouvé.',
            ], Response::HTTP_NOT_FOUND);
        }

        $entityManager->remove($analysis);
        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Diagramme Ishikawa supprimé avec succès.',
        ], Response::HTTP_OK);
    }
}

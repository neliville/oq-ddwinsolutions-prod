<?php

namespace App\Controller\Api;

use App\Entity\AmdecAnalysis;
use App\Entity\AmdecShare;
use App\Entity\User;
use App\Repository\AmdecAnalysisRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/amdec')]
#[IsGranted('ROLE_USER')]
final class AmdecController extends AbstractController
{
    #[Route('/save', name: 'app_api_amdec_save', methods: ['POST'])]
    public function save(
        Request $request,
        EntityManagerInterface $entityManager,
        AmdecAnalysisRepository $repository
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
            $analysis = new AmdecAnalysis();
            $analysis->setUser($user);
            $entityManager->persist($analysis);
        }

        $analysis->setTitle($payload['title']);
        $analysis->setSubject($payload['subject'] ?? null);
        $analysis->setData(json_encode($payload['content']));
        $analysis->setUpdatedAt(new \DateTimeImmutable());

        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => $isUpdate
                ? 'Analyse AMDEC mise à jour avec succès.'
                : 'Analyse AMDEC sauvegardée avec succès.',
            'data' => [
                'id' => $analysis->getId(),
                'title' => $analysis->getTitle(),
                'subject' => $analysis->getSubject(),
                'createdAt' => $analysis->getCreatedAt()->format('Y-m-d H:i:s'),
                'updatedAt' => $analysis->getUpdatedAt()?->format('Y-m-d H:i:s'),
            ],
        ], $isUpdate ? Response::HTTP_OK : Response::HTTP_CREATED);
    }

    #[Route('/share', name: 'app_api_amdec_share', methods: ['POST'])]
    public function share(
        Request $request,
        AmdecAnalysisRepository $repository,
        EntityManagerInterface $entityManager,
        UrlGeneratorInterface $urlGenerator
    ): JsonResponse {
        $payload = json_decode($request->getContent(), true);

        if (!isset($payload['id'])) {
            return new JsonResponse([
                'success' => false,
                'message' => 'L\'identifiant de l\'analyse est requis pour le partage.',
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

        $share = new AmdecShare();
        $share->setAnalysis($analysis);
        $share->setToken(bin2hex(random_bytes(24)));
        $share->setExpiresAt(new \DateTimeImmutable('+1 month'));

        $entityManager->persist($share);
        $entityManager->flush();

        $shareUrl = $urlGenerator->generate(
            'app_amdec_share_view',
            ['token' => $share->getToken()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        return new JsonResponse([
            'success' => true,
            'message' => 'Lien de partage AMDEC généré.',
            'data' => [
                'url' => $shareUrl,
                'expiresAt' => $share->getExpiresAt()->format('Y-m-d H:i:s'),
            ],
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id<\\d+>}', name: 'app_api_amdec_get', methods: ['GET'])]
    public function get(int $id, AmdecAnalysisRepository $repository): JsonResponse
    {
        $user = $this->getUser();
        $analysis = $repository->findOneBy(['id' => $id, 'user' => $user]);

        if (!$analysis) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Analyse AMDEC non trouvée.',
            ], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse([
            'success' => true,
            'data' => [
                'id' => $analysis->getId(),
                'title' => $analysis->getTitle(),
                'subject' => $analysis->getSubject(),
                'content' => json_decode($analysis->getData(), true),
                'createdAt' => $analysis->getCreatedAt()->format('Y-m-d H:i:s'),
                'updatedAt' => $analysis->getUpdatedAt()?->format('Y-m-d H:i:s'),
            ],
        ], Response::HTTP_OK);
    }

    #[Route('/list', name: 'app_api_amdec_list', methods: ['GET'])]
    public function list(AmdecAnalysisRepository $repository): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $analyses = $repository->findByUser($user->getId());

        $data = array_map(static function (AmdecAnalysis $analysis) {
            return [
                'id' => $analysis->getId(),
                'title' => $analysis->getTitle(),
                'subject' => $analysis->getSubject(),
                'content' => json_decode($analysis->getData(), true),
                'createdAt' => $analysis->getCreatedAt()->format('Y-m-d H:i:s'),
                'updatedAt' => $analysis->getUpdatedAt()?->format('Y-m-d H:i:s'),
            ];
        }, $analyses);

        return new JsonResponse(['data' => $data], Response::HTTP_OK);
    }

    #[Route('/{id<\\d+>}', name: 'app_api_amdec_delete', methods: ['DELETE'])]
    public function delete(int $id, AmdecAnalysisRepository $repository, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $this->getUser();
        $analysis = $repository->findOneBy(['id' => $id, 'user' => $user]);

        if (!$analysis) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Analyse AMDEC non trouvée.',
            ], Response::HTTP_NOT_FOUND);
        }

        $entityManager->remove($analysis);
        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Analyse AMDEC supprimée avec succès.',
        ], Response::HTTP_OK);
    }
}



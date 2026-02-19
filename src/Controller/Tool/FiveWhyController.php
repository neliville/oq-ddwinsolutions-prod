<?php

namespace App\Controller\Tool;

use App\Entity\FiveWhyAnalysis;
use App\Entity\User;
use App\Repository\FiveWhyAnalysisRepository;
use App\Validator\Constraints\ValidToolData;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/fivewhy')]
final class FiveWhyController extends AbstractToolController
{
    public function __construct(
        private readonly FiveWhyAnalysisRepository $repository,
        private readonly EntityManagerInterface $entityManager,
        \App\Application\Lead\CreateLead $createLead,
        \App\Application\Analytics\TrackingService $trackingService,
        \App\Application\Notification\NotificationService $notificationService,
        \App\Repository\LeadRepository $leadRepository,
        \Psr\Log\LoggerInterface $logger,
    ) {
        parent::__construct($createLead, $trackingService, $notificationService, $leadRepository, $logger);
    }

    protected function getToolName(): string
    {
        return 'fivewhy';
    }

    #[Route('/save', name: 'app_tool_fivewhy_save', methods: ['POST'])]
    public function save(Request $request, ValidatorInterface $validator, RateLimiterFactory $anonymousToolLimiter): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new BadRequestHttpException('Invalid JSON');
        }

        $violations = $validator->validate($data, [new ValidToolData(tool: $this->getToolName())]);
        if ($violations->count() > 0) {
            throw new BadRequestHttpException('Validation failed');
        }

        $user = $this->getUser();
        $isGuest = !$user;

        if ($isGuest) {
            $limiter = $anonymousToolLimiter->create($request->getClientIp());
            if (!$limiter->consume(1)->isAccepted()) {
                throw new TooManyRequestsHttpException();
            }
            $this->createLeadFromToolUsage($request, $this->getToolName());

            return new JsonResponse([
                'success' => true,
                'message' => 'Analyse sauvegardée localement. Connectez-vous pour sauvegarder définitivement.',
                'guest' => true,
                'data' => [
                    'title' => $data['title'],
                    'content' => $data['content'],
                    'problem' => $data['problem'] ?? null,
                    'timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                ],
            ], Response::HTTP_OK);
        }

        /** @var User $user */
        $analysis = null;
        $isUpdate = false;

        if (!empty($data['id'])) {
            $analysis = $this->repository->findOneBy([
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
            $this->entityManager->persist($analysis);
        }

        $analysis->setTitle($data['title']);
        $analysis->setProblem($data['problem'] ?? null);
        $analysis->setData(json_encode($data['content']));
        $analysis->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->flush();

        $this->createLeadFromToolUsage($request, $this->getToolName(), $user);

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

    #[Route('/list', name: 'app_tool_fivewhy_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Authentification requise pour lister les analyses.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        /** @var User $user */
        $analyses = $this->repository->findByUser($user->getId());

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

    #[Route('/{id<\d+>}', name: 'app_tool_fivewhy_get', methods: ['GET'])]
    public function get(int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Authentification requise.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        /** @var User $user */
        $analysis = $this->repository->findOneBy(['id' => $id, 'user' => $user]);

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

    #[Route('/{id<\d+>}', name: 'app_tool_fivewhy_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Authentification requise.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        /** @var User $user */
        $analysis = $this->repository->findOneBy(['id' => $id, 'user' => $user]);

        if (!$analysis) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Analyse 5 Pourquoi non trouvée.',
            ], Response::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($analysis);
        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Analyse 5 Pourquoi supprimée avec succès.',
        ], Response::HTTP_OK);
    }
}

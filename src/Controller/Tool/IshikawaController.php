<?php

namespace App\Controller\Tool;

use App\Application\Analytics\TrackingService;
use App\Application\Lead\CreateLead;
use App\Application\Lead\CreateLeadRequest;
use App\Application\Notification\NotificationService;
use App\Domain\Analytics\ToolUsedEvent;
use App\Entity\IshikawaAnalysis;
use App\Entity\User;
use App\Repository\IshikawaAnalysisRepository;
use App\Repository\LeadRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Contrôleur pour l'outil Ishikawa (accessible sans compte)
 */
#[Route('/api/ishikawa')]
final class IshikawaController extends AbstractController
{
    public function __construct(
        private readonly IshikawaAnalysisRepository $repository,
        private readonly EntityManagerInterface $entityManager,
        private readonly CreateLead $createLead,
        private readonly TrackingService $trackingService,
        private readonly NotificationService $notificationService,
        private readonly LeadRepository $leadRepository,
    ) {
    }

    /**
     * Sauvegarde une analyse Ishikawa
     * Accessible sans compte : sauvegarde en localStorage côté client
     * Si utilisateur connecté : sauvegarde en DB
     */
    #[Route('/save', name: 'app_tool_ishikawa_save', methods: ['POST'])]
    public function save(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['title']) || !isset($data['content'])) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Les champs title et content sont requis.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->getUser();
        $isGuest = !$user;

        // Si invité : retourner les données pour localStorage
        if ($isGuest) {
            // Créer un lead automatiquement
            $this->createLeadFromToolUsage($request, 'ishikawa');

            return new JsonResponse([
                'success' => true,
                'message' => 'Diagramme sauvegardé localement. Connectez-vous pour sauvegarder définitivement.',
                'guest' => true,
                'data' => [
                    'title' => $data['title'],
                    'content' => $data['content'],
                    'problem' => $data['problem'] ?? null,
                    'timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                ],
            ], Response::HTTP_OK);
        }

        // Utilisateur connecté : sauvegarde en DB
        /** @var User $user */
        $analysis = null;
        $status = Response::HTTP_OK;

        if (isset($data['id'])) {
            $analysis = $this->repository->find($data['id']);
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

        $this->entityManager->persist($analysis);
        $this->entityManager->flush();

        // Créer un lead si première utilisation
        $this->createLeadFromToolUsage($request, 'ishikawa', $user);

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

    /**
     * Liste les analyses (uniquement pour utilisateurs connectés)
     */
    #[Route('/list', name: 'app_tool_ishikawa_list', methods: ['GET'])]
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

    /**
     * Récupère une analyse (uniquement pour utilisateurs connectés)
     */
    #[Route('/{id<\d+>}', name: 'app_tool_ishikawa_get', methods: ['GET'])]
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

    /**
     * Supprime une analyse (uniquement pour utilisateurs connectés)
     */
    #[Route('/{id<\d+>}', name: 'app_tool_ishikawa_delete', methods: ['DELETE'])]
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
                'message' => 'Diagramme Ishikawa non trouvé.',
            ], Response::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($analysis);
        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Diagramme Ishikawa supprimé avec succès.',
        ], Response::HTTP_OK);
    }

    /**
     * Crée un lead automatiquement lors de l'utilisation d'un outil
     */
    private function createLeadFromToolUsage(Request $request, string $tool, ?User $user = null): void
    {
        // Vérifier si un lead existe déjà pour cette session/email
        $sessionId = $request->getSession()->getId();
        $email = $user?->getEmail();

        if ($email) {
            $existingLead = $this->leadRepository->findByEmail($email);
            if ($existingLead && $existingLead->getTool() === $tool) {
                // Lead déjà créé pour cet outil
                return;
            }
        }

        // Créer le lead
        $utmSource = $request->query->get('utm_source') ?? $request->request->get('utm_source');
        $utmMedium = $request->query->get('utm_medium') ?? $request->request->get('utm_medium');
        $utmCampaign = $request->query->get('utm_campaign') ?? $request->request->get('utm_campaign');

        $leadRequest = new CreateLeadRequest(
            email: $email,
            name: null,
            source: 'tool',
            tool: $tool,
            utmSource: $utmSource,
            utmMedium: $utmMedium,
            utmCampaign: $utmCampaign,
            ipAddress: $request->getClientIp(),
            userAgent: $request->headers->get('User-Agent'),
            sessionId: $sessionId,
            gdprConsent: false, // Consentement implicite via utilisation de l'outil
        );

        try {
            $leadResponse = $this->createLead->execute($leadRequest);
            
            // Notifier la création du lead
            $lead = $this->leadRepository->find($leadResponse->id);
            if ($lead) {
                $this->notificationService->notifyLeadCreated($lead);
            }

            // Tracker l'utilisation de l'outil
            $this->trackingService->trackToolUsed(new ToolUsedEvent(
                tool: $tool,
                sessionId: $sessionId,
                ipAddress: $request->getClientIp(),
                userAgent: $request->headers->get('User-Agent'),
                userId: $user?->getId(),
            ));
        } catch (\Exception $e) {
            // Log l'erreur mais ne bloque pas l'utilisation de l'outil
            error_log('Erreur lors de la création du lead : ' . $e->getMessage());
        }
    }
}


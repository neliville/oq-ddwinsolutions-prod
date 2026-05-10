<?php

declare(strict_types=1);

namespace App\Controller\Collaboration;

use App\Collaboration\CollaboratorInvitationRole;
use App\Collaboration\InvitationType;
use App\Collaboration\SharedAccessLevel;
use App\Collaboration\SharedResourceType;
use App\Collaboration\UserInvitationService;
use App\Collaboration\SharedAccessService;
use App\Collaboration\CollaborationSuggestionEngine;
use App\Collaboration\InvitationStatus;
use App\Entity\User;
use App\Repository\SharedAccessRepository;
use App\Repository\UserInvitationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/collaboration')]
#[IsGranted('ROLE_USER')]
final class CollaborationApiController extends AbstractController
{
    public function __construct(
        private readonly UserInvitationService $userInvitationService,
        private readonly SharedAccessService $sharedAccessService,
        private readonly UserInvitationRepository $userInvitationRepository,
        private readonly SharedAccessRepository $sharedAccessRepository,
        private readonly CollaborationSuggestionEngine $suggestionEngine,
    ) {
    }

    #[Route('/invite', name: 'app_api_collaboration_invite', methods: ['POST'])]
    public function invite(Request $request): JsonResponse
    {
        if (!$this->isCsrfValid($request)) {
            return new JsonResponse(['ok' => false, 'error' => 'csrf'], Response::HTTP_FORBIDDEN);
        }
        try {
            $data = $request->toArray();
        } catch (\Throwable) {
            return new JsonResponse(['ok' => false, 'error' => 'invalid_json'], Response::HTTP_BAD_REQUEST);
        }
        $email = isset($data['email']) ? trim((string) $data['email']) : '';
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return new JsonResponse(['ok' => false, 'error' => 'invalid_email'], Response::HTTP_BAD_REQUEST);
        }
        /** @var User $owner */
        $owner = $this->getUser();
        if (mb_strtolower($email) === mb_strtolower((string) $owner->getEmail())) {
            return new JsonResponse(['ok' => false, 'error' => 'self_invite'], Response::HTTP_BAD_REQUEST);
        }
        $firstName = isset($data['firstName']) ? trim((string) $data['firstName']) : null;
        $role = CollaboratorInvitationRole::tryFrom((string) ($data['role'] ?? 'lecteur')) ?? CollaboratorInvitationRole::LECTEUR;
        $source = (string) ($data['source'] ?? 'dashboard');
        $type = match ($source) {
            'audit', 'capa', 'risk' => InvitationType::CONTEXTUELLE,
            default => InvitationType::GENERALE,
        };
        $context = ['source' => $source];
        if (isset($data['auditId'])) {
            $context['auditId'] = (int) $data['auditId'];
        }
        if (isset($data['capaId'])) {
            $context['capaId'] = (int) $data['capaId'];
        }

        $created = $this->userInvitationService->createAndPersist($owner, $email, $firstName ?: null, $role, $type, $context);
        $send = filter_var($data['sendEmail'] ?? true, FILTER_VALIDATE_BOOLEAN);
        if ($send) {
            $this->userInvitationService->sendInvitationEmail($created['invitation'], $created['acceptUrl']);
        }

        return new JsonResponse([
            'ok' => true,
            'invitationId' => $created['invitation']->getId(),
            'acceptUrl' => $created['acceptUrl'],
            'plainToken' => $created['plainToken'],
        ]);
    }

    #[Route('/share', name: 'app_api_collaboration_share', methods: ['POST'])]
    public function share(Request $request): JsonResponse
    {
        if (!$this->isCsrfValid($request)) {
            return new JsonResponse(['ok' => false, 'error' => 'csrf'], Response::HTTP_FORBIDDEN);
        }
        try {
            $data = $request->toArray();
        } catch (\Throwable) {
            return new JsonResponse(['ok' => false, 'error' => 'invalid_json'], Response::HTTP_BAD_REQUEST);
        }
        /** @var User $owner */
        $owner = $this->getUser();
        $targetType = SharedResourceType::tryFrom((string) ($data['targetType'] ?? ''));
        if ($targetType === null) {
            return new JsonResponse(['ok' => false, 'error' => 'target_type'], Response::HTTP_BAD_REQUEST);
        }
        $targetId = (int) ($data['targetId'] ?? 0);
        if ($targetId <= 0) {
            return new JsonResponse(['ok' => false, 'error' => 'target_id'], Response::HTTP_BAD_REQUEST);
        }
        $level = SharedAccessLevel::tryFrom((string) ($data['accessLevel'] ?? 'lecture_seule')) ?? SharedAccessLevel::LECTURE_SEULE;
        $ttlDays = (int) ($data['ttlDays'] ?? $this->sharedAccessService->getDefaultShareTtlDays());
        if (!in_array($ttlDays, [7, 30], true)) {
            $ttlDays = $this->sharedAccessService->getDefaultShareTtlDays();
        }
        $invitedEmail = isset($data['invitedEmail']) ? trim((string) $data['invitedEmail']) : null;
        if ($invitedEmail === '') {
            $invitedEmail = null;
        }
        if ($invitedEmail !== null && !filter_var($invitedEmail, FILTER_VALIDATE_EMAIL)) {
            return new JsonResponse(['ok' => false, 'error' => 'invalid_email'], Response::HTTP_BAD_REQUEST);
        }
        $sendEmail = filter_var($data['sendEmail'] ?? false, FILTER_VALIDATE_BOOLEAN);

        try {
            $out = $this->sharedAccessService->createShare(
                $owner,
                $targetType,
                $targetId,
                $level,
                $ttlDays,
                $invitedEmail,
                $sendEmail,
            );
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['ok' => false, 'error' => 'forbidden'], Response::HTTP_FORBIDDEN);
        }

        return new JsonResponse([
            'ok' => true,
            'sharedAccessId' => $out['entity']->getId(),
            'shareUrl' => $out['shareUrl'],
            'plainToken' => $out['plainToken'],
        ]);
    }

    #[Route('/share/{id}/revoke', name: 'app_api_collaboration_share_revoke', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function revokeShare(int $id, Request $request): JsonResponse
    {
        if (!$this->isCsrfValid($request)) {
            return new JsonResponse(['ok' => false, 'error' => 'csrf'], Response::HTTP_FORBIDDEN);
        }
        /** @var User $owner */
        $owner = $this->getUser();
        $ok = $this->sharedAccessService->revoke($owner, $id);

        return new JsonResponse(['ok' => $ok], $ok ? Response::HTTP_OK : Response::HTTP_NOT_FOUND);
    }

    #[Route('/summary', name: 'app_api_collaboration_summary', methods: ['GET'])]
    public function summary(): JsonResponse
    {
        /** @var User $owner */
        $owner = $this->getUser();
        $invSent = $this->userInvitationRepository->countByOwnerAndStatus($owner, InvitationStatus::ENVOYEE);
        $invAccepted = $this->userInvitationRepository->countByOwnerAndStatus($owner, InvitationStatus::ACCEPTEE);
        $shares = $this->sharedAccessRepository->countActiveForOwner($owner);

        return new JsonResponse([
            'invitationsPending' => $invSent,
            'invitationsAccepted' => $invAccepted,
            'activeShares' => $shares,
        ]);
    }

    #[Route('/suggestion/dismiss', name: 'app_api_collaboration_suggestion_dismiss', methods: ['POST'])]
    public function dismissSuggestion(Request $request): JsonResponse
    {
        if (!$this->isCsrfValid($request)) {
            return new JsonResponse(['ok' => false], Response::HTTP_FORBIDDEN);
        }
        try {
            $data = $request->toArray();
        } catch (\Throwable) {
            return new JsonResponse(['ok' => false], Response::HTTP_BAD_REQUEST);
        }
        $key = (string) ($data['suggestionKey'] ?? '');
        if ($key === '') {
            return new JsonResponse(['ok' => false], Response::HTTP_BAD_REQUEST);
        }
        /** @var User $user */
        $user = $this->getUser();
        $this->suggestionEngine->dismiss($user, $key);

        return new JsonResponse(['ok' => true]);
    }

    private function isCsrfValid(Request $request): bool
    {
        $token = $request->headers->get('X-CSRF-TOKEN');
        if (!\is_string($token) || $token === '') {
            try {
                $json = $request->toArray();
                $token = isset($json['csrf_token']) ? (string) $json['csrf_token'] : '';
            } catch (\Throwable) {
                $token = '';
            }
        }

        return $this->isCsrfTokenValid('collaboration', $token);
    }
}

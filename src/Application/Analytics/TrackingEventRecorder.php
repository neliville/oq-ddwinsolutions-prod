<?php

declare(strict_types=1);

namespace App\Application\Analytics;

use App\Entity\TrackingEvent;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Persiste un {@see TrackingEvent} à partir du contexte HTTP courant (route, referer, hachages RGPD).
 */
final class TrackingEventRecorder
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly EntityManagerInterface $entityManager,
        private readonly Security $security,
        #[Autowire('%kernel.secret%')]
        private readonly string $kernelSecret,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @param array<string, mixed> $metadata Fusionné avec les métadonnées de requête (route, path).
     */
    public function record(
        TrackingEventType $type,
        array $metadata = [],
        ?User $user = null,
        ?string $tool = null,
        ?string $action = null,
        ?string $source = null,
        ?string $context = null,
    ): void {
        $user ??= $this->security->getUser();
        if (!$user instanceof User) {
            $user = null;
        }

        $request = $this->requestStack->getCurrentRequest();
        $resolvedSource = $source ?? $this->guessSource($request?->getPathInfo() ?? '');

        $meta = $metadata;
        if ($request !== null) {
            $route = $request->attributes->get('_route');
            if (\is_string($route) && $route !== '') {
                $meta['route'] = $route;
            }
            $meta['path'] = $request->getPathInfo();
            $ref = $request->headers->get('Referer');
            if (\is_string($ref) && $ref !== '') {
                $meta['referer_host'] = (string) (parse_url($ref, PHP_URL_HOST) ?? '');
            }
        }

        $event = new TrackingEvent();
        $event->setEventType($type);
        $event->setUser($user);
        $event->setTool($tool);
        $event->setAction($action);
        $event->setSource($resolvedSource);
        $event->setContext($context);
        $event->setMetadata($meta === [] ? null : $meta);
        $event->setCreatedAt(new \DateTimeImmutable());

        if ($request !== null) {
            $ip = $request->getClientIp();
            if (\is_string($ip) && $ip !== '') {
                $event->setIpHash($this->hashSensitive($ip));
            }
            if ($request->hasSession()) {
                $sid = $request->getSession()->getId();
                if ($sid !== '') {
                    $event->setSessionKey($this->hashSensitive($sid));
                }
            }
        }

        try {
            $this->entityManager->persist($event);
            $this->entityManager->flush();
        } catch (\Throwable $e) {
            // Ne pas bloquer le parcours métier (ex. CAPA préremplie) si la table tracking_event
            // n’existe pas encore ou en cas d’erreur SQL ponctuelle.
            $this->logger->warning('TrackingEventRecorder : enregistrement ignoré.', [
                'event_type' => $type->value,
                'message' => $e->getMessage(),
            ]);
            if ($this->entityManager->contains($event)) {
                $this->entityManager->detach($event);
            }
        }
    }

    private function guessSource(string $path): string
    {
        if (str_starts_with($path, '/admin')) {
            return 'admin';
        }
        if (str_starts_with($path, '/api')) {
            return 'api';
        }

        return 'web';
    }

    private function hashSensitive(string $value): string
    {
        return substr(hash('sha256', $value.'|'.$this->kernelSecret), 0, 64);
    }
}

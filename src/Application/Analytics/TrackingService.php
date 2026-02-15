<?php

namespace App\Application\Analytics;

use App\Domain\Analytics\LeadConvertedEvent;
use App\Domain\Analytics\ToolUsedEvent;
use App\Entity\PageView;
use App\Repository\PageViewRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service de tracking et analytics
 */
class TrackingService
{
    public function __construct(
        private readonly PageViewRepository $pageViewRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Enregistre l'utilisation d'un outil
     */
    public function trackToolUsed(ToolUsedEvent $event): void
    {
        // Créer une PageView spéciale pour l'outil
        $pageView = new PageView();
        $pageView->setUrl('/outil/' . $event->tool);
        $pageView->setIpAddress($event->ipAddress);
        $pageView->setUserAgent($event->userAgent);
        $pageView->setSessionId($event->sessionId);
        $pageView->setMethod('GET');

        $this->entityManager->persist($pageView);
        $this->entityManager->flush();
    }

    /**
     * Enregistre la conversion d'un lead
     */
    public function trackLeadConverted(LeadConvertedEvent $event): void
    {
        // Logique de tracking de conversion
        // Peut être étendue avec une entité Conversion si nécessaire
    }

    /**
     * Enregistre une page view
     */
    public function trackPageView(
        string $url,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?string $sessionId = null,
        ?string $referer = null,
        ?int $userId = null
    ): void {
        $pageView = new PageView();
        $pageView->setUrl($url);
        $pageView->setIpAddress($ipAddress);
        $pageView->setUserAgent($userAgent);
        $pageView->setSessionId($sessionId);
        $pageView->setReferer($referer);
        $pageView->setMethod('GET');

        if ($userId) {
            // TODO: Charger l'utilisateur si nécessaire
        }

        $this->entityManager->persist($pageView);
        $this->entityManager->flush();
    }
}


<?php

namespace App\Service\Analytics;

use App\Entity\PageView;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\SecurityBundle\Security;

class PageViewLogger
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly Security $security,
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    public function log(Request $request): void
    {
        try {
            $pageView = new PageView();
            $pageView->setUrl($request->getUri());
            $pageView->setIpAddress($request->getClientIp());
            $pageView->setUserAgent($request->headers->get('User-Agent'));
            $pageView->setReferer($request->headers->get('Referer'));
            $pageView->setMethod($request->getMethod());

            $session = $request->getSession();
            if ($session && $session->isStarted()) {
                $pageView->setSessionId($session->getId());
            }

            $user = $this->security->getUser();
            if ($user instanceof User) {
                $pageView->setUser($user);
            }

            // Placeholder for geo/device enrichment (can be expanded later)
            $pageView->setCountry($request->headers->get('X-Country') ?: null);
            $pageView->setCity($request->headers->get('X-City') ?: null);
            $pageView->setDevice($request->headers->get('X-Device') ?: null);

            $this->entityManager->persist($pageView);
            $this->entityManager->flush();
        } catch (\Throwable $exception) {
            if ($this->logger) {
                $this->logger->warning('Page view logging failed', [
                    'message' => $exception->getMessage(),
                ]);
            }
        }
    }
}

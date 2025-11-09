<?php

namespace App\Service\Analytics;

use App\Entity\ExportLog;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\SecurityBundle\Security;

class ExportLogger
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly Security $security,
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    public function log(string $tool, string $format, Request $request, array $metadata = []): void
    {
        try {
            $log = new ExportLog();
            $log->setTool($tool);
            $log->setFormat($format);
            $log->setSourceUrl($request->headers->get('Referer') ?? $request->get('sourceUrl'));
            $log->setIpAddress($request->getClientIp());
            $log->setUserAgent($request->headers->get('User-Agent'));
            $log->setReferer($request->headers->get('Referer'));

            if ($metadata !== []) {
                $log->setMetadata($metadata);
            }

            $user = $this->security->getUser();
            if ($user instanceof User) {
                $log->setUser($user);
            }

            $this->entityManager->persist($log);
            $this->entityManager->flush();
        } catch (\Throwable $exception) {
            if ($this->logger) {
                $this->logger->warning('Export logging failed', [
                    'message' => $exception->getMessage(),
                ]);
            }
        }
    }
}

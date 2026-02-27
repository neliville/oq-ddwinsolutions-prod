<?php

declare(strict_types=1);

namespace App\Newsletter\Service;

use App\Newsletter\DTO\NewsletterSubscriptionDTO;
use App\Newsletter\Exception\MauticApiException;
use App\Newsletter\Exception\NewsletterException;
use App\Newsletter\Infrastructure\MauticClient;
use Psr\Log\LoggerInterface;

final class NewsletterSubscriber
{
    public const TAG_NEWSLETTER = 'newsletter';

    public function __construct(
        private readonly MauticClient $mauticClient,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Inscrit un contact à la newsletter via Mautic.
     *
     * @throws NewsletterException En cas d'échec (validation ou API)
     */
    public function subscribe(NewsletterSubscriptionDTO $dto): void
    {
        $email = $dto->getEmail();
        if ('' === trim($email)) {
            throw new NewsletterException('L\'adresse email est requise.');
        }
        if (!filter_var($email, \FILTER_VALIDATE_EMAIL)) {
            throw new NewsletterException('L\'adresse email n\'est pas valide.');
        }

        $payload = [
            'email' => trim($email),
            'tags' => [['tag' => self::TAG_NEWSLETTER]],
        ];

        $firstname = $dto->getFirstname();
        if (null !== $firstname && '' !== trim($firstname)) {
            $payload['firstname'] = trim($firstname);
        }

        try {
            $this->mauticClient->createOrUpdateContact($payload);
        } catch (MauticApiException $e) {
            $this->logger->error('Mautic API error during newsletter subscription', [
                'email' => $email,
                'statusCode' => $e->getStatusCode(),
                'message' => $e->getMessage(),
            ]);

            throw new NewsletterException(
                'Impossible de finaliser l\'inscription à la newsletter. Veuillez réessayer plus tard.',
                0,
                $e,
            );
        }
    }
}

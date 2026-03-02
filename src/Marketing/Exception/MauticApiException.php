<?php

declare(strict_types=1);

namespace App\Marketing\Exception;

/**
 * Domain exception: Mautic API request failed (4xx/5xx or network error).
 */
final class MauticApiException extends \DomainException
{
    public function __construct(
        string $message,
        private readonly int $statusCode = 0,
        private readonly ?string $responseBody = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getResponseBody(): ?string
    {
        return $this->responseBody;
    }
}

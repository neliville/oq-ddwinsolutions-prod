<?php

declare(strict_types=1);

namespace App\Newsletter\Exception;

class MauticApiException extends NewsletterException
{
    public function __construct(
        string $message,
        private readonly int $statusCode,
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

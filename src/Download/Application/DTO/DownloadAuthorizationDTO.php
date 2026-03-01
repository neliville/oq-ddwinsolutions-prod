<?php

declare(strict_types=1);

namespace App\Download\Application\DTO;

final class DownloadAuthorizationDTO
{
    public function __construct(
        public readonly string $downloadRequestId,
        public readonly bool $authorized,
    ) {
    }

    public static function fromRequest(array $data): self
    {
        $id = trim((string) ($data['download_request_id'] ?? ''));
        $authorized = filter_var($data['authorized'] ?? false, \FILTER_VALIDATE_BOOLEAN);

        return new self($id, $authorized);
    }
}

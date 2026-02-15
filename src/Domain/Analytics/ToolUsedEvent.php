<?php

namespace App\Domain\Analytics;

/**
 * Event métier : Un outil a été utilisé
 */
class ToolUsedEvent
{
    public function __construct(
        public readonly string $tool,
        public readonly ?string $sessionId = null,
        public readonly ?string $ipAddress = null,
        public readonly ?string $userAgent = null,
        public readonly ?int $userId = null,
    ) {
    }
}


<?php

namespace App\Application\Lead;

/**
 * Request DTO pour créer un lead
 */
class CreateLeadRequest
{
    public function __construct(
        public ?string $email = null,
        public ?string $name = null,
        public ?string $source = null,
        public ?string $tool = null,
        public ?string $utmSource = null,
        public ?string $utmMedium = null,
        public ?string $utmCampaign = null,
        public ?string $ipAddress = null,
        public ?string $userAgent = null,
        public ?string $sessionId = null,
        public ?bool $gdprConsent = null,
    ) {
    }
}


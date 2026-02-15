<?php

namespace App\Domain\Analytics;

/**
 * Event métier : Un lead a été converti
 */
class LeadConvertedEvent
{
    public function __construct(
        public readonly int $leadId,
        public readonly ?string $email = null,
        public readonly ?string $source = null,
    ) {
    }
}


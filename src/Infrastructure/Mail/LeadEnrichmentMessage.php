<?php

namespace App\Infrastructure\Mail;

/**
 * Message Messenger pour enrichir un lead (données complémentaires) (async).
 */
class LeadEnrichmentMessage
{
    public function __construct(
        public readonly int $leadId,
    ) {
    }
}

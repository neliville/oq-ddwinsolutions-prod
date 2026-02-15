<?php

namespace App\Infrastructure\Mail;

/**
 * Message Messenger pour calculer le score d'un lead (async).
 */
class LeadScoringMessage
{
    public function __construct(
        public readonly int $leadId,
    ) {
    }
}

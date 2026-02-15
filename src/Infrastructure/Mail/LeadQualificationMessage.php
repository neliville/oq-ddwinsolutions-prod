<?php

namespace App\Infrastructure\Mail;

/**
 * Message Messenger pour qualifier un lead (B2B/B2C) (async).
 */
class LeadQualificationMessage
{
    public function __construct(
        public readonly int $leadId,
    ) {
    }
}

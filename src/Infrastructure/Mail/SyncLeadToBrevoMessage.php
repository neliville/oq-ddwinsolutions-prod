<?php

namespace App\Infrastructure\Mail;

/**
 * Message Messenger pour synchroniser un lead vers Brevo (async).
 */
class SyncLeadToBrevoMessage
{
    public function __construct(
        public readonly int $leadId,
    ) {
    }
}

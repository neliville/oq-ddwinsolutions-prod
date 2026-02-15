<?php

namespace App\Infrastructure\Mail;

/**
 * Message Messenger pour notifier la création d'un lead
 */
class LeadCreatedMessage
{
    public function __construct(
        public readonly int $leadId,
        public readonly ?string $email = null,
        public readonly ?string $source = null,
        public readonly ?string $tool = null,
    ) {
    }
}


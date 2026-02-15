<?php

namespace App\Application\Lead;

/**
 * Response DTO pour la création d'un lead
 */
class CreateLeadResponse
{
    public function __construct(
        public ?int $id = null,
        public ?string $email = null,
        public ?int $score = null,
        public ?string $type = null,
    ) {
    }
}


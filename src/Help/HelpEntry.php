<?php

declare(strict_types=1);

namespace App\Help;

/**
 * Contenu d’aide contextuelle (Hover Card / info-bulle enrichie).
 */
final readonly class HelpEntry
{
    public function __construct(
        public string $id,
        public string $title,
        public string $description,
        public ?string $caption = null,
        public ?string $example = null,
        public ?string $badge = null,
        public ?string $learnMoreUrl = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(string $id, array $data): self
    {
        return new self(
            id: $id,
            title: (string) ($data['title'] ?? ''),
            description: (string) ($data['description'] ?? ''),
            caption: isset($data['caption']) ? (string) $data['caption'] : null,
            example: isset($data['example']) ? (string) $data['example'] : null,
            badge: isset($data['badge']) ? (string) $data['badge'] : null,
            learnMoreUrl: isset($data['learn_more_url']) ? (string) $data['learn_more_url'] : null,
        );
    }
}

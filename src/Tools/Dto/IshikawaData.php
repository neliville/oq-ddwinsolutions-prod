<?php

namespace App\Tools\Dto;

use App\Tools\ToolAnalysisInterface;

final readonly class IshikawaData implements ToolAnalysisInterface
{
    public function __construct(
        public ?string $title = null,
        public ?string $problem = null,
        public ?string $data = null,
    ) {
    }

    public function getToolName(): string
    {
        return 'ishikawa';
    }

    public function getPayload(): array
    {
        return array_filter([
            'title' => $this->title,
            'problem' => $this->problem,
            'data' => $this->data,
        ], fn ($v) => null !== $v);
    }
}

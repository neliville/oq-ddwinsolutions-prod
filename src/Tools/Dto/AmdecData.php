<?php

namespace App\Tools\Dto;

use App\Tools\ToolAnalysisInterface;

final readonly class AmdecData implements ToolAnalysisInterface
{
    public function __construct(
        public ?string $title = null,
        public ?string $data = null,
    ) {
    }

    public function getToolName(): string
    {
        return 'amdec';
    }

    public function getPayload(): array
    {
        return array_filter([
            'title' => $this->title,
            'data' => $this->data,
        ], fn ($v) => null !== $v);
    }
}

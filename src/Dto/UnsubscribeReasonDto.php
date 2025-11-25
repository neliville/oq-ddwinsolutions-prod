<?php

namespace App\Dto;

class UnsubscribeReasonDto
{
    private array $reasons = [];
    private ?string $comment = null;

    public function getReasons(): array
    {
        return $this->reasons;
    }

    public function setReasons(array $reasons): self
    {
        $this->reasons = $reasons;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }
}


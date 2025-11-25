<?php

namespace App\Twig;

use App\Service\VersionService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class VersionExtension extends AbstractExtension
{
    public function __construct(
        private readonly VersionService $versionService
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('app_version', [$this, 'getVersion']),
            new TwigFunction('app_commit_hash', [$this, 'getCommitHash']),
            new TwigFunction('app_commit_date', [$this, 'getCommitDate']),
            new TwigFunction('app_commit_message', [$this, 'getCommitMessage']),
            new TwigFunction('app_short_hash', [$this, 'getShortHash']),
        ];
    }

    public function getVersion(): string
    {
        return $this->versionService->getVersion();
    }

    public function getCommitHash(): ?string
    {
        return $this->versionService->getCommitHash();
    }

    public function getCommitDate(): ?\DateTime
    {
        $dateString = $this->versionService->getCommitDate();
        if (!$dateString) {
            return null;
        }
        
        try {
            return new \DateTime($dateString);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getCommitMessage(): ?string
    {
        return $this->versionService->getCommitMessage();
    }

    public function getShortHash(): ?string
    {
        return $this->versionService->getShortHash();
    }
}


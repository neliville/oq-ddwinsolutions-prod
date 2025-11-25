<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

class VersionService
{
    private ?string $version = null;
    private ?string $commitHash = null;
    private ?string $commitDate = null;
    private ?string $commitMessage = null;

    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir
    ) {
    }

    public function getVersion(): string
    {
        if ($this->version === null) {
            $this->loadVersion();
        }

        return $this->version;
    }

    public function getCommitHash(): ?string
    {
        if ($this->commitHash === null) {
            $this->loadVersion();
        }

        return $this->commitHash;
    }

    public function getCommitDate(): ?string
    {
        if ($this->commitDate === null) {
            $this->loadVersion();
        }

        return $this->commitDate;
    }

    public function getCommitMessage(): ?string
    {
        if ($this->commitMessage === null) {
            $this->loadVersion();
        }

        return $this->commitMessage;
    }

    public function getShortHash(): ?string
    {
        $hash = $this->getCommitHash();
        return $hash ? substr($hash, 0, 7) : null;
    }

    private function loadVersion(): void
    {
        $gitDir = $this->projectDir . '/.git';
        
        // Vérifier si on est dans un dépôt Git
        if (!is_dir($gitDir)) {
            $this->version = 'dev';
            return;
        }

        // Récupérer le hash du commit HEAD
        $hash = $this->execGitCommand('rev-parse HEAD');
        $this->commitHash = $hash ?: null;

        // Récupérer la date du commit (format ISO 8601)
        $date = $this->execGitCommand('log -1 --format=%ci HEAD');
        if ($date) {
            // Convertir le format Git (2025-11-25 20:25:22 +0100) en DateTime
            try {
                $dateTime = new \DateTime($date);
                $this->commitDate = $dateTime->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
                $this->commitDate = null;
            }
        } else {
            $this->commitDate = null;
        }

        // Récupérer le message du commit
        $message = $this->execGitCommand('log -1 --format=%s HEAD');
        $this->commitMessage = $message ?: null;

        // Version = hash court
        $this->version = $this->getShortHash() ?: 'dev';
    }

    private function execGitCommand(string $command): ?string
    {
        $command = 'git ' . $command . ' 2>/dev/null';
        $output = [];
        $returnVar = 0;
        
        exec($command, $output, $returnVar);
        
        if ($returnVar !== 0 || empty($output)) {
            return null;
        }

        return trim(implode("\n", $output));
    }
}


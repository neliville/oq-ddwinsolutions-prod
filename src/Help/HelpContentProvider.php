<?php

declare(strict_types=1);

namespace App\Help;

use Symfony\Component\Yaml\Yaml;

/**
 * Registre central des textes d’aide contextuelle (clés métier → HelpEntry).
 */
final class HelpContentProvider
{
    /** @var array<string, HelpEntry>|null */
    private ?array $entries = null;

    public function __construct(
        private readonly string $configPath,
    ) {
    }

    public function has(string $id): bool
    {
        return isset($this->all()[$id]);
    }

    public function get(string $id): HelpEntry
    {
        return $this->all()[$id] ?? throw HelpContentNotFoundException::forId($id);
    }

    /**
     * @return array<string, HelpEntry>
     */
    public function all(): array
    {
        if ($this->entries !== null) {
            return $this->entries;
        }

        if (!is_readable($this->configPath)) {
            throw new \RuntimeException(sprintf('Fichier d’aide introuvable : %s', $this->configPath));
        }

        /** @var array<string, array<string, mixed>> $raw */
        $raw = Yaml::parseFile($this->configPath);
        $entries = [];
        foreach ($raw as $id => $data) {
            if (!\is_array($data)) {
                continue;
            }
            $entries[$id] = HelpEntry::fromArray($id, $data);
        }

        return $this->entries = $entries;
    }

    /**
     * @return list<string>
     */
    public function ids(): array
    {
        return array_keys($this->all());
    }
}

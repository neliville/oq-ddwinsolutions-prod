<?php

declare(strict_types=1);

namespace App\Tests\Unit\Help;

use App\Help\HelpContentNotFoundException;
use App\Help\HelpContentProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

final class HelpContentProviderTest extends TestCase
{
    private HelpContentProvider $provider;

    protected function setUp(): void
    {
        $path = dirname(__DIR__, 3).'/config/help/contextual_help.yaml';
        $this->provider = new HelpContentProvider($path);
    }

    public function testLoadsNavigationKeys(): void
    {
        self::assertTrue($this->provider->has('help.nav.audit'));
        $entry = $this->provider->get('help.nav.audit');
        self::assertSame('Audits ISO', $entry->title);
        self::assertStringContainsString('conformité', $entry->description);
        self::assertSame('Pilotage QSE', $entry->badge);
    }

    public function testVerdictKeysExist(): void
    {
        foreach (['not_evaluated', 'conform', 'observation', 'minor_nc', 'major_nc', 'to_review', 'not_applicable'] as $suffix) {
            self::assertTrue($this->provider->has('help.audit.verdict.'.$suffix), $suffix);
        }
    }

    public function testMissingKeyThrows(): void
    {
        $this->expectException(HelpContentNotFoundException::class);
        $this->provider->get('help.does.not.exist');
    }

    /**
     * Garde-fou : toutes les clés help_id référencées dans les templates existent dans le YAML.
     */
    public function testReferencedHelpIdsExistInRegistry(): void
    {
        $root = dirname(__DIR__, 3);
        $yamlKeys = array_keys(Yaml::parseFile($root.'/config/help/contextual_help.yaml'));
        $referenced = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root.'/templates', \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if (!$file->isFile() || !str_ends_with($file->getFilename(), '.twig')) {
                continue;
            }
            $content = file_get_contents($file->getPathname());
            if ($content === false) {
                continue;
            }
            if (preg_match_all("/help_id:\s*'([^']+)'/", $content, $m)) {
                foreach ($m[1] as $id) {
                    $referenced[$id] = true;
                }
            }
            if (preg_match_all('/helpId="([^"]+)"/', $content, $m2)) {
                foreach ($m2[1] as $id) {
                    $referenced[$id] = true;
                }
            }
            if (preg_match_all("/help_description\('([^']+)'\)/", $content, $m3)) {
                foreach ($m3[1] as $id) {
                    $referenced[$id] = true;
                }
            }
        }

        $missing = [];
        foreach (array_keys($referenced) as $id) {
            if (!str_starts_with($id, 'help.') || str_contains($id, '{{')) {
                continue;
            }
            if (!\in_array($id, $yamlKeys, true)) {
                $missing[] = $id;
            }
        }

        self::assertSame([], $missing, 'Clés help référencées mais absentes du YAML : '.implode(', ', $missing));
    }
}

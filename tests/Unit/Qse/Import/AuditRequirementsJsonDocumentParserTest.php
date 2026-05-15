<?php

declare(strict_types=1);

namespace App\Tests\Unit\Qse\Import;

use App\Qse\Import\AuditRequirementsJsonDocumentParser;
use PHPUnit\Framework\TestCase;

final class AuditRequirementsJsonDocumentParserTest extends TestCase
{
    private AuditRequirementsJsonDocumentParser $parser;

    protected function setUp(): void
    {
        $this->parser = new AuditRequirementsJsonDocumentParser();
    }

    public function testParseDdwinExigencesMapsOnglet14001ToIso14001(): void
    {
        $parsed = $this->parser->parse([
            'norme' => 'ISO 14001',
            'onglet' => '14001',
            'exigences' => [
                [
                    'article' => '4',
                    'paragraphe' => '4.1',
                    'exigence' => 'Test exigence env',
                    'commentaire' => 'Note env',
                ],
            ],
        ]);

        self::assertSame('iso_14001', $parsed->standardCode);
        self::assertCount(1, $parsed->rows);
        self::assertSame('json_iso_14001_0001', $parsed->rows[0]['legacy_key']);
        self::assertSame('4. Contexte de l\'organisation', $parsed->rows[0]['chapter']);
        self::assertSame('4.1', $parsed->rows[0]['article']);
        self::assertSame('Test exigence env', $parsed->rows[0]['requirement_text']);
        self::assertSame('Note env', $parsed->rows[0]['iso_comment']);
    }

    public function testParseDdwinExigencesMapsOnglet45001ToIso45001(): void
    {
        $parsed = $this->parser->parse([
            'onglet' => '45001',
            'exigences' => [
                ['article' => '5', 'paragraphe' => '5.1', 'exigence' => 'SST test', 'commentaire' => ''],
            ],
        ]);

        self::assertSame('iso_45001', $parsed->standardCode);
        self::assertSame('json_iso_45001_0001', $parsed->rows[0]['legacy_key']);
    }

    public function testParseIso9001ChaptersDocument(): void
    {
        $parsed = $this->parser->parse([
            '4. Contexte' => [
                ['id' => 'exig_1', 'article' => '4.1', 'exigence' => 'Enjeux', 'commentaire' => 'c', 'numero' => 1],
            ],
        ]);

        self::assertSame('iso_9001', $parsed->standardCode);
        self::assertSame('exig_1', $parsed->rows[0]['legacy_key']);
        self::assertSame('4. Contexte', $parsed->rows[0]['chapter']);
    }

    public function testContinuationRowWithoutParagraphKeepsPriorChapterAndArticle(): void
    {
        $parsed = $this->parser->parse([
            'onglet' => '14001',
            'exigences' => [
                [
                    'article' => '5',
                    'paragraphe' => '5.2',
                    'exigence' => 'Politique environnementale',
                    'commentaire' => '',
                ],
                [
                    'article' => '',
                    'paragraphe' => '',
                    'exigence' => 'La suite normative sans numéro de paragraphe',
                    'commentaire' => '',
                ],
            ],
        ]);

        self::assertCount(2, $parsed->rows);
        self::assertSame('5. Direction', $parsed->rows[0]['chapter']);
        self::assertSame('5. Direction', $parsed->rows[1]['chapter']);
        self::assertSame('5.2', $parsed->rows[1]['article']);
    }

    public function testRejectsMismatchedStandardAndOnglet(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->parser->parse([
            'onglet' => '14001',
            'exigences' => [['article' => '4', 'paragraphe' => '4', 'exigence' => 'x', 'commentaire' => '']],
        ], 'iso_45001');
    }
}

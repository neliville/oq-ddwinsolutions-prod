<?php

namespace App\Twig;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\Extension\Attributes\AttributesExtension;
use League\CommonMark\MarkdownConverter;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkRenderer;
use League\CommonMark\Normalizer\TextNormalizerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class MarkdownExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('markdown_to_html', [$this, 'markdownToHtml'], ['is_safe' => ['html']]),
            new TwigFilter('extract_headings', [$this, 'extractHeadings']),
        ];
    }

    /**
     * Extrait les titres d'un contenu Markdown pour prévisualisation
     */
    public function extractHeadings(string $content): array
    {
        $headings = [];
        $lines = explode("\n", $content);
        
        foreach ($lines as $line) {
            if (preg_match('/^(#{1,6})\s+(.+)$/', $line, $matches)) {
                $level = strlen($matches[1]);
                $title = trim($matches[2]);
                $slug = $this->slugify($title);
                
                $headings[] = [
                    'level' => $level,
                    'title' => $title,
                    'slug' => $slug,
                ];
            }
        }
        
        return $headings;
    }

    public function markdownToHtml(?string $content): string
    {
        if (empty($content)) {
            return '';
        }

        // Décoder les séquences Unicode échappées si présentes (format \u00e9)
        if (str_contains($content, '\\u')) {
            // Essayer de décoder les séquences Unicode échappées
            $content = preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function ($match) {
                return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UCS-2BE');
            }, $content);
        }

        // Remplacer le placeholder [TOC] par une table des matières générée automatiquement
        if (str_contains($content, '[TOC]') || str_contains($content, '[[TOC]]')) {
            $toc = $this->generateTableOfContents($content);
            $content = str_replace(['[TOC]', '[[TOC]]'], $toc, $content);
        }

        $config = [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
            // Configuration pour les ancres automatiques
            'heading_permalink' => [
                'html_class' => 'heading-permalink',
                'id_prefix' => '',
                'fragment_prefix' => '',
                'insert' => 'before',
                'title' => 'Lien permanent',
                'symbol' => '#',
                'aria_hidden' => true,
            ],
        ];

        // Créer l'environnement avec les extensions
        $environment = new Environment($config);
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new GithubFlavoredMarkdownExtension());
        $environment->addExtension(new HeadingPermalinkExtension());
        $environment->addExtension(new AttributesExtension());

        $converter = new MarkdownConverter($environment);
        $html = $converter->convert($content)->getContent();

        // Post-traitement : normaliser les IDs des titres pour supprimer les accents et caractères spéciaux
        $html = preg_replace_callback(
            '/<h([1-6])><a id="([^"]+)" href="#([^"]+)"/',
            function ($matches) {
                $level = $matches[1];
                $oldId = $matches[2];
                $newId = $this->slugify($oldId);
                
                return '<h' . $level . '><a id="' . $newId . '" href="#' . $newId . '"';
            },
            $html
        );

        return $html;
    }

    /**
     * Génère automatiquement une table des matières à partir des titres du document
     */
    private function generateTableOfContents(string $content): string
    {
        $toc = [];
        $lines = explode("\n", $content);
        
        foreach ($lines as $line) {
            // Détecter les titres Markdown (## Titre, ### Titre, etc.)
            if (preg_match('/^(#{2,6})\s+(.+)$/', $line, $matches)) {
                $level = strlen($matches[1]); // Nombre de #
                $title = trim($matches[2]);
                // Enlever les emojis et icônes du titre
                $title = preg_replace('/[\x{1F300}-\x{1F9FF}]|📑|🎯|💡|⚠️|✅|❌/u', '', $title);
                $title = trim($title);
                $slug = $this->slugify($title);
                
                // Ne pas utiliser d'indentation pour éviter la détection comme code
                // Les listes plates sont mieux rendues
                $toc[] = '- [' . $title . '](#' . $slug . ')';
            }
            // Détecter également les titres HTML (<h2 id="...">Titre</h2>)
            elseif (preg_match('/<h([2-6])(?: id="([^"]+)")?[^>]*>(.+?)<\/h\1>/', $line, $matches)) {
                $level = (int)$matches[1];
                $existingId = $matches[2] ?? ''; // ID existant si présent
                $title = strip_tags($matches[3]); // Titre sans balises HTML
                $title = html_entity_decode($title, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                // Enlever les emojis et icônes du titre
                $title = preg_replace('/[\x{1F300}-\x{1F9FF}]|📑|🎯|💡|⚠️|✅|❌/u', '', $title);
                $title = trim($title);
                
                // Utiliser l'ID existant ou générer un slug
                $slug = !empty($existingId) ? $existingId : $this->slugify($title);
                
                $toc[] = '- [' . $title . '](#' . $slug . ')';
            }
        }
        
        if (empty($toc)) {
            return '';
        }
        
        return "## 📑 Sommaire\n\n" . implode("\n", $toc) . "\n\n---\n";
    }

    /**
     * Convertit un texte en slug (sans accents, minuscules, tirets)
     */
    private function slugify(string $text): string
    {
        // Convertir en minuscules
        $slug = mb_strtolower($text, 'UTF-8');
        
        // Remplacer les caractères accentués
        $unwanted = [
            'á'=>'a', 'à'=>'a', 'â'=>'a', 'ä'=>'a', 'ã'=>'a', 'å'=>'a',
            'é'=>'e', 'è'=>'e', 'ê'=>'e', 'ë'=>'e',
            'í'=>'i', 'ì'=>'i', 'î'=>'i', 'ï'=>'i',
            'ó'=>'o', 'ò'=>'o', 'ô'=>'o', 'ö'=>'o', 'õ'=>'o',
            'ú'=>'u', 'ù'=>'u', 'û'=>'u', 'ü'=>'u',
            'ý'=>'y', 'ÿ'=>'y',
            'ñ'=>'n', 'ç'=>'c',
            'œ'=>'oe', 'æ'=>'ae',
        ];
        $slug = strtr($slug, $unwanted);
        
        // Remplacer les espaces et caractères spéciaux par des tirets
        $slug = preg_replace('/[^a-z0-9]+/i', '-', $slug);
        
        // Supprimer les tirets multiples
        $slug = preg_replace('/-+/', '-', $slug);
        
        // Supprimer les tirets au début et à la fin
        $slug = trim($slug, '-');
        
        return $slug;
    }
}

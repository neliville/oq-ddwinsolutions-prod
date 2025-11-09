<?php

namespace App\Twig;

use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class MarkdownExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('markdown_to_html', [$this, 'markdownToHtml'], ['is_safe' => ['html']]),
        ];
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

        $config = [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ];

        $converter = new CommonMarkConverter($config);
        $converter->getEnvironment()->addExtension(new GithubFlavoredMarkdownExtension());

        return $converter->convert($content)->getContent();
    }
}

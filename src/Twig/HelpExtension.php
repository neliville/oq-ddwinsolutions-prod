<?php

declare(strict_types=1);

namespace App\Twig;

use App\Help\HelpContentProvider;
use App\Help\HelpEntry;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class HelpExtension extends AbstractExtension
{
    public function __construct(
        private readonly HelpContentProvider $helpContent,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('help', $this->getHelp(...)),
            new TwigFunction('help_exists', $this->helpExists(...)),
            new TwigFunction('help_title', $this->helpTitle(...)),
            new TwigFunction('help_description', $this->helpDescription(...)),
        ];
    }

    public function getHelp(string $id): HelpEntry
    {
        return $this->helpContent->get($id);
    }

    public function helpExists(string $id): bool
    {
        return $this->helpContent->has($id);
    }

    public function helpTitle(string $id): string
    {
        return $this->helpContent->get($id)->title;
    }

    public function helpDescription(string $id): string
    {
        return $this->helpContent->get($id)->description;
    }
}

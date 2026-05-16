<?php

declare(strict_types=1);

namespace App\Help;

final class HelpContentNotFoundException extends \InvalidArgumentException
{
    public static function forId(string $id): self
    {
        return new self(sprintf('Aucun contenu d’aide pour la clé « %s ». Ajoutez-la dans config/help/contextual_help.yaml.', $id));
    }
}

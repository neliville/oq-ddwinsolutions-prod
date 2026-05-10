<?php

declare(strict_types=1);

namespace App\Collaboration;

enum InvitationType: string
{
    case GENERALE = 'generale';
    case CONTEXTUELLE = 'contextuelle';
}

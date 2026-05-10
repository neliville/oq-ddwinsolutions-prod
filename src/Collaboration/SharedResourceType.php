<?php

declare(strict_types=1);

namespace App\Collaboration;

enum SharedResourceType: string
{
    case AUDIT = 'audit';
    case CAPA = 'capa';
}

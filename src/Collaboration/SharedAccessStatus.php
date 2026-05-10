<?php

declare(strict_types=1);

namespace App\Collaboration;

enum SharedAccessStatus: string
{
    case ACTIF = 'actif';
    case REVOQUE = 'revoque';
    case EXPIRE = 'expire';
}

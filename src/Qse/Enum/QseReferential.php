<?php

declare(strict_types=1);

namespace App\Qse\Enum;

enum QseReferential: string
{
    case ISO9001 = 'iso_9001';
    case ISO14001 = 'iso_14001';
    case ISO45001 = 'iso_45001';
    case INTERNAL = 'internal';
}

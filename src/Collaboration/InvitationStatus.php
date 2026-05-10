<?php

declare(strict_types=1);

namespace App\Collaboration;

enum InvitationStatus: string
{
    case ENVOYEE = 'envoyee';
    case ACCEPTEE = 'acceptee';
    case EXPIREE = 'expiree';
    case ANNULEE = 'annulee';
}

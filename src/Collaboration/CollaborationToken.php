<?php

declare(strict_types=1);

namespace App\Collaboration;

/**
 * Jeton opaque pour invitations et partages : stockage SHA-256, comparaison timing-safe.
 */
final class CollaborationToken
{
    public static function generatePlain(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }

    public static function hashPlain(string $plainToken): string
    {
        return hash('sha256', $plainToken);
    }

    public static function equals(string $plainToken, string $storedHash): bool
    {
        return hash_equals($storedHash, self::hashPlain($plainToken));
    }
}

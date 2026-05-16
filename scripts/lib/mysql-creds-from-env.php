#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Charge .env via Symfony Dotenv (gère guillemets, CRLF, caractères spéciaux)
 * et écrit un fichier [client] pour mysqldump/mysql.
 * Affiche le nom de la base sur stdout.
 */

$projectDir = dirname(__DIR__, 2);
if (!is_file($projectDir . '/composer.json')) {
    fwrite(STDERR, "Racine projet introuvable: {$projectDir}\n");
    exit(1);
}

require $projectDir . '/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->bootEnv($projectDir . '/.env');

function iniEscape(string $value): string
{
    if ($value === '' || preg_match('/[\s#=;\'"\[\]]/', $value)) {
        return '"' . addcslashes($value, "\\\"\n\r") . '"';
    }

    return $value;
}

/**
 * @return array{host: string, port: string, user: string, pass: string, name: string}
 */
function resolveDbConfig(): array
{
    $url = $_ENV['DATABASE_URL'] ?? $_SERVER['DATABASE_URL'] ?? null;
    if (is_string($url) && str_starts_with($url, 'mysql')) {
        $parsed = parse_url($url);
        if (is_array($parsed) && isset($parsed['path'])) {
            return [
                'host' => $parsed['host'] ?? '127.0.0.1',
                'port' => (string) ($parsed['port'] ?? 3306),
                'user' => isset($parsed['user']) ? rawurldecode((string) $parsed['user']) : '',
                'pass' => isset($parsed['pass']) ? rawurldecode((string) $parsed['pass']) : '',
                'name' => explode('?', ltrim((string) $parsed['path'], '/'))[0],
            ];
        }
    }

    return [
        'host' => (string) ($_ENV['DB_HOST'] ?? '127.0.0.1'),
        'port' => (string) ($_ENV['DB_PORT'] ?? '3306'),
        'user' => (string) ($_ENV['DB_USER'] ?? ''),
        'pass' => (string) ($_ENV['DB_PASSWORD'] ?? ''),
        'name' => (string) ($_ENV['DB_NAME'] ?? ''),
    ];
}

$cnfPath = $argv[1] ?? '';
if ($cnfPath === '') {
    fwrite(STDERR, "Usage: php scripts/lib/mysql-creds-from-env.php <fichier.cnf>\n");
    exit(1);
}

$db = resolveDbConfig();
if ($db['user'] === '' || $db['name'] === '') {
    fwrite(STDERR, "Identifiants DB incomplets (DB_USER / DB_NAME ou DATABASE_URL).\n");
    exit(1);
}

$lines = [
    '[client]',
    'host=' . iniEscape($db['host']),
    'port=' . iniEscape($db['port']),
    'user=' . iniEscape($db['user']),
    'password=' . iniEscape($db['pass']),
];

file_put_contents($cnfPath, implode("\n", $lines) . "\n");
chmod($cnfPath, 0600);

echo $db['name'];

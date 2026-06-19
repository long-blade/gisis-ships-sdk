<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Mavroforakis\Gisis\Auth\CookieSessionProvider;
use Mavroforakis\Gisis\Exception\AuthenticationException;
use Mavroforakis\Gisis\GisisShips;

/**
 * Usage:  php examples/lookup.php 9074729
 *         php examples/lookup.php --name "EVER GIVEN"
 *
 * Reads the session cookie from .env (preferred) or .env.example.
 */
$env = loadEnv([__DIR__ . '/../.env', __DIR__ . '/../.env.example']);

$session = new CookieSessionProvider(array_filter([
    'IMOWEBACC'         => $env['GISIS_IMOWEBACC'] ?? '',
    'ARRAffinity'       => $env['GISIS_ARRAFFINITY'] ?? '',
    'ASP.NET_SessionId' => $env['GISIS_ASPNET_SESSIONID'] ?? '',
]));

$gisis = new GisisShips($session);

try {
    if (($argv[1] ?? '') === '--name') {
        $ships = $gisis->findByName($argv[2] ?? '');
        echo json_encode(array_map(fn ($s) => $s->toArray(), $ships), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), PHP_EOL;
    } else {
        $ship = $gisis->findByImo($argv[1] ?? '9074729');
        echo $ship
            ? json_encode($ship->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL
            : "No ship found.\n";
    }
} catch (AuthenticationException $e) {
    fwrite(STDERR, "Auth problem: {$e->getMessage()}\n");
    exit(1);
}

/** @param list<string> $paths @return array<string,string> */
function loadEnv(array $paths): array
{
    foreach ($paths as $path) {
        if (!is_file($path)) {
            continue;
        }
        $env = [];
        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
                continue;
            }
            [$k, $v] = explode('=', $line, 2);
            $env[trim($k)] = trim($v);
        }
        if (isset($env['GISIS_IMOWEBACC']) && $env['GISIS_IMOWEBACC'] !== '') {
            return $env;
        }
    }

    return [];
}

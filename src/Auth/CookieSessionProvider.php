<?php

declare(strict_types=1);

namespace Mavroforakis\Gisis\Auth;

use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\CookieJarInterface;
use GuzzleHttp\Cookie\SetCookie;
use Mavroforakis\Gisis\Exception\AuthenticationException;

/**
 * Builds a session from cookies you copied out of a logged-in browser.
 *
 * The only strictly required cookie is `IMOWEBACC` (the GISIS auth token).
 * You may also pass the `ARRAffinity` / `ASP.NET_SessionId` cookies; they help
 * stick to the same backend node but are refreshed automatically otherwise.
 */
final class CookieSessionProvider implements SessionProvider
{
    private const DOMAIN = 'gisis.imo.org';

    private CookieJar $jar;

    /**
     * @param array<string,string> $cookies name => value pairs
     */
    public function __construct(array $cookies)
    {
        if (empty($cookies['IMOWEBACC'])) {
            throw new AuthenticationException(
                'Missing required "IMOWEBACC" cookie. Log in to GISIS in a browser and copy it out.'
            );
        }

        $this->jar = new CookieJar();
        foreach ($cookies as $name => $value) {
            $this->jar->setCookie(new SetCookie([
                'Name'     => $name,
                'Value'    => $value,
                'Domain'   => self::DOMAIN,
                'Path'     => '/',
                'Secure'   => true,
                'HttpOnly' => true,
            ]));
        }
    }

    /** Convenience: build from a single raw cookie header copied from DevTools. */
    public static function fromCookieHeader(string $header): self
    {
        $cookies = [];
        foreach (explode(';', $header) as $pair) {
            $pair = trim($pair);
            if ($pair === '' || !str_contains($pair, '=')) {
                continue;
            }
            [$name, $value] = explode('=', $pair, 2);
            $cookies[trim($name)] = trim($value);
        }

        return new self($cookies);
    }

    public function cookieJar(): CookieJarInterface
    {
        return $this->jar;
    }
}

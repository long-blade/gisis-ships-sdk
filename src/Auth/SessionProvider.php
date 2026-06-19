<?php

declare(strict_types=1);

namespace Mavroforakis\Gisis\Auth;

use GuzzleHttp\Cookie\CookieJarInterface;

/**
 * Supplies an authenticated GISIS session.
 *
 * IMPORTANT: GISIS login is protected by Cloudflare Turnstile and a multi-step
 * username/password flow. This SDK deliberately does NOT automate or bypass
 * that challenge. Instead a human logs in via a real browser and the resulting
 * session (the `IMOWEBACC` cookie, plus the `ARRAffinity`/`ASP.NET_SessionId`
 * cookies) is handed to the SDK through an implementation of this interface.
 */
interface SessionProvider
{
    /** A Guzzle cookie jar primed with a valid, human-established GISIS session. */
    public function cookieJar(): CookieJarInterface;
}

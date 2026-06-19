<?php

declare(strict_types=1);

namespace Mavroforakis\Gisis\Exception;

/**
 * Thrown when the supplied session is missing, malformed, or has expired and
 * GISIS redirected us back to the login / Turnstile page.
 */
final class AuthenticationException extends GisisException
{
}

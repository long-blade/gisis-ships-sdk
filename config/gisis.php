<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | GISIS session cookie
    |--------------------------------------------------------------------------
    |
    | GISIS login is protected by Cloudflare Turnstile, so this SDK reuses a
    | session you establish in a real browser rather than logging in itself.
    | Paste the IMOWEBACC cookie (and optionally the affinity/session cookies)
    | from your logged-in browser. These expire — refresh them when searches
    | start throwing AuthenticationException.
    |
    */

    'imowebacc'   => env('GISIS_IMOWEBACC'),

    'arraffinity' => env('GISIS_ARRAFFINITY'),

    'session_id'  => env('GISIS_ASPNET_SESSIONID'),

];

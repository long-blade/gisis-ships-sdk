# gisis-ships

A PHP SDK for querying the **IMO GISIS public Ships database**
([gisis.imo.org](https://gisis.imo.org/Public/SHIPS/Default.aspx)) — look up
vessels by **IMO number**, **ship name**, **call sign** or **MMSI** and get back
clean, typed `Ship` objects.

It also ships a fully offline **IMO check-digit validator**, so you can validate
a number's format without any network call.

---

## Table of contents

- [How authentication works (read this first)](#how-authentication-works-read-this-first)
- [Requirements](#requirements)
- [Installation](#installation)
- [Getting your session cookie](#getting-your-session-cookie)
- [Quick start](#quick-start)
- [Laravel](#laravel)
- [Searching](#searching)
- [The `Ship` model](#the-ship-model)
- [Offline IMO validation](#offline-imo-validation)
- [Error handling](#error-handling)
- [How it works under the hood](#how-it-works-under-the-hood)
- [Development](#development)
- [Legal & fair use](#legal--fair-use)
- [Roadmap](#roadmap)
- [Versioning](#versioning)
- [License](#license)

---

## How authentication works (read this first)

GISIS sign-in goes through `webaccounts.imo.org`, which is protected by
**Cloudflare Turnstile** plus a multi-step username/password flow. That challenge
is designed to stop headless automation, and **this SDK does not automate or
bypass it.**

Instead it uses a **human-in-the-loop session model**:

1. You log in once in a real browser (you solve Turnstile + password).
2. You copy the resulting **`IMOWEBACC`** session cookie into the SDK (via `.env`
   or directly in code).
3. The SDK reuses that authenticated session to run searches and parse results.

When the session expires, log in again and refresh the cookie. This is the only
approach that is both reliable and respectful of the site's access controls —
keep request volume modest and cache results.

---

## Requirements

- PHP **8.2+** with `ext-json`
- [Composer](https://getcomposer.org/)
- A free GISIS public account

---

## Installation

```bash
composer require long-blade/gisis-ships-sdk
```

Or clone and install dev dependencies to hack on it:

```bash
git clone git@github.com:long-blade/gisis-ships-sdk.git
cd gisis-ships-sdk
composer install
```

---

## Getting your session cookie

1. Log in at <https://gisis.imo.org/Public/SHIPS/Default.aspx> in your browser.
2. Open **DevTools → Application/Storage → Cookies → `gisis.imo.org`**.
3. Copy the value of the **`IMOWEBACC`** cookie.
4. Put it in a `.env` file (copy `.env.example`):

```dotenv
GISIS_IMOWEBACC=PASTE_THE_LONG_HEX_VALUE_HERE
# Optional — helps stick to the same backend node:
GISIS_ARRAFFINITY=
GISIS_ASPNET_SESSIONID=
```

> 🔒 `.env` is gitignored. Never commit your cookie — it is a live session token.

---

## Quick start

```php
require 'vendor/autoload.php';

use Mavroforakis\Gisis\Auth\CookieSessionProvider;
use Mavroforakis\Gisis\GisisShips;

$session = new CookieSessionProvider([
    'IMOWEBACC' => getenv('GISIS_IMOWEBACC'),
]);

$gisis = new GisisShips($session);

$ship = $gisis->findByImo('9074729');

echo $ship?->name;            // KAVITA
echo $ship?->flag;            // Palau
echo $ship?->registeredOwner; // CASSINI SHIP OWNING CO
```

You can also build the session from a raw cookie header copied out of DevTools:

```php
$session = CookieSessionProvider::fromCookieHeader(
    'ARRAffinity=...; ASP.NET_SessionId=...; IMOWEBACC=...'
);
```

### CLI demo

```bash
php examples/lookup.php 9074729
php examples/lookup.php --name "EVER GIVEN"
```

---

## Laravel

The package ships a Laravel integration that is **auto-discovered** — no manual
provider or alias registration needed.

**1. Install**

```bash
composer require long-blade/gisis-ships-sdk
```

**2. Add the session cookie to your app's `.env`**

```dotenv
GISIS_IMOWEBACC=PASTE_THE_LONG_HEX_VALUE_HERE
GISIS_ARRAFFINITY=
GISIS_ASPNET_SESSIONID=
```

These map to `config/gisis.php`. To customise the config, publish it:

```bash
php artisan vendor:publish --tag=gisis-config
```

**3. Use it** — resolve from the container (constructor injection) …

```php
use Mavroforakis\Gisis\GisisShips;

class VesselController
{
    public function show(GisisShips $gisis, string $imo)
    {
        return response()->json($gisis->findByImo($imo)?->toArray());
    }
}
```

… or via the `Gisis` facade:

```php
use Mavroforakis\Gisis\Laravel\Gisis;

$ship    = Gisis::findByImo('9074729');
$matches = Gisis::findByName('EVER GIVEN');
```

> The `GisisShips` binding is a lazy singleton: the cookie is only read when you
> first resolve it, so a missing/expired cookie surfaces as an
> `AuthenticationException` at call time (not at boot). Handle it and prompt for a
> refreshed `GISIS_IMOWEBACC`.

> ℹ️ Don't call `env()` directly in app code — these values come through
> `config('gisis.*')`, which is safe under `php artisan config:cache`.

---

## Searching

### Exact IMO lookup

`findByImo()` validates the number's check digit **locally first** (no wasted
request on malformed input), then returns a single `?Ship`:

```php
$ship = $gisis->findByImo('IMO 9074729'); // "IMO" prefix & spacing are normalised
```

### By name (partial match)

```php
/** @var list<Ship> $ships */
$ships = $gisis->findByName('EVER GIVEN');
```

### Arbitrary conditions (the where-builder)

GISIS searches are driven by typed conditions. Compose them directly:

```php
use Mavroforakis\Gisis\Search\Condition;

$ships = $gisis->search(
    Condition::nameContains('MAERSK'),
    Condition::callSignIs('OXOM2'),
);
```

Available condition helpers:

| Helper | Field | Operator |
|---|---|---|
| `Condition::imoIs($imo)` | IMO Number | `imoNumber_is` |
| `Condition::nameContains($name)` | Ship name | `name_contains` |
| `Condition::callSignIs($cs)` | Call sign | `name_is` |
| `Condition::mmsiIs($mmsi)` | MMSI | `name_is` |

Need another field/operator combination? Construct a `Condition` directly with a
`ShipField` and a valid operator string (see `ShipField` for the field/operator
map; invalid pairings throw before any request is made):

```php
use Mavroforakis\Gisis\Search\{Condition, ShipField};

$ships = $gisis->search(new Condition(ShipField::ShipName, 'name_startswith', 'EVER'));
```

---

## The `Ship` model

`Mavroforakis\Gisis\Model\Ship` is an immutable value object. Fields are nullable
because GISIS does not populate every column in every view.

```php
$ship->imoNumber;        // "9074729" (stamped on findByImo results)
$ship->name;             // "KAVITA"
$ship->flag;             // "Palau"
$ship->grossTonnage;     // "15,899"
$ship->shipType;         // "General Cargo Ship (General Cargo)"
$ship->yearOfBuild;      // "1995"
$ship->registeredOwner;  // "CASSINI SHIP OWNING CO"
$ship->extra;            // ['registeredOwnerImoCompany' => '6277131', 'detailPostbackArgument' => '_rc0']

$ship->toArray();        // everything as an array (handy for JSON)
```

---

## Offline IMO validation

No session required — pure check-digit math:

```php
use Mavroforakis\Gisis\Imo\ImoNumber;

ImoNumber::isValid('9074729');        // true
ImoNumber::isValid('9074728');        // false (bad check digit)
ImoNumber::normalize('IMO 9074729');  // "9074729"

$imo = ImoNumber::fromString('IMO 9074729'); // throws InvalidImoNumberException if invalid
(string) $imo;                               // "9074729"
```

---

## Error handling

All exceptions extend `Mavroforakis\Gisis\Exception\GisisException`:

| Exception | When |
|---|---|
| `InvalidImoNumberException` | The IMO number fails local validation. |
| `AuthenticationException` | Missing cookie, or the session expired and GISIS bounced us to the login/Turnstile page. Re-login and refresh `IMOWEBACC`. |
| `GisisException` | Network/transport errors and other failures. |

```php
use Mavroforakis\Gisis\Exception\AuthenticationException;

try {
    $ship = $gisis->findByImo('9074729');
} catch (AuthenticationException $e) {
    // prompt the user to refresh their cookie
}
```

---

## How it works under the hood

`gisis.imo.org` is an ASP.NET WebForms application, so the SDK:

1. **GETs** `ShipSearch.aspx` to read the per-session hidden state
   (`__VIEWSTATE`, `__EVENTVALIDATION`, …) — these are not reusable, so every
   search fetches fresh ones.
2. **POSTs** the search as a WebForms postback (`__EVENTTARGET` = the search
   button) with the query encoded as the where-builder's
   `conditionsXml` (a double-URL-encoded XML fragment).
3. **Parses** the results grid (`#…_gvShip`) into `Ship` objects.

The expired-session guard inspects redirects and page markers (`WebLogin.aspx`,
`turnstile`) and raises `AuthenticationException` instead of returning garbage.

---

## Development

```bash
composer install
./vendor/bin/phpunit        # run the test suite
```

Parser tests run against a saved HTML fixture (`tests/fixtures/`), so they need
no live session. The IMO validator is fully unit-tested.

---

## Legal & fair use

This SDK automates **your own authenticated browser session** against a public
registry, at human-scale volume. It is intended for legitimate, occasional
vessel verification. You are responsible for complying with IMO/GISIS terms of
use — do not bulk-scrape, redistribute datasets, or hammer the service. For
high-volume or commercial needs, use a dedicated maritime data provider.

---

## Roadmap

- [ ] Ship **detail** page (call sign, MMSI, dimensions, former names) via the
      per-row postback already captured in `extra.detailPostbackArgument`.
- [ ] Pagination for large result sets.
- [ ] Optional response caching.

---

## Versioning

This project follows [Semantic Versioning](https://semver.org/). While on the
`0.x` line, minor versions may introduce changes; pin accordingly:

```bash
composer require long-blade/gisis-ships-sdk:^0.2
```

See [CHANGELOG.md](CHANGELOG.md) for the release history. New releases are cut by
tagging:

```bash
git tag -a v0.3.0 -m "..." && git push origin v0.3.0
```

---

## License

MIT

# Changelog

All notable changes to `long-blade/gisis-ships-sdk` are documented here.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.2.0] - 2026-06-19

### Added
- Native **Laravel** support:
  - Auto-discovered `GisisServiceProvider` registering `GisisShips` as a singleton.
  - Publishable `config/gisis.php` (`php artisan vendor:publish --tag=gisis-config`).
  - `Gisis` facade (`Gisis::findByImo(...)`, `Gisis::findByName(...)`, `Gisis::search(...)`).
- `illuminate/support` listed under `suggest` (no hard dependency for non-Laravel users).

## [0.1.0] - 2026-06-19

### Added
- Ship search over an authenticated (human-established) GISIS session:
  `findByImo()`, `findByName()`, and a composable `search()` with `Condition`s
  (IMO / ship name / call sign / MMSI via the GISIS where-builder).
- Results-grid parsing into immutable `Ship` value objects.
- Offline IMO check-digit validation (`ImoNumber`).
- Cookie-based `SessionProvider` (`CookieSessionProvider`) — no Turnstile bypass.
- ASP.NET ViewState/postback plumbing and expired-session detection.

[Unreleased]: https://github.com/long-blade/gisis-ships-sdk/compare/v0.2.0...HEAD
[0.2.0]: https://github.com/long-blade/gisis-ships-sdk/compare/v0.1.0...v0.2.0
[0.1.0]: https://github.com/long-blade/gisis-ships-sdk/releases/tag/v0.1.0

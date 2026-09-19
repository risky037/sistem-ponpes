# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [Unreleased]

### Planned
- **v13.0.0 (Laravel 13 Migration):**
  - Future framework upgrade to Laravel 13.x once officially released.
- **Repository Modernization (Phase 5.8):**
  - GitHub Actions CI pipeline enhancements (concurrency, frontend build checks).
  - Modularization of database seeders.
  - Pre-production migration consolidation.
  - Decoupling of hardcoded organization & domain configurations.

---

## [v12.0.0-security-baseline] - 2026-09-19

### Added
- Official **Laravel 12.69.2** runtime baseline on **PHP 8.4.16**.
- Automated security hardening regression test suite (107 passed, 531 assertions).
- Comprehensive developer workflow scripts in `composer.json` (`dev`, `test`, `lint`, `lint:check`, `setup`).
- Repository code styling configuration (`pint.json`) adhering to Laravel preset with migration isolation (100% compliance).
- Technical documentation hub in `docs/reports/` categorizing all historical audit and migration reports.
- GitHub Actions CI matrix optimization with `sqlite, pdo_sqlite` extension integration.

### Changed
- Upgraded core framework directly from Laravel 11.56.1 to Laravel 12.69.2.
- Upgraded package ecosystem:
  - `spatie/laravel-permission: ^6.0`
  - `yajra/laravel-datatables: ^12.0`
  - `milon/barcode: ^12.0`
  - `revolution/laravel-google-sheets: ^7.0`
  - `intervention/image-laravel: ^1.5`
  - `phpunit/phpunit: ^11.5`
  - `nunomaduro/collision: ^8.6`
- Normalized git branch strategy: consolidated migration and stabilization baseline into `develop` as primary canonical branch.
- Replaced deprecated `queue:work --daemon` scheduled tasks with non-blocking `queue:work --stop-when-empty`.

### Security
- Implemented login rate limiting via `RateLimiter` in `AuthController` with lockout thresholds.
- Enforced administrative account protections (prevention of self-deletion, self-demotion, and last-admin removal).
- Protected default institutional roles (`Administrator`, `Keuangan`, `Santri`) from accidental deletion via permission config.
- Hidden `remember_token` attribute on `User` model array/JSON serialization.
- Replaced permissive directory creation masks (`0777`) with secure `0755` permissions across controllers.
- Hardened image upload validation against malicious script injection and validated master student card templates.
- Enforced strict origin matching in CORS configuration and configurable Sanctum token expirations.

### Removed
- Removed obsolete JetBrains `qodana.yaml` configuration.

---

## [1.0.0] - 2026-09-17

### Added
- Repository decoupled from upstream fork into an independent standalone repository (`risky037/sistem-ponpes`).
- Full repository governance framework (`.github/CODEOWNERS`, pull request templates, issue templates).
- Open-source lineage attribution and MIT compliance documentation (`docs/OPEN_SOURCE_NOTICE.md`).
- Developer standards and contributing workflow (`CONTRIBUTING.md`).
- Automated CI pipeline workflow (`.github/workflows/ci.yml`) and security scanning (`.github/workflows/security.yml`).
- Initial offline bundle backup (`backup-pre-history-sanitization.bundle`).

### Security
- Comprehensive git history secret sanitization using `git-filter-repo`.
- Eradicated leaked WhatsApp Gateway API tokens, sender telephone numbers, and hardcoded Google Sheets IDs across all historical commits.
- Completely removed legacy Google Cloud Service Account JSON credentials from git object history.
- Fully verified repository integrity via `git fsck --full` and multi-layer zero-match git grep verification.

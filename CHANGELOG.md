# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [Unreleased]

### Planned
- **v1.1.0 (Laravel 10 Stabilization):**
  - Resolution of fatal bugs in `UsersController` role assignment.
  - Fix column mismatches in Google Sheets synchronization queries.
  - Complete removal of production `dd()` statements across controllers.
  - Replacement of `fake()` in production controllers with deterministic generator.
  - Passing automated test suite baseline on Laravel 10.
- **v2.0.0 (Laravel 11 Migration):**
  - Framework upgrade to Laravel 11.x.
  - Modernization of `bootstrap/app.php` and removal of legacy Kernels/Handlers.
  - Upgrade of `intervention/image` to v3 with `intervention/image-laravel`.
  - Upgrade of `spatie/laravel-permission` to v6 with single-word `Middleware` namespaces.
  - Upgrade of `milon/barcode` and `yajra/laravel-datatables` to v11.
- **v3.0.0 (Laravel 12 Migration):**
  - Framework upgrade to Laravel 12.x.
- **v4.0.0 (Laravel 13 Migration):**
  - Framework upgrade to Laravel 13.x.

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

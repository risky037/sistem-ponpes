# Contributing Guidelines

Thank you for contributing to **Sistem Informasi Pondok Pesantren Fatimah Az-Zahra**! To maintain code quality, structural integrity, and security, all contributors must adhere to the standards outlined below.

---

## 1. Branch Strategy

We follow a structured GitFlow-adapted branching model:

| Branch Pattern | Base Branch | Merge Target | Purpose |
| :--- | :--- | :--- | :--- |
| `main` | - | - | Production-ready, stable releases. Protected; direct commits prohibited. |
| `develop` | `main` | `main` | Primary active development and staging integration branch. |
| `feature/*` | `develop` | `develop` | New functionality or user-facing enhancements (e.g., `feature/rapor-santri`). |
| `bugfix/*` | `develop` | `develop` | Non-emergency defect resolutions (e.g., `bugfix/pre-laravel11-stabilization`). |
| `upgrade/*` | `develop` | `develop` | Major framework or dependency version upgrades (e.g., `upgrade/laravel-11-preparation`). |
| `hotfix/*` | `main` | `main` & `develop` | Critical production-only patches. |

---

## 2. Commit Message Convention

All commits must strictly follow the **Conventional Commits** standard:

```text
<type>(<scope>): <subject>

[optional body]

[optional footer(s)]
```

### 2.1. Allowed Commit Types
- **`feat`:** A new feature or capability for the user.
- **`fix`:** A bug fix for existing functionality.
- **`refactor`:** Code changes that neither fix a bug nor add a feature.
- **`security`:** Vulnerability remediation, secret elimination, or permission hardening.
- **`docs`:** Documentation changes only (e.g., README, PHPDoc, guides).
- **`test`:** Adding, refactoring, or repairing test suites.
- **`chore`:** Maintenance tasks, dependency updates, CI/CD scripts, build configuration.
- **`upgrade`:** Major framework and architectural version migrations.

### 2.2. Examples
```text
feat(santri): add student registration module
fix(auth): prevent unauthorized access on user impersonation
security(api): protect synchronization endpoint with sanctum tokens
refactor(tabungan): optimize balance calculation using transactions
docs(readme): update setup and prerequisite instructions
test(kamar): add feature tests for dormitory capacity validation
chore(npm): update vite and build dependencies
upgrade(framework): prepare configuration files for laravel 11
```

---

## 3. Pull Request Process

1. **Fork or Create a Topic Branch:** Create your branch from the latest `develop` branch (`git checkout -b feature/your-feature-name develop`).
2. **Adhere to Code Standards:**
   - Format code according to **Laravel / PSR-12** standards using Laravel Pint:
     ```bash
     composer run lint         # Fix formatting issues
     composer run lint:check   # Dry-run check (as in CI)
     ```
   - Do not leave debugging code (`dd()`, `dump()`, `ray()`, `var_dump()`) anywhere in committed code.
3. **Verify Locally:** Ensure tests and static checks pass before pushing:
   ```bash
   composer test
   composer audit
   ```
4. **Submit Pull Request:**
   - Open your PR against the `develop` branch.
   - Complete every section of the [Pull Request Template](.github/pull_request_template.md).
   - Ensure all automated GitHub Actions CI checks pass.

---

## 4. Security & Sensitive Information

- **Zero Secret Policy:** Never commit `.env`, private keys (`.key`, `.pem`), API credentials, or database passwords.
- All secrets must be referenced via `config('service.key')` and loaded from environment variables.
- Review your diff with `git diff` before committing to prevent accidental credential leakage.

---

## 5. Database Migration Policy

- **During Active Development / Pre-Production:** Existing migrations may be consolidated and cleaned up when appropriate to minimize migration bloat.
- **Post-Production Releases:** Never alter existing migration files that have already executed on production. Always create a new, forward-only migration.

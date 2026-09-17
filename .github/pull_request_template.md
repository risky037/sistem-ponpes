## Description
<!-- Briefly describe the purpose and context of this pull request -->

## Related Issue
<!-- Link the issue resolved by this PR (e.g., Closes #12, Fixes #34) -->

## Changes Made
<!-- List high-level changes introduced in this PR -->
- 

## Database Changes
<!-- Check one and explain if applicable -->
- [ ] No database migrations required
- [ ] New migrations added (append-only)
- [ ] Seeders / Factories updated

*Details:*

## Security Impact
<!-- Check one and explain if applicable -->
- [ ] No security impact or authentication/authorization changes
- [ ] Authorization / Role gates modified
- [ ] Environment variables or credentials modified

*Details:*

## Testing Performed
<!-- Describe how these changes were verified (unit, feature, or manual tests) -->
- [ ] Automated tests passing (`php artisan test`)
- [ ] Code style validated (`vendor/bin/pint --test`)
- [ ] Manual test performed:

## Screenshots (if UI changes)
<!-- Attach screenshots or animated GIFs demonstrating UI changes -->
| Before | After |
| :--- | :--- |
| *(None)* | *(None)* |

---

## Pre-Merge Checklist
Please ensure the following items are completed before requesting review:
- [ ] Code strictly follows **PSR-12** standards and passes Laravel Pint.
- [ ] Relevant Unit / Feature tests are added or updated.
- [ ] **No secrets, private keys, or API tokens** are committed.
- [ ] **No debugging statements** (`dd()`, `dump()`, `ray()`, `console.log`) remain in production code.
- [ ] Documentation ([`README.md`](README.md), [`CHANGELOG.md`](CHANGELOG.md)) has been updated if applicable.
- [ ] Commit messages follow the **Conventional Commits** standard.

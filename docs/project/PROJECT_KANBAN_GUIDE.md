# DIGITREN — GitHub Projects Kanban Workflow Guide

**Project Board**: [GitHub Project #6 (risky037)](https://github.com/users/risky037/projects/6)  
**Repository**: `risky037/sistem-ponpes`  
**Branch Strategy**: `develop` (canonical development branch), `main` (production-ready)  

---

## 1. Kanban Column Definitions

The GitHub Project #6 board follows a structured 4-column workflow:

| Column | Purpose | Criteria for Placement |
|---|---|---|
| **TODO** | Approved Backlog & Upcoming Work | Defined architectural scope, approved requirements, unstarted phases. |
| **IN PROGRESS** | Active Implementation | Currently under active development, services/migrations being written. |
| **REVIEW** | Verification & Checkpoints | Implementation complete, undergoing automated tests, Pint, audit, or awaiting user approval. |
| **DONE** | Released & Tagged | Release notes generated, commit finalized, annotated tag pushed, tests passing. |

---

## 2. Issue Naming and Structure Conventions

All domain increments are tracked using GitHub Issues.

### Title Convention
```text
[Phase 5.8.7C-X] Domain Name
```
*Example*: `[Phase 5.8.7C-11] Academic Reporting & Analytics Foundation`

### Standard Issue Template
```markdown
## Overview
Detailed description of the domain increments and objectives.

## Release
- Tag: `phase-5.8.7C-X-completed`
- Commit: `<commit-hash>`

## Completed Components
- Migration: `database/migrations/...`
- Models: `app/Models/...`
- Services: `app/Services/...`
- Controllers: `app/Http/Controllers/...`
- UI: `resources/views/...`
- Permissions: `config/permission.php`
- Tests: `tests/Feature/...`

## Validation
- Test count: `<count>` passed tests
- Assertions: `<assertions>` assertions
- Build status: Passed (`npm run build`)
- Lint status: Passed (`composer run lint:check`)
- Security audit: Passed (`composer audit`)

## Status
Completed | In Progress | Planned
```

---

## 3. Labeling Taxonomy

### Type Labels
- `feature`: New domain capability or functionality.
- `bug`: Defect fix or logic repair.
- `refactor`: Structural improvement without behavioral changes.
- `documentation`: Reports, guides, and architectural audits.
- `architecture`: Foundational entities, schema design, and domain isolation.
- `security`: Permission checks, rate limiting, and defensive coding.
- `testing`: Unit and feature test coverage.
- `release`: Release checkpoints and tagged deliverables.

### Domain Labels
- `academic-foundation`: Academic years, student batches, enrollments.
- `academic-operation`: Schedules, academic calendar events, teaching sessions.
- `attendance`: Student session attendance records and management.
- `evaluation`: Assessment definitions, components, and scores.
- `analytics`: Performance metrics calculation and summaries.
- `reporting`: Institutional academic aggregations and cache.
- `administration`: Administrative overrides and batch operations.
- `export`: Data export engine (Excel, PDF).

### Priority Labels
- `priority-critical`: Release blocker.
- `priority-high`: Core architectural dependency.
- `priority-medium`: Standard phased task.
- `priority-low`: Nice-to-have or enhancement.

---

## 4. Release Finalization Checkpoint Protocol

Before any phase is marked **DONE** and merged/tagged:

1. **Git Hygiene**: `git status`, `git diff --stat`, and `git diff --check` must be completely clean.
2. **Boundary Audit**: Strict search ensuring no forbidden LMS concepts are present in foundation code.
3. **Database Reset**: `php artisan optimize:clear && php artisan migrate:fresh --seed` must succeed without warnings.
4. **Permission Audit**: Roles (`Administrator`, `Pengurus`, `Santri`) verified via Tinker.
5. **Route Check**: `php artisan route:list` verified for proper naming and controller bindings.
6. **Full Test Execution**: `php artisan test` must maintain 100% passing tests with zero regressions.
7. **Asset Compilation**: `npm run build` must compile cleanly without errors.
8. **Pint Code Style**: `composer run lint:check` must report 100% compliance.
9. **Composer Audit**: `composer audit` must report 0 vulnerabilities.
10. **Documentation**: Release notes published under `docs/releases/phase-5.8.7C-X-release-notes.md`.
11. **Git Commit & Tag**: Semantic commit message and annotated tag created.
12. **Push**: Both branch and tag pushed to remote.

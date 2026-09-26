# F6.1 — Repository-wide Consistency / Preflight

**Status:** READY FOR EXECUTION — final repository-wide validation gate

## Objective

Run the repository-wide consistency and validation checks required by the accepted FINALIZATION-01 plan before the final closeout record and release-candidate handoff.

F6.1 is a validation gate, not a feature or cleanup milestone. A failure is handled according to the FINALIZATION-01 rule: characterize a concrete accepted-1.0 blocker, apply only a bounded fix, and repeat the affected checks. New architecture and opportunistic cleanup remain out of scope.

## Baseline

Run this preflight from the current `develop` baseline after F5.3 has merged.

The preceding finalization blocks have already established:

- canonical public L/C/B/S sample surface;
- classified/documented public 1.0 API;
- reconciled learning path and project presentation;
- public Packagist installation;
- clean external Recommended-workflow proof;
- Composer-installed canonical sample execution across all three 1.0 product models;
- supported environment reconciliation for PHP `^8.2`, DOM, ZIP, Composer/Packagist, and the LibreOffice boundary.

F6.1 does not reopen those decisions without new contradictory evidence.

## Automated repository preflight

Run from a clean checkout of the F6.1 candidate:

```bash
composer install --no-interaction --prefer-dist --no-progress
composer validate --strict --no-check-lock
find src tests -type f -name '*.php' -print0 | xargs -0 -n1 php -l
composer test
git diff --check
```

The full `composer test` run includes the repository PHPUnit suite and therefore the current public-sample smoke/integration coverage. If a failure makes that assumption false, record and run the missing focused suite explicitly rather than silently weakening the gate.

## Documentation preflight

Using the documented isolated documentation environment, run:

```bash
python3 -m venv .venv-docs
source .venv-docs/bin/activate
pip install zensical
zensical build --strict
```

An already established compatible docs virtual environment may be reused; the requirement is a strict successful documentation build, not recreation of the environment for its own sake.

## Public-surface consistency scans

Perform repository scans for release-facing stale terminology or removed historical public paths that would contradict the accepted 1.0 presentation.

At minimum review hits for:

```bash
grep -RInE 'sample_[0-9]+|samples/sample_[0-9]+' README.md docs demo samples --exclude-dir=output || true
grep -RInE 'master|develop' README.md docs CONTRIBUTING.md SECURITY.md || true
grep -RInE 'PHP 8\.[0-9]|PHP 8\.2\+|\^8\.2|ext-dom|ext-zip' README.md docs composer.json .github || true
```

These scans are evidence-gathering, not automatic defect declarations. Historical architecture records, compatibility documentation, and the explicitly pre-1.0 `SECURITY.md` wording may legitimately contain terms that should not be mechanically replaced.

## CI evidence

The F6.1 pull request must pass the normal GitHub CI matrix:

- PHP 8.2;
- PHP 8.3;
- PHP 8.4.

The repository CI performs strict Composer validation, dependency installation, PHP lint, and `composer test` for each matrix version.

The documentation workflow/check must also be green.

## Rendering / LibreOffice boundary

F6.1 must not pretend automated tests replace visual LibreOffice regression.

The finalization slices immediately preceding F6.1 did not introduce rendering semantics; F5.2 nevertheless supplied a real external LibreOffice proof for the documented Quick Start and successful ODT generation for representative installed samples.

For F6.1:

- if no rendering-sensitive code has changed since the last relevant manual regression, record that evidence rather than inventing a redundant rendering change;
- if the preflight or a bounded blocker fix changes rendering-sensitive code, repeat the established relevant LibreOffice regression before F6.1 can pass.

The broader integrated visual, headless, PDF, DOCX, save/reopen, and professional CV acceptance remains the separate RELEASE-1.0 INTEGRATION PRE-FLIGHT defined by the controlling plan.

## Required result record

Before F6.1 is marked COMPLETE, record the actual result of each gate:

| Gate | Required result |
| --- | --- |
| Composer install | PASS |
| `composer validate --strict --no-check-lock` | PASS |
| PHP lint for `src/` and `tests/` | PASS |
| full `composer test` | PASS |
| PublicSampleSmokeTest / included equivalent | PASS |
| `git diff --check` | PASS |
| `zensical build --strict` | PASS |
| release-facing consistency scans | REVIEWED / no unresolved blocker |
| GitHub CI PHP 8.2 | PASS |
| GitHub CI PHP 8.3 | PASS |
| GitHub CI PHP 8.4 | PASS |
| GitHub documentation check | PASS |
| LibreOffice regression requirement | SATISFIED by existing evidence or repeated if rendering-sensitive changes occur |

Do not replace an unexecuted gate with an assumption.

## Acceptance

F6.1 is **PASS / COMPLETE** only when all required repository and CI gates above have actual evidence and no unresolved accepted-1.0 blocker remains.

After F6.1 passes, proceed to:

- F6.2 — Finalization closeout record;
- F6.3 — handoff and release-candidate freeze for RELEASE-1.0 INTEGRATION PRE-FLIGHT.

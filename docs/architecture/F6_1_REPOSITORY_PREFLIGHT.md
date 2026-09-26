# F6.1 — Repository-wide Consistency / Preflight

**Status:** PASS / COMPLETE — repository-wide validation gate satisfied

## Objective

Run the repository-wide consistency and validation checks required by the accepted FINALIZATION-01 plan before the final closeout record and release-candidate handoff.

F6.1 is a validation gate, not a feature or cleanup milestone. A failure is handled according to the FINALIZATION-01 rule: characterize a concrete accepted-1.0 blocker, apply only a bounded fix, and repeat the affected checks. New architecture and opportunistic cleanup remain out of scope.

## Baseline

F6.1 was executed on 2026-09-26 from branch `finalization/01-f6-1-repository-preflight`, based on the current `develop` state after F5.3.

The preceding finalization blocks had already established:

- canonical public L/C/B/S sample surface;
- classified/documented public 1.0 API;
- reconciled learning path and project presentation;
- public Packagist installation;
- clean external Recommended-workflow proof;
- Composer-installed canonical sample execution across all three 1.0 product models;
- supported environment reconciliation for PHP `^8.2`, DOM, ZIP, Composer/Packagist, and the LibreOffice boundary.

F6.1 found no contradictory evidence requiring those decisions to be reopened.

## Automated repository preflight

The following commands were executed locally:

```bash
composer install --no-interaction --prefer-dist --no-progress
composer validate --strict --no-check-lock
find src tests -type f -name '*.php' -print0 | xargs -0 -n1 php -l
composer test
git diff --check
```

Results:

- Composer installed the locked dependency set without changes and generated autoload files successfully.
- `composer validate --strict --no-check-lock` reported `composer.json` valid.
- PHP lint completed across `src/` and `tests/` with no syntax errors.
- `composer test` completed on PHP 8.3.6 with **995 tests / 7,656 assertions**.
- The full suite includes the current `PublicSampleSmokeTest` integration coverage.
- `git diff --check` completed without output.

The PHPUnit run reported one PHP warning:

```text
tests/Integration/S03StructuredProfessionalReportTest.php:154
mkdir(): File exists
```

This is a non-blocking test-fixture hygiene warning: the test attempts to create an already existing directory. It did not indicate an engine/API semantic failure and was not changed opportunistically during F6.1. PHPUnit also reported eight deprecations in the aggregate summary; no accepted-1.0 blocker was evidenced by them.

## Documentation preflight

Using the isolated documentation environment:

```bash
python3 -m venv .venv-docs
source .venv-docs/bin/activate
pip install zensical
zensical build --strict
```

Result:

```text
Build started
No issues found
Build finished in 11.41s
```

The strict documentation build therefore passed locally.

## Public-surface consistency scans

The required repository scans were executed and reviewed:

```bash
grep -RInE 'sample_[0-9]+|samples/sample_[0-9]+' README.md docs demo samples --exclude-dir=output || true
grep -RInE 'master|develop' README.md docs CONTRIBUTING.md SECURITY.md || true
grep -RInE 'PHP 8\.[0-9]|PHP 8\.2\+|\^8\.2|ext-dom|ext-zip' README.md docs composer.json .github || true
```

Disposition:

- numbered legacy sample references are retained where they are historical architecture, closeout, regression, or `tests/Fixtures/LegacySamples/` evidence; no stale current public sample path requiring a finalization fix was identified;
- branch references are consistent with the documented repository model: `master` is the conservative stable/public line and `develop` is the integration line;
- `SECURITY.md` still intentionally describes the pre-1.0 policy that security fixes apply to `master`; F5.1/F5.3 already identified this as release-time policy wording to review when stable 1.0 is actually published, not as an F6.1 blocker;
- runtime/environment references remain consistent: Composer requires PHP `^8.2`, DOM and ZIP; public installation documentation states PHP 8.2+ with DOM/ZIP; CI exercises PHP 8.2, 8.3 and 8.4.

**Result:** REVIEWED / no unresolved accepted-1.0 blocker.

## CI evidence

For PR #133 at preflight-record head `f270732b1c71b61cbc33d14c7f5d87af14658847`, GitHub Actions completed successfully:

- CI — PHP 8.2: PASS;
- CI — PHP 8.3: PASS;
- CI — PHP 8.4: PASS;
- Documentation — Zensical build: PASS.

The final evidence-record commit must retain the same green CI status before merge; a documentation-only record update does not weaken the required gate.

## Rendering / LibreOffice boundary

F6.1 introduced no rendering-sensitive engine change. The immediately preceding finalization slices likewise did not introduce rendering semantics.

F5.2 supplied recent external LibreOffice evidence for the documented Quick Start and successful ODT generation for representative Composer-installed samples across the 1.0 product models. Because F6.1 required no rendering-sensitive blocker fix, no redundant rendering regression was triggered.

The broader integrated visual, headless, PDF, DOCX, save/reopen, and professional CV acceptance remains the separate RELEASE-1.0 INTEGRATION PRE-FLIGHT defined by the controlling plan.

## Result record

| Gate | Actual result |
| --- | --- |
| Composer install | PASS |
| `composer validate --strict --no-check-lock` | PASS |
| PHP lint for `src/` and `tests/` | PASS |
| full `composer test` | PASS — 995 tests / 7,656 assertions; one non-blocking fixture warning |
| PublicSampleSmokeTest / included equivalent | PASS — included in full PHPUnit suite |
| `git diff --check` | PASS |
| `zensical build --strict` | PASS — no issues found |
| release-facing consistency scans | REVIEWED / no unresolved blocker |
| GitHub CI PHP 8.2 | PASS |
| GitHub CI PHP 8.3 | PASS |
| GitHub CI PHP 8.4 | PASS |
| GitHub documentation check | PASS |
| LibreOffice regression requirement | SATISFIED by existing F5.2 evidence; no rendering-sensitive F6.1 change |

## Acceptance

F6.1 is **PASS / COMPLETE**.

All required local repository, documentation, consistency, CI, and rendering-boundary gates have actual evidence. No unresolved accepted-1.0 blocker was found. The one PHPUnit warning is characterized as non-blocking test-fixture hygiene and does not justify opportunistic finalization cleanup.

After this record update receives green CI, FINALIZATION-01 may proceed to:

- F6.2 — Finalization closeout record;
- F6.3 — handoff and release-candidate freeze for RELEASE-1.0 INTEGRATION PRE-FLIGHT.

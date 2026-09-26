# RELEASE-1.0 Integration Pre-Flight — Final Decision Record

**Status:** PASS / RELEASE READY  
**Final release-candidate SHA:** `c92d7651e026dc793df77b281f120bc288bb71b2`  
**Target release:** `v1.0.0`

## Purpose

This record closes the accepted `RELEASE_1_0_INTEGRATION_PREFLIGHT.md`.

The pre-flight validated the finalized 1.0 product as an integrated release candidate without reopening architecture, public API design, template syntax, sample taxonomy, or unrelated cleanup.

The governing rule was followed throughout:

> **Reuse valid evidence. Re-run only what is necessary to prove the frozen integrated candidate or a release-specific boundary.**

## Candidate identity

FINALIZATION-01 handed off the frozen product baseline:

`c27a821e8ff6fe5fbd7a83be8fbe4637e62f44e8`

The release pre-flight subsequently introduced only bounded release documentation/policy changes:

- the pre-1.0 security wording was replaced by the stable 1.x policy;
- the 1.0.0 changelog and release comparison links were added.

Those changes were merged by PR #137. The resulting `develop` commit is:

`c92d7651e026dc793df77b281f120bc288bb71b2`

No engine, API, sample, package-runtime, or document-semantic behavior changed after the FINALIZATION-01 freeze. The P2–P4 product evidence therefore remains valid for this final candidate.

## Inherited evidence

The pre-flight accepted the recent FINALIZATION-01 evidence where no later change invalidated it:

- Composer install: PASS;
- strict Composer validation: PASS;
- PHP lint across `src/` and `tests/`: PASS;
- full PHPUnit suite: **995 tests / 7,656 assertions — PASS**;
- PublicSampleSmoke coverage: PASS;
- `git diff --check`: PASS;
- strict Zensical documentation build: PASS;
- GitHub CI on PHP 8.2, 8.3, and 8.4: PASS;
- canonical sample/public terminology consistency review: PASS;
- clean external Packagist/Composer consumer installation: PASS;
- installed-package proof across Simple, Structured, and Writer-native models: PASS;
- representative LibreOffice open/edit proof from the clean-consumer exercise: PASS.

This evidence was not mechanically recreated during release validation.

## P1 — Candidate identity

**Result: PASS.**

The release pre-flight began from the exact FINALIZATION-01 freeze identity and admitted no unrelated source changes.

After the bounded P6 release metadata changes, the final candidate identity was updated to:

`c92d7651e026dc793df77b281f120bc288bb71b2`

## P2 — Integrated ODT lifecycle

**Result: PASS.**

The canonical professional benchmark used was:

**S01b — Professional CV · Structured Template**

The existing sample generated its native ODT successfully. The generated document:

- opened in LibreOffice without error;
- remained editable as native ODT;
- saved successfully;
- reopened successfully after save;
- showed no corruption or missing primary document content.

No lifecycle blocker was observed.

## P3 — Professional document benchmark / visual regression

**Result: PASS.**

The multi-page S01b CV was reviewed in LibreOffice as the professional architecture benchmark.

The review covered the visible integrated result across its pages, including:

- Writer-owned page/sidebar geometry;
- image/frame content;
- main-column content and paragraph flow;
- professional-experience and education structures;
- lists and skill-rating glyphs;
- page transition/pagination;
- additional qualifications and second-page sidebar content.

No structural corruption, clipping, content loss, destructive overlap, or release-blocking layout regression was observed.

Whitespace resulting from the authored CV layout/content amount was not classified as an engine defect.

## P4 — Export and interoperability

**Result: PASS.**

### PDF

The same validated S01b document was exported from LibreOffice to PDF.

The exported PDF:

- contained two A4 pages;
- rendered successfully;
- retained the expected CV content across both pages;
- retained the sidebar, image, headings, lists, skill glyphs, and page transition;
- showed no observed clipping, missing content, destructive overlap, or broken glyph rendering.

**PDF boundary: PASS.**

### DOCX

Current 1.0 documentation does not promise DOCX export or DOCX interoperability as a supported release behavior.

**DOCX: NOT IN 1.0 CONTRACT.**

No DOCX test was added merely for checklist completeness.

## P5 — Release package and consumer sanity

**Result: PASS.**

The F5 clean-consumer evidence remains applicable because no later package-runtime change invalidated it.

The release package contract remains:

- Composer package: `waltdietzney/odt-template-engine`;
- PHP `^8.2`;
- `ext-dom`;
- `ext-zip`;
- PSR-4 autoloading;
- Composer/Packagist installation;
- LibreOffice is not a PHP runtime dependency.

The project continues to use tag-based Composer versioning. No Composer `version` field was added for 1.0.

No release-package blocker was identified.

## P6 — Release metadata and policy readiness

**Result: PASS.**

Release-time review identified two bounded documentation/policy corrections, completed in PR #137:

1. `SECURITY.md` now describes the stable 1.x support policy and distinguishes the stable `master` line from unreleased `develop`.
2. `CHANGELOG.md` now contains the `1.0.0` release entry and comparison links.

The release naming is `v1.0.0`.

The accepted branch/publication flow is:

```text
develop
  -> master
  -> tag v1.0.0
  -> GitHub Release
  -> Packagist observes the release tag
```

At the time of this decision record, `master` remains the conservative stable/public line and has not yet received the 1.0 candidate. That merge is a publication action after this pre-flight, not unresolved product development.

## Blockers and bounded corrections

No engine or accepted-1.0 semantic blocker was found during the integration pre-flight.

The only corrections made were release-specific documentation/policy changes in P6. They did not invalidate the integrated ODT, LibreOffice, PDF, package-runtime, or public-API evidence.

No architecture was reopened and no new capability was introduced.

## Explicitly non-blocking future work

Post-1.0 topics already recorded in `FUTURE_DEVELOPMENT.md` remain deferred. They are not promoted into 1.0 by this decision record.

Known finalization observations without accepted-1.0 blocker evidence also remain non-blocking, including:

- the test-fixture `mkdir(): File exists` warning observed during F6.1;
- aggregate PHPUnit deprecations reported during F6.1.

These observations do not prevent the 1.0 release.

## Final decision

All completion criteria from `RELEASE_1_0_INTEGRATION_PREFLIGHT.md` are satisfied:

- final candidate identity is explicit;
- integrated native ODT generation/lifecycle is accepted;
- the professional multi-page Writer benchmark is accepted;
- PDF export is accepted;
- DOCX is explicitly outside the 1.0 contract;
- package/consumer readiness is accepted;
- release metadata and security policy are ready;
- no unresolved accepted-1.0 blocker remains.

# PASS / RELEASE READY

Candidate `c92d7651e026dc793df77b281f120bc288bb71b2` may proceed to the 1.0 publication flow.

The remaining work is publication, not product development.

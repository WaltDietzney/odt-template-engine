# RELEASE-1.0 Integration Pre-Flight Plan

**Status:** Accepted execution candidate — release validation only  
**Frozen baseline:** `c27a821e8ff6fe5fbd7a83be8fbe4637e62f44e8`  
**Source branch:** `develop`

## Goal

Prove that the frozen post-FINALIZATION-01 candidate works as one coherent 1.0 product and is suitable for release.

This is a release-validation gate, not another architecture, finalization, cleanup, or documentation milestone.

The governing rule is:

> **Reuse valid evidence. Re-run only what is necessary to prove the frozen integrated candidate or a release-specific boundary.**

FINALIZATION-01 evidence remains valid unless the release pre-flight changes the relevant surface or discovers contradictory evidence.

## Scope discipline

The release pre-flight may:

- execute integrated validation against the frozen candidate;
- perform release-specific manual LibreOffice/export/interoperability checks;
- verify release metadata and publication readiness;
- characterize a concrete release blocker;
- apply a bounded blocker fix and repeat only invalidated evidence.

It must not:

- add new architecture or capabilities;
- redesign public APIs;
- introduce new template syntax;
- restructure the canonical sample or documentation model;
- remove Compatibility/Deprecated APIs as cleanup;
- perform unrelated refactoring;
- turn known non-blocking observations into work without new blocker evidence.

If a blocker requires a source change, the freeze is broken intentionally. The fix receives focused validation, affected integrated checks are repeated, and a new candidate SHA is explicitly frozen.

## Evidence inherited without routine repetition

The following recent evidence is accepted from FINALIZATION-01 unless contradicted or invalidated:

- full PHPUnit suite: 995 tests / 7,656 assertions — PASS;
- PublicSampleSmoke coverage — PASS;
- PHP lint for `src/` and `tests/` — PASS;
- Composer strict validation — PASS;
- documentation strict build — PASS;
- GitHub CI on PHP 8.2, 8.3, and 8.4 — PASS;
- canonical sample/public terminology consistency review — PASS;
- clean external Packagist/Composer consumer installation — PASS;
- installed-package proof across Simple, Structured, and Writer-native models — PASS;
- representative LibreOffice-open/edit proof from the clean consumer exercise — PASS.

These checks are not repeated merely to produce another identical checklist. They are repeated only where the exact frozen-candidate proof or a later source change makes that evidence materially necessary.

## P1 — Frozen-candidate identity and repository state

Record and verify:

- frozen candidate SHA: `c27a821e8ff6fe5fbd7a83be8fbe4637e62f44e8`;
- F6.3 is merged to `develop`;
- release-preflight work starts from that exact baseline;
- no unrelated source changes are admitted during validation.

**Gate:** candidate identity is unambiguous and reproducible.

## P2 — Integrated ODT lifecycle proof

Run a bounded release-level lifecycle set against representative public/professional documents.

Required integrated behaviors:

- generate ODT from the frozen candidate;
- validate ODT as ZIP/XML package;
- open generated document in LibreOffice;
- save/reopen without corruption;
- exercise repeated lifecycle behavior where the accepted 1.0 contract promises it;
- confirm generated output remains editable native ODT.

Use existing canonical/professional fixtures rather than inventing a new showcase.

A failure is classified against accepted 1.0 semantics before any code change.

**Gate:** no integrated ODT lifecycle blocker.

## P3 — Professional document benchmark and visual regression

Use the existing professional multi-page CV as the primary generic architecture benchmark, supplemented only where another existing canonical document covers a materially different 1.0 surface.

Validate in LibreOffice:

- page/master-page behavior;
- paragraph flow and page breaks;
- tables and column geometry;
- frames/text boxes/images;
- lists and rich text;
- headers/footers where present;
- structured/template-owned content;
- overall multi-page visual stability.

The goal is not pixel-perfect comparison for its own sake. The check looks for structural, layout, clipping, pagination, missing-content, or obvious formatting regressions.

Existing known-good artifacts/evidence should be reused where possible.

**Gate:** professional Writer output is visually and structurally acceptable for 1.0.

## P4 — Export and interoperability boundary

Validate only export/interoperability promises that belong to the accepted 1.0 product.

### PDF

From a representative generated professional ODT:

- export through LibreOffice to PDF;
- confirm export succeeds;
- inspect page count/content presence and obvious visual integrity.

### DOCX

DOCX is tested only if current 1.0 public documentation explicitly promises DOCX interoperability/export as a supported release behavior.

If it is not promised, record **NOT IN 1.0 CONTRACT** rather than expanding scope.

Where tested, use representative interoperability, not exhaustive round-trip equivalence. Native ODF semantics are not silently redefined around DOCX limitations.

**Gate:** promised export/interoperability boundaries are satisfied or explicitly classified out of the 1.0 contract.

## P5 — Release package and consumer sanity

Reuse F5 clean-consumer evidence and add only the release-specific sanity needed for the frozen candidate.

Check:

- Composer package metadata still matches the documented runtime contract;
- package/version publication path is understood;
- no release-specific file or metadata issue blocks 1.0;
- installation instructions still correspond to the package that will be published.

A second full clean-machine exercise is not required unless packaging changes after F5 or contradictory evidence appears.

**Gate:** no release-package blocker.

## P6 — Release metadata and policy readiness

Review the surfaces that intentionally remained release-time concerns:

- `SECURITY.md` stable-1.0 wording;
- version/tag/release naming;
- changelog/release notes state;
- target branch flow from `develop` to the conservative public/release line;
- GitHub Release and Packagist publication sequence.

This slice may make bounded release-metadata/documentation corrections. Such changes do not reopen architecture, but the final release candidate SHA must be updated after any committed change.

Do not add a Composer `version` field merely to encode the release; tags remain the version authority unless a concrete package requirement proves otherwise.

**Gate:** publication metadata and policy are ready for 1.0.

## P7 — Final release decision record

Create a concise release-preflight record containing:

- final frozen candidate SHA;
- inherited evidence used;
- checks actually executed during this pre-flight;
- manual LibreOffice/PDF/interoperability results;
- any blockers found and bounded fixes applied;
- final package/release metadata status;
- remaining explicitly non-blocking future work.

Possible outcomes:

- **PASS / RELEASE READY** — candidate may proceed to 1.0 publication;
- **BLOCKED** — concrete release blocker remains, with evidence and required bounded action.

The record must not declare PASS while relying on an untested promise introduced during the pre-flight.

## Efficient execution order

The execution order is deliberately short:

```text
P1  Freeze identity
 |
 v
P2 + P3  Integrated ODT lifecycle + professional LibreOffice review
 |
 v
P4  PDF / promised interoperability
 |
 v
P5 + P6  Package and release metadata
 |
 v
P7  Release decision record
 |
 v
1.0 publication
```

P2 and P3 should normally be performed in the same generated-document/manual LibreOffice session. P4 should reuse that same representative document for PDF export. P5 reuses F5 evidence rather than recreating the clean-room exercise.

## Blocker protocol

If a check fails:

1. capture the concrete failure;
2. determine whether it violates an accepted 1.0 promise;
3. if not, record it as non-blocking/future work;
4. if yes, characterize the smallest correction;
5. implement only that correction on a focused branch/commit;
6. repeat the affected check and any evidence invalidated by the change;
7. establish a new candidate SHA.

Do not restart the entire pre-flight automatically after every bounded correction.

## Completion criterion

RELEASE-1.0 INTEGRATION PRE-FLIGHT is complete when:

- the final candidate identity is explicit;
- integrated native ODT generation/lifecycle is accepted;
- the professional multi-page Writer benchmark is accepted;
- PDF and any actually promised interoperability boundary are accepted;
- package and release metadata are ready;
- no unresolved accepted-1.0 blocker remains;
- the release decision record states **PASS / RELEASE READY**.

At that point the remaining work is publication, not product development.

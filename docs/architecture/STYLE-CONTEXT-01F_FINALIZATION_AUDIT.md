# STYLE-CONTEXT-01F — Finalization Audit and Decision Gate

## Purpose

This document records the evidence gathered before defining the implementation contract for STYLE-CONTEXT-01F.

01F is expected to address style finalization and multi-document isolation. The audit deliberately separates current behavior, compatibility constraints, and unresolved decisions before implementation starts.

## Repository baseline

Audit baseline:

- branch: `develop`
- commit: `13108b730b5a8ed2a89943525cbd6b89f3f47961`
- includes STYLE-CONTEXT-01A through 01E

## Current finalization path

`OdtTemplate::save()` currently performs:

```text
injectImageStyles()
    ↓
StyleWriter::writeAllStyles(stylesDom)
    ↓
adjustBulletIndentation()
    ↓
OdtPackage::saveAs()
```

`refresh()` also calls `StyleWriter::writeAllStyles()` before persisting and then resetting through `load()`.

`StyleWriter::writeAllStyles()` still discovers multiple pending style families through process-wide `StyleMapper` registries:

- text styles;
- legacy paragraph styles;
- frame / graphic styles;
- table-cell styles;
- table styles.

Image/fill-image styles are also still injected from process-wide `StyleMapper` registries by `OdtTemplate::injectImageStyles()` before `StyleWriter` runs.

Therefore paragraph-style migration alone is not equivalent to complete document-scoped style ownership across all existing style families.

## Current document-scoped paragraph path

`OdtDocumentContext` owns one `StyleContext` for the lifetime of one logical editable ODT document.

`StyleContext` currently owns pending paragraph requirements only.

`OdtTemplate::setElement()` performs two distinct actions for structured paragraph styles:

1. registers each paragraph requirement in the current document's `StyleContext`;
2. immediately calls `ensureParagraphStylesExist()`, which writes missing paragraph styles directly into the current document's `styles.xml` DOM.

Thus the current `StyleContext` is an ownership and conflict boundary, but it is not yet the source consumed by save-time finalization for this path. The generated paragraph style is normally already present in the document DOM before `save()` is called.

## Legacy paragraph compatibility path

STYLE-CONTEXT-01E moved legacy paragraph storage out of `StyleMapper` into `LegacyStyleRegistry`, while preserving the public/static facade:

```text
StyleMapper::registerParagraphStyle()
    ↓
LegacyStyleRegistry
    ↓
StyleMapper::getParagraphStyles()
    ↓
StyleWriter::writeAllStyles()
```

The historical semantics remain deliberately process-wide and first-registration-wins.

The existing STYLE-CONTEXT-01A characterization proves that a legacy paragraph style registered before document A is saved is also written into a later unrelated document B in the same PHP process.

This leakage is currently intentional compatibility behavior, not an accidental untested side effect.

## Fundamental compatibility constraint

A call such as:

```php
StyleMapper::registerParagraphStyle('Example', $definition);
```

contains no document identity.

The STYLE-CONTEXT-01B contract already prohibits pretending that such a call can be transparently document-scoped through:

- a process-global current-document pointer;
- constructor-time resets;
- last-created-document semantics;
- timing-dependent ownership guesses.

Therefore eliminating legacy paragraph leakage necessarily requires an explicit compatibility decision.

There is no implementation-only trick that can simultaneously guarantee all of the following:

1. context-free static registration remains unchanged;
2. the registered style is automatically materialized into the intended document;
3. unrelated documents never see the style;
4. no document identity is supplied anywhere.

At least one of those assumptions must change.

## Decision gate before implementation

Before 01F implementation begins, the project must explicitly choose the fate of context-free legacy paragraph registration.

The principal options are:

### Option A — Preserve legacy global finalization

Keep `StyleWriter::writeAllStyles()` consuming `LegacyStyleRegistry`.

Consequence:

- strongest backward compatibility;
- existing leakage remains;
- STYLE-CONTEXT-01 cannot claim complete cross-document isolation for legacy callers.

### Option B — Stop automatic legacy materialization

Keep the static APIs callable and keep their compatibility registry, but stop save-time finalization from importing legacy paragraph registrations into arbitrary documents.

Consequence:

- document isolation becomes achievable;
- callers relying on `StyleMapper::registerParagraphStyle()` followed by `OdtTemplate::save()` lose existing behavior;
- this is a deliberate backward-compatibility change and must be documented as such.

### Option C — Introduce explicit document transfer / registration

Keep legacy static storage for compatibility, but require an explicit document-aware operation to transfer selected legacy styles into one `OdtTemplate` / `OdtDocumentContext` before finalization.

Consequence:

- ownership becomes explicit;
- legacy static APIs can remain available;
- automatic historical behavior cannot remain fully transparent;
- exact public/protected API shape would require a separately approved design and must not be invented inside 01F without review.

The audit does not select one of these options automatically.

## Scope problem beyond paragraphs

Even after resolving legacy paragraph finalization, process-wide mutable style state still exists for other families used during save/finalization.

Therefore the project must also distinguish two possible meanings of "01F complete":

### Narrow 01F

Complete the paragraph-style migration only:

- finalization consumes document-owned paragraph requirements;
- modern paragraph styles are proven isolated across documents;
- legacy paragraph behavior follows the approved compatibility decision;
- remaining global text/image/frame/table/table-cell/fill-image/font paths remain explicit follow-up work.

This is the smaller, safer slice.

### Full STYLE-CONTEXT closeout in 01F

Migrate all remaining global style families involved in finalization into document-scoped ownership.

This would touch several independently complex ODF families and would mix paragraph finalization with image/frame/table/text/font behavior.

That would be a substantially larger architecture slice and conflicts with the established incremental-migration principle unless preceded by family-specific characterization.

Audit recommendation: prefer the narrow paragraph-focused 01F and do not silently claim that all style families have become document-scoped. If the strategic milestone must mean complete isolation for every family, define additional STYLE-CONTEXT slices rather than expanding 01F into a rewrite.

## Required paragraph finalization semantics

For the modern document-aware paragraph path, the desired invariant remains:

```text
OdtDocumentContext
    ↓ owns
StyleContext
    ↓ pending paragraph requirements
finalization(documentContext)
    ↓
current styles.xml only
```

Required behavior:

- document A requirements never appear in document B;
- document B requirements never appear in document A;
- save order does not affect output ownership;
- interleaved editing and saving does not cross-contaminate documents;
- repeated save of one unchanged document is style-idempotent;
- `load()` resets pending requirements consistently with the restored template;
- existing template-authored styles in `styles.xml` remain authoritative document data;
- same-name equivalent pending requirements remain idempotent;
- same-name conflicting pending requirements remain explicit conflicts according to the existing StyleContext contract.

## Immediate materialization versus deferred finalization

The current structured-element path writes paragraph styles to `styles.xml` immediately in `setElement()` via `ensureParagraphStylesExist()`.

01F must not remove that immediate write merely for architectural purity without first characterizing observable behavior between `setElement()` and `save()`.

Two implementation strategies are possible:

1. retain immediate DOM materialization and make finalization document-aware/idempotent;
2. defer paragraph style writing until finalization and change the pre-save DOM lifecycle.

The second strategy has broader behavioral implications and should not be chosen without characterization tests. The smaller compatible strategy is to preserve current immediate materialization unless evidence requires otherwise.

## Regression tests required for 01F

At minimum, focused tests should cover:

1. two simultaneous documents with distinct structured paragraph styles;
2. save A then B and assert no A-only style in B and no B-only style in A;
3. save B then A to prove order independence;
4. interleaved save A → save B → save A again;
5. repeated save of one unchanged document without duplicate or missing paragraph styles;
6. `load()` reset behavior;
7. equivalent repeated paragraph registration;
8. conflicting same-name document-scoped requirements;
9. the approved legacy `StyleMapper::registerParagraphStyle()` compatibility behavior;
10. saved `styles.xml` remains ODF-valid and contains the expected paragraph properties.

The existing STYLE-CONTEXT-01A leakage characterization must either:

- remain intentionally green if Option A is selected; or
- be explicitly replaced/inverted by a new approved regression if Option B or C changes the behavior.

It must not simply be deleted to make the suite pass.

## Existing tests that form the preflight set

The current style-context regression set includes:

- `tests/Document/StyleContextTest.php`;
- `tests/Integration/StyleContextCharacterizationTest.php`;
- `tests/Integration/StyleContextElementIntegrationTest.php`;
- `tests/Integration/StyleMapperCompatibilityTest.php`;
- `tests/Integration/StylePipelineP2BTest.php`;
- relevant package/lifecycle/finalization tests;
- `tests/Integration/PublicSampleSmokeTest.php`.

`PublicSampleSmokeTest` currently executes all 25 public sample scripts in an isolated temporary copy, validates generated ODT/ZIP structure, checks `content.xml` and `styles.xml`, applies additional Sample 25 assertions, and verifies that repository `samples/output/` was not modified.

## Rendering-sensitive validation

The current roadmap defines the rendering-sensitive workflow as:

```text
automated tests
    ↓
ODT / ZIP / XML validation
    ↓
public Sample Explorer
    ↓
LibreOffice headless
    ↓
PDF
    ↓
Poppler PNG pages
    ↓
visual review
```

For 01F, existing correctly rendered samples should be visually unchanged. The style-ownership refactor is not intended to alter the appearance of a single correctly generated document.

Therefore acceptance should include the existing ODT → LibreOffice → PDF → PNG visual-regression workflow for representative style-heavy samples, plus the complete public sample smoke test.

The repository documents the pipeline but this audit did not find a versioned repository script or command that implements the local PDF/PNG conversion helper. If that helper is intentionally external/local tooling, the implementation/preflight report should record the exact command used rather than inventing a repository command.

## Non-goals for the narrow 01F recommendation

Unless a broader contract is separately approved, 01F should not:

- migrate text styles;
- migrate image or fill-image registries;
- migrate frame/graphic styles;
- migrate table or table-cell styles;
- redesign font registration;
- redesign `StyleMapper` mapping helpers;
- redesign `refresh()` semantics;
- introduce document defaults;
- redesign image/frame/table APIs;
- alter sample layouts;
- touch `samples/output/` as source files.

## Audit conclusion

The repository is ready for a small finalization slice, but there is one real architecture decision that must be made before implementation:

> What compatibility behavior should remain for context-free legacy `StyleMapper::registerParagraphStyle()` calls once document-scoped finalization is enforced?

In addition, 01F should not be described as eliminating all process-wide style state unless the remaining text/image/frame/table/table-cell/fill-image/font paths are also migrated in separately characterized work.

Recommended next action:

1. approve the legacy paragraph compatibility strategy;
2. define 01F as a narrow paragraph finalization + multi-document regression slice;
3. capture remaining global style families as explicit subsequent STYLE-CONTEXT work rather than expanding 01F opportunistically;
4. only then issue the coding-agent implementation prompt.

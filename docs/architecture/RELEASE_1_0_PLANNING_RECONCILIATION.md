# Release 1.0 Planning Reconciliation

**Status:** Accepted planning baseline  
**Target:** Version 1.0  
**Baseline branch:** `develop`

## Purpose

This document reconciles the version-1.0 plan with the architecture and implementation state reached after TEMPLATE-AUTHORING-01E.

It is a planning closeout, not a new architecture milestone. It does not reopen completed semantics or authorize new public APIs.

## Reconciled mandatory path

The remaining mandatory path to version 1.0 is:

```text
TEMPLATE-AUTHORING-01F
    Authoring documentation, samples, templates, and public learning path
    ├── TABLE-ROW-01 + L12 Writer Table Population — COMPLETE
    ├── S03 Structured Professional Report — NEXT
    └── 01F Final Review
        ├── canonical sample migration / coverage review
        ├── canonical sample documentation pass
        ├── authoring documentation + learning-path coherence
        ├── README / Sample Explorer alignment
        └── bounded product-website alignment
    ↓
FINALIZATION-01
    Final document/export lifecycle contract
    ↓
RELEASE-1.0 INTEGRATION PRE-FLIGHT
    Feature freeze and whole-product validation
    ↓
1.0
```

The completed baseline includes:

- PAGE-FLOW-01 — COMPLETE / FINAL GO;
- TABLE-LAYOUT-01 — COMPLETE / FINAL GO;
- FRAME-LAYOUT-01 — COMPLETE / FINAL GO;
- TEMPLATE-AUTHORING-01A — COMPLETE;
- TEMPLATE-AUTHORING-01B — COMPLETE;
- TEMPLATE-AUTHORING-01C — COMPLETE for the bounded 1.0 Writer User Field scope;
- TEMPLATE-AUTHORING-01D — COMPLETE / FINAL GO;
- TEMPLATE-AUTHORING-01E — COMPLETE / FINAL GO.

TEMPLATE-AUTHORING-01E includes the required manual LibreOffice regression gate. Earlier planning text that still describes that gate as pending is stale and must not be used to reopen Phase E.

## TEMPLATE-AUTHORING-01C boundary

The version-1.0 native-field scope is intentionally bounded to Writer User Fields with `office:value-type="string"`.

Broader User Field value types, Set/Get Variable semantics, and additional Writer field families remain future work. They are not version-1.0 blockers unless a later mandatory milestone demonstrates a concrete dependency.

## TEMPLATE-AUTHORING-01F product-authoring objective

Phase F is not merely an API-reference writing task. It must make the implemented authoring model coherent, discoverable, and usable.

The public learning surfaces should tell one consistent story:

```text
README
   ├── Documentation
   ├── Public samples and templates
   └── Product website
```

They may differ in depth, but they must use consistent terminology, lifecycle descriptions, supported APIs, and recommended workflows.

Phase F should explain at least:

- LibreOffice as a visual template authoring environment;
- simple template processing with visible expressions such as `{{...}}`;
- programmatic structured content through ODT elements;
- template-owned native structure through Sections, bookmarks, tables, frames, and supported Writer User Fields;
- declarative structural controls;
- inspection, mapping, preflight, and optional automation;
- when the imperative APIs remain the appropriate choice;
- formatting-preservation and nesting constraints;
- diagnostics and validation;
- realistic end-to-end examples.

### Human and AI-agent readability

Version-1.0 documentation and examples should be authoritative enough for both human developers and coding/AI agents.

Canonical examples should make the supported workflow and lifecycle explicit and minimize ambiguity between historical, compatibility, and recommended APIs. Documentation should state important boundaries and non-goals rather than requiring consumers to infer them from implementation details.

A coding agent should be able to determine from public documentation which supported mechanism fits a task without inventing a mixed or nonexistent API.

### Sample roles

Phase F should distinguish between:

1. regression/technical samples, whose primary purpose is proving a bounded behavior; and
2. public learning/showcase samples, whose purpose is teaching the supported authoring model clearly and attractively.

Existing regression fixtures do not need to be redesigned merely for presentation. The public learning path should instead identify or create canonical examples that progress from simple to structured and professional use.

The canonical L/C/B/S sample families are the intended version-1.0 public learning path, not an additional permanent layer beside the historical sample collection. Existing registry migration metadata is retained while Phase F is active for traceability. During the 01F Final Review, historical samples must be checked against canonical coverage: covered public samples may be retired from the public learning path, while uncovered relevant capabilities should be represented canonically rather than preserved only because a historical sample already exists.

Canonical samples are product documentation. Before Phase F closes, they require a documentation-quality pass with useful English file headers and comments at architecturally important ownership/API decisions without cluttering trivial PHP. B02 is explicitly included in this pass.

Locally modified files under `samples/output/` remain regression artifacts and are not source changes unless a task explicitly concerns them.

The local `research/` material remains valuable ODF research evidence. It is not automatically part of the public authoring/tutorial surface.

### Product website

A bounded product-website alignment belongs at the end of Phase F, after the canonical terminology, documentation, samples, and templates are settled.

The website should present the same authoring model in a concise discovery-oriented form and lead users to the documentation and Sample Explorer. Phase F does not authorize a separate website redesign project.

## FINALIZATION-01

FINALIZATION-01 remains the last planned architecture decision before the release preflight.

It must define the final document/export lifecycle contract, including the boundary between engine processing and Writer/export-tool responsibility, completion of binding and structural instantiation, repeated render/save behavior, and any meaningful distinction between semantic source ODT and finalized output.

Version 1.0 does not require a universal PHP evaluator for all Writer-native semantics.

## RELEASE-1.0 integration preflight

After Phase F and FINALIZATION-01, unrelated feature work stops for the release preflight.

The preflight should cover, as applicable:

- focused PHPUnit and integration tests;
- full `composer test`;
- PHP lint for `src/` and `tests/`;
- `composer validate`;
- `git diff --check`;
- PublicSampleSmokeTest;
- ODT/ZIP/XML integrity;
- LibreOffice headless rendering;
- manual visual regression;
- save/reopen and repeated lifecycle behavior;
- PDF output;
- representative DOCX interoperability only where version 1.0 explicitly promises it;
- a professional multi-page CV as a generic end-to-end architecture benchmark.

Automated validation does not replace manual LibreOffice regression for rendering-sensitive behavior.

## Explicit non-blockers

Existing future-work items remain valid but are not version-1.0 blockers unless Phase F or FINALIZATION-01 demonstrates a concrete dependency.

This includes, among other topics:

- PAGE-STYLE-AUTHORING-01;
- DOCUMENT-DEFAULTS-01;
- TABLE-COLUMN-IDENTITY-01;
- broader Writer field families and value types;
- broader named-object operations;
- additional drawing capabilities;
- document import/round-trip reconstruction;
- renderer-neutral shared document models.

The remaining 1.0 work must stay bounded. New attractive capabilities do not enter the release path merely because they are useful.

## Governing rule

> Semantics before implementation.

For the remaining release path this also means: one coherent product model before expanding public examples, and one explicit finalization contract before declaring the engine 1.0-ready.

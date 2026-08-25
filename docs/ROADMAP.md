# Development Roadmap

This roadmap describes the planned development direction of the ODT Template Engine beyond the current stable public baseline.

It complements [`FUTURE_DEVELOPMENT.md`](FUTURE_DEVELOPMENT.md):

- `FUTURE_DEVELOPMENT.md` is the issue-oriented backlog of known limitations, technical debt, and possible improvements.
- `ROADMAP.md` defines the strategic sequence in which larger architectural work should be approached.

The roadmap is intentionally conservative about public API changes. Existing application-facing APIs should remain stable while internal responsibilities are extracted and clarified.

## Current baseline

The current stable line already provides:

- variable replacement and filters;
- conditions and repeating blocks;
- native rich text and paragraphs;
- native ODT lists;
- generated tables and styled table cells;
- image insertion and replacement;
- HTML import;
- document metadata;
- page-layout adjustments;
- public Samples 01–21 with smoke-test coverage;
- developer documentation and a real-world complex-document example.

The next phase is not primarily about adding isolated features. It is about preparing the engine for more complex document composition without continuing to grow `AbstractOdtTemplate` and `OdtTemplate` as central all-purpose classes.

## Development branch model

The repository uses `master` as the stable public baseline.

The long-running `develop` branch is the integration branch for the next architectural development phase.

Feature and refactoring work should normally branch from `develop` and return to `develop` through focused pull requests.

```text
master
  │
  └── stable / public baseline

 develop
  │
  ├── architecture/*
  ├── refactor/*
  ├── feature/*
  └── documentation/*
```

A larger development milestone should be merged from `develop` to `master` only after automated tests, public sample smoke tests, representative generated ODT documents, and LibreOffice regression checks are green.

## Phase A — Architecture and responsibility boundaries

### ARCH-01 — Responsibility audit — COMPLETE

Goal: map every responsibility currently located in `AbstractOdtTemplate`, `OdtTemplate`, and `PageLayoutOdtTemplate` before moving code.

The audit is recorded in [`architecture/ARCH-01_RESPONSIBILITY_AUDIT.md`](architecture/ARCH-01_RESPONSIBILITY_AUDIT.md).

Confirmed findings include:

- document/package state has no single owner;
- package lifecycle and template-language rendering are mixed in `OdtTemplate`;
- basic placeholder processing and structured element insertion are mixed into `AbstractOdtTemplate`;
- style serialization still has multiple compatibility/direct paths;
- page-layout behavior is a coherent domain responsibility but currently depends on inheritance for document access;
- several legacy/compatibility paths require characterization before removal;
- composition behind the existing `OdtTemplate` facade is the preferred direction.

The audit confirms ARCH-02 as the first implementation milestone.

### ARCH-02 — Extract ODT package / document context — COMPLETE

Goal: separate physical ODT package handling from template rendering.

Likely responsibilities include:

```text
ODT package
├── template extraction
├── temporary working directory
├── content.xml
├── styles.xml
├── meta.xml
├── META-INF/manifest.xml
├── Pictures/
├── ZIP rebuild
└── cleanup
```

ARCH-02 introduced `OdtPackage` and `OdtDocumentContext` as the owners of
package/workspace state and the core document DOMs.

Completed responsibilities include:

- template extraction and temporary workspace ownership;
- `content.xml`, `styles.xml`, and `meta.xml` access through
  `OdtDocumentContext`;
- XML persistence;
- manifest synchronization;
- ZIP rebuild with ODT `mimetype` handling;
- idempotent cleanup;
- reset-from-source behavior for the compatible public `load()` lifecycle.

Public `OdtTemplate` usage should remain unchanged during this extraction.

ARCH-01 further constrains ARCH-02:

- do not redesign template-language processing in this step;
- do not solve `StyleMapper` static state in this step;
- do not remove `AbstractOdtTemplate` yet;
- preserve constructor, render, and save behavior;
- add characterization/integration coverage for extraction, XML ownership, ZIP rebuild, `mimetype`, and cleanup.

### ARCH-03 — Technical ODT document layer — COMPLETE

ARCH-03 established and characterized the technical document layer between
the public template facade and the physical package lifecycle. The audit is
recorded in [`architecture/ARCH-03_DOCUMENT_CORE_AUDIT.md`](architecture/ARCH-03_DOCUMENT_CORE_AUDIT.md).

#### ARCH-03A — Technical ODT document layer audit — COMPLETE

The audit separated technical ODT document responsibilities from
template-language processing and confirmed that package persistence belongs
to `OdtPackage`, while the core XML documents belong to `OdtDocumentContext`.

#### ARCH-03B — Extract metadata and page-layout services — COMPLETE

Completed services:

- `MetadataManager`;
- `PageLayoutManager`;
- public facade compatibility for `OdtTemplate` and
  `PageLayoutOdtTemplate`.

The change contract is recorded in
[`architecture/ARCH-03B_CHANGE_CONTRACT.md`](architecture/ARCH-03B_CHANGE_CONTRACT.md).

#### ARCH-03C — Document finalization boundary — COMPLETE

The finalization sequence was characterized and remains orchestrated by
`OdtTemplate`. No `DocumentFinalizer` was extracted because current style and
image finalization still depends on process-wide static `StyleMapper` state.
`STYLE-CONTEXT-01` is therefore a prerequisite for a sound document-scoped
finalization boundary.

The decision is recorded in
[`architecture/ARCH-03C_FINALIZATION_DECISION.md`](architecture/ARCH-03C_FINALIZATION_DECISION.md).

### ARCH-04 — Extract template-language processing

Goal: move template-language processing behind a focused
`TemplateProcessor`/renderer responsibility.

The responsibility includes:

- variables;
- filters;
- `nl2br`;
- list placeholders;
- conditions;
- loops;
- template normalization.

The public workflow should remain compatible:

```php
$template->assign(...);
$template->assignRepeating(...);
$template->render();
```

ARCH-01 identified alternative/legacy conditional and repeating
implementations. These must be characterized before consolidation rather than
copied blindly into the new processor. Existing public APIs must remain
compatible.

### ARCH-05 — Extract structured element insertion

Goal: isolate the DOM-specific work required to place `OdtElement` structures into `content.xml` and other valid document regions.

This should create a clearer home for placeholder lookup, replacement, and structured content insertion without coupling that code to ZIP or metadata behavior.

This boundary is also intended to provide a future home for structural document concepts without adding them directly to the template-language processor.

### ARCH-06 — Reassess `AbstractOdtTemplate`

After the package, renderer, and insertion responsibilities have been extracted, reassess whether `AbstractOdtTemplate` still represents a meaningful abstraction.

A possible long-term outcome is removal of the abstract base class in favor of composition, but this is not a requirement. The decision should be based on the remaining responsibilities after extraction.

## Phase B — Document-scoped defaults, style and asset state

### DOCUMENT-DEFAULTS-01 — Document-level default settings

Goal: allow an application to define document-wide defaults once instead of repeating the same style and layout options on every generated element.

Representative defaults include:

```text
Text defaults
├── font family
├── font size
├── color
└── language where appropriate

Paragraph defaults
├── line height
├── paragraph spacing
├── alignment where appropriate
└── default paragraph style / role

Page defaults
├── margins
├── page size
└── orientation
```

The design should distinguish document defaults from explicit element styles. Explicit element options must continue to override defaults.

A possible future usage might look conceptually like:

```php
$template->setDocumentDefaults([
    'text' => [
        'font-family' => 'Arial',
        'font-size' => '10pt',
    ],
    'paragraph' => [
        'line-height' => '110%',
        'margin-bottom' => '0.08cm',
    ],
    'page' => [
        'margin-top' => '1cm',
        'margin-right' => '1cm',
        'margin-bottom' => '1cm',
        'margin-left' => '1cm',
    ],
]);
```

This example is illustrative only; the public API is not fixed by this roadmap entry.

The important semantics are:

```text
document defaults
      ↓
element-specific style
      ↓
explicit local override
```

The resulting values should be resolved in a document-scoped context rather than through new process-wide static defaults.

This milestone should be coordinated with ARCH-02 and STYLE-CONTEXT-01 so that default settings have a natural document owner. It should not be implemented by adding more global state to `StyleMapper`.

The long-term purpose is larger than convenience: document-wide defaults are a prerequisite for treating the engine as a coherent document composition system and for allowing multiple renderers to consume the same visual/document semantics later.

### STYLE-CONTEXT-01 — Document-scoped style context

Replace process-wide explicit style registration with document-scoped state while preserving compatibility with existing public registration behavior where practical.

This work should be coordinated with the architectural extraction rather than implemented as an isolated static reset.

Related backlog:

- `STYLE-CONTEXT-01`
- `STYLE-API-02`

### ASSET-CONTEXT — Document-scoped asset lifecycle

Clarify ownership of images, importer-created temporary assets, package assets, and manifest updates.

This work should support both normal request/CLI execution and future long-running worker scenarios.

Related backlog:

- `TEMP-ASSET-01`
- `SAMPLE-INFRA-01`

## Phase C — Page structure and document composition

This phase introduces concepts that are larger than individual text or table elements.

### DOC-STRUCTURE-01 — Page styles and master pages

Goal: support explicit document/page-style concepts rather than treating page layout only as margin changes.

Future use cases include:

- first-page layout different from following pages;
- different page styles for document sections;
- controlled master-page transitions;
- page-style-aware headers and footers.

### DOC-STRUCTURE-02 — Header and footer content

Goal: provide a reliable model for dynamic header/footer content tied to page styles or master pages.

A representative target use case is:

```text
Page 1
└── Header A

Page 2+
└── Header B
```

This should be designed as ODF page/master-page behavior, not as a visual workaround using normal body tables or text boxes.

### DOC-STRUCTURE-03 — Sections and explicit page breaks

Investigate higher-level document structures such as:

- sections;
- explicit page breaks;
- keep-with-next;
- keep-together;
- controlled page-style changes.

The API should express semantic document intent rather than manually manipulating XML from application code.

## Phase D — Layout-critical existing features

Once the architecture provides clear ownership, revisit existing layout limitations.

### Tables

Priority work:

- reliable explicit table width;
- reliable physical column widths;
- explicit row height/minimum row height;
- improved relative width distribution;
- vertical cell alignment.

Related backlog:

- `TABLE-LAYOUT-01`
- `TABLE-LAYOUT-02`
- `TABLE-LAYOUT-03`
- `TABLE-LAYOUT-04`
- `TABLE-CELL-01`

### Frames and images

Priority work:

- unified frame positioning model;
- reliable text-box positioning;
- reliable image anchors, wrapping, and positioning.

Related backlog:

- `FRAME-LAYOUT-01`
- `FRAME-LAYOUT-02`
- `IMAGE-LAYOUT-01`

### Lists

Priority work:

- explicit indentation controls;
- improved nested-list style control.

Related backlog:

- `LIST-LAYOUT-01`
- `LIST-LAYOUT-02`

## Phase E — Shared document model and additional renderers

This is a longer-term direction and should not be started before the earlier responsibilities are sufficiently separated.

A possible future architecture is a semantic document model consumed by more than one renderer:

```text
Application data
      ↓
Document model
      ├── Paragraph
      ├── List
      ├── Table
      ├── Image
      ├── Section
      ├── PageBreak
      ├── Header / Footer
      └── PageStyle
          │
          ├── ODT renderer
          └── HTML renderer
```

The important objective is not to make ODT behave like HTML. It is to allow ODT output and browser previews to consume the same document semantics where that is useful.

### HTML preview and pagination

A browser preview may eventually need page-aware rendering.

This should not be implemented as a simplistic content counter. Actual pagination depends on rendered height, fonts, line-height, paragraphs, tables, images, margins, and header/footer geometry.

A future HTML renderer should therefore distinguish:

- semantic page-control hints from the document model;
- browser-side measurement for preview pagination;
- ODF-native pagination behavior in the ODT renderer.

Exact cross-renderer page equivalence should not be promised unless it can be demonstrated reliably.

## Development principles

The following principles apply across all phases:

1. Preserve the current public API unless an explicit migration plan exists.
2. Prefer composition over adding more responsibilities to large inheritance-based classes.
3. Prefer native ODF structures over visual simulations.
4. Keep LibreOffice responsible for stable document design where appropriate.
5. Use PHP for dynamic structure, data, and application-driven variation.
6. Do not add abstractions only to reduce file size; extract real responsibilities.
7. Every architectural extraction must keep the public sample suite green.
8. Validate non-trivial ODF changes with tests, package/XML inspection, and LibreOffice.
9. Document known limitations rather than presenting uncertain layout behavior as guaranteed functionality.
10. Treat `FUTURE_DEVELOPMENT.md` as the issue backlog and this roadmap as the sequencing document.
11. Prefer document-scoped defaults and state over process-wide configuration.
12. Explicit element settings must override document defaults predictably.

## Immediate next milestone

The next recommended development milestone is:

> **ARCH-04 — Extract template-language processing**

ARCH-03 completed the technical ODT document-layer audit and the low-coupling
metadata/page-layout extraction. The next milestone should characterize and
extract template-language processing while preserving the existing public
workflow and legacy/alternative condition and repeating paths.

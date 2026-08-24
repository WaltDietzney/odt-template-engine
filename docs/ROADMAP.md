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

### ARCH-01 — Responsibility audit

Goal: map every responsibility currently located in `AbstractOdtTemplate`, `OdtTemplate`, and `PageLayoutOdtTemplate` before moving code.

The audit should classify methods and state by responsibility, including:

- ODT package lifecycle;
- temporary workspace management;
- XML document access;
- template-language rendering;
- element insertion;
- styles;
- metadata;
- images and assets;
- manifest handling;
- page layout;
- compatibility helpers;
- debugging/internal utilities.

No code movement should happen before this map exists.

### ARCH-02 — Extract ODT package / document context

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

Possible concepts include `OdtPackage` and/or a document-scoped context object. Final names and APIs should follow the ARCH-01 findings rather than being fixed in advance.

Public `OdtTemplate` usage should remain unchanged during this extraction.

### ARCH-03 — Extract template-language processing

Goal: move variables, filters, conditions, and repeating-block processing behind a focused renderer/processor responsibility.

The public workflow should remain compatible:

```php
$template->assign(...);
$template->assignRepeating(...);
$template->render();
```

### ARCH-04 — Extract structured element insertion

Goal: isolate the DOM-specific work required to place `OdtElement` structures into `content.xml` and other valid document regions.

This should create a clearer home for placeholder lookup, replacement, and structured content insertion without coupling that code to ZIP or metadata behavior.

### ARCH-05 — Reassess `AbstractOdtTemplate`

After the package, renderer, and insertion responsibilities have been extracted, reassess whether `AbstractOdtTemplate` still represents a meaningful abstraction.

A possible long-term outcome is removal of the abstract base class in favor of composition, but this is not a requirement. The decision should be based on the remaining responsibilities after extraction.

## Phase B — Document-scoped style and asset state

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

## Immediate next milestone

The next recommended development milestone is:

> **ARCH-01 — Responsibility & Future Document Model Audit**

It should produce a concrete responsibility map for `AbstractOdtTemplate`, `OdtTemplate`, and `PageLayoutOdtTemplate`, identify extraction boundaries, and verify that the proposed architecture can accommodate page styles, dynamic headers/footers, sections, and future renderer/pagination requirements before implementation begins.

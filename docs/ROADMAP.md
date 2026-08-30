# Development Roadmap

This roadmap describes the planned development direction of the ODT Template Engine beyond the current stable public baseline.

It complements [`FUTURE_DEVELOPMENT.md`](FUTURE_DEVELOPMENT.md):

- `FUTURE_DEVELOPMENT.md` is the issue-oriented backlog of known limitations, technical debt, research topics, and possible improvements.
- `ROADMAP.md` defines the strategic sequence in which larger architectural and product work should be approached.

The roadmap is intentionally conservative about public API changes. Existing application-facing APIs should remain stable where practical, and future APIs shown here are conceptual unless explicitly documented as implemented.

## Current baseline

The project has moved beyond treating ODT primarily as text with placeholders. The current `develop` line combines the classic template language with an increasingly addressable structured document model.

Established capabilities include:

- variable replacement, filters, conditions, and repeating blocks;
- native rich text, paragraphs, lists, tables, images, frames, and text boxes;
- HTML import;
- document metadata and page-layout adjustments;
- package/document ownership through `OdtPackage` and `OdtDocumentContext`;
- composed services behind the public `OdtTemplate` facade;
- typed resolution of native template targets;
- named section and bookmark inspection;
- exact native section cloning and deterministic identity rewriting;
- data-bound section instantiation;
- nested section ownership and nested collection instantiation;
- collection lifecycle semantics in which authoring prototypes are removed from final output;
- structure-aware template-expression inspection, normalization, and replacement;
- preservation of authored whitespace and fragmented styled expressions in the supported scalar-expression path;
- a complete CV showcase proving the structured-section model against a realistic LibreOffice-authored document;
- practical template-authoring and named-section documentation.

The rendering-sensitive validation workflow remains:

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

Generated sample outputs and visual baselines are validation artifacts. In particular, locally modified files under `samples/output/` are not normal source changes and must not be committed, restored, deleted, or regenerated unless a task explicitly concerns those artifacts.

## Development branch model

The repository uses `master` as the conservative stable/public line and `develop` as the integration branch for architectural development.

Feature, architecture, refactoring, and documentation work should normally branch from `develop` and return to `develop` through focused pull requests.

```text
master
  │
  └── stable / public baseline

 develop
  │
  ├── architecture/*
  ├── refactor/*
  ├── feature/*
  ├── product/*
  └── docs/*
```

A larger development milestone should reach `master` only after the relevant automated tests, public sample smoke tests, representative generated ODT documents, and LibreOffice regression checks are green.

## Completed foundation

### Phase A — Architecture and responsibility boundaries — COMPLETE

ARCH-01 through ARCH-07 established the structural foundation of the engine.

The resulting active structure is centered on composition:

```text
OdtTemplate
├── OdtPackage
│   └── OdtDocumentContext
├── TemplateProcessor
├── StructuredElementMaterializer
├── TemplateTargetResolver
├── MetadataManager
├── PageLayoutManager
└── temporary style/finalization helpers

PageLayoutOdtTemplate extends OdtTemplate
```

Important outcomes include:

- package/workspace ownership is separated from template-language processing;
- document DOM ownership is explicit;
- metadata and page-layout responsibilities are extracted;
- template-language processing is behind `TemplateProcessor`;
- constructed ODF materialization is separated from existing native template-object resolution;
- `AbstractOdtTemplate` has been removed from the active architecture;
- historical state mirrors were removed;
- style/finalization state remains the main known temporary architectural boundary.

The detailed ARCH-01 through ARCH-07 audit, characterization, change-contract, and closeout documents remain the authoritative history for those decisions.

### Product / structured-document milestone — COMPLETE

The PRODUCT-01 / SECTION-03 development cycle established a practical addressable document model on top of the architecture foundation.

The completed milestone includes:

- non-mutating template-structure inspection;
- native named section discovery and typed section targets;
- bookmark/range understanding and identity handling;
- exact native section cloning;
- deterministic rewriting of section, bookmark, table, frame, and template-expression identities where required by instantiation;
- local data binding inside cloned section ownership boundaries;
- nested section lookup and nested instantiation;
- collection instantiation with terminal prototype removal;
- transactional behavior and rollback for collection operations;
- structure-preserving scalar expression replacement;
- safe normalization of proven repairable fragmented expressions;
- preservation of ODF whitespace and authored literal spaces;
- practical authoring guidance;
- the Sample 25 complete CV showcase.

This milestone changes the starting point for future work: sections are no longer merely a possible future document-structure primitive. They are an implemented structured-template capability with defined cloning, instantiation, nesting, and collection semantics.

## Strategic development direction

The next phase is not a feature checklist. The engine should gain capabilities in layers while preserving native ODF semantics and keeping LibreOffice useful as the visual template designer.

The current preferred sequence is:

```text
STYLE-CONTEXT-01
        ↓
DOCUMENT-DEFAULTS-01
        ↓
layout-critical ODF capabilities
        ├── FRAME-LAYOUT-01
        └── TABLE-LAYOUT-* / TABLE-CELL-01
        ↓
template authoring / format-preservation re-audit
        ↓
higher document structure and page flow
        ↓
named-object operations and dynamic content
        ↓
document import / round-trip workflows
```

This order is a planning preference, not a promise that every milestone must be completed before any smaller independent slice can proceed. Findings from one milestone may justify reordering later work.

## Phase B — Document-scoped style, defaults, and asset state

### STYLE-CONTEXT-01 — Document-scoped style context — PREFERRED NEXT ARCHITECTURE BLOCK

Goal: replace process-wide explicit style-registration state with document-scoped ownership while preserving compatibility where practical.

This work is now preferred before `DOCUMENT-DEFAULTS-01` because document defaults should not be built on top of mutable process-wide style state.

Expected investigation and implementation slices include:

1. audit current explicit style-registration and finalization paths;
2. characterize cross-document behavior and compatibility surfaces;
3. define document ownership and lifecycle semantics;
4. introduce an internal document-scoped style context;
5. integrate element/materializer/finalization paths incrementally;
6. preserve legacy `StyleMapper` entry points through compatibility facades where justified;
7. add multi-document regression coverage.

Do not solve this by resetting static state in constructors. The goal is ownership, not a timing-dependent cleanup workaround.

Related backlog:

- `STYLE-CONTEXT-01`
- `STYLE-API-02`

### DOCUMENT-DEFAULTS-01 — Document-level default settings

Goal: allow applications to define document-wide defaults once instead of repeating common style and layout options on every generated element.

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

The important semantic order is:

```text
document defaults
      ↓
element-specific style
      ↓
explicit local override
```

The exact public API remains undecided. Defaults must have a natural document owner and must not introduce new process-wide global state.

### ASSET-CONTEXT — Document-scoped asset lifecycle

Clarify ownership of images, importer-created temporary assets, package assets, and manifest updates.

This work should support both normal request/CLI execution and future long-running worker scenarios.

Related backlog:

- `TEMP-ASSET-01`
- `SAMPLE-INFRA-01`

## Phase C — Layout-critical ODF capabilities

Professional documents expose layout gaps quickly. CVs remain an important practical benchmark, but the capabilities must be generic ODF features rather than CV-specific shortcuts.

### FRAME-LAYOUT-01 — Unified frame positioning

Goal: define consistent frame semantics across images, text boxes, and other drawing content.

Research must start from real LibreOffice-authored ODF and existing engine behavior. Topics include:

- anchor types;
- horizontal and vertical position;
- position relation/reference area;
- wrapping;
- size;
- existing-template frame mutation versus constructed frames;
- interoperability with LibreOffice and Word round-trips where relevant.

`ImageElement` and `DrawTextBox` should not evolve separate incompatible positioning models.

### TABLE-LAYOUT — Reliable table geometry

Priority topics are:

- `TABLE-LAYOUT-02` — explicit/reliable column widths;
- `TABLE-LAYOUT-01` — explicit table width;
- `TABLE-LAYOUT-04` — reliable relative width;
- `TABLE-LAYOUT-03` — row/minimum height;
- `TABLE-CELL-01` — vertical cell alignment.

The existing table-column/style path must be investigated before introducing a new public API. Workarounds should not be mistaken for native ODF semantics.

### Smaller layout topics

The following can be taken as bounded slices when useful:

- `LIST-LAYOUT-01` — list indentation;
- `LIST-LAYOUT-02` — nested list style control;
- `IMAGE-LAYOUT-01` — image-specific anchor/wrap/position behavior not already absorbed by the frame model.

## Phase D — Template authoring and format preservation

### TEMPLATE-FORMAT-PRESERVATION-01 — RE-AUDIT REQUIRED

The original backlog item predates the completed template-structure work.

Scalar expressions split across compatible or differently styled text nodes, bookmark markers, and authored whitespace are no longer accurately described as a generally unsolved problem. The completed projector, inspector, safe normalizer, and replacement service changed that baseline substantially.

Before further implementation, re-audit the remaining active template-language paths, especially:

- conditions;
- foreach/control blocks;
- `nl2br`;
- `ul` / `ol` structural placeholders;
- expressions interacting with more complex ODF boundaries.

Do not reopen already solved scalar-expression behavior without evidence.

### TEMPLATE-AUTHORING-UX-01 — Research and design

Goal: make powerful templates understandable and practical to author in LibreOffice without turning the template syntax into a layout language.

Research topics include:

- naming conventions for structured template objects;
- template validation and diagnostics;
- inspection tooling;
- clear distinction between simple template expressions and structured objects;
- discoverability of section/bookmark/frame/table capabilities;
- authoring guidance for fixed-layout versus flow-based content;
- realistic maximum-content testing for professional templates.

This milestone should begin as research/design. It must not assume that a new template syntax is required.

## Phase E — Higher document structure and page flow

### DOC-STRUCTURE-01 — Page styles and master pages

Support explicit page/master-style concepts rather than treating page layout only as isolated attribute changes.

### DOC-STRUCTURE-02 — Headers and footers

Support header/footer content tied to page/master styles.

### DOC-STRUCTURE-03 — Page-flow semantics

The section portion of the older roadmap entry has been superseded by the completed structured-section milestone.

Future work under this heading should focus on remaining page-flow semantics such as:

- page breaks;
- keep-with-next;
- keep-together;
- page-style transitions;
- interactions between structured content and page/master styles.

Any further section work should extend proven section semantics rather than restarting section discovery from scratch.

## Phase F — Named object operations and dynamic content

### NAMED-OBJECT-OPERATIONS-01 — Research direction

The addressable section model suggests a broader future capability for native named template objects.

The important semantic distinction is between operations, not a single universal `replaceElementByName()` method:

```text
replace content
    → preserve the authored container/layout where possible

replace object
    → replace the complete native object

clone
    → duplicate according to object-specific identity semantics

remove
    → remove the addressed object safely
```

Potential addressable object families include frames, text boxes, tables, images/drawing objects, and other native objects for which ODF semantics and LibreOffice authoring provide a stable identity.

The public API is intentionally undecided. Typed targets and object-specific capabilities are preferred over an unbounded generic operation that assumes every ODF object supports every mutation.

### DYNAMIC-CONTENT-01 — Graphs, QR codes, and generated graphics

Dynamic graphics are a use case of named-object/content replacement, not yet a separate document architecture.

Potential content types include:

- generated images;
- circular/profile images;
- QR codes;
- charts and graphs;
- small infographics.

For charts/graphs, the rendering strategy remains deliberately open:

- SVG;
- PNG;
- native ODF chart structures.

Before choosing native ODF charts, inspect real LibreOffice-authored chart packages and round-trip behavior. Do not implement a chart model from specification assumptions alone.

A desirable design direction is that LibreOffice owns position, size, frame style, and surrounding page layout while PHP supplies dynamic content.

## Phase G — Import, round-trip, and shared document models

### DOCUMENT-IMPORT-01 — Engine document identification and structured data extraction

Future workflow:

```text
engine-generated ODT
        ↓
identify engine/schema metadata
        ↓
inspect known structured objects
        ↓
reconstruct application data
        ↓
select another template
        ↓
render again
```

Identification and integrity are separate concerns. A full-file hash is not a suitable identity mechanism for documents that may be opened and saved by LibreOffice.

No public import API is approved yet.

### Shared semantic document model / additional renderers — Later

A future renderer-independent semantic layer may eventually describe concepts such as:

- Paragraph;
- List;
- Table;
- Image;
- Section;
- PageBreak;
- Header/Footer;
- PageStyle.

ODT and HTML could then consume the same semantic input where that is genuinely useful. This remains a later direction and must not force premature abstraction into the current ODT model.

## Immediate next milestone

The preferred next architecture milestone is `STYLE-CONTEXT-01`.

Before implementation:

1. inspect the current `StyleMapper`, `StyleWriter`, element style registration, and finalization paths;
2. identify active, legacy, and compatibility behavior;
3. add or confirm characterization tests for cross-document style leakage;
4. define document ownership and lifecycle semantics;
5. document a change contract;
6. implement in small slices;
7. run focused and full automated validation;
8. perform LibreOffice regression checks for rendering-sensitive changes.

`DOCUMENT-DEFAULTS-01` follows naturally once document-scoped style ownership is sound.

The exact ordering of later FRAME, TABLE, authoring, document-structure, and named-object milestones should be revisited after each major architecture block rather than treated as immutable.

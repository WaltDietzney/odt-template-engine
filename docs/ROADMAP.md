# Development Roadmap

This roadmap describes the strategic development direction of the ODT Template Engine beyond the current stable public baseline.

It complements [`FUTURE_DEVELOPMENT.md`](FUTURE_DEVELOPMENT.md):

- `FUTURE_DEVELOPMENT.md` is the issue-oriented backlog of known limitations, technical debt, research topics, and possible improvements.
- `ROADMAP.md` defines the strategic sequence in which larger architectural and product work should be approached.

The roadmap is intentionally conservative about public API changes. Existing application-facing APIs should remain stable where practical, and future APIs shown here are conceptual unless explicitly documented as implemented.

The current sequencing incorporates the decision recorded in [`architecture/ROADMAP_REFRESH_02_POST_SR05_ARCHITECTURE_REASSESSMENT.md`](architecture/ROADMAP_REFRESH_02_POST_SR05_ARCHITECTURE_REASSESSMENT.md) and the SR-06 closeout recorded in [`architecture/SR-06_SEMANTIC_GRAPHIC_STYLE_REQUIREMENTS_CLOSEOUT.md`](architecture/SR-06_SEMANTIC_GRAPHIC_STYLE_REQUIREMENTS_CLOSEOUT.md).

## Current baseline

The project has moved beyond treating ODT primarily as text with placeholders. The current `develop` line combines the classic template language with an increasingly addressable structured document model and a document-local semantic dependency model.

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
- data-bound section instantiation, nesting, collections, and prototype removal;
- structure-aware template-expression inspection, normalization, and replacement;
- preservation of authored whitespace and fragmented styled expressions in the supported scalar-expression path;
- semantic structured-element ownership through `ownedElements()`;
- transitive style-requirement and physical image-resource discovery;
- document-local style ownership through `StyleContext`;
- semantic `StyleRequirement` values with definition/reference, ODF family, common/automatic scope, document-part ownership, parent dependency, and typed property groups;
- semantic paragraph/text and graphic style materialization;
- document-local font-face dependency discovery, resolution, conflict handling, and materialization;
- document-local fill-image dependency discovery, declaration materialization, and package-owned resource preparation;
- compatibility adoption of legacy image, fill-image, and frame registrations only when referenced by the current document;
- a complete CV showcase proving the structured-section model against a realistic LibreOffice-authored document.

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
visual review against known-good baseline
```

Generated sample outputs and visual baselines are validation artifacts. Locally modified files under `samples/output/` are not normal source changes and must not be committed, restored, deleted, or regenerated unless a task explicitly concerns those artifacts.

## Development branch model

The repository uses `master` as the conservative stable/public line and `develop` as the integration branch for architectural development.

Feature, architecture, refactoring, and documentation work should normally branch from `develop` and return to `develop` through focused pull requests.

A larger development milestone should reach `master` only after the relevant automated tests, public sample smoke tests, representative generated ODT documents, and LibreOffice regression checks are green.

## Completed foundation

### Phase A — Architecture and responsibility boundaries — COMPLETE

ARCH-01 through ARCH-07 established the structural foundation of the engine.

The active architecture is composition-first:

```text
OdtTemplate
├── OdtPackage
│   └── OdtDocumentContext
├── TemplateProcessor
├── StructuredElementMaterializer
├── TemplateTargetResolver
├── MetadataManager
├── PageLayoutManager
└── document-local dependency/finalization collaborators
```

Important outcomes include explicit package/document ownership, extracted template-language processing, metadata and page-layout services, structured materialization separated from native target resolution, and removal of `AbstractOdtTemplate` from the active architecture.

### Product / structured-document milestone — COMPLETE

PRODUCT-01 / SECTION-03 established a practical addressable document model based on native LibreOffice/ODF structures.

The completed milestone includes native section discovery, typed targets, exact cloning, deterministic identity rewriting, local data binding, nested instantiation, collection lifecycle semantics, rollback, structure-preserving scalar replacement, ODF whitespace preservation, authoring guidance, and the Sample 25 CV showcase.

Sections are therefore an implemented structured-template primitive, not merely a future design direction.

### STYLE-CONTEXT / semantic requirement foundation — ADVANCED, NOT YET CLOSED

The original roadmap described `STYLE-CONTEXT-01` as the preferred next architecture block. That description is obsolete.

Completed work now includes:

- document-local `StyleContext` ownership and reset semantics;
- compatibility isolation of legacy static registration paths;
- one semantic ownership tree for composite structured elements;
- conflict-preserving transitive requirement collection;
- transitive physical image-resource discovery and package-owned preparation;
- semantic `StyleRequirement` representation;
- semantic paragraph/text producers and materialization;
- semantic graphic-style producers, resolution, and materialization;
- document-local fill-image dependency discovery and declaration materialization;
- preservation of already-native ODF properties;
- document-local font-face dependency handling;
- document-reference-based compatibility adoption for legacy image, fill-image, and frame state.

D5C through D5E and SR-01 through SR-06 are accepted architecture baseline. SR-06 is COMPLETE / FINAL GO.

The remaining work is not to invent document-local style ownership again. It is to migrate the table-related structured style families needed by insertion, then simplify lifecycle/finalization around the coherent semantic model.

## Immediate architecture sequence

The preferred sequence is now:

```text
SR-07 Semantic Table / Table-Cell Requirements — COMPLETE
        ↓
D5F Lifecycle / Materialization Integration — COMPLETE
        ↓
D5G Compatibility Closeout — COMPLETE
        ↓
STYLE-CONTEXT-01 final closeout
```

D5F has established the authoritative pre-materialization semantic/resource
path and bounded post-materialization compatibility adoption. D5G has now
completed the evidence-based compatibility closeout while retaining public
static registries and legacy lifecycle facades. `STYLE-CONTEXT-01` is the next
architecture closeout; `OdtTemplate` must not be normalized beyond the accepted
lifecycle boundary without new evidence.

### SR-06 — Semantic Graphic Style Requirements — COMPLETE / FINAL GO

SR-06 migrated structured graphic-style semantics into the semantic `StyleRequirement` model while keeping drawing structure, placement, geometry, fill-image dependencies, and physical resources distinct.

Completed outcomes include:

- ODF `graphic` family definitions/references as semantic requirements;
- semantic graphic producers for supported structured drawing elements;
- document-local resolution and materialization;
- a dedicated document-local fill-image dependency model and declaration materializer;
- compatibility narrowing for legacy frame, image, and fill-image registries based on current-document references;
- preservation of public/static compatibility APIs and protected lifecycle hooks;
- manual LibreOffice visual-regression FINAL GO.

SR-06 deliberately did not redesign frame positioning, image anchor/wrap APIs, table layout, or the public layout model. The closeout is recorded in [`architecture/SR-06_SEMANTIC_GRAPHIC_STYLE_REQUIREMENTS_CLOSEOUT.md`](architecture/SR-06_SEMANTIC_GRAPHIC_STYLE_REQUIREMENTS_CLOSEOUT.md).

### SR-07 — Semantic Table / Table-Cell Requirements — COMPLETE / FINAL GO

SR-07 completed the semantic table-family migration and compatibility closeout
for structured insertion. The completed families are `table`, `table-column`,
`table-row`, and `table-cell`, with document-local semantic ownership and
preserved legacy compatibility facades.

SR-07H completed the visual closeout for Samples 11, 13, 19, and 20 and the
focused `min-row-height` proof. Sample 11's corrected explicit widths and
Sample 20's corrected relative-width semantics are intentional behavior
corrections, not baseline regressions. The distinction between table width,
absolute/relative column width, and structural repetition/spans remains
important evidence for later table-layout work.

### D5F / D5G — lifecycle integration and compatibility closeout — COMPLETE

D5F completed the lifecycle/orchestration integration around the coherent
semantic model without removing compatibility paths. D5G completed the review
of protected extension surfaces, repeated render/save behavior, and remaining
legacy registration/finalization behavior. Static registries and direct
compatibility defaults remain documented residue for `STYLE-CONTEXT-01`.

## Document defaults — RESEARCH/DESIGN AFTER STYLE-CONTEXT CLOSEOUT

`DOCUMENT-DEFAULTS-01` remains an important product goal, but it is no longer the immediate implementation step.

The ODF/LibreOffice research shows that "document defaults" can refer to distinct mechanisms:

- ODF `style:default-style`;
- LibreOffice Default Paragraph Style / `Standard`;
- authored named base styles;
- application-level LibreOffice basic-font defaults;
- page-layout defaults.

These mechanisms must not be collapsed into one API merely because they appear as defaults to an application developer. The exact public API remains undecided.

The FONT-02 and FONT-03 empirical cases cited by the SR-05 contract also require a bounded reference-fixture reconciliation because corresponding locally created ODT fixtures are not currently part of the versioned reference-fixture baseline.

## Layout-critical ODF capabilities

Professional documents expose layout gaps quickly. CVs remain an important practical benchmark, but capabilities must remain generic ODF features rather than CV-specific shortcuts.

### FRAME-LAYOUT-01 — Unified frame positioning

Goal: define consistent frame semantics across images, text boxes, and other drawing content.

Research must start from real LibreOffice-authored ODF and existing engine behavior. Topics include anchor types, horizontal/vertical position, relation/reference area, wrapping, size, existing-template frame mutation, constructed frames, and interoperability where relevant.

SR-06 has established the semantic graphic-style foundation. FRAME-LAYOUT-01 remains a separate public/layout design problem and must not be retroactively folded into SR-06.

### TABLE-LAYOUT — Reliable table geometry

Priority topics remain:

- `TABLE-LAYOUT-02` — explicit/reliable column widths;
- `TABLE-LAYOUT-01` — explicit table width;
- `TABLE-LAYOUT-04` — reliable relative width;
- `TABLE-LAYOUT-03` — row/minimum height;
- `TABLE-CELL-01` — vertical cell alignment.

SR-07 has established the relevant semantic style foundation. These remain
separate behavior/API topics and are not implicitly solved by SR-07.

### Smaller layout topics

Bounded future slices include `LIST-LAYOUT-01`, `LIST-LAYOUT-02`, and image-specific behavior not absorbed by the unified frame model.

## Template authoring and format preservation

### TEMPLATE-FORMAT-PRESERVATION-01 — RE-AUDIT REQUIRED

The completed template-structure work changed the baseline substantially. Scalar expressions split across inline structure and authored whitespace are no longer accurately described as a generally unsolved problem.

Future work begins with a fresh audit of remaining paths, especially conditions, foreach/control blocks, `nl2br`, `ul` / `ol` structural placeholders, and complex ODF boundaries. Do not reopen solved scalar behavior without evidence.

### TEMPLATE-AUTHORING-UX-01 — Research and design

LibreOffice should remain the visual template designer where practical. Research should focus on naming conventions, validation/diagnostics, inspection tooling, discoverability of structured objects, flow versus fixed-layout guidance, and realistic maximum-content testing. No new template syntax is implied.

## Higher document structure and page flow

Future major blocks remain:

- `DOC-STRUCTURE-01` — explicit page/master-style concepts;
- `DOC-STRUCTURE-02` — headers and footers associated with page/master styles;
- `DOC-STRUCTURE-03` — page breaks, keep-with-next, keep-together, page-style transitions, and structured-content interaction with page flow.

Existing `PageLayoutManager` behavior for mutation of established master/page-layout relationships remains a valid separate responsibility.

## Named object operations and dynamic content

`NAMED-OBJECT-OPERATIONS-01` remains a research direction building on the proven section model. Replace content, replace object, clone, and remove are distinct operations and should not be hidden behind an unbounded universal method.

Potential future targets include frames, text boxes, tables, images/drawing objects, and other native structures with stable identity and understood lifecycle semantics.

`DYNAMIC-CONTENT-01` remains a use case of this direction for generated images, QR codes, charts/graphs, and small infographics. LibreOffice should preferably own authored layout while PHP supplies dynamic content.

## Import, round-trip, and shared models

`DOCUMENT-IMPORT-01` remains later work for identifying engine/schema metadata, inspecting known structured objects, reconstructing application data, and rendering through another template.

A renderer-independent semantic document model also remains a later design direction and must not force premature abstraction into the current ODT model.

## Strategic sequence after STYLE-CONTEXT closeout

After the immediate SR-07 → D5F → D5G sequence, ordering remains evidence-driven rather than fixed:

```text
STYLE-CONTEXT-01 closeout
        ├── DOCUMENT-DEFAULTS-01 research/design
        ├── FRAME-LAYOUT-01
        ├── TABLE-LAYOUT-* / TABLE-CELL-01
        ├── template authoring / format-preservation re-audit
        ├── higher document structure and page flow
        ├── named-object operations / dynamic content
        └── import / round-trip work later
```

The governing principle remains:

> Semantics before implementation.

A capability belongs in the engine when its ODF semantics, ownership, lifecycle, compatibility impact, and authoring model are understood—not merely because an API can be invented for it.

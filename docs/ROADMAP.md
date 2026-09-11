# Development Roadmap

This roadmap describes the strategic development direction of the ODT Template Engine beyond the current stable public baseline.

It complements [`FUTURE_DEVELOPMENT.md`](FUTURE_DEVELOPMENT.md):

- `FUTURE_DEVELOPMENT.md` is the issue-oriented backlog of known limitations, technical debt, research topics, and possible improvements.
- `ROADMAP.md` defines the strategic sequence in which larger architectural and product work should be approached.

The roadmap is intentionally conservative about public API changes. Existing application-facing APIs should remain stable where practical, and future APIs shown here are conceptual unless explicitly documented as implemented.

The current sequencing incorporates the completed semantic/style architecture, the post-RESEARCH-01 version-1.0 reassessment recorded in [`architecture/RESEARCH-01_1_0_REASSESSMENT_DECISION.md`](architecture/RESEARCH-01_1_0_REASSESSMENT_DECISION.md), and the completed PAGE-FLOW-01 milestone.

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
- semantic paragraph/text, table-family, and graphic style materialization;
- document-local font-face and fill-image dependency handling;
- public document-style authoring through `DocumentStyles`, currently with `defineParagraph()`;
- stateless style-option mapping through `StyleMapper`;
- a narrow `StyleWriter` serialization boundary;
- native paragraph-flow authoring for keep/break/widow/orphan semantics through the existing style architecture;
- preservation of authored master-page succession, page-layout references, and paragraph-to-master-page references;
- page/master-style-owned header/footer content participating in normal template processing and supported structured insertion;
- Section flow preservation without engine-side pagination ownership;
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

### STYLE-CONTEXT-01 — COMPLETE / FINAL GO

The document-local semantic style foundation is complete.

Completed work includes document-local `StyleContext` ownership, one semantic ownership tree for composite structured elements, conflict-preserving transitive requirement collection, transitive physical image-resource discovery, semantic `StyleRequirement` representation, paragraph/text/table-family/graphic materialization, fill-image dependency handling, preservation of already-native ODF properties, and document-local font-face handling.

D5C through D5G and SR-01 through SR-07 are accepted architecture baseline.

### STYLE-API-02 — COMPLETE / FINAL GO

STYLE-API-02 aligned the public and compatibility style surfaces with the document-local semantic architecture.

The completed series established application authoring through element options/fluent APIs, explicit document-style authoring through `$template->styles()`, `DocumentStyles::defineParagraph()`, semantic structured-element extension hooks, stateless `StyleMapper`, a narrow `StyleWriter`, and removal of obsolete process-global paragraph/text style registry behavior.

### PAGE-FLOW-01 — COMPLETE / FINAL GO

PAGE-FLOW-01 established the native page/flow semantic boundary required for the 1.0 path.

Completed outcomes include:

- explicit paragraph-flow mappings for keep-with-next, keep-together, widows, orphans, break-before, and break-after;
- preservation of authored paragraph-to-master-page references, master-page succession, and page-layout references;
- preservation and processing of master-page-owned header/footer content through the existing cross-document-part model;
- supported page-owned image insertion through the established `setImage()` path;
- Section/repeated/nested content remaining structural while Writer owns physical pagination;
- empirical LibreOffice validation of first-page/following-page behavior and multi-page Section flow;
- an explicit boundary between page-style identity/content and page-layout geometry;
- no PHP pagination engine and no premature broad page-style authoring API.

Programmatic page-style definition/mutation/assignment remains the required future capability `PAGE-STYLE-AUTHORING-01`. The bounded `ImageElement` header discrepancy remains tracked as `GRAPHIC-PART-COMPAT-01`.

The accepted contract and completion evidence are recorded in [`architecture/PAGE_FLOW_01_CHANGE_CONTRACT.md`](architecture/PAGE_FLOW_01_CHANGE_CONTRACT.md) and the accompanying PAGE-FLOW-01 research/evidence documents.

## RESEARCH-01 — Native ODF Authoring Capabilities — STRATEGIC RESEARCH COMPLETE

RESEARCH-01 established enough empirical Writer/ODF evidence to reassess the path to version 1.0 without requiring a survey of the entire native ODF surface.

Important findings include:

- native Writer fields and conditional structures provide useful semantics but do not uniformly survive DOCX conversion;
- `{{...}}` remains a strong portable scalar-binding mechanism;
- native ODT structures can carry structural template meaning without forcing all value binding into Writer fields;
- Writer accepts semantic-looking Section names such as `#foreach:experience` unchanged;
- the existing SECTION-03 resolver and `instantiateMany()` machinery already support such a Section name mechanically;
- page styles, paragraph flow, Section flow, and export finalization are more fundamental to a coherent professional-document 1.0 than implementing every newly discovered native feature.

The evidence is recorded in:

- [`architecture/NATIVE_ODF_AUTHORING_RESEARCH.md`](architecture/NATIVE_ODF_AUTHORING_RESEARCH.md);
- [`architecture/RESEARCH-01A_DECLARATIVE_SECTION_FIXTURE.md`](architecture/RESEARCH-01A_DECLARATIVE_SECTION_FIXTURE.md);
- [`architecture/RESEARCH-01_1_0_REASSESSMENT_DECISION.md`](architecture/RESEARCH-01_1_0_REASSESSMENT_DECISION.md).

RESEARCH-01B through RESEARCH-01E remain available as research directions and are not claimed complete. Their relevant questions should be resumed from concrete milestones rather than blocking all further implementation.

## Version 1.0 target

ODT Template Engine 1.0 should reliably generate professional native ODT documents from LibreOffice-authored templates and/or structured PHP content, preserve or express the central native style, structure, layout, and page-flow semantics required by professional documents, and have a defined path to a finalized document state suitable for supported export workflows.

A professional multi-page CV remains the primary practical architecture benchmark. It is a generic-document stress test, not a request for CV-specific APIs.

## Mandatory path to version 1.0

The post-RESEARCH-01 sequence remains:

```text
PAGE-FLOW-01                 COMPLETE
    ↓
TABLE-LAYOUT-01              NEXT / ACTIVE
    ↓
FRAME-LAYOUT-01
    ↓
TEMPLATE-RELIABILITY-01
    ↓
FINALIZATION-01
    ↓
RELEASE-1.0 INTEGRATION PRE-FLIGHT
    ↓
1.0
```

The sequence is intentionally bounded. High-value but non-foundational features must not indefinitely delay 1.0.

### PAGE-FLOW-01 — Page styles, paragraph flow, and Section flow — COMPLETE / FINAL GO

PAGE-FLOW-01 is complete. Its accepted baseline is summarized above and defined in the PAGE-FLOW architecture/evidence documents. Future work must preserve its governing rule:

> **The engine describes or preserves native ODF flow semantics; LibreOffice/Writer computes actual pagination.**

The existing `PageLayoutManager` remains a valid narrow service for mutating selected properties of an existing master-page/page-layout relationship. It is not a complete page-style system.

### TABLE-LAYOUT-01 — Professional table geometry — 1.0 BLOCKER / NEXT ACTIVE MILESTONE

Treat the previously separate table-layout backlog items as one coherent 1.0 capability block:

- explicit table width;
- whole-table alignment / placement;
- absolute column widths;
- relative column widths;
- row/minimum height;
- vertical cell alignment.

SR-07 already established semantic ownership for the relevant table style families. This milestone should focus on ODF behavior, API semantics, and rendering reliability rather than reopening style ownership.

### FRAME-LAYOUT-01 — Reliable frame geometry core — 1.0 BLOCKER, BOUNDED SCOPE

Establish a coherent shared model for supported `draw:frame` content:

- anchor semantics;
- size;
- horizontal/vertical position;
- relation/reference area;
- fundamental wrap behavior;
- consistent semantics across images and text boxes;
- preservation/mutation of relevant LibreOffice-authored existing frames.

1.0 does not require every Writer drawing or positioning option.

### TEMPLATE-RELIABILITY-01 — Remaining template-format/control audit — 1.0 BLOCKER AS AUDIT

Re-audit the remaining paths after the completed structure-preserving scalar work:

- conditions;
- foreach/control structures;
- `nl2br`;
- `ul` / `ol` structural placeholders;
- complex ODF boundary interactions.

Do not reopen solved scalar behavior without evidence. If characterization finds no relevant defect, no implementation is required. Proven defects should receive characterization tests before bounded fixes.

### FINALIZATION-01 — Final document/export semantics — 1.0 BLOCKER AS ARCHITECTURE DECISION

Define what constitutes a finalized document state and how native semantic ODT content relates to interoperability-sensitive export.

The architecture must clarify data-binding completion, structured-instantiation completion, native semantic materialization where required, engine versus export-tool responsibility, repeated render/save behavior, and whether semantic source ODT and finalized static ODT are distinct lifecycle concepts.

1.0 does not require a universal engine-side evaluator for every Writer field or condition. It does require a coherent finalization/export contract.

### RELEASE-1.0 integration preflight

After the mandatory blocks, stop adding unrelated features and perform a dedicated release preflight covering automated tests, ODT/ZIP/XML integrity, public samples, LibreOffice headless rendering, visual regression, lifecycle/save-reopen behavior, PDF output, representative DOCX interoperability where promised, and a professional multi-page CV benchmark.

Automated tests do not replace manual LibreOffice visual regression for rendering-sensitive changes.

## High-value but non-blocking directions

The following remain valuable but do not block 1.0 by themselves:

### Declarative Section authoring

A future Writer Section such as `#foreach:experience` may act as a template-owned declarative frontend over the existing SECTION-03 `instantiateMany()` mechanics. Mechanical feasibility is proven; automatic discovery, lifecycle ordering, diagnostics, and syntax remain undecided.

### Native Writer fields and conditional content

User Fields, Set/Get Variables, Conditional Text, Hidden Text, Hidden Paragraphs, and Conditional Sections remain useful native capabilities. Broad public support is deferred until concrete authoring value and finalization/export semantics justify it.

### Document defaults

`DOCUMENT-DEFAULTS-01` remains useful research/design work, but it is no longer ahead of the mandatory table/frame/finalization path. Resume it earlier only if a mandatory milestone exposes a concrete dependency.

### Named object operations and dynamic content

Generalized replace-content/replace-object/clone/remove semantics for additional named native object families remain post-1.0 directions unless a mandatory milestone requires a bounded capability.

### Import, round-trip, and renderer-neutral models

`DOCUMENT-IMPORT-01`, broad round-trip reconstruction, and renderer-independent shared document models remain later work and must not force premature abstraction into the 1.0 native ODT core.

## Smaller independent follow-up

Bounded list-layout, lifecycle, sample-infrastructure, asset-context, temporary-asset, and reference-fixture work may be inserted where useful if it does not destabilize the mandatory 1.0 sequence.

## Governing principle

> Semantics before implementation.

A capability belongs in the engine when its ODF semantics, ownership, lifecycle, compatibility impact, and authoring model are understood—not merely because an API can be invented for it.

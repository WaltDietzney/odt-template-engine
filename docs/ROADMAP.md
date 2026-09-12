# Development Roadmap

This roadmap describes the strategic development direction of the ODT Template Engine beyond the current stable public baseline.

It complements [`FUTURE_DEVELOPMENT.md`](FUTURE_DEVELOPMENT.md):

- `FUTURE_DEVELOPMENT.md` is the issue-oriented backlog of known limitations, technical debt, research topics, and possible improvements.
- `ROADMAP.md` defines the strategic sequence in which larger architectural and product work should be approached.

The roadmap is intentionally conservative about public API changes. Existing application-facing APIs should remain stable where practical, and future APIs shown here are conceptual unless explicitly documented as implemented.

The current sequencing incorporates the completed semantic/style architecture, the post-RESEARCH-01 version-1.0 reassessment recorded in [`architecture/RESEARCH-01_1_0_REASSESSMENT_DECISION.md`](architecture/RESEARCH-01_1_0_REASSESSMENT_DECISION.md), the completed PAGE-FLOW-01, TABLE-LAYOUT-01, and FRAME-LAYOUT-01 milestones, and the subsequent decision to make template-driven authoring a mandatory part of the 1.0 product contract.

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

Programmatic page-style definition/mutation/assignment remains the required future capability `PAGE-STYLE-AUTHORING-01`. The former `GRAPHIC-PART-COMPAT-01` ImageElement header discrepancy was resolved by FRAME-LAYOUT-01 through correct frame carrier and insertion semantics.

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
TABLE-LAYOUT-01              COMPLETE
    ↓
FRAME-LAYOUT-01              COMPLETE
    ↓
TEMPLATE-AUTHORING-01
    ├── A — Existing Template Reliability & Format Preservation
    ├── B — Unified Template Inspection
    ├── C — Native Field Binding
    ├── D — Declarative Structural Controls
    ├── E — High-Level Render Pipeline
    └── F — Authoring Documentation & Samples
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

### TABLE-LAYOUT-01 — Professional table geometry — COMPLETE / FINAL GO

Treat the previously separate table-layout backlog items as one coherent 1.0 capability block:

- explicit table width;
- whole-table alignment / placement;
- absolute column widths;
- relative column widths;
- row/minimum height;
- vertical cell alignment.

SR-07 already established semantic ownership for the relevant table style families. This milestone should focus on ODF behavior, API semantics, and rendering reliability rather than reopening style ownership.

### FRAME-LAYOUT-01 — Reliable frame geometry core — COMPLETE / FINAL GO

FRAME-LAYOUT-01 is complete.

The accepted baseline establishes:

- one immutable `DrawingLayout` semantic authority shared by `DrawTextBox` and `ImageElement`;
- explicit separation of object geometry from graphic-layout style semantics;
- native `draw:frame` ownership for anchor, width/height, and explicit x/y coordinates;
- semantic graphic-style ownership for alignment, relation, wrap, flow-with-text, wrap influence, overlap, and appearance;
- a public `setFrameLayout()` master API plus approved convenience methods on both frame-backed element types;
- compatibility-preserving legacy setters and ImageElement autoscaling;
- anchor-sensitive structured insertion with preserved paragraph/text-flow carriers;
- cross-part body/header parity for structured `ImageElement` insertion;
- semantic style deduplication and repeated-save stability;
- LibreOffice-validated floating, offset, as-character, body, and header frame behavior.

The previously tracked `GRAPHIC-PART-COMPAT-01` discrepancy is resolved by this milestone: the root cause was structural insertion/carrier semantics, not a header prohibition or image-resource failure.

The accepted contract and completion evidence are recorded in
[`architecture/FRAME_LAYOUT_01C_CHANGE_CONTRACT.md`](architecture/FRAME_LAYOUT_01C_CHANGE_CONTRACT.md) and
[`architecture/FRAME_LAYOUT_01_CLOSEOUT.md`](architecture/FRAME_LAYOUT_01_CLOSEOUT.md).

Broader Writer drawing features, CustomShape architecture, and exhaustive positioning options remain outside the bounded 1.0 frame core.

### TEMPLATE-AUTHORING-01 — Template-driven authoring and rendering — 1.0 BLOCKER

Version 1.0 must support a coherent template philosophy in which a LibreOffice-authored ODT can carry not only layout and scalar placeholders, but also discoverable native structure and bounded declarative control. The existing imperative APIs remain supported; this milestone complements them with a higher-level template-driven path rather than replacing them.

The target application-facing workflow is conceptually:

```php
$schema = $template->inspect();
$template->render($mappedData);
$template->save('result.odt');
```

The exact public API remains subject to evidence, compatibility review, and a Change Contract. The product goal is nevertheless fixed: a generic application should be able to inspect a well-authored template, map application data to its declared inputs, and let the engine orchestrate supported rendering without rebuilding the document layout in PHP.

The milestone is divided into six bounded phases:

#### A — Existing Template Reliability & Format Preservation

Characterize the remaining classic template-language paths before changing them:

- conditions and elseif/else behavior;
- foreach/control structures;
- `nl2br`;
- `ul` / `ol` structural placeholders;
- paragraph, span, style, whitespace, bookmark, list, table, frame, and Section preservation at relevant ODF boundaries.

Previously solved scalar-expression preservation must not be reopened without evidence. Proven defects receive characterization tests before bounded fixes. This phase absorbs the former standalone `TEMPLATE-RELIABILITY-01` 1.0 blocker.

#### B — Unified Template Inspection

Design one read-only template inspection result that composes existing template-language inspection with native document inspection. It should be able to describe, where supported:

- scalar variables and filters;
- data dependencies of conditions;
- collection dependencies of repetitions;
- Sections;
- bookmarks;
- tables;
- frames;
- supported native fields;
- declarative controls;
- diagnostics, malformed declarations, duplicate identities, and unsupported constructs.

Existing `TemplateStructureInspector` and `DocumentInspector` are architectural inputs; do not create competing inspection models without evidence.

#### C — Native Field Binding

Define a bounded 1.0 native-field capability based on actual Writer/ODF semantics and RESEARCH-01 evidence. User Fields are a primary candidate; Set/Get Variable support and other field families require explicit justification.

Native fields complement `{{...}}` rather than replace the portable scalar placeholder path. Scope, identity, repeated rendering, finalization, and interoperability semantics must be understood before public API approval.

#### D — Declarative Structural Controls

Design native template structures as a declarative frontend over existing structural mechanics rather than as a second renderer. Writer Section names such as:

```text
#foreach:experience
#if:photo
#ifnot:photo
```

are the principal design direction already supported by RESEARCH-01A mechanical evidence.

Where condition expressions are supported, visible template syntax and native declarations should share one condition semantics instead of developing independent evaluators. Likewise, declarative repetition should orchestrate the established Section instantiation model.

Nesting, lifecycle order, missing-data behavior, diagnostics, prototype removal, identity rewriting, and compatibility must be specified before implementation.

#### E — High-Level Render Pipeline

Design a high-level render orchestration path over the existing lower-level APIs. It should coordinate supported structural controls, native binding, classic scalar/filter processing, structured insertion, and finalization boundaries in a deterministic order.

The existing imperative methods remain first-class APIs. A high-level `render($mappedData)` path is additive and intended to enable generic integrations such as CMS plugins, form-driven document generation, and other applications that can map their data to an inspected template contract.

#### F — Authoring Documentation & Samples

Version 1.0 requires first-class authoring guidance, not merely API reference. Documentation should explain:

- the template philosophy;
- simple template processing versus structured template processing;
- how to create inspectable LibreOffice templates;
- naming conventions for native objects and declarative controls;
- when to use `{{...}}`, native fields, Sections, bookmarks, tables, and frames;
- how generic data mapping and high-level rendering fit together;
- formatting-preservation and nesting constraints;
- diagnostics and validation;
- realistic complete examples.

The public samples should be reviewed before 1.0 and should demonstrate attractive, professionally authored templates rather than only technical feature fixtures.


### FINALIZATION-01 — Final document/export semantics — 1.0 BLOCKER AS ARCHITECTURE DECISION

Define what constitutes a finalized document state and how native semantic ODT content relates to interoperability-sensitive export.

The architecture must clarify data-binding completion, structured-instantiation completion, native semantic materialization where required, engine versus export-tool responsibility, repeated render/save behavior, and whether semantic source ODT and finalized static ODT are distinct lifecycle concepts.

1.0 does not require a universal engine-side evaluator for every Writer field or condition. It does require a coherent finalization/export contract.

### RELEASE-1.0 integration preflight

After the mandatory blocks, stop adding unrelated features and perform a dedicated release preflight covering automated tests, ODT/ZIP/XML integrity, public samples, LibreOffice headless rendering, visual regression, lifecycle/save-reopen behavior, PDF output, representative DOCX interoperability where promised, and a professional multi-page CV benchmark.

Automated tests do not replace manual LibreOffice visual regression for rendering-sensitive changes.

## High-value but non-blocking directions

The following remain valuable but do not block 1.0 by themselves:

### Broader declarative and native Writer semantics

The bounded declarative Section and native-field capabilities required by `TEMPLATE-AUTHORING-01` are now part of the mandatory 1.0 path. Broader operators, additional Writer field families, Conditional Text, Hidden Text, Hidden Paragraphs, Conditional Sections, and other native semantics remain later directions unless the milestone's evidence establishes a concrete 1.0 dependency.

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

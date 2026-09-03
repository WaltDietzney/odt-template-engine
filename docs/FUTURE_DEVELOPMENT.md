# Future Development

This document is the issue-oriented backlog for known limitations, architectural debt, research topics, and possible future capabilities of the ODT Template Engine.

It complements [`ROADMAP.md`](ROADMAP.md). The roadmap defines strategic sequencing; this file records individual topics without implying that every item is approved for immediate implementation.

The current planning baseline incorporates [`architecture/ROADMAP_REFRESH_02_POST_SR05_ARCHITECTURE_REASSESSMENT.md`](architecture/ROADMAP_REFRESH_02_POST_SR05_ARCHITECTURE_REASSESSMENT.md). Backlog descriptions must be interpreted against the current `develop` architecture rather than the earlier placeholder-centric or pre-SR-05 architecture.

## Style and dependency architecture

### STYLE-CONTEXT-01 — Document-scoped style context — ADVANCED / CLOSEOUT PENDING

**Priority:** High architecture closeout

The core ownership problem is no longer future work. The current baseline includes document-local `StyleContext` ownership, semantic structured-element traversal, conflict-preserving transitive requirement discovery, semantic `StyleRequirement` values, paragraph/text semantic materialization, and document-local font-face dependencies.

D5C–D5E and SR-01–SR-05 are accepted architecture baseline.

Remaining work is deliberately sequenced as:

```text
SR-06 Graphic Requirements
        ↓
SR-07 Table / Table-Cell Requirements
        ↓
D5F Lifecycle / Materialization Integration
        ↓
D5G Compatibility Closeout
        ↓
STYLE-CONTEXT-01 final closeout
```

Do not solve remaining transition complexity through constructor resets, process-global current-document state, or premature lifecycle abstraction.

### SR-06 — Semantic Graphic Style Requirements

**Priority:** Highest / preferred next architecture slice

Migrate graphic-style definitions and references used by structured elements from historical `frame` / `image` / related engine-role buckets into the semantic `StyleRequirement` model.

Required distinctions include:

- structural `draw:frame` attributes versus graphic-style properties;
- ODF `graphic` family versus engine producer/use roles;
- style definition/reference versus physical image resources;
- document-local style materialization versus package-owned resource preparation.

SR-06 must not redesign frame positioning, image anchor/wrap APIs, or package resource handling. It must not absorb `FRAME-LAYOUT-01` or unrelated rendering fixes.

Immediate first slice: **SR-06A — Graphic Requirement Audit and Characterization**, followed by a Change Contract before implementation.

### SR-07 — Semantic Table / Table-Cell Requirements

**Priority:** High after SR-06

Characterize and migrate the table-related style families required by structured insertion to the semantic requirement model.

Potential ODF families include:

- `table`;
- `table-column`;
- `table-row`;
- `table-cell`.

Style family and property group remain independent concepts. Do not combine this migration automatically with table geometry or cell-layout behavior changes.

### D5F — Lifecycle / materialization integration

**Priority:** High after SR-06/SR-07

D5F remains deliberately paused while structured insertion still combines semantic and legacy requirement worlds.

When the required style families have been migrated or explicitly bounded as compatibility behavior, D5F should simplify lifecycle/orchestration around the coherent semantic model. It must not centralize native element rendering in `OdtTemplate` or create a God renderer.

### D5G — Compatibility closeout

**Priority:** High after D5F

Before final STYLE-CONTEXT closeout, explicitly review:

- protected extension surfaces and external subclass compatibility;
- legacy style registration/finalization paths;
- repeated `render()` / `save()` behavior;
- content.xml / styles.xml compatibility behavior;
- remaining structured-value legacy paths.

### STYLE-API-02 — Style API consistency

**Priority:** Medium architectural/API debt

Reassess public style semantics only after the internal semantic ownership and family migration is complete. Do not combine public API redesign with SR-06/SR-07 unless a compatibility requirement makes it unavoidable.

## Document defaults and state

### DOCUMENT-DEFAULTS-01 — Document-level defaults — RESEARCH/DESIGN

**Priority:** High research after STYLE-CONTEXT closeout

The user-facing goal remains useful: applications should eventually be able to express appropriate document-wide defaults without repeating the same options on every element.

However, ODF/LibreOffice research shows that "defaults" may refer to distinct mechanisms:

- ODF `style:default-style`;
- LibreOffice Default Paragraph Style / `Standard`;
- authored named base styles;
- application-level LibreOffice basic-font defaults;
- page-layout defaults.

Do not invent one `setDefault...` API until these mechanisms and their precedence semantics are established. No public `setDefaultFont()` API is currently approved.

### FONT-REFERENCE-RECONCILIATION — FONT-02 / FONT-03 provenance

**Priority:** Bounded documentation/reference follow-up

The SR-05 contract cites FONT-02 and FONT-03 empirical cases, while corresponding locally created LibreOffice ODT fixtures are not currently part of the versioned reference-fixture baseline.

Inspect provenance and actual ODF structures, reconcile the case descriptions with the semantic reference matrix, and only then decide whether the fixtures belong in the repository. Do not use this task to introduce document-default behavior.

### TEMP-ASSET-01 — Temporary asset lifecycle

**Priority:** Low

Clarify cleanup and ownership of importer-created or generated temporary assets.

### ASSET-CONTEXT — Document-scoped asset lifecycle

**Priority:** Architectural follow-up

Clarify the relationship between generated assets, package assets, manifest updates, and temporary resources, especially for long-running worker processes. Preserve the established separation in which elements describe resources, collectors discover them, and `OdtPackage` owns physical package preparation.

### LIFECYCLE-API-01 — Lifecycle API clarity

**Priority:** Medium

Continue documenting and, where justified, simplifying lifecycle semantics around load/render/save/repeated operations without silently breaking compatible behavior. Coordinate with D5F/D5G rather than creating a competing lifecycle architecture.

## Layout and document rendering

### FRAME-LAYOUT-01 — Unified frame positioning

**Priority:** High after semantic graphic requirement foundation

Define a shared frame-positioning model for drawing content instead of allowing images and text boxes to evolve separate positioning semantics.

Research areas include anchor type, horizontal/vertical position, relation/reference area, wrap behavior, size, existing-template mutation, constructed frames, LibreOffice behavior, and Word round-trip behavior where relevant.

Use real LibreOffice-authored ODF as primary implementation evidence. SR-06 must remain a semantic style migration and must not be expanded into this layout API.

### FRAME-LAYOUT-02 — DrawTextBox positioning

**Priority:** Medium-high

Resolve `DrawTextBox` positioning through or consistently with `FRAME-LAYOUT-01`, not through an independent incompatible API.

### IMAGE-LAYOUT-01 — Image anchor, wrap, and position

**Priority:** High

Reassess after the unified frame model is understood. Image-specific behavior should not duplicate general frame semantics.

### TABLE-LAYOUT-02 — Explicit column widths

**Priority:** Very high

Reliable explicit column widths remain one of the most visible professional-layout limitations. Characterize the existing table-column/style path before introducing new APIs. Prefer beginning this product/layout work after SR-07 has clarified the semantic table-style foundation.

### TABLE-LAYOUT-01 — Explicit table width

**Priority:** High

Provide reliable explicit table-width semantics based on actual ODF behavior and LibreOffice output. Do not introduce an API until existing table-style paths are characterized.

### TABLE-LAYOUT-04 — Reliable relative table width

**Priority:** High

Investigate and support relative table sizing without relying on accidental LibreOffice behavior.

### TABLE-LAYOUT-03 — Row and minimum height

**Priority:** Medium-high

Support explicit row-height/minimum-height semantics where ODF and LibreOffice behavior permit reliable control.

### TABLE-CELL-01 — Vertical cell alignment

**Priority:** Medium

Support vertical alignment inside table cells through native ODF style semantics. Keep this behavioral capability separate from SR-07's architecture migration unless evidence requires otherwise.

### LIST-LAYOUT-01 / LIST-LAYOUT-02

**Priority:** Medium

Provide reliable list indentation and nested list style control using native list/paragraph semantics.

## Template structure and authoring

### TEMPLATE-FORMAT-PRESERVATION-01 — Re-audit remaining paths

**Priority:** Medium-high

The completed TEMPLATE-STRUCTURE work substantially changed this backlog item. Supported scalar expressions now have logical projection across transparent inline structure, non-mutating inspection, safe normalization, structure-preserving replacement, bookmark preservation, and ODF whitespace preservation.

Remaining work begins with a fresh audit of:

- conditions;
- foreach/control structures;
- `nl2br`;
- `ul` / `ol` structural placeholders;
- complex boundary interactions not covered by scalar replacement.

Unexpected legacy behavior should first be characterized rather than opportunistically changed.

### TEMPLATE-AUTHORING-UX-01 — Template authoring experience

**Priority:** Medium-high research/design

LibreOffice should remain the visual template designer where practical. Research topics include naming conventions, validation and diagnostics, inspection tooling, discoverability of structured capabilities, flow versus fixed-layout guidance, realistic maximum-content tests, and clearer separation between simple syntax and structured object operations.

No new template syntax is implied.

### HTML-IMPORT-01 — Extended HTML import

**Priority:** Later

Extend HTML import only where there is a concrete application need and semantics map cleanly to the structured ODT model.

## Structured document and named-object work

### NAMED-OBJECT-OPERATIONS-01 — Addressable native object operations

**Priority:** Future architectural/product direction

The completed section API demonstrates that native LibreOffice/ODF structures can act as addressable template objects when identity and lifecycle semantics are understood.

Investigate extending the model to additional native object families while preserving the distinction between:

```text
replace content
replace object
clone
remove
```

Potential targets include frames, text boxes, tables, images/drawing objects, and other stable named structures. Typed targets and capability-specific operations remain preferable to an unbounded universal replacement API.

### DYNAMIC-CONTENT-01 — Graphs, charts, QR codes, generated graphics

**Priority:** Future use case

Treat dynamic graphics initially as content supplied to or replacing content in a named template object. Keep SVG, PNG, and native ODF chart strategies open until empirical LibreOffice package research justifies a choice.

A desirable workflow remains:

```text
LibreOffice template owns layout
        ↓
engine addresses named object
        ↓
dynamic renderer supplies content
```

## Higher document structure

### DOC-STRUCTURE-01 — Page styles and master pages

**Priority:** Future major document-structure block

Support explicit page/master-style concepts based on native ODF structures.

### DOC-STRUCTURE-02 — Header and footer content

**Priority:** Future major document-structure block

Support headers and footers associated with page/master styles.

### DOC-STRUCTURE-03 — Page-flow semantics

**Priority:** Future major document-structure block

Focus on page breaks, keep-with-next, keep-together, page-style transitions, and structured-content interaction with page/master styles. Future section extensions must build on the established section target/clone/instantiate semantics.

## Document import and round-trip workflows

### DOCUMENT-IMPORT-01 — Engine document identification and structured data extraction

**Priority:** Later

Possible future workflow: identify engine/schema metadata, inspect known structured objects, reconstruct application-level data where semantics are defined, select another template, and render again.

Identification and integrity are separate concerns. A full-file hash is unsuitable as primary identity for documents that may be opened and saved by LibreOffice.

No public import API is approved.

## Shared document model / additional renderers

**Priority:** Later

A future semantic model may describe Paragraph, List, Table, Image, Section, PageBreak, Header/Footer, and PageStyle for more than one renderer. This remains a design direction, not an approved abstraction. Do not introduce a renderer-neutral context merely because multiple renderers are imaginable.

## Sample and validation infrastructure

### SAMPLE-INFRA-01 — Sample infrastructure

**Priority:** Medium

Continue improving sample/visual regression infrastructure when it materially reduces validation cost or makes rendering regressions easier to detect.

Rendering-sensitive changes require actual LibreOffice rendering and inspection of the complete affected output against a known-good baseline. Automated tests, XML validity, successful generation, or an agent report do not substitute for that visual gate.

Generated files under `samples/output/` remain local regression artifacts unless a task explicitly changes that policy. LibreOffice `.~lock.*#` files must never be committed.

## Planning notes

Current preferred strategic order:

1. `SR-06` semantic graphic style requirements;
2. `SR-07` semantic table/table-cell requirements;
3. `D5F` lifecycle/materialization integration;
4. `D5G` compatibility closeout and final `STYLE-CONTEXT-01` closeout;
5. reassess `DOCUMENT-DEFAULTS-01`, `FRAME-LAYOUT-01`, and table-layout priorities from the completed semantic baseline;
6. template-authoring / format-preservation re-audit;
7. page/master-style and page-flow work;
8. named-object operations and dynamic-content research;
9. `DOCUMENT-IMPORT-01` and broader round-trip workflows later.

The sequence after STYLE-CONTEXT closeout remains revisitable. Smaller independent list, lifecycle, sample-infrastructure, asset, or reference-fixture slices may be inserted where useful.

Most importantly:

> Semantics before implementation.

A feature belongs in the engine when its ODF semantics, ownership, lifecycle, compatibility impact, and authoring model are understood—not merely because an API can be invented for it.

# Future Development

This document is the issue-oriented backlog for known limitations, architectural debt, research topics, and possible future capabilities of the ODT Template Engine.

It complements [`ROADMAP.md`](ROADMAP.md). The roadmap defines strategic sequencing; this file records individual topics without implying that every item is approved for immediate implementation.

The current `develop` baseline includes the completed architecture foundation and the PRODUCT-01 / SECTION-03 structured-document milestone. Backlog descriptions must therefore be interpreted against that newer baseline rather than against the earlier placeholder-centric architecture.

## Layout and document rendering

### TABLE-LAYOUT-01 — Explicit table width

**Priority:** High

Provide reliable explicit table-width semantics based on actual ODF behavior and LibreOffice output.

Do not introduce an API until the existing table-style and column-style paths have been characterized.

### TABLE-LAYOUT-02 — Explicit column widths

**Priority:** Very high

Reliable explicit column widths are important for professional layouts and currently remain one of the most visible table limitations.

Existing `table:table-column` / `style:column-width` handling has historically been unreliable enough that ratio/virtual-column workarounds have been used. The first step is therefore investigation and characterization of the existing path, not a second competing implementation.

### TABLE-LAYOUT-03 — Row and minimum height

**Priority:** Medium-high

Support explicit row-height/minimum-height semantics where ODF and LibreOffice behavior permit reliable control.

### TABLE-LAYOUT-04 — Reliable relative table width

**Priority:** High

Investigate and support relative table sizing without relying on accidental LibreOffice behavior.

### TABLE-CELL-01 — Vertical cell alignment

**Priority:** Medium

Support vertical alignment inside table cells through native ODF style semantics.

### FRAME-LAYOUT-01 — Unified frame positioning

**Priority:** High architectural improvement

Define a shared frame-positioning model for drawing content instead of allowing images and text boxes to evolve separate positioning semantics.

Research areas:

- anchor type;
- horizontal and vertical position;
- relation/reference area;
- wrap behavior;
- width and height;
- existing-template object mutation;
- constructed frames;
- LibreOffice behavior;
- Word round-trip behavior where relevant.

Use real LibreOffice-authored ODF as the primary implementation evidence.

### FRAME-LAYOUT-02 — DrawTextBox positioning

**Priority:** Medium-high

`DrawTextBox` has positioning limitations that should be resolved through or consistently with `FRAME-LAYOUT-01`, rather than through an independent incompatible API.

### IMAGE-LAYOUT-01 — Image anchor, wrap, and position

**Priority:** High

Image layout remains complex and incomplete. Reassess this item after the unified frame model is understood; image-specific behavior should not duplicate general frame semantics.

### LIST-LAYOUT-01 — List indentation

**Priority:** Medium

Provide reliable indentation control using native list/paragraph style semantics.

### LIST-LAYOUT-02 — Nested list style control

**Priority:** Medium

Improve explicit style control for nested lists while preserving native ODF list structure.

## Style architecture

### STYLE-CONTEXT-01 — Document-scoped style context

**Priority:** High / preferred next architecture block

Explicit style registration currently has process-wide static paths through `StyleMapper`. Characterization has shown that explicit registrations can leak into later documents in the same process, while ordinary element-generated styles do not necessarily exhibit the same contamination.

The goal is a document-scoped style registry/context with clear ownership and lifecycle semantics.

Required principles:

- do not solve the problem through constructor resets;
- avoid duplicated mutable state;
- preserve compatibility entry points where practical;
- keep authoritative style state document-scoped;
- integrate finalization only after ownership is clear;
- add multi-document regression coverage.

Likely slices:

1. style-state audit and characterization;
2. ownership/lifecycle decision;
3. internal `StyleContext` or equivalent;
4. element/materializer integration;
5. compatibility facade for legacy registration paths where justified;
6. finalization integration and multi-document regression.

### STYLE-API-02 — Style API consistency

**Priority:** Medium architectural/API debt

The style API has grown through multiple element types and compatibility paths. Reassess public style semantics after document-scoped style ownership is established.

Do not combine this automatically with `STYLE-CONTEXT-01`; internal ownership can be corrected without redesigning every public style API in the same change.

## Document defaults and state

### DOCUMENT-DEFAULTS-01 — Document-level defaults

**Priority:** High after STYLE-CONTEXT-01

Allow document-wide defaults for common text, paragraph, and page semantics while preserving explicit local overrides.

The exact API is not fixed. Defaults must belong to a document-scoped context and must not add new global `StyleMapper` state.

### TEMP-ASSET-01 — Temporary asset lifecycle

**Priority:** Low

Clarify cleanup and ownership of importer-created or generated temporary assets.

### ASSET-CONTEXT — Document-scoped asset lifecycle

**Priority:** Architectural follow-up

Clarify the relationship between generated assets, package assets, manifest updates, and temporary resources, especially for long-running worker processes.

### LIFECYCLE-API-01 — Lifecycle API clarity

**Priority:** Medium

Continue documenting and, where justified, simplifying lifecycle semantics around load/render/save/repeated operations without silently breaking compatible behavior.

## Template structure and authoring

### TEMPLATE-FORMAT-PRESERVATION-01 — Re-audit remaining paths

**Priority:** Medium-high

This backlog item has been substantially changed by the completed TEMPLATE-STRUCTURE work.

The following are now implemented for the supported scalar-expression path:

- logical projection across transparent inline structure;
- non-mutating structure inspection;
- safe normalization of proven repairable same-style fragments;
- structure-preserving replacement across contributing text nodes;
- preservation of bookmark markers;
- preservation of authored literal spaces and ODF whitespace;
- support for expressions fragmented across differently styled text nodes without flattening their structure.

Therefore this item must no longer be treated as a general request to "fix split placeholders".

Remaining work begins with a fresh audit of other template-language paths, especially:

- conditions;
- foreach/control structures;
- `nl2br`;
- `ul` / `ol` structural placeholders;
- complex boundary interactions not covered by scalar expression replacement.

Unexpected legacy behavior should first be characterized rather than opportunistically changed.

### TEMPLATE-AUTHORING-UX-01 — Template authoring experience

**Priority:** Medium-high research/design

LibreOffice should remain the visual template designer where practical. The engine should not reproduce LibreOffice's layout system in PHP merely to make templates programmable.

Research topics include:

- naming conventions for sections, bookmarks, frames, tables, and future named objects;
- template validation and diagnostics;
- inspection tooling;
- discoverability of structured template capabilities;
- guidance for flow-based versus fixed-layout authoring;
- realistic maximum-content tests;
- clearer separation between simple template syntax and structured object operations.

No new template syntax is implied by this backlog item.

### HTML-IMPORT-01 — Extended HTML import

**Priority:** Later

Extend HTML import only where there is a concrete application need and the resulting semantics can map cleanly to the structured ODT model.

## Structured document and named-object work

### NAMED-OBJECT-OPERATIONS-01 — Addressable native object operations

**Priority:** Future architectural/product direction

The completed section API demonstrates that native LibreOffice/ODF structures can act as addressable template objects when their identity and lifecycle semantics are understood.

Investigate extending that model to additional native object families.

The key semantic distinction is:

```text
replace content
    preserve the authored container/layout where possible

replace object
    replace the complete addressed native object

clone
    duplicate according to object-specific identity rules

remove
    remove the addressed object safely
```

Potential targets include:

- frames;
- text boxes;
- tables;
- images/drawing objects;
- other stable named ODF structures discovered through evidence.

Do not assume a universal object supports every operation. Typed targets and capability-specific operations are preferable to a generic `replaceElementByName()` API that hides incompatible semantics.

The exact public API remains undecided.

### DYNAMIC-CONTENT-01 — Graphs, charts, QR codes, and generated graphics

**Priority:** Future use case

Dynamic graphics should initially be treated as content that can be inserted into or replace the content of a named template object.

Potential content types:

- generated images;
- circular/profile images;
- QR codes;
- charts/graphs;
- small infographics.

For chart rendering, keep all of these strategies open until researched:

- SVG;
- PNG;
- native ODF charts.

A native ODF chart implementation must begin by examining real LibreOffice-authored chart structures and package relationships. Do not derive an implementation solely from the ODF specification.

A strong target workflow is:

```text
LibreOffice template
    owns frame position / size / style / surrounding layout
        ↓
engine addresses named object
        ↓
dynamic renderer supplies content
```

This makes a graph creator a useful benchmark for named-object content replacement without prematurely making chart generation part of the core document model.

## Higher document structure

### DOC-STRUCTURE-01 — Page styles and master pages

**Priority:** Future major document-structure block

Support explicit page/master-style concepts based on native ODF structures.

### DOC-STRUCTURE-02 — Header and footer content

**Priority:** Future major document-structure block

Support headers and footers associated with page/master styles.

### DOC-STRUCTURE-03 — Page-flow semantics

**Priority:** Future major document-structure block

The older version of this backlog area included investigating sections. That part is now superseded by the completed structured-section work.

Remaining topics include:

- page breaks;
- keep-with-next;
- keep-together;
- page-style transitions;
- structured-content interaction with page/master styles.

Future section extensions should build on the established section target, clone, instantiate, nested ownership, and collection lifecycle semantics.

## Document import and round-trip workflows

### DOCUMENT-IMPORT-01 — Engine document identification and structured data extraction

**Priority:** Later

Possible future workflow for returning documents:

1. identify that an ODT was generated by this engine/application and determine a schema/version;
2. inspect known structured objects;
3. reconstruct application-level data where semantics are sufficiently defined;
4. select another template;
5. render a new document.

Potential metadata may include a stable generator/application marker, schema/version, and optional document identifier.

Identification is not the same as integrity verification. A full-file hash is unsuitable as the primary identity mechanism because opening and saving a valid document in LibreOffice can change package bytes without changing its application meaning.

No public import API is approved yet.

## Shared document model / additional renderers

**Priority:** Later

A future semantic model could potentially represent concepts such as Paragraph, List, Table, Image, Section, PageBreak, Header/Footer, and PageStyle for consumption by more than one renderer.

This remains a design direction, not an approved abstraction. Do not introduce a renderer-neutral context merely because multiple renderers are imaginable.

Browser pagination and exact visual parity with ODT are separate problems and should not be assumed to follow automatically from a shared semantic model.

## Sample and validation infrastructure

### SAMPLE-INFRA-01 — Sample infrastructure

**Priority:** Medium

Continue improving sample/visual regression infrastructure when it materially reduces validation cost or makes rendering regressions easier to detect.

Generated files under `samples/output/` remain local regression artifacts unless a task explicitly changes that policy.

LibreOffice `.~lock.*#` files must never be committed.

## Planning notes

Current preferred strategic order:

1. `STYLE-CONTEXT-01`;
2. `DOCUMENT-DEFAULTS-01`;
3. `FRAME-LAYOUT-01`;
4. table-layout work, beginning with investigation around `TABLE-LAYOUT-02`;
5. template-authoring / format-preservation re-audit;
6. page/master-style and page-flow work;
7. named-object operations and dynamic-content research;
8. `DOCUMENT-IMPORT-01` and broader round-trip workflows later.

This sequence is intentionally revisitable. Smaller independent list, cell, lifecycle, sample-infrastructure, or asset slices may be inserted where useful.

Most importantly, future work should continue the architecture principle established by the recent milestones:

> Semantics before implementation.

A feature belongs in the engine when its ODF semantics, ownership, lifecycle, compatibility impact, and authoring model are understood—not merely because an API can be invented for it.

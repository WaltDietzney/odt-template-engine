# ODT-CONSTRUCTION-01 — Post-1.0 Development Plan

## Purpose

This document defines the planning baseline for development after version 1.0.

The work is intentionally split into three rounds. Version numbers are proposed working labels, not commitments. In particular, the first round is a 1.0.x maintenance line so that additional evidenced fixes can be included without forcing feature work into a patch release.

The governing rule remains:

> **Semantics before implementation.**

Public API spellings shown in discussions such as `addSection()`, `addPageBreak()`, `addColumnBreak()`, `body()->add()`, or `page()->break()` are illustrative only. This plan specifies required capabilities. API shape follows ODF/Writer research and integration with the existing architecture.

## Round A — 1.0.x Fix & Complete

### Goal

Complete and correct the released 1.0 product contract without introducing the new ODT Construction architecture.

### Known work

- integrate the public documentation / Sample Explorer navigation fix into source control:
  - stable `id="sample-..."` fragment targets on Sample Explorer cards;
  - documentation sample-source links routed to the corresponding Sample Explorer entry;
  - keep non-card resources such as `sample-registry.php` distinct;
- implement a bounded compatibility-preserving `replaceImageByName()` fix for the concrete 1.0 defect, after the exact failing behavior is characterized;
- keep the broader image-layout redesign out of this maintenance round;
- correct stale post-release planning/documentation that still describes completed 1.0 work as pending;
- accept additional evidenced 1.0 regressions, packaging defects, installation/documentation defects, or public-sample defects if discovered during real use.

### Boundaries

This round does not introduce Section construction, page/master authoring, a new image API, data-source integration, or high-level automation convenience APIs.

Every behavior fix should first receive a characterization test where practical.

## Round B — proposed 1.1: ODT Construction

### Goal

Extend the engine from processing Writer-authored templates and inserting PHP-owned content toward composing complete native ODT document structures from a minimal/prepared ODT base.

The existing PHP-owned building blocks remain the foundation:

- `RichText`;
- `Paragraph`;
- `RichTable` and cells;
- `ListElement`;
- `ImageElement` and `CircularImageElement`;
- `DrawTextBox`;
- semantic style/resource infrastructure.

The milestone must reuse these capabilities rather than create a competing document model.

### Stage B0 — Construction baseline and native research

Before approving public APIs:

- characterize the existing structured materialization path as a general construction substrate;
- characterize Writer/ODF Section creation, naming, style ownership, nesting, and save/reopen behavior;
- characterize Section/page column semantics, including column count, widths/spacing, separators where relevant, and the relationship between Section columns and page-layout columns;
- characterize explicit page-break and column-break semantics and their correct native ownership;
- characterize the minimum page/master-style authoring semantics required for generated headers and footers;
- verify native heading semantics versus merely styled paragraphs;
- characterize creation of bookmarks and, if useful to the bounded milestone, String User Fields.

Research must distinguish element creation, style/layout definition, references, and mutation of existing Writer-owned structures.

### Stage B1 — Document/body construction surface

Provide a coherent way to insert PHP-owned ODT elements into the document body without requiring a pre-authored placeholder.

Do not assume that the correct model is a `BodyElement`. The body is a document structural target; the public surface must follow the existing document/materialization architecture.

### Stage B2 — PHP-owned Section construction

Add the capability to create native named Sections programmatically and populate them with existing structured elements.

Required semantics include:

- native Section identity/name;
- owned child content;
- nesting where native semantics support it;
- style/layout references where required;
- deterministic repeated-save behavior;
- LibreOffice save/reopen stability;
- compatibility with document inspection and template inspection.

The desired architectural loop is:

```text
construct Section
    -> save ODT
    -> LibreOffice open/save
    -> reload
    -> inspect
    -> address/use the generated Section as template structure
```

No concrete `SectionElement` class name is approved by this plan.

### Stage B3 — Column layout and explicit flow breaks

Support professional multi-column document composition where native ODF semantics allow it.

Required capabilities include:

- Section-associated column layout where appropriate;
- relevant page-layout column semantics where required by the use case;
- explicit page breaks;
- explicit column breaks.

These are capability requirements, not approved method names. The design must determine whether a break belongs to paragraph flow, a document-flow operation, a dedicated semantic value/object, or another existing abstraction.

The engine continues to describe native flow semantics; Writer/LibreOffice remains responsible for physical pagination and column flow.

### Stage B4 — Bounded page/master construction

Provide only the page/master authoring needed for coherent generated documents, coordinated with `PAGE-STYLE-AUTHORING-01`.

The bounded target includes:

- creating or defining the required page/master relationship;
- generated header content;
- generated footer content;
- page-style assignment/succession where required;
- preservation of the established distinction between page-style identity, master-page content, page-layout geometry, and paragraph flow.

This stage must not silently expand into an exhaustive Writer page-style API.

### Stage B5 — Template-structure construction

Allow generated documents to become useful engine templates themselves.

Candidates include:

- named Sections;
- generated bookmarks;
- existing classic template expressions inside generated content;
- bounded creation of String User Fields if research shows that it belongs naturally in this milestone.

The goal is not to invent a second template language. It is to make generated native structures compatible with the existing inspection, mapping, preflight, and rendering model.

### Stage B6 — Construction correctness and existing-element hardening

Resolve construction-specific correctness dependencies exposed by composing larger documents.

`TABLE-COLUMN-IDENTITY-01` is a likely dependency because multiple generated tables must not collide through document-global positional style names.

`LIST-LAYOUT-01/02`, `DOCUMENT-DEFAULTS-01`, and other backlog topics enter this milestone only if concrete construction work proves them necessary. They are not automatically part of 1.1.

### Stage B7 — Integration showcase and acceptance

The milestone should finish with a representative document constructed from a minimal/prepared ODT base and containing, as applicable:

- body content;
- named Section structure;
- multi-column Section content;
- paragraphs/rich text;
- table;
- list;
- image/frame/text box;
- explicit page and column breaks;
- header/footer;
- generated template-addressable structure.

Acceptance requires:

```text
ODT Construction
    -> native ODT
    -> LibreOffice open/save
    -> reload
    -> inspection
    -> template processing
    -> valid editable ODT
```

Automated XML/package tests remain necessary but do not replace visual LibreOffice regression for rendering-sensitive work.

## Round C — later capability expansion

This round adds capabilities rather than merely completing the first construction model. Its version number remains deliberately open.

Candidate groups include:

### Automation and application data

- high-level orchestration over Template Contract -> Mapping -> Preflight -> Automation;
- the deferred convenience workflow concept sometimes described as `mapAndRenderVariables()`;
- a data-source/provider/resolver boundary for profile data, contacts, databases, application data, and future AI-produced data;
- keep external systems such as Nextcloud, SQL databases, contacts services, and AI providers outside the core ODT engine.

### Richer Writer-native operations

- `SECTION-INSTANCE-NATIVE-ADDRESSING-01`;
- `NAMED-OBJECT-OPERATIONS-01`;
- `LIST-ITEM-POPULATION-01`;
- `TEMPLATE-DECLARED-STRUCTURAL-SEMANTICS-01`;
- `CLASSIC-FOREACH-SCOPE-01`;
- broader `NATIVE-FIELDS-1.1`.

### Formatting and graphics

- `TEMPLATE-FORMAT-PRESERVATION-01`;
- broader `IMAGE-LAYOUT-01` semantics such as intrinsic ratio, fit/contain/cover/crop, and resource-only replacement while preserving Writer-owned frames;
- `CUSTOM-SHAPE-FILL-IMAGE-REPLACEMENT-01`;
- dynamic graphics, QR codes, charts, and related generated content.

### Broader document capabilities

- footnotes/endnotes;
- table of contents/index work when justified;
- `DOCUMENT-IMPORT-01`;
- extended HTML import;
- broader document defaults and page-style authoring;
- validation/linting and authoring diagnostics;
- asset/temp-resource lifecycle improvements.

## Product layer / ecosystem

The following direction should influence engine ergonomics but is not part of the core construction milestone:

- LibreOffice extension / document assistant;
- AI-assisted document editing and template population;
- Nextcloud/contact/database integrations;
- professional template kits and application-specific workflows.

A useful long-term layering is:

```text
Product / Integration Layer
        |
Automation / Data Resolution
        |
ODT Template Processing
        |
ODT Construction
        |
Native ODF package/document model
```

Fundamental ODF correctness remains an engine responsibility. External-system connectivity and workflow-specific orchestration should not leak into the core document model.

## Planning rule

The stages above are sequencing and capability boundaries, not pre-approved implementation slices.

For each substantial stage:

1. inspect current code and real Writer/ODF structures;
2. identify active, legacy, and compatibility paths;
3. add characterization tests where behavior already exists;
4. decide semantics and ownership;
5. record a focused change contract;
6. implement in small slices;
7. run focused and full automated validation;
8. review the diff;
9. perform LibreOffice regression when rendering or native document structure changes;
10. complete final preflight before integration.

The roadmap may be adjusted when empirical ODF/Writer evidence contradicts an assumption in this planning document.

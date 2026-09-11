# Future Development

This document is the issue-oriented backlog for known limitations, architectural debt, research topics, and possible future capabilities of the ODT Template Engine.

It complements [`ROADMAP.md`](ROADMAP.md). The roadmap defines strategic sequencing; this file records individual topics without implying that every item is approved for immediate implementation.

The current planning baseline incorporates the completed semantic style architecture, the RESEARCH-01 version-1.0 reassessment recorded in [`architecture/RESEARCH-01_1_0_REASSESSMENT_DECISION.md`](architecture/RESEARCH-01_1_0_REASSESSMENT_DECISION.md), and the completed PAGE-FLOW-01 milestone.

## Version 1.0 mandatory sequence

The explicit path to 1.0 is now:

1. `PAGE-FLOW-01` — **COMPLETE / FINAL GO**;
2. `TABLE-LAYOUT-01` — professional table geometry — **NEXT ACTIVE MILESTONE**;
3. `FRAME-LAYOUT-01` — bounded reliable frame geometry core;
4. `TEMPLATE-RELIABILITY-01` — focused remaining template-format/control audit and only evidence-based fixes;
5. `FINALIZATION-01` — final document/export lifecycle contract;
6. `RELEASE-1.0` integration preflight.

High-value backlog items outside this sequence remain valid but are not 1.0 blockers unless a mandatory milestone exposes a concrete dependency.

## Completed style and dependency architecture

### STYLE-CONTEXT-01 — Document-scoped style context — COMPLETE / FINAL GO

The core ownership problem is no longer future work. The current baseline includes document-local `StyleContext` ownership, semantic structured-element traversal, conflict-preserving transitive requirement discovery, semantic `StyleRequirement` values, paragraph/text, table-family and graphic semantic materialization, document-local font-face dependencies, document-local fill-image dependencies, and package-owned physical resource preparation.

### STYLE-API-02 — Public style API consistency — COMPLETE / FINAL GO

STYLE-API-02 is no longer active backlog debt. Application element options/fluent APIs, `$template->styles()->defineParagraph()`, authored named style references, semantic custom-element extension, stateless `StyleMapper`, narrow `StyleWriter`, and removal of process-global paragraph/text registry behavior are established baseline.

### SR-06 / SR-07 / D5F / D5G — COMPLETE

SR-06 established semantic graphic-style requirements and dependency boundaries without redesigning frame positioning. SR-07 established semantic table-family ownership without completing table geometry. D5F/D5G established lifecycle/materialization integration and compatibility closeout that FINALIZATION-01 must build on rather than replace.

## PAGE-FLOW-01 — Page styles, paragraph flow, and Section flow — COMPLETE / FINAL GO

PAGE-FLOW-01 superseded the earlier planning split of `DOC-STRUCTURE-01`, `DOC-STRUCTURE-02`, and `DOC-STRUCTURE-03` as independent later blocks. The milestone is complete and its semantic decisions are now architecture baseline.

The completed milestone established:

- native page/master style identity as distinct from referenced page-layout geometry;
- preservation of authored master-page succession and paragraph-to-master-page references;
- paragraph keep/break/widow/orphan semantics through the existing document-local style architecture;
- Section flow as structural preservation rather than engine-side pagination;
- page/master-style-owned headers and footers as normal structured content domains;
- Writer/LibreOffice as the authority for physical pagination;
- no PHP pagination engine and no broad opportunistic page-style authoring API.

The accepted completion contract is recorded in [`architecture/PAGE_FLOW_01_CHANGE_CONTRACT.md`](architecture/PAGE_FLOW_01_CHANGE_CONTRACT.md).

### PAGE-STYLE-AUTHORING-01 — Programmatic page-style authoring — REQUIRED FUTURE CAPABILITY

**Priority:** Confirmed future architecture requirement; sequencing after PAGE-FLOW-01 / not part of the completed PAGE-FLOW-01 implementation scope

PAGE-FLOW-01 research confirms that page styles are not merely template-owned implementation detail. The engine should eventually support semantically explicit programmatic page-style work while preserving the native distinction between `style:master-page` identity/content and referenced `style:page-layout` geometry.

The future capability must investigate and design at least:

- referencing an existing page/master style;
- modifying an existing page/master style where semantically valid;
- defining page/master styles where justified;
- assigning a page/master style to document flow;
- editing native succession such as `First Page -> Standard`;
- coordinating page-style identity with page-layout geometry without merging their ownership;
- interaction with master-page-owned headers/footers without creating a separate header/footer processing engine.

`style:master-page-name` requires particular care. PAGE-FLOW-01 established that Writer places this page-style reference on the paragraph `style:style` element, not inside `style:paragraph-properties`. A raw paragraph-property escape hatch must therefore not be documented as generated page-style assignment if it serializes the attribute at the wrong structural level.

This future work should follow the style-architecture distinction already established elsewhere:

```text
reference != definition != mutation
```

The exact public API is not approved yet. Do not infer symmetric methods such as `definePageStyle()` merely from the existence of paragraph-style APIs. The capability must be designed from actual ODF ownership and concrete application needs.

A practical motivation is document-wide/template-wide authoring: applications should be able to modify meaningful base/page styles and rely on native Writer inheritance and references instead of repeating equivalent formatting or rebuilding Writer layout in PHP.

Deferral from PAGE-FLOW-01 is a sequencing decision, not rejection of page-style authoring.

### Paragraph flow semantics — completed baseline

The 1.0 baseline includes:

- keep with next;
- keep paragraph/content together;
- widow handling;
- orphan handling;
- break before;
- break after.

The current paragraph style mapping supports `keep-with-next`, `keep-together`, `widows`, `orphans`, `break-before`, and `break-after`, while preserving native values rather than interpreting pagination.

The engine expresses or preserves native flow semantics. LibreOffice/Writer remains responsible for actual pagination.

Programmatic page-style transitions remain part of `PAGE-STYLE-AUTHORING-01`; PAGE-FLOW-01 preserves authored transitions and established their correct semantic location.

### Section flow semantics — completed baseline

PAGE-FLOW-01 characterized and preserved Sections spanning page boundaries, nested Sections, repeated Section instances, paragraph flow inside Sections, native tables/lists inside Sections, and save/reopen behavior.

There is no evidenced generic Section-level native pagination ownership corresponding to paragraph keep/break semantics. Do not invent a generic Section-level "keep whole section together" property.

### Explicit page breaks and transitions — completed baseline

PAGE-FLOW-01 supports/preserves native paragraph break semantics and authored page-style relationships. Programmatic page-style assignment/transition authoring remains explicitly retained for `PAGE-STYLE-AUTHORING-01` rather than being approximated through the wrong ODF property level.

### Headers and footers — completed baseline

Headers and footers are page/master-style-owned structured content. Existing cross-document-part processing reaches this content, so no separate header/footer processing subsystem is planned. Future page-owned addressing may be designed when a concrete requirement justifies it.

### PAGE-FLOW non-goals retained as architecture boundaries

Do not introduce a PHP pagination engine, page-height calculations, CV-specific pagination helpers, opportunistic page-style authoring APIs, or unrelated Writer-field semantics under the PAGE-FLOW baseline.

## TABLE-LAYOUT-01 — Professional table geometry

**Priority:** 1.0 BLOCKER / NEXT ACTIVE MILESTONE

The previously separate table-layout backlog topics are consolidated for 1.0 planning into one coherent capability block:

- explicit table width;
- whole-table alignment / placement;
- absolute column widths;
- relative column widths;
- row/minimum height;
- vertical cell alignment.

SR-07 already established semantic ownership for `table`, `table-column`, `table-row`, and `table-cell`. This milestone must focus on actual ODF/Writer geometry semantics, public behavior, and rendering reliability rather than reopening style ownership.

Historical identifiers `TABLE-LAYOUT-02`, `TABLE-LAYOUT-03`, `TABLE-LAYOUT-04`, and `TABLE-CELL-01` remain useful provenance for existing discussions/tests, but they are no longer separate strategic 1.0 milestones.

## FRAME-LAYOUT-01 — Reliable frame geometry core

**Priority:** 1.0 BLOCKER / bounded scope

Define a shared frame-positioning model for supported drawing content instead of allowing images and text boxes to evolve separate positioning semantics.

The 1.0 core should cover:

- `draw:frame` structural semantics;
- anchor type;
- size;
- horizontal/vertical position;
- relation/reference area;
- fundamental wrap behavior;
- consistent semantics across images and text boxes;
- relevant preservation/mutation of LibreOffice-authored existing frames.

`FRAME-LAYOUT-02` and `IMAGE-LAYOUT-01` should be resolved through or consistently with this shared model rather than through independent incompatible APIs.

1.0 does not require every Writer drawing or positioning option.

### GRAPHIC-PART-COMPAT-01 — Structured graphic materialization across document parts

**Priority:** Focused compatibility investigation; not part of PAGE-FLOW-01 unless later evidence makes it a dependency

PAGE-FLOW-01D exposed a bounded Writer-visibility discrepancy across document parts:

```text
ImageElement via setElement() / content.xml body      -> Writer-visible
ImageElement via setElement() / styles.xml header     -> not Writer-visible
setImage() / styles.xml header                         -> Writer-visible
```

The image resource, manifest entry, `draw:frame`, and `draw:image` reference were present in the failing structured-header result. Changing only the generated graphic parent style from `Standard` to `Graphics` did not make the image visible. Existing body samples confirm that the semantic `ImageElement` path is not generally broken.

The investigation must therefore compare the actual body and page-owned materialization contexts before changing graphic-style or frame semantics. It should determine whether the discrepancy is caused by document-part-specific ODF structure, insertion context, automatic-style placement/scope, frame anchoring constraints, or another Writer interoperability rule.

Do not fold this finding into PAGE-FLOW pagination logic, and do not assume it is merely a frame-positioning defect. Coordinate with `FRAME-LAYOUT-01` only if evidence shows that the root cause belongs to the shared frame model.

Until resolved, page-owned image insertion characterized for PAGE-FLOW-01 uses the established public `setImage()` path.

## TEMPLATE-RELIABILITY-01 — Remaining template-format/control audit

**Priority:** 1.0 BLOCKER AS AUDIT

This replaces the older interpretation of `TEMPLATE-FORMAT-PRESERVATION-01` as a broad unsolved formatting project.

Supported scalar expressions already have logical projection across transparent inline structure, non-mutating inspection, safe normalization, structure-preserving replacement, bookmark preservation, and ODF whitespace preservation.

The remaining 1.0 task is to re-audit:

- conditions;
- foreach/control structures;
- `nl2br`;
- `ul` / `ol` structural placeholders;
- complex boundary interactions not covered by scalar replacement.

Unexpected legacy behavior should first be characterized. If no relevant defect is found, no implementation is required. Do not reopen solved scalar behavior without evidence.

## FINALIZATION-01 — Final document/export semantics

**Priority:** 1.0 BLOCKER AS ARCHITECTURE DECISION

RESEARCH-01 showed that native ODF semantics can render correctly in LibreOffice/PDF while not surviving DOCX conversion with equivalent semantics. Conditional Sections and Hidden Paragraphs are concrete evidence.

Define:

- when template data binding is complete;
- when structured instantiation is complete;
- when native semantic materialization is required for interoperability;
- engine versus LibreOffice/export-tool responsibility;
- whether semantic source ODT and finalized static ODT are distinct lifecycle concepts;
- repeated `render()` / `save()` behavior around finalization.

A universal engine-side evaluator for every Writer field/condition is not required for 1.0. A coherent lifecycle/export contract is.

## Document defaults and state

### DOCUMENT-DEFAULTS-01 — Document-level defaults — DEFERRED UNLESS DEPENDENCY EMERGES

**Priority:** Post-1.0/high-value research unless a concrete dependency emerges

The user-facing goal remains useful: applications should eventually be able to express appropriate document-wide defaults without repeating the same options on every element.

However, ODF/LibreOffice research shows that "defaults" may refer to distinct mechanisms:

- ODF `style:default-style`;
- LibreOffice Default Paragraph Style / `Standard`;
- authored named base styles;
- application-level LibreOffice basic-font defaults;
- page-layout defaults.

This topic overlaps with, but is not identical to, `PAGE-STYLE-AUTHORING-01`. A future design should determine when modifying an authored base style is preferable to introducing a separate default-setting abstraction. Native inheritance should be used where it provides the intended semantics.

Do not invent one `setDefault...` API until these mechanisms and their precedence semantics are established. No public `setDefaultFont()` API is currently approved.

### FONT-REFERENCE-RECONCILIATION — FONT-02 / FONT-03 provenance

**Priority:** Bounded documentation/reference follow-up

Inspect provenance and actual ODF structures before deciding whether corresponding local fixtures belong in the repository. Do not use this task to introduce document-default behavior.

### TEMP-ASSET-01 — Temporary asset lifecycle

**Priority:** Low

Clarify cleanup and ownership of importer-created or generated temporary assets.

### ASSET-CONTEXT — Document-scoped asset lifecycle

**Priority:** Architectural follow-up

Clarify the relationship between generated assets, package assets, manifest updates, and temporary resources, especially for long-running worker processes. Preserve the established separation in which elements describe resources, collectors discover them, and `OdtPackage` owns physical package preparation.

### LIFECYCLE-API-01 — Lifecycle API clarity

**Priority:** Medium / coordinate with FINALIZATION-01

Continue documenting and, where justified, simplifying lifecycle semantics around load/render/save/repeated operations without silently breaking compatible behavior. FINALIZATION-01 may absorb or sharpen part of this question; do not create a competing lifecycle architecture.

## Template authoring and native semantics

### TEMPLATE-AUTHORING-UX-01 — Template authoring experience

**Priority:** High-value, non-blocking

LibreOffice should remain the visual template designer where practical. Research topics include naming conventions, validation and diagnostics, inspection tooling, discoverability of structured capabilities, flow versus fixed-layout guidance, realistic maximum-content tests, and clearer separation between simple syntax and structured object operations.

### DECLARATIVE-SECTION-01 — Native Section declarations such as `#foreach:collection`

**Priority:** High-value, non-blocking / research-design candidate

RESEARCH-01A established that Writer can author and serialize `#foreach:experience` unchanged and that the existing Section resolver plus `instantiateMany()` machinery can already process that exact native Section name mechanically.

A future declarative layer should therefore be treated as discovery/orchestration over SECTION-03 rather than a second foreach renderer.

Still undecided:

- whether `#foreach:...` becomes approved syntax;
- automatic discovery timing;
- missing-data diagnostics;
- render/save lifecycle ordering;
- interaction with nested declarations;
- whether any operators beyond collection repetition are justified.

Do not silently fuzzy-correct declaration names such as the characterized `#foreach:expiriene` typo.

### NATIVE-FIELDS-01 — Writer fields and conditional content

**Priority:** Post-1.0/selective earlier use only when justified

User Fields, Set/Get Variables, Conditional Text, Hidden Text, Hidden Paragraphs, and Conditional Sections remain valuable native mechanisms. Broad public support is deferred because field scope, authoritative branch content, lifecycle evaluation, and DOCX interoperability differ from the existing placeholder model.

`{{variable}}` remains the preferred general/portable scalar-binding mechanism unless a native field provides a concrete semantic or authoring advantage.

### HTML-IMPORT-01 — Extended HTML import

**Priority:** Later

Extend HTML import only where there is a concrete application need and semantics map cleanly to the structured ODT model.

## Structured document and named-object work

### NAMED-OBJECT-OPERATIONS-01 — Addressable native object operations

**Priority:** Post-1.0 architectural/product direction

The completed section API demonstrates that native LibreOffice/ODF structures can act as addressable template objects when identity and lifecycle semantics are understood.

Investigate extending the model while preserving the distinction between:

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

## List layout

### LIST-LAYOUT-01 / LIST-LAYOUT-02

**Priority:** Medium / bounded independent follow-up

Provide reliable list indentation and nested list style control using native list/paragraph semantics. May be inserted before 1.0 only if it is independent and does not destabilize the mandatory sequence or if PAGE-FLOW/TEMPLATE-RELIABILITY exposes a concrete dependency.

## Document import and round-trip workflows

### DOCUMENT-IMPORT-01 — Engine document identification and structured data extraction

**Priority:** Later / post-1.0

Possible future workflow: identify engine/schema metadata, inspect known structured objects, reconstruct application-level data where semantics are defined, select another template, and render again.

Identification and integrity are separate concerns. A full-file hash is unsuitable as primary identity for documents that may be opened and saved by LibreOffice.

No public import API is approved.

## Shared document model / additional renderers

**Priority:** Later / post-1.0

A future semantic model may describe Paragraph, List, Table, Image, Section, PageBreak, Header/Footer, and PageStyle for more than one renderer. This remains a design direction, not an approved abstraction. Do not introduce a renderer-neutral context merely because multiple renderers are imaginable.

## Sample and validation infrastructure

### SAMPLE-INFRA-01 — Sample infrastructure

**Priority:** Medium

Continue improving sample/visual regression infrastructure when it materially reduces validation cost or makes rendering regressions easier to detect.

Rendering-sensitive changes require actual LibreOffice rendering and inspection of the complete affected output against a known-good baseline. Automated tests, XML validity, successful generation, or an agent report do not substitute for that visual gate.

Generated files under `samples/output/` remain local regression artifacts unless a task explicitly changes that policy. LibreOffice `.~lock.*#` files must never be committed.

## Product/tooling layer candidates

Potential higher-level capabilities exposed by RESEARCH-01 include:

- template validation and linting;
- diagnostics for malformed/unresolved semantic object names;
- visual inspection of named template objects;
- template authoring assistants;
- professional template kits;
- maximum-content / overflow testing;
- export/conversion workflow tooling;
- application-specific CV/application-document tooling built on generic engine capabilities.

Fundamental ODF correctness and core document semantics belong in the engine rather than being withheld to create a commercial boundary. Higher-level workflow, validation, designer assistance, packaged templates, and application services are more appropriate product-layer candidates.

## Release 1.0 integration preflight

After PAGE-FLOW-01, TABLE-LAYOUT-01, FRAME-LAYOUT-01, TEMPLATE-RELIABILITY-01, and FINALIZATION-01, stop adding unrelated features and perform a dedicated release preflight.

It should include, as applicable:

- focused PHPUnit tests;
- full `composer test`;
- PHP lint for `src/` and `tests/`;
- `composer validate`;
- `git diff --check`;
- public sample smoke tests;
- ODT/ZIP/XML integrity;
- LibreOffice headless rendering;
- manual visual regression;
- save/reopen and repeated lifecycle behavior;
- PDF output;
- representative DOCX interoperability for behavior explicitly promised by 1.0;
- a professional multi-page CV benchmark using generic engine capabilities.

## Planning notes

The current strategic order is no longer the pre-RESEARCH-01 list of defaults, frames, tables, template work, and later page flow.

The mandatory path is now:

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
RELEASE-1.0
```

Smaller independent list, lifecycle, sample-infrastructure, asset, or reference-fixture slices may be inserted where useful, but they must not obscure the 1.0 blockers.

Most importantly:

> Semantics before implementation.

A feature belongs in the engine when its ODF semantics, ownership, lifecycle, compatibility impact, and authoring model are understood—not merely because an API can be invented for it.

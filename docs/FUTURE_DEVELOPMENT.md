# Future Development

This document is the issue-oriented backlog for known limitations, architectural debt, research topics, and possible future capabilities of the ODT Template Engine.

It complements [`ROADMAP.md`](ROADMAP.md). The roadmap defines strategic sequencing; this file records individual topics without implying that every item is approved for immediate implementation.

The current planning baseline incorporates the completed semantic style architecture, the RESEARCH-01 version-1.0 reassessment recorded in [`architecture/RESEARCH-01_1_0_REASSESSMENT_DECISION.md`](architecture/RESEARCH-01_1_0_REASSESSMENT_DECISION.md), and the completed PAGE-FLOW-01 milestone.

## Version 1.0 mandatory sequence

The explicit path to 1.0 is now:

1. `PAGE-FLOW-01` — **COMPLETE / FINAL GO**;
2. `TABLE-LAYOUT-01` — professional table geometry — **COMPLETE / FINAL GO**;
3. `FRAME-LAYOUT-01` — bounded reliable frame geometry core — **COMPLETE / FINAL GO**;
4. `TEMPLATE-AUTHORING-01` — **1.0 BLOCKER** — Phases A–E are complete for the bounded 1.0 scope, including the Phase-E manual LibreOffice Writer gate. Phase F (authoring documentation, samples, templates, and the public learning path) remains mandatory;
5. `FINALIZATION-01` — final document/export lifecycle contract;
6. `RELEASE-1.0` integration preflight.

High-value backlog items outside this sequence remain valid but are not 1.0 blockers unless a mandatory milestone exposes a concrete dependency. The reconciled remaining path and product-authoring principles are recorded in [`architecture/RELEASE_1_0_PLANNING_RECONCILIATION.md`](architecture/RELEASE_1_0_PLANNING_RECONCILIATION.md).

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

**Priority:** COMPLETE / FINAL GO

The previously separate table-layout backlog topics are consolidated for 1.0 planning into one coherent capability block:

- explicit table width;
- whole-table alignment / placement;
- absolute column widths;
- relative column widths;
- row/minimum height;
- vertical cell alignment.

SR-07 already established semantic ownership for `table`, `table-column`, `table-row`, and `table-cell`. This milestone must focus on actual ODF/Writer geometry semantics, public behavior, and rendering reliability rather than reopening style ownership.

Historical identifiers `TABLE-LAYOUT-02`, `TABLE-LAYOUT-03`, `TABLE-LAYOUT-04`, and `TABLE-CELL-01` remain useful provenance for existing discussions/tests, but they are no longer separate strategic 1.0 milestones.


### TABLE-COLUMN-IDENTITY-01 — Generated column-style identity across multiple tables

**Priority:** Post-TABLE-LAYOUT compatibility/architecture follow-up; not part of TABLE-LAYOUT-01

TABLE-LAYOUT-01 manual showcase work exposed an existing naming limitation in generated `table-column` automatic styles. Column definitions currently use positional names such as:

```text
co0
co1
co2
```

These names are document-global semantic identities in `StyleContext`, not table-local identities. Two generated tables in one document can therefore conflict when the same positional name carries different definitions, for example:

```text
table A: co0 -> style:column-width="5cm"
table B: co0 -> style:rel-column-width="32766*"
```

`StyleContext` correctly rejects such conflicting same-identity definitions instead of silently overwriting one of them.

SR-07 already classified positional `coN` names as legacy behavior and explicitly did not authorize a new collision-renaming strategy. TABLE-LAYOUT-01 therefore does not solve this by opportunistically introducing hashes, table-prefixed names, or automatic renaming.

A future design should determine a stable document-local identity/allocation strategy for generated table-column styles that:

- supports multiple independently generated tables with different column geometry in the same document;
- preserves repeated-save stability;
- does not silently mutate authored automatic styles;
- remains compatible with existing table-column references where feasible;
- avoids process-global counters or hidden cross-document state;
- keeps semantic de-duplication and true conflicts distinguishable.

Until that work is explicitly designed, tests/samples that combine multiple generated column geometries in one document should avoid conflicting `coN` definitions or use separate regression documents.

## FRAME-LAYOUT-01 — Reliable frame geometry core — COMPLETE / FINAL GO

FRAME-LAYOUT-01 is completed architecture baseline.

It established a shared `DrawingLayout` model for frame-backed elements, native carrier ownership for geometry, semantic graphic-style ownership for layout policies, compatible public authoring APIs, and anchor-sensitive structured insertion.

The bounded 1.0 frame core now covers:

- anchor type;
- size;
- horizontal/vertical alignment and relation;
- explicit x/y offset positioning;
- fundamental wrap behavior;
- retained compatibility policies such as flow-with-text, wrap influence, and overlap;
- consistent semantics across `DrawTextBox` and `ImageElement`;
- body/header structured insertion parity;
- paragraph/text-flow carrier preservation for frame insertion;
- semantic style deduplication and repeated-save stability.

`FRAME-LAYOUT-02`, `IMAGE-LAYOUT-01`, broader Writer drawing options, and future specialized draw elements must build on this shared model rather than introduce incompatible positioning semantics.

S03-B2 also recorded a post-1.0 image replacement question for
`IMAGE-LAYOUT-01`. The current named-image replacement contract intentionally
retains legacy dimensions: no options produce `5cm × 3cm`, width-only means
the supplied width with `3cm` height, height-only means `5cm` width with the
supplied height, and two explicit dimensions are used exactly. S03-B therefore
uses explicit `15cm × 8.452cm` dimensions for its cover asset. Future work
must investigate intrinsic-ratio preservation, fitting or filling a
Writer-authored frame, contain/cover/crop behavior, and replacement of only a
frame's image resource from actual ODF/Writer structures. This is not a 1.0
blocker and does not authorize redefining `replaceImageByName()`.

### GRAPHIC-PART-COMPAT-01 — RESOLVED BY FRAME-LAYOUT-01

The PAGE-FLOW-01D discrepancy was investigated and closed during FRAME-LAYOUT-01.

The decisive finding was structural:

```text
structured draw:frame
+ removal or invalid nesting of the required text paragraph carrier
= Writer-invisible result
```

The failure was not caused by image packaging, manifest registration, a general header prohibition, or a need for a separate header image engine.

FRAME-LAYOUT-01 introduced anchor-sensitive insertion and paragraph/text-container preservation, including promotion of generated frames out of inline `text:span` wrappers when necessary. LibreOffice regression confirmed structured `ImageElement` visibility in both body and header contexts.

No separate `GRAPHIC-PART-COMPAT-01` implementation remains open.

## TEMPLATE-AUTHORING-01 — Template-driven authoring and rendering

**Priority:** 1.0 BLOCKER

The earlier `TEMPLATE-RELIABILITY-01`, `TEMPLATE-AUTHORING-UX-01`, `DECLARATIVE-SECTION-01`, and the bounded 1.0 subset of `NATIVE-FIELDS-01` are now coordinated through one strategic milestone. Their historical identifiers remain useful provenance; they must not evolve as competing architectures.

The approved product direction is additive:

- existing imperative APIs remain supported;
- classic visible `{{...}}` syntax remains the portable/simple template path;
- native ODT structures may carry discoverable structural template meaning;
- one unified inspection model should expose the supported template contract;
- a high-level render path should be able to orchestrate a mapped data set against that contract;
- LibreOffice remains the visual template designer rather than having PHP reconstruct authored layout.

The milestone phases are:

1. **A — Existing Template Reliability & Format Preservation — COMPLETE:** characterize conditions, foreach, `nl2br`, lists, and relevant ODF/style boundaries; fix only evidenced defects.
2. **B — Unified Template Inspection — COMPLETE:** compose template-language expressions with Sections, bookmarks, tables, frames, supported fields, declarative controls, dependencies, and diagnostics.
3. **C — Native Field Binding — COMPLETE:** bounded Writer User Field binding is established; broader field families remain later work where separately justified.
4. **D — Declarative Structural Controls — COMPLETE / FINAL GO:** native `#foreach`, `#if`, and `#ifnot` Sections execute through established Section/condition mechanics in BODY and supported master-page header/footer regions, including nested item scopes, ordered foreach materialization, scoped classic scalar/filter binding, strict collection validation, and execution-unit rollback. Automated and real LibreOffice-authored save/reopen regression are complete. The authoritative handoff is [`architecture/TEMPLATE_AUTHORING_01D_DECLARATIVE_CONTROLS_BASELINE.md`](architecture/TEMPLATE_AUTHORING_01D_DECLARATIVE_CONTROLS_BASELINE.md) plus [`architecture/TEMPLATE_AUTHORING_01D_CHANGE_CONTRACT.md`](architecture/TEMPLATE_AUTHORING_01D_CHANGE_CONTRACT.md); D0–D4 must not be reopened without contradictory evidence.
5. **E — Optional Mapping / Automation Layer:** determine whether and how a convenience service should orchestrate mapped application data against an inspected template contract while preserving lower-level APIs as first-class usage. Mapping-driven rendering is not an inherent `OdtTemplate` lifecycle.
6. **F — Authoring Documentation & Samples:** provide first-class guidance for creating inspectable, reusable LibreOffice templates and polished examples before 1.0.

The conceptual target is:

```php
$contract = $template->inspectTemplate();
$template->render($mappedData);
```

This is a product/architecture target, not yet an approved method signature. Semantics, lifecycle ordering, compatibility, nesting, diagnostics, and repeated render/save behavior require characterization and a Change Contract before implementation.

### TEMPLATE-RELIABILITY-01 — absorbed provenance

The former 1.0 reliability audit is Phase A of `TEMPLATE-AUTHORING-01`. Existing scalar-expression preservation remains established baseline and must not be reopened without evidence.

### TEMPLATE-AUTHORING-UX-01 — absorbed provenance

Authoring UX is no longer merely non-blocking research. The bounded 1.0 authoring contract and documentation are Phases B/F of `TEMPLATE-AUTHORING-01`. Broader designer tooling, visual assistants, template kits, and authoring automation remain future product-layer work.

### DECLARATIVE-SECTION-01 — absorbed provenance

RESEARCH-01A already proved that Writer preserves names such as `#foreach:experience` and that existing Section mechanics can address such names. Phase D decides and implements the bounded 1.0 declarative semantics after compatibility/design review. Do not fuzzy-correct malformed declaration names.

**Superseding planning note:** early Phase-D planning that treated nested foreach mechanics, item-local binding, identity rewriting, prototype removal, collection rollback, scope discovery, or condition grammar as open research is obsolete. Those topics are established repository baseline. Resume Phase D from [`architecture/TEMPLATE_AUTHORING_01D_DECLARATIVE_CONTROLS_BASELINE.md`](architecture/TEMPLATE_AUTHORING_01D_DECLARATIVE_CONTROLS_BASELINE.md).

### NATIVE-FIELDS-01 — split between 1.0 and later work

A bounded native-field binding capability is Phase C of `TEMPLATE-AUTHORING-01`. Broad support for every Writer field/conditional construct remains post-1.0 unless concrete evidence makes it necessary. `{{variable}}` remains the preferred general/portable scalar-binding mechanism where native semantics provide no concrete advantage.

### SECTION-INSTANCE-NATIVE-ADDRESSING-01 — Instance-relative addressing inside cloned Sections

**Priority:** Post-1.0 architecture/research follow-up unless a mandatory milestone proves a bounded dependency

TEMPLATE-AUTHORING-01F S03 established a specific gap between native identity
preservation and application-level addressability. Section cloning already
rewrites contained native identities, including bookmark names, so each clone
remains structurally distinct. The current public Section instance surface does
not provide equivalent instance-relative addressing for a logical bookmark
inside that clone.

The future design must characterize the general semantics before approving an
API. In particular, determine:

- whether instance-relative addressing should apply only to bookmarks or to
  other contained named native object families;
- how callers identify the logical authored object without depending on
  generated clone suffixes;
- how nested Section instances affect lookup scope and ambiguity;
- how zero/multiple matches and malformed native identities are diagnosed;
- whether addressing and mutation remain separate typed capabilities;
- compatibility with existing Section identity rewriting and repeated
  instantiate/save lifecycles.

Do not infer `SectionTarget::bookmark()`, a universal named-element accessor,
or another concrete method signature from this backlog item. The established
principle remains:

```text
addressability != mutation capability
```

This topic is related to, but distinct from, classic foreach scope semantics.
It concerns native objects contained in cloned Writer-authored Sections rather
than placeholder-variable shadowing.

### CLASSIC-FOREACH-SCOPE-01 — Scoped placeholder resolution and shadowing

**Priority:** Bounded architecture follow-up

Current classic rendering applies globally assigned scalar placeholders before
classic foreach item cloning and row-local replacement. A ROOT scalar and an
item field with the same placeholder name therefore collide: the global value
reaches the foreach prototype first, so item-over-ROOT shadowing is not
available in the current public lifecycle.

The S02 compatibility rule is to use distinct authored names, such as
`items[].line_total` and `ROOT.total`, rather than relying on same-name scope
shadowing. Future work may investigate true scoped placeholder resolution, but
must first characterize existing template compatibility. Reordering render
phases is not automatically the solution and is not prescribed by this note.


## TABLE-ROW-01 — Native Writer table population — COMPLETE / FINAL GO

TABLE-ROW-01 is completed architecture baseline. A typed named table can now populate a bounded scalar data region while preserving the native Writer table object, native header rows, explicitly kept ordinary source rows, and Writer-owned row/cell/paragraph formatting. Repeated population reconciles against the immutable Writer/source-row baseline, and unsupported mutable topology is rejected rather than guessed.

Canonical sample L12 — Writer Table Population teaches this ownership model. `RichTable` remains the separate PHP-owned/generated-table path.

### TEMPLATE-DECLARED-STRUCTURAL-SEMANTICS-01 — Writer-authored structural markers

**Priority:** Post-1.0 design/research direction; not a 1.0 blocker

TABLE-ROW-01 deliberately uses application-side source indices such as `keepRows => [0]`. That is appropriate for the bounded 1.0 contract, but it also exposes a broader authoring opportunity: structural knowledge that belongs to a Writer template should, where practical, be declarable in the template instead of duplicated as numeric application configuration.

A concrete future research case is a Writer-authored bookmark or another suitable native ODF marker placed in an ordinary table row to declare semantics such as `keepRow`, optionally with a semantic identifier. The engine could then derive the containing named table and row context from the native document tree rather than requiring redundant table names or changing row positions in PHP configuration.

This is intentionally a design direction, not an approved bookmark naming convention or public API. Future work must first characterize real Writer/ODF behavior, including bookmark placement inside table cells/rows, stability under row cloning/removal and Writer save/reopen, duplicate/ambiguous markers, interaction with table identity, and whether bookmarks are the correct carrier at all.

The broader principle is:

> Template-owned structural semantics should live in the Writer-authored template when a stable native representation exists; application code should supply application-owned data and explicit runtime choices.

This direction may later inform semantic named-element work, but it must not create one universal mutation API. Addressability and mutation capability remain separate: Sections, bookmarks, tables, and frames may share discoverable identity while retaining type-specific operations and ownership rules.

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

### TEMPLATE-AUTHORING-01 provenance

The former TEMPLATE-AUTHORING-UX-01, DECLARATIVE-SECTION-01, and bounded NATIVE-FIELDS-01 topics are coordinated by the mandatory TEMPLATE-AUTHORING-01 milestone above. Broader native Writer semantics remain future work after the bounded 1.0 contract is established.

S03-B2 provides a concrete `TEMPLATE-FORMAT-PRESERVATION-01` follow-up:
application-created structured content supplied through `SectionTarget::replaceContent()`
does not automatically inherit character formatting from the replaced
Writer-authored content. In S03, a generated finding needed to request bold
text explicitly. No engine defect was established. The post-1.0 question is
what authored style context, inheritance, or preservation semantics should be
available during structured replacement, if any; this does not reopen the
completed `STYLE-CONTEXT-01` or `STYLE-API-02` foundations and does not approve
an automatic inheritance API.

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

### LIST-ITEM-POPULATION-01 — Application-owned items in Writer-native lists

**Priority:** Post-1.0 architecture/research; not a 1.0 blocker

S03 demonstrated that Writer-native lists can remain Writer-owned, and that a
whole containing Section can be replaced with application-generated structured
content. It did not establish a capability for populating an existing
Writer-authored list/list-item collection while preserving the authored list
structure, item prototype, numbering, indentation, and formatting.

Future work should determine, from actual ODF and LibreOffice Writer
structures, whether and how Writer authors a mutable item region, how zero or
more application items reconcile with preserved authored items, and which list
and paragraph semantics remain template-owned. This is conceptually analogous
to TABLE-ROW-01's separation of Writer-owned structure from application-owned
data, but it is not a table API and must not be collapsed into classic
`{{#foreach}}` or `CLASSIC-FOREACH-SCOPE-01`, which concern template-expression
iteration and scope resolution. No public list-population API is approved.

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

After PAGE-FLOW-01, TABLE-LAYOUT-01, FRAME-LAYOUT-01, TEMPLATE-AUTHORING-01, and FINALIZATION-01, stop adding unrelated features and perform a dedicated release preflight.

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
TABLE-LAYOUT-01              COMPLETE
    ↓
FRAME-LAYOUT-01              COMPLETE
    ↓
TEMPLATE-AUTHORING-01
    ↓
FINALIZATION-01
    ↓
RELEASE-1.0
```

Smaller independent list, lifecycle, sample-infrastructure, asset, or reference-fixture slices may be inserted where useful, but they must not obscure the 1.0 blockers.

Most importantly:

> Semantics before implementation.

A feature belongs in the engine when its ODF semantics, ownership, lifecycle, compatibility impact, and authoring model are understood—not merely because an API can be invented for it.


## NATIVE-FIELDS-1.1 — Broader Writer field types and field families

**Target:** version 1.1 or later.

TEMPLATE-AUTHORING-01C v1 is deliberately bounded to Writer User Fields with `office:value-type="string"`.

Future research/implementation may consider:

- numeric User Fields;
- date/time User Fields;
- boolean semantics;
- currency/value formatting;
- formulas;
- Set/Get Variable with document-flow-aware semantics;
- additional Writer field families where they provide a concrete authoring benefit.

This work must not be folded back into Phase C v1 without new evidence and an explicit architecture decision. Set/Get Variable in particular must not be modeled as an ordinary document-global binding because its characterized semantics are position-dependent document-flow state.


## CUSTOM-SHAPE-FILL-IMAGE-REPLACEMENT-01 — Writer/custom-shape bitmap fill replacement

**Status:** post-1.0 architecture topic discovered during the TEMPLATE-AUTHORING-01F public API audit.

### Problem

Advanced Writer/ODF graphic objects such as `draw:custom-shape` can represent
their image appearance through a bitmap graphic fill rather than a direct
`draw:image` child. `CircularImageElement` uses this model and the project
already has document-local semantic fill-image dependency infrastructure.

A practical example is a circular CV portrait: replacing the photograph means
replacing the bitmap fill/background resource while preserving the authored
custom shape, geometry, size, position, and surrounding layout.

Current frame image replacement APIs target `draw:frame/draw:image` semantics
and must not be generalized to custom-shape bitmap fills.

### Existing foundation

SR-06 already separates:

```text
graphic style
    -> draw:fill-image-name
        -> draw:fill-image declaration
            -> Pictures/... resource
```

It also established document-local fill-image requirements, conflict semantics,
target-document authority, declaration materialization, and package/resource
separation for generated structured content.

SR-06 deliberately left mutation/replacement of an existing target
`draw:fill-image` declaration to a separately designed structured operation.

### Future design questions

A post-1.0 architecture pass should characterize real Writer-authored and
Word-converted custom-shape fixtures before selecting an API. It must decide:

- whether logical targeting is by custom-shape identity, graphic style, or
  fill-image declaration;
- how shared fill-image declarations behave when only one object should change;
- how replacement preserves Writer-owned geometry and layout;
- how resource replacement, declaration identity, and package cleanup interact;
- whether the capability belongs to a broader advanced-graphics/custom-shape
  target model.

No public API shape is approved by this entry.


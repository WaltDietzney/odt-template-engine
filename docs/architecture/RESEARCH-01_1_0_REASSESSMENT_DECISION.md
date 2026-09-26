# RESEARCH-01 — Version 1.0 Architecture Reassessment Decision

## Status

**Architecture planning decision based on RESEARCH-01 evidence.**

This document does not approve new public APIs or implementation details by itself. It defines the post-RESEARCH-01 prioritization baseline for the path to ODT Template Engine 1.0.

The purpose of this reassessment is to prevent the project from turning every newly discovered ODF capability into immediate implementation work. RESEARCH-01 demonstrated that LibreOffice Writer and ODF already provide significantly richer document, field, section, layout, style, and page-flow semantics than the earlier placeholder-centric planning model assumed. The correct response is therefore not to implement everything at once, but to decide which capabilities are foundational for 1.0 and which can deliberately remain later work.

The governing principle remains:

> Semantics before implementation.

A capability belongs before 1.0 when omitting it would leave a fundamental professional-document limitation, an architectural ambiguity that would be expensive to reverse later, or an unreliable rendering/export path. Interesting but non-foundational capabilities may remain post-1.0 even when their technical feasibility is already proven.

## 1. Evidence baseline

This decision is based on the current `develop` architecture and on the empirical findings recorded in:

- `NATIVE_ODF_AUTHORING_RESEARCH.md`;
- `RESEARCH-01A_DECLARATIVE_SECTION_FIXTURE.md`;
- the completed SECTION-03 structured-section architecture;
- the completed STYLE-CONTEXT-01 and STYLE-API-02 architecture;
- the current `ROADMAP.md` and `FUTURE_DEVELOPMENT.md` planning baseline.

Relevant established facts include:

- Sections are already an implemented structured-template primitive with exact cloning, identity rewriting, local data binding, nested instantiation, collection lifecycle behavior, rollback, and save/reopen persistence.
- A Writer-authored Section name such as `#foreach:experience` can be stored unchanged as native `text:name` and can already be resolved and passed through the existing `instantiateMany()` machinery without special handling for `#` or `:`.
- `{{...}}` remains a strong, portable scalar-binding mechanism, especially for item-local values in repeated structures.
- Native Writer fields and conditional structures provide useful semantics but do not uniformly survive DOCX conversion.
- Headless LibreOffice correctly reevaluates characterized native conditions for PDF rendering.
- Conditional Sections and Hidden Paragraphs demonstrate that export compatibility cannot be assumed merely because LibreOffice renders the ODT correctly.
- The current page-layout implementation can mutate selected properties of an existing master-page/page-layout relationship, but it is not yet a general page-style or page-flow model.
- Paragraph-style mapping already supports a subset of flow properties such as `keep-with-next`, `break-before`, and `break-after`; this is useful implementation evidence, but there is not yet a complete, characterized page-flow capability.

## 2. Version 1.0 target definition

ODT Template Engine 1.0 does not need to implement all of ODF or all of the capabilities exposed by LibreOffice Writer.

The 1.0 target is:

> The engine should reliably generate professional native ODT documents from LibreOffice-authored templates and/or structured PHP content, preserve or express the central native style, structure, layout, and page-flow semantics required by professional documents, and have a defined path to a finalized document state suitable for the supported export workflows.

This target deliberately combines two authoring perspectives:

- **LibreOffice/template-owned structure:** Writer remains the visual designer for native layout and reusable document structure where practical.
- **PHP-owned generated structure:** structured elements remain available where application code must generate dynamic document subtrees.

Neither perspective should require the engine to reproduce Writer's pagination engine in PHP.

## 3. Professional-document benchmark

A professional multi-page CV remains the primary practical architecture benchmark for the 1.0 path.

This is not a request for CV-specific APIs. The CV is used because it exposes generic document requirements quickly:

- first page versus following-page layout;
- headers and footers;
- page-style transitions;
- headings that must remain with following content;
- entries that should paginate predictably;
- repeated structured Sections;
- tables with reliable geometry;
- frames, images, and text boxes;
- lists and rich text;
- realistic maximum-content behavior;
- ODT, PDF, and where promised DOCX interoperability.

A capability should therefore remain generic ODF functionality even when the CV use case is the reason its importance became visible.

## 4. Prioritization decision

The path to 1.0 is organized into five architecture/capability blocks followed by an integration preflight.

```text
RESEARCH-01
    ↓
PAGE-FLOW-01
    ↓
TABLE-LAYOUT-01
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

The sequence is intentional but not mechanically rigid. A bounded independent fix may be inserted when useful. The important decision is the mandatory versus deferred boundary, not the exact number of implementation commits.

## 5. PAGE-FLOW-01 — mandatory 1.0 foundation

PAGE-FLOW-01 becomes the first major post-RESEARCH-01 architecture block and is **1.0-blocking**.

The existing planning grouped page/master styles, headers/footers, breaks, keep semantics, and page-style transitions under later document-structure work. The reassessment raises this area because professional multi-page documents require a coherent native page and flow model before 1.0.

PAGE-FLOW-01 must treat the following as related but semantically distinct concerns.

### 5.1 Page Style / Page Template semantics

Research and characterize native Writer/ODF behavior for:

- master/page styles;
- referenced page layouts;
- page size, orientation, and margins;
- first-page versus following-page behavior;
- next/following page-style relationships where applicable;
- page-style transitions triggered from document content;
- authored page styles that should remain owned by the template.

The existing `PageLayoutManager` remains valid as a narrow mutation service for established master-page/page-layout relationships. PAGE-FLOW-01 must not silently reinterpret that service as a complete page-style architecture.

### 5.2 Paragraph flow semantics

The following are mandatory 1.0 research/behavior targets:

- keep with next;
- keep paragraph/content together where native ODF semantics provide it;
- widow handling;
- orphan handling;
- break before;
- break after;
- page-style transitions associated with paragraph/document flow.

The engine should express or preserve native flow semantics. It must not attempt to calculate actual page placement or page height itself.

The desired responsibility is:

```text
paragraph / paragraph style
    ↓
native flow properties
    ↓
LibreOffice layout engine
    ↓
actual pagination
```

### 5.3 Section flow semantics

Sections are already an active structured-document primitive. PAGE-FLOW-01 must therefore characterize how native Sections interact with page boundaries rather than treating Sections as page-sized boxes.

Research must include:

- Sections spanning page boundaries;
- nested Sections;
- repeated Section instances;
- interaction between Section content and paragraph keep/break behavior;
- interaction with tables and lists inside Sections;
- whether any Section-level native semantics materially affect pagination;
- save/reopen and headless rendering behavior.

No assumption should be made that a Section has a generic "keep whole section together" property. Actual ODF/Writer semantics must decide the implementation model.

### 5.4 Explicit page breaks and page-style transitions

A professional document must be able to express intentional page changes without the engine manually calculating layout.

Characterize and support the native path for:

- explicit page breaks;
- page-style changes at a break;
- first-page to following-page transitions;
- interaction with structured/generated content.

### 5.5 Headers and footers

Headers and footers are strongly coupled to page/master styles and must be researched as part of PAGE-FLOW-01 so that the page-style model is not designed without them.

Whether the entire header/footer authoring API is implemented inside the same milestone may depend on the evidence. However, the 1.0 page-style architecture must not preclude or mis-model native header/footer ownership.

### 5.6 PAGE-FLOW-01 non-goals

PAGE-FLOW-01 must not:

- implement a PHP pagination engine;
- calculate page heights or manually move content based on geometry;
- introduce CV-specific page-layout helpers;
- invent universal page-style APIs before ODF/Writer characterization;
- mix unrelated native field semantics into the page-flow implementation.

## 6. TABLE-LAYOUT-01 — mandatory professional table geometry

Reliable table geometry remains a 1.0 requirement.

The previously separate backlog topics should be treated as one coherent architecture/capability block for planning purposes:

- explicit table width;
- absolute column widths;
- relative column widths;
- row/minimum height;
- vertical cell alignment.

SR-07 already established semantic ownership for the relevant table-related style families. TABLE-LAYOUT-01 should therefore focus on ODF behavior, public semantics, and rendering reliability rather than reopening style ownership.

The milestone is 1.0-blocking because professional documents frequently use tables for native layout and because unreliable geometry is immediately visible in generated output.

## 7. FRAME-LAYOUT-01 — mandatory bounded frame core

FRAME-LAYOUT-01 remains required before 1.0, but with deliberately bounded scope.

The 1.0 core should establish a coherent model for:

- `draw:frame` as the shared structural basis for supported drawing content;
- anchor semantics;
- size;
- horizontal and vertical position;
- relation/reference area;
- fundamental wrap behavior;
- consistent semantics across images and text boxes;
- preservation/mutation of LibreOffice-authored existing frames where relevant.

1.0 does not require every Writer positioning option or every possible drawing feature. The milestone should solve the core model without becoming an exhaustive graphics subsystem.

## 8. TEMPLATE-RELIABILITY-01 — mandatory re-audit, not mandatory redesign

Template-format preservation is no longer a general unsolved scalar-expression problem.

The 1.0 requirement is a focused re-audit of the remaining paths:

- conditions;
- foreach/control structures;
- `nl2br`;
- `ul` / `ol` structural placeholders;
- complex ODF boundary interactions not already covered by structure-preserving scalar replacement.

The decision rule is intentionally conservative:

- if characterization finds no relevant defect, no implementation is required;
- if a relevant defect is found, preserve current behavior with characterization tests first, then apply a bounded fix.

TEMPLATE-RELIABILITY-01 must not reopen solved scalar-formatting behavior without evidence.

## 9. FINALIZATION-01 — mandatory architecture decision, bounded implementation

RESEARCH-01A established that native ODF semantics can render correctly in LibreOffice/PDF while not surviving DOCX conversion with equivalent semantics.

Therefore, before 1.0 the project must define what constitutes a **finalized document state**.

The architecture must answer at least:

- when template data binding is considered complete;
- when structural instantiation is considered complete;
- whether native conditional/content semantics must be materialized before interoperability-sensitive conversion;
- what responsibility belongs to the engine versus LibreOffice/export tooling;
- whether the source semantic ODT and the finalized static ODT are distinct lifecycle concepts;
- how repeated `render()` / `save()` behavior interacts with finalization.

A conceptual pipeline may be:

```text
semantic template ODT
    ↓
data binding
    ↓
structured instantiation
    ↓
native semantic evaluation/materialization where required
    ↓
final static ODT
    ↓
PDF and/or DOCX conversion
```

This is not an approved API.

1.0 does **not** require a universal engine-side evaluator for every Writer field or condition. It does require a coherent export/finalization contract so that later native features do not force incompatible lifecycle redesign.

## 10. Release 1.0 integration preflight

After the five mandatory blocks, the project should stop adding unrelated features and perform a dedicated 1.0 integration preflight.

The preflight should include, as applicable:

- focused PHPUnit tests for all 1.0 architecture blocks;
- full `composer test`;
- PHP lint for `src/` and `tests/`;
- `composer validate` where relevant;
- `git diff --check`;
- public sample smoke tests;
- ODT/ZIP/XML integrity checks;
- LibreOffice headless rendering;
- PDF visual review;
- representative DOCX interoperability checks for behavior explicitly promised by 1.0;
- save/reopen and repeated lifecycle checks;
- professional multi-page CV benchmark using generic engine capabilities.

Automated tests do not replace manual LibreOffice visual regression for rendering-sensitive changes.

## 11. Capabilities explicitly not required for 1.0

The following remain valuable but are not 1.0 blockers by this decision.

### 11.1 Declarative `#foreach:collection` Section syntax

The feasibility evidence is strong and the existing SECTION-03 machinery already provides the difficult mutation semantics.

A future declarative layer may map:

```text
Writer Section: #foreach:experience
    ↓
collection lookup
    ↓
existing instantiateMany()
```

However, the engine is already structurally capable when the application explicitly invokes Section instantiation. Therefore automatic discovery/interpretation of `#foreach:...` is a high-value authoring feature, not a fundamental 1.0 blocker.

It may still enter 1.0 opportunistically if its semantics and lifecycle are resolved with low risk after the mandatory work, but 1.0 must not be delayed solely for this feature.

### 11.2 Native Writer fields and conditional content as general public features

User Fields, Set/Get Variables, Conditional Text, Hidden Text, Hidden Paragraphs, and Conditional Sections remain important native capabilities.

They are not required as a complete public feature family before 1.0 because:

- existing `{{...}}` binding remains effective and portable;
- existing engine conditions remain available;
- field scope differs from item-local collection scope;
- export interoperability is not uniform;
- finalization semantics should be defined before broad adoption.

Native mechanisms may still be used internally or selectively where a mandatory 1.0 block requires them.

### 11.3 Document defaults

`DOCUMENT-DEFAULTS-01` remains useful research/design work but is no longer a top 1.0 sequencing priority.

The distinction between ODF default styles, Writer's Default Paragraph Style, authored base styles, application defaults, and page-layout defaults remains important. This work should not delay the more fundamental page-flow, table, frame, template-reliability, and finalization milestones unless PAGE-FLOW-01 uncovers a concrete dependency.

### 11.4 General named-object operations

The section model already proves the architecture direction. Extending replace/clone/remove/content-replacement semantics to every named native object family is post-1.0 work unless a mandatory milestone establishes a concrete need.

### 11.5 Dynamic content, import, and renderer-neutral models

The following remain post-1.0 directions:

- charts/graphs/QR/infographic strategies beyond existing generic image support;
- broad `DOCUMENT-IMPORT-01` / round-trip data reconstruction;
- renderer-independent shared document models;
- large HTML-import expansion without a concrete application requirement.

These capabilities are strategically interesting but must not prevent completion of the native ODT core.

## 12. Product/tooling layer candidates

RESEARCH-01 also exposed capabilities that may belong above the core engine rather than inside it.

Potential later product/tooling directions include:

- template validation and linting;
- diagnostics for malformed or unresolved semantic object names;
- visual inspection of named template objects;
- template authoring assistants;
- professional template kits;
- maximum-content / overflow test tooling;
- export/conversion workflow tooling;
- application-specific CV or application-document tooling built on generic engine capabilities.

Fundamental ODF correctness and core document semantics should not be artificially withheld from the open-source engine in order to create a commercial boundary. Higher-level tooling, workflow, validation, designer assistance, packaged templates, and application services are more appropriate product-layer candidates.

## 13. Revised 1.0 mandatory/deferred matrix

| Capability | 1.0 decision | Notes |
| --- | --- | --- |
| Page styles / page templates | **Mandatory** | Core PAGE-FLOW-01 concern |
| Paragraph flow semantics | **Mandatory** | keep/break/widow/orphan behavior |
| Section flow across page boundaries | **Mandatory** | Must build on SECTION-03, not bypass it |
| Explicit page breaks / page-style transitions | **Mandatory** | Native Writer/ODF semantics |
| Headers / footers | **Architecture must include them** | Implementation scope depends on PAGE-FLOW evidence |
| Professional table geometry | **Mandatory** | One coherent table-layout block |
| Core frame geometry/positioning | **Mandatory, bounded** | No exhaustive drawing subsystem |
| Remaining template-format/control audit | **Mandatory audit** | Fix only proven relevant defects |
| Final document/export semantics | **Mandatory architecture decision** | Universal native evaluator not required |
| Declarative `#foreach:...` discovery | **High-value, not blocking** | Reuse existing SECTION-03 machinery |
| General native field API | **Deferred** | Selective use possible |
| Document defaults | **Deferred unless dependency emerges** | No generic default API yet |
| General named-object operations | **Deferred** | Section primitive sufficient for 1.0 baseline |
| Dynamic charts/QR/infographics | **Deferred** | Later use case/product work |
| Document import/round trip | **Deferred** | Later architecture |
| Renderer-neutral document model | **Deferred** | Avoid premature abstraction |

## 14. Transition out of RESEARCH-01

RESEARCH-01 does not need to exhaust every originally listed topic before implementation work can resume.

Its strategic purpose is fulfilled once there is enough evidence to choose the next architecture milestones responsibly. The remaining research map continues to be valuable and should remain available for later work, but it must not become an obligation to investigate all native ODF capabilities before 1.0 development continues.

The immediate transition is therefore:

1. preserve RESEARCH-01 findings and this reassessment decision;
2. merge the research documentation when reviewed and accepted;
3. begin PAGE-FLOW-01 from `develop`;
4. use the established architecture workflow:
   - inspect current code and real Writer/ODF structures;
   - identify active, legacy, and compatibility paths;
   - add characterization tests;
   - discuss semantics;
   - write the change contract;
   - implement in bounded slices;
   - run automated and visual regression;
   - final review and preflight;
   - PR to `develop`.

## 15. Decision summary

The path to 1.0 is no longer driven by the largest list of possible ODF features.

It is driven by the minimum coherent native-document foundation required for reliable professional documents:

```text
PAGE + FLOW
    ↓
TABLE RELIABILITY
    ↓
FRAME RELIABILITY
    ↓
TEMPLATE RELIABILITY
    ↓
FINALIZATION / EXPORT CONTRACT
    ↓
PROFESSIONAL DOCUMENT PRE-FLIGHT
    ↓
1.0
```

The most important planning change is the promotion of **PAGE-FLOW-01** to a 1.0-blocking foundation covering page styles/page templates, paragraph flow, Section flow, explicit page transitions, and the page-style relationship to headers/footers.

The equally important scope decision is what does **not** block 1.0: automatic declarative `#foreach` discovery, a broad Writer-field API, document-default redesign, universal named-object operations, document import, and renderer-neutral abstraction may all follow later without undermining the integrity of the 1.0 core.

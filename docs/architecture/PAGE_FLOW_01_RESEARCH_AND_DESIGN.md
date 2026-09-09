# PAGE-FLOW-01 — Page Styles, Paragraph Flow, and Section Flow

## Status

**Research and design phase. No public API or implementation decision is implied by this document.**

PAGE-FLOW-01 is the first mandatory architecture milestone on the post-RESEARCH-01 path to version 1.0.

The milestone investigates the native ODF/LibreOffice Writer semantics that determine page identity, page-style transitions, paragraph pagination behavior, and the behavior of structured content across page boundaries. The engine must understand enough of those semantics to preserve, address, and where justified generate them without implementing its own pagination engine.

The governing rule is:

> **The engine describes or preserves native ODF flow semantics; LibreOffice/Writer computes actual pagination.**

## 1. Why PAGE-FLOW-01 is a 1.0 blocker

Professional documents are not correct merely because their text, styles, tables, images, and Sections are structurally valid.

A document can still be visibly defective when:

- a heading is stranded at the bottom of a page;
- the first line of a logical entry is separated from the content it introduces;
- a paragraph splits in an undesirable way;
- a generated/repeated Section crosses a page boundary unexpectedly;
- the first page and following pages require different layout or page-owned content;
- an explicit page break loses or changes the intended page style;
- headers or footers are associated with the wrong page/master style.

The professional multi-page CV remains the primary practical stress test because it combines these problems naturally. It is not a reason to introduce CV-specific APIs.

## 2. Starting architecture

PAGE-FLOW-01 starts from the current `develop` architecture after RESEARCH-01.

Relevant established facts include:

- `OdtPackage` and `OdtDocumentContext` own package/document state;
- `StyleContext` owns document-local semantic style requirements;
- `Paragraph` can describe paragraph style options through semantic `StyleRequirement` values;
- `StyleMapper` already maps some flow-related options, including `keep-with-next`, `break-before`, and `break-after`;
- `PageLayoutManager` can mutate selected properties of an existing master-page/page-layout relationship;
- `PageLayoutOdtTemplate` exposes the current narrow page margin/layout facade;
- SECTION-03 provides exact native Section resolution, cloning, deterministic identity rewriting, nested instantiation, collection instantiation, local binding, prototype removal, rollback, and save/reopen behavior.

These capabilities are starting evidence, not proof that page/flow semantics are complete.

In particular:

> `PageLayoutManager` is currently a narrow page-layout mutator, not a complete page-style/page-template model.

## 3. Research principles

### 3.1 Semantics before API

Do not begin by designing methods such as `setFirstPageStyle()`, `keepTogether()`, or `addPageBreak()`.

First determine the actual Writer/ODF structures, ownership, inheritance, and transition behavior.

### 3.2 Preserve authored document semantics

LibreOffice-authored page styles, paragraph styles, Sections, headers, footers, and flow properties are first-class document semantics. The engine should not flatten or rebuild them unnecessarily.

### 3.3 Separate style semantics from pagination result

A paragraph or page style may request a flow behavior. The final physical page break remains a Writer layout result.

The engine must not attempt to predict page count or calculate available page height in PHP.

### 3.4 Characterize existing behavior before changing it

Where current APIs already emit flow-related properties, add characterization before refactoring or extending them.

Historical behavior that is surprising should be documented before deciding whether it should remain compatible.

### 3.5 Keep template-owned and PHP-owned authoring distinct

PAGE-FLOW-01 must support both:

- preserving and addressing page/flow semantics already authored in LibreOffice;
- generating semantic flow requirements from structured PHP content where application code legitimately owns that content.

These are related but not identical responsibilities.

## 4. Research map

PAGE-FLOW-01 is divided into four tightly related research areas. They may produce separate characterization fixtures/tests, but they belong to one architecture decision because their ODF semantics interact.

## 4.1 PAGE-FLOW-01A — Page Styles / Page Templates

### Questions

Investigate how Writer represents and resolves:

- `style:master-page`;
- `style:page-layout` and `style:page-layout-properties`;
- named page styles visible to the Writer author;
- first-page versus following-page behavior;
- page-style succession / next-style relationships;
- explicit page-style transitions;
- page number restart/continuation where relevant to a transition;
- left/right page behavior if it affects the core model;
- authored page styles that share or differ in page layout;
- the relationship between page styles and paragraph styles that trigger a page change.

### Initial hypotheses to test

1. A Writer-visible page style is not equivalent to only a `style:page-layout` node.
2. Page identity and page geometry should remain separate concepts in the engine model.
3. First-page/following-page behavior is likely a relationship among named page/master styles rather than a special CV/layout flag.
4. Existing `PageLayoutManager` behavior should probably remain a bounded compatibility/service path even if a broader page-style model is introduced.

No hypothesis is an API decision.

### Minimum fixture set

Create small Writer-authored fixtures for:

1. one ordinary page style;
2. First Page followed by a different page style;
3. an explicit manual page break that changes page style;
4. two page styles with visibly different geometry so transition behavior is easy to verify;
5. save/reopen and headless PDF rendering of the above.

Inspect at least `styles.xml` and `content.xml` for each fixture.

## 4.2 PAGE-FLOW-01B — Paragraph Flow Semantics

### Required 1.0 topics

Characterize:

- keep with next;
- keep paragraph/content together;
- widows;
- orphans;
- break before;
- break after;
- paragraph-triggered page-style transitions;
- direct formatting versus named paragraph-style ownership of these properties;
- inheritance/default behavior where it materially changes semantics.

### Existing implementation questions

The current mapping of `keep-with-next`, `break-before`, and `break-after` must be checked against Writer-authored ODF rather than treated as canonical merely because it exists.

Determine:

- exact ODF property names and accepted values;
- which properties belong in `style:paragraph-properties`;
- whether current option names represent the right semantic abstraction;
- whether boolean PHP options are sufficient or hide meaningful native values;
- how generated automatic paragraph styles interact with authored named paragraph styles;
- whether missing keep-together/widow/orphan support is a mapper gap, a public authoring gap, or both.

### Minimum fixture set

Create controlled documents where a visible amount of filler forces the relevant paragraphs near a page boundary:

1. heading + following paragraph with keep-with-next on/off;
2. multi-line paragraph with keep-together on/off where Writer exposes such semantics;
3. long paragraph with distinct widow/orphan settings;
4. break-before;
5. break-after;
6. paragraph-triggered page-style transition.

For each fixture record both XML representation and rendered pagination behavior.

## 4.3 PAGE-FLOW-01C — Section and Structured-Content Flow

### Core question

A native Writer Section is a structural container, but what actually controls its behavior at a page boundary?

Do not assume a generic Section-level "keep whole Section together" feature exists.

### Required scenarios

Characterize:

- a normal Section spanning a page boundary;
- a short Section that fits on the current page;
- a Section whose first child paragraph uses keep-with-next;
- a Section containing multiple paragraphs with keep semantics;
- nested Sections crossing a page boundary;
- repeated Section instances created through existing SECTION-03 mechanics;
- a table inside a Section near a page boundary;
- a list inside a Section near a page boundary;
- save/reopen behavior;
- headless PDF behavior.

### Structured-instantiation question

Existing `instantiateMany()` must remain a structural operation. It should not become responsible for deciding where instances paginate.

Research should determine whether cloning/instantiation already preserves all relevant authored flow semantics automatically and which generated style dependencies must be materialized for PHP-owned flow options.

### CV benchmark scenario

A useful generic fixture is an "entry block" Section containing:

```text
period
position/title
organization
2–4 detail lines
```

Multiple instances should be generated until a page boundary is crossed. The research question is not how to keep every entry unconditionally on one page; it is which native paragraph/Section semantics produce professional and predictable Writer pagination.

## 4.4 PAGE-FLOW-01D — Headers, Footers, and Page-Owned Content

Headers and footers are included in PAGE-FLOW-01 research because their ownership is tied to page/master styles.

### Questions

Investigate:

- where header/footer content is stored in ODF;
- whether it is owned directly by `style:master-page` or indirectly through another structure;
- first-page versus following-page header/footer behavior;
- left/right variants if they materially affect the core model;
- whether content can contain ordinary structured ODF content, fields, images, frames, tables, and placeholders;
- whether current template processing already reaches such content in `styles.xml`;
- how structured insertion and scalar replacement would need to address page-owned content;
- what must be preserved during page-style mutation or creation.

### Scope boundary

PAGE-FLOW-01 must understand header/footer ownership well enough that the page-style architecture is correct.

It does not automatically require a broad convenience API for every possible header/footer authoring operation before 1.0.

## 5. ODF evidence to collect

For every characterized behavior, record:

1. Writer authoring steps at a useful semantic level;
2. relevant `content.xml` fragment;
3. relevant `styles.xml` fragment;
4. any other package part involved;
5. whether the semantic value is stored in common style, automatic style, master page, page layout, paragraph, or another structure;
6. Writer open/save behavior;
7. headless PDF rendering behavior;
8. current engine preservation/mutation behavior;
9. whether generated structured content can express the same semantics today;
10. whether the result survives save/reopen through the engine.

Avoid relying on screenshots or GUI labels alone when the ODF structure is available.

## 6. Characterization before implementation

Before production changes, PAGE-FLOW-01 should establish tests around current behavior in at least these areas:

- current `PageLayoutManager` master-page/page-layout resolution and mutation;
- existing `StyleMapper` flow-property mapping;
- `Paragraph` semantic style requirement generation for existing flow options;
- preservation of authored paragraph flow styles during scalar/structured operations;
- Section clone/instantiate preservation of authored paragraph/style references;
- processing boundaries between `content.xml` and `styles.xml` where page-owned content is involved.

Tests should distinguish:

- existing intended behavior;
- accidental compatibility behavior;
- missing capability;
- actual defect.

## 7. Architecture questions to answer after research

PAGE-FLOW-01 must not move into implementation until the evidence is sufficient to answer the following.

### 7.1 Page style identity

What is the engine-level semantic identity of a page style/page template?

Possible native structures must be understood before deciding whether a typed target, document-style facade, page-layout service, or another abstraction owns the operation.

### 7.2 Definition versus reference

How should the architecture distinguish:

- referencing an existing LibreOffice-authored page style;
- mutating selected properties of an existing page style/layout;
- defining a generated page style;
- assigning/requesting a page style transition from content?

This should follow the STYLE-API-02 lesson that references and definitions are not the same operation.

### 7.3 Paragraph flow ownership

Should flow options remain ordinary paragraph style options, gain explicit fluent methods, be expressible through named style definitions, or use some combination of these layers?

The answer should follow actual semantics and authoring usability rather than API symmetry.

### 7.4 Section flow ownership

Which flow semantics belong to the Section itself, which belong to child paragraphs/tables/lists, and which are purely Writer layout consequences?

### 7.5 Page-owned content

How should headers/footers participate in:

- scalar template processing;
- structured target resolution;
- style/resource collection;
- package/document lifecycle;
- finalization/export?

### 7.6 Compatibility

Which existing public/protected page-layout and paragraph-style APIs must remain as facades, and which accidental surfaces can be retired deliberately before 1.0?

Compatibility should protect meaningful application behavior, not preserve obsolete architecture for its own sake.

## 8. Explicit non-goals

PAGE-FLOW-01 does **not** include:

- PHP-side page-height calculation;
- predicting exact page count without Writer rendering;
- a custom pagination/layout engine;
- CV-specific page or entry APIs;
- automatic declarative `#foreach` discovery;
- broad Writer-field/conditional-field APIs;
- general named-object operations;
- complete table geometry redesign beyond interactions required to understand page flow;
- complete frame positioning redesign beyond interactions required to understand page flow;
- document import/round-trip architecture;
- renderer-neutral document abstraction.

TABLE-LAYOUT-01 and FRAME-LAYOUT-01 remain subsequent mandatory 1.0 milestones.

## 9. Research artifacts

Exploratory Writer fixtures may be created under the project-local `research/` directory.

They are not automatically public samples or test fixtures. Version them only when they provide durable architecture evidence or are needed by characterization tests.

Do not modify or normalize unrelated files under `samples/output/` during research.

LibreOffice `.~lock.*#` files must never be committed.

## 10. Proposed research order

The initial order should be:

```text
A. Page style anatomy
   ↓
B. First Page → Following Page transition
   ↓
C. Paragraph keep/break/widow/orphan semantics
   ↓
D. Section behavior at page boundaries
   ↓
E. Repeated/nested Section flow
   ↓
F. Header/footer ownership and processing boundaries
   ↓
G. Current-engine characterization
   ↓
H. Architecture decision / Change Contract
```

This order starts with the native page model before asking generated content to interact with it.

## 11. Exit criteria for research/design

The research/design phase is complete when:

- the relevant Writer/ODF structures have empirical evidence;
- page style identity and page-layout ownership are understood;
- first/following-page behavior is characterized;
- paragraph flow properties are characterized and mapped against current engine behavior;
- Section/repeated-Section page-boundary behavior is characterized;
- header/footer ownership is understood sufficiently for the page-style model;
- active, compatibility, and missing paths are identified;
- 1.0 scope is separated from optional Writer features;
- architecture questions in section 7 have explicit decisions;
- a PAGE-FLOW-01 Change Contract can be written without inventing semantics.

Only then should implementation slices be planned.

## 12. Expected implementation shape — hypothesis only

The research may ultimately justify a shape in which:

- authored page styles remain native template-owned objects;
- document-local services resolve and mutate page-style/page-layout semantics;
- paragraph flow remains semantic paragraph-style data;
- structured Section operations preserve authored flow semantics;
- generated paragraph flow requirements materialize through the existing style architecture;
- headers/footers are recognized as page-owned structured content;
- Writer remains the pagination authority.

This is intentionally a hypothesis, not a Change Contract.

## 13. Relationship to the 1.0 sequence

PAGE-FLOW-01 is followed by:

```text
TABLE-LAYOUT-01
    ↓
FRAME-LAYOUT-01
    ↓
TEMPLATE-RELIABILITY-01
    ↓
FINALIZATION-01
    ↓
RELEASE-1.0 integration preflight
```

The purpose of PAGE-FLOW-01 is therefore bounded: establish reliable native page and flow semantics that the later 1.0 milestones can build on, without expanding into every possible Writer capability.

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

### Empirical findings

The first PAGE-FLOW-01A fixtures were authored manually in LibreOffice Writer and inspected directly at ODT package/XML level. They are research artifacts and are not yet committed as public samples or permanent test fixtures.

#### Page-style identity and page-layout geometry are distinct

A Writer document with `First Page` followed by `Standard` initially used the same page-layout definition for both master pages. After assigning a visibly different top margin to `First Page`, Writer emitted two separate page-layout definitions and referenced them from the two master pages.

Representative structure:

```xml
<style:page-layout style:name="Mpm1">
    <style:page-layout-properties
        fo:page-width="21.001cm"
        fo:page-height="29.7cm"
        fo:margin-top="2cm"
        fo:margin-bottom="2cm"
        fo:margin-left="2cm"
        fo:margin-right="2cm"/>
</style:page-layout>

<style:page-layout style:name="Mpm2">
    <style:page-layout-properties
        fo:page-width="21.001cm"
        fo:page-height="29.7cm"
        fo:margin-top="5.001cm"
        fo:margin-bottom="2cm"
        fo:margin-left="2cm"
        fo:margin-right="2cm"/>
</style:page-layout>
```

The corresponding master pages referenced those layouts independently:

```xml
<style:master-page
    style:name="Standard"
    style:page-layout-name="Mpm1"/>

<style:master-page
    style:name="First_20_Page"
    style:display-name="First Page"
    style:page-layout-name="Mpm2"
    style:next-style-name="Standard"/>
```

This confirms the initial hypothesis that page-style identity and page geometry are separate native concepts. A `style:master-page` represents page-style/master-page identity and relationships, while `style:page-layout` carries geometry and related layout properties.

The same page layout may be shared by multiple master pages when their geometry is identical. Writer may create distinct page layouts when their geometry diverges.

#### First-page selection is content-triggered

Writer selected the initial `First Page` master page through an automatic paragraph style in `content.xml`:

```xml
<style:style
    style:name="P1"
    style:family="paragraph"
    style:parent-style-name="Standard"
    style:master-page-name="First_20_Page">
    <style:paragraph-properties style:page-number="auto"/>
</style:style>
```

The first body paragraph referenced that automatic paragraph style.

The resulting semantic chain is therefore:

```text
first content paragraph
    ↓ automatic paragraph style
style:master-page-name="First_20_Page"
    ↓
First Page master page
```

The initial page style is therefore not inferred merely from physical page position. Content can explicitly request a master page through paragraph-style semantics.

#### Automatic page-style succession is a separate mechanism

The `First Page` master page used:

```xml
style:next-style-name="Standard"
```

The `Standard` master page did not require `style:next-style-name="Standard"`; omission leaves the current master page in effect for normal continuing page flow.

The characterized succession is:

```text
First Page
    ↓ style:next-style-name
Standard
    ↓ no successor requested
Standard ...
```

An accidental research state also proved useful: Writer temporarily serialized `Standard -> First Page -> Standard`, which caused a later page to receive the First Page style unexpectedly. Correcting `Standard` to have no `First Page` successor removed that behavior. This provides negative evidence that `style:next-style-name` is an active flow relationship rather than descriptive metadata.

#### Explicit page break and page-style request are distinct semantics

A later manual page break with an explicitly selected page style produced separate automatic paragraph-style semantics.

A pure page break was represented as:

```xml
<style:style
    style:name="P3"
    style:family="paragraph"
    style:parent-style-name="Standard">
    <style:paragraph-properties fo:break-before="page"/>
</style:style>
```

The explicit request for `First Page` was represented separately through another automatic paragraph style:

```xml
<style:style
    style:name="P4"
    style:family="paragraph"
    style:parent-style-name="Standard"
    style:master-page-name="First_20_Page">
    <style:paragraph-properties style:page-number="auto"/>
</style:style>
```

This establishes an important semantic distinction:

> **Forcing a new page and requesting a particular page style are different ODF operations, even when Writer exposes them together in one authoring action.**

`fo:break-before="page"` is paragraph-flow semantics. `style:master-page-name` is a page-style/master-page reference associated with content/paragraph-style semantics. Future APIs must not collapse these concepts merely because Writer's UI can configure them together.

The break fixture also provides early empirical confirmation for PAGE-FLOW-01B that page breaks live in `style:paragraph-properties`, matching the existing `StyleMapper` direction. The complete paragraph-flow mapping remains to be characterized in PAGE-FLOW-01B.

#### Header content is master-page-owned; header geometry is page-layout-owned

A controlled fixture assigned distinct headers to the `First Page` and `Standard` page styles while retaining different page geometry. Writer serialized each header as content owned directly by its corresponding master page:

```xml
<style:master-page
    style:name="Standard"
    style:page-layout-name="Mpm1">
    <style:header>
        <text:p text:style-name="Header">STANDARD PAGE HEADER</text:p>
    </style:header>
</style:master-page>

<style:master-page
    style:name="First_20_Page"
    style:display-name="First Page"
    style:page-layout-name="Mpm2"
    style:next-style-name="Standard">
    <style:header>
        <text:p text:style-name="Header">FIRST PAGE HEADER</text:p>
    </style:header>
</style:master-page>
```

Header geometry, however, was represented through `style:header-style` / `style:header-footer-properties` under the referenced page layout.

The resulting ownership model is therefore:

```text
Master Page / Page Style
├── identity
├── successor relationship
├── header/footer content
└── page-layout reference
        ↓
Page Layout
├── page size
├── margins
├── orientation
└── header/footer geometry
```

This is directly relevant to future page-style architecture: mutating page geometry and manipulating page-owned content are related through the master-page/page-layout relationship but are not the same responsibility.

#### Writer also supports first-page header variants within one master page

A separate exploratory fixture unexpectedly used a different native mechanism: the `Standard` master page contained both a normal header and a first-page-specific header:

```xml
<style:master-page style:name="Standard" style:page-layout-name="Mpm1">
    <style:header>...</style:header>
    <style:header-first>...</style:header-first>
</style:master-page>
```

This is distinct from using separate `First Page` and `Standard` master pages with separate `style:header` content.

The finding is retained because it demonstrates that Writer/ODF has more than one valid first-page/header authoring model. PAGE-FLOW-01 must not assume that all first-page differences require a separate master page. A broad convenience API for these variants is not implied by this finding.

#### Current architecture consequence

The empirical model is richer than the current `PageLayoutManager` abstraction:

```text
Content / Paragraph Flow
├── page break request
└── master-page request
        ↓
Master Page / Page Style
├── identity
├── successor relationship
├── page-owned content
└── page-layout reference
        ↓
Page Layout
└── geometry and header/footer layout properties
```

`PageLayoutManager` currently resolves an existing master page, follows its `style:page-layout-name`, and mutates selected `style:page-layout-properties`. That remains useful behavior, but it does not model page-style identity, succession, content-triggered page-style references, or page-owned content.

This strengthens the design constraint that a broader page-style model, if introduced, should not be implemented merely by adding unrelated responsibilities to `PageLayoutManager`.

### PAGE-FLOW-01A interim conclusions

The following findings are now supported by Writer-authored ODF evidence:

1. `style:master-page` and `style:page-layout` are distinct semantic structures.
2. A master page references page geometry rather than containing it directly.
3. Multiple master pages may share a page layout; different geometry can produce separate layouts.
4. First-page selection can be triggered from content through `style:master-page-name` on paragraph-style semantics.
5. Automatic page-style succession uses `style:next-style-name` on the master page.
6. A normal continuing page style does not need to point to itself explicitly.
7. Explicit page breaking and explicit page-style selection are separate semantics.
8. Header/footer content belongs to the master-page layer, while header/footer geometry belongs to the page-layout layer.
9. Writer also supports first-page-specific header content within a single master page via `style:header-first`.
10. The current `PageLayoutManager` is confirmed as a narrow geometry mutator rather than a complete page-style/page-template abstraction.

These conclusions are architecture evidence, not yet a public API decision.

### Remaining PAGE-FLOW-01A questions

The core page-style anatomy needed to proceed to paragraph-flow research is now sufficiently characterized. The following details remain candidates for later PAGE-FLOW-01 research where they become material to the 1.0 model:

- page number restart/continuation semantics associated with transitions;
- left/right page variants;
- footer variants corresponding to the characterized header ownership model;
- full save/reopen/headless-PDF characterization through the engine rather than Writer alone;
- exact processing boundaries for placeholders/structured content inside master-page-owned header/footer content.

Those questions should not block PAGE-FLOW-01B unless evidence shows they affect paragraph-flow architecture.

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

The PAGE-FLOW-01A fixtures already establish that normal header content is directly owned by `style:master-page`, while header geometry is represented in the referenced page layout. They also establish the existence of `style:header-first` as a distinct first-page variant within one master page. PAGE-FLOW-01D should build on those facts rather than rediscover them.

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

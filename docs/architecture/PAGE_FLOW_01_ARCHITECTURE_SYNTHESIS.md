# PAGE-FLOW-01 — Architecture Synthesis

## Status

**Research synthesis complete. Ready for Change Contract.**

This document synthesizes PAGE-FLOW-01A through PAGE-FLOW-01D and answers the architecture questions raised by `PAGE_FLOW_01_RESEARCH_AND_DESIGN.md`.

It does not by itself authorize production implementation or define a public API.

The governing rule is unchanged:

> **The engine describes or preserves native ODF flow semantics; LibreOffice/Writer computes actual pagination.**

## 1. Evidence base

PAGE-FLOW-01 now has four complementary evidence sets:

- PAGE-FLOW-01A — page-style/master-page and page-layout anatomy;
- PAGE-FLOW-01B — paragraph flow semantics;
- PAGE-FLOW-01C — Section and structured-content flow;
- PAGE-FLOW-01D — headers, footers, and page-owned content.

Companion evidence documents include:

```text
docs/architecture/PAGE_FLOW_01B_PARAGRAPH_FLOW_EVIDENCE.md
docs/architecture/PAGE_FLOW_01C_SECTION_FLOW_EVIDENCE.md
docs/architecture/PAGE_FLOW_01D_PAGE_OWNED_CONTENT_EVIDENCE.md
```

The combined evidence is sufficient to distinguish native ODF ownership from Writer pagination consequences and to plan bounded implementation work without inventing a pagination model.

## 2. Unified native model

The research supports the following model:

```text
Content / Paragraph Flow
├── paragraph style reference
├── keep/break/widow/orphan semantics
└── optional master-page request
        ↓
Master Page / Page Style
├── native identity
├── successor relationship
├── page-owned header/footer content
└── page-layout reference
        ↓
Page Layout
├── page geometry
└── header/footer geometry

Structured Containers
├── Section
├── Table
├── List
└── Frame
    ↓
retain their own native semantics

Writer Layout Engine
└── computes physical pagination from the document model
```

The important point is that these layers cooperate but must not be collapsed into one page-layout abstraction.

## 3. Page-style identity

### Decision

The engine-level semantic identity of a Writer page style is the native **master-page/page-style object represented by `style:master-page`**, not the referenced `style:page-layout`.

A page layout is a separate geometry/resource object referenced by the master page.

### Consequences

- Page identity and page geometry remain distinct concepts.
- Multiple page styles may share one page layout.
- A change in geometry does not redefine page-style identity.
- A future page-style target/service, if needed, must resolve master-page identity first and follow its page-layout reference only for geometry operations.
- `PageLayoutManager` remains conceptually narrower than a complete page-style model.

No public `pageStyle()` or `masterPage()` API is authorized by this decision alone.

## 4. Page-style selection and succession

### Decision

Initial or explicit page-style selection and automatic page-style succession are distinct native mechanisms.

The research established:

```text
content paragraph style
    └── style:master-page-name
            ↓
        selected master page

master page
    └── style:next-style-name
            ↓
        automatic successor master page
```

A page break is separate again:

```text
fo:break-before="page"
```

is paragraph-flow semantics and is not equivalent to requesting a particular page style.

### Consequences

- The engine must not collapse “new page” and “use page style X” into one semantic operation.
- Existing and future APIs should distinguish reference/selection from flow break semantics.
- Writer remains responsible for applying succession and assigning physical pages.

## 5. Definition versus reference

### Decision

PAGE-FLOW follows the same semantic discipline established by STYLE-API-02:

- **reference** an existing Writer-authored page style;
- **mutate** selected properties of an existing referenced page layout;
- **define** a generated page/master style only if a concrete application requirement justifies it;
- **request** a page-style transition from content as a separate operation.

These operations must not be represented as one generic page-style setter.

### 1.0 consequence

The research does **not** establish a requirement to add a broad generated-page-style definition API before 1.0.

Native-first authoring should prefer LibreOffice-authored page styles where the template owns layout.

## 6. Paragraph flow ownership

### Decision

Paragraph pagination behavior belongs to paragraph-style semantics.

Established mappings include:

| Meaning | ODF property |
| --- | --- |
| keep with next | `fo:keep-with-next="always"` |
| keep paragraph together | `fo:keep-together="always"` |
| orphans | `fo:orphans="N"` |
| widows | `fo:widows="N"` |
| page break before | `fo:break-before="page"` |
| page break after | `fo:break-after="page"` |

These properties may be owned by authored named styles, inherited through paragraph-style hierarchy, or generated through automatic paragraph styles.

### Consequences

- PAGE-FLOW does not introduce a parallel pagination object model.
- Generated flow semantics should continue through the existing semantic style architecture.
- Missing friendly mappings for `keep-together`, widows, and orphans are bounded capability gaps, not evidence for a new flow subsystem.
- Whether convenience fluent methods are desirable is an authoring-UX decision; it is not required by the semantic model.

## 7. Section flow ownership

### Decision

A native `text:section` is a structural/content ownership container, not a generic pagination container.

The research found no general Section-level equivalents of:

```text
keep whole section together
break before section
break after section
widows/orphans for section
page-style assignment on section
```

Instead, contained paragraphs, tables, lists, frames, and nested Sections retain their own semantics.

### Consequences

- `SectionInstantiationService` and `instantiateMany()` remain structural operations.
- SECTION-03 must preserve authored style references and contained flow semantics but must not decide pagination.
- No `keepSectionOnPage()` API should be introduced as a PAGE-FLOW shortcut.
- Table-specific pagination remains TABLE-LAYOUT-01 work.

## 8. Page-owned header/footer content

### Decision

Header/footer content is a normal structured ODF content domain owned by `style:master-page`.

Header/footer geometry is owned by the referenced page layout.

Current engine behavior already demonstrates that page-owned content can participate in:

- scalar template processing;
- nested scalar replacement inside native structures such as tables;
- native Writer field preservation;
- generic structured insertion for ordinary `OdtElement` content;
- established `setImage()` image insertion;
- package resource lifecycle;
- save/reopen.

### Consequences

- No separate header/footer template language is required.
- No separate page-owned scalar processor is required.
- Page-owned content must remain part of the document lifecycle and finalization model.
- A future target/addressing API for page-owned native objects is a separate capability question and is not required merely to process placeholders there.

## 9. First/left/right variants

### Decision

PAGE-FLOW must preserve the fact that Writer/ODF supports multiple native mechanisms and variants, including:

- separate First Page and Standard master pages;
- `style:next-style-name` succession;
- first-page-specific header variants within a master page such as `style:header-first`;
- left/right header/footer variants where authored.

### 1.0 boundary

The engine must not design an abstraction that makes these native forms impossible or silently normalizes them into one mechanism.

However, PAGE-FLOW-01 does not require exhaustive convenience APIs for every variant before 1.0.

## 10. Current active, compatibility, and missing paths

### Active/native-first paths

- Writer-authored page/master styles and page layouts;
- paragraph style semantics for keep/break behavior;
- structured Sections preserving contained styles;
- scalar and structured template processing across `content.xml` and `styles.xml`;
- semantic document-local style materialization for generated structured content.

### Compatibility/bounded existing paths

- `PageLayoutManager` as a narrow existing-layout geometry mutator;
- existing public image convenience behavior such as `setImage()`;
- any protected facade behavior still required for meaningful polymorphic compatibility.

These should not be promoted into a larger architecture merely because they already exist.

### Missing or incomplete 1.0 capabilities

The research identifies a small set of bounded gaps:

- friendly/semantic coverage for paragraph `keep-together`;
- friendly/semantic coverage for widows;
- friendly/semantic coverage for orphans;
- explicit page-style reference/transition support only if current public authoring cannot express the required 1.0 scenarios;
- tests that lock down preservation of page-style/master-page relationships across relevant engine operations.

Generated page-style definition, exhaustive header/footer APIs, page-number restart APIs, and left/right convenience APIs are not automatically required.

## 11. Compatibility decision

Compatibility protects meaningful behavior, not historical mechanism.

### Keep

- existing page-layout behavior that applications can reasonably depend on;
- authored ODF structures and references across load/render/save;
- public paragraph/style behavior already used by supported authoring paths;
- structural Section behavior established by SECTION-03.

### Do not preserve merely for architecture history

- accidental internal coupling between page identity and page layout;
- implementation details that can be replaced behind a compatibility facade;
- duplicated mutable state;
- unsupported assumptions about Sections controlling pagination;
- any need for PHP-side pagination calculations.

## 12. `ImageElement` header discrepancy — bounded finding

PAGE-FLOW-01D exposed a real but narrow discrepancy:

```text
ImageElement in normal body content      ✓ Writer-visible
ImageElement in page-owned header        ✗ not Writer-visible
setImage() in page-owned header           ✓ Writer-visible
```

The package resource, manifest entry, `draw:image` reference, and save/reopen behavior are intact in the failing case.

Changing only the generated graphic parent style from `Standard` to `Graphics` did not make the image visible, so that hypothesis was falsified.

### Decision

Do not expand PAGE-FLOW-01 into a general graphic refactor.

Record the discrepancy and investigate it later in a focused graphic/structured-materialization compatibility pass unless new evidence shows that it blocks a required PAGE-FLOW implementation slice.

## 13. Research-method refinement

PAGE-FLOW-01 established a more efficient architecture-research method:

> **Specification first. Existing engine code second. Writer verification third. Isolated experiments only where semantics, serialization, interoperability, or engine behavior remain uncertain.**

This preserves the project principle “Semantics before implementation” while avoiding unnecessary one-fixture-per-question experimentation when the specification already answers ownership or allowed-content questions.

## 14. Answers to the original architecture questions

### 14.1 Page style identity

`style:master-page` carries page-style/master-page identity; `style:page-layout` carries referenced geometry.

### 14.2 Definition versus reference

Reference, mutation, definition, and transition request are separate operations. No generic page-style setter should collapse them.

### 14.3 Paragraph flow ownership

Flow semantics remain paragraph-style semantics and should use the existing document-local style architecture.

### 14.4 Section flow ownership

Sections own structure; contained native elements own their respective flow semantics; Writer owns physical pagination.

### 14.5 Page-owned content

Headers/footers participate in normal template processing, resource lifecycle, and finalization while remaining master-page-owned native content.

### 14.6 Compatibility

Preserve meaningful public behavior and authored ODF semantics; do not preserve obsolete internal architecture merely because it exists.

## 15. 1.0 architecture boundary

PAGE-FLOW-01 authorizes a **bounded flow capability**, not a Writer page-layout framework.

The 1.0 implementation should focus only on gaps necessary to make the researched native semantics reliably expressible and preservable:

```text
preserve authored page/master semantics
        +
complete required paragraph flow mappings
        +
keep page-break and page-style reference semantics distinct
        +
preserve page-owned content processing
        +
characterize regression behavior
```

It should not include:

- PHP pagination;
- exact page-count prediction;
- page-height calculations;
- CV-specific page logic;
- automatic “keep this Section on one page” behavior;
- exhaustive page-style/header/footer authoring APIs;
- table pagination redesign;
- frame geometry redesign.

## 16. Readiness for Change Contract

The PAGE-FLOW-01 research exit criteria are now sufficiently satisfied to write a Change Contract without inventing semantics.

The Change Contract should be implementation-oriented and answer only:

1. which existing behavior receives permanent characterization coverage;
2. which missing paragraph-flow mappings are added;
3. whether a minimal page-style reference/transition capability is actually required for 1.0;
4. which existing page-layout APIs remain compatibility facades;
5. which production files may change;
6. which behaviors are explicitly deferred to TABLE-LAYOUT-01, FRAME-LAYOUT-01, TEMPLATE-RELIABILITY-01, or later work;
7. which manual LibreOffice regressions are mandatory before merge.

No implementation should begin until that contract is accepted.

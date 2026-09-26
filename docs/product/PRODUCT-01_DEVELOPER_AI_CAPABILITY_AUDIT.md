# PRODUCT-01 — Developer & AI Capability Audit

## 1. Purpose

This audit steps outside the implementation roadmap and asks a product question:

> If a developer or coding agent wants to create, inspect, modify, validate, or compose a professional native ODT document, which capabilities should the ODT Template Engine reasonably provide?

The goal is not to define APIs prematurely. It is to identify capability gaps, user value, AI-agent value, and architectural leverage before the next implementation milestones are prioritized.

This audit treats the repository and current documentation as the source of truth for existing capabilities. External libraries are used only as product/ergonomic benchmarks, not as architecture templates.

## 2. Current product position

The current engine already has a substantial foundation:

- scalar placeholders and filters;
- conditions and foreach blocks;
- structured `OdtElement` insertion;
- `RichText` and `Paragraph`;
- native lists;
- native tables and styled table cells;
- images and named image replacement;
- editable text boxes;
- HTML import;
- metadata;
- page-layout manipulation;
- style mapping and style writing;
- package/resource/manifest handling;
- typed frame/table target resolution;
- a standalone `OdtTemplate` facade backed by package/document/services;
- an established ODT → LibreOffice → PDF → PNG visual-regression workflow.

The public product model is already stronger than a pure mail-merge library:

```text
Simple template processing
        +
structured native ODT elements
        +
existing native template-object resolution
        +
real editable ODT output
```

The remaining product question is how far this should grow into a native OpenDocument composition and inspection API.

## 3. Product principles

The audit recommends the following product principles.

### 3.1 LibreOffice remains the visual template designer

The engine should not try to rebuild LibreOffice layout in PHP. Where possible, complex visual structure should be authored in an ODT template and addressed semantically from PHP.

### 3.2 Native ODF semantics over visual hacks

Prefer paragraphs, lists, tables, styles, frames, page styles, sections, and native document objects over simulated layout based on spaces, empty paragraphs, or HTML/CSS emulation.

### 3.3 Simple template language, structured API for complex work

The visible placeholder language should remain relatively small. Complex composition belongs in `OdtElement`/structured APIs and, later, named template-object operations.

### 3.4 Introspection is a first-class capability

A mature document engine should be able not only to write a document but also to explain what is in it.

This is especially important for coding agents, which can generate much better code if they can first inspect a template and receive deterministic structured information.

### 3.5 Diagnostics should be machine-actionable

Errors such as missing placeholders, ambiguous named targets, unsupported structures, unresolved values, or incompatible operations should be exposed explicitly rather than only producing malformed or surprising output.

### 3.6 Document-scoped state

Defaults, styles, assets, and other mutable document configuration should be scoped to the current document rather than process-wide global/static state.

## 4. External benchmark signals

The following libraries show useful capability/ergonomic patterns. They are not direct replacement targets.

### PHPWord

PHPWord exposes a broad document-construction model including sections, headers/footers, page settings, default font/paragraph styles, hyperlinks, footnotes/endnotes, table-of-contents, page breaks, tables, images, charts, fields, and template operations such as block/row cloning and replacement.

References:

- https://phpoffice.github.io/PHPWord/index.html
- https://phpoffice.github.io/PHPWord/usage/template.html

Product signal for this engine:

- page/section structure matters to developers;
- cloning template blocks/rows is highly useful;
- defaults and readable document-level settings are expected ergonomics;
- not every PHPWord feature is appropriate for ODT Template Engine.

### Docxtemplater

Docxtemplater emphasizes office-authored templates with data-driven placeholders, conditions, loops, images, HTML, tables, search/replace, and subtemplates/modules.

References:

- https://docxtemplater.com/
- https://docxtemplater.com/docs/tag-types/

Product signal:

- non-programmers designing templates is a strong model;
- subtemplates/document inclusion and search/replace are useful composition capabilities;
- template syntax should remain approachable rather than becoming a general-purpose programming language.

### odfdo / ODFDOM

ODF-focused libraries expose rich read/introspection APIs. `odfdo`, for example, can retrieve styles, styled elements, paragraphs, images, frames, tables, sections, headers, document parts, style properties, and language. ODFDOM similarly exposes content/style/meta DOMs, master pages, tables, styles, and package parts.

References:

- https://jdum.github.io/odfdo/reference_document.html
- https://jdum.github.io/odfdo/reference_element.html
- https://odftoolkit.org/api-0.10/odfdom/org/odftoolkit/odfdom/doc/OdfDocument.html

Product signal:

> A native ODT library feels much more complete once read/inspection operations exist alongside write operations.

This is particularly relevant to future AI-assisted programming.

## 5. Capability matrix

Ratings:

- Developer value: Low / Medium / High / Very high
- AI-agent value: Low / Medium / High / Very high
- Architectural leverage: how strongly the capability unlocks later work

| Capability | Current state | Developer value | AI-agent value | Architectural leverage | Audit priority |
|---|---|---:|---:|---:|---:|
| Template variables inspection | Partial (`extractTemplateVariables()`) | High | Very high | High | P0 |
| General document parser / inspection model | Missing | Very high | Very high | Very high | P0 |
| Style inspection (`getStyles`, properties, inheritance) | Missing/low-level only | High | Very high | Very high | P0 |
| Page/layout getters (`getMargins`, size, orientation) | Mostly write-oriented | High | High | High | P0 |
| Named object inventory | Resolver foundation only | Very high | Very high | Very high | P0 |
| Validation / diagnostics API | Fragmented through exceptions/tests | Very high | Very high | High | P0 |
| Named object content replacement | Deferred | Very high | Very high | Very high | P1 |
| Named object removal | Deferred | High | High | High | P1 |
| Template clone / template instance | Deferred | Very high | Very high | Very high | P1 |
| Exact/structural clone semantics | Designed, deferred | High | High | Very high | P1 |
| Document defaults | Planned | Very high | High | Very high | P1 |
| Document-scoped style context | Planned | High | High | Very high | P1 |
| Reliable table width/column width | Incomplete | Very high | High | High | P1 |
| Headers/footers | Missing as first-class API | High | High | High | P1 |
| Page breaks / keep-with-next / keep-together | Limited/missing | High | High | High | P1 |
| Page styles/master pages/sections | Partial low-level support | High | High | Very high | P1 |
| Unified frame positioning | Incomplete | High | High | High | P1 |
| Image fit/crop/aspect behavior | Partial | High | High | Medium | P1 |
| General text search/replace | Limited to template paths | Medium-high | High | Medium | P2 |
| Subtemplate/document inclusion | Missing | High | High | High | P2 |
| Hyperlinks/bookmarks | Missing/limited | Medium-high | Medium | Medium | P2 |
| Footnotes/endnotes | Missing | Medium | Medium | Medium | P2 |
| Table of contents | Missing | Medium | Medium | Medium | P2 |
| Fields/page numbers/date fields | Missing/limited | Medium-high | Medium-high | Medium | P2 |
| Comments/annotations | Missing | Low-medium | Medium | Low | P3 |
| Tracked changes | Missing | Low-medium | Medium | Medium | P3 |
| Charts/shapes/forms/OLE | Missing | Low | Low-medium | Low | P3/later |
| Full HTML/CSS rendering | Intentionally limited | Low strategic fit | Low | Negative | Do not pursue broadly |

The priority levels are product-audit recommendations, not approved roadmap milestones.

## 6. Highest-value capability: document/template inspection

### 6.1 Why inspection matters

Today the engine is strongest in generation and transformation. A developer who receives an unfamiliar ODT still needs detailed ODF/XML knowledge to answer basic questions such as:

- Which placeholders exist?
- Which named frames exist?
- Which are images versus text boxes?
- Which named tables exist?
- Which paragraph/text/table/graphic styles exist?
- Which style does an element use?
- What are the page margins and orientation?
- Which master pages/page styles exist?
- Which images/resources are in the package?
- Which sections, headers, footers, lists, and tables exist?

A parser/inspection layer would turn an opaque ZIP/XML package into a machine-readable document description.

### 6.2 AI-agent use case

A coding agent could perform:

```text
inspect template
    ↓
receive deterministic document inventory
    ↓
identify placeholders / named structures / styles
    ↓
generate PHP against the actual template
    ↓
validate result
```

This reduces hallucinated API usage and guessed template structure.

### 6.3 Possible semantic capabilities

API names are deliberately not fixed by this audit, but useful capabilities include:

```text
get variables
get named frames
get named tables
get images
get text boxes
get paragraphs / headings
get styles
get style properties
get page layouts / margins / orientation
get master pages
get package assets
get metadata
get unresolved template expressions
```

The parser should expose semantic ODT concepts rather than a public generic XPath API.

## 7. Diagnostics and validation

A high-value diagnostic layer should eventually be able to answer:

```text
Template diagnostics
├── unresolved placeholders
├── malformed template expressions
├── duplicate/ambiguous named objects
├── missing named targets
├── operation/type mismatch
├── unsupported structures
├── missing assets
├── broken package references
└── invalid or suspicious style references
```

Potential data-binding diagnostics:

```text
provided values not used by template
required placeholders with no supplied value
foreach data with missing row fields
structured element targeted at absent placeholder
```

For humans this improves debugging. For coding agents it creates a feedback loop that can be acted on automatically.

The diagnostic API should prefer structured result objects/enums over parsing human log strings.

## 8. Named template objects

ARCH-05 already created an important foundation: native target identity is type-specific, for example `draw:name` for frames and `table:name` for tables.

The product opportunity is to let LibreOffice remain the visual designer while PHP manipulates named structures.

High-value semantic operations include:

- inspect named object;
- replace named object content while preserving the container/layout;
- replace the whole named object;
- remove named object;
- clone named object;
- instantiate a named template object with local placeholder evaluation.

This is strategically stronger than adding many more formatting switches because it lets developers reuse sophisticated layouts authored visually.

## 9. Template Clone / Template Instance

This capability has exceptional product leverage.

Example: a CV template contains one visually designed experience block with fields such as date, role, employer, and description.

Instead of recreating the layout in PHP, an application could conceptually:

```text
find named template block "experience_entry"
        ↓
clone / instantiate once per experience
        ↓
evaluate placeholders locally for each clone
        ↓
preserve layout, styles and visual structure
```

This should remain distinct from textual foreach. Textual foreach repeats template-language regions; template instances operate on native named ODT structures.

Exact Clone, Template Clone/Instance, and Structural Clone remain different semantics and should not be collapsed into one ambiguous `clone()` operation.

## 10. Read APIs and resolved values

The user-facing engine is currently write-heavy. A rounded API should increasingly support read-modify-write workflows.

Examples of useful questions:

```text
What are the current page margins?
Which page style is active?
Which styles exist?
What properties does style X resolve to?
Which images and frames exist?
What width does this table/column currently have?
What metadata/language is set?
Which placeholders remain unresolved?
```

A key future distinction is likely needed between:

- declared/raw properties;
- inherited properties;
- resolved/effective properties.

For example, `getStyle()` and `getEffectiveStyle()` are semantically different concepts even if final API names differ.

This distinction matters strongly for ODF because style inheritance and default styles can make the effective appearance differ from local attributes.

## 11. Document defaults and style context

`DOCUMENT-DEFAULTS-01` remains a high-value feature, but PRODUCT-01 reframes why it matters.

Developer intent is naturally expressed at document level:

```text
Use Arial 10pt throughout this document.
Use these paragraph spacing defaults.
Use A4 portrait with these margins.
```

The important precedence semantics remain:

```text
document default
    ↓
named/element style
    ↓
explicit local override
```

This work should coordinate with `STYLE-CONTEXT-01`. New document defaults must not become additional global static `StyleMapper` state.

Inspection should eventually make defaults readable as well as writable.

## 12. Page and section structure

A professional document composition engine should eventually cover:

- page styles and master pages;
- sections;
- headers and footers;
- different first-page behavior;
- page numbers/fields;
- explicit page breaks;
- keep-with-next;
- keep-together / avoid splitting important blocks;
- controlled page-style transitions.

These capabilities are especially important for reports, contracts, invoices, letters and CVs.

They should use native ODF structure rather than simulated blank paragraphs or layout hacks.

## 13. Table capability gaps

Tables are a core professional-document primitive. Current table construction is useful, but a rounded product should make these scenarios reliable and explicit:

- overall table width;
- absolute column widths;
- relative column widths;
- row height/minimum height;
- cell vertical alignment;
- repeated header rows;
- merge/rowspan/colspan reliability;
- table alignment and positioning;
- predictable page splitting where ODF supports it.

Existing `TABLE-LAYOUT-*` backlog items remain relevant and likely deserve high product priority.

## 14. Frames, images and text boxes

The existing engine already contains images, frames and editable text boxes, but the shared layout semantics should become easier to reason about.

A useful future model should cover concepts such as:

- anchor type;
- horizontal/vertical position;
- relation target;
- wrap mode;
- size;
- aspect-ratio preservation;
- fit/fill/crop behavior where native ODF permits;
- z-order;
- named frame identity.

The product goal is not to reproduce a browser layout engine. It is to expose the native ODF frame model predictably.

## 15. Composition features worth considering later

The following capabilities would make the product more complete but should generally follow the higher-leverage work above:

### Subtemplates / document inclusion

Insert or compose content from another ODT/template. This is useful for reusable clauses, cover pages, appendices, report sections, and shared corporate components.

Resource/style/reference reconciliation makes this non-trivial and should be designed after style/asset ownership is clearer.

### Search and replace

General semantic search/replace beyond template placeholders can be useful for document editing workflows. It should remain ODF-aware and formatting-preserving.

### Hyperlinks and bookmarks

Common business-document primitives and useful anchors for generated documents.

### Footnotes/endnotes

Important for academic/legal/reporting use cases but not a current core benchmark.

### Table of contents / headings / outline

Useful once heading semantics and page/section structure are stronger.

### Fields

Page numbers, date/time fields and document variables are common professional-document capabilities.

## 16. Features that should not drive the near-term roadmap

### 16.1 Full HTML/CSS renderer

The engine should not compete with browser/PDF layout engines. HTML import should remain a pragmatic supported subset mapped to native ODF.

### 16.2 Full programming language inside templates

The template language should remain useful for simple data binding and light logic. Complex document construction belongs in structured APIs and named template objects.

### 16.3 Generic public XPath/XML mutation API

Developers can always manipulate XML themselves if absolutely necessary. The engine's product value comes from semantic ODF APIs, not exposing arbitrary internal XPath as the main interface.

### 16.4 Charts, complex shapes, OLE and office forms as early priorities

These capabilities may be useful eventually but have substantially lower strategic leverage than inspection, diagnostics, named objects, document defaults, page structure, tables and frames.

### 16.5 Becoming a format-conversion engine

ODT → PDF conversion is useful for testing and workflows, but LibreOffice/other converters should remain external tools. Native editable ODT is the product center.

## 17. AI-friendly API characteristics

A library used by coding agents benefits from properties that also improve human developer experience:

- predictable class and method naming;
- explicit types and return values;
- immutable/read-only inspection results where appropriate;
- structured exceptions and diagnostic codes;
- no hidden process-wide mutable state;
- clear separation of read, write and destructive operations;
- deterministic target resolution;
- explicit missing/ambiguous/type-mismatch behavior;
- easy discovery of document capabilities;
- examples that map directly to real tasks;
- APIs whose names express ODF semantics instead of implementation details;
- small orthogonal operations rather than large option bags with ambiguous interactions.

A future AI agent should ideally be able to inspect a template, understand what operations are available, perform them, validate the result, and report remaining problems without requiring raw XML reasoning for normal use cases.

## 18. Recommended product capability groups

Rather than immediately resuming the existing technical roadmap, PRODUCT-01 recommends discussing the following capability groups first.

### Group A — Inspect and understand

Highest strategic priority.

- document/template parser;
- variable inventory;
- named object inventory;
- style inventory and style-property inspection;
- page/layout inspection;
- asset/resource inspection;
- metadata inspection;
- structured diagnostics.

### Group B — Manipulate visually authored structures

- named object content replacement;
- named object replacement/removal;
- template clone/template instances;
- exact/structural clone where justified.

### Group C — Coherent document configuration

- document defaults;
- document-scoped style context;
- asset lifecycle/context;
- read/write symmetry for document settings.

### Group D — Professional page/document composition

- page/master-page/section model;
- headers/footers;
- page fields/numbers;
- page breaks;
- keep-with-next/keep-together.

### Group E — Layout reliability

- tables;
- frames/images/text boxes;
- lists;
- cell alignment;
- relative/absolute sizing.

### Group F — Secondary document primitives

- hyperlinks/bookmarks;
- footnotes/endnotes;
- TOC/outline;
- subtemplates;
- general search/replace;
- annotations/tracked changes where real use cases justify them.

## 19. Preliminary strategic ranking

The audit's current ranking by combined developer value, AI-agent value and architectural leverage is:

1. **Document/template inspection and parser capabilities**
2. **Structured diagnostics and validation**
3. **Named template objects + Template Clone / Template Instance**
4. **Document defaults + document-scoped style state**
5. **Professional page/section structure**
6. **Reliable tables**
7. **Unified frame/image/text-box layout**
8. **Subtemplates/search/replace and common document primitives**

This ranking is deliberately not yet a roadmap decision.

## 20. Key conclusion

The strongest future identity for the project is not merely:

> PHP placeholder replacement for ODT.

A more distinctive product direction is:

> A native PHP API for inspecting, templating and composing editable OpenDocument Text documents, with LibreOffice serving as the visual template designer.

For coding agents, the compelling workflow becomes:

```text
inspect ODT
    ↓
understand variables, styles and named structures
    ↓
select semantic operations
    ↓
modify / populate / clone
    ↓
validate
    ↓
save native editable ODT
```

The existing ARCH-01 through ARCH-07 work provides a strong structural foundation for this direction. `OdtPackage`, `OdtDocumentContext`, typed target resolution, structured materialization and the standalone `OdtTemplate` facade make inspection and semantic manipulation realistic next capabilities without returning to a God-class architecture.

## 21. Recommended next product-design step

Before choosing the next implementation milestone, perform a focused follow-up design exercise around **Group A — Inspect and understand**.

Questions to resolve include:

- What should a document parser expose without leaking raw DOM as the primary API?
- Which inspection results should be value objects?
- Which concepts can be read reliably from `content.xml` versus `styles.xml`?
- How should styles expose raw, inherited and effective properties?
- How should named target inventory relate to `TemplateTargetResolver`?
- Should inspection be accessible through `OdtTemplate`, a dedicated read-only document inspector, or both?
- How should diagnostics be represented for both humans and coding agents?

Do not implement these APIs until their semantics have been audited against real ODF documents and the current sample corpus.

# RESEARCH-01 — Native ODF Authoring Capabilities

## Status

**Research / discovery. No public API or implementation decision is implied by this document.**

RESEARCH-01 investigates which authoring, template, layout, style, page-flow, and addressable-object semantics are already provided natively by ODF and LibreOffice Writer, and how the ODT Template Engine should make use of them.

The research is intentionally prior to the next major implementation milestone. Its results will be used to define the remaining scope for version 1.0, post-1.0 development, and—only after the technical and product foundations are understood—possible commercial capabilities built on top of the engine.

## 1. Motivation

The engine has evolved from placeholder-oriented ODT processing toward a structured document model. Recent work on named sections, bookmarks, typed targets, structured insertion, semantic style requirements, and document-local style ownership has shown that important problems can often be solved more naturally by preserving and addressing native ODF structures than by reproducing document semantics in PHP.

This changes the planning question.

The primary research question is no longer only:

> Which ODT feature should the engine implement next?

It is:

> Which semantics do ODF and LibreOffice Writer already provide, and how can the engine expose or preserve them in a way that is useful to template authors and PHP developers?

The engine should not rebuild Writer's layout and document model in PHP where native ODF semantics already provide a stronger solution.

At the same time, **native-first is not a dogma**. A native Writer mechanism is not automatically preferable when a simple engine feature is substantially clearer, safer, or easier to author. The relevant criteria are authoring clarity, ODF fidelity, reliable processing, interoperability, and developer usability.

## 2. Research principles

### 2.1 Semantics before implementation

Research must establish actual ODF/LibreOffice behavior before public APIs are designed.

A research finding does not imply a public API decision.

### 2.2 Preserve the two authoring perspectives

The engine serves two distinct but complementary authoring modes:

- **Template-driven authoring:** a template author or editor designs and maintains the document in LibreOffice without needing to program document layout in PHP.
- **Code-driven authoring:** a developer generates or changes document structure through the PHP API.

Neither mode should accidentally erase the value of the other.

### 2.3 LibreOffice should remain a visual template designer

Where practical, document structure, layout, and reusable styles should be authored in LibreOffice and preserved by the engine rather than reconstructed in application code.

### 2.4 Existing syntax remains valid until deliberately reconsidered

RESEARCH-01 may discover native alternatives to `{{variable}}`, filters, conditions, loops, or other template-language constructs. Such discoveries do not deprecate or remove existing syntax by themselves.

Any later compatibility or product decision must be explicit.

### 2.5 Separate evidence from product design

For each research topic, distinguish:

1. **Observation** — what LibreOffice exposes to an author.
2. **ODF evidence** — how the behavior is represented in `content.xml`, `styles.xml`, or other package parts.
3. **Behavioral evidence** — what happens when Writer opens, updates, saves, exports, clones, or nests the structure.
4. **Engine relationship** — what the current engine already preserves, understands, or conflicts with.
5. **Product candidate** — what useful capability might follow.
6. **API decision** — deliberately deferred until the evidence and product need justify it.

## 3. Research map

### RESEARCH-01A — Fields, Variables, and Conditional Content

**Research priority: 1**

Investigate native Writer/ODF mechanisms for data-bearing fields and conditional content.

Topics include:

- variable fields and user-defined fields;
- field value representation;
- conditional text;
- hidden text;
- hidden paragraphs;
- conditional/hidden sections;
- expressions and condition syntax;
- string, numeric, boolean, and empty-value behavior where applicable;
- update/evaluation behavior when opening or exporting a document;
- headless LibreOffice behavior where relevant;
- nesting and interaction with named sections;
- preservation of paragraph, character, and surrounding document formatting;
- interaction with current scalar placeholder replacement;
- relationship to `{{variable}}`, filters, `if`/`elseif`/`else`, and similar visible template syntax.

A central question is whether structural selection can be represented as native, visually authored document structure rather than visible control syntax.

Sample 10 should serve as a negative/legacy authoring benchmark: determine how much of its visible control structure can be represented more clearly without sacrificing template-author control.

### RESEARCH-01B — Sections and Native Layout

**Research priority: 2**

Investigate Writer sections as both semantic template objects and native layout containers.

Topics include:

- single- and multi-column sections;
- equal and unequal column widths;
- column gaps and separators;
- section styles and their location in ODF;
- backgrounds, borders, and other section formatting;
- text flow through columns;
- nested sections;
- named sections as layout blocks;
- interaction with current section cloning and instantiation;
- repeatable structures inside and outside multi-column sections;
- interaction with conditional sections and field semantics;
- suitability for CV/sidebar and other professional document layouts.

The research must determine how much layout can remain LibreOffice-authored while PHP only addresses, selects, clones, or binds structured content.

### RESEARCH-01C — Native Style Semantics

**Research priority: 3**

Investigate Writer/ODF style families from the perspective of template authorship and application authorship.

Topics include:

- paragraph styles;
- character/text styles;
- page styles;
- frame/graphic styles;
- list styles;
- table-related styles and Writer table-style behavior;
- inheritance and parent-style relationships;
- automatic versus named styles;
- style references versus definitions;
- authored-template styles versus generated styles;
- style precedence and document defaults where relevant.

This research builds on the completed STYLE-CONTEXT-01 and STYLE-API-02 architecture. It must not reintroduce global registries or generic style APIs merely for symmetry.

A key product question is when applications should reference LibreOffice-authored named styles instead of constructing equivalent formatting in PHP.

### RESEARCH-01D — Page Styles and Document Flow

**Research priority: 4**

Investigate the native mechanisms required for reliable professional multi-page documents.

Topics include:

- page styles and master pages;
- first-page versus following-page layouts;
- transitions between page styles;
- page margins;
- headers and footers;
- page numbering;
- explicit page breaks;
- keep-with-next;
- keep-together;
- widow/orphan behavior;
- paragraph pagination properties;
- interaction between paragraph styles and page flow;
- interaction with dynamically instantiated content.

A professional CV is an important benchmark, but conclusions must remain generally useful for reports, letters, offers, invoices, and other ODT documents.

### RESEARCH-01E — Named and Addressable Native Objects

**Research priority: 5**

Survey additional native ODF/Writer structures that may be useful as addressable template objects.

Existing engine concepts include sections, bookmarks, tables, and frames. Research should determine which additional native structures have stable identity and useful semantics, including where relevant:

- text boxes;
- images and image-bearing frames;
- reference marks;
- fields;
- lists;
- page/master-style related structures;
- other Writer objects discovered during empirical research.

Do not assume that every addressable object requires the same operations. Replacement, content replacement, selection, cloning, instantiation, and removal are distinct capabilities.

## 4. Cross-cutting research: Authoring and Developer Experience

Authoring UX is not a separate late-stage cosmetic concern. It must be evaluated throughout RESEARCH-01.

### 4.1 Template Author UX

Ask for every candidate mechanism:

- Can a non-programmer understand the template in LibreOffice?
- Does the template still look substantially like the resulting document?
- Can content and formatting be changed without editing PHP?
- Is control information visible only where it helps the author?
- Does the mechanism cause layout drift in the editable template?
- Can the author discover and inspect the relevant object through normal Writer tools such as styles, fields, sections, or the Navigator?

### 4.2 Developer UX

Ask in parallel:

- Can a PHP developer use the capability without knowing internal ODF XML details?
- Are missing/invalid template structures diagnosable?
- Can the engine inspect and report what a template contains?
- Is the distinction between template-owned and application-owned structure clear?
- Can capability-specific APIs remain simpler than generic object manipulation?

Possible later capabilities include improved inspection, validation, diagnostics, naming guidance, and CLI tooling. These are candidates, not approved APIs.

## 5. Benchmarks

### 5.1 Sample 10 — Template Authoring UX benchmark

Sample 10 represents the strengths and limitations of visible template-language authoring. Variables and simple filters can remain compact, while structural controls such as multi-line conditions and loops can make the editable document diverge significantly from its rendered appearance.

RESEARCH-01 should use Sample 10 to compare native alternatives with the existing syntax rather than assuming either approach is universally superior.

### 5.2 Sample 25 — Structured Document benchmark

Sample 25 represents the newer model in which LibreOffice-authored native structure and PHP-driven data binding/instantiation cooperate.

Research should preserve the architectural lesson that native structure can remain template-owned while the engine provides typed, semantic operations over it.

### 5.3 Future professional authoring proof

After research and prioritization, a future sample may prove the combined model with capabilities such as:

- authored named styles;
- first/following page styles;
- multi-column sections;
- conditional native content;
- repeatable named sections;
- images;
- pagination controls such as keep-with-next;
- a template that remains understandable in LibreOffice before rendering.

This is a target benchmark, not an approved implementation task.

## 6. Research evidence and fixtures

Where Writer behavior is not obvious from the ODF specification or existing repository evidence, prefer small empirical LibreOffice fixtures.

For each fixture, record:

- the authoring steps in LibreOffice;
- relevant `content.xml` and `styles.xml` structures;
- behavior before and after Writer save/reopen;
- behavior after engine load/render/save where relevant;
- headless export behavior where relevant;
- interaction with cloning, instantiation, or nesting where relevant.

Characterization tests should be added when a discovered behavior becomes important to current or planned engine semantics.

Do not turn exploratory fixtures into permanent repository artifacts without deciding their long-term purpose.

## 7. Post-research capability assessment

After the research areas are sufficiently understood, candidate capabilities will be evaluated separately from research order.

At minimum, assess:

- **User value** — how commonly and materially the capability helps real documents;
- **Template-author value** — how much it improves visual/redactional authoring in LibreOffice;
- **Developer value** — how much complexity it removes from application code;
- **Architectural leverage** — which later capabilities depend on it;
- **ODF fidelity** — whether it uses stable native semantics rather than fragile reconstruction;
- **implementation and compatibility risk** — how deeply it affects current behavior and APIs.

Research priority must not be mistaken for implementation priority.

## 8. Version 1.0 and later product planning

RESEARCH-01 should conclude with enough evidence to define a deliberate product boundary.

The resulting planning pass should classify candidate work into at least:

1. **Required for version 1.0** — capabilities needed for a coherent, reliable, professionally useful core engine.
2. **Post-1.0 development** — valuable capabilities that do not need to delay a stable 1.0 release.
3. **Commercial/product-layer candidates** — optional higher-level tooling or packaged capabilities that may be suitable for a paid offering after the open engine foundation is understood.

No open-source/commercial boundary is decided by RESEARCH-01 itself. Fundamental document correctness, stable ODF semantics, and a coherent core API must not be weakened merely to manufacture a commercial distinction.

Potential commercial value should be assessed at the product/tooling layer only after technical dependencies and user value are known.

## 9. Expected RESEARCH-01 outcome

RESEARCH-01 is complete when it provides:

- an evidence-based map of relevant native ODF/Writer capabilities;
- explicit findings for fields/conditions, sections/layout, styles, page flow, and addressable native objects;
- a clear account of what the current engine already supports, preserves, or conflicts with;
- identified gaps without premature API invention;
- an authoring-UX assessment using the existing samples as benchmarks;
- a prioritized capability set for the next implementation phase;
- a proposed version 1.0 boundary;
- a post-1.0 backlog adjustment;
- a separately reasoned assessment of possible commercial/product-layer capabilities.

## 10. Immediate next step

Begin **RESEARCH-01A — Fields, Variables, and Conditional Content**.

The first pass should investigate LibreOffice-authored examples of variable fields, conditional text, hidden text/paragraphs, and conditional sections, then inspect their actual ODF representation and behavior before considering any engine API changes.

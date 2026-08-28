# FRAME-LAYOUT-01 — Template Suitability Findings

## A. Purpose

This document records an important product and architecture finding discovered
while visually validating Samples 23 and 24 against a commercially designed CV
template converted from DOCX to ODT.

The finding is not primarily an `ImageElement` defect. It concerns the
underlying document model used by the template itself and therefore affects how
such templates should be evaluated before being used as programmable ODT
templates.

This document is evidence and design guidance only. It does not change
production code or approve a new public API.

## B. Observed CV template structure

The examined CV does not behave like a conventional flowing Writer document.
Large parts of the visible layout are composed from positioned drawing objects
rather than ordinary paragraphs and sections participating in Writer text flow.

The converted ODT contains drawing structures including:

- `draw:frame`;
- `draw:image`;
- `draw:custom-shape`;
- drawing lines and other positioned objects.

Many visible text blocks are represented as drawing/custom-shape objects with
explicit placement information such as:

- `text:anchor-type`;
- `svg:x`;
- `svg:y`;
- `svg:width`;
- `svg:height`;
- frame/drawing styles;
- z-order and related drawing properties.

In particular, several text areas originating from the DOCX design are not
normal Writer paragraphs in document flow. LibreOffice converted them into
positioned `draw:custom-shape` objects containing text paragraphs.

This means the visual CV is effectively a drawing/layout composition layered
on top of the Writer document rather than a normal reflowing text document.

## C. Consequence for reflow

A correctly materialized `as-char` image inside a normal paragraph can reserve
space in that paragraph's text flow.

However, it cannot push unrelated drawing objects downward when those objects
are independently positioned using explicit coordinates.

Conceptually:

```text
normal flow
    paragraph
        as-char image
            reserves space in that flow

positioned CV layout
    custom-shape A at y = ...
    custom-shape B at y = ...
    custom-shape C at y = ...
```

Increasing the height of the normal-flow paragraph does not automatically
rewrite the `svg:y` positions of the surrounding shapes.

Therefore Sample 24 can be structurally correct and still overlap existing CV
content.

This is expected behavior for a fixed-position drawing layout; it is not proof
that the image package/resource transaction failed.

## D. Important distinction: engine capability versus template model

The engine must distinguish two questions:

1. Can the engine create, inspect, clone, replace and preserve the native ODF
   structures correctly?
2. Is the chosen template structurally suitable for the type of dynamic
   document generation the application expects?

These are not the same question.

A template may be visually excellent but poorly suited to content whose length
or number of entries changes dynamically.

A fixed-position CV can be appropriate for replacing values of approximately
known size, but problematic for operations that require natural reflow, such
as:

- adding variable-length profile text;
- inserting a taller image;
- adding or removing experience entries;
- expanding lists;
- changing content height substantially;
- inserting sections that are expected to push following content downward.

## E. Implication for commercial DOCX templates

Commercial Word templates must not automatically be considered suitable engine
templates merely because they convert successfully to ODT and render correctly
in LibreOffice.

DOCX-to-ODT conversion may preserve the visual result by converting Word text
boxes and drawing objects into positioned LibreOffice shapes.

That can produce valid ODT while retaining a layout model that behaves more
like a fixed canvas than a flowing Writer document.

The conversion result must therefore be audited before it is adopted as a
programmable template.

This does not mean commercial templates are unusable.

It means their suitability depends on the intended mutation model.

## F. Template suitability categories

A future template audit should distinguish at least these broad categories.

### Flow-oriented template

Primarily uses:

- paragraphs;
- headings;
- lists;
- tables where appropriate;
- sections;
- inline/as-character frames where normal flow is intended.

Advantages:

- natural reflow;
- variable-length content behaves predictably;
- sections can expand and move following content;
- well suited to repeated/instantiated content.

This is the preferred model for highly dynamic documents.

### Hybrid template

Uses ordinary Writer flow for variable content, with positioned frames/shapes
for bounded decorative or fixed-layout elements.

Examples:

- fixed header decoration;
- profile photo;
- icons;
- sidebar background;
- normal flowing experience/education content.

This may be an excellent practical CV architecture if dynamic areas are kept in
flow and fixed drawing elements remain bounded.

### Fixed-position/drawing template

Most visual sections are independently positioned drawing objects.

Advantages:

- precise design;
- strong visual fidelity for fixed content.

Risks:

- little or no automatic vertical reflow between independent objects;
- content expansion causes overlap;
- adding repeated items may require explicit coordinate management;
- image/section growth does not naturally move surrounding objects;
- dynamic document generation becomes much more layout-sensitive.

Such templates may still be useful for bounded value replacement, but should
not be assumed suitable for free-form dynamic generation.

## G. Relevance to addressable document operations

The new addressable-document model remains useful for all three template
categories.

For example:

```php
$template->bookmark('Name')->replaceText('Walter Dietz');
```

is still useful in a fixed-position template if the replacement remains within
the visual capacity of the target object.

Likewise, inspecting and addressing named frames/custom shapes can be useful.

The limitation arises when an operation assumes reflow that the underlying
layout model does not provide.

Therefore addressability and layout behavior must remain separate semantics.

## H. Clone/instantiate implication

The finding strengthens the preservation requirement for future SECTION-03.

For existing professionally designed content, `clone()` / `instantiate()`
should preserve the native source subtree and its designer-authored layout
properties rather than rebuilding it through simplified engine defaults.

For a positioned object this includes, where applicable:

- host context;
- anchor;
- x/y coordinates;
- width/height;
- frame/drawing style;
- wrapping;
- z-order;
- text properties;
- payload;
- native names and references after deterministic identity rewriting.

However, cloning a positioned block does not automatically solve document
reflow.

If multiple cloned blocks require different vertical locations, the engine
would need explicit placement semantics or the template should be redesigned so
that repeated content participates in normal flow.

Therefore SECTION-03 must not assume that every cloneable section is also a
naturally repeatable flow block.

## I. `draw:custom-shape` is now a first-class audit concern

FRAME-LAYOUT-01 initially focused mainly on `draw:frame` payloads such as images
and text boxes.

The converted CV demonstrates that `draw:custom-shape` is also important for
real-world template compatibility.

Future inspection/fixture work should characterize at least:

- custom shapes containing text;
- their native identity/name behavior;
- position/size semantics;
- styles and enhanced geometry;
- clone preservation;
- whether content can be safely addressed without reconstructing the shape;
- distinction between shape geometry and contained text.

This does not approve a public CustomShape API yet.

## J. Template suitability should become an explicit audit capability

A professional/AI-friendly engine should eventually be able to inspect a
template and report whether it is primarily flow-oriented or fixed-position.

A future machine-readable diagnostic could conceptually identify:

```text
Document layout profile
├── flowing block content
├── positioned frames
├── positioned custom shapes
├── tables
├── sections
└── diagnostics
```

Potential diagnostics could include:

- high number of absolutely/explicitly positioned drawing objects;
- dynamic section surrounded by fixed-position objects;
- repeated-content candidate contained in positioned shape;
- likely non-reflowing template region;
- frame/custom-shape overlap risk.

This would be especially useful for coding agents because it prevents them from
assuming browser-like or Writer-flow behavior from a visually complex template.

No such public audit API is implemented by this document.

## K. Practical guidance for CV template selection

Before adopting a CV template for `cv-generator-pro` or similar applications,
inspect its ODT structure rather than judging only the rendered appearance.

Prefer templates where the areas expected to vary significantly are built from
normal Writer-flow structures.

A useful target architecture is likely hybrid:

```text
fixed visual shell
├── decorative sidebar/background
├── fixed photo/icon areas
└── flowing semantic content
    ├── profile
    ├── experience
    ├── education
    └── other repeatable sections
```

This keeps LibreOffice as the visual designer while allowing the engine to
benefit from natural document flow.

Templates dominated by independent fixed-position text boxes/shapes should be
used only when their bounded behavior matches the application requirements or
a deliberate coordinate-based layout strategy exists.

## L. Product conclusion

The commercial CV template experiment was still valuable.

It revealed that visual quality alone is not enough to select a programmable
ODT template.

The engine should support native drawing structures correctly, but it should
not attempt to emulate a full page-layout engine merely to compensate for a
source template whose dynamic regions were designed as a fixed canvas.

The preferred long-term principle is:

> Preserve designer-authored native layout, but choose templates whose native
> layout model matches the intended mutation semantics.

For dynamic CV generation this likely means favoring flow-oriented or hybrid
ODT templates over templates whose content is almost entirely composed from
positioned shapes.

## M. Recommended follow-up

Do not make a final keep/discard decision for the purchased CV templates based
on a single file.

When work resumes:

1. inspect several representative purchased templates;
2. classify them as flow-oriented, hybrid or fixed-position;
3. identify whether DOCX-to-ODT conversion changes their original layout model;
4. determine which are suitable for variable/repeated content;
5. retain fixed-position templates only for use cases where their layout model
   is appropriate;
6. include `draw:custom-shape` in FRAME-LAYOUT-01A native fixture
   characterization;
7. keep SECTION-03 clone/instantiate semantics independent from assumptions
   about automatic reflow.

This finding should be treated as a template-authoring/suitability constraint,
not as a reason to abandon the addressable-document architecture.

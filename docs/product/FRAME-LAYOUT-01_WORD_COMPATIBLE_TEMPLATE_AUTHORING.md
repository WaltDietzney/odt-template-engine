# FRAME-LAYOUT-01 — Word-Compatible Template Authoring Direction

## A. Purpose

This document records an important product and authoring finding from the
FRAME-LAYOUT and CV-template investigations:

> ODT Template Engine should not only be able to generate valid native ODT
> structures. For selected document-design elements it should deliberately
> support ODF structures that LibreOffice can round-trip back to useful DOCX
> drawing structures.

This is particularly relevant for professional CV/application templates, where
ODT is the authoritative template/editing format but DOCX export may still be a
practical interoperability requirement.

This document does not define a complete DOCX compatibility layer and does not
turn DOCX into a source-of-truth format. LibreOffice/ODF remains the native
model.

## B. Evidence

The CV round-trip investigation compared:

1. an original DOCX template;
2. the LibreOffice-converted ODT;
3. a DOCX exported again from that ODT.

The documents showed that many modern Word drawing objects survive the
DOCX -> ODT -> DOCX cycle in structurally meaningful form.

In ODT, LibreOffice represents many of these objects as drawing structures such
as:

- `draw:custom-shape`;
- `draw:frame`;
- graphic styles;
- bitmap fills;
- drawing geometry;
- anchored/positioned objects.

The round-tripped DOCX again contains corresponding Word drawing/shape
structures rather than flattening the complete design into an image.

This means these ODF drawing structures can be useful interoperability targets,
not merely conversion artifacts.

## C. CircularImageElement as existing evidence

The repository already contains:

`src/Elements/CircularImageElement.php`

The class was intentionally designed for the common CV/profile-photo case.

It does not create a rectangular `draw:frame` containing `draw:image`.
Instead it produces a native ODF custom shape with ellipse geometry and a
bitmap-filled graphic style:

```text
draw:custom-shape
├── ellipse geometry
└── graphic style
    └── bitmap fill
        └── image resource
```

The essential implementation direction is:

```xml
<draw:custom-shape ...>
    <draw:enhanced-geometry draw:type="ellipse" .../>
</draw:custom-shape>
```

with a graphic style that references a registered bitmap fill.

This is materially similar to the structure LibreOffice produced for the round
profile photo in the converted commercial CV template.

Therefore `CircularImageElement` is not only a convenience element. It is an
important example of an ODF-native element that is also suitable for practical
Word interoperability.

## D. Authoring principle

For templates that may later be exported to DOCX, the engine should prefer
well-understood native ODF structures that LibreOffice maps reliably to Word
constructs.

The guiding workflow is:

```text
semantic document requirement
        ↓
ODF-native structure
        ↓
LibreOffice rendering
        ↓
LibreOffice DOCX export
        ↓
usable Word structure
```

The goal is not XML identity between ODT and DOCX.

The goal is preservation of:

- visual intent;
- editability;
- semantic object boundaries where practical;
- geometry and layout behavior;
- image resources;
- text content;
- useful Word drawing/object equivalents.

## E. Template design must distinguish flow and drawing layout

The commercial CV investigations also showed an important limitation.

A converted DOCX template may consist largely of fixed-position drawing objects.
Such a document may render beautifully while being a poor basis for highly
dynamic flowing content.

Word-compatible template authoring should therefore distinguish two areas.

### Flow-oriented content

Use native text/document flow for dynamic regions such as:

- work experience;
- education;
- qualifications;
- long profile text;
- repeated entries;
- variable-length lists.

These should normally use paragraphs, lists, tables, sections and other
flow-aware ODF structures.

### Drawing-oriented content

Use drawing objects deliberately for objects whose visual geometry is part of
the design, such as:

- profile-photo shapes;
- icons;
- decorative circles/rectangles;
- skill bars;
- badges;
- background shapes;
- selected callouts;
- controlled header/footer design objects.

The distinction is essential. A fixed-position drawing should not be used merely
because it was convenient in Word if the content is expected to expand and
reflow dynamically.

## F. Predefined interoperability-oriented elements

The engine should consider a small set of predefined, semantically clear
structured elements for common professional-document design patterns.

Potential examples include:

- `CircularImageElement`;
- rectangular/rounded image shape;
- icon-with-text contact item;
- horizontal skill/proficiency bar;
- badge/pill element;
- controlled separator/line element;
- simple filled shape with text;
- reusable profile-photo frame variants.

These are design directions, not approved APIs.

The important product principle is:

> Common document-design patterns should not require every developer or AI
> agent to rediscover low-level ODF drawing geometry and Word interoperability
> rules.

Where interoperability has been characterized, the engine can provide a
reliable higher-level element that emits the appropriate native ODF structure.

## G. CV-specific example: circular profile image

A profile photo provides a useful benchmark.

A simple rectangular image can use a normal image frame.

A circular profile photo should not require the application to crop a bitmap
into a circle before insertion. The document structure can express the design
semantically:

```text
Circular profile-photo shape
├── size
├── anchor/layout
├── border/stroke
└── bitmap fill
    └── applicant photo
```

This allows the engine to replace only the image resource while preserving:

- circular geometry;
- border;
- size;
- position;
- anchor;
- drawing semantics.

This is also aligned with the addressable-document-model direction: existing
shape geometry should normally be preserved while only the requested payload is
changed.

## H. Future named-shape manipulation

The round-trip evidence makes named drawing objects strategically relevant to
the addressable document model.

A future operation may conceptually support:

```php
$template->shape('ProfilePhoto')->replaceFillImage($path);
```

or another typed equivalent.

No such API is approved by this document.

The key semantic requirement is that replacing a bitmap-filled shape must not
recreate or reinterpret unrelated properties such as:

- geometry;
- border/stroke;
- position;
- anchor;
- wrap;
- z-order;
- size;
- style references.

This follows the existing-document editing principle:

> change the requested payload, preserve the designer-authored object.

## I. Word-compatible template profile

A future authoring guide should define a bounded "Word-compatible template
profile".

The profile should not promise perfect ODT/DOCX equivalence.

Instead it should document tested structures and their expected round-trip
quality.

Possible classification:

```text
SUPPORTED / VERIFIED
    repeatedly tested ODT -> DOCX behavior

SUPPORTED WITH LIMITATIONS
    useful round-trip but known differences

ODT-ONLY
    correct native ODF behavior but unreliable DOCX conversion

AVOID FOR DYNAMIC TEMPLATES
    visually valid construct with poor reflow/dynamic behavior
```

This classification should be evidence-based and backed by actual LibreOffice
round-trip fixtures.

## J. Required round-trip characterization

For interoperability-sensitive predefined elements, tests should eventually
cover:

1. generate or author ODT structure;
2. render in LibreOffice;
3. export to DOCX;
4. reopen DOCX in LibreOffice/Word where practical;
5. compare visual behavior;
6. inspect whether the object remains editable and structurally meaningful;
7. convert back to ODT where useful;
8. verify resources and geometry remain usable.

Automated XML tests alone cannot establish Word compatibility.

## K. Relationship to FRAME-LAYOUT-01

FRAME-LAYOUT-01 remains responsible for understanding:

- host context;
- anchoring;
- geometry;
- position/relation;
- text flow/wrap;
- overlap/z-order;
- drawing payload.

Word-compatible authoring adds another dimension:

```text
ODF semantic correctness
        +
LibreOffice visual correctness
        +
DOCX round-trip usefulness
```

A construct should not be promoted as a Word-compatible predefined element
until all three have sufficient evidence.

## L. Relationship to CV template design

Professional CV generation remains an important architecture benchmark.

A strong CV template should ideally combine:

```text
Flow-oriented dynamic document model
├── experience
├── education
├── profile text
└── variable-length lists

Drawing-oriented stable design elements
├── profile-photo shape
├── contact icons
├── decorative accents
├── skill bars
└── controlled visual branding
```

This hybrid model is preferable to reproducing the full layout as dozens of
absolutely positioned text boxes.

Commercial templates remain valuable as:

- direct template candidates where structurally suitable;
- design references;
- reusable drawing-pattern references;
- sources of icons/shapes/layout ideas;
- interoperability fixtures.

They should be adapted into engine-friendly ODF templates where dynamic flow
requires a different structure.

## M. AI/developer ergonomics

A predefined interoperability-aware element library is particularly valuable
for AI-assisted development.

An agent should be able to express intent such as:

```text
circular applicant photo
skill bar at 80 percent
contact item with location icon
```

without constructing `draw:enhanced-geometry`, bitmap-fill styles or low-level
ODF relationships manually.

The engine should encode proven ODF/LibreOffice interoperability knowledge once
and expose it through discoverable semantic APIs.

This supports the broader PRODUCT-01 objective: make the library easy for both
human developers and coding agents to use correctly.

## N. Scope boundaries

This document does not approve:

- a complete Word/DOCX writer;
- direct OOXML generation;
- automatic DOCX import;
- arbitrary shape editing;
- a public generic Shape API;
- a full diagramming subsystem;
- exact Word visual fidelity guarantees.

LibreOffice remains the interoperability bridge.

The engine's responsibility is to produce and preserve ODF structures that are
known to round-trip usefully where Word compatibility matters.

## O. Recommended follow-up

FRAME-LAYOUT-01A fixture characterization should explicitly include:

- ordinary rectangular image frame;
- `CircularImageElement` / ellipse bitmap-fill shape;
- text box;
- custom shape with text;
- simple filled rectangle/bar;
- icon-like drawing object;
- representative anchor and wrap modes.

For each fixture, record both native ODT behavior and ODT -> DOCX round-trip
behavior.

A later dedicated authoring milestone may define:

`WORD-COMPAT-AUTHORING-01 — LibreOffice/Word-compatible template profile`

That milestone should convert the accumulated evidence into public developer
documentation, tested predefined elements and template-authoring guidance.

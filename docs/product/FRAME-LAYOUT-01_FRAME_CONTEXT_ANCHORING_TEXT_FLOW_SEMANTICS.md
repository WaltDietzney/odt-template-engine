# FRAME-LAYOUT-01 — Frame Context, Anchoring and Text-Flow Semantics

## A. Purpose

FRAME-LAYOUT-01 records the frame-layout architecture debt exposed by the visual
SECTION-02D regression.

SECTION-02D proved an important but narrow distinction:

- a `draw:frame` must first be materialized in a legal Writer-flow context;
- legal XML placement alone does **not** define correct visual placement;
- anchoring, positioning, text flow, wrapping and geometry are separate
  semantics that must be characterized explicitly.

The visual Sample 24 result is the key product evidence: after adding a
paragraph host the image became visible, but it overlapped the surrounding CV
content instead of participating in the intended flow. Therefore the frame is
structurally renderable but layout-semantically wrong.

This document is an architecture/audit contract only. It does not change
production code.

## B. Evidence reviewed

The review uses:

- SECTION-02A through SECTION-02D findings;
- Sample 23/24 visual regression results;
- LibreOffice-authored CV structures;
- current `ImageElement`;
- current `DrawTextBox`;
- `StyleMapper::mapImageStyleOptions()`;
- `StyleMapper::mapFrameStyleOptions()`;
- current section block-context materialization;
- existing image/frame/text-box APIs and tests.

The decisive visual evidence is:

1. pre-SECTION-02D: naked top-level `draw:frame` inside the section was not
   rendered as intended;
2. post-SECTION-02D: paragraph-hosted frame became visible;
3. the visible image still overlaps text and does not create the expected
   vertical flow between the surrounding paragraphs.

## C. Core semantic model

A frame-like ODF object has at least four independent semantic layers:

```text
Host context
    where the frame is structurally placed
        ↓
Anchor semantics
    what textual/layout object owns the frame position
        ↓
Geometry / relation
    where the frame is positioned relative to that anchor
        ↓
Text flow / wrap / overlap
    how surrounding content reacts to the frame
        ↓
Payload
    image, text box, or another drawing payload
```

These layers must not be collapsed into one vague `align` option.

A frame can be structurally valid and visible while still being wrong because
its anchor or text-flow semantics are wrong.

## D. Host context

The engine now has evidence that frame-like content used as direct section
replacement needs a Writer-flow host.

Conceptually:

```xml
<text:section text:name="ImageSection">
    <text:p>
        <draw:frame>...</draw:frame>
    </text:p>
</text:section>
```

This is a **materialization-context** concern. It answers where an element must
live structurally.

It does not answer how the frame should be anchored or positioned.

Future host contexts that require separate evidence include:

- ordinary document body;
- named section;
- table cell;
- list item/paragraph;
- text box;
- nested frame or drawing context;
- page-oriented/absolute placement.

SECTION-02D solves only the proven section top-level frame-host case.

## E. Anchor semantics

The current engine accepts image anchors including:

- `paragraph`;
- `page`;
- `char`;
- `as-char`.

These values are not interchangeable presentation options.

They represent different layout contracts.

### `as-char`

The frame behaves like a character in the text flow. This is the strongest
candidate when the desired semantic is:

```text
TEXT BEFORE

[IMAGE TAKES SPACE IN FLOW]

TEXT AFTER
```

This must still be proven with LibreOffice-authored fixtures before becoming a
default section-image policy.

### `char`

The frame is anchored to a character but remains a positioned drawing object.
LibreOffice-authored CV images provide evidence for this mode together with
explicit coordinates and frame styles. It should not be interpreted as
"inline image" merely because the anchor is character-based.

### `paragraph`

The frame is anchored to a paragraph and may float/overlap depending on its
graphic style, position relationships and wrap settings. Sample 24 proves that
a paragraph-hosted `paragraph` anchor does not by itself create normal vertical
flow.

### `page`

Page anchoring is inherently layout-oriented and should remain explicit. It is
not suitable as an implicit fallback for ordinary template-flow insertion.

No global default-anchor change is approved by FRAME-LAYOUT-01.

## F. Geometry and relation semantics

Frame layout is not determined by `svg:width` / `svg:height` alone.

Relevant ODF/LibreOffice properties include at least:

- `svg:x`;
- `svg:y`;
- `svg:width`;
- `svg:height`;
- `style:horizontal-pos`;
- `style:horizontal-rel`;
- `style:vertical-pos`;
- `style:vertical-rel`;
- anchor type;
- frame/graphic style.

The important semantic distinction is between **position** and **relation**.
For example, "center" is incomplete without saying relative to what:
paragraph, paragraph content, character, page, page content, frame, or another
reference box.

The current APIs expose several of these values independently, but the engine
does not yet define valid combinations as a coherent public contract.

## G. Text flow and wrapping

Text behavior is a first-class frame property, not cosmetic styling.

Relevant concepts include:

- no wrap;
- wrap on one side;
- parallel/both-side wrap where supported;
- run-through/overlay behavior;
- flow-with-text behavior;
- overlap permission;
- wrap influence on position.

A professional document can be visually invalid even when frame geometry is
correct if surrounding text reacts incorrectly.

The Sample 24 visual result is evidence of this category of failure.

Future tests must verify not only frame coordinates but also where the next
paragraph appears.

## H. Z-order and overlap

Frame semantics also include stacking/overlap behavior:

- `draw:z-index`;
- overlap allowance;
- run-through/wrap policy.

These properties matter for CV templates containing decorative shapes,
backgrounds, icons and text boxes.

They must not be guessed from object type.

An image is not necessarily foreground content, and a text box is not
necessarily non-overlapping flow content.

## I. Payload versus frame layout

`ImageElement` and `DrawTextBox` have different payload semantics but share the
same underlying `draw:frame` layout problem.

Conceptually:

```text
Frame layout
├── host context
├── anchor
├── geometry
├── relation
├── wrap/text flow
├── overlap/z-order
└── payload
    ├── image
    └── text box
```

This does **not** approve a new public `Frame` class yet.

It does establish that frame layout should not continue evolving as unrelated
option handling duplicated independently across `ImageElement` and
`DrawTextBox`.

## J. Current ImageElement findings

Current `ImageElement` mixes several concerns:

- image resource identity/path;
- width/height;
- anchor;
- alignment shorthand;
- wrap;
- frame positioning;
- graphic style registration;
- frame DOM creation.

The code supports anchor values through style mapping, but also contains
separate `$anchor` / `$wrap` state and mapped option state. This is a warning
against adding more independent mutable layout state.

The `align` convenience API currently expands into combinations such as:

```text
left
    wrap right
    horizontal-pos left
    horizontal-rel paragraph

right
    wrap left
    horizontal-pos right
    horizontal-rel paragraph

center
    wrap none
    horizontal-pos center
    horizontal-rel paragraph

absolute
    wrap none
    horizontal-pos from-left
    horizontal-rel page-content
```

These are implementation shorthands, not yet a characterized LibreOffice
layout contract.

FRAME-LAYOUT work must test them visually before treating them as stable
semantic guarantees.

## K. Current DrawTextBox findings

`DrawTextBox` already exposes several frame-layout operations:

- anchor;
- horizontal/vertical position and relation;
- overlap;
- `flowWithText()`;
- width/height;
- frame style.

It also contains its own host-placement behavior: non-`as-char` frames are
returned inside a `text:p`, while `as-char` returns the frame directly.

This confirms that frame placement concerns already exist in more than one
class and are not Section-specific.

The historical text-box visual-placement limitations therefore belong to the
same architecture area, although fixing all text-box geometry is explicitly
outside the SECTION-02D scope.

## L. Style placement is part of the audit

The engine currently maps many frame layout properties into graphic style
definitions, while some are also written directly on `draw:frame`.

FRAME-LAYOUT implementation must characterize where LibreOffice actually
stores and consumes each property:

- frame attributes;
- `style:graphic-properties`;
- paragraph host style;
- LibreOffice extension attributes where relevant.

Do not assume that an ODF-looking attribute on `draw:frame` has the same effect
as the equivalent property in the frame's graphic style.

This must be proven against Writer-authored files.

## M. Fixture matrix required before implementation

FRAME-LAYOUT implementation should begin with native LibreOffice fixtures, not
code changes.

At minimum create/inspect the following matrix.

### Images

1. image anchored `as-char` in ordinary paragraph flow;
2. image anchored `char`;
3. image anchored `paragraph`;
4. page-anchored image if LibreOffice authoring allows a stable fixture;
5. centered image that takes normal vertical space;
6. left/right floating image with text wrap;
7. image with no text wrap;
8. image in named section;
9. image in table cell.

### Text boxes / frames

10. text box anchored `as-char`;
11. character-anchored positioned text box;
12. paragraph-anchored positioned text box;
13. text box with flow-with-text enabled/disabled;
14. overlap enabled/disabled where distinguishable;
15. text box in named section;
16. text box in table cell.

For each fixture record:

- host XML parent;
- `text:anchor-type`;
- frame style name;
- frame attributes;
- relevant `style:graphic-properties`;
- horizontal/vertical position;
- horizontal/vertical relation;
- wrap;
- flow-with-text;
- overlap;
- z-index;
- x/y;
- rendered behavior.

## N. Required visual assertions

XML correctness is insufficient for FRAME-LAYOUT.

Every supported semantic mode must have visual assertions such as:

- frame is visible;
- frame remains inside intended container/column;
- following text begins below the frame when flow semantics require it;
- wrapped text actually wraps on the intended side;
- no unexpected overlap;
- no unexpected extra page;
- table-cell content remains inside the cell;
- save/reopen preserves placement.

LibreOffice visual regression is mandatory for behavior-changing
FRAME-LAYOUT slices.

## O. Proposed semantic API direction

Do not add more free-form keys independently to each element.

A future internal semantic model should likely group frame layout into a
single coherent value object or bounded options model, conceptually:

```php
FrameLayout(
    anchor: ...,
    width: ...,
    height: ...,
    horizontal: ...,
    vertical: ...,
    wrap: ...,
    flowWithText: ...,
    allowOverlap: ...,
    zIndex: ...
)
```

The exact class/API is not approved.

The key architecture rule is that one semantic frame-layout model should be
usable by both image and text-box payloads, while payload-specific behavior
remains separate.

Do not create duplicate authoritative mutable state between the element and a
layout object.

## P. Context-aware materialization

SECTION-02D established the first context-aware rule: top-level frame payloads
inside a section require a host paragraph.

Future materialization must preserve a distinction between:

```text
Element payload materialization
```

and

```text
legal placement in target context
```

This distinction is directly relevant to:

- section replacement;
- structured placeholder insertion;
- table-cell insertion;
- future section clone/instantiate;
- named frame manipulation.

The long-term design should avoid each mutation service independently learning
ODF host-placement rules.

## Q. Interaction with clone / instantiate

FRAME-LAYOUT must precede or at least define preservation semantics before
SECTION-03 mutates cloned frame structures.

A section clone should normally preserve the source frame's:

- anchor semantics;
- host context;
- geometry;
- relations;
- wrap/text-flow behavior;
- z-order;
- frame style references;
- payload dimensions.

Clone/instantiate should **not** reinterpret a designer-authored frame through
current constructor defaults.

This is crucial for the visual-template workflow: cloning a professionally
designed CV section must preserve the designer's layout exactly unless the
caller explicitly requests a layout change.

## R. Existing-document editing implication

For existing documents, the engine should favor **preserving native frame
properties** over regenerating them from simplified option arrays.

This reinforces the PRODUCT-01 direction:

```text
inspect existing ODF object
    ↓
address it semantically
    ↓
change only requested properties/payload
    ↓
preserve unrelated native layout structure
```

This is especially important for converted DOCX CV templates where Writer may
produce complex but valid frame styles and coordinates.

## S. Compatibility rules

Future FRAME-LAYOUT implementation must preserve existing public behavior until
specific semantics are characterized.

In particular:

- do not globally change `ImageElement` default anchor without evidence;
- do not silently reinterpret existing `align` values;
- preserve existing working structured insertion paths;
- preserve existing DrawTextBox APIs through compatibility facades if internals
  are consolidated;
- characterize current behavior before correcting surprising legacy semantics.

## T. Explicit non-goals

FRAME-LAYOUT-01 does not implement:

- SECTION clone/instantiate;
- arbitrary page-layout redesign;
- shape editing;
- SVG drawing APIs;
- Style Context;
- Asset Context;
- HTML/CSS layout emulation;
- conversion from DOCX;
- a full Writer layout engine.

LibreOffice remains the visual template designer and rendering authority.

## U. Recommended implementation slices

The recommended sequence is:

### FRAME-LAYOUT-01A — Native Fixture Characterization

Create the LibreOffice image/text-box fixture matrix and document exact XML +
visual behavior. No production changes.

### FRAME-LAYOUT-01B — Frame Layout Semantic Contract

Define the supported anchor/position/relation/wrap combinations and the bounded
internal layout representation. Documentation/tests first.

### FRAME-LAYOUT-01C — ImageElement Layout Normalization

Refactor ImageElement to use the characterized internal frame-layout semantics
while retaining compatibility with current options. Add visual tests.

### FRAME-LAYOUT-01D — DrawTextBox Layout Normalization

Move DrawTextBox onto the same internal layout semantics while preserving its
public API. Characterize/fix only proven placement defects.

### FRAME-LAYOUT-01E — Context-Aware Frame Placement

Consolidate host-context placement rules needed for sections and table/text
contexts so mutation services do not duplicate them.

### FRAME-LAYOUT-01F — Final Visual / Compatibility Review

Run representative public samples and dedicated layout fixtures through
LibreOffice, review compatibility and document the supported frame-layout
contract.

The slices may be reduced if fixture evidence shows a simpler architecture is
sufficient.

## V. Relationship to SECTION-03

SECTION-03 clone/instantiate work does not require every future frame-layout
feature to be implemented first.

It **does** require one preservation rule from FRAME-LAYOUT-01:

> Existing frame layout is native document structure and must be cloned
> structurally, not regenerated from default ImageElement/DrawTextBox options.

Therefore SECTION-03 can proceed after the FRAME-LAYOUT preservation contract
is accepted, while active creation/modification of advanced frame layout can
continue in its own milestone.

## W. Final recommendation

The Sample 24 result should be classified as:

```text
Package/resource handling        PASS
Section structural hosting       PASS
Frame visibility                 PASS
Frame layout/text-flow semantics FAIL / not yet defined
```

FRAME-LAYOUT-01 should become an explicit roadmap milestone between SECTION-02
and SECTION-03 planning, with the first step being native LibreOffice fixture
characterization.

The architecture should converge on three separate concepts:

```text
Target/container context
        ↓
Frame layout semantics
        ↓
Frame payload (Image / TextBox / ...)
```

This keeps ODF placement semantics explicit, allows LibreOffice-authored layout
to be preserved, and prevents future section/template-instance work from
recreating today's frame-placement bugs.

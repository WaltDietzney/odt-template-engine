# FRAME-LAYOUT-01A0 — Existing Architecture & Historical Decision Inventory

Status: ANALYSIS / EVIDENCE GATHERING

Milestone: `FRAME-LAYOUT-01`

Branch: `architecture/frame-layout-01`

This document is an architecture inventory. It is not a Change Contract and does not approve a public API or implementation model.

## 1. Purpose

FRAME-LAYOUT-01 is the next mandatory 1.0 architecture milestone after PAGE-FLOW-01 and TABLE-LAYOUT-01.

Before any public API design or production implementation, FRAME-LAYOUT-01A0 reconstructs the existing frame/drawing architecture and the historical decisions that produced it.

This step is intentionally deeper than a normal API inventory because the current drawing classes evolved incrementally and combine multiple semantic layers.

The inventory must answer:

1. What native ODF concepts are already represented correctly?
2. What behavior exists only as historical convenience or compatibility?
3. Which architectural decisions were already made during SR-06 and related style work?
4. Which layout questions were deliberately deferred to FRAME-LAYOUT-01?
5. Which concepts should form a stable substrate for future drawing elements beyond the current `DrawTextBox` and `ImageElement`?
6. Which Writer/ODF semantics must be researched from primary/native sources before implementation?

## 2. Strategic role of frame layout

FRAME-LAYOUT-01 is not merely an image-positioning task.

A reliable frame/drawing layout model is expected to become foundational infrastructure for additional native drawing elements.

Current examples already include:

- `DrawTextBox`;
- `ImageElement`;
- `CircularImageElement` through a `draw:custom-shape` path.

Future native drawing capabilities may include additional frame-contained content and non-frame drawing objects.

Therefore FRAME-LAYOUT-01 must avoid an image-specific or textbox-specific geometry model.

At the same time, it must not assume that every drawing object is a `draw:frame`. Existing SR-06 evidence shows that professional LibreOffice/Word-converted documents also rely heavily on `draw:custom-shape` and `draw:enhanced-geometry`.

The target architectural principle is therefore:

> Shared drawing-layout semantics where the native ODF model is shared; preserve native structural distinctions where they are not.

## 3. Primary source hierarchy

FRAME-LAYOUT-01A0 uses the following evidence order:

1. current repository code on `develop`;
2. existing architecture contracts/audits/tests, especially SR-06;
3. native ODF specification;
4. LibreOffice-authored ODT fixtures;
5. LibreOffice/Writer source code where ODF output alone does not explain intended Writer behavior.

The project must not infer Writer semantics solely from current engine code.

The supplied ODF specification confirms two important starting points:

- `draw:frame` is the native container for frame content such as `draw:image` and `draw:text-box`;
- frame formatting properties are stored in styles of family `graphic`;
- frame object attributes include identity, anchor, size, relative size, and absolute coordinates.

These distinctions must remain visible during the inventory.

## 4. Recovered SR-06 architecture decisions

SR-06 already performed substantial drawing-model analysis and must be treated as prior architecture, not rediscovered from scratch.

### 4.1 Four semantic channels

SR-06A separated drawing semantics into:

```text
Drawing Object
├── Structural semantics
│   ├── frame
│   ├── image
│   ├── text-box
│   ├── custom-shape
│   └── enhanced geometry
│
├── Placement / geometry
│   ├── anchor
│   ├── size
│   ├── x / y
│   ├── positioning relations
│   └── z-index
│
├── Graphic appearance
│   ├── fill
│   ├── stroke
│   ├── border
│   ├── padding
│   ├── wrap
│   └── related graphic properties
│
└── Dependencies / resources
    ├── bitmap
    ├── fill-image declaration
    └── package resource
```

This distinction remains authoritative unless new evidence contradicts it.

### 4.2 Graphic style family

Historical engine labels such as `frame` and `image` are not native ODF style families.

The relevant semantic style family is:

```text
style:family="graphic"
```

SR-06 migrated appearance-style ownership toward semantic `StyleRequirement` handling without trying to solve frame geometry.

### 4.3 Structural/geometry values are not graphic-style identity

SR-06 established that values such as:

- `draw:name`;
- `text:anchor-type`;
- `svg:width`;
- `svg:height`;
- `svg:x`;
- `svg:y`;
- `draw:z-index`;

are drawing-object identity/geometry/placement concerns, not semantic graphic-style identity.

This is a critical constraint for FRAME-LAYOUT-01.

### 4.4 Existing native representation should be preserved

SR-06 established a preservation principle for existing template objects:

> Existing native ODF drawing representation should be preserved unless the requested operation requires replacing that representation.

Examples:

- a text-bearing `draw:custom-shape` should not be rebuilt automatically as `draw:frame + draw:text-box`;
- a circular bitmap-filled shape should ideally retain its shape geometry when only the image resource changes.

FRAME-LAYOUT-01 must preserve this principle.

## 5. Current element inventory

## 5.1 DrawTextBox

`DrawTextBox` currently owns or performs:

- `draw:frame` creation;
- `draw:text-box` creation;
- frame name;
- anchor;
- width/height;
- z-index;
- horizontal and vertical placement;
- placement relation values;
- graphic style reference;
- graphic style production;
- nested structured content ownership;
- legacy frame-style compatibility production.

Current structural output is approximately:

```text
[text:p wrapper for non-as-char]
└── draw:frame
    ├── draw:name
    ├── draw:style-name
    ├── text:anchor-type
    ├── draw:z-index
    ├── svg:width / svg:height
    ├── style:horizontal-pos / rel
    ├── style:vertical-pos / rel
    └── draw:text-box
        └── structured child elements
```

Current public convenience methods include:

```php
setHorizontalPos($pos, $rel = 'char')
setVerticalPos($pos, $rel = 'baseline')
setHorizontalPosition($pos, $rel = 'page')
setVerticalPosition($pos, $rel = 'page')
flowWithText()
setAllowOverlap()
```

The duplicate-looking positioning methods already indicate historical API layering that must be characterized before any rename/removal.

## 5.2 ImageElement

`ImageElement` currently combines:

- image asset/resource handling;
- auto-scaling when only one dimension is given;
- `draw:frame` creation;
- `draw:image` creation;
- frame size;
- anchor;
- wrap;
- convenience `align` translation;
- horizontal/vertical position;
- optional `svg:x/y`;
- legacy image-style identity/state.

Its current behavior is materially different from `DrawTextBox`.

Notably, `toDomNode()` may resolve convenience placement and then write derived values back into `imageOptions`.

This means materialization currently mutates observable element state.

That behavior is characterized by existing tests and must not be silently removed during an architecture refactor.

## 5.3 CircularImageElement

`CircularImageElement` is strategically important because it demonstrates that visible "image" behavior may be implemented as:

```text
draw:custom-shape
    + draw:enhanced-geometry
    + graphic style
    + bitmap fill
    + fill-image dependency
    + package resource
```

It therefore proves that future drawing support cannot reduce the native model to only Frame/Image/TextBox.

The class remains a convenience/product-oriented element, not automatically a fundamental native type.

## 6. Current mapper boundary

`StyleMapper` currently has separate:

```text
mapFrameStyleOptions()
mapImageStyleOptions()
```

SR-06 characterization shows that these mappings still preserve historical mixing of:

- friendly convenience input;
- native graphic properties;
- placement properties;
- geometry-related values.

FRAME-LAYOUT-01 must determine which parts should become shared drawing-layout semantics and which remain content-specific compatibility input.

No mapper redesign is approved yet.

## 7. Current semantic graphic boundary

`DrawTextBox` currently produces semantic `graphic` style requirements for appearance-like properties.

Its semantic graphic identity intentionally ignores drawing placement and geometry.

However, the class may still render a legacy graphic carrier when unmigrated layout properties require it.

`ImageElement` currently does not produce an owned semantic graphic style definition for the characterized placement-only option set. Its legacy image style path remains observable.

This asymmetry is architectural evidence for FRAME-LAYOUT-01, not automatically a defect to normalize.

## 8. Existing template/image compatibility paths

FRAME-LAYOUT-01 must distinguish structured elements from template-level image operations.

Relevant existing paths include:

```text
ImageElement + setElement()
DrawTextBox + setElement()
setImage()
replaceImageByName()
existing named draw:frame / image replacement
```

PAGE-FLOW-01 additionally exposed a bounded document-part discrepancy:

```text
ImageElement via setElement() in body        -> Writer-visible
ImageElement via setElement() in header      -> not Writer-visible
setImage() in header                         -> Writer-visible
```

This is tracked separately as `GRAPHIC-PART-COMPAT-01`.

FRAME-LAYOUT-01 may coordinate with that issue if evidence shows a shared root cause, but must not assume that all header/image visibility issues are frame-positioning defects.

## 9. Frame versus broader drawing model

FRAME-LAYOUT-01 should provide a reliable core for frame-contained content, but architecture must remain extensible to other drawing structures.

Recovered native model hypothesis:

```text
Frame
├── identity
├── placement
├── size
├── graphic style reference
└── content
    ├── Image
    ├── TextBox
    └── other frame-legal content

CustomShape
├── identity
├── placement
├── size
├── geometry
├── graphic style reference
├── optional text style reference
└── optional structured text content
```

The shared part may eventually be something like drawing-object placement/geometry semantics, but FRAME-LAYOUT-01A0 does not decide whether that becomes:

- a PHP base class;
- a trait/capability;
- a value object;
- a service;
- shared helpers;
- or simply normalized internal state.

Semantics come before class design.

## 10. Native ODF questions to answer next

The ODF specification and Writer fixtures must be used to establish the exact ownership/location of:

### Structural attributes on drawing objects

- `text:anchor-type`;
- `text:anchor-page-number`;
- `svg:width`;
- `svg:height`;
- `style:rel-width`;
- `style:rel-height`;
- `svg:x`;
- `svg:y`;
- `draw:z-index`;
- `draw:name`;
- `draw:transform`.

### Graphic/layout style properties

At minimum investigate:

- `style:wrap`;
- `style:horizontal-pos`;
- `style:horizontal-rel`;
- `style:vertical-pos`;
- `style:vertical-rel`;
- `style:flow-with-text`;
- overlap-related properties;
- contour/wrap interaction where relevant to the bounded 1.0 core.

The inventory must not assume that the current engine places every property at the correct structural level merely because LibreOffice accepts the result.

## 11. Writer source-code research

Where Writer-authored ODT output does not reveal intent clearly, inspect LibreOffice source code for:

- anchor type serialization/import;
- horizontal and vertical orientation mapping;
- relation/reference-area mapping;
- wrap-mode mapping;
- absolute position serialization;
- relative size handling;
- frame versus drawing-shape positioning paths.

Writer source findings must be recorded alongside ODF output evidence so they survive chat/session boundaries.

## 12. Historical/API questions to characterize

Before Change Contract work, characterize at least:

1. `DrawTextBox` constructor option behavior;
2. `setHorizontalPos()` versus `setHorizontalPosition()`;
3. `setVerticalPos()` versus `setVerticalPosition()`;
4. default anchor values;
5. default relation values;
6. text-paragraph wrapper behavior for non-`as-char` frames;
7. `flowWithText()`;
8. `setAllowOverlap()`;
9. `ImageElement` `align` translation;
10. direct `wrap` behavior;
11. `svg:x/y` handling;
12. image width/height autoscaling;
13. repeated `toDomNode()` mutation/stability;
14. style-name generation before/after materialization;
15. `setImage()` option semantics;
16. `replaceImageByName()` preservation semantics;
17. existing named-frame descriptors/targets;
18. body versus header/footer behavior where relevant;
19. repeated save/reopen behavior.

## 13. Proposed FRAME-LAYOUT-01A0 outputs

A0 should end with four concrete outputs:

### A. Existing architecture map

A diagram/table showing:

```text
public API
    -> element/local state
    -> semantic/legacy style path
    -> drawing structure
    -> document part
    -> package/resource dependencies
```

for every active frame/image/textbox path.

### B. Historical decision ledger

For each relevant decision:

```text
decision
source document/test
still authoritative?
intentionally deferred?
superseded?
```

### C. Active / compatibility / legacy matrix

Each observed path should be classified as:

```text
authoritative
compatibility
legacy-but-public
transitional
future/deferred
```

### D. Native evidence backlog

A finite list of questions that must be answered by ODF fixtures and Writer source research before API design.

## 14. A0 non-goals

A0 does not authorize:

- a new public Frame API;
- a `DrawingObject` class hierarchy;
- merging `DrawTextBox` and `ImageElement`;
- removing compatibility methods;
- changing anchor/position/wrap behavior;
- fixing GRAPHIC-PART-COMPAT-01;
- implementing Custom Shape APIs;
- changing graphic style ownership;
- rewriting existing template image APIs.

## 15. Initial conclusion

The current repository already contains the essential architecture insight needed to start FRAME-LAYOUT-01:

> Frame layout is a shared native concern beneath multiple convenience elements, but frame layout is only one part of a broader drawing-object model.

SR-06 deliberately solved graphic-style ownership first and left placement/geometry for this milestone.

FRAME-LAYOUT-01A0 must therefore reconstruct that boundary completely before any new API is designed.

The next evidence step after this inventory is a combined:

```text
A0.1 repository/API characterization
+
A0.2 ODF/Writer semantic research
```

followed by a synthesis that defines the exact questions FRAME-LAYOUT-01A must answer with LibreOffice fixtures.

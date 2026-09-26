# FRAME-LAYOUT-01A0.2 — ODF / Writer Positioning Research

Status: RESEARCH / NATIVE SEMANTICS EVIDENCE

Milestone: `FRAME-LAYOUT-01`

Parent:

- `FRAME_LAYOUT_01A0_EXISTING_ARCHITECTURE_HISTORICAL_INVENTORY.md`
- `FRAME_LAYOUT_01A01_REPOSITORY_API_CHARACTERIZATION.md`

This document records native ODF and LibreOffice/Writer evidence. It is not yet a Change Contract.

## 1. Purpose

A0.1 established that current frame positioning is historically layered and visibly unreliable in Sample 17.

A0.2 answers the next question:

> What does ODF actually mean by anchor, position, relation, offsets, and wrap, and how does LibreOffice map Writer's internal orientation model to ODF?

Primary evidence:

- ODF 1.2 Part 1;
- ODF 1.1 as compatibility/historical cross-check;
- current LibreOffice `xmloff` text property maps/handlers;
- LibreOffice FODT regression fixtures authored/used by Writer tests.

## 2. First decisive result: alignment and offset are different concepts

ODF does **not** define `style:horizontal-pos` as a free-form coordinate.

For text documents its values are an enum:

```text
from-left
left
center
right
from-inside
inside
outside
```

Likewise `style:vertical-pos` is an enum:

```text
from-top
top
middle
bottom
below
```

Absolute offsets are carried separately by:

```text
svg:x
svg:y
```

This distinction is fundamental.

### Horizontal semantics

ODF states:

```text
style:horizontal-pos = from-left
    -> svg:x defines offset from the left edge of the reference area

style:horizontal-pos = left|center|right|inside|outside
    -> svg:x is ignored in text documents
```

The analogous mirrored form is `from-inside`.

### Vertical semantics

ODF states:

```text
style:vertical-pos = from-top
    -> svg:y defines offset from the top of the reference area

style:vertical-pos = top|middle|bottom|below
    -> svg:y is ignored in text documents
```

Therefore a valid native positioning model needs at least two different concepts:

```text
orientation/alignment keyword
+
optional coordinate offset
```

They must not be collapsed into one string field.

## 3. Direct implication for Sample 17

Sample 17 currently uses:

```text
horizontal-pos = 100%
horizontal-pos = 50%
vertical-pos   = 50%
```

These values are not members of the ODF `style:horizontal-pos` or `style:vertical-pos` value sets.

Current `DrawTextBox` serializes them verbatim.

That is not a subtle Writer-layout disagreement. It is a category mismatch:

```text
percentage-like coordinate
was written into
orientation enum property
```

This provides a concrete root cause for the known positioning failure in Sample 17.

No target replacement syntax is approved yet, but any future model must distinguish semantic alignment from coordinate offset.

## 4. Second decisive result: position/relation are graphic style properties

ODF assigns these properties to `style:graphic-properties`:

```text
style:horizontal-pos
style:horizontal-rel
style:vertical-pos
style:vertical-rel
style:wrap
draw:wrap-influence-on-position
style:flow-with-text
```

The `draw:frame` object itself accepts structural/geometry attributes including:

```text
draw:name
draw:style-name
draw:z-index
text:anchor-type
text:anchor-page-number
svg:width
svg:height
style:rel-width
style:rel-height
svg:x
svg:y
draw:transform
```

This means the current `DrawTextBox::toDomNode()` behavior that writes:

```text
style:horizontal-pos
style:horizontal-rel
style:vertical-pos
style:vertical-rel
```

directly onto `draw:frame` is not the native ODF ownership model.

The current mapper/legacy graphic style path is closer to the correct ownership for those properties than the direct frame attributes.

## 5. LibreOffice source independently confirms the ODF split

LibreOffice's current `xmloff/source/text/txtprmap.cxx` maps Writer orientation properties as follows.

### Coordinate positions

```text
HoriOrientPosition
    -> svg:x

VertOrientPosition
    -> svg:y
```

### Orientation keywords

```text
HoriOrient
    -> style:horizontal-pos

VertOrient
    -> style:vertical-pos
```

### Reference areas

```text
HoriOrientRelation
    -> style:horizontal-rel

VertOrientRelation
    -> style:vertical-rel
```

This is extremely strong evidence because it mirrors the ODF conceptual split exactly.

The Writer model therefore also treats:

```text
orientation
relation
offset
```

as separate state.

## 6. LibreOffice enum mapping for horizontal position

`xmloff/source/text/txtprhdl.cxx` maps ODF horizontal position tokens to Writer's `HoriOrientation`:

```text
from-left   -> NONE
left        -> LEFT
center      -> CENTER
right       -> RIGHT
```

with mirrored/import forms for `from-inside`, `inside`, and `outside`.

Interpretation:

```text
NONE
    -> coordinate-driven position
    -> serialized as from-left/from-inside + svg:x

LEFT/CENTER/RIGHT
    -> semantic alignment
    -> no meaningful svg:x in text layout
```

This explains why percentage strings in `style:horizontal-pos` cannot represent "50% across the page" in Writer's native model.

## 7. LibreOffice enum mapping for relation/reference area

Writer's horizontal relation mapping includes native distinctions for:

```text
paragraph
paragraph-content
page
page-content
paragraph-start-margin
paragraph-end-margin
page-start-margin
page-end-margin
char
```

and frame-related forms during import/appropriate contexts.

Vertical relation includes:

```text
paragraph
paragraph-content
char
page
page-content
page-content-top
page-content-bottom
frame
frame-content
line
baseline
text
```

The exact legal combination depends on anchor type.

Therefore `rel` is not an arbitrary textual suffix. It is a constrained reference-area semantic that interacts with anchoring.

## 8. Anchor/relation compatibility is native semantics

ODF explicitly defines compatibility tables between:

```text
text:anchor-type
style:horizontal-rel
style:vertical-rel
```

The engine currently accepts arbitrary combinations.

Examples from the specification include:

- `char` horizontal relation for character-anchored cases;
- `baseline` vertical relation for as-character positioning;
- page relations for page/frame/paragraph/character anchored objects in defined combinations;
- paragraph relations only for appropriate non-page anchors.

This means a future friendly API should probably validate combinations, not merely individual strings.

However, compatibility paths should not be tightened before their behavior is characterized and the Change Contract defines migration rules.

## 9. Size and object geometry

`draw:frame` directly owns:

```text
svg:width
svg:height
style:rel-width
style:rel-height
svg:x
svg:y
```

ODF additionally allows width/height/x/y in graphic styles as defaults for newly created frames.

For concrete generated objects, Writer's property map confirms the object-level `svg:x/y` coordinate path.

FRAME-LAYOUT should therefore distinguish:

```text
concrete object geometry
vs
graphic style default geometry
```

and should not derive semantic style identity from concrete object size/position merely because old option arrays do so.

## 10. Wrap is a graphic/layout property, not a child element

ODF defines `style:wrap` on `style:graphic-properties`.

Defined values include:

```text
none
left
right
parallel
dynamic
biggest
run-through
```

Writer regression fixtures consistently serialize wrap together with position/relation inside a graphic style, for example:

```xml
<style:graphic-properties
    style:wrap="parallel"
    style:vertical-pos="from-top"
    style:vertical-rel="paragraph"
    style:horizontal-pos="from-left"
    style:horizontal-rel="paragraph"/>
```

This has an immediate implication for the current template-level `setImage()` path.

It currently creates:

```xml
<draw:frame>
    <style:wrap style:wrap="..."/>
    ...
</draw:frame>
```

A0.2 finds no native ODF basis for treating `style:wrap` as a child element of `draw:frame`.

That path must be characterized for compatibility, but its structure should not be reused as the future shared Frame model.

## 11. Wrap influence is a Writer/ODF layout hint

`draw:wrap-influence-on-position` is a graphic style property.

ODF describes it as a hint controlling how wrap-induced text reflow influences final frame placement.

Historically defined values include:

```text
iterative
once-concurrent
once-successive
```

LibreOffice Writer regression documents commonly emit:

```text
draw:wrap-influence-on-position="once-concurrent"
```

Sample 17 currently uses:

```text
none
once-concurrent
```

The second value is native/known.

The first (`none`) is not part of the ODF 1.1 value set and requires specific compatibility/LibreOffice investigation before it can be treated as valid public friendly input.

## 12. flow-with-text and allow-overlap

LibreOffice's property map confirms:

```text
IsFollowingTextFlow
    -> style:flow-with-text

WrapInfluenceOnPosition
    -> draw:wrap-influence-on-position

AllowOverlap
    -> loext:allow-overlap
```

Writer FODT fixtures show these alongside position/relation/wrap inside `style:graphic-properties`.

Thus the current `DrawTextBox` classification of these as style-carrier properties is directionally correct.

They are not frame object attributes.

`loext:allow-overlap` remains a LibreOffice extension, not an ODF-core property.

## 13. Writer fixture evidence

Current LibreOffice regression fixtures repeatedly use patterns such as:

```xml
<style:style style:name="gr1" style:family="graphic">
    <style:graphic-properties
        style:wrap="parallel"
        style:vertical-pos="from-top"
        style:vertical-rel="paragraph"
        style:horizontal-pos="from-left"
        style:horizontal-rel="paragraph"
        draw:wrap-influence-on-position="once-concurrent"
        loext:allow-overlap="true"
        style:flow-with-text="false"/>
</style:style>
```

Other Writer fixtures use:

```text
horizontal-pos = right
horizontal-rel = page-content
```

or:

```text
horizontal-pos = center
horizontal-rel = paragraph-content
```

This confirms that Writer's saved ODF expresses keyword alignment through graphic properties rather than using percentages as alignment values.

## 14. Current engine versus native model

### Current DrawTextBox

```text
frameOptions
    ├── width/height
    ├── anchor
    ├── horizontal-pos/rel
    ├── vertical-pos/rel
    ├── wrap influence
    ├── overlap
    └── appearance

toDomNode()
    ├── object geometry
    └── incorrectly duplicates pos/rel directly on draw:frame

legacy graphic style
    ├── pos/rel
    ├── wrap influence
    ├── overlap
    └── appearance
```

### Native model indicated by ODF + Writer

```text
draw:frame
    ├── identity
    ├── anchor
    ├── z-index
    ├── size
    ├── svg:x / svg:y offsets
    └── draw:style-name
             ↓
       graphic style
          ├── horizontal-pos
          ├── horizontal-rel
          ├── vertical-pos
          ├── vertical-rel
          ├── wrap
          ├── wrap influence
          ├── flow-with-text
          ├── allow-overlap (LO extension)
          └── appearance
```

This is the strongest architectural result of A0.2.

## 15. Consequences for future Draw elements

The same split is not unique to `draw:frame`.

ODF drawing shapes such as rectangles/polylines/custom shapes also use object-level geometry attributes such as:

```text
svg:x
svg:y
svg:width
svg:height
text:anchor-type
draw:z-index
draw:style-name
```

This strengthens the user's stated architectural goal:

> FRAME-LAYOUT-01 should establish a reusable geometry/placement foundation for additional Draw elements.

But shared placement semantics must not imply shared XML structure.

A future `draw:custom-shape` still owns enhanced geometry that a `draw:frame` does not.

## 16. Sample 17 reinterpretation

The intended first two cases can now be described more precisely.

### "right floating box"

The phrase "right aligned relative to page" naturally corresponds to a keyword alignment model such as:

```text
horizontal-pos = right
horizontal-rel = page or page-content
```

not `horizontal-pos = 100%`.

Exact relation choice remains a fixture/UX decision.

### "centered box"

True center alignment corresponds naturally to:

```text
horizontal-pos = center
horizontal-rel = ...
vertical-pos = middle
vertical-rel = ...
```

not `50%` position values.

If the intended semantics instead mean "offset by half the reference area's size", then the coordinate model would require:

```text
horizontal-pos = from-left
svg:x = concrete coordinate
vertical-pos = from-top
svg:y = concrete coordinate
```

and the engine would need to know/derive the reference area's physical dimensions, which would move toward layout computation.

That is likely **not** desirable for the bounded 1.0 core.

Writer should compute keyword alignment whenever the intended semantics are alignment.

## 17. Important bounded-design implication

FRAME-LAYOUT-01 should prefer native semantic alignment over engine-side page-coordinate calculation.

This follows the same architectural principle established by PAGE-FLOW:

> Express native layout semantics; let Writer perform physical layout.

Therefore a future friendly API should not offer percentage pseudo-coordinates unless they map to a real native ODF concept with evidenced semantics.

## 18. Candidate semantic dimensions — research result, not API

A native internal frame/drawing placement model now appears to need distinct dimensions:

```text
anchor
size
horizontal alignment mode
horizontal relation/reference area
optional horizontal coordinate
vertical alignment mode
vertical relation/reference area
optional vertical coordinate
wrap
wrap influence
flow with text
overlap policy
z-index
```

Not every anchor/object supports every dimension.

This is an internal semantic decomposition only. It does not approve classes, method names, arrays, or value objects.

## 19. Required Writer/fixture follow-up

Before Change Contract work, A0 still needs concrete LibreOffice-authored fixture evidence for representative combinations.

At minimum create/inspect:

1. paragraph-anchored left/center/right;
2. paragraph-anchored `from-left + svg:x`;
3. page/page-content aligned cases;
4. vertical top/middle/bottom;
5. `from-top + svg:y`;
6. as-character baseline/text/line relations;
7. wrap none/parallel/left/right/run-through;
8. wrap influence variants Writer actually exposes;
9. flow-with-text on/off;
10. overlap on/off;
11. image frame versus text-box frame;
12. named existing frame preservation after targeted mutation.

The goal is not to enumerate every Writer dialog option. It is to determine the bounded 1.0 semantic core and safe compatibility mappings.

## 20. A0.2 verdict

ODF and LibreOffice source agree on the central model.

### Confirmed

- `style:horizontal-pos` / `vertical-pos` are enumerated orientation semantics, not coordinates;
- `svg:x/y` carry coordinate offsets when the corresponding mode is `from-left/from-top`;
- relations/reference areas are independent semantic values;
- position/relation/wrap belong to graphic style properties;
- concrete anchor/size/x/y/z-index belong to the drawing object;
- Writer uses separate internal properties for orientation, relation, and coordinate;
- Sample 17's `50%` / `100%` position values are category-invalid for ODF position enums;
- current direct pos/rel attributes on `draw:frame` do not match native ODF ownership;
- current `setImage()` child `style:wrap` structure is not the native shared model;
- shared placement semantics can support future Draw elements without collapsing their native structures.

### Still unresolved

- exact public compatibility translation rules;
- which current invalid/historical values should throw, translate, or remain raw-only;
- whether friendly frame geometry should be represented by an internal value object/service;
- exact common abstraction boundary between Frame and CustomShape;
- named-frame mutation API scope;
- GRAPHIC-PART-COMPAT-01 interaction;
- final bounded set of wrap/anchor/relation values for 1.0.

The next step is a Writer-authored fixture matrix plus characterization of the remaining public paths. Only after that evidence should FRAME-LAYOUT-01A synthesis and Change Contract preparation begin.

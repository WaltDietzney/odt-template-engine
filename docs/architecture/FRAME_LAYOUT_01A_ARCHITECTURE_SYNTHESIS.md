# FRAME-LAYOUT-01A — Architecture Synthesis

Status: ARCHITECTURE SYNTHESIS / PRE-CONTRACT

Milestone: `FRAME-LAYOUT-01`

Branch: `architecture/frame-layout-01`

Evidence base:

- `FRAME_LAYOUT_01A0_EXISTING_ARCHITECTURE_HISTORICAL_INVENTORY.md`
- `FRAME_LAYOUT_01A01_REPOSITORY_API_CHARACTERIZATION.md`
- `FRAME_LAYOUT_01A02_ODF_WRITER_POSITIONING_RESEARCH.md`
- `FRAME_LAYOUT_01A03_CROSS_PART_IMAGE_COMPATIBILITY.md`
- `FRAME_LAYOUT_01A04_WRITER_FIXTURE_MATRIX.md`
- current repository code and characterization tests;
- ODF 1.1/1.2 specifications supplied to the project;
- LibreOffice Writer-authored fixtures and Writer source research already recorded in A0.

This document synthesizes the research into an architectural target. It is **not yet a Change Contract** and does not authorize production changes.

## 1. Executive conclusion

FRAME-LAYOUT-01 is not fundamentally an image API or a text-box API.

The stable architectural subject is:

> **drawing-object placement semantics shared by multiple native ODF drawing structures, while preserving the structural differences between those structures.**

For frame-contained objects, the native model is not one undifferentiated style array. It consists of distinct semantic channels:

```text
Drawing object
├── native structure / content
├── identity
├── anchor
├── object geometry
├── placement policy
├── text-flow / wrap policy
├── graphic appearance
└── dependencies/resources
```

The most important synthesis result is that **placement itself spans two native carriers**:

```text
object attributes
    anchor
    size
    x/y coordinates
    z-index
    name

graphic style properties
    horizontal position mode
    horizontal relation
    vertical position mode
    vertical relation
    wrap
    wrap influence
    flow-with-text
    overlap policy
    related layout properties
```

The public/internal semantic model must represent that split without exposing raw ODF attribute names as the primary friendly API.

## 2. Architecture decisions inherited from prior milestones

FRAME-LAYOUT-01 does not reopen these established decisions.

### 2.1 Semantic graphic styles remain graphic styles

SR-06 established `style:family="graphic"` as the native style family for drawing appearance and related graphic properties.

Historical engine labels such as `frame` and `image` are compatibility concepts, not native style families.

### 2.2 Geometry is not graphic-style identity

The following remain object identity/geometry/placement concerns rather than semantic graphic appearance identity:

```text
draw:name
text:anchor-type
text:anchor-page-number
svg:width
svg:height
style:rel-width
style:rel-height
svg:x
svg:y
draw:z-index
draw:transform
```

### 2.3 Existing native structures should be preserved

A `draw:custom-shape` must not be rebuilt as a `draw:frame` merely because both can display text or an image.

FRAME-LAYOUT may provide shared semantics beneath multiple drawing structures, but it must not normalize away native ODF structure.

### 2.4 Writer remains the physical layout engine

The engine should express native layout intent and let Writer calculate physical layout.

FRAME-LAYOUT must not become a page-layout calculator merely to support friendly positioning.

This rules out pseudo-coordinate semantics such as "50% means centered" when ODF already has `center` / `middle`.

## 3. Reconstructed semantic model

The research supports the following decomposition.

### 3.1 Structural semantics

Examples:

```text
draw:frame
    ├── draw:image
    └── draw:text-box

draw:custom-shape
    └── draw:enhanced-geometry
```

Structure determines what the object is and which child/content model is legal.

It must not be conflated with placement.

### 3.2 Anchor semantics

Anchor answers:

> What textual/page context owns the drawing object's position?

Observed/currently relevant anchor types include at least:

```text
paragraph
char
as-char
page
```

Anchor is not just another position string. It constrains:

- insertion/container semantics;
- meaningful relation/reference areas;
- available placement behavior;
- Writer layout interpretation.

A future friendly API must therefore treat anchor as a first-class semantic dimension.

### 3.3 Object geometry

Object geometry includes at least:

```text
width
height
optional relative width/height
x
y
z-index
transform where supported
```

Concrete coordinates `x/y` do not by themselves mean that free/absolute positioning is active.

Writer fixtures proved that coordinates may remain serialized even while the active orientation mode is `middle` or `bottom`.

Therefore:

> **Coordinates are data; position mode determines whether they are semantically operative.**

### 3.4 Horizontal placement

Horizontal placement is a tuple:

```text
horizontal position mode
+
horizontal reference area
+
optional x coordinate
```

Examples of semantic position modes include:

```text
left
center
right
from-left
```

The coordinate becomes operative only for coordinate-oriented modes such as `from-left`.

### 3.5 Vertical placement

Vertical placement is analogously:

```text
vertical position mode
+
vertical reference area
+
optional y coordinate
```

Writer-authored evidence confirmed:

```text
vertical-pos = from-top
vertical-rel = paragraph
svg:y = 0.302cm
```

as one complete native coordinate-oriented placement.

### 3.6 Reference areas are independent semantics

Writer uses distinct relation/reference areas such as:

```text
paragraph
paragraph-content
baseline
page / page-content where applicable
```

Therefore "center" is not a complete placement instruction by itself.

The architecture must preserve:

```text
alignment/orientation
!=
reference area
```

### 3.7 Text-flow and wrap policy

Writer evidence confirms a richer model than one boolean:

```text
wrap mode
run-through behavior
contour wrapping
wrap contour mode
number of wrapped paragraphs
wrap influence on position
flow-with-text
overlap policy
```

Not all of these need first-class friendly 1.0 APIs.

They must, however, remain distinct internally/rawly enough that the architecture does not prevent future support.

### 3.8 Graphic appearance

Appearance remains a separate concern:

```text
fill
background
stroke
border
padding
bitmap fill
related graphic appearance
```

A placement change should not require redefining the conceptual appearance model.

### 3.9 Resources/dependencies

Images and bitmap-backed shapes additionally own package dependencies.

Resource ownership is orthogonal to placement.

## 4. The key two-carrier rule

A central FRAME-LAYOUT rule is now established:

> **Drawing placement is one semantic concern whose native serialization is split between object attributes and graphic-style properties.**

This means neither of these architectural extremes is correct:

### Wrong model A — everything belongs on draw:frame

Current historical paths sometimes serialize:

```text
style:horizontal-pos
style:horizontal-rel
style:vertical-pos
style:vertical-rel
style:wrap
```

directly on `draw:frame`.

That does not reflect native ODF ownership.

### Wrong model B — everything belongs in graphic style

Conversely:

```text
svg:x
svg:y
svg:width
svg:height
text:anchor-type
```

must not be absorbed into semantic graphic-style identity merely because they participate in placement.

### Target model

```text
friendly/shared placement semantics
              │
              ▼
     semantic normalization
          ┌───┴──────────┐
          ▼              ▼
 object geometry      graphic layout
 attributes           properties
```

One semantic operation may therefore materialize changes in both native carriers.

That is intentional and must not be mistaken for responsibility leakage.

## 5. Anchor-sensitive insertion is part of frame layout

A0.3 establishes a second major architectural result:

> **Insertion/container semantics cannot be inferred solely from the generated XML node name.**

The current `StructuredElementMaterializer` classifies `draw:frame` as block-like because it only recognizes a fixed list of inline node names.

For `text:anchor-type="as-char"`, that destroys required text-flow context.

Confirmed Writer-visible structure:

```text
text:p
└── draw:frame anchor=as-char
    └── draw:image
```

Confirmed failing structured path:

```text
office:text / style:header
└── draw:frame anchor=as-char
```

Therefore insertion behavior is a semantic consequence of the anchor.

At minimum:

```text
as-char
    -> inline/text-flow insertion

floating anchors
    -> separately characterized container/replacement behavior
```

FRAME-LAYOUT-01 must correct this boundary in a compatibility-conscious slice.

The correction should be expressed through element/insertion semantics, not by adding `draw:frame` blindly to a hard-coded inline node-name list.

## 6. Header frames, floating frames and master-page drawings are different concepts

The research exposed three structures that must remain distinct:

```text
A. frame inside header/footer text flow

B. floating frame anchored relative to text/paragraph/page

C. drawing object belonging to page/master-page layout
```

An image in a header is legal and useful. Its existence does not imply that arbitrary page-level drawings and header text-flow content share one insertion model.

Future page/master-page drawing support must not be smuggled into FRAME-LAYOUT-01 merely because both involve `draw:frame`.

## 7. Shared drawing semantics versus frame-specific semantics

The user requirement that FRAME-LAYOUT become a foundation for further Draw elements is supported by the evidence.

The correct reuse boundary is semantic, not necessarily inheritance-based.

### 7.1 Good candidates for shared drawing placement semantics

```text
anchor
size
x/y coordinates
horizontal orientation
horizontal relation
vertical orientation
vertical relation
z-index
selected text-flow/layout policy where native structures share it
```

### 7.2 Frame-specific concerns

```text
draw:frame child/content legality
draw:text-box containment
draw:image containment
frame insertion/container behavior
frame-specific auto-sizing/content behavior
```

### 7.3 Shape-specific concerns

```text
draw:custom-shape
draw:enhanced-geometry
shape geometry
bitmap-fill semantics
shape-specific transforms
```

### 7.4 Architectural rule

FRAME-LAYOUT-01 should establish a **shared semantic substrate**, but it should not pre-decide a PHP inheritance hierarchy such as `DrawingObject -> Frame -> ImageFrame`.

A small value object, normalized array, service, capability interface, or another representation may be appropriate.

That implementation choice belongs in the Change Contract/design pass after the semantics are fixed.

## 8. Public API direction

This synthesis does not freeze method names, but it establishes what a good public API must communicate.

### 8.1 Friendly API must use domain vocabulary

The primary API should express concepts such as:

```text
anchor
horizontal alignment/position
horizontal reference
vertical alignment/position
vertical reference
offset
size
wrap
```

It should not require users to write:

```php
[
    'style:horizontal-pos' => 'center',
    'style:horizontal-rel' => 'paragraph',
    'svg:y' => '0.3cm',
]
```

as the normal path.

Native/raw ODF access may remain available as an advanced/compatibility path where justified.

### 8.2 Alignment and coordinate positioning must be different operations

The historical Sample 17 values:

```text
50%
100%
```

used as position enums demonstrate why a single loosely typed "position" string is unsafe.

Friendly API design should make these two intents difficult to confuse:

```text
align center relative to paragraph
vs.
position from top by 0.3cm relative to paragraph
```

### 8.3 Reference area should be explicit where ambiguity matters

A convenience method may provide sensible defaults, but the model must retain the selected relation/reference area.

### 8.4 Do not reproduce current ambiguous method families

Current pairs:

```php
setHorizontalPos(...)
setHorizontalPosition(...)

setVerticalPos(...)
setVerticalPosition(...)
```

are semantically indistinguishable except for default relation values.

The target API should not perpetuate duplicate names whose only meaningful difference is a hidden default.

Compatibility wrappers may remain.

## 9. Compatibility classification

FRAME-LAYOUT-01 must preserve existing public behavior unless the Change Contract explicitly changes it.

### 9.1 Authoritative direction

- semantic graphic style ownership from SR-06;
- native object geometry ownership;
- Writer/ODF placement vocabulary;
- structured element ownership model;
- preservation of existing native drawing structures.

### 9.2 Compatibility / transitional paths

- `DrawTextBox::$frameOptions` mixed state;
- `StyleMapper::mapFrameStyleOptions()` mixed legacy mapping;
- `StyleMapper::mapImageStyleOptions()`;
- generated legacy frame/image style identities;
- duplicate positioning convenience methods;
- ImageElement state mutation during `toDomNode()`;
- legacy style carrier selection when unmigrated properties require it.

These may need façade behavior while internal authority moves.

### 9.3 Known incorrect behavior that must be characterized before correction

- Sample 17 percentage pseudo-position values;
- position/relation properties written directly on `draw:frame`;
- `ImageElement` direct placement/wrap attributes that do not follow the shared native carrier model;
- anchor-insensitive structured placeholder replacement for `as-char` frames.

Backward compatibility does not require preserving invalid Writer layout forever, but behavior changes must be explicit and gated.

## 10. Bounded 1.0 scope

FRAME-LAYOUT-01 should be ambitious enough to create a reusable foundation but bounded enough to finish safely.

### 10.1 Must-have semantic core

The Change Contract should target at least:

1. anchor semantics;
2. width/height object geometry;
3. horizontal keyword alignment/orientation;
4. horizontal relation/reference area;
5. vertical keyword alignment/orientation;
6. vertical relation/reference area;
7. coordinate-oriented `from-left/from-top` semantics with x/y offsets;
8. basic Writer wrap modes required by current elements/samples;
9. anchor-sensitive insertion for `as-char`;
10. shared application to `DrawTextBox` and `ImageElement`;
11. preservation of graphic appearance ownership;
12. compatibility wrappers for existing public methods where required;
13. stable repeated render/save behavior;
14. body and styles/header document-part coverage.

### 10.2 Candidate 1.0 but contract decision required

The following have evidence but need explicit scope decisions:

- `style:flow-with-text`;
- `draw:wrap-influence-on-position`;
- `loext:allow-overlap`;
- relative width/height;
- page anchor/page number;
- z-index convenience API;
- selected contour wrap controls.

Current public behavior may force compatibility support even if no new friendly API is added.

### 10.3 Deferred unless required by compatibility

- full contour geometry API;
- arbitrary transforms;
- rotation UI;
- engine-side page coordinate calculation;
- percentage pseudo-position semantics;
- master-page drawing API;
- comprehensive CustomShape API;
- enhanced geometry API;
- named-object clone/replace/remove APIs;
- broad redesign of image resource handling;
- speculative generic DrawingObject class hierarchy.

These topics should be recorded in Future Development when new evidence requires it, not pulled into FRAME-LAYOUT-01 implementation.

## 11. Validation and compatibility rules

Any FRAME-LAYOUT production work should follow these rules.

### 11.1 Characterize first

Before changing each historical path, tests must freeze:

- current public call behavior;
- generated XML;
- style requirement ownership;
- repeated materialization/save behavior;
- relevant body/header behavior.

### 11.2 Validate semantic combinations

The new friendly path should not silently accept category-invalid values such as percentage strings where ODF requires an orientation enum.

Exact validation/exception policy belongs in the Change Contract.

### 11.3 Do not infer mode from coordinate presence

This rule is mandatory:

```text
svg:x/y exists
!=
coordinate positioning is active
```

The active orientation mode is authoritative.

### 11.4 Preserve polymorphism and compatibility façades

Protected/public extension points that existing subclasses may override must not disappear casually during extraction.

### 11.5 Repeated rendering must remain stable

No semantic migration should introduce:

- duplicate graphic definitions;
- changing generated style names across repeated render/save;
- mutation-dependent output drift;
- document-part divergence.

## 12. Proposed implementation responsibility boundary

Without committing to concrete class names, the target responsibility flow should resemble:

```text
DrawTextBox / ImageElement / future Draw element
        │
        │ friendly API + element-specific content
        ▼
shared drawing placement semantics
        │
        ├── validate/normalize anchor + orientation + relation + offsets
        │
        ├── expose object-geometry requirements
        │
        └── expose graphic-layout requirements
        │
        ▼
element materialization
        ├── native structure remains element-specific
        ├── object attributes applied to native drawing object
        └── graphic style requirement references normalized layout properties
        │
        ▼
StructuredElementMaterializer
        └── insertion behavior respects anchor/text-flow semantics
```

This keeps services small and avoids introducing a broad mutable context object.

## 13. Recommended implementation slices

The Change Contract should consider small slices rather than one drawing rewrite.

### Slice 0 — characterization gate

Freeze:

- current DrawTextBox method families/defaults;
- ImageElement alignment/wrap/coordinate behavior;
- Sample 17 current XML and visual defect;
- as-char insertion behavior;
- repeated save/style identity behavior.

### Slice 1 — shared placement normalization and native carrier ownership

Introduce the smallest internal semantic representation needed to normalize:

- anchor;
- horizontal mode/relation/x;
- vertical mode/relation/y;
- size where appropriate.

Materialize position/relation through graphic properties and geometry through object attributes.

Keep compatibility façades.

### Slice 2 — DrawTextBox migration

Move DrawTextBox onto the shared semantic authority while preserving:

- public methods;
- nested structured content;
- graphic appearance;
- compatibility style paths where still required.

Correct Sample 17 through explicit sample/API migration rather than interpreting percentages magically.

### Slice 3 — ImageElement migration

Move ImageElement placement onto the same authority.

Preserve image-specific:

- resource handling;
- autoscaling;
- image content;
- characterized lifecycle behavior.

Do not merge ImageElement and DrawTextBox classes.

### Slice 4 — anchor-sensitive structured insertion

Correct `as-char` structured materialization so inline frame content remains in paragraph flow.

Cover body and header/styles parts.

This may be implemented earlier if dependency order requires it, but should remain a separately reviewable behavior change.

### Slice 5 — bounded flow/wrap compatibility

Normalize only the wrap/flow properties explicitly accepted into 1.0.

Keep LibreOffice-extension behavior clearly identified.

### Slice 6 — samples and visual regression

Update/add a dedicated FRAME-LAYOUT sample showing:

- image and textbox;
- left/center/right;
- top/middle/bottom;
- one coordinate-offset case;
- as-char;
- representative wrap.

Use `tools/visual-regression/render.sh` for repeatable LibreOffice inspection.

## 14. Change Contract questions now ready for decision

Research is sufficiently complete to move from discovery to contract preparation.

The Change Contract should explicitly decide:

1. What internal representation owns shared drawing placement semantics?
2. What exact friendly public methods/arrays are introduced?
3. Which anchor values are first-class in 1.0?
4. Which horizontal/vertical modes and relation values are accepted?
5. How are coordinate modes represented so they cannot be confused with alignment?
6. Which wrap modes are first-class?
7. Is `flow-with-text` first-class or compatibility-only?
8. Is `allow-overlap` first-class despite being a LibreOffice extension?
9. How are old `setHorizontalPos/Position` and vertical equivalents retained/deprecated/routed?
10. How are invalid historical values such as `50%` handled?
11. Which old raw option keys remain accepted?
12. How is anchor-sensitive insertion communicated from an element to the materializer?
13. Which current state mutation/lifecycle behavior must remain observable?
14. Which visual samples are mandatory acceptance evidence?

These are now design decisions rather than research questions.

## 15. Explicitly rejected architecture directions

Based on A0 evidence, FRAME-LAYOUT-01 should reject:

### 15.1 One giant frame style array as semantic authority

It mixes object geometry, layout policy, appearance, and compatibility syntax.

### 15.2 Direct ODF attribute names as the primary public API

Raw access can exist, but it should not define the normal developer experience.

### 15.3 Percentage strings as alignment values

They are category-invalid for ODF orientation enums and encourage engine-side pseudo-layout semantics.

### 15.4 Node-name-only inline/block materialization

A `draw:frame` can require inline insertion when anchored `as-char`.

### 15.5 Image-specific placement architecture

The same native placement semantics apply beyond images.

### 15.6 Premature universal DrawingObject inheritance

Shared semantics do not prove that all drawing structures need one PHP base class.

### 15.7 Rebuilding native template structures unnecessarily

Existing custom shapes, frames and other native structures should remain intact when the requested operation does not require structural replacement.

## 16. Synthesis verdict

FRAME-LAYOUT-01A has enough evidence to leave the exploratory phase.

The architecture target is:

> **A shared, native-semantic drawing placement model that separates anchor, object geometry, orientation/reference-area policy, text-flow policy and graphic appearance; serializes each concern through the correct ODF carrier; preserves element-specific native structure; and makes structured insertion anchor-aware.**

This target supports the current `DrawTextBox` and `ImageElement` while creating a credible foundation for later Draw elements.

The evidence also explains two previously confusing historical problems:

1. Sample 17 fails because percentage pseudo-positions were used where Writer/ODF expect orientation semantics or coordinate-mode + concrete offsets.
2. structured `as-char` ImageElement insertion fails because the materializer removes the paragraph context required by inline text-flow semantics.

Neither problem should be solved with an isolated special case.

They should be resolved as consequences of the shared FRAME-LAYOUT semantic model.

## 17. Next step

Proceed to a focused **FRAME-LAYOUT-01B API / Compatibility Design Pass**.

That pass should resolve the 14 contract questions in Section 14, review naming and call-order semantics, and then produce the FRAME-LAYOUT Change Contract before any production implementation.

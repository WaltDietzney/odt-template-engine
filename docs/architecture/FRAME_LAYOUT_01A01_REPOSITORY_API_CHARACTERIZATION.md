# FRAME-LAYOUT-01A0.1 — Repository / API Characterization

Status: ANALYSIS / CHARACTERIZATION

Milestone: `FRAME-LAYOUT-01`

Parent inventory:

- `FRAME_LAYOUT_01A0_EXISTING_ARCHITECTURE_HISTORICAL_INVENTORY.md`

This document records the current repository behavior. It does not approve the current behavior as target ODF/Writer semantics.

## 1. Scope

A0.1 inventories the currently reachable frame/drawing APIs and classifies their responsibilities before native ODF/Writer research.

The primary paths are:

```text
DrawTextBox
ImageElement
CircularImageElement
OdtTemplate::setImage()
OdtTemplate::replaceImageByName()
FrameTarget / FrameDescriptor
```

Sample 17 is treated as a first-class regression oracle because it contains three text-box scenarios whose intended positioning is known not to work correctly in Writer.

## 2. Sample 17 is an explicit known layout defect

`samples/sample_17_textfield.php` contains three cases:

### Box 1 — intended right-floating frame

```php
new DrawTextBox('Box1', [
    'width' => '6cm',
    'height' => '4cm',
    'horizontal-pos' => '100%',
    'horizontal-rel' => 'page',
    'wrap-influence' => 'none',
    ...
])
```

Intent expressed by the sample comment:

> right-aligned floating text box that lets body text flow around it

Known current result:

> the intended positioning does not work reliably/correctly in Writer.

### Box 2 — intended page-centered frame

```php
new DrawTextBox('Box2', [
    'width' => '5cm',
    'height' => '6cm',
    'horizontal-pos' => '50%',
    'horizontal-rel' => 'page',
    'vertical-pos' => '50%',
    'vertical-rel' => 'page',
    'wrap-influence' => 'once-concurrent',
    ...
])
```

Intent expressed by the sample comments:

> centered floating text box

and:

> exactly in the middle of the page

Known current result:

> the intended horizontal/vertical positioning does not work correctly in Writer.

### Inline box

```php
new DrawTextBox('Inline', [
    'width' => '4cm',
    'height' => '3cm',
    'anchor' => 'as-char',
    ...
])
```

This case is structurally different. It is inserted directly into a paragraph and is not evidence for floating-position semantics.

### A0.1 conclusion

Sample 17 must not be treated as proof that the existing positioning vocabulary is correct merely because it serializes XML and the sample runs.

It is instead a mandatory FRAME-LAYOUT visual regression fixture.

## 3. DrawTextBox current state model

`DrawTextBox` stores all constructor options in one mutable array:

```text
frameOptions
```

That array currently mixes:

- object geometry;
- anchoring;
- positioning;
- positioning relation;
- appearance;
- wrap influence;
- overlap behavior;
- convenience input;
- raw/native keys.

The class derives a generated legacy style name from:

```php
StyleMapper::mapFrameStyleOptions($frameOptions)
```

while its semantic graphic requirement filters that mapped array down to appearance-only properties.

This means one object currently has at least two style identities:

```text
legacy/mixed frame style identity
semantic graphic appearance identity
```

Depending on options, the rendered `draw:frame` may reference either identity.

## 4. DrawTextBox structural serialization

`DrawTextBox::toDomNode()` currently writes directly on `draw:frame`:

```text
draw:name
text:anchor-type
draw:z-index
draw:style-name
svg:width
svg:height
style:horizontal-pos
style:horizontal-rel
style:vertical-pos
style:vertical-rel
```

For every anchor other than `as-char`, it additionally creates a wrapper:

```text
text:p
└── draw:frame
```

For `as-char`, it returns the frame directly.

This wrapper behavior is public-output behavior and must be characterized before changing frame anchoring.

## 5. Duplicate positioning method families

The current public API exposes:

```php
setHorizontalPos(string $pos, string $rel = 'char')
setHorizontalPosition(string $pos, string $rel = 'page')

setVerticalPos(string $pos, string $rel = 'baseline')
setVerticalPosition(string $pos, string $rel = 'page')
```

Implementation:

```text
setHorizontalPosition()
    -> delegates to setHorizontalPos()

setVerticalPosition()
    -> delegates to setVerticalPos()
```

Therefore the method pairs are not two different mechanisms.

They differ only in their default relation value:

```text
setHorizontalPos()       default rel = char
setHorizontalPosition()  default rel = page

setVerticalPos()         default rel = baseline
setVerticalPosition()    default rel = page
```

This is a historically layered API and a compatibility hazard.

A caller changing only the method name changes semantics even when passing the same position value.

No rename/removal is authorized by A0.1.

## 6. DrawTextBox option-to-output matrix

| Input / method | Current local state | Current mapped style property | Current frame attribute | Classification |
| --- | --- | --- | --- | --- |
| `width` | `width` | passthrough `width` in legacy mapper | `svg:width` | mixed / compatibility |
| `height` | `height` | passthrough `height` in legacy mapper | `svg:height` | mixed / compatibility |
| `anchor` | `anchor` | passthrough `anchor` in legacy mapper | `text:anchor-type` | structural, mapper residue |
| `horizontal-pos` | local | `style:horizontal-pos` | also `style:horizontal-pos` | placement duplicated across channels |
| `horizontal-rel` | local | `style:horizontal-rel` | also `style:horizontal-rel` | placement duplicated across channels |
| `vertical-pos` | local | `style:vertical-pos` | also `style:vertical-pos` | placement duplicated across channels |
| `vertical-rel` | local | `style:vertical-rel` | also `style:vertical-rel` | placement duplicated across channels |
| `wrap-influence` | local | `draw:wrap-influence-on-position` | no direct equivalent | layout style / legacy carrier |
| `allow-overlap` | local | `loext:allow-overlap` | no direct equivalent | extension layout style |
| `flowWithText()` | `style:flow-with-text` | passthrough | no direct attribute | layout style |
| background/fill/border/padding | local | graphic properties | no direct duplicate | semantic graphic appearance |
| `rx/ry` | local | `svg:rx/ry` | no direct duplicate | historical frame appearance/geometry boundary |

This table records current behavior only. A0.2 must determine the correct native Writer/ODF ownership for every placement/layout entry.

## 7. Important DrawTextBox style-carrier behavior

`requiresLegacyGraphicCarrier()` treats these mapped keys as geometry/placement that do not by themselves require a legacy carrier:

```text
width
height
anchor
style:horizontal-pos
style:horizontal-rel
style:vertical-pos
style:vertical-rel
```

But options such as:

```text
draw:wrap-influence-on-position
loext:allow-overlap
style:flow-with-text
svg:rx
svg:ry
```

can force use of the legacy mixed graphic style.

This is directly relevant to Sample 17:

- Box 1 uses `wrap-influence`;
- Box 2 uses `wrap-influence` plus `rx/ry`.

Therefore Sample 17 does not exercise only frame attributes. It also exercises the legacy mixed graphic-style carrier.

## 8. ImageElement current state model

`ImageElement` has overlapping state:

```text
width
height
anchor
wrap
imageOptions
rawOptions
styleMap
```

Constructor behavior:

- validates physical image readability;
- derives default dimensions;
- proportionally calculates a missing dimension when only one is supplied;
- maps options through `mapImageStyleOptions()`;
- stores raw options separately;
- assigns `anchor` and `wrap` convenience fields.

The mapped `imageOptions` includes both style-like and structural/placement values.

## 9. ImageElement materialization mutates state

`ImageElement::toDomNode()` resolves `align` convenience semantics and then writes the resolved values back into `imageOptions`.

Example:

```text
align = right
    -> style:wrap = left
    -> style:horizontal-pos = right
    -> style:horizontal-rel = paragraph
```

Those derived values become observable through `getImageOptions()`.

Existing characterization explicitly freezes this behavior.

Classification:

```text
transitional / compatibility-sensitive
```

This must not be silently normalized away during FRAME-LAYOUT refactoring.

## 10. ImageElement alignment vocabulary

Current convenience `align` maps as follows:

```text
left
    wrap = right
    horizontal-pos = left
    horizontal-rel = paragraph

right
    wrap = left
    horizontal-pos = right
    horizontal-rel = paragraph

center
    wrap = none
    horizontal-pos = center
    horizontal-rel = paragraph

absolute
    wrap = none
    horizontal-pos = from-left
    horizontal-rel = page-content
```

This vocabulary is different from `DrawTextBox`.

FRAME-LAYOUT must decide later whether this is:

- retained convenience translated into shared frame semantics;
- compatibility-only syntax;
- partially incorrect historical behavior.

A0.1 does not decide.

## 11. ImageElement direct geometry

Current `ImageElement::toDomNode()` writes:

```text
text:anchor-type
svg:width
svg:height
style:wrap
style:horizontal-pos
style:horizontal-rel
style:vertical-pos
style:vertical-rel
svg:x
svg:y
```

directly on `draw:frame`.

Like DrawTextBox, this must be checked against Writer-authored ODF and specification ownership instead of assumed correct.

## 12. OdtTemplate::setImage()

`setImage()` is a separate template-level insertion path.

It:

- copies the bitmap directly into the package;
- computes/defaults dimensions;
- applies to both `content.xml` and `styles.xml`;
- creates a new `draw:frame`;
- sets `draw:name`, anchor, size, z-index;
- creates nested `draw:image`;
- replaces the placeholder paragraph.

Its wrap handling is structurally distinct from both structured elements.

For `left`, `right`, or `parallel`, it currently creates:

```xml
<style:wrap style:wrap="..."/>
```

as a child of `draw:frame`.

Whether that is correct Writer/ODF structure must be checked in A0.2. It must not be copied into a future shared abstraction merely because the path works in existing cases.

Classification:

```text
public compatibility/utility path
important cross-document-part oracle
not current structured-element architecture
```

PAGE-FLOW proved that this path has practical behavior that the current `ImageElement` path does not reproduce in headers.

## 13. OdtTemplate::replaceImageByName()

`replaceImageByName()` is fundamentally different from generated frame authoring.

It targets an existing:

```xml
<draw:frame draw:name="...">
    <draw:image .../>
</draw:frame>
```

and changes only:

- `svg:width`;
- `svg:height`;
- nested image `xlink:href`.

It intentionally preserves other frame attributes/styles/placement.

This is strong existing evidence for the project-wide preservation principle:

> mutate the requested concern of a LibreOffice-authored object rather than reconstructing its layout.

Classification:

```text
authoritative preservation-oriented named-frame mutation path
with legacy duplicate-name compatibility behavior
```

## 14. FrameTarget / FrameDescriptor

The structured inspection layer already recognizes native named frames.

`FrameTarget` is currently read-only.

`FrameDescriptor` exposes:

```text
name
document part
payload type
width
height
containing section
diagnostics
```

It does not yet expose:

- anchor;
- x/y;
- horizontal/vertical position;
- relations;
- wrap;
- z-index;
- style reference.

This is a potentially useful future inspection/mutation boundary, but FRAME-LAYOUT must not automatically turn it into a broad mutation API without design evidence.

## 15. CircularImageElement / CustomShape boundary

`CircularImageElement` does not use `draw:frame`.

It emits:

```text
draw:custom-shape
├── text:anchor-type
├── svg:width
├── svg:height
├── draw:z-index
├── draw:style-name
└── draw:enhanced-geometry
```

Its existence is important for future Draw support.

Shared concepts with Frame include:

- anchor;
- size;
- z-index;
- style reference;
- potentially placement.

Non-shared concepts include:

- enhanced geometry;
- shape-specific semantics;
- bitmap-fill dependency path.

Therefore FRAME-LAYOUT should seek reusable **drawing placement semantics**, not a model that requires all future draw elements to inherit frame structure.

## 16. Current active / compatibility / legacy matrix

| Path | Classification | Reason |
| --- | --- | --- |
| semantic graphic StyleRequirement | authoritative | completed SR-06 architecture |
| structured resource collection | authoritative | document/package-local ownership |
| `DrawTextBox` structured content ownership | authoritative | normal structured element traversal |
| `DrawTextBox` placement API | legacy-but-public / unresolved | historical API, known bad Sample 17 positioning |
| `DrawTextBox` legacy frame carrier | compatibility/transitional | retained for unmigrated graphic/layout properties |
| `ImageElement` resource path | authoritative | structured image resource preparation |
| `ImageElement` placement/options model | transitional | mixed channels and materialization mutation |
| `ImageElement::align` | public compatibility convenience | separate historical vocabulary |
| `OdtTemplate::setImage()` | public compatibility/utility | proven cross-part behavior, separate architecture |
| `replaceImageByName()` | authoritative preservation-oriented operation | mutates existing native frame/image |
| `FrameTarget` / descriptor | authoritative read model | typed native object inspection |
| `CircularImageElement` | supported convenience over CustomShape | strategically important drawing evidence |
| static/legacy frame/image style adoption | compatibility | document-local adoption retained by SR-06F |

## 17. Sample 17 immediate technical observations

Without yet deciding the native fix, A0.1 can state several repository-level facts.

### 17.1 Percent values are passed through verbatim

The values:

```text
100%
50%
```

are passed verbatim as `horizontal-pos` / `vertical-pos`.

No current code interprets them as coordinates or percentages.

There is no translation to `svg:x` / `svg:y`.

### 17.2 Position and relation are duplicated

For DrawTextBox, position/relation can exist:

- directly on the generated frame;
- in the mixed legacy frame-style definition.

The semantic graphic requirement intentionally excludes them.

### 17.3 Wrap intent is not represented by a common Frame API

Sample 17 uses:

```text
wrap-influence
```

while ImageElement uses:

```text
wrap
align
```

and `setImage()` has its own wrap handling.

There is no unified frame wrap model today.

### 17.4 "right", "center", and absolute offsets are conceptually conflated

The repository currently allows a caller to express a position using arbitrary strings.

No validation distinguishes:

- keyword position;
- relative reference area;
- absolute coordinate;
- percent-like input.

That absence is likely relevant to Sample 17, but native Writer/ODF evidence is required before declaring the exact correction.

## 18. Compatibility constraints for future implementation

FRAME-LAYOUT implementation must preserve or explicitly review:

1. `DrawTextBox` public constructor options;
2. both positioning method-name families;
3. their different default relation values;
4. `as-char` direct-return behavior;
5. paragraph wrapper behavior for floating boxes;
6. ImageElement proportional size calculation;
7. ImageElement materialization-state mutation;
8. `align` convenience behavior;
9. `setImage()` body/header behavior;
10. `replaceImageByName()` preservation behavior;
11. duplicate named-frame replacement compatibility;
12. semantic graphic appearance ownership;
13. legacy carrier behavior required by unmigrated properties.

A future extraction should retain compatibility facades where public/protected polymorphism or observable behavior requires them.

## 19. Characterization tests already available

Existing tests provide significant A0.1 coverage:

- `StyleContextGraphicDrawingBoundaryCharacterizationTest`;
- `DrawTextBoxSemanticGraphicProducerTest`;
- `ImageElementSemanticGraphicProducerTest`;
- `StyleContextFrameCompatibilityAdoptionTest`;
- `StyleContextGraphicImageAdoptionTest`;
- `StructuredImageResourceArch05HTest`;
- SR-06 integration preflights.

They establish:

- semantic graphic identity excludes placement/geometry;
- legacy identities still include mixed state;
- ImageElement materialization mutation is stable;
- document-local legacy adoption behavior;
- no cross-document style leakage;
- resource/package handling;
- CustomShape bitmap-fill dependencies.

## 20. Missing characterization before production work

A0.1 still needs focused executable tests for:

1. Sample 17's exact three current structures;
2. the two horizontal setter families and default relation difference;
3. the two vertical setter families and default relation difference;
4. `flowWithText()` current style/carrier behavior;
5. `setAllowOverlap()` current style/carrier behavior;
6. paragraph wrapper versus `as-char`;
7. explicit `svg:x/y` behavior for ImageElement;
8. `setImage()` generated frame/wrap structure;
9. `replaceImageByName()` preservation of unrelated frame placement/style attributes.

These tests should characterize current behavior without claiming it is correct Writer behavior.

## 21. A0.1 verdict

The repository has a strong semantic graphic-style foundation but no coherent frame-layout authority.

The current state can be summarized as:

```text
Graphic appearance ownership
    -> mostly modern / semantic

Drawing resource ownership
    -> modern / document-local

Frame geometry / placement
    -> duplicated historical implementations

Frame public vocabulary
    -> inconsistent across TextBox, ImageElement, setImage()

Existing-frame targeted mutation
    -> preservation-oriented and strategically valuable
```

Sample 17 is the clearest visible proof that successful XML generation is not sufficient.

The next implementation-free step is to add the missing characterization tests from section 20 and then perform A0.2 native ODF/Writer research, including LibreOffice source-code inspection for position/relation serialization.

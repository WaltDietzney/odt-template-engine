# FRAME-LAYOUT-01B — API / Compatibility Design Pass

Status: PROPOSED API + COMPATIBILITY DESIGN / PRE-CONTRACT

Milestone: `FRAME-LAYOUT-01`

Parent:

- `FRAME_LAYOUT_01A_ARCHITECTURE_SYNTHESIS.md`

Evidence:

- `FRAME_LAYOUT_01A0_EXISTING_ARCHITECTURE_HISTORICAL_INVENTORY.md`
- `FRAME_LAYOUT_01A01_REPOSITORY_API_CHARACTERIZATION.md`
- `FRAME_LAYOUT_01A02_ODF_WRITER_POSITIONING_RESEARCH.md`
- `FRAME_LAYOUT_01A03_CROSS_PART_IMAGE_COMPATIBILITY.md`
- `FRAME_LAYOUT_01A04_WRITER_FIXTURE_MATRIX.md`

This document proposes the public authoring model, internal semantic boundary, compatibility behavior, naming, and call-order rules to be frozen by the later Change Contract.

No production implementation is authorized by this document.

## 1. Design goals

FRAME-LAYOUT-01B must produce an API that:

1. reflects native Writer/ODF semantics rather than historical option accidents;
2. clearly separates semantic alignment from coordinate offsets;
3. clearly separates reference area from position mode;
4. works consistently for `DrawTextBox` and `ImageElement`;
5. creates a reusable semantic substrate for future drawing elements without forcing one PHP inheritance hierarchy;
6. preserves existing public compatibility paths where practical;
7. does not make raw ODF QNames the primary application vocabulary;
8. keeps Writer responsible for physical page layout;
9. supports body and page-owned header/footer content;
10. fixes `as-char` insertion through anchor semantics rather than an ImageElement special case.

## 2. Public owner: frame-backed elements

For the current 1.0 milestone, the recommended public API owner is the **frame-backed element**, not a generic `DrawingObject` API.

Both current elements are backed by `draw:frame`:

```text
ImageElement
    -> draw:frame
       -> draw:image

DrawTextBox
    -> draw:frame
       -> draw:text-box
```

Therefore the preferred application-facing master name is:

```php
$element->setFrameLayout([...]);
```

This should be available on both `ImageElement` and `DrawTextBox`.

### 2.1 Why `setFrameLayout()`

Alternatives considered:

```text
setLayout()
setDrawingLayout()
setDrawingPlacement()
setFrameStyle()
setFrameLayout()
```

Decision: **prefer `setFrameLayout()`.**

Reasons:

- `setLayout()` is too generic;
- `setDrawingLayout()` over-generalizes the public model before CustomShape and other Draw objects have their own contract;
- `setDrawingPlacement()` is narrower than the proposed master state because size and basic wrap also belong to the bounded frame-layout authoring surface;
- `setFrameStyle()` is actively misleading because object geometry and anchor are not graphic-style properties;
- `setFrameLayout()` describes the user's concern without pretending the native serialization has one carrier.

Future non-frame Draw elements may reuse the same internal placement semantics while exposing an element-appropriate public API.

## 3. Recommended master API

The preferred master method is:

```php
$box->setFrameLayout([
    'anchor' => 'paragraph',
    'width' => '6cm',
    'height' => '4cm',

    'horizontal' => [
        'alignment' => 'right',
        'relative-to' => 'paragraph',
    ],

    'vertical' => [
        'alignment' => 'top',
        'relative-to' => 'paragraph',
    ],

    'wrap' => 'parallel',
]);
```

The same vocabulary applies to `ImageElement`.

### 3.1 Why nested horizontal/vertical groups

A flat API such as:

```php
[
    'horizontal-position' => 'center',
    'horizontal-relative-to' => 'paragraph',
    'horizontal-offset' => '1cm',
]
```

is possible but still makes contradictory combinations easy to express.

The native semantics are tuples:

```text
horizontal:
    mode + reference area + optional coordinate

vertical:
    mode + reference area + optional coordinate
```

The grouped form exposes that relationship directly.

It also allows validation to reject contradictory intent at one semantic boundary.

## 4. Alignment mode versus offset mode

This distinction is the most important public API rule.

### 4.1 Alignment mode

Horizontal:

```php
'horizontal' => [
    'alignment' => 'center',
    'relative-to' => 'paragraph',
]
```

Native semantic intent:

```text
style:horizontal-pos = center
style:horizontal-rel = paragraph
```

Vertical:

```php
'vertical' => [
    'alignment' => 'middle',
    'relative-to' => 'paragraph',
]
```

Native semantic intent:

```text
style:vertical-pos = middle
style:vertical-rel = paragraph
```

### 4.2 Offset mode

Horizontal:

```php
'horizontal' => [
    'offset' => '1.5cm',
    'relative-to' => 'paragraph',
]
```

Native semantic intent:

```text
style:horizontal-pos = from-left
style:horizontal-rel = paragraph
svg:x = 1.5cm
```

Vertical:

```php
'vertical' => [
    'offset' => '0.3cm',
    'relative-to' => 'paragraph',
]
```

Native semantic intent:

```text
style:vertical-pos = from-top
style:vertical-rel = paragraph
svg:y = 0.3cm
```

### 4.3 Conflict rule

Reject:

```php
'horizontal' => [
    'alignment' => 'center',
    'offset' => '1cm',
    'relative-to' => 'paragraph',
]
```

and the vertical equivalent.

A single friendly declaration must not express both orientation alignment and coordinate mode.

### 4.4 No friendly percentage pseudo-position

Reject friendly values such as:

```text
50%
100%
```

for alignment/position semantics.

FRAME-LAYOUT must not guess that:

```text
50%  => center
100% => right
```

Historical Sample 17 should be migrated explicitly to semantic values.

## 5. Friendly anchor vocabulary

Recommended first-class 1.0 values:

```text
paragraph
char
as-char
page
```

These correspond to existing engine/public usage and Writer/ODF evidence.

The friendly master API should validate these values.

Other raw/native anchor values may remain available through compatibility/native paths if already supported, but should not be added by symmetry without evidence.

### 5.1 Anchor controls insertion semantics

The anchor is not merely serialized state.

At minimum:

```text
as-char
    -> inline/text-flow materialization
    -> preserve containing text:p

paragraph / char / page
    -> floating semantics
    -> block/container behavior remains separately governed
```

This must be conveyed to the structured materialization boundary semantically.

Do not implement the fix as:

```text
if nodeName == draw:frame then inline
```

because floating frames are also `draw:frame`.

## 6. Friendly alignment values

### Horizontal alignment mode

Recommended 1.0 friendly values:

```text
left
center
right
```

Coordinate mode is **not** expressed as `alignment => from-left`.

It is expressed through `offset`.

This keeps the application API semantic.

### Vertical alignment mode

Recommended 1.0 friendly values:

```text
top
middle
bottom
```

Coordinate mode is expressed through `offset`, not `alignment => from-top`.

### 6.1 Why hide `from-left/from-top` from friendly alignment

`from-left` and `from-top` are native mode tokens describing how a coordinate becomes active.

They are implementation details of the friendly `offset` concept.

Applications should not need to know both:

```text
alignment = from-top
+
y = 0.3cm
```

to express "offset 0.3cm from the top of the selected reference area".

Raw/native paths may still expose native tokens for advanced use.

## 7. Reference-area vocabulary

Reference area remains explicit.

Initial friendly values should include the values required by current Writer evidence and current public APIs:

```text
paragraph
paragraph-content
page
page-content
char
baseline
```

Additional Writer/ODF relation values such as `text`, `line`, margin-specific forms, frame-relative forms, or inside/outside variants should not automatically become first-class 1.0 friendly values.

### 7.1 Axis-aware validation

Not every reference value is meaningful on both axes or with every anchor.

The semantic normalizer should validate combinations, not merely string membership.

Examples:

- `baseline` is a vertical relation;
- `as-char` uses text-flow/baseline semantics;
- page-relative combinations are not interchangeable with inline character placement.

The exact allowed matrix should be frozen in the Change Contract after one focused validation review against ODF tables/current compatibility cases.

## 8. Width and height

The master API should accept:

```php
'width' => '6cm',
'height' => '4cm',
```

as object geometry.

These map to object-level frame size, not graphic-style identity.

### 8.1 ImageElement autoscaling compatibility

`ImageElement` currently auto-calculates a missing dimension when only width or height is supplied to its constructor.

That behavior is public/observable and must remain characterized.

Recommended rule:

- constructor compatibility keeps existing autoscaling behavior;
- `setFrameLayout()` uses explicit geometry semantics and does not silently invent a second autoscaling policy unless the Change Contract deliberately says so.

This avoids coupling generic frame layout to image pixel ratios.

### 8.2 Relative width/height

Relative frame size has native ODF support but is not necessary to prove the 1.0 reliable geometry core.

Recommendation: defer new friendly relative-size API unless compatibility analysis shows it is already public/required.

Raw/native preservation should remain possible.

## 9. Wrap API

Recommended first-class friendly `wrap` values for 1.0:

```text
none
left
right
parallel
dynamic
run-through
```

These are directly evidenced by Writer fixtures.

`biggest` exists in native ODF vocabulary but is not required by current fixture/application evidence and can remain raw/future unless compatibility requires it.

### 9.1 Wrap remains a graphic-layout property

The master API may expose `wrap`, but normalized output belongs in graphic layout properties.

Do not serialize the friendly wrap value as a child element of `draw:frame`.

### 9.2 Related wrap controls

Keep these conceptually separate:

```text
wrap influence
contour wrapping
run-through foreground/background
number of wrapped paragraphs
flow-with-text
overlap
```

Do not fold them into one `wrap` option.

## 10. flow-with-text, wrap influence, and overlap

### 10.1 `flow-with-text`

Current `DrawTextBox::flowWithText()` is public.

Recommendation:

- preserve it as a compatibility/public method;
- normalize it through the shared graphic-layout state;
- do **not** add a master `flow-with-text` option to the initial recommended 1.0 vocabulary until dedicated Writer-object evidence or concrete product need justifies it.

This preserves capability without expanding the primary authoring surface prematurely.

### 10.2 `wrap-influence`

Current constructor/options and Sample 17 use it.

Recommendation:

- preserve compatibility input;
- support known native values in normalized state;
- do not promote it to the primary friendly master API in Slice 1;
- explicitly reject/characterize historical non-native values such as `none` before changing them.

### 10.3 `allow-overlap`

`DrawTextBox::setAllowOverlap()` is public and maps to LibreOffice extension `loext:allow-overlap`.

Recommendation:

- retain the method;
- normalize it through shared layout state;
- document it as LibreOffice-specific;
- do not pretend it is portable ODF-core semantics.

It need not appear in the initial `setFrameLayout()` vocabulary.

## 11. Convenience API

The master API should be complemented by explicit, concern-specific convenience methods.

Recommended:

```php
$element->setFrameAnchor('paragraph');

$element->setFrameHorizontalAlignment('center', 'paragraph');
$element->setFrameVerticalAlignment('middle', 'paragraph');

$element->setFrameHorizontalOffset('1.5cm', 'paragraph');
$element->setFrameVerticalOffset('0.3cm', 'paragraph');

$element->setFrameWrap('parallel');
```

These methods should be available consistently on `DrawTextBox` and `ImageElement`.

### 11.1 Why the `Frame` prefix

Names such as:

```text
setHorizontalAlignment()
setVerticalAlignment()
setWrap()
```

are ambiguous on elements that contain text or images.

For example:

- horizontal alignment could mean paragraph/text alignment inside a text box;
- image alignment could be mistaken for content alignment;
- wrap could refer to content wrapping rather than frame text-flow policy.

The `Frame` prefix makes the semantic owner explicit.

This mirrors the successful TABLE-LAYOUT decision to prefer `setTableAlignment()` over `setAlignment()`.

### 11.2 Size convenience methods

No additional shared `setFrameWidth()` / `setFrameHeight()` methods are required for the first implementation.

Reasons:

- existing ImageElement constructor sizing has autoscale semantics;
- DrawTextBox sizing currently comes from constructor options;
- the master `setFrameLayout()` can author explicit size;
- adding several size mutators now creates unnecessary interaction rules.

They can be added later if actual application ergonomics justify them.

## 12. Compatibility treatment of existing DrawTextBox methods

Current public methods:

```php
setHorizontalPos(string $pos, string $rel = 'char')
setHorizontalPosition(string $pos, string $rel = 'page')

setVerticalPos(string $pos, string $rel = 'baseline')
setVerticalPosition(string $pos, string $rel = 'page')
```

must not be removed in FRAME-LAYOUT-01.

### 12.1 Compatibility classification

They become **legacy public façades**.

The new semantic authority should be the shared frame-layout state.

### 12.2 Valid native-enum calls

Calls such as:

```php
setHorizontalPosition('right', 'page')
setVerticalPosition('middle', 'paragraph')
```

can be translated into the new semantic alignment state.

### 12.3 Coordinate-mode calls

Legacy calls explicitly using:

```text
from-left
from-top
```

need corresponding coordinate state to be meaningful.

If no coordinate exists, compatibility behavior must be characterized rather than invented.

### 12.4 Category-invalid percentage calls

Historical calls such as:

```php
setHorizontalPosition('50%', 'page')
```

or constructor options with `50%` / `100%` should **not** be silently translated to `center` / `right`.

Preferred compatibility rule:

- existing low-level/legacy path remains characterizable during transition;
- new friendly API rejects such values;
- samples/documentation migrate to semantic API;
- the Change Contract decides whether legacy invalid values remain raw-pass-through temporarily or begin throwing only after an explicit deprecation boundary.

Backward compatibility does not justify guessing user intent.

## 13. Compatibility treatment of ImageElement `align`

Current `ImageElement` supports convenience `align` values:

```text
left
right
center
absolute
```

and translates them into a mixture of wrap + placement properties.

Recommendation:

- retain `align` as a compatibility convenience;
- translate it into shared frame-layout semantics before materialization;
- stop using it as an independent authority;
- do not add `align` to the new `setFrameLayout()` vocabulary.

Potential normalized meanings:

```text
left
    horizontal alignment = left
    relative-to = paragraph
    wrap = right

right
    horizontal alignment = right
    relative-to = paragraph
    wrap = left

center
    horizontal alignment = center
    relative-to = paragraph
    wrap = none
```

`absolute` requires more care because the existing convenience currently selects `from-left + page-content` without necessarily providing an x coordinate.

Its exact compatibility mapping must be frozen after characterization.

## 14. Raw/native compatibility input

Existing raw/native option paths should remain available where already public.

However, they are not the semantic authority.

Recommended layering:

```text
friendly setFrameLayout()
    -> strict semantic validation

legacy constructor/options and mapper paths
    -> compatibility normalization where safe
    -> raw pass-through where explicitly preserved

materializer/style writer
    -> normalized native state only
```

The new API should not require callers to use QNames such as:

```text
style:horizontal-pos
style:vertical-rel
svg:x
```

for normal authoring.

## 15. Internal semantic authority

FRAME-LAYOUT should introduce **one shared semantic placement authority per frame-backed element**.

Do not keep parallel mutable stores such as:

```text
frameOptions
frameLayoutOptions
imageOptions
positionOptions
wrapOptions
```

as independent authorities.

### 15.1 Recommended representation direction

The preferred design is a small immutable semantic value object, conceptually:

```text
DrawingPlacement
    anchor
    width
    height
    horizontal mode
    horizontal relation
    x
    vertical mode
    vertical relation
    y
    wrap
    selected layout policy
```

Each fluent mutation replaces/returns updated semantic state rather than maintaining duplicated option stores.

Why a value object is justified here:

- placement is a real multi-property semantic tuple;
- validation depends on combinations;
- it is shared beyond one element class;
- it must project into two native carriers;
- it avoids another process/global context;
- it does not own document state.

The exact PHP class name is not yet contract-frozen. `DrawingPlacement` is preferred over `FrameStyle` because the semantics are reusable beyond frames.

### 15.2 Stateless projection service/mapper

A stateless projector/mapper should derive:

```text
object attributes
graphic layout properties
insertion semantics
```

from the semantic state.

This projector must not own mutable document state.

## 16. Semantic projection

Conceptually:

```text
DrawingPlacement
    │
    ├── objectAttributes()
    │      text:anchor-type
    │      svg:width
    │      svg:height
    │      svg:x / svg:y when relevant/preserved
    │
    ├── graphicLayoutProperties()
    │      style:horizontal-pos
    │      style:horizontal-rel
    │      style:vertical-pos
    │      style:vertical-rel
    │      style:wrap
    │      ...
    │
    └── insertionMode()
           inline-text-flow for as-char
           floating/block-aware for other anchors
```

The same semantic state can therefore serve multiple native drawing structures while each element keeps responsibility for its own XML structure/content.

## 17. Graphic style integration

The shared placement authority must integrate with the existing SR-06 semantic graphic-style architecture rather than replace it.

Target:

```text
graphic appearance properties
+
graphic layout properties
        ↓
one coherent graphic StyleRequirement where element-owned style is required
```

But geometry/anchor must remain outside the graphic style identity.

The design must avoid creating:

```text
appearance style requirement
+
separate placement graphic style requirement
```

for one frame when one native graphic style can carry both normalized property groups.

Exact style identity migration requires characterization because legacy style names currently include mixed state.

## 18. Master method replacement semantics

`setFrameLayout(array $options)` should replace the **complete friendly frame-layout semantic state**, not merge arbitrary unspecified prior layout options.

It must not clear graphic appearance.

Example:

```php
$box
    ->setBackground('#eeeeee')
    ->setFrameLayout([
        'anchor' => 'paragraph',
        'horizontal' => [
            'alignment' => 'center',
            'relative-to' => 'paragraph',
        ],
    ]);
```

The background remains.

A later:

```php
$box->setFrameLayout([
    'anchor' => 'as-char',
]);
```

replaces the previous friendly placement/layout concerns while preserving appearance/content.

### 18.1 Empty master layout

Recommended:

```php
$box->setFrameLayout([]);
```

clears explicitly authored friendly frame-layout state and returns to element defaults/compatibility defaults.

It must not remove content, image resources, or appearance.

The exact default anchor for each element must be preserved unless the Change Contract explicitly unifies it.

## 19. Convenience call-order semantics

Convenience methods mutate their own semantic concern in the same shared state.

Examples:

```php
$box
    ->setFrameAnchor('paragraph')
    ->setFrameHorizontalAlignment('right', 'page-content')
    ->setFrameVerticalAlignment('top', 'paragraph')
    ->setFrameWrap('parallel');
```

Independent concerns merge.

Competing modes replace each other:

```php
$box->setFrameHorizontalAlignment('center', 'paragraph');
$box->setFrameHorizontalOffset('2cm', 'paragraph');
```

Result:

```text
horizontal mode = offset/from-left
x = 2cm
alignment=center removed as active mode
```

Reverse order:

```php
$box->setFrameHorizontalOffset('2cm', 'paragraph');
$box->setFrameHorizontalAlignment('center', 'paragraph');
```

Result:

```text
horizontal mode = center
x may be retained only as inactive round-trip/compatibility data if required,
but must not control layout
```

For newly authored friendly state, the simpler recommended behavior is to clear the superseded coordinate so state remains canonical.

Writer-preserved existing objects may require retaining inactive coordinates during targeted mutation. That is a different mutation/preservation path and must not force generated-object authoring to keep stale values.

## 20. Generated authoring versus existing-object mutation

FRAME-LAYOUT-01 must keep these operations conceptually separate:

### Generated frame authoring

```text
DrawTextBox / ImageElement
    -> semantic state
    -> generated native structure
```

Canonicalize state aggressively enough to avoid contradictory authored output.

### Existing LibreOffice-authored frame mutation

```text
named/existing frame
    -> preserve unrelated native properties
    -> mutate requested concern only
```

For existing objects, inactive coordinates or Writer-specific companion attributes may need preservation for round-trip stability.

Therefore the generated-object API should not dictate destructive normalization rules for future named-frame mutation.

## 21. `setImage()` compatibility role

`OdtTemplate::setImage()` remains a public utility/compatibility path in FRAME-LAYOUT-01.

It proved useful as an oracle for correct `as-char` paragraph containment.

Recommendation:

- do not rewrite the whole method merely to force all image insertion through `ImageElement`;
- correct clearly invalid native structures only when bounded by characterization;
- share semantic helpers where useful, but preserve protected/public lifecycle behavior;
- do not make `setImage()` the new frame-layout authority.

Its long-term role may shrink after structured ImageElement parity is proven, but that is not a 1.0 requirement.

## 22. Anchor-sensitive insertion contract direction

The materializer needs semantic insertion information.

Preferred direction:

```text
OdtElement / capability
    -> tells materializer insertion semantics

not:

materializer
    -> guesses from node name
```

A minimal capability could conceptually answer:

```text
inline
block
anchor-sensitive frame
```

or simply:

```text
requiresInlineTextFlow(): bool
```

for the first slice.

The exact interface is not frozen here.

Important rule:

- `as-char` frame-backed elements must keep the frame inside the surrounding paragraph;
- this must work in `content.xml` and page-owned `styles.xml` header/footer content.

## 23. Validation policy

The friendly API should fail early on semantic category errors.

Recommended exceptions: `InvalidArgumentException`.

Validate at least:

- supported anchor;
- supported alignment value;
- supported wrap value;
- valid length strings for width/height/offset;
- alignment and offset are mutually exclusive on one axis;
- `relative-to` is present/defaulted appropriately;
- axis-incompatible relation values;
- obvious anchor/relation incompatibility where Writer/ODF evidence is clear.

The raw/legacy path may remain more permissive for compatibility.

## 24. Proposed public 1.0 surface

Subject to Change Contract review:

```php
$box->setFrameLayout([
    'anchor' => 'paragraph',
    'width' => '6cm',
    'height' => '4cm',
    'horizontal' => [
        'alignment' => 'right',
        'relative-to' => 'page-content',
    ],
    'vertical' => [
        'alignment' => 'top',
        'relative-to' => 'paragraph',
    ],
    'wrap' => 'parallel',
]);

$box->setFrameAnchor('paragraph');
$box->setFrameHorizontalAlignment('center', 'paragraph');
$box->setFrameVerticalAlignment('middle', 'paragraph');
$box->setFrameHorizontalOffset('1.5cm', 'paragraph');
$box->setFrameVerticalOffset('0.3cm', 'paragraph');
$box->setFrameWrap('parallel');
```

The same frame-layout API should be available on `ImageElement`.

Existing compatibility methods remain:

```php
$box->setHorizontalPos(...);
$box->setHorizontalPosition(...);
$box->setVerticalPos(...);
$box->setVerticalPosition(...);
$box->flowWithText(...);
$box->setAllowOverlap(...);
```

and ImageElement's historical constructor/`align` inputs remain characterized compatibility surfaces.

## 25. Explicitly rejected API shapes

### Generic ambiguous setters

Reject as new primary APIs:

```php
setAlignment(...)
setPosition(...)
setHorizontalPosition(...) // already legacy/ambiguous
setVerticalPosition(...)   // already legacy/ambiguous
setWrap(...)
```

### Raw QNames as recommended syntax

Reject as normal documentation:

```php
$box->setStyle([
    'style:horizontal-pos' => 'center',
    'style:horizontal-rel' => 'paragraph',
    'svg:y' => '0.3cm',
]);
```

### Percentage pseudo-position

Reject:

```php
[
    'horizontal' => [
        'alignment' => '50%',
    ],
]
```

### One universal `setDrawingStyle()`

Rejected because geometry and anchor are not graphic-style properties and because the current milestone does not justify one universal public Draw hierarchy.

## 26. Characterization gate before production code

Before implementing this design, add/freeze tests for:

1. DrawTextBox constructor anchor/size/current option state;
2. current duplicate horizontal/vertical setter defaults;
3. Sample 17 percentage behavior;
4. current graphic style requirement identity and legacy carrier selection;
5. ImageElement constructor autoscaling;
6. ImageElement `align` translation;
7. ImageElement direct `svg:x/y`;
8. ImageElement materialization mutation of `imageOptions`;
9. `as-char` body insertion failure;
10. `as-char` header insertion failure;
11. `setImage()` visible paragraph-wrapped baseline;
12. repeated save/materialization;
13. body vs styles/header parity;
14. `flowWithText()` and `setAllowOverlap()` compatibility output.

The H1-H7 research tooling can inform these tests but temporary ODT research artifacts should not become brittle committed fixtures unless needed.

## 27. Proposed implementation slices

### Slice 0 — Characterization Gate

No production behavior change.

### Slice 1 — Shared semantic state + projection

Introduce the shared placement semantic representation and stateless projection.

Do not yet change all public render output.

### Slice 2 — DrawTextBox semantic migration

Route new API and valid legacy placement calls through the shared authority.

Correct native carrier ownership.

Migrate Sample 17 to the semantic API.

### Slice 3 — ImageElement semantic migration

Route ImageElement placement through the same authority while preserving resource/autoscale behavior.

### Slice 4 — Anchor-sensitive insertion

Make structured insertion respect `as-char` text-flow semantics in body and header/footer document parts.

### Slice 5 — Wrap/legacy layout compatibility

Move retained `flowWithText`, wrap influence, overlap, and legacy mapper behavior onto the normalized carrier without widening the friendly 1.0 API unnecessarily.

### Slice 6 — Integration / visual regression

Create a public FRAME-LAYOUT sample and render through `tools/visual-regression/render.sh`.

Run focused tests, PublicSampleSmokeTest, full suite, lint, diff check, and manual LibreOffice regression.

## 28. Open decisions before Change Contract

The design is mostly converged. The remaining decisions are narrow:

1. confirm `setFrameLayout()` as the master public name;
2. confirm nested `horizontal` / `vertical` groups rather than flat keys;
3. confirm `setFrameHorizontalAlignment/Offset` and vertical equivalents;
4. freeze the exact friendly relation matrix;
5. decide whether `flow-with-text` enters the master array now or remains compatibility-only;
6. decide whether `allow-overlap` enters the master array or remains explicit LibreOffice compatibility API;
7. freeze the transition policy for legacy invalid percentage position values;
8. choose the exact internal type name (`DrawingPlacement` is preferred);
9. choose the minimal insertion-capability interface/contract for `as-char`.

These are suitable for a final Compatibility / Call-Order Review before drafting the Change Contract.

## 29. Design-pass verdict

The recommended API direction is:

```text
PUBLIC FRAME-BACKED API
    setFrameLayout([...])

EXPLICIT CONVENIENCE METHODS
    setFrameAnchor(...)
    setFrameHorizontalAlignment(...)
    setFrameVerticalAlignment(...)
    setFrameHorizontalOffset(...)
    setFrameVerticalOffset(...)
    setFrameWrap(...)

SHARED INTERNAL AUTHORITY
    DrawingPlacement-like semantic value

NATIVE PROJECTION
    object geometry attributes
    +
    graphic layout properties
    +
    anchor-aware insertion semantics

COMPATIBILITY FACADES
    existing DrawTextBox methods
    existing ImageElement constructor/align behavior
    setImage()
```

This preserves the current frame-backed public model while establishing a semantic foundation reusable by future Draw elements.

Proceed next to a focused FRAME-LAYOUT-01B Compatibility / Call-Order Review, then freeze FRAME-LAYOUT-01C as the Change Contract.

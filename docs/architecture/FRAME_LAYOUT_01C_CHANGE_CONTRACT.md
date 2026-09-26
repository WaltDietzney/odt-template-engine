# FRAME-LAYOUT-01C — Change Contract

Status: **CHANGE CONTRACT / IMPLEMENTATION AUTHORITY**

Milestone: `FRAME-LAYOUT-01`

Branch: `architecture/frame-layout-01`

Predecessors:

- `FRAME_LAYOUT_01A_ARCHITECTURE_SYNTHESIS.md`
- `FRAME_LAYOUT_01B_API_COMPATIBILITY_DESIGN_PASS.md`
- `FRAME_LAYOUT_01B_COMPATIBILITY_CALL_ORDER_REVIEW.md`

Evidence base:

- FRAME-LAYOUT A0.1–A0.4 research and characterization;
- current repository code and tests;
- Writer-authored fixture matrix;
- Writer source research recorded in A0;
- ODF 1.1/1.2 specifications supplied to the project;
- established SR-06 graphic-style ownership architecture.

This document freezes the intended semantics, public API, compatibility boundaries, implementation slices, and acceptance gates for FRAME-LAYOUT-01.

Production work under FRAME-LAYOUT-01 MUST conform to this contract unless the contract is explicitly amended first.

---

## 1. Contract objective

FRAME-LAYOUT-01 establishes a reliable semantic layout API for frame-backed structured elements while creating a reusable internal foundation for future Draw elements.

The current public participants are:

```text
DrawTextBox
ImageElement
```

Both materialize through `draw:frame`, but their element-specific content remains distinct.

The milestone MUST:

1. provide a friendly frame-layout API;
2. separate alignment from coordinate positioning;
3. separate position mode from reference area;
4. serialize geometry and graphic-layout properties through their correct native carriers;
5. preserve graphic appearance ownership;
6. preserve public compatibility paths where feasible;
7. fix anchor-sensitive `as-char` structured insertion;
8. support body and page-owned header/footer document parts;
9. remain stable across repeated render/save;
10. provide reusable drawing-layout semantics without introducing a speculative universal Draw inheritance hierarchy.

---

## 2. Non-goals

FRAME-LAYOUT-01 MUST NOT become:

- a page-layout calculator;
- a full Writer drawing UI abstraction;
- a comprehensive CustomShape API;
- an enhanced-geometry API;
- a master-page drawing API;
- a general named-object mutation API;
- a broad image-resource rewrite;
- a generic `DrawingObject` class hierarchy;
- a compatibility cleanup that silently removes historical public methods;
- a mechanism that guesses semantic intent from invalid legacy values.

Relative sizing, contour geometry, arbitrary transforms, rotation, comprehensive margin-relative positioning, and other advanced Draw concerns remain outside the new friendly 1.0 API unless required to preserve existing behavior.

---

## 3. Native ownership rule

Drawing layout is one semantic concern serialized through multiple native carriers.

### 3.1 Object attributes

The following are object/frame geometry or identity concerns:

```text
text:anchor-type
svg:width
svg:height
svg:x
svg:y
draw:z-index
draw:name
```

where applicable.

They MUST NOT become graphic-style identity merely because they participate in placement.

### 3.2 Graphic layout properties

The following belong to native graphic layout/style properties:

```text
style:horizontal-pos
style:horizontal-rel
style:vertical-pos
style:vertical-rel
style:wrap
style:flow-with-text
draw:wrap-influence-on-position
loext:allow-overlap
```

where supported.

These MUST NOT be emitted directly on `draw:frame` by the new semantic path merely because historical code did so.

### 3.3 Graphic appearance

Appearance properties such as fill, background, border, padding, stroke, and bitmap-fill remain graphic-style concerns.

FRAME-LAYOUT MUST integrate layout properties with the established semantic graphic-style requirement system rather than create a second competing style authority.

---

## 4. Public master API

The master public authoring method is frozen as:

```php
$element->setFrameLayout(array $layout): self;
```

It MUST be available consistently on `DrawTextBox` and `ImageElement`.

Canonical example:

```php
$element->setFrameLayout([
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

The public friendly API MUST use semantic vocabulary. Raw ODF QNames are not the recommended authoring syntax.

---

## 5. Public convenience API

The following method family is frozen:

```php
setFrameAnchor(string $anchor): self

setFrameHorizontalAlignment(
    string $alignment,
    ?string $relativeTo = null
): self

setFrameVerticalAlignment(
    string $alignment,
    ?string $relativeTo = null
): self

setFrameHorizontalOffset(
    string $offset,
    ?string $relativeTo = null
): self

setFrameVerticalOffset(
    string $offset,
    ?string $relativeTo = null
): self

setFrameWrap(string $wrap): self
```

The nullable relation parameter is intentional: it permits semantic defaults to depend on the active anchor rather than freezing misleading historical defaults into the new API signature.

For ordinary floating anchors the effective default relation is `paragraph`.

For `as-char`, vertical alignment without an explicit relation defaults to `baseline`.

A horizontal friendly placement operation while the active anchor is `as-char` MUST fail validation rather than silently changing the anchor.

No generic new methods named `setAlignment()`, `setPosition()`, `setHorizontalPosition()`, `setVerticalPosition()`, or `setWrap()` shall be introduced as semantic authorities.

---

## 6. Friendly master vocabulary

### 6.1 Top-level keys

The initial supported friendly keys are:

```text
anchor
width
height
horizontal
vertical
wrap
```

Unknown friendly keys MUST fail validation.

Compatibility/native APIs are separate from this strict surface.

### 6.2 Horizontal group

Exactly one positioning mode may be active:

```php
'horizontal' => [
    'alignment' => 'center',
    'relative-to' => 'paragraph',
]
```

or:

```php
'horizontal' => [
    'offset' => '1.5cm',
    'relative-to' => 'paragraph',
]
```

A group containing both `alignment` and `offset` MUST throw `InvalidArgumentException`.

### 6.3 Vertical group

Likewise:

```php
'vertical' => [
    'alignment' => 'middle',
    'relative-to' => 'paragraph',
]
```

or:

```php
'vertical' => [
    'offset' => '0.3cm',
    'relative-to' => 'paragraph',
]
```

Both modes in one declaration are invalid.

### 6.4 Alignment values

Horizontal:

```text
left
center
right
```

Vertical:

```text
top
middle
bottom
```

The native tokens `from-left` and `from-top` are NOT friendly alignment values.

### 6.5 Offset semantics

Friendly horizontal offset means:

```text
style:horizontal-pos = from-left
+
style:horizontal-rel = selected reference area
+
svg:x = supplied offset
```

Friendly vertical offset means:

```text
style:vertical-pos = from-top
+
style:vertical-rel = selected reference area
+
svg:y = supplied offset
```

### 6.6 Wrap values

The friendly 1.0 wrap values are:

```text
none
left
right
parallel
dynamic
run-through
```

Other native values may remain available through compatibility/native paths but are not part of this contract's friendly API.

---

## 7. Anchor vocabulary

The friendly 1.0 anchors are:

```text
paragraph
char
as-char
page
```

Other existing native values MUST NOT be deleted merely because they are outside the friendly set.

Anchor is a semantic input to both placement validation and structured insertion behavior.

---

## 8. Friendly relation matrix

The friendly relation set is deliberately bounded.

### 8.1 paragraph anchor

Horizontal:

```text
paragraph
paragraph-content
page
page-content
```

Vertical:

```text
paragraph
paragraph-content
page
page-content
```

### 8.2 char anchor

Horizontal:

```text
char
paragraph
paragraph-content
page
page-content
```

Vertical:

```text
char
paragraph
paragraph-content
page
page-content
baseline
```

### 8.3 page anchor

Horizontal and vertical:

```text
page
page-content
```

### 8.4 as-char anchor

Horizontal friendly frame positioning is not supported.

Vertical relation:

```text
baseline
```

Vertical friendly alignment family:

```text
top
middle
bottom
```

Before production support for all three is considered complete, tests MUST verify Writer/ODF behavior. If evidence disproves one member, the contract MUST be amended rather than implementing guessed behavior.

Friendly offset mode for `as-char` is outside FRAME-LAYOUT-01.

### 8.5 Raw compatibility

Existing raw/native relation values outside this matrix may remain preserved on legacy paths.

The bounded friendly matrix MUST NOT be used as a destructive validator for pre-existing native ODF state.

---

## 9. Length validation

Friendly `width`, `height`, and `offset` values MUST be valid ODF-compatible absolute length strings accepted by the engine's chosen validation utility.

Percentage pseudo-position values are invalid.

FRAME-LAYOUT MUST NOT define:

```text
50% = center
100% = right
```

or any equivalent inferred positioning semantics.

Relative frame sizing is not introduced by this contract.

---

## 10. Internal semantic authority

The preferred internal semantic type is frozen conceptually as:

```text
DrawingLayout
```

A concrete class with this name SHOULD be used unless implementation-fit review demonstrates a material compatibility problem. Any alternative requires documenting the reason before production migration.

`DrawingLayout` owns normalized drawing-layout semantics, including at least:

```text
anchor
width
height
horizontal mode
horizontal relation
horizontal offset/x
vertical mode
vertical relation
vertical offset/y
wrap
retained compatibility layout policy where needed
```

It MUST NOT own:

- document/package state;
- image resources;
- text-box content;
- global style registries;
- template-language state.

It SHOULD be immutable or otherwise designed so that one canonical state exists per element and convenience mutations cannot create duplicated mutable authorities.

---

## 11. Projection boundary

A stateless projection component MUST translate semantic drawing layout into native requirements.

Conceptually:

```text
DrawingLayout
    |
    +-- object attributes
    |     text:anchor-type
    |     svg:width
    |     svg:height
    |     svg:x
    |     svg:y
    |
    +-- graphic layout properties
    |     style:horizontal-pos
    |     style:horizontal-rel
    |     style:vertical-pos
    |     style:vertical-rel
    |     style:wrap
    |     compatibility layout properties
    |
    +-- insertion semantics
          inline text flow
          block/floating
```

The projector MUST NOT own mutable document state.

The implementation may use one projector or narrowly separated stateless helpers if that produces clearer responsibilities.

---

## 12. Graphic style requirement integration

FRAME-LAYOUT MUST integrate with the existing semantic `StyleRequirement` architecture.

Target graphic style identity:

```text
graphic style identity
    =
    graphic appearance properties
    +
    graphic layout properties
```

It MUST exclude object geometry/identity such as:

```text
width
height
anchor
x
y
name
z-index
```

A frame MUST NOT receive two independent generated graphic style definitions merely because appearance and layout were authored through different public calls.

Repeated equivalent semantic state MUST deduplicate to stable style requirements.

Generated hash/style names are internal and are not required to remain identical to pre-FRAME-LAYOUT legacy names.

---

## 13. Master replacement semantics

`setFrameLayout($layout)` replaces the complete **friendly core layout state**.

It does not clear:

- content;
- image resources;
- graphic appearance;
- explicit compatibility policy state authored through retained compatibility methods.

Example:

```php
$box->flowWithText(true);
$box->setFrameLayout([
    'anchor' => 'paragraph',
]);
```

MUST retain the explicit `flowWithText(true)` compatibility policy.

### 13.1 Empty layout

```php
$element->setFrameLayout([]);
```

clears explicitly authored friendly core layout state and returns to the element's existing compatibility/default behavior.

It MUST NOT clear appearance, content, resources, or retained explicit compatibility policy.

---

## 14. Convenience call-order rules

Convenience methods mutate only their owned concern.

Independent concerns merge.

### 14.1 Competing axis modes

```php
$element->setFrameHorizontalAlignment('center', 'paragraph');
$element->setFrameHorizontalOffset('2cm', 'paragraph');
```

results in offset mode.

The reverse order results in alignment mode.

For newly authored friendly state, the superseded coordinate/alignment MUST be cleared from canonical active state.

The same applies vertically.

### 14.2 Master after convenience

A later `setFrameLayout([...])` replaces all prior friendly core layout state.

### 14.3 Convenience after master

A later convenience method mutates only its concern in the master-created state.

### 14.4 Appearance commutes with layout

Appearance setters and frame-layout setters MUST NOT erase each other's concerns.

---

## 15. Legacy DrawTextBox API contract

The following existing methods remain callable:

```php
setHorizontalPos(string $pos, string $rel = 'char')
setHorizontalPosition(string $pos, string $rel = 'page')
setVerticalPos(string $pos, string $rel = 'baseline')
setVerticalPosition(string $pos, string $rel = 'page')
flowWithText(bool $enable = true)
setAllowOverlap(bool $allow = true)
```

Existing default arguments MUST remain unchanged.

### 15.1 Valid alignment calls

Recognized alignment values SHOULD translate into the shared semantic authority while preserving the legacy relation argument/default.

### 15.2 Legacy from-left/from-top

Legacy coordinate-mode tokens remain callable.

If an accompanying raw coordinate already exists, it may be used/preserved.

If no coordinate exists, FRAME-LAYOUT MUST NOT invent one.

### 15.3 Legacy percentages

Invalid historical values such as:

```text
50%
100%
```

remain raw/pass-through on legacy paths for 1.0.

They:

- MUST NOT be auto-translated;
- MUST NOT be documented as recommended API;
- MUST be rejected by the new friendly API;
- MAY continue to produce Writer-ineffective legacy output as characterized.

Sample 17 MUST migrate to explicit semantic values.

---

## 16. Legacy ImageElement compatibility

Existing constructor/options behavior remains public compatibility surface.

### 16.1 Autoscaling

Existing behavior when only width or height is supplied MUST remain characterized and preserved.

The generic friendly frame-layout API MUST NOT silently introduce a second image-ratio/autoscaling policy.

### 16.2 align

Legacy `align` remains accepted.

The characterized translations are:

```text
left
    horizontal = left relative to paragraph
    wrap = right

right
    horizontal = right relative to paragraph
    wrap = left

center
    horizontal = center relative to paragraph
    wrap = none
```

### 16.3 absolute

`align=absolute` remains compatibility-only.

If explicit x state exists, it may participate in coordinate positioning.

If no x exists, the engine MUST preserve the characterized legacy semantics rather than invent `0cm`.

`absolute` is not a new friendly alignment value.

### 16.4 Observable imageOptions mutation

Current materialization writes resolved compatibility placement values into `imageOptions`.

FRAME-LAYOUT-01 MUST preserve equivalent observable behavior of `getImageOptions()` unless this contract is amended.

Internal output authority nevertheless moves to `DrawingLayout`.

---

## 17. Compatibility policy properties

### 17.1 flow-with-text

`flowWithText()` remains public.

It MUST migrate to the normalized graphic-layout carrier.

It is NOT a top-level `setFrameLayout()` key in FRAME-LAYOUT-01.

### 17.2 wrap influence

Existing wrap-influence inputs remain compatibility/native state.

Known valid values should be preserved.

Historical invalid/non-native values MUST NOT be silently reinterpreted.

No new friendly `wrap-influence` key is introduced.

### 17.3 allow-overlap

`setAllowOverlap()` remains public.

It maps to LibreOffice-specific `loext:allow-overlap`.

It MUST be documented/treated as LibreOffice-specific rather than portable ODF-core semantics.

It is not a top-level master-array key.

### 17.4 Primary wrap call order

`setFrameWrap()` changes only the primary wrap concern.

It MUST NOT implicitly clear flow-with-text, wrap influence, overlap, or contour state.

---

## 18. Anchor-sensitive structured insertion

Anchor semantics affect insertion.

The required semantic result is:

```text
anchor = as-char
    -> inline text-flow insertion
    -> containing text:p is preserved

floating anchors
    -> existing block/floating insertion semantics
```

The materializer MUST NOT decide this solely from the generated DOM node name.

It MUST NOT globally classify every `draw:frame` as inline.

### 18.1 Preferred insertion contract

The preferred semantic boundary is conceptually:

```text
StructuredInsertionMode
    INLINE_TEXT_FLOW
    BLOCK
```

with an element capability/hook that exposes the mode.

Exact PHP representation may be an enum/interface or a smaller compatibility-preserving hook on `OdtElement`.

The semantic contract is mandatory even if the implementation shape changes.

### 18.2 Body and styles/header parity

The `as-char` fix MUST work in:

- `content.xml`;
- page-owned header/footer content in `styles.xml`.

A body-only fix is incomplete.

---

## 19. DrawTextBox DOM return-shape compatibility

Current `DrawTextBox::toDomNode()` return shape varies by anchor.

This is observable and MUST be characterization-gated before any normalization.

FRAME-LAYOUT SHOULD move toward coherent ownership where the element emits its drawing structure and the materializer owns placeholder/container insertion.

However, the milestone MUST NOT casually change `toDomNode()` shape if doing so breaks callers or tests.

If a wrapper-shape change becomes necessary for the insertion architecture, it requires:

1. characterization;
2. explicit compatibility analysis;
3. protected/public override review;
4. focused integration tests.

---

## 20. OdtTemplate::setImage compatibility

`setImage()` remains a supported public/compatibility path.

FRAME-LAYOUT MAY reuse semantic helpers, but MUST NOT rewrite it wholesale as an `ImageElement` facade.

Preserve:

- placeholder replacement lifecycle;
- body/styles processing;
- resource handling;
- relevant protected override observability;
- repeated save behavior;
- known working as-char paragraph containment.

Any correction of historically invalid wrap serialization in this path requires dedicated characterization before behavior change.

---

## 21. Generated authoring versus existing-object mutation

FRAME-LAYOUT distinguishes two future/use-case semantics.

### Generated frame authoring

Canonicalization is appropriate:

- contradictory friendly state rejected;
- superseded authored coordinates cleared;
- native output generated from semantic authority.

### Existing Writer-authored object mutation

Preservation is primary:

- unrelated native attributes preserved;
- inactive Writer-authored coordinates may remain;
- targeted changes should not rebuild unrelated structure.

FRAME-LAYOUT-01 MUST NOT force generated-object canonicalization rules onto future named/existing-object mutation APIs.

---

## 22. Error policy

New friendly API argument-domain errors MUST throw `InvalidArgumentException`.

Examples:

- unknown master key;
- unsupported anchor;
- unsupported alignment;
- unsupported wrap;
- malformed length;
- alignment and offset on one axis;
- invalid relation for axis/anchor;
- horizontal friendly placement while anchor is `as-char`.

`LogicException` is reserved for lifecycle/state conflicts rather than ordinary invalid public arguments.

Legacy compatibility paths remain permissive only where this contract explicitly preserves them.

---

## 23. Characterization gate

Before production behavior is changed, tests MUST freeze at least:

1. all four legacy DrawTextBox position methods and their defaults;
2. percentage pass-through;
3. legacy `from-left/from-top` without coordinates;
4. DrawTextBox anchor/size constructor behavior;
5. DrawTextBox current DOM wrapper shape;
6. `flowWithText()`;
7. `setAllowOverlap()`;
8. wrap-influence compatibility values;
9. ImageElement autoscaling;
10. ImageElement `align=left/right/center/absolute`;
11. explicit ImageElement `svg:x/y`;
12. ImageElement materialization mutation of `imageOptions`;
13. current semantic/legacy graphic style identities;
14. current `as-char` structured insertion defect in body;
15. current `as-char` structured insertion defect in header/styles;
16. working `setImage()` paragraph-wrapped baseline;
17. repeated render/materialization/save;
18. body/styles document-part behavior.

Characterization tests describe current behavior; they do not automatically approve that behavior as the target semantic contract.

---

## 24. Implementation slices

Production work MUST proceed in bounded slices.

### Slice 0 — Characterization Gate

No intended production behavior change.

Freeze the compatibility/lifecycle surface listed above.

Gate:

- focused characterization suite green;
- no production code change;
- `git diff --check` clean.

### Slice 1 — DrawingLayout semantic core

Introduce:

- shared `DrawingLayout` semantic representation;
- strict friendly validation;
- stateless native projection;
- shared public frame-layout API surface where it can be added without changing existing output.

This slice SHOULD initially prove semantic state/projection independently from broad producer migration.

Gate:

- new unit tests for validation and call order;
- existing characterization green;
- no duplicate mutable semantic authority introduced.

### Slice 2 — DrawTextBox migration

Move DrawTextBox layout output onto `DrawingLayout`.

Requirements:

- new master/convenience API works;
- legacy setters remain callable;
- graphic appearance remains stable;
- layout properties materialize through native graphic style;
- geometry remains object attributes;
- Sample 17 migrates away from percentage pseudo-position;
- legacy invalid calls remain characterized.

Gate:

- focused DrawTextBox tests;
- Sample 17 output visually checked in LibreOffice;
- visual-regression render available where useful.

### Slice 3 — ImageElement migration

Move ImageElement onto the same layout authority.

Requirements:

- constructor autoscaling preserved;
- align compatibility preserved;
- image resource behavior preserved;
- `getImageOptions()` compatibility preserved;
- geometry/layout native ownership corrected.

Gate:

- focused ImageElement tests;
- body insertion behavior characterized;
- repeated materialization stable.

### Slice 4 — Anchor-sensitive structured insertion

Introduce semantic insertion mode/hook.

Requirements:

- `as-char` frame-backed elements remain inside text flow;
- containing paragraph is preserved;
- body and header/styles paths both work;
- no blanket `draw:frame` inline rule;
- floating frame behavior remains characterized.

Gate:

- body integration tests;
- header/styles integration tests;
- setImage baseline remains green.

### Slice 5 — Compatibility layout-policy migration

Move retained:

- `flowWithText()`;
- wrap influence;
- `setAllowOverlap()`;
- remaining compatible legacy layout carrier state

onto coherent normalized graphic-layout requirements without widening the friendly API.

Gate:

- compatibility tests;
- semantic style deduplication tests;
- repeated save stability.

### Slice 6 — Integration, sample, visual regression, closeout

Create or extend a public sample demonstrating:

- paragraph-relative left/center/right;
- vertical top/middle/bottom where supported;
- explicit x/y offset positioning;
- multiple wrap modes;
- as-char image/textbox behavior;
- image and text-box parity;
- header use where practical.

The sample MUST use the new friendly API rather than raw QNames for the demonstrated FRAME-LAYOUT features.

Use:

```text
tools/visual-regression/render.sh
```

to assist ODT -> PDF -> PNG review.

Manual LibreOffice review remains mandatory for layout-visible changes.

---

## 25. Test and preflight contract

After relevant slices, run focused tests plus the affected integration/compatibility suites.

Before closeout, normally run:

```text
composer test
PHP lint for src/ and tests/
PublicSampleSmokeTest
git diff --check
composer validate when relevant
documentation build when relevant
```

The final suite MUST be green except for already-known, explicitly unrelated baseline deprecations/warnings.

Visual correctness MUST be checked in LibreOffice for the final FRAME-LAYOUT sample.

Generated files under `samples/output/` are local regression artifacts and MUST NOT be committed, restored, deleted, or regenerated as repository changes unless explicitly in scope.

LibreOffice `.~lock.*#` files MUST NOT be committed.

---

## 26. Compatibility and polymorphism contract

Before extracting or changing existing methods:

- inspect public/protected visibility;
- inspect subclass overrides/usages;
- preserve protected facade wrappers where polymorphism requires them;
- do not silently bypass override points through new services.

Refactoring and behavior correction SHOULD remain separated where practical.

If implementation reveals that a protected compatibility facade is required, retain it and document the delegation.

---

## 27. Documentation contract

The following MUST be updated when implementation semantics become real:

- relevant public API documentation;
- samples;
- ROADMAP closeout state;
- FUTURE_DEVELOPMENT if newly discovered deferred work is material;
- architecture closeout document.

New findings that materially affect future Draw semantics MUST be recorded rather than left only in chat.

---

## 28. Acceptance criteria

FRAME-LAYOUT-01 is complete only when all of the following hold.

### Public API

- `setFrameLayout()` works on DrawTextBox and ImageElement.
- Approved convenience methods work on both.
- Friendly API rejects contradictory/category-invalid state.
- Alignment and offset are distinct operations.

### Native semantics

- anchor/geometry are object attributes.
- position/relation/wrap are native graphic-layout properties.
- x/y become operative only through offset/from-left/from-top semantics.
- appearance and layout share coherent graphic style requirements.

### Compatibility

- legacy DrawTextBox methods remain callable.
- their default relations remain unchanged.
- legacy percentages are not reinterpreted.
- ImageElement align/autoscale compatibility remains.
- `getImageOptions()` observable compatibility remains.
- `flowWithText()` and `setAllowOverlap()` remain.
- `setImage()` remains functional.

### Insertion

- as-char structured ImageElement is visible and paragraph-contained.
- as-char structured DrawTextBox is paragraph/text-flow compatible.
- body and header/styles insertion both work.
- floating frame insertion does not regress.

### Lifecycle

- repeated render/materialization/save is stable.
- semantic graphic definitions deduplicate.
- no document-part drift occurs.

### Regression

- focused tests green;
- full test suite green apart from explicitly unrelated baseline issues;
- PublicSampleSmokeTest green;
- diff/lint/preflight clean;
- final sample visually correct in LibreOffice.

---

## 29. Stop conditions

Implementation MUST stop and return to architecture review if any slice reveals that:

1. the friendly relation matrix conflicts with Writer/ODF evidence;
2. one shared `DrawingLayout` cannot represent ImageElement and DrawTextBox without element-specific hacks;
3. correct native carrier ownership requires breaking an established public API beyond the compatibility rules here;
4. insertion semantics cannot be fixed without materially changing generic structured insertion behavior;
5. graphic style integration would require re-opening SR-06 ownership decisions;
6. repeated save stability cannot be preserved;
7. header/styles behavior materially diverges from body behavior;
8. a proposed change requires pulling CustomShape/master-page/named-object architecture into this milestone.

A stop condition requires evidence and an explicit contract amendment, not an ad hoc implementation workaround.

---

## 30. Final contract summary

FRAME-LAYOUT-01 establishes this architecture:

```text
PUBLIC AUTHORING
    setFrameLayout([...])
    explicit setFrame* convenience methods
             |
             v
SHARED SEMANTIC AUTHORITY
    DrawingLayout
             |
             +-------------------------+
             |                         |
             v                         v
OBJECT GEOMETRY                 GRAPHIC LAYOUT
anchor / size / x / y           alignment / relation / wrap
             |                         |
             +-------------+-----------+
                           |
                           v
                ELEMENT MATERIALIZATION
                           |
                           v
              ANCHOR-AWARE INSERTION
                as-char -> text flow
                floating -> block/floating
```

Compatibility remains a facade around this target, not its semantic definition.

The implementation sequence is:

```text
Slice 0  Characterization
Slice 1  DrawingLayout core
Slice 2  DrawTextBox
Slice 3  ImageElement
Slice 4  anchor-sensitive insertion
Slice 5  compatibility policies
Slice 6  integration / visual regression / closeout
```

With this contract accepted, FRAME-LAYOUT-01 may proceed to **Slice 0 — Characterization Gate**.

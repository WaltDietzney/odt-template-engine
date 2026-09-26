# FRAME-LAYOUT-01B — Compatibility / Call-Order Review

Status: REVIEWED / PRE-CONTRACT

Milestone: `FRAME-LAYOUT-01`

Related:

- `FRAME_LAYOUT_01A_ARCHITECTURE_SYNTHESIS.md`
- `FRAME_LAYOUT_01B_API_COMPATIBILITY_DESIGN_PASS.md`
- A0.1–A0.4 evidence documents

User-approved API decisions entering this review:

1. `setFrameLayout()` is accepted as the master public authoring method.
2. Nested `horizontal` / `vertical` groups are accepted.
3. The explicit convenience method family is accepted:
   - `setFrameAnchor()`
   - `setFrameHorizontalAlignment()`
   - `setFrameVerticalAlignment()`
   - `setFrameHorizontalOffset()`
   - `setFrameVerticalOffset()`
   - `setFrameWrap()`

This review resolves the remaining compatibility, call-order, validation, internal-authority, and insertion-boundary questions before the Change Contract is drafted.

No production implementation is authorized by this document.

## 1. Governing compatibility principle

FRAME-LAYOUT-01 introduces a new semantic authority without pretending that historical frame APIs were already semantically correct.

The compatibility rule is:

> Preserve callable public surfaces and observable lifecycle behavior where feasible, but do not make invalid or non-native historical semantics the authority for new authoring.

This yields two layers:

```text
new friendly API
    -> strict semantic validation
    -> canonical native output

legacy/public compatibility API
    -> preserved callable surface
    -> compatibility translation where intent is unambiguous
    -> raw/pass-through retention where intent cannot be safely inferred
```

Backward compatibility does not require silently inventing meaning for invalid historical values.

## 2. State model

The target element state should have one shared semantic frame-layout authority plus existing element-specific state.

Conceptually:

```text
DrawTextBox
├── content
├── appearance
├── compatibility input state
└── DrawingLayout

ImageElement
├── image resource/autoscale state
├── appearance/compatibility input state
└── DrawingLayout
```

There must not be independent mutable authorities for:

```text
frameOptions
imageOptions
frameLayoutOptions
positionOptions
wrapOptions
```

after migration is complete.

Legacy arrays may remain as compatibility facades or observable snapshots during transition, but they must not independently decide final native output.

## 3. Internal type name

The earlier design proposed `DrawingPlacement`.

After review, **`DrawingLayout` is preferred**.

Reasons:

- the semantic state contains more than placement;
- width/height are included;
- wrap belongs to the bounded 1.0 core;
- insertion mode is derived from anchor/layout semantics;
- the type is intended to be reusable by future Draw elements;
- `FrameLayout` would be too narrow internally;
- `DrawingStyle` would wrongly imply graphic-style ownership.

Conceptually:

```text
DrawingLayout
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
    selected retained layout-policy state
```

The Change Contract should freeze the exact class namespace/name only after implementation-fit review, but `DrawingLayout` is the preferred contract name.

## 4. Master method semantics

```php
$element->setFrameLayout([...]);
```

is a **complete replacement of friendly frame-layout state**.

It does not replace:

- frame content;
- image resource state;
- graphic appearance;
- text-box child content.

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

replaces the prior friendly frame-layout concerns.

### 4.1 Empty master call

Decision:

```php
$element->setFrameLayout([]);
```

clears explicitly authored friendly frame-layout state and returns the element to its element-specific compatibility/default behavior.

It does not clear:

- appearance;
- content;
- resources.

This differs intentionally from TABLE-LAYOUT's `setTableStyle([])`, because frame-backed elements already have constructor/default geometry/anchor behavior that must remain compatible.

## 5. Convenience call-order semantics

Convenience methods mutate one concern in the shared `DrawingLayout`.

Independent concerns merge regardless of call order.

Example:

```php
$element
    ->setFrameAnchor('paragraph')
    ->setFrameHorizontalAlignment('center', 'paragraph')
    ->setFrameVerticalAlignment('top', 'paragraph')
    ->setFrameWrap('parallel');
```

Equivalent orderings produce equivalent semantic state.

### 5.1 Alignment versus offset replacement

On one axis, alignment and offset are competing modes.

```php
$element->setFrameHorizontalAlignment('center', 'paragraph');
$element->setFrameHorizontalOffset('2cm', 'paragraph');
```

Result:

```text
horizontal mode = offset/from-left
horizontal relation = paragraph
x = 2cm
active alignment removed
```

Reverse order:

```php
$element->setFrameHorizontalOffset('2cm', 'paragraph');
$element->setFrameHorizontalAlignment('center', 'paragraph');
```

Result for newly authored semantic state:

```text
horizontal mode = center
horizontal relation = paragraph
x cleared from canonical authored state
```

The same rule applies vertically.

### 5.2 Why newly authored state clears inactive coordinates

Writer may preserve stale `svg:x/y` while semantic alignment is active.

Generated friendly authoring should not imitate that incidental round-trip residue.

Existing-object mutation is different: it may preserve inactive Writer-authored coordinates when the caller did not request changing them.

## 6. Master versus convenience call order

| Earlier call | Later call | Result |
| --- | --- | --- |
| none | `setFrameLayout([...])` | create/replace friendly layout authority |
| none | convenience setter | create semantic state with element defaults for unspecified concerns |
| `setFrameLayout([...])` | convenience setter | mutate only that concern |
| convenience setter(s) | `setFrameLayout([...])` | master call replaces complete friendly layout state |
| convenience setter(s) | another independent convenience setter | merge |
| alignment setter | offset setter on same axis | offset mode wins |
| offset setter | alignment setter on same axis | alignment mode wins, authored coordinate cleared |
| any friendly state | `setFrameLayout([])` | clear friendly state, fall back to compatibility/default state |

## 7. Friendly relation/reference-area matrix

The 1.0 friendly API should be intentionally narrower than the complete ODF relation vocabulary.

### 7.1 Axis-level vocabulary

Horizontal friendly `relative-to` values:

```text
paragraph
paragraph-content
page
page-content
char
```

Vertical friendly `relative-to` values:

```text
paragraph
paragraph-content
page
page-content
char
baseline
```

Values such as:

```text
line
text
frame
frame-content
paragraph-start-margin
paragraph-end-margin
page-start-margin
page-end-margin
inside/outside variants
```

remain raw/future unless a 1.0 compatibility case requires them.

### 7.2 Anchor-aware rules

The friendly API should apply a conservative supported matrix.

#### anchor = paragraph

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

#### anchor = char

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

This reflects current public defaults plus Writer/ODF evidence without attempting to expose the full native combination table.

#### anchor = as-char

Horizontal placement group:

```text
not supported by the new friendly 1.0 frame-layout API
```

Reason: an as-character frame participates in text flow; its horizontal placement is governed by character/paragraph text layout rather than floating frame alignment.

Vertical friendly relation:

```text
baseline
```

Friendly vertical alignments:

```text
top
middle
bottom
```

The fixture directly evidenced `top + baseline`. `middle/bottom` are retained as the bounded semantic family to be contract-tested against Writer/ODF behavior before implementation freeze.

Offset mode for `as-char` is not part of the first friendly 1.0 surface.

#### anchor = page

Horizontal:

```text
page
page-content
```

Vertical:

```text
page
page-content
```

### 7.3 Compatibility/raw paths remain broader

Legacy/native callers may continue to carry other relation tokens where already supported.

The friendly API should not reject valid existing authored ODF merely because the first friendly matrix is intentionally narrow.

## 8. Default relation policy

Convenience methods should have explicit, semantically safe defaults.

Recommended:

```php
setFrameHorizontalAlignment($alignment, $relativeTo = 'paragraph')
setFrameVerticalAlignment($alignment, $relativeTo = 'paragraph')
setFrameHorizontalOffset($offset, $relativeTo = 'paragraph')
setFrameVerticalOffset($offset, $relativeTo = 'paragraph')
```

Exception:

For an element whose current semantic anchor is `as-char`, calling:

```php
setFrameVerticalAlignment('top')
```

should default relation to:

```text
baseline
```

rather than paragraph.

A horizontal convenience call while anchor is `as-char` should reject rather than silently change the anchor.

This makes defaults dependent on semantic anchor, not on which historical method name the caller happened to choose.

## 9. Legacy DrawTextBox setters

Existing public methods remain callable:

```php
setHorizontalPos(string $pos, string $rel = 'char')
setHorizontalPosition(string $pos, string $rel = 'page')
setVerticalPos(string $pos, string $rel = 'baseline')
setVerticalPosition(string $pos, string $rel = 'page')
```

Their differing defaults are preserved for compatibility.

### 9.1 Valid semantic values

When `$pos` is a recognized alignment enum:

```text
horizontal: left / center / right
vertical: top / middle / bottom
```

the facade should translate into shared semantic state while retaining the passed/default relation.

### 9.2 Native coordinate-mode tokens

Legacy:

```text
from-left
from-top
```

may remain accepted.

However, those methods do not supply a coordinate.

Therefore they cannot manufacture a meaningful coordinate that was never provided.

If compatible raw constructor state already contains `svg:x/y`, the projector may preserve/use it.

Otherwise the legacy facade should preserve historical raw/native state without inventing an offset.

### 9.3 Invalid percentage values — decision

Decision for 1.0 compatibility:

> **Preserve raw/pass-through behavior on legacy constructor/method paths; reject on the new friendly API; do not guess intent.**

Therefore:

```php
new DrawTextBox(..., ['horizontal-pos' => '50%'])
```

and equivalent legacy setter calls remain callable in 1.0.

They may continue to produce Writer-ineffective/non-native output exactly as characterized.

But:

- documentation must stop recommending them;
- Sample 17 must migrate;
- `setFrameLayout()` and new convenience methods reject them;
- no automatic `50% -> center` or `100% -> right` translation is allowed.

This is the safest backward-compatible boundary.

## 10. Legacy ImageElement `align`

Existing:

```text
left
right
center
absolute
```

remains accepted.

### 10.1 Deterministic mappings

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

These are safe compatibility translations into shared state because they reflect current characterized behavior.

### 10.2 `absolute`

Current `align=absolute` means approximately:

```text
horizontal mode = from-left
horizontal relation = page-content
wrap = none
```

but it may lack an x coordinate.

Decision:

- preserve `align=absolute` as a legacy compatibility input;
- if explicit `svg:x` is present in compatible options, use it as the coordinate;
- if no x coordinate is present, preserve the current legacy raw semantics rather than invent `0cm`;
- do not expose `absolute` in the new friendly API.

## 11. flow-with-text decision

Decision:

> **Compatibility/public method retained; not part of the initial master `setFrameLayout()` vocabulary.**

Reasons:

- `DrawTextBox::flowWithText()` already exists publicly;
- ODF/Writer source supports the property;
- the Writer fixture did not isolate a dedicated object-level UI round-trip case;
- exposing it in the master array is not necessary for the bounded 1.0 geometry core.

Implementation direction:

```text
flowWithText()
    -> shared normalized graphic-layout state
    -> style:flow-with-text
```

It should no longer require a conceptually separate legacy style authority once migrated.

## 12. wrap-influence decision

Decision:

> **Compatibility-only in the 1.0 friendly API.**

Known valid/native compatibility values should be preserved.

Historical `none` must remain raw/pass-through on legacy input until a separate behavior change is approved.

Do not add:

```php
'wrap-influence' => ...
```

to the initial documented `setFrameLayout()` vocabulary.

## 13. allow-overlap decision

Decision:

> **Retain `setAllowOverlap()`; keep it outside the master friendly array; explicitly classify it as LibreOffice-specific.**

It maps to:

```text
loext:allow-overlap
```

and therefore is not portable ODF-core semantics.

The method should still use the same normalized graphic-layout carrier internally after migration.

## 14. Wrap call-order behavior

`setFrameWrap()` owns only the primary `style:wrap` concern.

It does not implicitly clear:

- wrap influence;
- flow-with-text;
- allow-overlap;
- contour properties.

Example:

```php
$box->flowWithText(true);
$box->setFrameWrap('parallel');
```

retains both concerns.

A later master:

```php
$box->setFrameLayout([
    'wrap' => 'none',
]);
```

replaces friendly frame-layout state but compatibility policy fields already authored through explicit public compatibility methods should remain unless the contract chooses to model them as part of the same complete layout replacement.

Recommended contract rule:

> master replacement governs the **friendly core**; explicit compatibility policy state remains independent unless explicitly re-authored.

This avoids surprising:

```php
flowWithText(true)
-> setFrameLayout(['anchor' => 'paragraph'])
-> flowWithText unexpectedly disappears
```

The internal value object may contain both core and compatibility fields, but replacement semantics should distinguish them.

## 15. Appearance interaction

Frame-layout authoring must not clear graphic appearance.

These commute semantically:

```php
$box->setBackground('#eeeeee');
$box->setFrameWrap('parallel');
```

and:

```php
$box->setFrameWrap('parallel');
$box->setBackground('#eeeeee');
```

Both must yield equivalent appearance + layout state.

The final graphic `StyleRequirement` may combine appearance and layout properties into one native style definition.

## 16. Style identity compatibility

Current legacy style names may include:

- geometry;
- anchor;
- position;
- relation;
- wrap;
- appearance.

SR-06 semantic style identity intentionally excluded geometry/placement.

FRAME-LAYOUT changes that boundary because native graphic-layout properties such as position/relation/wrap belong in the same native `style:graphic-properties` carrier as appearance.

Target rule:

```text
graphic style identity
    = appearance properties
    + graphic-layout properties

not:
    object geometry
    anchor
    x/y
    width/height
```

This will change some generated style names.

That is an internal materialization consequence, not a public promise to preserve generated hash names.

Characterization must verify repeated-save stability and deduplication.

## 17. Existing-object mutation versus generated authoring

Generated semantic authoring and mutation of Writer-authored existing frames require different normalization rules.

### Generated authoring

Canonical state:

- clear superseded offsets;
- reject contradictory friendly input;
- emit only intended active geometry/layout.

### Existing object mutation

Preservation-oriented:

- mutate requested concern;
- preserve unrelated Writer-authored attributes;
- preserve inactive companion coordinates when not targeted;
- do not rebuild native structure unnecessarily.

FRAME-LAYOUT-01 should not force a broad existing-frame mutation API if current scope only needs preservation compatibility.

## 18. Insertion capability decision

The materializer must receive semantic insertion information from the element.

Preferred minimal contract:

```text
StructuredInsertionMode
    INLINE_TEXT_FLOW
    BLOCK

StructuredInsertionAware
    structuredInsertionMode(): StructuredInsertionMode
```

For frame-backed elements:

```text
anchor = as-char
    -> INLINE_TEXT_FLOW

other current anchors
    -> BLOCK
```

This is preferred over:

- hard-coding `draw:frame` in the inline-node list;
- making the materializer parse `text:anchor-type` out of generated DOM;
- adding ImageElement-specific branching.

### 18.1 Why an enum/capability is justified

The distinction is real, evidenced, and may apply to future element types.

It also keeps `StructuredElementMaterializer` independent of drawing-object implementation details.

If implementation review shows that a smaller protected hook on `OdtElement` preserves compatibility better, the Change Contract may choose that form, but the semantic contract remains the same.

## 19. DrawTextBox current wrapper behavior

Current `DrawTextBox::toDomNode()` returns:

```text
as-char
    -> bare draw:frame

other anchors
    -> text:p containing draw:frame
```

This behavior interacts awkwardly with `StructuredElementMaterializer`, which itself decides whether to replace a paragraph.

FRAME-LAYOUT should move wrapper/insertion authority toward one coherent boundary.

Preferred direction:

- element emits native drawing object structure;
- materializer owns template-placeholder insertion/container behavior;
- insertion mode comes from element semantics.

However, changing `DrawTextBox::toDomNode()` return shape is observable and must be characterization-gated.

Do not normalize this casually in Slice 1.

## 20. ImageElement lifecycle compatibility

Current ImageElement materialization mutates observable `imageOptions` by writing resolved align/wrap/position values back into state.

Decision:

> Preserve this observable behavior through the migration until a separate lifecycle/API cleanup is explicitly approved.

The new shared semantic state becomes authority for output, but `getImageOptions()` should continue exposing equivalent resolved compatibility values after materialization during FRAME-LAYOUT-01.

Do not mix layout architecture cleanup with public introspection behavior removal.

## 21. `setImage()` compatibility

`OdtTemplate::setImage()` remains callable and retains its public lifecycle.

FRAME-LAYOUT may reuse semantic helpers but must preserve:

- placeholder replacement behavior;
- body/styles processing;
- protected override observability where applicable;
- image resource handling;
- working as-char paragraph containment.

Its historically incorrect wrap child structure may be corrected only behind dedicated characterization and contract language.

Do not rewrite `setImage()` wholesale as an `ImageElement` facade during this milestone.

## 22. Error policy for new friendly API

Use `InvalidArgumentException` for invalid friendly semantic input.

Examples:

- unsupported anchor;
- unsupported alignment;
- unsupported wrap;
- malformed length;
- both alignment and offset in one axis;
- horizontal group on `as-char`;
- invalid friendly relation for axis/anchor.

Use `LogicException` only for state/lifecycle conflicts that are not argument-domain errors.

Legacy compatibility paths remain more permissive where explicitly preserved.

## 23. Characterization gate required before production work

The Change Contract should require tests freezing:

1. all four legacy DrawTextBox position setters and defaults;
2. constructor percentage pass-through;
3. `from-left/from-top` without coordinates;
4. ImageElement `align=left/right/center/absolute`;
5. explicit `svg:x/y` in ImageElement;
6. ImageElement materialization mutation;
7. `flowWithText()`;
8. `setAllowOverlap()`;
9. wrap-influence native and historical values;
10. current DrawTextBox return shape by anchor;
11. current ImageElement as-char body/header failure;
12. setImage as-char body/header visible baseline;
13. graphic style identity/deduplication;
14. repeated save/materialization;
15. body/header document-part parity.

## 24. Final pre-contract decisions

The review resolves the open points as follows.

### Confirmed by user

1. Master API: **`setFrameLayout()`**
2. Nested axis groups: **accepted**
3. Convenience names: **accepted**

### Resolved in this review

4. Friendly relation matrix:
   - conservative axis/anchor-aware subset;
   - broader raw compatibility preserved.

5. `flow-with-text`:
   - retained public compatibility method;
   - shared internal state;
   - **not** in initial master array.

6. `allow-overlap`:
   - retained public method;
   - LibreOffice-specific;
   - **not** in initial master array.

7. Legacy percentage positions:
   - remain raw/pass-through on legacy paths for 1.0;
   - rejected by friendly API;
   - never auto-translated.

8. Internal type:
   - **`DrawingLayout` preferred**.

9. Insertion boundary:
   - semantic capability/mode;
   - **`as-char -> INLINE_TEXT_FLOW`**;
   - materializer must not guess from DOM node name.

## 25. Review verdict

FRAME-LAYOUT-01B is ready for Change Contract drafting.

The resulting architecture is deliberately two-speed:

```text
NEW AUTHORING
    strict
    semantic
    canonical
    Writer/ODF-aligned

LEGACY COMPATIBILITY
    callable
    observable behavior preserved where feasible
    raw invalid historical inputs not reinterpreted
    progressively routed through shared authority where safe
```

The Change Contract should now freeze:

- public signatures;
- exact friendly vocabulary;
- relation validation matrix;
- compatibility facade behavior;
- `DrawingLayout` ownership/projection;
- insertion-mode contract;
- implementation slices and acceptance gates.

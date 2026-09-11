# FRAME-LAYOUT-01 — Final Closeout

Status: COMPLETE / FINAL GO

## 1. Executive summary

FRAME-LAYOUT-01 is complete.

The milestone established one coherent shared frame-layout architecture for supported frame-backed elements instead of continuing the historical split between image and text-box positioning behavior.

Accepted responsibility boundaries:

```text
DrawingLayout
    semantic frame-layout authority

DrawTextBox / ImageElement
    frame-backed producers
    + compatibility facade
    + insertion semantics

StyleRequirement pipeline
    semantic graphic-style materialization

StructuredElementMaterializer
    native-carrier-aware structural insertion
```

## 2. Public API accepted for 1.0

Both `DrawTextBox` and `ImageElement` support the master API `setFrameLayout()` and the approved convenience methods:

- `setFrameAnchor()`;
- `setFrameHorizontalAlignment()`;
- `setFrameVerticalAlignment()`;
- `setFrameHorizontalOffset()`;
- `setFrameVerticalOffset()`;
- `setFrameWrap()`.

The friendly API rejects category-invalid state instead of silently guessing Writer semantics. Alignment and offset are distinct operations.

Legacy DrawTextBox positioning methods remain callable. ImageElement constructor autoscaling and observable `getImageOptions()` compatibility remain preserved.

## 3. Native carrier ownership

FRAME-LAYOUT-01 establishes this split:

```text
draw:frame object attributes
    text:anchor-type
    svg:width
    svg:height
    svg:x
    svg:y

semantic graphic style
    style:horizontal-pos
    style:horizontal-rel
    style:vertical-pos
    style:vertical-rel
    style:wrap
    style:flow-with-text
    draw:wrap-influence-on-position
    loext:allow-overlap
    appearance properties
```

Explicit x/y coordinates become operative through the matching `from-left` / `from-top` positioning modes. Appearance and layout share one semantic graphic-style identity while object geometry remains outside that identity.

## 4. DrawingLayout semantic core

`DrawingLayout` is the immutable shared authority for friendly frame-layout state. It distinguishes anchor, size, horizontal alignment versus offset, horizontal relation, vertical alignment versus offset, vertical relation, and wrap.

Important validation rules:

- width/height are positive absolute lengths;
- offsets may be signed absolute lengths;
- percentages are not accepted as friendly alignment/offset pseudo-values;
- one axis cannot simultaneously own alignment and offset state;
- incompatible anchor/relation changes fail instead of silently rewriting authored intent.

`DrawingLayoutProjector` remains stateless and projects semantic state into native object attributes and graphic-layout properties.

## 5. Producer migrations

`DrawTextBox` and `ImageElement` now share the same frame-layout core while retaining their compatibility surfaces.

Writer validation confirmed paragraph-relative placement, vertical placement, explicit offsets, wrap behavior, and as-character insertion for text boxes. ImageElement retains legacy align behavior when the friendly API is inactive, constructor autoscaling, resource packaging, manifest registration, and repeated materialization stability.

## 6. Structured insertion resolution

FRAME-LAYOUT-01 separated structural insertion from frame style/geometry.

The final insertion modes are:

```text
BLOCK
INLINE_TEXT_FLOW
PRESERVE_TEXT_CONTAINER
```

For `as-char` frames, the containing `text:p` / `text:h` carrier is preserved so the frame participates in text flow. Floating ImageElement insertion preserves the paragraph carrier required by Writer.

When a placeholder is wrapped by `text:span`, generated frame nodes are promoted out of that inline wrapper as needed so that they become valid direct paragraph/heading children while surrounding styled text is preserved.

This works in both `content.xml` body content and page/master-style-owned header content in `styles.xml`.

## 7. GRAPHIC-PART-COMPAT-01 resolution

The earlier discrepancy between body ImageElement insertion, header ImageElement insertion, and the established `setImage()` header path is resolved.

The root cause was structural carrier semantics, not image packaging, manifest registration, a general header prohibition, or a need for a separate header image engine.

```text
generated frame
+ missing or invalid text paragraph carrier
= Writer-invisible result
```

After carrier-aware insertion, LibreOffice renders structured ImageElement content in both body and header contexts.

## 8. Compatibility policy migration

The retained specialized policies `flowWithText()`, `wrap-influence`, and `setAllowOverlap()` remain compatibility APIs but now participate in the same normalized semantic graphic-style requirement path.

They are intentionally not added as friendly master-array keys. Changing primary wrap does not clear flow-with-text, wrap influence, or overlap. Historical values remain pass-through rather than being silently normalized.

## 9. Lifecycle and style identity

Focused integration tests verify semantic graphic-style deduplication, stable style identity for equivalent state, repeated materialization/save stability, and preservation of the legacy carrier path for genuinely unmigrated properties.

FRAME-LAYOUT builds on SR-06 semantic graphic ownership and does not reopen it.

## 10. Public sample and LibreOffice evidence

Sample 27 is the integrated FRAME-LAYOUT showcase. The final visual regression covers left/center/right placement, top/middle/bottom placement, explicit x/y offsets, wrap modes, compatibility policies, as-character DrawTextBox, as-character ImageElement, floating ImageElement, and structured ImageElement content in a page header.

LibreOffice manual inspection confirmed the intended frame behavior. The earlier H1-H9 research fixtures separately captured the transition from invisible structured images to visible body/header images after carrier-aware insertion.

## 11. Final automated preflight

Final validation reported green:

- PublicSampleSmokeTest: 1 test, 199 assertions;
- full PHPUnit suite: 729 tests, 4729 assertions, 0 failures after the final compatibility-preflight correction;
- PHP lint across `src/` and `tests/`: clean;
- `composer validate`: clean;
- `git diff --check`: clean.

The suite still reports PHPUnit deprecations already outside FRAME-LAYOUT scope. They are not FRAME-LAYOUT failures.

Local generated artifacts under `samples/output/`, `research/`, `tmp/`, and LibreOffice lock files remain local regression artifacts and are not production closeout source.

## 12. Acceptance criteria resolution

The FRAME-LAYOUT-01C Change Contract acceptance criteria are satisfied:

- `setFrameLayout()` and approved convenience methods work on both target element types;
- friendly contradictory/category-invalid state is rejected;
- anchor/geometry and graphic layout properties use their accepted native carriers;
- explicit coordinates pair with offset modes;
- legacy DrawTextBox APIs remain callable;
- legacy percentages are not reinterpreted;
- ImageElement align/autoscale and `getImageOptions()` compatibility remain;
- `flowWithText()` and `setAllowOverlap()` remain;
- existing `setImage()` behavior remains functional;
- structured `as-char` ImageElement is Writer-visible and paragraph-contained;
- structured `as-char` DrawTextBox participates in text flow;
- body and header insertion both work;
- floating frame/image insertion retains the required carrier;
- repeated save is stable;
- semantic graphic definitions deduplicate;
- focused tests, full suite, sample smoke, lint, validation, diff checks, and final LibreOffice inspection are green.

## 13. Stop-condition review

No Change Contract stop condition requires reopening architecture.

The shared DrawingLayout model works for both target element types; correct carrier ownership did not require breaking established public APIs; repeated-save stability remains; header/body behavior converges under one insertion model; and CustomShape, broad named-object architecture, and unrelated drawing features were not pulled into the milestone.

## 14. Scope deliberately left outside FRAME-LAYOUT-01

FRAME-LAYOUT-01 is a bounded 1.0 geometry core, not a universal Writer drawing API.

Outside this milestone remain broader Writer positioning options, CustomShape semantics, additional specialized draw elements, broad mutation of arbitrary existing named frames, generalized named-object clone/remove APIs, and post-1.0 FRAME-LAYOUT-02 / IMAGE-LAYOUT-01 extensions.

Future drawing work should build on `DrawingLayout`, semantic graphic requirements, and carrier-aware insertion rather than inventing parallel positioning models.

## 15. Final decision

**FRAME-LAYOUT-01: COMPLETE / FINAL GO**

The next mandatory 1.0 milestone is:

```text
TEMPLATE-RELIABILITY-01
```

The FRAME-LAYOUT architecture is accepted baseline for subsequent work.

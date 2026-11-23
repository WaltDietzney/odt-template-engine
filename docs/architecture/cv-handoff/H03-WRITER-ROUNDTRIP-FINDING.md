# H03 Writer roundtrip characterization — 2026-11-23

## Reproduced

- Using `tests/Fixtures/LegacySamples/templates/sample_textfeld.odt`, `setElement('test1', ...)` inserts a native `draw:custom-shape` inside `text:p / draw:frame / draw:text-box / text:p`.
- H03 `ShapeElement` with bitmap ellipse: source ODT has one native custom shape and `Pictures/WaltDietzney.png`; after headless LibreOffice ODT-to-ODT roundtrip, the custom shape count is zero. The output still has a fill-image declaration but the original bitmap filename is replaced by an internal Writer image name.
- Existing `CircularImageElement` using the *same* template placeholder and Writer roundtrip likewise changes from one custom shape to zero. This is a legacy-path comparison, not evidence that H03 alone caused the loss.
- New H03 PHPUnit packaging and repeat-save tests pass; they do not establish Writer roundtrip compatibility.

## Interpretation and next experiment

The shared failure is consistent with invalid or unsupported nested shape placement in `draw:text-box`, but the root cause is **not proven**. Compare an otherwise identical native shape in a top-level paragraph (using a namespace-preserving fixture), then compare with a Writer-authored reference shape. Do not alter legacy `CircularImageElement` or generic placeholder semantics until this distinction is established and characterized.

## Acceptance gate

H03 is not Writer-roundtrip approved. A passing Writer-native preservation test for the three agreed shapes and their fills, plus visual inspection, is required before completion.

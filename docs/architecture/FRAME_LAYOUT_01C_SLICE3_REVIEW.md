# FRAME-LAYOUT-01C Slice 3 — ImageElement Migration Review

Status: CLOSED / AUTOMATED GATE GREEN / WRITER EVIDENCE CONFIRMS REMAINING SLICE 4 DEFECT

## Scope

Slice 3 migrated `ImageElement` onto the shared FRAME-LAYOUT semantic authority while preserving its compatibility surface.

Implemented and verified:

- `setFrameLayout()` master API;
- shared convenience methods;
- object geometry projected to `draw:frame`;
- position/relation/wrap projected through semantic graphic style requirements;
- constructor autoscaling preserved;
- legacy `align` behavior preserved when the friendly API is not active;
- observable `getImageOptions()` compatibility retained;
- image resource and manifest handling preserved;
- repeated materialization stable.

## Automated gate

The focused Slice 3 suite passed locally after one integration-test selector correction.

The selector issue was not a production defect: the template already contained another frame, and the test initially asserted against the first `draw:frame` in the document rather than the generated image frame.

After targeting the frame by its `draw:image/@xlink:href`, the Slice 3 gate was green.

## Writer research extension

The existing H1-H7 cross-part matrix was extended with:

```text
H8 — Friendly ImageElement, as-char, body
H9 — Friendly ImageElement, as-char, header
```

Both cases use the Slice 3 friendly semantic path:

```php
$image = (new ImageElement($imagePath, [
    'width' => '1cm',
]))->setFrameLayout([
    'anchor' => 'as-char',
    'vertical' => [
        'alignment' => 'top',
        'relative-to' => 'baseline',
    ],
]);
```

## LibreOffice / visual-regression evidence

H8 and H9 both remain visually invisible.

The generated render evidence shows:

- H8 consumes the body placeholder, while the untouched header placeholder remains visible;
- H9 consumes the header placeholder, while the untouched body placeholder remains visible;
- the target image itself is not rendered in either case.

This is the expected result for the architecture boundary between Slice 3 and Slice 4.

Slice 3 corrected the semantic/native carrier model, but it deliberately did not change `StructuredElementMaterializer` insertion/container behavior.

Therefore the remaining failure is no longer attributable to ImageElement style/geometry ownership.

It is consistent with the A0.3 finding:

```text
as-char draw:frame
+ paragraph removed by structured replacement
= Writer-invisible frame
```

## Architectural conclusion

The H8/H9 result strengthens, rather than weakens, the FRAME-LAYOUT decomposition.

The two concerns are now experimentally separated:

```text
Slice 3
    ImageElement semantic layout / native carrier ownership
    -> green

Slice 4
    anchor-sensitive structured insertion / paragraph preservation
    -> still required
```

No ImageElement-specific workaround should be added.

The next behavior change belongs in the generic structured insertion boundary.

## Closeout

No Change Contract stop condition is triggered.

Slice 3 is closed.

Next: Slice 4 — Anchor-sensitive structured insertion.

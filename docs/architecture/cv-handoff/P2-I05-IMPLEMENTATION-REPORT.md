# P2-I05 — Native annular sector integration

Date: 2026-11-25. Status: **implemented and technically verified in the P2 worktree;
new variants visually accepted by the owner on 2026-11-25; interactive Writer editing remains separate**.
CC06; source HEAD 473ea17705288ad431c3960835fafa05ec2d5340, branch
architecture/p2-capability-audit. Pending I01b–I04 changes and accepted research
were preserved. No commit, push, merge, deployment or originalworktree/sample-output
changes. The owner approved the proposed factory: “Ja, machen wir so, bitte umsetzen”.

## API, semantics and reuse

The only production class changed in this slice is ShapeGeometry:

```php
ShapeGeometry::annularSector(float $sweepDegrees, float $innerRadiusRatio): ?ShapeGeometry
```

- Start at the top; increase clockwise. Finite sweep `[0,360]`; finite inner/outer
  radius ratio `(0,1)`. Validate both arguments before handling zero.
- At zero return null, so the consumer omits the progress shape entirely.
- At 360 reuse the existing ring preset path/equations/handles and set its native
  thickness modifier. Existing preset output remains byte-identical to the recorded
  pre-slice metadata, including its default modifier 5400.
- Intermediate sweeps reuse the H06 annular polygon construction: outer points forward,
  inner points reverse, closed radial ends and Z N. The maximum chord span is 3°;
  the true supplied endpoint is explicitly included. There is no percent quantization.
- Eight decimal coordinate precision in the existing 21600-square viewBox. Reject
  nonfinite/out-of-range values and numerically collapsed thicknesses/endpoints.
  Physical visibility of extremely small values still depends on size and renderer.

The private immutable attribute override only supplies the existing full-ring native
modifier; no general public attribute/path API was added. No ring preset, star,
rounded-rectangle, SkillBarGroup or native shape catalog was reimplemented.
ShapeElement, ShapeAppearance, DrawingLayout and DrawingGroup handle serialization,
size, colors, native contour and ordered group/style behavior unchanged. Consumer
factories assemble a full track plus an optional progress shape. Percent/rating
meaning, labels, missing/unscored skill policy and chosen region stay outside the
engine. No new indicator class, cache, resource registry or SVG converter.

## Native evidence

All new evidence is in research/p2-capability-audit/partial-rings/:

| Set | Actual evidence |
| --- | --- |
| showcase | 0/1/70/99/100%, additional 0.1/33.37/70.1%, two sizes/thicknesses and a native stroked contour. Eleven drawing groups, 21 native shapes, no images. |
| lifecycle | Showcase repeated save and reload; page-style/body document repeated save. Five source/lifecycle pages have byte-identical PNGs after Writer. |
| stories | Body/header/footer groups with correct content.xml/styles.xml automatic drawing style references; six native shapes, no images. |
| calibrated probes | 0/1/70/99/100/70.1% on separate pages. At 1800dpi, actual fill/track colors, white center holes and unpainted outside regions verified in both direct and roundtrip PDFs. Sampling beyond 252° distinguishes 70.1% from a wrongly quantized 70%. |

Eleven native documents produce eleven pages before and after Writer. Actual XML is
extracted separately; no output patching or raster substitution is used. Existing
accepted H06 artifacts remain unchanged; start-state hashes protect all prior research.

The six positioned calibration pages are not byte/pixel-identical after Writer:
exactly nine edge pixels differ per page at the inner circle in the 1400px raster
(maximum channel delta 27/255; most cases at most 4). Verification restricts all
changed pixels to that inner-edge band, and independently verifies geometry, paint
coverage, holes and endpoints at 1800dpi. This bounded edge difference is recorded,
not silently called pixel equality. Normal inline consumer/lifecycle pages are exact.

## Checks

- Geometry/composition focused suite: 27 tests, 3048 assertions, no failures.
  Includes finite/range/collapse rejection, exact endpoints, bounded sampling,
  radii, winding/area, open holes, 0/1/n composition, full-preset compatibility,
  actual-part styles and repeat-save/reload.
- PHP 8.5.4 full composer test including public sample smoke: 1200 tests,
  12393 assertions, no failures/errors; existing 1 warning, 4 PHP deprecation
  sources and 8 PHPUnit deprecations remain.
- PHP 8.2.36 full suite: 1200 cases, 12267 assertions, no failures/errors;
  1 warning, 8 PHPUnit deprecations and the existing external Writer test skip
  because soffice is absent from the isolated PHP container. Host Writer checks
  and the native evidence run separately on Writer 26.2.6.3/Poppler.
- Lint, Composer validation, diff check, strict docs build and evidence verifier
  are recorded in preflight.json/logs. No new runtime dependency was introduced.

An initial test-provider naming collision was corrected without a production change.
Initial strict pixel comparisons of the calibration pages were replaced by explicit
edge-delta reporting, not by changing the existing ring preset. Boundary color probes
at 900dpi sampled the antialias transition; 1800dpi resolves the intended interior
samples while retaining exact expected paint colors. These investigation details
are retained to prevent unnecessary repeated diagnosis.

The existing 70% feasibility acceptance is not automatic approval of every new
variant. The owner accepted the presented PDF/native ODT on 2026-11-25: “Perfekt, abgenommen”. Interactive selection,
move/edit and H10 acceptance remain separate. Next planned slice: P2-I06/CC07 consumer
composition, followed by P2-I07/H10. No implicit Git publication approval.


## Owner acceptance — 2026-11-25

P2-I05 visual status: ACCEPTED. Owner statement: “Perfekt, abgenommen”.
Interactive Writer editing remains separate. This does not authorize commit, push, merge or deployment. Next planned slice remains P2-I06/CC07.

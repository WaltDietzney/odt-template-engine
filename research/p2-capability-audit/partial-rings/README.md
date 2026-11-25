# P2-I05 — Bounded annular sector integration

Date: 2026-11-25. Source HEAD 473ea17705288ad431c3960835fafa05ec2d5340
plus pending I01b–I04 work. CC06 and H06-CONSTRUCTION-RECIPE.md are the inputs.
Existing native 70% evidence remains untouched; no new indicator class or ring preset rewrite.

## Approved geometry contract

Owner approved the proposed factory on 2026-11-25 (“Ja, machen wir so, bitte umsetzen”).
ShapeGeometry::annularSector(sweepDegrees, innerRadiusRatio): ?ShapeGeometry.
The consumer converts ratings/percentages to degrees and owns their meaning.
Start is at the top, increasing clockwise. Finite sweep in [0,360], finite inner
radius ratio strictly between zero and one. Validate both arguments before zero handling.
Zero returns null so the consumer omits the progress shape. Full sweep uses the
existing ring preset's geometry/equations/handles, with its native thickness modifier.
Partial sweeps use the proven closed annular polygon: outer arc forward, inner arc
reverse, close with Z N. Three-degree maximum chords do not quantize endpoints;
every supplied endpoint is explicitly included. Coordinates have bounded decimal
precision; inputs collapsing the ring or its endpoint at that precision must fail.
No color, diameter, percent/rating labels, icon semantics or pagination enter geometry.

Existing ShapeElement, ShapeAppearance, DrawingLayout and DrawingGroup provide
appearance, size, ordered grouping and actual body/header/footer style projection.
A consumer composes one background track plus an optional progress shape.
The accepted H06 70% example uses inner radius 7000/10800.

## Implemented evidence

0/1/70/99/100 percent plus non-step-aligned and tiny progress; two thicknesses,
solid/outlined appearances, body and page styles, repeated save/reopen and native
Writer/PDF. Check exact endpoints, contour winding/closure/hole, bounded sampling,
invalid/nonfinite/precision-collapse inputs, unchanged existing ring preset and
actual rendered fill/track colors. Manual owner visual acceptance stays separate.
No commit/push is authorized.


## Reproduction

```sh
php research/p2-capability-audit/partial-rings/generate.php
python3 research/p2-capability-audit/partial-rings/render.py
python3 research/p2-capability-audit/partial-rings/verify.py
vendor/bin/phpunit tests/Elements/AnnularSectorGeometryTest.php tests/Integration/PartialRingCompositionTest.php
composer test
```

Rendering uses isolated Writer profiles and saves sources, Writer ODTs, PDFs and PNGs.
The verifier records five byte-identical inline/lifecycle pages and six calibration
pages with exactly nine changed inner-edge pixels each; it checks visible coverage,
white holes and exact endpoint discrimination separately at 1800dpi. The old ring
preset metadata is unchanged (full-ring-before.json/full-ring-after.json).

A first data-provider version used duplicate rounded string keys; indexed names fixed
that test setup issue. Strict calibration-page pixel equality exposed the edge
normalization delta described above. At 900dpi, a near-endpoint color probe sampled
an antialias transition; the 1800dpi interior probe passes with exact paint colors.
These were verification refinements; the existing ring preset was not rewritten.

PHP 8.2 reproduction uses the I04 test-only image odt-p2-php82-test:local and a read-only
source mount, network disabled and a disposable /tmp copy. Host PHP 8.5 and Writer
perform full/native checks. Interactive editing and new owner visual acceptance are pending.
[Full implementation report](../../../docs/architecture/cv-handoff/P2-I05-IMPLEMENTATION-REPORT.md).

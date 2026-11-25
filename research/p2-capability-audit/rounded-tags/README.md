# H08-R — rounded tags, native feasibility supplement

2026-11-24. Source HEAD 473ea17705288ad431c3960835fafa05ec2d5340 plus retained
uncommitted P2-I01b/I02. Owner explicitly requires rounded auto-growing tags.
Status: TECHNICAL PROBE PASS; OWNER VISUAL ACCEPTANCE RECEIVED 2026-11-24.
No production source or public API was changed by this supplement.

## Reproduce

Uses the P2-I02 field-free flow seed. If needed first run ../flow/prepare.py.

```sh
php generate.php
python3 render.py
php showcase.php
python3 render.py showcase showcase-repeat showcase-reloaded multipage
python3 verify.py
```

Dependencies: PHP DOM/ZIP, repository source/bootstrap, Python, LibreOffice,
Poppler and Liberation fonts. Verified PHP 8.5.4 / Writer 26.2.6.3.

Positive carrier: existing ShapeElement with ShapeGeometry::preset(round-rectangle).
The entire existing geometry/equations/stretch-points/text-area recipe is reused.
No replacement rounding geometry is developed. GrowingRectangle is a research
composition adapter: it projects graphic Auto-Grow/padding/margins and as-char
anchoring. ShapeLabelParagraph projects paragraph/text styles into content
automatic drawing-text scope. ShapeRow is temporary flow composition; native
custom-shapes are not yet admitted by the public addInlineElement frame contract.
These identify bounded P2-I03 integration points, not new promised classes.

showcase: three styles, 18 tags, real Sans/Serif fonts, colors, wrap, native editable
shape text. showcase-repeat/reloaded: lifecycle controls. multipage: 90 tags over
three pages. Six positive lifecycle roundtrip pages are pixel-identical; full text,
font/color evidence and page/overlap checks pass. No new manual GUI or owner
acceptance is implied. XML/PDF/PNG evidence is retained with verification.json.

## Negative controls, retained to avoid repetition

- draw:corner-radius on draw:text-box is valid ODF 1.2 Part 1 19.127/10.4.3,
  but this Writer path ignores it, drops it on save and renders exactly like control.
- Existing native draw:rect retains its radius, but all tested widths stay about
  1cm despite drawing Auto-Grow flags; long labels overflow. Rejected for this flow.
- Common text styles gave incorrect default fonts in drawing text. Their source,
  Writer output, PDF and image are preserved in common-style-font-rejected.
  Content-automatic drawing text styles fix the scoped probe. This is CC04 evidence.
- initial-four-way preserves early source packages/scripts. The historical failed
  hand-built custom path under v01 remains unchanged and was not rerun.

The positive evidence establishes the full existing preset combination, not an
isolated causal claim about one equation or stretch-point. Corner radius remains
the preset's current modifier; arbitrary radius APIs are not newly approved.
Contact User Fields must remain in Writer frames; this plain-text tag result does
not override the previously established field-in-drawing limitation.

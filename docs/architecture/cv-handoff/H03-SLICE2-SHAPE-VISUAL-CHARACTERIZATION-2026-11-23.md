# H03 Slice 2 — Shape visual characterization (2026-11-23)

Status: research finding; not an API approval or implementation change.

## Method

- Isolated, programmatically authored ODT probes using native `draw:rect`, `draw:g`, and `draw:frame`.
- LibreOffice Writer 26.2.6.3 headless ODT roundtrip.
- Repository tool `tools/visual-regression/render.sh` for PDF and 150-DPI PNG output.
- Manual inspection of rendered PNGs. These are not Writer-GUI-authored reference documents.
- Probe sources: `/tmp/h03-shape-probes-v2-20261123/` (ephemeral, not committed).
- Generated render candidates: `tmp/visual-regression/` (not baseline artifacts).

## Observations

1. **Overlay (P1):** Gray background and blue foreground appear visibly layered in the intended order after roundtrip. Initial same-color probe was inconclusive for paint order.
2. **Group (P2):** `draw:g` and its two rectangles plus text frame survived XML roundtrip. Moving the label below the rectangles removed visual collision in the rendered PNG. This does **not** establish that the entire group behaves correctly when dragged/resized interactively in Writer.
3. **Skill bar (P3):** 10 cm gray track, 7 cm blue fill, separate unbordered label `PHP 70%` visibly render as a 70% bar after roundtrip. Initial same-color rectangles visually looked like a full bar; colors and label placement fixed this.
4. Some ODF geometry attributes were normalized by approximately 0.001 cm in the initial roundtrip. XML literal equality is not a suitable universal geometry assertion.

## Architectural implications (provisional)

- Independently editable background and foreground rectangles are sufficient to **render** a simple 0..100 skill bar in this controlled example; do not infer stable behavior across paragraph contexts, repeated bars, or Writer interactive edits yet.
- Distinct graphic styles, explicit shape order, and text placement are necessary presentation contracts.
- Generic grouping API remains out of scope under the H03 Slice 2 draft Change Contract.
- Preserve `tools/visual-regression/render.sh` as a required visual evidence gate alongside XML and ODT roundtrip checks.

## Remaining gates

- Writer-GUI manual edit/move/reopen of grouped shapes and skill bars.
- Multiple bars, zero/full values, varying paragraph styles, anchors, and repeated render/save.
- Writer-authored preset geometry references (rounded rectangle, triangle, star, chevron).
- API decision and Change Contract approval, followed by focused tests and full preflight.

No source implementation or baseline artifact was changed during this characterization.

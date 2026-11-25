# H09 – Accepted graphical heading construction recipe

## Implementation update — P2-I01b

Explicit native heading authoring is now implemented in Paragraph via
setOutlineLevel(1–10 or null). [Implementation report](P2-I01B-IMPLEMENTATION-REPORT.md)
records exact scope and remaining gates. Older construction-API-open statements
below are historical; general drawing composition and fit policy remain separate.

## Current H09 status — closeout, 2026-11-24
**H09 native feasibility/design is complete.** Graphical A/B/C acceptance is
preserved; the bounded semantic probe now demonstrates native body headings
and real PDF outlines without duplicate text. See
[H09 closeout](H09-CLOSEOUT-2026-11-24.md). Historical semantic-open statements
below are superseded by this evidence; production/API integration remains open.


Owner visually accepted p2-v03-fixed.odt on 2026-11-24.
Evidence: fixed.py, integrated.py, grouped.py, generate.py and Writer roundtrip
under this directory. This is an implementation input, not production code.

## Reuse first
Read develop docs/architecture/H03-SHAPE-TEXT-WRITER-REFERENCE.md and
H03_SKILLBAR_CLOSEOUT.md. Reuse ShapeGeometry, ShapeAppearance, DrawingLayout,
ShapeElement, Paragraph and ShapeTextLayout. Inspect SkillBarGroup for native
draw:g composition and style/resource collection in content.xml and styles.xml.
Do not duplicate existing classes or assume SkillBarGroup itself is a generic
group API. Check actual API gaps before proposing extensions.

## Accepted native recipe
One draw:g anchored as-char; children use local coordinates, without individual
text:anchor-type. Text belongs directly to the colored base custom shape,
not an independent transparent overlay. Set draw:text-style-name consistently
with its text:p style. Use foreground run-through where applicable.
Graphic properties: draw:auto-grow-width=false, draw:auto-grow-height=false,
draw:textarea-horizontal-align=left, draw:textarea-vertical-align=middle,
fo:padding-left/right=0.25cm. Prototype uses 15pt bold white text.
Enhanced geometry viewBox 0 0 21600 21600; text-areas 0 0 19000 21600
for the tested bands. Derive safe text areas for other contours.

## Exact accepted geometry
All base bands: x=0 y=0 width=12cm height=0.9cm.
A: M 0 0 L 21600 0 21600 21600 0 21600 Z N
B: M 0 0 L 21600 0 20400 10800 21600 21600 0 21600 Z N
Fold: x=0 y=0.9 width=0.55 height=0.35cm;
M 0 0 L 21600 0 21600 21600 Z N
C: M 0 0 L 19800 0 21600 21600 0 21600 Z N
Accent: x=11.2 y=0 width=1.25 height=0.9cm;
M 0 0 L 4320 0 21600 21600 17280 21600 Z N
Colors: base #375e80, fold #203a52, accent #87accb.
C uses equal physical slope of 1cm per 0.9cm height, gap 0.2cm.
These are reference dimensions, not mandatory product defaults.

## Evidence and limits
Writer roundtrip preserves base width 12.001cm and height 0.9cm;
relative child positions preserved within 0.001cm. Owner accepted visible
text, fold and accent. Combined property intervention, not isolated causality.
Semantic text:h behavior, interactive group editing, overlong text policy,
automatic group resizing and generic anchor API support remain open.
Do not claim these are solved. No new public class/API approved.
Implement via existing engine paths, no post-save XML patch; test both XML
parts, repeated render/save and Writer visual regression.

## Scope clarification at H07 closeout
2026-11-24: graphical A/B/C headings remain owner-accepted and complete as native
visual feasibility. The separate semantic heading/outline concern does not
reopen their shape/design acceptance. Review existing authored text:h handling
and current engine capabilities before planning any narrowly scoped work.
No new semantic-heading investigation or implementation was started by this
H07 closeout, and no semantic support is newly claimed.

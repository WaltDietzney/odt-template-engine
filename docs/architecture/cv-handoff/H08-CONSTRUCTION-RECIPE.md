# H08 – Inline tag construction recipe

## Required rounded-tag supplement — 2026-11-24

Owner explicitly requires rounded auto-growing tags. The existing round-rectangle
preset now has positive native evidence; [H08-R proof and recipe](H08-ROUNDED-TAGS-NATIVE-PROOF.md).
Owner visually accepted the presented proof on 2026-11-24; engine integration remains pending. Older “optional/deferred” statements below are
historical; they do not remove this new required integration scope. Rectangular
acceptance remains unchanged; the failed hand-built path remains rejected.


Aktueller Status 24.11.2026: native Machbarkeit/Gestaltung im dokumentierten Umfang abgenommen. [Konsolidierung](P2-CONSOLIDATION-2026-11-24.md) trennt verbleibende Engine-Integration und optionale Grenzen; ältere offene Abnahmevermerke sind historisch.

Native auto-grow visually accepted; padding and rounded corners not yet accepted.
Evidence: ../../../research/p2-capability-audit/v01/extend.py, finish.py,
p2-v01-native.odt, p2-v01-final.odt, p2-v01-padding.odt.
Reuse DrawTextBox, Paragraph/RichText, DrawingLayout and inline insertion paths.
- draw:frame with draw:text-box, as-char anchor, initial width 1cm height 0.5cm.
- Content automatic graphic style P2NativeAuto copied from E011 Box, no Frame
  parent: solid #dce8f2, no stroke, fo:padding=0.08cm, draw:auto-grow-width=true,
  draw:auto-grow-height=true, style:vertical-pos=middle, style:vertical-rel=char.
  Writer roundtrip widths 0.999/2.073/1.25/3.389/3.601cm for five labels.
- Explicit spaces between atomic frames; final 15-tag candidate wraps over
  three lines, roundtrip pixelidentical; final human gate open.
- Padding candidates 0.15 and 0.25cm, configurable padding required by owner.
  Rounded corners optional. Existing ShapeGeometry round-rectangle preset and SkillBarGroup rounded geometry are established H03 capabilities; reuse them. Only the combination with editable auto-growing as-char tags remains unproven. Do not develop rounding geometry again.
Legacy engine baseline clips text; semantic graphic properties omit auto-grow
keys. Multiple style differences in successful candidate, cause not isolated.
Paragraph currently serializes text before embedded elements; ordered inline
composition requires contract. Open: overwide tags, page breaks, header/styles
part, spacing, padding visual gate, rounded corners, repeated save.
No new public API approved.

## Rounded auto-grow probe
v01/closeout.py uses existing native round-rectangle geometry direction with
as-char anchor, auto-grow and 0.15cm padding. This particular custom path/text
area combination failed: Writer roundtrip widths 2.488, 46.511, 203.021,
243.506cm; PDF text breaks vertically. Reject this candidate, not rounded
geometry generally. Optional rounding deferred to H08 contract; rectangular
native text frames remain baseline. Human padding acceptance still required.

## Owner visual feedback – inline padding
Wrapping screenshot shows text directly against colored frame edges. Visible inner
padding not accepted; inter-frame spacing is not padding. Inspect Writer graphic
and paragraph style interaction before claiming support. Separate 0.15/0.25cm
padding comparison still requires verification. P2-V01 remains open.

## Writer reference and explicit padding
Owner edited WrapTag3 in p2-v01-padding.odt; preserved as
p2-v01-padding-writer-reference.odt. Writer emits four fo:padding-* attributes
(0.152cm each), not shorthand. Isolated replacement in p2-v01-padding-explicit.odt
reproduces width 3.693cm; owner visually accepts padding. Adjacent frames touch
in initial unspaced row. padding-spacing.py adds explicit native margins
(left/right 0.08cm, top/bottom 0.06cm); visual acceptance pending.

## Owner acceptance – rectangular inline tags
2026-11-24: owner visually accepted p2-v01-padding-spacing.odt.
Use explicit fo:padding-top/bottom/left/right (0.152cm reference) and
fo:margin-left/right (0.08cm), fo:margin-top/bottom (0.06cm).
Writer roundtrip retains padding and margins (horizontal 0.079cm).
Native as-char text frames with auto-grow remain the accepted carrier.
These are reference dimensions, not mandatory defaults. Optional rounded
auto-grow combination remains deferred; existing rounding geometry is reusable.
Multipage/overwide and engine lifecycle tests remain H08 implementation gates.

# H07 composition review after owner correction

Status: read-only source review and research correction, not a new API decision.
Owner requested review before further contact construction. No prototype or
production source was changed during this review.

## Sources actually reviewed
- src/Elements/SkillBarGroup.php
- src/Elements/ShapeElement.php, ShapeTextLayout.php, Paragraph.php
- docs/architecture/H03_SKILLBAR_CLOSEOUT.md
- docs/architecture/cv-handoff/H09-CONSTRUCTION-RECIPE.md
- research/p2-capability-audit/v03/fixed.py
- Current native/generate.py contact composition
- CV Generator Pro: VISUAL-TEMPLATE-DESIGNER-PLAN-2026-10-28.md,
  VTD-01-GROBRASTER-MAPPING-UND-DESIGNSTUDIEN-2026-10-29.md,
  research/engine-handoff/DOE-DESIGNLABOR.md and ENGINE-ANFORDERUNGSLISTE.md

## Findings and correction
SkillBarGroup assembles editable text shapes and rounded native rectangles in
one draw:g, removes individual child anchors, and supplies cross-part styles.
Font family/size/color/weight, label gap, skill gap and geometry are already
configurable. Its one-line label-height approximation and calculated fixed
vertical pitch are not evidence for variable-length contact layout.
The accepted H09 native prototype groups the text-bearing base shape and its
additional shapes. It fixes size explicitly; automatic group resizing and
long-label behavior remain open in its own recipe.
The contact prototype reused draw:g only for icon plus backing. Contact text
and row composition were instead assigned to paragraphs/tables. Thus the
owner's requested complete drawing composition was not actually compared
against the chosen flow carriers. No evidence currently justifies rejecting
that drawing composition, or treating tables as the intended solution.
CV VTD explicitly targets visual-reference reconstruction in editable ODT,
separates semantics/slots/components, and requires behavior-based carrier
selection and visual comparison. The historical E005 table candidate is not
a later owner decision requiring tables.

## Next bounded evidence question
Compare a complete contact drawing composition (symbol, optional native backing,
label and native User Field text) using the established group/shape mechanisms.
Investigate field survival in the actual drawing-text carrier separately from
Writer text frames. Prove text wrapping, actual row/group height and movement
of following entries; do not infer them from SkillBarGroup or fixed H09 geometry.
Keep local icon geometry separate from dynamic row flow. An as-char group per
entry is a candidate to test, not an approved solution. Likewise, a whole-block
group may require measurement/recomposition; Writer auto-layout is unproven.
If this fails, preserve exact evidence before discussing alternate carriers.
No separate Contact class, public API change or forced font shrink is approved.
Owner feedback also requires compact configurable typography/spacing,
proportion-preserving icons, and full-width horizontal alignment with left,
center and right blocks and icons above or alongside their values.
Previous technical roundtrips remain valid, but do not establish acceptance of
the contact construction strategy or successful reference-image reconstruction.

## Existing capability inventory — source/docs/tests review
No new rendering experiment or test rerun was needed for this inventory.
API documentation: docs/api-reference/structured-content/native-shapes.md and
skillbars.md; catalog contract: H03-SLICE2-SHAPE-CATALOG-CHANGE-CONTRACT.md.
Its later Package 1 acceptance explicitly records owner review of 22 ODTs.
Do not repeat that catalog acceptance because earlier draft sections say open.

| Existing building block | Reuse for contact composition | Existing evidence |
| --- | --- | --- |
| ShapeGeometry::preset | rectangle, ellipse, rounded rectangle and other native backings/contours | H03NativeShapeTest; accepted catalog |
| ShapeAppearance | solid/transparent/bitmap fills, none/solid stroke | H03ShapeOdtIntegrationTest; bitmap/style/manifest checks |
| ShapeElement::addParagraph | editable text inside the drawing shape | H03NativeShapeTest::testOptionalParagraphIsEmittedBeforeGeometryAndOwnedForStyles; H03ShapeOdtIntegrationTest::testNativeShapeTextStylesArePackaged |
| Paragraph text styles | font family, size, color, bold and paragraph alignment | native-shapes.md; SkillBarGroup typography integration test |
| ShapeTextLayout | independent internal horizontal/vertical alignment, per-side padding | H03NativeShapeTest::testTextLayoutProjectsVerticalAlignmentAndPadding |
| DrawingLayout / projector | dimensions, anchor, offsets, relative placement, wrap | FrameLayout01CSlice1DrawingLayoutTest; existing frame-layout closeout |
| DrawTextBox | native frame/text container, nested OdtElement content, shared frame layout, background | FrameLayout01CSlice2DrawTextBoxIntegrationTest; semantic producer contracts |
| RoundedRectElement | native rounded rectangles with existing size/radius/color | SkillBarGroup integration tests |
| SkillBarGroup | working draw:g assembly, child ownership, local coordinates and cross-part descendant styles | H03SkillBarGroupIntegrationTest; accepted H03 closeout |
| ImageElement | existing image component and asset ownership, SVG findings already recorded | existing producer tests plus V04 SVG evidence |

Boundaries from actual code: ShapeAppearance exposes only none/solid stroke,
not arbitrary contour color/width parameters; ShapeElement requires explicit
width/height and paragraph anchor. ShapeGeometry accepts catalog presets,
not arbitrary new contours through a public constructor. SkillBarGroup is a
specialized composition example, not an existing generic group public API.
These limits do not invalidate the proven geometry/text/grouping capabilities.
Native User Field construction in the chosen paragraph/carrier remains distinct
from the established setUserField binding API. Only concrete integration gaps
should drive further work; the shape catalog, text styling, alignment, grouping,
resource packaging and accepted Writer evidence are established starting points.

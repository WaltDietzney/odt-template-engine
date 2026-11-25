# P2-A — Capability audit H05–H09

## Aktueller Abschluss — 25.11.2026

P2-I01–I07 einschließlich H10 sind im vereinbarten Umfang umgesetzt und abgenommen. Beide Review-v2-Templates sind visuell akzeptiert; der Owner bestätigt die interaktive Writer-Prüfung. Versionierung/Veröffentlichung bleiben separat offen. Verbindlicher Status: [P2-Implementierungsabschluss](P2-CLOSEOUT-2026-11-25.md) und [maschinenlesbarer Stand](P2-CLOSEOUT-STATE.json). Offene Folgepunkte werden dort ausdrücklich aufgeführt.

### Historische Planung und Fortschreibung

Die nachfolgenden älteren Status- und Arbeitsaufträge sind historische Quellen; aktuelle Arbeit wird vom oben verlinkten Abschluss aus gewählt.


## Aktueller Abschlussstand — 24.11.2026

Diese Untersuchungshistorie wird durch die [P2-Konsolidierung](P2-CONSOLIDATION-2026-11-24.md) zusammengeführt. H05–H09 sind als Machbarkeit/Gestaltung abgenommen; Implementierungslücken bleiben ausdrücklich getrennt.

## Current H09 status — closeout, 2026-11-24
**H09 native feasibility/design is complete.** Graphical A/B/C acceptance is
preserved; the bounded semantic probe now demonstrates native body headings
and real PDF outlines without duplicate text. See
[H09 closeout](H09-CLOSEOUT-2026-11-24.md). Historical semantic-open statements
below are superseded by this evidence; production/API integration remains open.


## Current status — H07 owner closeout, 2026-11-24
**H07 native feasibility/design is accepted and closed in its bounded scope.**
See [H07 closeout](H07-CLOSEOUT-2026-11-24.md) for accepted timeline/contact
variants, authoritative evidence, limits and implementation handoff.
Earlier pending-owner statements below are historical and superseded for this
accepted scope. Engine integration/public API and H10 remain separate.

2026-11-24; Forgejo develop f8679a7. Read-only production audit; draft for owner review.
No implementation or API approval. Source/test tree verified equal to develop.

## Sources
CV research/engine-handoff/UEBERGABE-AUFTRAEGE.md, ENGINE-ANFORDERUNGSLISTE.md,
E007-MERIDIAN-ANFORDERUNG.md, E011-BEFUND.md, E011-ODF-EVIDENCE.xml;
LOCAL-039/generate-timeline.php and local E009/E011/LOCAL-039 artifact inventory.
Engine H01_SECTION_AUTHORING_CLOSEOUT, H03_SKILLBAR_CLOSEOUT, H04 contract,
FUTURE_DEVELOPMENT and source/test paths below. Original design images and backup
provenance not yet verified. Existing Writer evidence is not a new visual acceptance.

## Findings
| ID | Evidence | Implication |
|---|---|---|
| F01 | ShapeElement constructor requires paragraph anchor and fixed dimensions | Other anchors require evidence, not blanket relaxation |
| F02 | DrawingLayout::fromArray whitelist excludes z-index; projector has no z-index; DrawTextBox and CircularImageElement emit 0, SkillBarGroup 4 | Shared ordering semantics candidate |
| F03 | FrameImageReplacementService::updateFrame updates direct draw:image children and optional dimensions | Bitmap fill replacement is distinct; preserve legacy named replacement |
| F04 | SkillBarGroup::toDomNode emits draw:g and removes child anchors | Specialized grouping exists, no generic group API established |
| F05 | DrawTextBox semanticGraphicProperties excludes draw:auto-grow-width/height; StyleMapper passes raw keys to legacy path | Legacy capability candidate, semantic support not established |
| F06 | Paragraph::toDomNode creates text:p; H01 explicitly documents styled paragraph, not native heading | Preserve authored text:h or investigate native authoring |
| F07 | Paragraph serializes parts before embeddedElements | Interleaving cannot be assumed |
| F08 | ShapeElement requirements target content.xml; SkillBarGroup adds styles-part descendant definitions | Cross-part ownership audit before generalization |

## H05 — photo and overlay
Reuse ImageElement, CircularImageElement, ShapeAppearance::bitmap, DrawingLayout,
FillImageRequirement and existing resource collection. ImageElement constructor
derives missing dimension from intrinsic ratio, but both dimensions are accepted
as supplied; default 5x3cm. Named replacement has separate legacy defaults
documented in FUTURE_DEVELOPMENT IMAGE-LAYOUT-01. Do not conflate them.
CUSTOM-SHAPE-FILL-IMAGE-REPLACEMENT-01 explicitly separates bitmap fills from
direct image replacement. Investigate actual Writer fill/crop, shared fill
isolation, manifest, same-basename resources, portrait/landscape/square,
overlay ordering and long content across pages. No new photo class approved.
Tests: H03ShapeOdtIntegrationTest, NamedImageReplacementArch05GTest,
CircularImageElementSemanticGraphicProducerTest, FrameLayout01CSlice3ImageElementIntegrationTest.
## H06 — indicators
SkillBarGroup accepted for integer 0–100, rounded native track/fill, editable
labels, group-wide styling, sidebar insertion. ShapeGeometry includes star5,
ring, round-rectangle. Ring preset has fixed modifiers; partial progress not
established. Group uses fixed calculated positions and approximate one-line
label height; long labels need evidence. Test stars/segments with existing
shapes first; ring progress requires native geometry evidence. Quantization
and rating meaning belong to consumer; unscored soft skills are application policy.
Tests: H03NativeShapeTest, H03SkillBarGroupIntegrationTest (zero/full, tiny
radius, typography, invalid values), H03ShapeOdtIntegrationTest.
## H07 — contact and timeline
Reuse RichTable, RichTableCell borders/padding, Paragraph/RichText, native user
fields, SectionElement and collection services. LOCAL-039 generator already
uses three columns, glyph marker and left cell border; this is a candidate
not proof of alternating timeline or page continuity. H04 resolves generated
column identity in content part, not all cross-part issues. H01 proves 0/1/3
stations and nested section lifecycle. Test optional contact rows, long address,
0/1/n stations, long split row, axis per page, alternating chronological reading
order, mapping and Writer editing. Avoid absolute y positions for variable rows.
Tests: H01SectionAuthoringClosureTest, H04TableColumnIdentityCharacterizationTest,
NestedSectionInstantiationTest, TemplateAuthoring01C2UserFieldBindingTest,
TableLayout01CSlice2RowHeightTest, TableLayout01CSlice3VerticalCellAlignmentTest.
## H08 — inline tags
E011 exact carrier draw:frame/draw:text-box, as-char, initial 1cm x 0.5cm,
draw:auto-grow-width/height true, padding 0.08cm, vertical middle relative char.
DrawTextBox already emits same carrier and INLINE_TEXT_FLOW. Raw auto-grow keys
survive legacy mapping but are absent from semantic requirements. Do not infer
package or Writer support from mapper alone. Existing inline placeholder
insertion preserves body/header paragraphs and surrounding text; investigate
multiple placeholders with spaces before changing Paragraph. Overwide tags,
rounding, baseline relation, page transitions and styles part need Writer proof.
Tests: FrameLayout01CSlice4AnchorSensitiveInsertionTest,
FrameLayout01CSlice2DrawTextBoxIntegrationTest.
## H09 — graphical headings
ShapeElement text, native geometry, DrawTextBox and paragraph styles are available.
Paragraph with Heading 1 emits text:p; native text:h authoring not established.
Existing template processing recognizes text:h. Prefer authored semantic heading
plus decoration, investigate native authoring only if required. Verify outline,
navigation, reading order, long labels, contrast, page flow and no duplicated text.
Depends on H05 ordering; no dedicated ribbon class approved.

## Reproduced probes (no source changes)
PHP standalone: Paragraph('Heading 1')->addText('A')->addElement(inline box)
->addText('B') serializes text:p with AB followed by frame, not A-frame-B.
DrawTextBox with raw auto-grow keys: empty semantic requirements, legacy map
contains both keys. ShapeElement with as-char throws constructor exception.
DrawingLayout with z-index throws unsupported key exception.
Focused PHPUnit on H03NativeShape, H03ShapeOdtIntegration, H03SkillBarGroup,
FrameLayout01CSlice4AnchorSensitiveInsertion, NamedImageReplacementArch05G,
H01SectionAuthoringClosure, H04TableColumnIdentityCharacterization,
TemplateAuthoring01C2UserFieldBinding: 64 tests, 931 assertions, zero failures,
1 deprecation, 3 PHPUnit deprecations (PHP 8.5.4).

## Proposed decision order
1. H05: shared ordering, image fitting and resource replacement boundaries.
2. H06: composition with existing shapes; only proven gaps in progress geometry.
3. H07: flow-driven contact/timeline prototypes and mapping.
4. H08: legacy auto-grow package/Writer probe and ordered inline composition.
5. H09: semantic heading plus decoration, reuse H05 findings.
Cross-cutting: anchor/ordering, group ownership, content/styles placement,
dynamic sizing and flow, reading order. Do not introduce generic abstractions
before two concrete use cases demonstrate a common contract.

## Remaining gates
Original reference images/provenance and backup verification; exact ODF/Writer
probes for photo fitting, ordering, partial ring, timeline pagination, inline
auto-grow packaging and heading semantics. These are evidence tasks, not
approved implementation. H10 remains separate integration proof.
Documentation discrepancy: cv-handoff/README.md still labels H04-B not approved
despite merged accepted contract; correct during next authorized docs slice.

## P2-V01 experimental closeout
Artifacts in /tmp/p2-a-audit/v01: generate.php, extend.py, finish.py,
engine/native/final ODTs and Writer roundtrips. Engine baseline visual review:
overlap, stars, segments and full ring accepted; fixed-width tags rejected.
Native variant visual review: reversed explicit z-index, 70% polygon annulus,
image geometry and auto-grow accepted by user. E011 graphic style copied into
content automatic styles without Frame parent; multiple differences, root cause
not isolated. Writer widths 0.999/2.073/1.25/3.389/3.601 cm from 1cm.
Final variant adds 15 spaced inline tags wrapping across three lines and
independent bitmap fill declarations/resources with different aspect ratios.
This demonstrates native resource isolation candidate, not Engine replacement API
or actual mutation of a previously saved document. Final visual human gate open.
Final Writer roundtrip PNG SHA256 identical:
caf5de666f3269d4019de6e4a0864f23bc6bead7ab6d433f2187220181d6e920.
No production changes. Remaining edge cases (overwide tags, page transitions,
0/100 ring, actual bitmap replacement lifecycle) deferred to H05/H06/H08 contracts.

### H08 owner decision
Configurable inner padding is required; rounded corners are an optional design
capability, subject to native editable text, automatic sizing and atomic wrap.
Padding candidates 0.15/0.25cm in v01/p2-v01-padding.odt; visual verification
pending. Rounded corners not yet proven. No API or implementation approved.
Proceed to P2-V02; retain pending visual evidence explicitly.

## P2-V02 section candidate
Owner rejects table approach. Native drawing connectors retained but fixed
frames overlap; not accepted. Two-column section separator plus paragraph
anchored native circle/square/diamond visually accepted by owner. PDF vector
centers agree at 297.30pt. Offset 8.30cm is calibrated to this page layout,
not a generic relative-position contract. Alternating-flow candidate uses
12 sections, alternating content/date columns, station 6 has 45 extra
paragraphs. Four PDF pages; all station labels and final extra paragraph
present. Human visual review of pagination and alternating layout pending.

### P2-V02 owner acceptance and H07 direction
Owner visually accepted centered linear and alternating section timelines,
including the multipage candidate. Four alternating-flow PNGs were identical
after Writer roundtrip. Nested named sections survive XML roundtrip but
visually interrupt the column flow and span the available width; rejected.
Owner explicitly accepts script-driven timeline composition as an exception
to template-structure-first authoring for this CV-specific use case.
Use native two-column sections, separator axis and anchored drawing markers;
no tables. Reuse template styles where applicable. Generic positioning,
styling contracts and component ownership (engine vs CV generator) remain
implementation design questions. No new public API approved.
P2-V02 visual feasibility accepted; implementation not started.

## P2-V03 H03 reuse and fixed geometry
Reopened develop H03-SHAPE-TEXT-WRITER-REFERENCE.md, H03_SKILLBAR_CLOSEOUT.md,
ShapeElement and ShapeTextLayout. Existing text-style-name, foreground wrap,
horizontal/vertical alignment and padding support must be reused. Existing
preset text areas are evidence, not a general public text-area setter.
Integrated prototype shrinks 12cm bands to 4.047/9.699/3.813cm and height
0.9 to 0.611cm after Writer roundtrip. Fixed candidate adds explicit false
auto-grow width/height, text-style-name, left alignment, bilateral padding
and text-areas. Roundtrip preserves all three widths at 12.001cm and height
0.9cm; fold/accent relative positions preserved within 0.001cm.
Combined intervention, individual causal contribution not isolated.
v03/fixed.py and p2-v03-fixed.odt; visual acceptance pending.
Semantic heading and auto-sized group remain open. No production changes.

### P2-V03 owner acceptance
Owner visually accepted fixed-size A/B/C graphical headings on 2026-11-24.
Implementation recipe: v03/H09-CONSTRUCTION-RECIPE.md, including exact native
geometry, text properties, H03 reuse map and remaining evidence boundaries.
Preserve recipe and generator chain in durable architecture documentation
before implementation. No production changes or new public API approved.

## Durable construction recipe index
Research evidence is preserved under ../../../research/p2-capability-audit/.
Implementation inputs: H05-CONSTRUCTION-RECIPE.md, H06-CONSTRUCTION-RECIPE.md,
H07-CONSTRUCTION-RECIPE.md, H08-CONSTRUCTION-RECIPE.md, H09-CONSTRUCTION-RECIPE.md.
Later owner acceptance entries supersede earlier pending gates only where explicit.
Recipes distinguish accepted native evidence from open engine contracts.

## P2-V01 closeout review
Rounded custom-shape auto-grow probe preserved in research/v01/closeout.py
and p2-v01-closeout.odt, roundtrip and rendered PDF/PNG. Failed sizing;
optional rounding deferred to H08, not a blocker for rectangular tags.
Final image/bitmap and padding human review remains open. Do not mark
P2-V01 fully accepted until owner reviews p2-v01-final.odt and
p2-v01-padding.odt. No production changes.

## Owner visual feedback – inline padding
Wrapping screenshot shows text directly against colored frame edges. Visible inner
padding not accepted; inter-frame spacing is not padding. Inspect Writer graphic
and paragraph style interaction before claiming support. Separate 0.15/0.25cm
padding comparison still requires verification. P2-V01 remains open.

## Owner acceptance – rectangular inline tags
2026-11-24: owner visually accepted p2-v01-padding-spacing.odt.
Use explicit fo:padding-top/bottom/left/right (0.152cm reference) and
fo:margin-left/right (0.08cm), fo:margin-top/bottom (0.06cm).
Writer roundtrip retains padding and margins (horizontal 0.079cm).
Native as-char text frames with auto-grow remain the accepted carrier.
These are reference dimensions, not mandatory defaults. Optional rounded
auto-grow combination remains deferred; existing rounding geometry is reusable.
Multipage/overwide and engine lifecycle tests remain H08 implementation gates.

## Owner acceptance – bitmap fills and P2-V01 closeout
2026-11-24: owner confirmed prior visual acceptance of independent bitmap
fills. Together with rectangular inline tag padding/spacing acceptance,
P2-V01 visual feasibility is closed. This supersedes earlier pending visual
gates. Actual bitmap replacement lifecycle, crop semantics, optional rounded
auto-grow tags and remaining edge cases belong to H05/H06/H08 contracts.
No production implementation or new API approved.

## Owner correction – contact authoring contract
Contact values must exclusively use native Writer User Fields, including
address components, phone, mobile, email and website. No classic template
variables or classic conditional markers in the contact template.
The initial V04 classic-condition prototype is historical partial evidence,
not an accepted construction recipe. Symbols must be independently variable.
Investigate native conditional paragraph visibility including anchored icons;
distinguish hiding from actual removal and assess retained data. No new API
or production change approved.

## H07 contact continuation
See H07-CONTACT-COMPOSITION-DESIGN.md for the detailed design, reuse map,
symbol library/SVG investigation, native User Field contract, dynamic sidebar
flow, visual options and slices A–F. Contact feasibility remains open.

Continuation entry point: P2-WORK-HANDOFF-2026-11-24.md.

## Native contact feasibility — 2026-11-24 continuation
V04 A–F research now lives in repository evidence file `research/p2-capability-audit/v04/native/README.md`.
Four native-field visual directions, dynamic rows, long values, structural omission,
SVG families/custom artwork, resources, actual engine binding/replacement, and
Writer/PDF roundtrips are available. Owner visual acceptance remains pending.
31 documents / 36 PNG pages passed structure and roundtrip comparison; the
intentional capacity-overflow candidate is explicitly rejected (website/profile
missing from PDF). No engine implementation or public API has been approved.
PHP 8.5.4 recognizes these SVGs through getimagesize; mapped SVG preflight
rejects the same recognized SVG because the expected-MIME switch lacks SVG.
This supersedes the assumption that getimagesize always fails on SVG on Patil.
PHP 8.2 compatibility remains unproven. Existing field binding does not create
fields or remove rows. Hidden native paragraphs retain XML/resources.
Use construction-time omission for these prototypes; future authored-field
present-to-empty recomposition and overflow policies need explicit contracts.

## Compact composition continuation — 2026-11-24
The current class-based research implementation is recorded in repository file
`research/p2-capability-audit/v04/composition/README.md`. It reuses the existing
Shape/Image/Paragraph/DrawTextBox classes, with ContactItem, ContactBlock and
ContactStrip as research composition responsibilities. Style variants include
compact, uniform circle/rounded-rectangle sidebar, labeled serif/color and
full-width horizontal above/inline symbols. No tables/classic contact syntax.
Targeted valid-XML Writer evidence: native User Fields disappear in Shape text
and draw:g (including grouped DrawTextBox), but survive in Writer text frames.
Thus only icon/backing use draw:g; the complete contact uses a Writer frame.
Minimum-height authoring and drawing-context style placement need bounded
existing-component integration contracts. No production API is approved.
Source shape/catalog/skillbar evidence is reused rather than reopened.
Visual owner acceptance and later implementation/preflight remain separate.

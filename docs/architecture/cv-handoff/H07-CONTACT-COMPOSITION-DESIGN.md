# H07 – Native contact composition: design and investigation contract

## Current status — H07 owner closeout, 2026-11-24
**H07 native feasibility/design is accepted and closed in its bounded scope.**
See [H07 closeout](H07-CLOSEOUT-2026-11-24.md) for accepted timeline/contact
variants, authoritative evidence, limits and implementation handoff.
Earlier pending-owner statements below are historical and superseded for this
accepted scope. Engine integration/public API and H10 remain separate.

Date: 2026-11-24. Status: design direction discussed with owner; implementation
and native visual feasibility still open. Supersedes the classic-variable
contact approach in the initial V04 evidence.

## Purpose and ownership
Create reusable graphical contact compositions for professional CVs, including
narrow sidebar text frames and horizontal contact areas. Analogous to skillbars
and graphical headings, but dynamic text flow is essential. Separate applicant
data, composition, icon resources and visual styles. The CV generator owns
contact semantics and icon selection; generic ODT capabilities belong in the
engine. Exact class boundaries and public API remain undecided. Do not change
VTD-00 or applicant schema. No classic template variables or classic conditional
markers in contact compositions.

## Data and lifecycle
Use native text:user-field-decl and text:user-field-get for all contact values:
name where needed, street, house number, postal code, city, optional country,
phone, mobile, email, website and further profiles as applicable. These are
illustrative field roles, not a new schema. Existing OdtTemplate::setUserField
updates authoritative string declarations across content.xml/styles.xml;
Writer reevaluates cached display text. Preserve this contract, including
inspection, load, repeated binding/render/save and body/header references.
Existing tests: TemplateAuthoring01C0/C1/C2/C3 UserField integration tests.
Inspect creation of new declarations/references separately from binding
existing authored fields; do not assume a construction API already exists.
Test native fields inside graphical text, nested frames and sidebar content.
No plain text substitution as a substitute for native field references.

## Composition
One reusable construction principle with configurable variants, not a separate
class per design. A contact block contains zero or more rows/items; each has
an optional icon, optional decorative backing, optional label and one or more
native field references. Sidebar is an outer native text frame. The row must
grow with its content, and subsequent rows must follow actual Writer flow.
Do not copy SkillBarGroup fixed-coordinate row stepping for variable text.
Candidate carriers: flowing paragraphs with hanging indent and inline icon,
two-column borderless table with dynamic row height, nested native frames or
drawing groups where Writer proves adequate flow. Native draw:g is a candidate,
not a requirement. Timeline table rejection is specific to timeline.
Compare editable content, reading order, width, clipping, frame overflow,
pagination, and compatibility with existing H02 frame composition. Do not
assume arbitrary nested frames/groups can grow or split.

## Visual variants
A: classic vertical list, icon left, text right.
B: colored sidebar, icon on circular or rectangular backing, contrasting text.
C: horizontal contact strip, icon above or beside each item, wrapping policy.
D: editorial list, optional small uppercase label above value.
Test left/right/above icon placement where supported, top/middle/bottom
alignment, icon size and color, stroke width, backing shape (none, circle,
ellipse, square, rectangle, rounded rectangle), fill/stroke, padding, gap,
row spacing, paragraph/text style, label style, optional separators, contrast.
All are design dimensions, not approved API options or mandatory defaults.
Long text must wrap in available width, including unbroken email/URL;
continuation lines align with text, not icon. No overlap, hidden data or
unexpected empty space. Define policy for overwide tokens and frame overflow.
Zero/one/many items, mixed address parts, absent phone/mobile/website and
all-empty cases. Remove whole unused row including icon, label and separator.
Native hidden paragraph is an experiment only; distinguish hiding from
structural omission and inspect retained field declarations, cached text and
resources. For generated compositions, omitting absent rows at construction
time is a candidate; preserve native fields for included values.

## Symbol resources
Separate semantic role (address, phone, mobile, email, web, profile) from
chosen icon and visual treatment. Support different symbols for same role,
consistent families and custom assets. Candidate upstream libraries: Lucide
(ISC), Tabler (MIT), Bootstrap Icons (MIT), Material Design Icons (Apache 2.0),
Font Awesome (asset-specific license). Verify actual version and license
before vendoring; preserve notices. Prefer a small pinned local subset for
prototypes, no runtime network dependency. SVG preferred for vector quality,
recoloring and scaling; PNG fallback only if evidence requires. No symbol
font dependency as default. Keep icon artwork separate from native ODF
background shapes where feasible; compare precomposed SVG as alternative.
Icon selection belongs to CV generator, generic image handling to engine.
Inspect untrusted SVG resource handling (external references/scripts), package
manifest, deduplication, dimensions/viewBox, color/stroke customization,
replacement, repeated save and cross-part references.
Observed source: OdtPackage recognizes image/svg+xml; ImageElement constructor
uses getimagesize, which does not normally support SVG. Named replacement
exists (replaceImageByName), but SVG support is unproven; inspect
FrameImageReplacementPreflightValidator and all image paths. Do not infer
end-to-end support from MIME mapping. Native ODF custom shapes are another
candidate for simple icons; avoid building a general SVG-to-ODF converter
without a demonstrated need.

## Reuse map
H02 frame/layout services; H03 ShapeGeometry, ShapeAppearance, ShapeTextLayout,
DrawingLayout/Projector, ShapeElement, SkillBarGroup composition pattern;
H04 table column identities if table carrier proves suitable; Paragraph,
RichText, ImageElement, SectionElement, existing style and resource services.
Paragraph currently emits text parts before embedded elements: verify mixed
inline ordering rather than assuming icon-first works. Inspect exact current
source and tests before changes. No duplicate classes or production XML
post-save patching. Research XML prototypes are legitimate evidence only.

## Investigation slices
A: SVG resource insertion/replacement, two icon families, manifest and Writer
roundtrip; characterize existing PHP limitations.
B: native User Fields for every contact value, inside sidebar and graphical
text, binding and reevaluation across parts.
C: vertical contact composition, dynamic height and long text; compare carriers.
D: graphical sidebar with native backing shapes, multiple design variants.
E: horizontal contact strip with responsive native flow or explicit policy.
F: optional rows, all-empty, no orphan icons/labels/separators, retained-data
inspection; distinguish generated omission from authored native visibility.
For each: baseline, exact XML recipe, source reuse, gaps, reproducible scripts,
ODT/PDF/PNG and roundtrip, objective text/geometry checks, owner visual gate.
Store in research/p2-capability-audit/v04/ (separate subdirectories as useful).
No production implementation before semantics and change contract approval.

## Existing evidence and limits
Initial V04 full/minimal/long documents used native customer field only and
classic contact variables/conditions. All three Writer roundtrip PNGs matched;
body/header customer reevaluated and long text wrapped at normal page width.
Bare ordinary string condition returned null from FILTER_VALIDATE_BOOLEAN and
caused TypeError; explicit flags worked. Owner rejected classic syntax for
contact composition. Preserve as historical characterization, not accepted
solution. No SVG/contact sidebar/fully native field composition tested yet.
Interactive chat design study illustrated options only, not ODT evidence.
No visual acceptance of contact area, no new production code/API approved.

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

## Owner clarification and source review — 2026-11-24
The owner reiterates complete graphical composition analogous to SkillBarGroup
and accepted H09 headings, aimed at CV Generator Pro visual-reference matching.
The current V04 prototype groups only icons/backings, not complete contact items;
its technical roundtrip evidence does not settle the requested composition.
See research/p2-capability-audit/v04/native/COMPOSITION-REVIEW-2026-11-24.md.
Evaluate full drawing composition before selecting table/paragraph alternatives.
Native User Fields, dynamic text, compact adjustable typography/spacing and
absence without orphan objects remain required. No new API is decided here.

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

# P2 — Bounded change contracts for implementation

Date: 2026-11-24. Baseline f8679a782a23b82ca2b9a0536d7affff6554301c.
Status: **consolidated implementation contracts for review, not API or merge approval**.
The accepted semantics/visual evidence in the [P2 consolidation](P2-CONSOLIDATION-2026-11-24.md)
are inputs. Class/method spellings not already implemented remain undecided.

## Rules shared by every contract

- Extend existing services/elements; no competing document model or global mutable registry.
- Characterize existing behavior before changing it. Separate extraction/refactoring from
  changed semantics. Preserve facade dispatch and externally overridable protected methods.
- Keep authored content/styles authoritative. Generated identities/resources are document-local.
- Explicit definition/reference/mutation and document-part ownership; no silent style overwrite.
- Generated operations must fail without partial mutation at the existing snapshot boundary.
- Repeated render/save, save/reload, content.xml and supported styles.xml regions are in scope.
- Writer owns final flow/pagination. No automatic font shrinking, hidden duplicates or truncation.
- No post-save XML surgery as the production implementation strategy.
- Research names and CV aesthetic presets are not automatically public engine APIs.
- No change to VTD-00/applicant schema, unrelated STYLE/TEMPLATE milestones or master.
- Sample outputs and owner evidence remain untouched; no lock files staged.
- Tests and manual Writer gates are separate. Blocked checks remain explicitly open.

## CC01 — Bounded native string User Field construction


Evidence: H07 composition UserFieldParagraph/seed authoring and existing C0–C3
field tests. Reuse UserFieldAnalyzer, UserFieldBinder, document context,
inspection and semantic element ownership.

Required behavior:
- Programmatically construct a native string declaration and native references without
  classic scalar/conditional expressions for contact data.
- Bind through the existing authoritative declaration contract; fields are document-global,
  never made foreach-item-local merely by containment.
- Define explicit reuse/collision behavior: compatible existing declarations may be reused
  only under an explicit rule; conflicting values/types/ambiguous authored definitions fail
  before mutation. Do not silently replace unrelated authored fields.
- Multiple references to one logical field are allowed. Each reference resolves after reload.
- Keep reference content native. An optional display cache is not a substitute for a field.
  Empty-cache authoring is valid; any cache support must deliberately address appendBody's
  current text-node validation rather than broadly admitting arbitrary nodes.
- Removing an optional generated contact does not authorize removal of a declaration still
  referenced elsewhere. Consumer selection/recomposition and document-global field lifetime differ.
- Native construction and setUserField binding remain distinct responsibilities.

Do not add numeric/date fields, new condition grammars or implicit applicant mappings.
Tests: declaration/ref identity, repeated refs, conflicts/rollback, body/header/footer,
construct→bind→save→Writer→reload, complete omission, and existing C0–C3 compatibility.
API/ownership decision needed before coding: declaration registration belongs to document
ownership; reference serialization belongs to native inline content. Avoid a parallel binder.

## CC02 — Native heading authoring, independent of visual style names

Evidence: H09 semantic control and three lifecycle documents, plus H01's explicit
styled-paragraph boundary and existing authored-text:h processing.

Required behavior:
- Preserve default Paragraph output as text:p, including a paragraph styled Heading 1.
- Provide an explicit bounded route to author a native text:h with a Writer-supported
  outline level. Proposed supported range is 1–10; validate before mutation.
- Prefer extending the existing paragraph representation/serializer when it remains clear.
  A new Heading class is not assumed or approved.
- Preserve current public signatures/subclass dispatch and authored text:h handling.
- For semantic decorated headings, visible text occurs once in the body heading;
  drawing decoration is textless. Shape labels remain a legitimate graphical-only recipe.
- Keep paragraph alignment/font/flow and drawing geometry responsibilities separate.
  No generic placement formula is inferred from the probe's calibrated offset.

Tests: default text:p characterization, explicit heading semantics, style-name independence,
binding/inline insertion, outline levels, repeat-save/reload, no duplicate labels, real PDF
outlines and Writer render. PDF/UA and arbitrary overlong-heading fitting are not claimed.

## CC03 — Ordered inline content and bounded Writer text-frame sizing

Evidence: Paragraph parts-before-embeddedElements; H07 FlowTextBox; accepted H08
rectangular as-char frames, explicit per-side padding and margins.

Required behavior:
- Introduce an explicit ordered-composition route for text, fields, images and inline
  frames. Existing addElement/text ordering is observable legacy behavior and must not
  silently change. Choose an opt-in or compatible representation after characterization.
- Reuse existing Paragraph/RichText/materialization and anchor-sensitive insertion logic.
  Preserve surrounding body/header text and native text:h carriers.
- Distinguish fixed size, Writer-frame minimum/maximum bounds and drawing-text Auto-Grow.
  A Writer text frame needs appropriate draw:text-box carrier bounds; setting a drawing
  graphic Auto-Grow property alone is not an equivalent contract.
- Extend DrawTextBox's existing semantic path for the proven properties and per-side
  padding/margins. Preserve existing legacy defaults and raw-option compatibility.
- Fixed H09 bands stay fixed; flow-driven contact frames grow. Neither behavior silently
  replaces the other. Explicit available width remains a caller responsibility.
- No rounded Auto-Grow tag requirement is introduced.

Tests: A–image/frame/field–B in order, unchanged legacy ordering, body/styles parts,
as-char versus block insertion, single/multiline text, explicit min/fixed sizes,
per-side spacing, repeat-save and Writer wrapping. Overwide content needs an explicit
consumer fallback/rejection policy, never silent shrink/clipping.
Implementation steps must separate ordering from carrier-bound support.

## CC04 — Drawing composition, style context and paint order

Evidence: SkillBarGroup/H03, accepted H09 groups and H07 icon/backing groups.
Reuse DrawingLayout/Projector, ShapeElement, OdtElement ownedElements,
StyleRequirement/StyleContext and current resource collectors.

Required behavior:
- Correct local child geometry and exactly one group anchor; do not model draw:g as
  a text frame by blindly applying its svg:x/y placement rules.
- Propagate effective graphic/text requirements to the actual drawing/document context.
  A Writer image frame's common style is not automatically sufficient for drawing import.
- Preserve standalone ShapeElement/ImageElement style semantics. New context projection
  belongs in a shared existing materialization/collection boundary, not mutable duplicate
  caches inside every consumer class.
- Model foreground/background and within-group paint order explicitly for the proven cases.
  Numeric z-index preservation through Writer is not itself the success criterion;
  actual visible order and resource/editability preservation are.
- Native field-bearing Writer text remains outside drawing text/groups. Reject unsupported
  newly generated compositions explicitly; do not reinterpret or strip existing authored data.
- A generic grouping public API is not automatically required. Decide whether a small neutral
  element or internal composition collaborator genuinely reduces the three proven patterns.

Tests: icon/backing visibility after reopen, accepted bands/skillbars, body/header scope,
equal-definition reuse, authored-style conflict isolation, resource collection, repeated
save/reload and actual rendered layer order. Native GUI selection/move remains a human gate.

## CC05 — Graphic resources and replacement consistency

Evidence: OdtPackage SVG MIME support; PHP 8.5.4 SVG getimagesize recognition;
mapped SVG preflight rejects that recognized SVG; named replacement and native bitmap
evidence are distinct paths. Reuse ImageElement, OdtPackage, existing replacement
payload/preflight/services and FillImageRequirement.

Three bounded steps, reviewed separately:
1. Characterize/fix the specific SVG recognition inconsistency without unrelated image API changes.
2. Define shared bounded image-info/resource handling for the required SVG cases, with the
   declared minimum PHP 8.2 and current host runtime in the validation matrix.
3. Implement isolated bitmap-fill replacement separately from direct draw:image replacement.

Invariants:
- Explicit dimensions remain explicit; intrinsic ratio inference is validated for non-square
  SVG/viewBox and raster inputs. No stretching caused by symbol backing dimensions.
- Correct manifest MIME and package-local references, identical-byte reuse where applicable,
  and different content with the same basename cannot accidentally overwrite another asset.
- Replacement of one consumer must not mutate other consumers of a shared resource unless
  explicitly requested. Fill declarations and direct image children have distinct ownership.
- Preserve existing replaceImageByName defaults and protected facade/service compatibility
  unless a separate approved behavior contract changes them.
- No runtime icon-library dependency or CV semantic selection inside the engine.
- Recognizing SVG XML is not safe processing. Define a bounded local static input policy,
  deny external references, scripts/event handlers/foreign content and unsafe XML expansion,
  and bound size/complexity. Preserve upstream notices in the consumer's asset pipeline.
- Namespace-prefix normalization is not assumed for arbitrary SVG. The current Writer evidence
  establishes a reliable default-SVG-namespace subset; other cases require explicit handling.
- Writer may add PNG fallbacks/rename resources; effective rendering/references matter.

Tests: PNG/JPEG regression, SVG on PHP 8.2 and 8.5, missing/explicit dimensions,
viewBox ratio, MIME/preflight consistency, same-basename/dedup, direct versus bitmap
replacement, shared-resource isolation, both parts, repeated save/reload, Writer/PDF.
Contain/cover/crop is a separate bounded decision: current crop candidate is not proven.
No universal image-fit redesign is included without a concrete integration dependency.

## CC06 — Existing geometry reuse and bounded progress-ring completion

Evidence: accepted stars/segments/full ring and native polygonal 70% annulus.
Reuse existing shape presets/appearance/layout. SkillBarGroup remains specialized.

Required behavior:
- Do not reimplement stars, rounded rectangles, rings or the accepted shape catalog.
- If partial-ring support is needed, choose a bounded annulus factory or a minimally justified
  geometry extension after API review. No general SVG-to-ODF converter/path parser/chart engine.
- Include exact progress endpoints; the research 3-degree polygon step is not a rounding rule
  for arbitrary values. Define 0% (no progress fill), 100%, tiny segments and invalid geometry.
- Consumer owns percent/rating/quantization meaning and unscored soft-skill policy.
- Styles/resources/group handling use CC04, not another independent registry.

Tests: 0/1/70/99/100 and invalid values, closure/contour geometry, compatible full ring,
repeat-save, body/styles context and native Writer rendering.
A dedicated public indicator class is not assumed merely because a research case exists.

## CC07 — Assemble consumer compositions without promoting research wholesale

Dependencies: the relevant CC01–CC06 primitives. Reuse accepted recipes as fixtures,
not a new general investigation.

Consumer responsibilities:
- ContactItem/Block/Strip semantics, field selection, label/icon family, design presets,
  explicit widths, image-reference interpretation and DOE remain in CV Generator Pro.
  A reusable neutral composition abstraction may be evaluated; role-specific engine
  classes and automatic adoption of research adapters are not approved.
- Contacts use native fields only. Missing items are omitted at construction/recomposition,
  including labels/decorations. Current horizontal scope is 1–3 items; above-icon mode is
  unbacked. Section indents constrain the outer region; caller supplies matching width.
- Timelines use accepted Sections/separators/markers, including alternation. Compute
  placement from the chosen region/column geometry; 8.30cm is a fixture calibration.
  Validate 0/1/n, mixed-length entries, long split content and chronological reading order.
- Rectangular tags use CC03; preserve atomic wrap and explicit per-side spacing.
- Semantic headings use CC02 with the CC04 textless decoration recipe; graphical shape
  text remains available when document outline semantics are not needed.
- Rendering checks reject missing/overlapping/clipped content. Engine validates structure
  and declared bounds; it cannot infer Writer's physical capacity without render feedback.

No general frame-content mutation API, automatic contact-data garbage collection,
arbitrary responsive layout or engine-side pagination is implied.
Acceptance: synthetic short/normal/long and empty profiles, required region widths,
native ODT editing gate, complete PDFs and accepted visual comparisons.

## CC08 — H10 integration and release-facing preflight

Start only after the actually required bounded capabilities are integrated.
Use S01b and selected LOCAL-039/reference combinations, not every purchased design.
Pin engine/renderer/fonts/input profiles and distinguish source and Writer-finalized ODT.

Required evidence:
- native fields, semantic headings, selected graphics, tags and timelines compose in
  first/continuation page layouts; 0/1/n and more than one page;
- no unresolved expressions, orphan decorations, missing values, hidden duplicate text,
  ambiguous style identities, resource collisions or silent clipping;
- repeat render/save, reopen/inspect/bind, consumer layout ownership and readable order;
- focused/integration tests, PublicSampleSmokeTest, full composer test, PHP lint,
  composer validate where relevant, git diff --check and strict docs build;
- real Writer open/save/edit and owner visual acceptance, recorded independently.

Block on unsupported required runtime, uncertain API/ownership, failed checks, missing
reference rights/provenance needed for distribution, or unresolved physical data loss.
Do not turn a blocked check into PASS or expand H10 into deployment/release automation.
Only approved feature/architecture branches target develop; master remains conservative.

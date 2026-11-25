# P2-I03/I03R — Drawing composition and rounded tags

Date: 2026-11-24. Status: **implemented in the P2 worktree; automated and
headless Writer/PDF checks passed; interactive Writer editing gate remains open**.
Branch `architecture/p2-capability-audit`, source HEAD
`473ea17705288ad431c3960835fafa05ec2d5340`. Previously pending I01b/I02 work and
accepted H08-R evidence were retained. No commit/push/merge/deployment performed.
Contracts: CC04 and CC09; no repetition of the accepted native feasibility work.

## Result and public API

- Existing `ShapeElement` accepts `as-char` in addition to its previous paragraph
  anchor, and exposes opt-in `setTextAutoGrow(bool $width = true, bool $height = true)`.
  The existing complete `round-rectangle` preset supplies rounded tags; no new
  geometry or RoundedTag class was added. Text fonts/colors stay Paragraph-owned;
  padding stays ShapeTextLayout-owned, outside margins DrawingLayout-owned.
- `DrawingLayout::fromArray()` adds explicit `run-through` foreground/background,
  nonnegative integer `z-index`, and nonnegative per-side `margin`. Immutable layout
  updates preserve these values; inconsistent layer/wrap combinations fail.
- `Paragraph::addInlineElement()` admits native as-char shapes/groups;
  `addDrawing()` admits positioned paragraph/character drawing carriers in ordered
  parts. Legacy addElement ordering and protected facade dispatch remain intact.
- One small neutral `DrawingGroup(name, children, layout?)` replaces the H07
  research group adapter. Existing SkillBarGroup is specialized to indicator bars;
  it cannot represent arbitrary icon/backing and heading composition without
  importing skill semantics. Shape/image/nested-group ownership uses the existing
  resource/style collectors. ContactItem/Block/Strip remain consumer code.

Group bounds derive from child-local geometry. A group has one anchor, not child
anchors, and never emits frame-only svg:x/width/height. Explicit group sizes and
nonzero horizontal group offsets fail. Ordered children receive ordinal paint
order, overriding their standalone indexes. Paragraph and as-char group anchors
are supported; this does not claim arbitrary page/character group positioning.

## Shared styles, identity and compatibility

StyleRequirementCollector projects owned drawing graphic/paragraph/text definitions
into automatic styles in the actual content.xml or styles.xml context. Materialization
boundaries rebind only newly serialized subtrees. Generated style identities have
isolated `drawing_c_`/`drawing_s_` prefixes. Ordinary standalone shape/image style
behavior is characterized and preserved. Drawing images use the Graphics parent;
ordinary Writer images retain Frame. Group style identity is separate from image
identity, even when graphic property maps coincide.

An authored definition conflicting with an isolated generated identity fails at
the existing package snapshot boundary without overwriting source XML. Reuse of
identical definitions, repeated save and use of one element in body/header pass.
The collision comparison is confined to these new projected definitions; it is
not a global style-policy refactor. No extra document-global mutable projection
cache or consumer-specific serializer was added.

Projection covers owned definitions, not universal inheritance flattening of arbitrary
named Writer styles. Verified drawing recipes use explicit owned text/paragraph
properties. Fixed standalone graphics retain their old behavior. Native User Fields
remain in Writer text frames outside drawing text/groups: newly generated unsafe
shape text and grouped Writer frames fail explicitly. Existing authored package
XML is not stripped or reinterpreted. The original field-loss negative control
remains at v04/composition/carrier.odt.

## Actual evidence

All new proof output is under `research/p2-capability-audit/drawing-integration/`:

| Set | Evidence |
| --- | --- |
| rounded | Production APIs only; no research serializers. Four lifecycle documents, six PDF pages; 18 tags in three font/color designs and 90 tags across three pages. All source/roundtrip PNG pages pixel-identical to accepted H08-R references. Complete PDF labels, font/color, geometry and no off-page/overlapping words verified. |
| contacts/composition | Ten variants/lifecycle documents, ten pages. DrawingParagraph/DrawingGroup/ForegroundImage research adapters removed. Native User Fields, all omitted/empty cases, undistorted icons, resources and repeat-save retained. Every page pixel-identical to accepted H07/I02 references before and after Writer. Long content moves subsequent text by 56.4pt. |
| stories | Foreground/background visibility, group child order versus independent object z-index, and Serif 11pt white rounded text in header/body/footer. Two documents/two pages, including a grouped icon/backing in the header; roundtrip pixels identical. Actual raster probes: group overlap blue, independent overlap red; overlapping text has 0 dark pixels in foreground and 757 in background. |
| regressions | Accepted graphical and semantic H09 headings and H08 rectangular tags reopened/rendered/saved without regenerating sources. All three accepted visuals and canonical content XML preserved; roundtrip pixels identical. Historical graphical PNG uses 150dpi, so its comparison explicitly uses matching resolution. |

`verify.py` reproduces the checks, including hashes of protected pre-existing research
artifacts. XML extracts preserve selected actual source/roundtrip structures. Existing
research negatives and previously accepted source ODTs were not modified.

## Checks and remaining gates

- Focused DrawingCompositionTest: 9 tests, 50 assertions, no failures.
- Full composer test, including PublicSampleSmokeTest: 1142 tests, 9266 assertions,
  no failures/errors. Existing one warning, four PHP deprecation sources and eight
  PHPUnit deprecations remain; this is not a warning-free claim.
- PHP lint: all 393 src/tests files pass. Composer validation and git diff --check
  pass. Strict Zensical docs build exits 0 with “No issues found”. Accompanying
  preflight files record the results.
- Actual runtime: PHP 8.5.4, Writer 26.2.6.3, Poppler. PHP 8.2 is not installed on
  Patil; minimum-runtime verification remains open, particularly for I04 SVG work.
- Owner accepted H08-R visuals on 2026-11-24. The implementation reproduces those
  pixels; this is distinct from interactive selection/move/edit verification.
  No interactive Writer editing or final H10 owner gate is claimed here.
- Physical overflow, region width, contact selection and design presets remain
  consumer responsibilities. Neither font shrinking nor engine-side pagination
  was introduced. SVG safe-input/resource replacement work remains P2-I04.

Next implementation slice: P2-I04/CC05, starting with the already identified SVG
preflight inconsistency. No implicit commit/push or release approval follows from
this implementation report.

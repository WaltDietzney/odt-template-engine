# H07 — compact drawing compositions using existing engine elements

2026-11-24. Baseline f8679a782a23b82ca2b9a0536d7affff6554301c.
Functional research implementation for owner visual review. No production src/
change, no new approved public API, no commit/push/merge. Supersedes the earlier
table-based contact candidates as the preferred current review set.

## Scope and class responsibilities

ContactComposition.php contains three research composition classes:
- ContactItem: one self-contained native text-frame object with icon,
  optional independent native backing shape, optional label and User Fields.
- ContactBlock: vertical flow, background, outer padding and inter-item gap.
- ContactStrip: full content width with one to three columns; item alignment
  left/center/right for three, left/right for two, left for one.

Visual families are configurations of these classes, not one new engine class
per aesthetic variation. Field meanings and selection stay outside the generic
composition: generate.php supplies already selected field tokens, labels and
graphic resources. There is no Phone/Address/Email engine-specific class.

Existing engine implementation supplies:
ShapeElement, ShapeGeometry::preset, ShapeAppearance, ShapeTextLayout,
DrawingLayout, ImageElement, DrawTextBox, Paragraph, style/resource collection,
OdtTemplate::appendBody, setUserField and save.
No tables and no classic template variables or conditions are used.
No XML transplant or patch is applied after engine save.

BuildingBlocks.php contains small research adapters for concrete integration gaps:
- UserFieldParagraph: constructs empty-cache native field references, while
  Paragraph still owns paragraph/text styling. Declarations are authored in
  the seed template and bound through the existing setUserField API.
- FlowTextBox: uses DrawTextBox serialization and adds fo:min-height to its
  native draw:text-box. This makes the Writer frame height a minimum, not a
  clipped fixed box. draw:auto-grow-height alone was insufficient here.
- ForegroundImage: reuses ImageElement, supplies foreground run-through and
  projects the image graphic requirement into content automatic styles with
  Graphics inheritance for use inside a drawing group. Common Writer-frame
  graphic styles alone were insufficient in the drawing importer.
- DrawingGroup: uses the SkillBarGroup child-ownership/grouping pattern for
  the icon plus independent native backing. It also supplies the targeted
  carrier control, where fields inside the group are deliberately rejected.

These adapters are implementation/contract candidates, not four new promised
engine APIs. Consolidation into appropriate existing responsibilities must be
reviewed before production implementation. Existing protected/public methods
and source classes remain unchanged.

## Actual carrier result — not a repeated shape investigation

carrier.php composes four native field references using the existing classes:
1. ShapeElement text;
2. ShapeElement text within draw:g;
3. DrawTextBox within draw:g;
4. ungrouped Writer DrawTextBox.
Writer preserves only reference 4. The other three disappear on save and do not
show the value in PDF. This is checked against well-formed input XML with all
required namespace declarations; it is not an inference from a broken fixture.
Existing shape geometry/grouping remains proven and was not re-investigated.

Therefore a complete field-bearing contact uses a native Writer text-frame
container, with actual native Shapes and ImageElements composed inside it.
The container is a drawing/frame object, but **not draw:g**. Native field
preservation takes precedence over pretending a drawing group supports fields.
Interactive joint selection/move/edit in Writer still needs owner review.

## Variants

| File | Demonstration |
| --- | --- |
| compact.odt | Compact unbacked icons, sans serif, 8.5pt |
| sidebar-round.odt | Dark background, white text, uniform circular native shapes |
| sidebar-rounded.odt | Uniform rounded rectangle shapes, second SVG family |
| editorial.odt | Optional labels, serif font, independently colored text/icons |
| strip-above.odt | Entire 18cm content width, centered icon above each entry |
| strip-inline.odt | Entire content width, icon left, left/center/right blocks |
| long.odt | Multiline long address and URL, later items move in native flow |
| rebound.odt | Reload, changed native fields, repeated render/save, longer data |
| minimal.odt | Only email: no orphan symbols, labels or other field declarations |
| empty.odt | No contact object, icon or contact declaration at all |

Horizontal item widths are consumer-provided layout values within equal
distribution columns, so the left, center and right blocks can have different
physical widths. This allows the top icon to center within the configured item
rather than always at the full page-column center. The item width is a layout
parameter; automatic typographic measurement/tight fitting is not claimed.
The total strip leaves 0.015cm rounding allowance to prevent three inline
frames wrapping due to Writer unit normalization.

Configurable examples: block/item width and padding, font family and size,
text and label colors, label size/gap, symbol size, backing size/shape/fill,
symbol-to-text gap, row gap, text alignment and icon placement.
Icon dimensions stay square, independent of the backing. There is no ellipse
that stretches the phone artwork. The selected SVGs have a square 24-unit viewBox.

## Deliberate bounds

- One to three items per horizontal strip. More items require explicit
  consumer row distribution; no automatic arbitrary responsive layout.
- Above-icon mode currently supports unbacked icons only.
- Text inside native drawing groups/shapes cannot be substituted with plain
  strings: native User Fields remain mandatory.
- No automatic font shrinking, truncation, guessed text height or hidden data.
- Page-height overflow and automatic linked-frame pagination are not solved.
  The earlier explicit overflow rejection remains applicable.
- Changing a field to empty does not structurally remove its existing item;
  the consumer must recompose the selected items.
- This demonstrates parameterized native composition, not a measured match to
  a specific CV reference image. That belongs to the next consumer design study.
- PHP 8.2 SVG handling and the previously found mapped SVG-preflight defect
  remain separate production gates; this host runs PHP 8.5.4.

## Reproduce

Run these in order from this directory (all other paths are script-relative):

```sh
python3 seed.py
php carrier.php
php generate.php
php lifecycle.php
python3 render.py carrier
python3 render.py
python3 verify.py
```

seed.py reuses the preserved native fixture authoring helper and adds all ODF
namespace declarations for the existing engine's legacy prefixed DOM emitters.
generate.php prunes unused declarations **before** opening the source template
with the engine. All visible values are then bound with setUserField.
References use an empty cache; Writer evaluates the authoritative declaration.
The appendBody validator currently rejects character-data caches directly
inside text:user-field-get, so general cached-field construction is not claimed.

No network or new package installation is needed. SVG inputs and licenses are
the pinned local Lucide/Tabler subset in ../native/assets. Derived colored SVGs
are generated locally with the default SVG namespace. Full license notices
remain inside every ODT. No external SVG or hostile graphic is processed.

## Evidence and failure retention

PDFs/PNGs, Writer saves, repeat saves, specs and machine checks are retained
here. verification.json records native field preservation/completeness,
absence of tables/classic markers, image proportions, manifest coverage,
repeat-save stability, PNG roundtrip comparisons, and measured vertical
movement of the following control paragraph for long data.
Existing focused capability tests: 44 tests, 528 assertions, zero failures;
three PHPUnit deprecations. See phpunit-existing-capabilities.txt.

failed-scaffold/ preserves the malformed minimal fixture attempt, including
the missing-prefix issue; final fixtures parse cleanly before rendering.
failed-z-order/ preserves the disappearing phone icon after Writer save.
Explicit local z-order and a common foreground image style alone did not fix
it. The successful combination uses an icon/backing DrawingGroup, explicit
child order, and automatic content graphic styles for both group and drawing
image. Local placement belongs to the children (as in SkillBarGroup), not to
an assumed frame-like svg:x translation on draw:g. Inline group placement
expanded line metrics; paragraph-anchored icon groups keep the text compact.
Field text remains outside the group, in the enclosing native Writer frame.
These changes were combined; no claim that every individual contribution has
been isolated is made. The successful final native structures and recipes are
reproducible through the PHP source in this directory.
The existing shape catalog/accepted headings/skillbar visual gates were not
repeated, and no sample output was changed.
Owner visual acceptance, native GUI editing and public API contracts remain open.

## Final technical result
10 review documents / 10 pages: native fields and authoritative values survive,
all PDF values are complete, repeated saves have identical XML/manifest, all
source-to-Writer-roundtrip PNG pages are byte-identical. Long content moves the
following control paragraph downward by 56.4pt. Representative final Writer-derived pages were also
visually inspected, including both sidebar shapes, long content and strips; XML/PDF text alone
cannot establish visible artwork. Strict Zensical build: no issues found.
No manual owner visual/native-editing acceptance is implied by these checks.

## Owner feedback — section wrapper for horizontal placement
2026-11-24: owner positively reviewed the compact composition and proposed
wrapping the full-width, above-icon contact strip in a Section with left/right
indents to adjust its overall placement. Preserve this as a consumer composition
pattern: Section owns the available outer region; ContactStrip owns distribution
of entries within its configured width. Reuse existing Section construction and
style capabilities. Current ContactStrip width is explicit and must be supplied
consistently with the indented region; automatic width inference/percentage
resolution is not implemented or claimed. No new indent/Section/shape research,
public API, commit, push or production approval follows from this feedback.

## Owner closeout — 2026-11-24
Owner explicitly requested H07 closure after reviewing the compact variants.
Bounded native feasibility/design is accepted; earlier pending visual-gate
statements are superseded for that scope. Production integration, native GUI
interaction checks and API contracts remain separate. Canonical record:
docs/architecture/cv-handoff/H07-CLOSEOUT-2026-11-24.md.

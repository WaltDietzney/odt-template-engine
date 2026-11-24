# H03 – Native shape text: Writer reference findings

Status: implementation slice under review; not a final visual acceptance.

## Evidence

Manual Writer reference: `/tmp/h03-three-text-layouts-writer-reference.odt`
Baseline: `/tmp/h03-three-text-layouts.odt`

The Writer-edited reference was rendered with `tools/visual-regression/render.sh`.
The reference contains three `draw:custom-shape` elements with editable `text:p`
content. Writer persisted these properties:

| Label | `style:horizontal-pos` | `draw:textarea-vertical-align` | `fo:text-align` |
| --- | --- | --- | --- |
| Links oberhalb | left | top | start |
| Mittig innen | center | middle | center |
| Rechts 14pt | right | bottom | end |

Writer also changed shape dimensions and coordinates. Therefore the changes in
`style:horizontal-pos` **cannot** be interpreted as proof of text alignment
within an otherwise unchanged object. They position the **shape** relative to
the paragraph. `fo:text-align` belongs to the paragraph; vertical text
alignment and padding belong to the shape graphic style.

Writer normalized padding values to about 0.101 cm horizontally and 0.019 cm
vertically. This is a serialization observation, not a required API rounding rule.

## Implementation contract for this slice

- `DrawingLayout` owns the shape's anchor, dimensions and placement.
- `Paragraph` owns text content, inline formatting and horizontal text alignment.
- `ShapeTextLayout` owns vertical text alignment and padding.
- Existing textless `ShapeElement` behavior remains unchanged.
- No table-based SkillBar implementation.
- Do not infer that the three variants' positions are pixel-identical to the
  Writer-edited reference: Writer changed object sizes and offsets.

## Remaining verification

The current tests assert serialized ODF properties, not pixel geometry.
A visual layout acceptance of a PHP-generated multi-label SkillBar in the
S01b sidebar is still needed before declaring the SkillBar complete.

## PHP-generated single SkillBar proof (2026-11-23)

A separate visual probe uses three PHP-generated `ShapeElement` objects:
one editable text label, one solid track and one solid progress fill. The
objects are assembled into the S01b sidebar's existing text paragraph by a
temporary ODT fixture-composition script, **not** by a public group API.

Rendered output: `/tmp/h03-s01b-single-skillbar-php-generated.odt`.
The image was inspected: the label and the track share a left edge, the
label is above the track, and the fill covers 90% of the track.

Controlled visual comparisons established:

- `draw:text-style-name` or foreground run-through can restore visibility
  of the text-bearing shape in the tested Writer frame context.
- `draw:textarea-horizontal-align="left"` changes the actual text position
  inside the shape; `fo:text-align` alone did not provide the desired result.
- `style:run-through="foreground"` restores the visibility of solid
  PHP-generated shapes when `style:wrap="run-through"` is used.
- The successful bar shapes are native rectangles and are **not rounded**.
  Rounding, grouped object placement, and a first-class PHP SkillBar API are
  outside this narrow proof.

No claim of visual equivalence outside the tested S01b sidebar is made.

## Complete grouped SkillBars – direct PHP insertion

A final S01b test uses a prepared Writer template with a `{{SkillBars}}`
placeholder in its existing sidebar text frame. The template is derived from
the prior S01b reference and removes the temporary test heading.

PHP directly inserts a `SkillBarGroup` with `OdtTemplate::setElement()`,
without an XML transplant after the engine save:

```php
$group = (new SkillBarGroup('Skills'))
    ->addSkill('Teamarbeit', 90)
    ->addSkill('Kommunikation', 72)
    ->addSkill('Organisation', 84);

$template->setElement('SkillBars', $group);
$template->save('/tmp/h03-s01b-skillbars-final-php.odt');
```

The output is `/tmp/h03-s01b-skillbars-final-php.odt`; the authoring template
is `/tmp/h03-s01b-skillbar-authoring-template.odt`. The full LibreOffice
render has been inspected: three white labels start at the same left edge as
their rounded track/fill pairs, and the test heading is absent.

`SkillBarGroup` emits a single native `draw:g`, three editable text
`draw:custom-shape` elements and six native `draw:rect` children with
`draw:corner-radius`. Style requirements are mirrored into the styles part
as automatic styles because a group inserted into a sidebar in `styles.xml`
otherwise renders without its graphic/text formatting. The child styles'
standalone content-part requirements remain unchanged for compatibility.

The test proves native ODF grouping and LibreOffice rendering. Interactive
dragging/group editing inside the Writer UI remains a manual check.

## Compact group-level layout configuration (2026-11-24)

The native `SkillBarGroup` supports these **group-wide** constructor
options (units are centimeters except font size in points):

| Option | Compact default | Meaning |
| --- | --- | --- |
| `x`, `y` | 0.263, 0.0 | Position within the Writer paragraph anchor |
| `width` | 3.001 | Maximum track width (100% fill) |
| `barHeight` | 0.15 | Height of track and fill |
| `trackColor` | #DDE2E8 | Unfilled background color |
| `fillColor` | #2776B6 | Filled progress color |
| `fontFamily` | Liberation Sans | Label typeface |
| `fontSize` | 8.0 | Label size in points |
| `fontColor` | #FFFFFF | Label color |
| `fontBold` | true | Label weight |
| `labelGap` | 0.10 | Space between label box and bar |
| `skillGap` | 0.25 | Space after a bar before the next label |

The corner radius is derived from half the bar height, capped for short
fills to half the actual fill width. Each skill supplies only its label and
integer percent (0–100). A 0% skill has no fill object; 100% uses the full
track width. The automatic vertical pitch is calculated as:

`fontSize * (2.54 / 72) * 1.45 + labelGap + barHeight + skillGap`

The 1.45 text-line multiplier is an empirical Writer layout allowance,
not an ODF-specified line-height guarantee. The legacy `rowHeight`
constructor option remains accepted as an explicit pitch override, while
its default is now `null` (automatic). This default changes the previous
H03 prototype's vertical spacing intentionally.

The visual regression uses the existing S01b Writer template, with a
temporary `{{SkillBars}}` placeholder replacing `{{CVSidebarPage1}}` in
a disposable copy; no `samples/output/*.odt` files are touched.

- Input: `/tmp/h03-compact-sidebar-template.odt`
- Output: `/tmp/h03-compact-sidebar-result.odt`
- Renderer: `tools/visual-regression/render.sh`
- Generator: `tools/visual-regression/h03-skillbar-group.php`

LibreOffice rendering was inspected and confirms compact white labels,
rounded slim tracks, three rows, and a full-length 100% fill. This is a
rendering verification, not an interactive Writer grouping/move test.

# Skill Bars (SkillBarGroup)

**Recommended for grouped skill indicators · H03**

`SkillBarGroup` generates a single native ODF drawing group (`draw:g`)
containing editable labels and rounded background/progress bars. Each skill
has a label and a percentage; all appearance settings apply to the group.

## Example

Create a Writer template containing `{{SkillBars}}` in a paragraph inside
the desired sidebar text frame. Then:

```php
use OdtTemplateEngine\Elements\SkillBarGroup;
use OdtTemplateEngine\OdtTemplate;

$template = new OdtTemplate('cv-template.odt');

$skills = (new SkillBarGroup(
    name: 'Skills',
    x: 0.263,
    y: 0.0,
    width: 5.0,
    trackColor: '#DDE2E8',
    fillColor: '#2776B6',
    barHeight: 0.15,
    fontFamily: 'Liberation Sans',
    fontSize: 7.0,
    fontColor: '#FFFFFF',
    fontBold: true,
    labelGap: 0.05,
    skillGap: 0.12
))
    ->addSkill('Teamarbeit', 100)
    ->addSkill('Kommunikation', 72)
    ->addSkill('Organisation', 84);

$template->setElement('SkillBars', $skills);
$template->save('cv-output.odt');
```

The dimensions and spacing above are illustrative. **Choose a width that
fits your actual Writer frame.** The engine does not yet calculate the
available frame width automatically.

## Public API

```php
new SkillBarGroup(
    string $name,
    float $x = 0.263,
    float $y = 0.0,
    float $width = 3.001,
    ?float $rowHeight = null,
    string $trackColor = '#DDE2E8',
    string $fillColor = '#2776B6',
    float $barHeight = 0.15,
    string $fontFamily = 'Liberation Sans',
    float $fontSize = 8.0,
    string $fontColor = '#FFFFFF',
    bool $fontBold = true,
    float $labelGap = 0.10,
    float $skillGap = 0.25
);

public function addSkill(string $label, int $percent): self;
```

| Option | Unit | Meaning |
| --- | --- | --- |
| `name` | text | Native group name; must not be empty |
| `x`, `y` | cm | Group content offsets relative to its paragraph anchor; nonnegative |
| `width` | cm | Full track width, corresponding to 100% |
| `rowHeight` | cm or `null` | Optional fixed vertical pitch between labels; `null` calculates it automatically |
| `trackColor` | `#RRGGBB` | Unfilled track color |
| `fillColor` | `#RRGGBB` | Progress fill color |
| `barHeight` | cm | Track/fill height; rounded end radius is derived automatically |
| `fontFamily` | text | Typeface for every label |
| `fontSize` | pt | Font size for every label |
| `fontColor` | `#RRGGBB` | Label color |
| `fontBold` | boolean | Bold labels when `true` |
| `labelGap` | cm | Gap between label box and its bar |
| `skillGap` | cm | Gap between consecutive skills |

All sizes and gaps are group-wide. `addSkill()` is fluent, accepts an
integer from 0 to 100 inclusive, and requires a nonempty label. A 0% skill
has only the background bar; a 100% skill fills the full track width.
Invalid sizes, colors, labels or percentages raise
`InvalidArgumentException`.

With `rowHeight: null`, the engine calculates the next row's position from
an estimated label height, `labelGap`, `barHeight` and `skillGap`.
The text-height estimate is an implementation approximation, not a promise
of identical line metrics in every Writer font or layout. The fill's corner
radius is capped for very small percentages.

## Writer template integration

The insertion point belongs to Writer; the generated group belongs to PHP.
Use `OdtTemplate::setElement('SkillBars', $skills)` for a placeholder in
a text paragraph. This insertion is immediate; `save()` does not require
`render()` unless classic staged bindings are also used.

When the placeholder is inside a sidebar frame stored in `styles.xml`,
the engine also materializes the drawing/text styles required by the group
there. The resulting labels and bars remain native ODF objects.

**Not part of this API:** per-skill font or color overrides, frame-relative
percentage widths, automatic fitting to the sidebar, or CV-specific layout
selection. A consuming application such as CV Generator Pro can select the
group-wide settings. Native grouping and visual rendering have been verified;
joint dragging/editing of the group in the Writer UI has not been manually
confirmed.

See [Native Drawing Shapes](native-shapes.md) for the lower-level shape API
and [Frames & Text Boxes](frames-text-boxes.md) for constructing a text frame.

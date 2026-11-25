# Native Drawing Shapes

**Advanced · PHP-owned Structured Content · H03**

Use `ShapeElement` to create an editable native ODF `draw:custom-shape`.
Unlike a bitmap, the geometry remains a drawing object in LibreOffice Writer.
The caller supplies three separate objects: geometry, appearance, and drawing
layout. Optionally, add editable paragraphs and a text layout.

## Create a shape

```php
use OdtTemplateEngine\Elements\DrawingLayout;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\Elements\ShapeAppearance;
use OdtTemplateEngine\Elements\ShapeElement;
use OdtTemplateEngine\Elements\ShapeGeometry;
use OdtTemplateEngine\Elements\ShapeTextLayout;
use OdtTemplateEngine\OdtTemplate;

$layout = DrawingLayout::fromArray([
    'anchor' => 'paragraph',
    'width' => '4cm',
    'height' => '1cm',
    'horizontal' => [
        'offset' => '0cm',
        'relative-to' => 'paragraph',
    ],
    'vertical' => [
        'offset' => '0cm',
        'relative-to' => 'paragraph',
    ],
    'wrap' => 'run-through',
]);

$shape = new ShapeElement(
    ShapeGeometry::preset('rectangle'),
    ShapeAppearance::solid('#2776B6'),
    $layout
);
$shape->setTextLayout(new ShapeTextLayout('middle', [], 'center'));
$shape->addParagraph(
    (new Paragraph())->addText('Editable label', [
        'font-family' => 'Liberation Sans',
        'font-size' => '9pt',
        'color' => '#FFFFFF',
    ])
);

$template = new OdtTemplate('template.odt');
$template->setElement('shape', $shape); // {{shape}} in a Writer paragraph
$template->save('shape-output.odt');
```

## API

| Class or method | Contract |
| --- | --- |
| `ShapeGeometry::preset(string $type): ShapeGeometry` | Supported examples: `rectangle`, `ellipse`, `diamond`, `arrow-contour`, `hexagon`, `round-rectangle`, `left-arrow`, `ring`; additional catalog presets are supported |
| `ShapeAppearance::solid(string $color, string $stroke = 'none')` | Solid fill; six-digit hexadecimal color |
| `ShapeAppearance::none(string $stroke = 'solid')` | No fill |
| `ShapeAppearance::bitmap(string $path, string $stroke = 'none')` | Bitmap fill from an existing readable file |
| `new ShapeElement(ShapeGeometry $geometry, ShapeAppearance $appearance, DrawingLayout $layout)` | Native editable shape |
| `ShapeElement::addParagraph(Paragraph $paragraph): self` | Append editable text inside the shape |
| `ShapeElement::setTextLayout(ShapeTextLayout $layout): self` | Position text within the shape |
| `new ShapeTextLayout(string $verticalAlignment = 'top', array $padding = [], ?string $horizontalAlignment = null)` | Text alignment and padding |

`ShapeAppearance` accepts only `none` or `solid` strokes.
`ShapeElement` requires a `paragraph` or `as-char` anchor and explicit width
and height. The complete `DrawingLayout::fromArray()` schema is described in
[Frames & Text Boxes](frames-text-boxes.md#semantic-frame-layout).

For `ShapeTextLayout`, vertical alignment is `top`, `middle` or `bottom`;
horizontal alignment is `left`, `center`, `right`, or `null` (unspecified).
Padding accepts an array keyed by `top`, `right`, `bottom`, `left`, with
nonnegative lengths in `cm`, `mm`, `pt` or `in`. Text styling is supplied
by `Paragraph`, not by `ShapeTextLayout`.

The `RoundedRectElement` class is currently used as a building block of
[SkillBarGroup](skillbars.md). For ordinary skill indicators, use the group
API instead of constructing each rounded rectangle manually.

## Limits

The API emits native drawing geometry; it does not reproduce every
LibreOffice drawing feature. Text rendering and positioning depend on the
Writer layout context. Verify visually in the intended template, especially
inside header or sidebar frames. No general-purpose native group-building
API was promised by the original H03 implementation; the bounded P2 composition API is described below.


## Inline rounded tags and drawing composition (P2-I03/I03R)

Use the existing `round-rectangle` preset with explicit drawing-text Auto-Grow.
No separate tag class or hand-built rounding geometry is required:

```php
$tag = (new ShapeElement(
    ShapeGeometry::preset('round-rectangle'),
    ShapeAppearance::solid('#203A52'),
    DrawingLayout::fromArray([
        'anchor' => 'as-char', 'width' => '1cm', 'height' => '0.2cm',
        'margin' => ['left' => '0.08cm', 'right' => '0.08cm'],
    ])
))->setTextAutoGrow(true, true)
    ->setTextLayout(new ShapeTextLayout('top', [
        'top' => '0.152cm', 'right' => '0.152cm',
        'bottom' => '0.152cm', 'left' => '0.152cm',
    ]))
    ->addParagraph((new Paragraph(null, ['fo:margin' => '0cm']))->addText('PostgreSQL', [
        'font-family' => 'Liberation Sans', 'font-size' => '9pt', 'color' => '#FFFFFF',
    ]));
$template->appendBody((new Paragraph())->addText('Skills: ')->addInlineElement($tag));
```

`setTextAutoGrow(bool $width = true, bool $height = true): self` is opt-in.
Without this call, existing fixed-size shapes retain their previous behavior.
Drawing-text growth is distinct from the Writer-frame minima on `DrawTextBox`.
The preset controls rounding; arbitrary corner-radius fitting is not provided.
Writer places inline shapes atomically and wraps them to subsequent lines/pages.
The consumer must check physical overflow, especially labels wider than the region.

`DrawingGroup` is a neutral composition carrier for shapes, images and nested
`DrawingGroup` instances. Contact roles and visual presets remain consumer-owned:

```php
use OdtTemplateEngine\Elements\DrawingGroup;

// $backing and $icon use explicit child-local dimensions and offsets.
$group = new DrawingGroup('contact-symbol', [$backing, $icon]);
$paragraph = (new Paragraph())->addInlineElement($group)->addText(' Contact');
```

The constructor accepts `(string $name, array $children, ?DrawingLayout $layout = null)`.
The default anchor is `as-char`; `paragraph` is also supported with
`Paragraph::addDrawing()`. Children are `ShapeElement`, `RoundedRectElement`,
`ImageElement` or nested groups. Empty groups and Writer text frames are rejected.
Child array order defines paint order (later children appear in front), regardless
of each child's standalone z-index. One anchor belongs to each group, not its children.
Child coordinates remain local, including negative coordinates. Group bounds derive
from the children: group `width`, `height` and nonzero horizontal offsets are rejected;
`draw:g` does not receive frame-only `svg:x/width/height` attributes.

New inline/growing shapes and groups project their owned graphic, paragraph and text
style definitions into automatic styles in the actual document part. Generated names
are isolated from ordinary Writer styles. A conflicting authored definition at a
reserved generated identity fails without overwriting it. Ordinary standalone
ShapeElement/ImageElement behavior remains unchanged. Explicit foreground/background
images use the drawing image context and `Graphics` parent. This is not a universal
conversion/flattening service for arbitrary inherited named Writer styles; use explicit
owned text/paragraph properties for the verified drawing recipes.

Writer User Fields belong in Writer text or `DrawTextBox`, outside shape text and
groups. Newly generated field-bearing shape/group content is rejected because Writer
loses those native references during drawing import. Existing authored package XML is
not rewritten by this guard. Generic contact compositions combine Writer field text
with independent symbol/backing drawings.

## Annular sectors and progress rings (P2-I05)

`ShapeGeometry::annularSector(float $sweepDegrees, float $innerRadiusRatio): ?ShapeGeometry`
constructs a clockwise annular sector beginning at the top. Both arguments must be
finite: sweep in `[0, 360]`, inner radius divided by outer radius strictly in `(0, 1)`.
Zero returns `null`; omit the progress shape. A full sweep reuses the existing `ring`
preset, equations and handles with the requested native thickness modifier. The
ordinary `ShapeGeometry::preset('ring')` is unchanged.

Partial arcs use the already proven closed polygonal construction: outer arc forward,
inner arc backward, closed radial ends. Chords span at most three degrees; the actual
supplied endpoint is always included, rather than rounding the sweep to that grid.
Coordinates use eight decimal places in the existing 21600-square viewBox. Inputs
whose thickness or endpoint collapses at that precision are rejected. This is a bounded
polygonal arc approximation, not a new general path engine. Tiny values are not enlarged
to make them visible at every physical size or output resolution.

```php
use OdtTemplateEngine\Elements\DrawingGroup;

// The consumer owns percentage meaning, diameter, colors and optional labels.
$ratio = 7000 / 10800;
$layout = DrawingLayout::fromArray([
    'anchor' => 'paragraph', 'width' => '2cm', 'height' => '2cm',
    'horizontal' => ['offset' => '0cm', 'relative-to' => 'paragraph-content'],
    'vertical' => ['offset' => '0cm', 'relative-to' => 'paragraph-content'],
    'wrap' => 'run-through', 'run-through' => 'foreground',
]);
$children = [new ShapeElement(
    ShapeGeometry::annularSector(360, $ratio), ShapeAppearance::solid('#DDDDDD'), $layout
)];
$progress = ShapeGeometry::annularSector(70 / 100 * 360, $ratio);
if ($progress !== null) {
    $children[] = new ShapeElement($progress, ShapeAppearance::solid('#337799'), $layout);
}
$template->appendBody((new Paragraph())->addInlineElement(new DrawingGroup('skill-ring', $children)));
```

The full track fixes the group's region; the optional progress follows it in paint
order. Use normal Paragraph/Writer text for field-bearing labels outside the drawing
text boundary. Unscored skills, percentages, rating quantization and layout choices
remain consumer responsibilities. There is no new RingIndicator or SkillBarGroup API.

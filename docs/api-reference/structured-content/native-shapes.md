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
`ShapeElement` currently requires a paragraph anchor and explicit width
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
API is promised by this H03 implementation.

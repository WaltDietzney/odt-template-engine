# Text & Paragraph Styles

Text and paragraph styles solve different problems. Keeping them separate is one of the most important practices for predictable generated ODT documents.

## Text styles

Pass text styling to `Paragraph::addText()`:

```php
$paragraph->addText('Section title', [
    'font-family' => 'Arial',
    'font-size' => '12pt',
    'bold' => true,
    'color' => '#12324a',
]);
```

Common options currently mapped by the engine include:

```php
[
    'bold' => true,
    'italic' => true,
    'underline' => true,
    'font-weight' => 'bold',
    'font-style' => 'italic',
    'color' => '#333333',
    'background-color' => '#fff4cc',
    'font-size' => '10pt',
    'font-family' => 'Arial',
]
```

Font-family usage is also reflected in the ODT font-face declarations written for the document.

## Paragraph style options

Paragraph styles are supplied when creating a paragraph or through `setParagraphStyleOptions()`:

```php
$paragraph = new Paragraph(null, [
    'margin-left' => '0.5cm',
    'margin-right' => '0cm',
    'margin-top' => '0.2cm',
    'margin-bottom' => '0.1cm',
    'text-align' => 'left',
    'line-height' => '110%',
]);
```

Supported mappings include properties such as:

- left, right, top, and bottom margins;
- text alignment;
- text indentation;
- line height;
- background color;
- keep-with-next;
- page/column break behavior;
- writing mode;
- padding;
- borders;
- tab stops.

Advanced ODF attributes can also pass through the mapper, but application code should prefer the documented friendly options where possible.

## Borders and spacing

A paragraph can be used for visual section separators without creating a table:

```php
$heading = new Paragraph(null, [
    'margin-top' => '0.45cm',
    'margin-bottom' => '0.10cm',
    'border-bottom' => '1.5pt solid #12324a',
    'padding-bottom' => '0.03cm',
]);

$heading->addText('Experience', [
    'bold' => true,
    'font-size' => '12pt',
]);
```

This pattern is used by the complex CV sample.

## Tab stops

Paragraph tab stops are paragraph-level geometry:

```php
$paragraph = new Paragraph();
$paragraph
    ->addTabStopDefinition(4.0, 'left')
    ->addTabStopDefinition(12.0, 'right')
    ->addText('Description')
    ->addTab()
    ->addText('129.90');
```

The engine maps the tab definitions into ODF paragraph style data.

Use tabs for compact aligned text. For genuinely tabular data, prefer a native table.

## Semantic reusable styles

For repeated roles in a complex document, register a meaningful paragraph style name:

```php
use OdtTemplateEngine\Utils\StyleMapper;

StyleMapper::registerParagraphStyle('InvoiceSectionHeading', [
    'margin-top' => '0.4cm',
    'margin-bottom' => '0.1cm',
    'border-bottom' => '1pt solid #333333',
]);

$heading = new Paragraph('InvoiceSectionHeading');
$heading->addText('Items', ['bold' => true]);
```

The benefit is not only code reuse. The generated ODT also contains a style name that describes its purpose.

For one-off paragraph geometry, constructing the paragraph directly with style options is simpler and avoids global explicit registration.

## Named text styles

`StyleMapper` also supports explicit text-style registration, but most application code does not need to register text styles manually. `Paragraph::addText()` generates and collects the required inline styles automatically.

Use explicit registration only when you have a concrete advanced requirement and understand the current process-scoped registry behavior.

## Avoid mixing responsibilities

Prefer:

```php
$paragraph = new Paragraph(null, [
    'margin-bottom' => '0.2cm',
]);

$paragraph->addText('Important', [
    'bold' => true,
    'color' => '#a40000',
]);
```

rather than trying to express paragraph spacing as a text property or font styling as paragraph geometry.

## Related samples

- Sample 09 — RichText and paragraph styling
- Sample 14 — tabs, borders, margins, and paragraph style behavior
- Sample 21 — semantic named paragraph styles in a complex document

For the overall architecture and the current static-registry caveat, see [Style Model](style-model.md).

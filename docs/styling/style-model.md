# Style Model

Styling in ODT is structural. A visual result may involve text properties, paragraph properties, table-cell properties, graphic styles, and style definitions stored in different parts of the ODT package.

The engine therefore does not treat every style option as one undifferentiated CSS-like array.

The most important rule is to style the correct document layer.

## Style responsibilities

Think of generated content in layers:

```text
Document structure
│
├── Paragraph
│   ├── paragraph style
│   │   ├── margins
│   │   ├── alignment
│   │   ├── indentation
│   │   ├── borders
│   │   ├── padding
│   │   └── line height
│   │
│   └── text runs
│       ├── font family
│       ├── font size
│       ├── bold / italic
│       ├── underline
│       └── color
│
├── Table cell
│   ├── background
│   ├── border
│   └── padding
│
└── Image / frame
    ├── size
    ├── anchor
    ├── wrap
    └── positioning
```

This distinction matters because ODF serializes these responsibilities differently.

## Text styles

Text styles describe inline text runs:

```php
$paragraph->addText('Important', [
    'bold' => true,
    'italic' => true,
    'color' => '#12324a',
    'font-size' => '11pt',
    'font-family' => 'Arial',
]);
```

The engine maps these friendly options to ODF text properties and creates the required style definitions.

## Paragraph styles

Paragraph properties describe the block rather than its characters:

```php
$paragraph = new Paragraph(null, [
    'margin-top' => '0.3cm',
    'margin-bottom' => '0.1cm',
    'text-align' => 'left',
    'border-bottom' => '1pt solid #12324a',
    'padding-bottom' => '0.05cm',
]);
```

Do not encode paragraph spacing by adding empty text or repeated line breaks when a paragraph margin expresses the intention more accurately.

## Table-cell styles

A table cell has another style layer:

```php
$cell = new RichTableCell('Total', [
    'background' => '#eeeeee',
    'border' => '0.5pt solid #999999',
    'padding' => '0.15cm',
    'text-align' => 'right',
    'bold' => true,
]);
```

The convenience API separates cell, paragraph, and text options before rendering. This is useful for small examples, but understanding the layers remains important when debugging a complex document.

## Automatic styles and named styles

For local styling, the engine can generate style names automatically from style definitions.

For complex documents, semantic named paragraph styles can make the generated ODT easier to understand and maintain:

```php
StyleMapper::registerParagraphStyle('CVEntryTitle', [
    'margin-top' => '0.1cm',
    'margin-bottom' => '0.03cm',
]);

$paragraph = new Paragraph('CVEntryTitle');
```

Names such as `CVEntryTitle`, `ReportHeading`, or `InvoiceTotal` communicate intent when inspecting the generated XML or editing the resulting document.

## Where styles are stored

An ODT package can contain styles in more than one XML location.

The engine currently writes document styles primarily through `styles.xml`, while some generated automatic structures such as table-column and table-cell styles may also involve `content.xml` automatic styles.

You normally do not need to manage those XML locations manually. The distinction becomes important when diagnosing missing or duplicated styles.

## StyleMapper and StyleWriter

`StyleMapper` translates developer-facing style options into ODF attributes and maintains several style registries.

`StyleWriter` serializes registered and required styles into the ODT XML package.

Normal application code should generally style elements through `Paragraph`, `RichTableCell`, `ImageElement`, and related public elements. Direct `StyleMapper` registration is useful for advanced reusable semantic styles.

`StyleWriter` is an implementation utility and is not the recommended application-facing styling API.

## Advanced registration and process scope

The current `StyleMapper` registries are static. Explicit registrations such as:

```php
StyleMapper::registerParagraphStyle(...);
StyleMapper::registerTextStyle(...);
```

can therefore persist across multiple documents generated within the same PHP process.

This does not make semantic registration unusable, but it means the current API should not be treated as a document-scoped style configuration object. A future `StyleContext`-style architecture is tracked in `FUTURE_DEVELOPMENT.md`.

For ordinary element-generated styles, use the element APIs and allow the engine to collect the required styles from the generated content.

## Prefer semantic intent over visual hacks

When possible:

- use paragraph margins instead of empty paragraphs for spacing;
- use `ListElement` instead of a bullet character;
- use `RichTable` instead of aligned spaces for tabular data;
- use table-cell padding instead of spaces inside a cell;
- use paragraph styles for paragraph geometry and text styles for characters.

This produces more native, editable ODF and usually behaves better when the document is modified in LibreOffice.

## Verification

For style-sensitive features, the project's effective verification model is:

```text
PHPUnit / regression tests
        +
ODT package and XML verification
        +
LibreOffice visual inspection
```

An ODT package can be structurally valid while still producing an unexpected office-layout result. Conversely, a visually plausible document can hide incorrect style ownership in the XML. Both levels matter for complex document generation.

Continue with [Text & Paragraph Styles](text-and-paragraph-styles.md) and [Table & Cell Styles](table-and-cell-styles.md) for practical examples.

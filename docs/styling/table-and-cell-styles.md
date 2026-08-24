# Table & Cell Styles

A generated table combines several style layers. Understanding which layer owns a property makes table rendering much easier to debug.

## Cell properties

A `RichTableCell` owns cell geometry and decoration such as:

```php
$cell = new RichTableCell('Value', [
    'background' => '#f2f2f2',
    'border' => '0.5pt solid #999999',
    'padding' => '0.15cm',
]);
```

These options are mapped to ODF table-cell properties.

## Paragraph properties inside a cell

Text alignment belongs to the paragraph inside the cell, not to the characters themselves:

```php
$cell = new RichTableCell('129.90', [
    'text-align' => 'right',
]);
```

The convenience style splitter routes paragraph-oriented options to the cell's paragraph content.

For more explicit control, construct the paragraph yourself:

```php
$paragraph = new Paragraph(null, [
    'text-align' => 'right',
    'margin-top' => '0cm',
    'margin-bottom' => '0cm',
]);
$paragraph->addText('129.90', ['bold' => true]);

$cell = new RichTableCell($paragraph, [
    'background' => '#f2f2f2',
    'padding' => '0.15cm',
]);
```

This makes the three responsibilities visible in code:

```text
RichTableCell
├── background / border / padding
└── Paragraph
    ├── alignment / spacing
    └── text
        └── font / bold / color
```

## Mixed convenience styles

For simple cells, a single style array can contain cell, paragraph, and text properties:

```php
$cell = new RichTableCell('Total', [
    'background' => '#12324a',
    'border' => '0.5pt solid #0b1f2d',
    'padding' => '0.15cm',
    'text-align' => 'right',
    'bold' => true,
    'color' => '#ffffff',
]);
```

The engine separates these options internally. This is convenient, but explicit nested content is preferable when a cell becomes structurally complex.

## Spans

Cell spans are structural properties rather than visual styles:

```php
$cell
    ->setColspan(2)
    ->setRowspan(2);
```

The engine writes native ODF span attributes to the table cell.

## Table style names

`RichTable::setTableStyleName()` can assign a table-level style name:

```php
$table->setTableStyleName('InvoiceTable');
```

Table-level style registration is an advanced area. For most generated tables, start with cell and paragraph styling unless the document has a clear reusable table-style requirement.

## Style sets for array-built tables

`buildTableFromArray()` can use predefined or custom style sets containing roles such as:

```text
header
row
row-alt
summary / highlight
```

Custom style sets are registered on the table instance:

```php
$table->addCustomStyle('report', [
    'header' => [
        'background' => '#333333',
        'color' => '#ffffff',
        'font-weight' => 'bold',
    ],
    'row' => [
        'background' => '#f5f5f5',
    ],
    'row-alt' => [
        'background' => '#ffffff',
    ],
]);
```

Use these when they simplify application rendering. For a highly designed document, LibreOffice template styles or explicit semantic rendering code may be easier to maintain.

## XML placement is an implementation concern

ODF allows style definitions in different package locations. The current table pipeline uses both normal style writing and `content.xml` automatic-style paths for some generated table structures.

Application code should not depend on the exact XML destination of a generated cell style. Treat `RichTable`, `RichTableCell`, and their documented style options as the public abstraction.

The project roadmap tracks further consolidation of style ownership and serialization paths.

## Current limitations

Styling a cell does not solve exact table geometry. Physical table width, precise column widths, and row heights have separate known limitations documented in the [Tables](../rich-documents/tables.md) guide.

Always distinguish:

```text
style problem
vs.
layout/geometry problem
```

when diagnosing a generated table.

## Related samples

- Sample 12 — advanced table styling
- Sample 13 — cell configuration
- Sample 15 — styled table
- Sample 20 — ratio-based layout

See [Style Model](style-model.md) for the general styling architecture.

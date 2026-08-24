# Tables

`RichTable` and `RichTableCell` generate native ODT table structures when the table itself must be assembled from PHP.

If a table has a stable layout and only its values change, designing the table directly in LibreOffice may still be the simpler solution. Use `RichTable` when rows, cells, spans, or cell content are application-driven.

## Basic table

```php
use OdtTemplateEngine\Elements\RichTable;

$table = new RichTable();
$table
    ->addRow(['Product', 'Quantity', 'Price'])
    ->addRow(['Tea', '2', '3.50'])
    ->addRow(['Coffee', '1', '4.20']);

$template->setElement('order_table', $table);
```

Plain strings are automatically wrapped in `RichTableCell` objects and paragraph content.

## Header rows

Mark one or more initial rows as table headers:

```php
$table->setHeaderRowCount(1);
```

The generated ODT wraps these rows in `table:table-header-rows`.

## Rich cell content

A cell may contain a string, `Paragraph`, or `RichText`:

```php
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\Elements\RichTableCell;

$title = (new Paragraph())
    ->addText('Total', ['bold' => true]);

$cell = new RichTableCell($title);

$table->addRow([
    $cell,
    new RichTableCell('129.90'),
]);
```

Use `RichText` when a cell needs several paragraphs or more complex generated content.

## Cell styling

`RichTableCell::setStyle()` accepts a mixed convenience style array. The engine separates table-cell, paragraph, and text responsibilities internally.

```php
$cell = new RichTableCell('Total', [
    'background' => '#e9eef2',
    'border' => '0.5pt solid #9aa7b2',
    'padding' => '0.15cm',
    'text-align' => 'right',
    'bold' => true,
    'color' => '#12324a',
]);
```

Typical cell-level properties include background, border, and padding. Text alignment belongs to the paragraph inside the cell, while font properties belong to the text content.

For larger documents, keeping those responsibilities explicit usually produces clearer rendering code.

## Column and row spans

Cells support column and row spans:

```php
$heading = (new RichTableCell('Section'))
    ->setColspan(2);

$table->addRow([$heading]);
```

and:

```php
$cell = (new RichTableCell('Shared value'))
    ->setRowspan(2);
```

The engine writes the corresponding ODF span attributes.

## Building tables from arrays

For conventional data tables, `buildTableFromArray()` can create rows and apply one of the table's predefined style sets:

```php
$table = new RichTable();
$table->buildTableFromArray([
    ['Product', 'Quantity', 'Price'],
    ['Tea', '2', '3.50'],
    ['Coffee', '1', '4.20'],
], 'default');
```

The implementation also contains style presets intended for report-like and finance-oriented examples. For application-specific visual identity, explicit cell styles or custom style definitions are generally easier to reason about than depending heavily on built-in presets.

## Custom style sets

A table can register a named style set for `buildTableFromArray()`:

```php
$table->addCustomStyle('invoice', [
    'header' => [
        'background' => '#12324a',
        'color' => '#ffffff',
        'font-weight' => 'bold',
    ],
    'row' => [
        'background' => '#f5f7f8',
    ],
    'row-alt' => [
        'background' => '#ffffff',
    ],
]);

$table->buildTableFromArray($rows, 'invoice');
```

## Column widths and ratios

`RichTable` currently exposes `setColumnWidths()` and `setColumnWidthRatios()`. These APIs are used by existing samples, but precise table geometry is one of the areas where ODF generation and LibreOffice layout behavior remain under active development.

For example:

```php
$table->setColumnWidths([
    '5cm',
    '3cm',
    '2cm',
]);
```

or ratio-based layout:

```php
$table->setColumnWidthRatios([3, 2, 1]);
```

The ratio implementation maps logical ratios to virtual column spans. It is useful for existing layouts, but it should not be interpreted as a general guarantee of exact physical column widths.

## Current limitations

Precise programmatic table geometry is not yet a fully solved part of the public API.

Known development areas include:

- reliable explicit overall table width;
- exact per-column physical widths across LibreOffice layouts;
- explicit row-height control;
- simplifying the current ratio/virtual-column workaround;
- clearer ownership of some table-cell style serialization paths.

When exact geometry matters, prefer defining the stable table structure in the LibreOffice template where practical. Use PHP-generated tables for dynamic structure, and verify representative output in LibreOffice.

## Related samples

- Sample 11 — generated table basics
- Sample 12 — advanced table content and styling
- Sample 13 — table-cell configuration
- Sample 15 — styled simple table
- Sample 19 — HTML table import into native ODT structures
- Sample 20 — ratio-based table layout

See [Table & Cell Styles](../styling/table-and-cell-styles.md) for the style-responsibility model.

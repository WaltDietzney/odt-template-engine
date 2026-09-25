# Tables & Cells

`RichTable` and `RichTableCell` are the Recommended APIs when **PHP owns the table structure**.

If a table already exists in the Writer template and PHP should populate that existing structure, use the Writer-native `TableTarget` API instead. Do not rebuild a Writer-owned table as `RichTable` merely to insert data.

## Basic table construction

```php
use OdtTemplateEngine\Elements\RichTable;
use OdtTemplateEngine\Elements\RichTableCell;

$table = new RichTable();
$table
    ->addRow([
        new RichTableCell('Product', ['bold' => true]),
        new RichTableCell('Price', ['bold' => true]),
    ])
    ->addRow([
        'Coffee',
        (new RichTableCell('4.99'))->setBackground('#f5f5f5'),
    ]);

$table->setHeaderRowCount(1);

$content->addTable($table);
```

## `RichTable::addRow()`

**Recommended**

```php
public function addRow(array $cells, array $style = []): self
```

Each item that is not already a `RichTableCell` is passed to the cell constructor. The supported convenience content types are therefore `string`, `Paragraph`, and `RichText`.

### Row style contract

The current semantic row-layout contract supports:

| Key | Type | Meaning |
| --- | --- | --- |
| `row-height` | ODF length string | Fixed row height. |
| `min-row-height` | ODF length string | Minimum row height. |

They are mutually exclusive. Supplying both throws `InvalidArgumentException`. Supported length units are `cm`, `mm`, `in`, `pt`, `pc`, and `px`.

Unrelated row-style keys may remain in compatibility state but are not materialized as row layout and are not part of the Recommended contract.

## Header rows

```php
public function setHeaderRowCount(int $count): self
```

The first `$count` rows are emitted inside `table:table-header-rows`. The method currently performs no explicit range validation; use a non-negative count.

## Table identity

```php
public function setTableName(string $name): self
```

Sets the ODT `table:name`. New tables otherwise receive an automatically generated name such as `Table_1`.

## Table-level style and layout

### `setTableStyle()`

**Recommended semantic master API**

```php
public function setTableStyle(array $options): self
```

Replaces the complete current element-owned table style.

| Key | Type / values | Meaning |
| --- | --- | --- |
| `width` | ODF length | Absolute table width. |
| `relative-width` | percentage string such as `60%` | Relative table width. |
| `alignment` | `left`, `center`, `right`, `margins` | Horizontal table alignment. |

`width` and `relative-width` are mutually exclusive. The current validator explicitly checks the documented friendly keys, but it does **not** reject additional unknown friendly keys before the mapper runs. The mapper passes native-prefixed keys through and would reject unknown friendly keys if called directly; through `setTableStyle()`, however, unknown friendly keys currently reach that mapper and therefore throw `InvalidArgumentException`.

Native `fo:*`, `style:*`, and `table:*` keys are accepted as an Advanced compatibility escape hatch.

Calling `setTableStyle([])` clears both the element-owned table properties and the table style reference.

### Focused table layout methods

**Recommended**

```php
public function setTableWidth(string $width): self
public function setTableRelativeWidth(string $width): self
public function setTableAlignment(string $alignment): self
```

These update the element-owned table style. Absolute width uses the same ODF length units listed above; relative width requires a percentage.

Setting an absolute width removes a current relative width and vice versa.

### Named style references

```php
public function setTableStyleName(string $styleName): self
```

References an existing named table style and clears element-owned table properties.

A named-style reference and local element-owned table style are intentionally separate modes. Calling one of the focused layout mutators while a named-style reference is active throws `LogicException`; explicitly switch back with `setTableStyle(...)` when PHP should own the table properties.

### `setStyle()`

**Advanced**

```php
public function setStyle(array $style): self
```

Replaces the complete element-owned **already-normalized/native** table-property state. Unlike `setTableStyle()`, it does not map or validate friendly keys. Prefer `setTableStyle()` for application code.

## Column widths

### Absolute widths

```php
public function setColumnWidths(array $widths): void
public function getColumnWidths(): array
```

The array defines absolute column widths in column order and is materialized as table-column styles.

This legacy-shaped setter currently returns `void`, so it does not participate in the fluent chain. It does not validate the width strings at the call boundary.

### Relative ratios

```php
public function setColumnWidthRatios(array $ratios): void
```

Positive integer ratios are normalized into ODF relative column widths totaling the engine's internal relative-width scale. For example, `[1, 2]` expresses a 1:2 relationship.

The current method does not validate the array at the public call boundary. Treat positive integer ratios as the Recommended contract; other values are historical/undefined input and are not promised by the 1.0 reference.

When relative ratios are present they take precedence over absolute column widths during semantic materialization.

## `RichTableCell`

**Recommended**

```php
public function __construct(
    string|Paragraph|RichText $content,
    array $style = []
)

public static function create(
    string|Paragraph|RichText $content,
    array $style = []
): self
```

Plain string content is wrapped in a `Paragraph`, which allows mixed cell/paragraph/text styling to be applied at the correct ODF layer.

### `setContent()`

```php
public function setContent(mixed $content): self
```

The signature is historically broad, but table rendering is designed around `string`, `Paragraph`, and `RichText`. Use those types for supported application code.

## Cell style contract

```php
public function setStyle(array $style): self
```

The mixed convenience array is split across **cell**, **paragraph**, and **text** responsibilities.

### Cell properties

| Key | Meaning |
| --- | --- |
| `background`, `background-color` | Cell background color. |
| `padding`, `padding-left`, `padding-right`, `padding-top`, `padding-bottom` | Cell padding. |
| `border`, `border-left`, `border-right`, `border-top`, `border-bottom` | Cell borders. |
| `vertical-align` | Native mapper supports `top`, `middle`, `bottom`, `automatic`; see limitation below. |

Text options such as `bold`, `font-weight`, `italic`, `color`, and font properties are delegated to the owned paragraph/text content. Paragraph options such as `align`/`text-align`, margins, line height, and paragraph borders/padding are likewise delegated to that content.

Native-prefixed keys remain an Advanced compatibility escape hatch.

**Current limitation:** `StyleOptionSplitter` does not currently classify the friendly key `vertical-align` as a table-cell key. Consequently `RichTableCell::setStyle(['vertical-align' => ...])` does not reach the mapper's validated friendly `vertical-align` branch as intended. The 1.0 reference therefore does not promise that friendly key through `setStyle()`; this is a documented implementation mismatch, not silently corrected here.

### Focused cell style methods

**Recommended**

```php
public function setBackground(string $color): self
public function setBorder(string $border): self
public function setBorderTop(string $border): self
public function setBorderRight(string $border): self
public function setBorderBottom(string $border): self
public function setBorderLeft(string $border): self
public function setPadding(string $padding): self
public function setPaddingTop(string $padding): self
public function setPaddingRight(string $padding): self
public function setPaddingBottom(string $padding): self
public function setPaddingLeft(string $padding): self
```

These methods mutate the cell-owned ODF properties directly and refresh the generated cell style.

## Alignment

```php
public function alignLeft(): self
public function alignCenter(): self
public function alignRight(): self
```

These convenience methods assign the paragraph style names `LeftPara`, `CenterPara`, or `RightPara` when the cell content is a `Paragraph`.

They do not define those paragraph styles. Prefer `setStyle(['text-align' => ...])` when PHP should own the actual paragraph alignment definition.

`forceParagraphAlignment()` is retained public compatibility state; the current rendering path does not consult its stored flag and no visual effect is promised.

## Cell spans

```php
public function setColspan(int $colspan): self
public function setRowspan(int $rowspan): self
public function colspan(int $count): self
public function rowspan(int $count): self
```

Values are clamped to at least `1`.

The supporting read surface is:

```php
public function getColspan(): int
public function getRowspan(): int
public function getStyle(): array
public function getStyleName(): ?string
```

These accessors expose the current cell span/style state for Advanced inspection and tooling; they do not introduce a second cell-authoring path.

These methods emit the ODF span attributes on the originating cell. The current PHP-owned table renderer does **not** automatically emit the covered-table-cell placeholders required to complete a full ODF merged-cell grid. Treat spans as Advanced in 1.0 when exact merged-grid interoperability matters.

## Compatibility table builder

The following `RichTable` methods belong to the historical preset/builder layer rather than the semantic master API:

```php
public function buildTableFromArray(array $tableData, string $styleName = 'default'): self
public function addCustomStyle(string $name, array $styleDefinition): self
public function getCustomStyle(string $name): ?array
public function setSummaryKeywords(array $keywords): self
```

They remain supported Compatibility/Advanced conveniences. New code that needs predictable table semantics should construct rows/cells directly and use the explicit style/layout APIs.

## Internal/public-for-technical-reasons methods

Style/resource discovery, DOM materialization, `registerStylesAndRefresh()`, raw style getters, and similar public implementation helpers are not normal application-authoring entry points.

## Ownership

`RichTable` means **PHP owns rows, cells, column definitions, and generated table layout**.

For a Writer-authored table whose structure should remain in the template, use Writer-native table population instead.

## See also

- [Structured Content](index.md)
- [RichText](richtext.md)
- [Paragraphs & Inline Content](paragraph.md)

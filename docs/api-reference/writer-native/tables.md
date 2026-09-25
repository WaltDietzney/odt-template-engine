# Tables

A Writer-native table is designed and formatted in LibreOffice Writer. PHP supplies row data while the engine preserves the supported authored table structure.

Use this API when **Writer owns the table**. If PHP should construct the table itself, use [RichTable](../structured-content/tables.md) instead.

## Resolve a Writer table

**Recommended**

```php
public function table(string $name): TableTarget
```

```php
$table = $template->table('InvoiceLines');
```

Resolution is strict across the inspected Writer table set:

- no match throws `TargetNotFoundException`;
- multiple same-named tables throw `AmbiguousAddressableTargetException`.

Table inspection can discover tables in `content.xml` and in supported master-page header/footer content in `styles.xml`.

Population is deliberately narrower: `populate()` supports only a uniquely addressable table in `content.xml`.

Targets are identity-backed handles rather than captured DOM nodes.

## `TableTarget`

```php
name(): string
type(): string
descriptor(): TableDescriptor
populate(array $rows, array $options = []): self
```

`type()` returns `table`.

## Populate authored rows

**Recommended · bounded Writer-table population**

```php
public function populate(array $rows, array $options = []): self
```

```php
$template->table('InvoiceLines')->populate([
    ['Consulting', 3, 120.00],
    ['Review', 1, 90.00],
]);
```

The complete data contract is:

```php
list<list<scalar|null>>
```

Both the outer array and every row must be PHP lists. Every data row must contain exactly the number of writable cells required by the authored mutable row structure.

Cell values may be scalar or `null`:

- `null` is written as an empty string;
- other scalar values are cast to strings;
- string values may not contain CR, LF, or TAB.

This is scalar population. It is not a general cell-content or arbitrary table-reconstruction API.

## Ownership model

The authored table supplies the structure and formatting. PHP supplies scalar row payloads.

The population operation retains the supported row/cell/paragraph/span formatting while replacing only the scalar text carrier.

This makes the ownership distinction explicit:

| Need | API |
| --- | --- |
| Writer designs the table; PHP supplies row values | `TableTarget::populate()` |
| PHP constructs the table and its structure | `RichTable` |

## Authored row model

Writer tables can contain header rows and ordinary rows.

Header rows are preserved and do not consume supplied data.

Ordinary authored rows form the source model for population. Unless retained with `keepRows`, they are the mutable data region.

Population can therefore:

- shrink the mutable region when fewer data rows are supplied;
- replace authored mutable rows with populated copies;
- grow beyond the authored mutable-row count by cloning an authored mutable source row.

Growth still uses Writer-authored structure; PHP does not invent a new row layout.

## Options

The complete 1.0 option vocabulary is:

```php
[
    'keepRows' => list<int>,
]
```

No other option keys are accepted.

### `keepRows`

| Property | Contract |
| --- | --- |
| Type | `list<int>` |
| Default | `[]` |
| Values | zero-based ordinary source-row indices |
| Meaning | preserve selected authored ordinary rows instead of treating them as mutable data rows |
| Validation | each index must exist in the original ordinary source-row set |

Example:

```php
$template->table('InvoiceLines')->populate(
    [
        ['Consulting', 3, 120.00],
        ['Review', 1, 90.00],
    ],
    [
        'keepRows' => [0, 4],
    ]
);
```

Indices refer to the **original authored ordinary rows**, not to generated rows or current row positions after earlier population.

Duplicate indices normalize to one retained source index.

Kept rows retain their authored content and position. They consume no data row.

## Repeated population

The first population captures the authored ordinary-row baseline for that table in the current Working Document lifecycle.

Later calls deliberately reuse that captured source model. Generated rows do not become the source for the next call.

Consequently:

- repeated population does not accumulate previously generated rows;
- a later call can grow or shrink again from the authored baseline;
- changing `keepRows` between calls still addresses the original authored ordinary-row indices.

```php
$table = $template->table('InvoiceLines');

$table->populate([
    ['A', 1],
    ['B', 2],
    ['C', 3],
]);

$table->populate([
    ['Replacement', 4],
]);
```

The second call is not an edit of the three generated rows from the first call. It is another population from the captured authored source model.

A document reset through `load()` starts a new Working Document lifecycle.

## Supported mutable row structure

The current 1.0 population contract deliberately supports simple Writer-authored scalar rows.

A mutable source row must satisfy all of the following:

- it is not a repeated row;
- its direct writable children are ordinary `table:table-cell` elements;
- it contains no covered/non-cell mutable topology;
- mutable cells are not repeated or row/column-spanned;
- mutable cells are not protected;
- mutable cells do not carry formula/non-string typed value payloads;
- each mutable cell contains exactly one simple Writer paragraph;
- each paragraph has one unambiguous scalar text carrier.

The scalar carrier may be:

- direct paragraph text; or
- one optional styled `text:span` containing the scalar text.

The styled-span form preserves the authored span and formatting while changing its text.

Fragmented or multiple formatted text runs are intentionally outside this bounded population contract.

All mutable authored source rows must have compatible structure and writable-cell count.

Non-empty input requires at least one mutable source row.

## Empty data

An empty data list is valid.

Mutable ordinary rows are removed from the populated result while header rows and rows selected through `keepRows` remain.

If the table has no mutable source rows, empty data is a no-op apart from establishing the population baseline; non-empty data fails because there is no authored mutable row available as a population source.

## Atomic validation and failure

Population is staged before it replaces the live table children.

Contract failures throw:

```php
NativeTablePopulationException

tableName(): string
operation(): string
reason(): string
```

The current operation name is `populate`.

Typical failure reasons include:

- invalid row/data shape;
- wrong cell count;
- unsupported option;
- invalid `keepRows` index;
- unsupported Writer row/cell topology;
- incompatible mutable source rows;
- attempts to populate a table outside `content.xml`.

Validation/mutation failures do not intentionally leave a partially populated table.

Strict identity errors remain the common target-resolution exceptions rather than `NativeTablePopulationException`.

## Descriptor

`TableDescriptor` is documented under [Inspection](inspection.md):

```php
name(): string
documentPart(): string
rowCount(): int
columnCount(): ?int
containingSection(): ?string
diagnostics(): array
toArray(): array
```

A descriptor can represent a table that is inspectable but not populatable—for example, a table in supported master-page content.

## Lifecycle

Population mutates the current Working Document immediately. It does not require `render()`.

The populated table survives `save()`. Calling `load()` resets the Working Document to the original template and discards current population state.

## See also

- [Inspection](inspection.md)
- [Writer-native Objects](index.md)
- [Structured Tables & Cells](../structured-content/tables.md)

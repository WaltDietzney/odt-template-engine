# SR-07H — Relative Table-Column Width Contract Correction

Status: FINAL GO FOR SR-07H-FIX-1

Base: `develop` after SR-07G merge `65c93807c080950ec1530e9b98661d7a3c3289a8`

Corrects: `docs/architecture/SR-07_SEMANTIC_TABLE_STYLE_REQUIREMENTS_CHANGE_CONTRACT.md`

Evidence context: SR-07H visual regression, Sample 20 (`sample_20_tableRatio.php`), current and historical ODT/XML comparison, and the legacy `RichTable::setColumnWidthRatios()` implementation.

## 1. Reason for correction

The original SR-07 contract classified ratio-based columns together with `table:number-columns-repeated` as structural concerns and therefore excluded ratio widths from semantic `table-column` requirements.

SR-07H visual verification showed that this classification is semantically incorrect.

The public API:

```php
$table->setColumnWidthRatios([2, 1, 1]);
```

expresses relative **column width** semantics. Sample 20 explicitly describes and demonstrates a 2:1:1 column width ratio.

The legacy implementation introduced in commit `0488ccefaffef6c517f2ccc3969ed9b80cac1fdd` represents those ratios through a virtual 12-column grid:

```text
2 : 1 : 1
    ↓
6 : 3 : 3 repeated virtual columns
    ↓
cell colspans 6 / 3 / 3
```

That representation is structural, but it does not encode the intended relative physical widths. `table:number-columns-repeated` repeats a column definition; it does not itself express a relative column width.

The visual baseline therefore preserved a pre-existing semantic defect. SR-07 did not introduce that defect, but SR-07H exposed it before final closeout.

## 2. Corrected semantic distinction

SR-07 must distinguish three independent concepts:

```text
EXPLICIT COLUMN WIDTH
    setColumnWidths(['2cm', '10cm'])
    -> table-column style semantics
    -> style:column-width

RELATIVE COLUMN WIDTH
    setColumnWidthRatios([2, 1, 1])
    -> table-column style semantics
    -> style:rel-column-width

REPEATED COLUMNS
    repeated equivalent column definitions
    -> table structure
    -> table:number-columns-repeated
```

A value does not become structural merely because a legacy implementation represented it through spans or repeated columns.

The API meaning is authoritative for the semantic model: `setColumnWidthRatios()` describes relative widths and therefore belongs to the `table-column` style family.

## 3. Corrected `table-column` family contract

### 3.1 Explicit widths

The existing SR-07 contract remains unchanged for explicit widths:

```text
family: table-column
scope: automatic
document part: content.xml
property group: style:table-column-properties
property: style:column-width
```

The explicit width value is preserved exactly.

### 3.2 Relative widths

A ratio supplied through `RichTable::setColumnWidthRatios()` must be represented as semantic `table-column` style definitions:

```text
family: table-column
scope: automatic
document part: content.xml
property group: style:table-column-properties
property: style:rel-column-width
```

For:

```php
$table->setColumnWidthRatios([2, 1, 1]);
```

the semantic result is three actual table columns with equivalent relative width proportions:

```text
2* / 1* / 1*
```

The implementation may use an ODF-equivalent normalized star representation if required by LibreOffice/ODF interoperability, but it must preserve the caller-visible ratio. It must not depend on absolute page/table width calculation merely to express a relative ratio.

### 3.3 Structure

Each ratio entry represents one actual logical table column.

Ratio semantics must not be implemented by fabricating a virtual column grid solely to simulate width. In particular, `setColumnWidthRatios()` must not require artificial cell colspans such as 6/3/3 for a 2:1:1 ratio.

`table:number-columns-repeated` remains valid structural ODF when genuinely representing repeated equivalent columns. It is not the semantic carrier for relative width ratios.

Existing real colspan/rowspan behavior remains structural and outside this correction.

## 4. Ownership and materialization

Relative-width definitions use the same document-local semantic ownership pipeline already approved and implemented for explicit `table-column` widths.

`RichTable` owns the generated relative `table-column` requirements.

`StyleRequirement` / `StyleContext` remain authoritative for normal structured insertion.

`StyleRequirementMaterializer` materializes the definitions into `content.xml` `office:automatic-styles`.

`RichTable::toDomNode()` emits the table structure and corresponding column style references. It must not become a second authoritative definition producer.

No static ratio registry, global current-document pointer, new context object, or duplicated mutable ownership is introduced.

## 5. Naming and identity

SR-07H-FIX-1 does not redesign table-column style naming.

The existing generated positional naming model used by semantic table-column ownership remains the starting point unless implementation evidence shows that the same names cannot safely represent the corrected semantics.

Semantic identity remains:

```text
family + name + scope + documentPart
```

Existing target definitions remain authoritative according to the established semantic materializer rules. No automatic collision-renaming strategy is authorized by this correction.

## 6. Compatibility contract

The public API remains unchanged:

```php
RichTable::setColumnWidthRatios(array $ratios)
```

The intended meaning remains relative column widths.

This correction deliberately changes generated ODF/layout where the legacy virtual-column representation failed to implement that meaning. That is an approved semantic bug fix, not an attempt to preserve the incorrect virtual-grid output byte-for-byte.

Existing explicit-width behavior, real colspan/rowspan behavior, header rows, table naming, table style references, cell styles, row styles, and nested content semantics are not changed by this correction.

Direct compatibility APIs unrelated to ratio widths remain available.

## 7. SR-07H-FIX-1 implementation boundary

The authorized implementation slice is deliberately narrow.

It may:

- make `RichTable::setColumnWidthRatios()` produce semantic automatic/content-local `table-column` requirements;
- map each ratio to `style:rel-column-width`;
- emit one actual logical `table:table-column` per ratio entry with the corresponding style reference;
- remove artificial ratio-derived cell colspans from the normal ratio path;
- add focused characterization, semantic ownership, materialization, lifecycle, and Sample-20-oriented tests;
- update only documentation directly necessary to record this corrected behavior.

It must not:

- redesign `setColumnWidths()`;
- introduce absolute width calculation for relative ratios unless evidence proves ODF interoperability requires it and contract review is reopened first;
- redesign genuine colspan/rowspan semantics;
- redesign repeated-column APIs or invent a new repeated-column public API;
- alter table-row semantics;
- address the separate `min-row-height` visual failure;
- introduce new process-global style state;
- perform unrelated table cleanup or refactoring.

If correct relative widths cannot be represented through `style:rel-column-width` in the existing semantic table-column model, implementation stops and returns to evidence/contract review.

## 8. Required tests and evidence

SR-07H-FIX-1 must prove at least:

1. `setColumnWidthRatios([2, 1, 1])` produces exactly three semantic `table-column` definitions.
2. Their property group is `style:table-column-properties`.
3. Their relative widths preserve 2:1:1 semantics.
4. Definitions are automatic and content-local.
5. Structural table output contains three corresponding logical columns, not a 6/3/3 virtual grid.
6. Ratio-derived artificial cell colspans are absent.
7. Existing explicit `setColumnWidths()` behavior remains green.
8. Existing genuine cell colspan/rowspan behavior remains green.
9. Repeated save/materialization does not duplicate the semantic ratio-column definitions.
10. Sample 20 renders visually as a 2:1:1 table in LibreOffice.

The visual acceptance criterion is semantic rather than pixel identity with the archived SR-07 baseline, because that baseline preserves the defective virtual-grid behavior.

Sample 20 therefore becomes an intentional baseline correction candidate after human visual review.

## 9. Relationship to the original SR-07 contract

This document supersedes only the original statements that:

- ratio-based columns remain purely structural;
- relative width semantics are outside SR-07;
- SR-07D/H must preserve the virtual-column ratio layout behavior.

All other SR-07 family, ownership, compatibility, lifecycle, and non-goal decisions remain in force unless separately corrected.

In particular, this correction does not modify the independent SR-07E table-row contract. The unresolved LibreOffice behavior of `min-row-height` remains a separate SR-07H investigation and must not be mixed into SR-07H-FIX-1.

## 10. Decision

**FINAL GO FOR SR-07H-FIX-1.**

Relative column widths are semantic `table-column` style requirements. `table:number-columns-repeated` is structural repetition and is not a substitute for relative width semantics.

SR-07H-FIX-1 may now implement the corrected ratio semantics within the narrow boundary above. Final SR-07 closeout remains blocked until both the ratio-width correction and the separate table-row visual investigation are completed and reviewed.

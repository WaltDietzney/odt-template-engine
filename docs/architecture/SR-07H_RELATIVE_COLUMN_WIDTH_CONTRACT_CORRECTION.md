# SR-07H — Relative Table-Column Width Contract Correction

Status: FINAL GO FOR SR-07H-FIX-1 REVISION

Base: `develop` after SR-07G merge `65c93807c080950ec1530e9b98661d7a3c3289a8`

Corrects: `docs/architecture/SR-07_SEMANTIC_TABLE_STYLE_REQUIREMENTS_CHANGE_CONTRACT.md`

Evidence context: SR-07H visual regression, Sample 20 (`sample_20_tableRatio.php`), current and historical ODT/XML comparison, the legacy `RichTable::setColumnWidthRatios()` implementation, focused LibreOffice interoperability experiments A-D, and LibreOffice Writer source inspection.

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

The first SR-07H correction correctly reclassified ratios as `table-column` style semantics, but its example materialization `2* / 1* / 1*` proved insufficient for LibreOffice Writer interoperability. Focused experiments established that normalized Writer-compatible relative widths are required in this context.

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

Each ratio entry represents one actual logical table column.

The caller-facing ratio remains a semantic weight vector. It is **not** written literally as small ODF star values. For LibreOffice Writer interoperability, the weights are materialized into Writer's 16-bit relative table-width space based on `USHRT_MAX = 65535`.

For positive integer ratios `r[0..n-1]`:

```text
sum  = r[0] + ... + r[n-1]
unit = floor(65535 / sum)

width[i] = unit * r[i]        for i < n - 1
width[n-1] = 65535 - sum(width[0..n-2])
```

The final logical column receives the integer-division remainder so that the materialized widths sum exactly to 65535. This follows the Writer table-width model observed in LibreOffice source, where default variable table widths use `USHRT_MAX`, integer division, and assign the remainder to the final box.

Therefore:

```php
$table->setColumnWidthRatios([2, 1, 1]);
```

materializes as:

```text
32766* / 16383* / 16386*
```

and not as literal:

```text
2* / 1* / 1*
```

The normalized representation preserves the caller-visible 2:1:1 semantics within the finite Writer width space.

This normalization is a LibreOffice interoperability/materialization rule, not a claim that ODF itself mandates a 65535 total.

### 3.3 Evidence for normalized materialization

SR-07H isolated the relevant behavior through focused ODT experiments:

```text
FIX-1 initial
    rel widths 2* / 1* / 1*
    -> LibreOffice rendered approximately 1:1:1

Experiment A
    + explicit table width
    -> still approximately 1:1:1

Experiment B
    + absolute column-width hints
    -> still approximately 1:1:1

Experiment C
    normalized rel widths 32766* / 16383* / 16386*
    + table/absolute width hints retained
    -> rendered 2:1:1

Experiment D
    normalized rel widths only
    no explicit table width
    no absolute column-width hints
    -> rendered 2:1:1
```

Experiment D establishes the minimal requirement for the current ratio path: normalized relative star widths are sufficient. Absolute page width, table width, and absolute column-width calculation are not required merely to express a relative ratio.

LibreOffice Writer source inspection independently shows that its internal variable table-width model uses `USHRT_MAX = 65535` and distributes integer-division remainder to the final box. This reproduces the working 2:1:1 oracle values exactly.

### 3.4 Structure

Ratio semantics must not be implemented by fabricating a virtual column grid solely to simulate width. In particular, `setColumnWidthRatios()` must not require artificial cell colspans such as 6/3/3 for a 2:1:1 ratio.

`table:number-columns-repeated` remains valid structural ODF when genuinely representing repeated equivalent columns. It is not the semantic carrier for relative width ratios.

Existing real colspan/rowspan behavior remains structural and outside this correction.

## 4. Ownership and materialization

Relative-width definitions use the same document-local semantic ownership pipeline already approved and implemented for explicit `table-column` widths.

`RichTable` owns the generated relative `table-column` requirements.

`StyleRequirement` / `StyleContext` remain authoritative for normal structured insertion.

`StyleRequirementMaterializer` materializes the definitions into `content.xml` `office:automatic-styles`.

`RichTable::toDomNode()` emits the table structure and corresponding column style references. It must not become a second authoritative definition producer.

The ratio-to-Writer-width normalization belongs to the ratio/table-column materialization path and must be deterministic. It must not introduce document-global mutable state.

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

For SR-07H-FIX-1, supported ratio inputs remain positive integer weights consistent with the existing Sample-20/API usage. This correction does not authorize new float/fractional ratio semantics or a broader validation redesign. If existing behavior accepts additional forms, they must not be silently redefined as part of this slice; implementation evidence must be reviewed before changing compatibility behavior.

This correction deliberately changes generated ODF/layout where the legacy virtual-column representation failed to implement the intended meaning. That is an approved semantic bug fix, not an attempt to preserve the incorrect virtual-grid output byte-for-byte.

Existing explicit-width behavior, real colspan/rowspan behavior, header rows, table naming, table style references, cell styles, row styles, and nested content semantics are not changed by this correction.

Direct compatibility APIs unrelated to ratio widths remain available.

## 7. SR-07H-FIX-1 revision implementation boundary

The authorized implementation slice is deliberately narrow.

It may:

- make `RichTable::setColumnWidthRatios()` produce semantic automatic/content-local `table-column` requirements;
- normalize positive integer ratio weights into the Writer-compatible 65535 relative-width space using the deterministic final-column remainder rule above;
- map the normalized values to `style:rel-column-width`;
- emit one actual logical `table:table-column` per ratio entry with the corresponding style reference;
- remove artificial ratio-derived cell colspans from the normal ratio path;
- add focused characterization, semantic ownership, normalization, materialization, lifecycle, and Sample-20-oriented tests;
- update only documentation directly necessary to record this corrected behavior.

It must not:

- redesign `setColumnWidths()`;
- add explicit table width or absolute column-width calculation to make ratios work;
- introduce a new table-width public API;
- introduce float/fractional ratio semantics without separate evidence and contract review;
- redesign genuine colspan/rowspan semantics;
- redesign repeated-column APIs or invent a new repeated-column public API;
- alter table-row semantics;
- address the separate `min-row-height` visual failure;
- introduce new process-global style state;
- perform unrelated table cleanup or refactoring.

The broader relationship between table width, absolute column width, percentage width, and relative column ratios is valuable future architecture evidence but remains outside SR-07H-FIX-1.

## 8. Required tests and evidence

SR-07H-FIX-1 revision must prove at least:

1. `setColumnWidthRatios([2, 1, 1])` produces exactly three semantic `table-column` definitions.
2. Their property group is `style:table-column-properties`.
3. `[2, 1, 1]` materializes deterministically as `32766* / 16383* / 16386*`.
4. The normalized values sum exactly to 65535.
5. A ratio set with a non-zero division remainder proves that the remainder is assigned to the final logical column.
6. Definitions are automatic and content-local.
7. Structural table output contains three corresponding logical columns, not a 6/3/3 virtual grid.
8. Ratio-derived artificial cell colspans are absent.
9. Existing explicit `setColumnWidths()` behavior remains green.
10. Existing genuine cell colspan/rowspan behavior remains green.
11. Repeated save/materialization does not duplicate the semantic ratio-column definitions.
12. Sample 20 renders visually as a 2:1:1 table in LibreOffice using normalized relative widths without requiring explicit table or absolute column widths.

The visual acceptance criterion is semantic rather than pixel identity with the archived SR-07 baseline, because that baseline preserves the defective virtual-grid behavior.

Sample 20 therefore becomes an intentional baseline correction candidate after human visual review.

## 9. Relationship to the original SR-07 contract and first correction

This document supersedes the original SR-07 statements that:

- ratio-based columns remain purely structural;
- relative width semantics are outside SR-07;
- SR-07D/H must preserve the virtual-column ratio layout behavior.

It also supersedes the first SR-07H correction's illustrative assumption that literal small star values such as `2* / 1* / 1*` are an adequate LibreOffice materialization. The semantic ratio remains 2:1:1; only its interoperable ODF representation is corrected.

All other SR-07 family, ownership, compatibility, lifecycle, and non-goal decisions remain in force unless separately corrected.

In particular, this correction does not modify the independent SR-07E table-row contract. The unresolved LibreOffice behavior of `min-row-height` remains a separate SR-07H investigation and must not be mixed into SR-07H-FIX-1.

## 10. Future architecture note: table width

The LibreOffice investigation exposed a useful distinction for future work:

```text
TABLE WIDTH
    automatic / absolute / percentage-relative

COLUMN WIDTH
    absolute style:column-width
    relative style:rel-column-width

COLUMN STRUCTURE
    actual/repeated columns and genuine spans
```

SR-07H-FIX-1 records this distinction as evidence only. It does not introduce or decide a new table-width API. Any future table-width work should start from ODF semantics and LibreOffice Writer's distinction between base width, actual width, and relative column widths rather than coupling table width to the ratio normalization implemented here.

## 11. Decision

**FINAL GO FOR SR-07H-FIX-1 REVISION.**

Relative column widths are semantic `table-column` style requirements. For LibreOffice Writer interoperability, positive integer ratio weights are materialized deterministically into the 65535 Writer relative-width space, with integer-division remainder assigned to the final logical column. `table:number-columns-repeated` remains structural repetition and is not a substitute for relative width semantics.

SR-07H-FIX-1 may now revise the existing implementation within the narrow boundary above. Final SR-07 closeout remains blocked until both the ratio-width correction and the separate table-row visual investigation are completed and reviewed.

# TABLE-LAYOUT-01B — Architecture Synthesis / Change-Contract Preparation

Status: PROPOSED ARCHITECTURE SYNTHESIS / PRE-CONTRACT

Parent milestone: `TABLE-LAYOUT-01`

Predecessor: `TABLE_LAYOUT_01A_CLOSEOUT.md`

## 1. Purpose

TABLE-LAYOUT-01B translates the completed TABLE-LAYOUT-01A evidence and characterization into the smallest compatible semantic design that can be turned into a Change Contract.

This phase does **not** authorize production implementation. Its purpose is to decide what TABLE-LAYOUT-01 should mean at the public/semantic level, what existing behavior must remain untouched, which small additions are justified, and which tempting expansions remain out of scope.

The synthesis is intentionally cumulative. It builds on:

- the completed SR-07 semantic ownership model for `table`, `table-column`, `table-row`, and `table-cell`;
- the SR-07H LibreOffice Writer interoperability rule for relative column widths;
- the ODF/Writer evidence collected in TABLE-LAYOUT-01A;
- the element-level and lifecycle characterization tests added in TABLE-LAYOUT-01A;
- the version-1.0 milestone scope in `docs/FUTURE_DEVELOPMENT.md`.

## 2. Governing principle

TABLE-LAYOUT-01 adopts the following architecture rule:

> The engine expresses native ODF table geometry semantics and preserves compatible authored geometry; LibreOffice/Writer resolves, normalizes, and renders the final physical layout.

The engine therefore does not attempt to reproduce Writer's table layout algorithm or to precompute all Writer-generated helper geometry.

This has three immediate consequences:

1. semantic authoring intent is more important than byte-identical Writer serialization;
2. Writer-generated calculated values are evidence of interoperability behavior, not automatically requirements for engine generation;
3. table, column, row, and cell geometry remain separate semantic concerns even when Writer physically coordinates them.

## 3. Existing architecture that remains authoritative

TABLE-LAYOUT-01 must not redesign the following completed behavior.

### 3.1 Table-family ownership

Table-level geometry belongs to the `table` style family and materializes under:

```xml
<style:table-properties .../>
```

The existing `RichTable::setStyle()` native pass-through remains a compatibility escape hatch.

### 3.2 Absolute column width

`RichTable::setColumnWidths()` remains the established semantic path for:

```xml
<style:table-column-properties style:column-width="..."/>
```

No new competing absolute-column model is required.

### 3.3 Relative column width

`RichTable::setColumnWidthRatios()` remains the established semantic path for:

```xml
<style:table-column-properties style:rel-column-width="...*"/>
```

For positive integer ratios, the current Writer-compatible normalization remains authoritative:

```text
USHRT_MAX = 65535 = 2^16 - 1
```

Example:

```text
[2, 1, 1]
    ->
32766* / 16383* / 16386*
```

This is an interoperability rule derived from LibreOffice Writer behavior/source investigation, not an ODF normative constant.

### 3.4 Minimum row height

The established row-style option:

```php
$table->addRow($cells, ['min-row-height' => '2cm']);
```

continues to mean:

```xml
<style:table-row-properties style:min-row-height="2cm"/>
```

This behavior is already characterized and must remain backward-compatible.

### 3.5 Native cell-style escape hatch

The existing cell style path continues to accept native cell properties such as:

```php
new RichTableCell('value', ['style:vertical-align' => 'middle']);
```

This compatibility path must remain valid even if a convenience mapping is added.

## 4. Semantic gaps to close for TABLE-LAYOUT-01

The evidence identifies three genuine missing semantic capabilities and one bounded table-width design question.

### 4.1 Explicit table width

ODF owns absolute table width through:

```xml
<style:table-properties style:width="10cm"/>
```

Although `RichTable::setStyle()` can already pass this native property through, TABLE-LAYOUT-01 should provide an explicit semantic authoring surface for absolute table width.

Proposed public intent:

```php
$table->setWidth('10cm');
```

Proposed semantics:

```text
setWidth('10cm')
    -> table-family semantic style requirement
    -> style:table-properties
    -> style:width="10cm"
```

The method should return `self` and behave as an element convenience API rather than as a second style subsystem.

`setWidth()` should not calculate or alter column widths. Table width and column widths remain independent authoring concerns.

### 4.2 Relative table width

ODF owns percentage table width through:

```xml
<style:table-properties style:rel-width="60%"/>
```

Writer may save a relative table together with a calculated absolute `style:width`, but TABLE-LAYOUT-01A found no evidence that the engine must generate that calculated absolute value itself.

Proposed public intent:

```php
$table->setRelativeWidth('60%');
```

Proposed semantics:

```text
setRelativeWidth('60%')
    -> table-family semantic style requirement
    -> style:table-properties
    -> style:rel-width="60%"
```

The engine should **not** calculate a companion `style:width` in the first implementation slice. Writer remains responsible for resolving the physical width from the containing area.

Absolute and relative table-width intent should be mutually exclusive at the convenience-API level. Calling `setWidth()` after `setRelativeWidth()`, or vice versa, should replace the previous convenience width intent rather than authoring two contradictory requests.

This replacement rule applies only to the convenience API. Existing raw/native `setStyle()` behavior is not retroactively normalized or rewritten.

### 4.3 Exact/fixed row height

TABLE-LAYOUT-01A established the Writer/ODF distinction:

```text
fixed height      -> style:row-height
minimum height    -> style:min-row-height
```

The existing row style array is already the semantic location for minimum row height. The smallest compatible extension is therefore to accept:

```php
$table->addRow($cells, ['row-height' => '2cm']);
```

with native output:

```xml
<style:table-row-properties style:row-height="2cm"/>
```

No separate row object or row-layout service is justified for this milestone.

`row-height` and `min-row-height` express different semantics and should not be silently translated into each other.

For the first implementation contract, a row should not author both convenience properties simultaneously. If both are supplied, the implementation should reject the ambiguous request rather than invent precedence.

The milestone should not introduce `style:use-optimal-row-height` without separate evidence.

### 4.4 Vertical cell alignment

The native property is:

```xml
<style:table-cell-properties style:vertical-align="middle"/>
```

The existing raw/native form already works. The smallest public consistency addition is to extend `StyleMapper::mapTableCellStyleOptions()` so that:

```php
new RichTableCell('value', ['vertical-align' => 'middle']);
```

maps to:

```text
style:vertical-align="middle"
```

Supported semantic values should follow ODF values relevant to text-table cells:

```text
top
middle
bottom
automatic
```

Writer's observed empty serialization for the top/default UI state should **not** become a public semantic value. The API should express `top` when the caller explicitly requests top alignment; omission remains the way to request no explicit override.

No separate fluent methods such as `alignTop()`, `alignMiddle()`, and `alignBottom()` are required for the 1.0 blocker. They may be considered later if API ergonomics justify them.

## 5. Table width and alignment remain separate

Writer evidence shows that width and placement are distinct:

```text
style:width / style:rel-width
    !=
table:align
```

TABLE-LAYOUT-01 should not make `setWidth()` implicitly left-align, center, or margin-align a table.

Likewise `setRelativeWidth()` should not silently choose placement.

The milestone scope in `FUTURE_DEVELOPMENT.md` requires explicit table width, not a broad table-placement API redesign. Existing native style access remains available for alignment where needed.

A future convenience alignment API can be considered separately if a concrete user-facing requirement justifies it.

## 6. Interaction rules

The Change Contract should encode the following interaction semantics.

### 6.1 Table width versus column width

```text
TABLE WIDTH
    !=
ABSOLUTE COLUMN WIDTH
    !=
RELATIVE COLUMN WIDTH
```

Therefore:

- `setWidth()` must not rewrite `setColumnWidths()` values;
- `setRelativeWidth()` must not rewrite `setColumnWidthRatios()` values;
- column APIs must not synthesize table width;
- the engine need not validate that absolute column widths sum exactly to an explicitly authored table width in this milestone.

Writer may normalize or coordinate physical geometry during save/reopen.

### 6.2 Absolute versus relative column APIs

Existing behavior remains authoritative: relative ratios take the established relative-column path and absolute widths take the absolute-column path. TABLE-LAYOUT-01 should not add a third mixed column-geometry model.

### 6.3 Convenience table width versus raw native style

A dedicated width method should integrate with `RichTable`'s existing element-owned style state rather than create a parallel style store.

The implementation must preserve unrelated table style properties already present on the element.

For example, adding an absolute width must not discard an existing native alignment property:

```php
$table->setStyle(['table:align' => 'center']);
$table->setWidth('10cm');
```

should preserve the alignment while adding/replacing the convenience width property.

Conversely, existing `setStyle()` replacement semantics are compatibility behavior and should not be silently changed merely to make all call orders commutative. The Change Contract must state the supported interaction/order explicitly after implementation-level inspection.

### 6.4 Exact versus minimum row height

These are mutually distinct semantic requests.

The convenience row path should support one or the other per row. Ambiguous simultaneous authoring should fail early rather than depend on Writer precedence.

### 6.5 Vertical alignment versus paragraph alignment

Cell vertical alignment and paragraph horizontal alignment remain independent:

```text
vertical-align
    -> table-cell style family

text-align
    -> paragraph semantics
```

Adding vertical alignment must not change the existing paragraph/text splitting behavior of `RichTableCell`.

## 7. Validation philosophy

TABLE-LAYOUT-01 should validate semantic API inputs narrowly without becoming a general CSS/ODF validator.

The Change Contract should require:

- non-empty absolute table-width values for `setWidth()`;
- percentage-shaped input for `setRelativeWidth()`;
- non-empty row-height values for row-height convenience options;
- only `top`, `middle`, `bottom`, or `automatic` for unprefixed convenience `vertical-align`;
- preservation of the existing raw/native escape hatches for advanced callers.

Length-unit normalization or conversion is not required. The engine may preserve valid caller-supplied ODF-compatible length strings rather than converting all dimensions into a canonical unit.

## 8. Compatibility contract direction

The implementation must preserve all behavior characterized in TABLE-LAYOUT-01A except where the Change Contract explicitly introduces a new previously unsupported convenience form.

Specifically:

```text
PRESERVE
    setColumnWidths()
    setColumnWidthRatios()
    65535 Writer normalization
    min-row-height
    raw/native table geometry
    raw/native style:vertical-align
    repeated save/materialization stability

ADD
    explicit absolute table-width convenience
    explicit relative table-width convenience
    exact row-height convenience
    unprefixed vertical-align convenience
```

No existing public method needs to be removed or renamed.

## 9. Explicit non-goals

TABLE-LAYOUT-01 must not expand into:

- a PHP table layout engine;
- automatic column measurement from text content;
- page-width or page-margin computation;
- automatic distribution of unspecified columns;
- automatic reconciliation of mismatched table width and absolute column totals;
- generic table auto-fit algorithms;
- row pagination rules;
- merged-cell redesign;
- table-style ownership redesign;
- a new style context or global style registry;
- broad table alignment/placement redesign;
- frame positioning;
- template authoring UX;
- page-style authoring;
- `STYLE-API-02` or `STYLE-CONTEXT-01` reopening.

## 10. Proposed implementation slices

The eventual Change Contract should allow small, independently reviewable slices.

### Slice B1 — Table-width semantic convenience

- introduce absolute table-width convenience;
- introduce relative table-width convenience;
- preserve unrelated table-style properties;
- make absolute/relative convenience intent mutually exclusive;
- add element and materialization tests;
- verify coexistence with existing absolute/relative column APIs.

### Slice B2 — Exact row height

- extend the existing row option path with `row-height`;
- preserve `min-row-height` unchanged;
- reject simultaneous convenience `row-height` + `min-row-height`;
- add element/materialization tests;
- perform Writer visual regression with content that exceeds the requested fixed height.

### Slice B3 — Vertical cell alignment

- add unprefixed `vertical-align` mapping;
- retain native `style:vertical-align` pass-through;
- keep paragraph horizontal alignment separate;
- test top/middle/bottom/automatic mapping;
- perform Writer visual regression on a visibly tall row.

### Slice B4 — Integration/preflight

- combined table width + column width/ratio cases;
- repeated save lifecycle;
- save/reopen inspection;
- focused existing table integration tests;
- PublicSampleSmokeTest;
- full `composer test`;
- PHP lint for `src/` and `tests/`;
- `git diff --check`;
- manual LibreOffice regression.

## 11. Open points before freezing the Change Contract

The architecture is sufficiently constrained that only a few implementation-level questions remain before TABLE-LAYOUT-01C can freeze the contract:

1. How should dedicated table-width setters update `RichTable::$tableStyleOptions` while preserving the current documented/reachable `setStyle()` and `setTableStyleName()` lifecycle?
2. Should convenience width setters clear only the competing width key (`style:width` versus `style:rel-width`) or also reject a conflicting raw/native width already present?
3. What exception type/message convention should be used for ambiguous simultaneous `row-height` and `min-row-height` input, based on existing element validation conventions?
4. Should percentage validation for relative table width accept only strings such as `"60%"`, or also integer/float convenience inputs that are normalized to percentages?

These are bounded contract-shaping questions. They do not require further Writer/ODF research unless implementation inspection reveals a contradiction.

## 12. Proposed contract direction

Subject to resolving the bounded points above, TABLE-LAYOUT-01C should freeze the following public behavior:

```php
$table->setWidth('10cm');
$table->setRelativeWidth('60%');

$table->setColumnWidths(['4cm', '8cm']);
$table->setColumnWidthRatios([2, 1, 1]);

$table->addRow($cells, ['row-height' => '2cm']);
$table->addRow($cells, ['min-row-height' => '2cm']);

$cell->setStyle(['vertical-align' => 'middle']);
```

with the native ownership model:

```text
Table absolute width       -> style:width
Table relative width       -> style:rel-width
Column absolute width      -> style:column-width
Column relative weight     -> style:rel-column-width
Row exact height           -> style:row-height
Row minimum height         -> style:min-row-height
Cell vertical alignment    -> style:vertical-align
```

The 65535 relative-column normalization remains untouched.

## 13. Exit criterion for TABLE-LAYOUT-01B

TABLE-LAYOUT-01B is ready to close when:

1. the proposed semantic additions are accepted or adjusted;
2. the four bounded implementation-level questions are resolved from current code/API conventions;
3. no conflict with existing SR-07 or TABLE-LAYOUT-01A characterization remains;
4. the result can be expressed as a precise TABLE-LAYOUT-01C Change Contract without further architectural invention.

No production implementation is authorized by this document.

# TABLE-LAYOUT-01B — Code Inspection Resolution

Status: RESOLVED / READY FOR CHANGE CONTRACT

Parent: `TABLE_LAYOUT_01B_ARCHITECTURE_SYNTHESIS.md`

## 1. Purpose

This note resolves the four bounded implementation-level questions left open by TABLE-LAYOUT-01B using the current `architecture/table-layout-01` code as the source of truth.

No production implementation is authorized here.

## 2. Current `RichTable` style lifecycle

`RichTable` currently has two mutually different table-style modes:

```text
setTableStyleName('NamedStyle')
    -> reference mode
    -> tableStyleName = 'NamedStyle'
    -> tableStyleOptions = []

setStyle([...])
    -> element-owned definition mode
    -> tableStyleOptions = supplied properties
    -> tableStyleName = generated hash name
```

`setTableStyleName()` explicitly clears `tableStyleOptions`. `setStyle([])` clears both style options and style name. `setStyle([...])` replaces the complete current element-owned option array rather than merging it.

`getOwnStyleRequirements()` preserves the SR-07 distinction:

```text
tableStyleName + non-empty tableStyleOptions
    -> table definition requirement

tableStyleName + empty tableStyleOptions
    -> table reference requirement
```

A table-width convenience setter must therefore not silently convert a named-style reference into a generated definition with the same name, and must not fabricate a definition for an externally authored style.

## 3. Resolution: table-width convenience integration

### 3.1 Element-owned definition mode

`setWidth()` and `setRelativeWidth()` should operate on `tableStyleOptions` only when the table is already unstyled or in element-owned definition mode.

They should:

1. preserve unrelated table properties already present in `tableStyleOptions`;
2. remove the competing width property;
3. assign the requested width property;
4. regenerate `tableStyleName` from the resulting complete `tableStyleOptions` using the existing `StyleMapper::generateStyleName()` mechanism;
5. return `self`.

Conceptually:

```text
setWidth('10cm')
    remove style:rel-width
    set style:width = 10cm
    regenerate generated style name

setRelativeWidth('60%')
    remove style:width
    set style:rel-width = 60%
    regenerate generated style name
```

This preserves unrelated properties such as `table:align`.

### 3.2 Named-style reference mode

If `tableStyleName !== null` while `tableStyleOptions === []`, the object is in named-style reference mode. A width convenience setter must not silently mutate that state into a locally generated table definition.

The Change Contract should therefore reject `setWidth()` / `setRelativeWidth()` while a reference-only table style is active.

Recommended exception: `LogicException`, because the value may be valid but the object is in a state where element-owned geometry cannot be added without changing reference semantics.

This follows the already-established architecture rule:

```text
reference != definition != mutation
```

The caller may explicitly switch to element-owned style mode with `setStyle([...])` before using the convenience setter.

### 3.3 Existing call-order behavior retained

Calling `setTableStyleName()` after an element-owned width/style continues to clear the generated options, because that is current public behavior.

TABLE-LAYOUT-01 must not make `setTableStyleName()` merge with prior generated style properties.

## 4. Resolution: raw/native width interaction

Existing `setStyle()` replacement semantics remain unchanged.

If an element-owned raw style contains `style:width` or `style:rel-width`, a subsequent dedicated convenience width setter may deliberately replace only the width intent:

```text
setStyle([
    'table:align' => 'center',
    'style:rel-width' => '60%',
])

setWidth('10cm')

=> preserve table:align
=> remove style:rel-width
=> add style:width='10cm'
```

This is not retroactive normalization of `setStyle()`. It is the explicit later convenience call taking authority over the width concern only.

Raw `setStyle()` itself must continue to accept whatever native properties it accepts today, including both width forms simultaneously if the caller explicitly supplies them.

## 5. Resolution: row-height conflict

Current `addRow()` stores the caller-provided row style array unchanged. `getOwnStyleRequirements()` currently recognizes only `min-row-height`; `row-height` is ignored.

TABLE-LAYOUT-01 should extend this existing row-style path rather than create a row object or secondary state store.

When both are supplied:

```php
[
    'row-height' => '2cm',
    'min-row-height' => '2cm',
]
```

the call should fail before the row is appended.

Recommended exception: `InvalidArgumentException`, because the ambiguity is contained in the argument supplied to `addRow()` rather than in pre-existing object state.

The implementation should not define precedence and should not translate one semantic into the other.

For one supported height property, the emitted row requirement should contain only the corresponding native ODF property.

## 6. Resolution: relative-width input type

`RichTable`'s existing dimension APIs use strings for physical widths (`setColumnWidths()` receives string width values), and native ODF geometry is currently passed through without unit conversion.

For the first TABLE-LAYOUT-01 contract, `setRelativeWidth()` should therefore accept a string only:

```php
$table->setRelativeWidth('60%');
```

It should not also accept `60`, `60.0`, or infer percentages from unitless numeric values.

Reasons:

- avoids an unnecessary second input convention;
- keeps the API close to native ODF value semantics;
- avoids ambiguous interpretation of floats;
- stays consistent with the milestone rule not to build a general geometry normalization layer.

Validation should be narrow: trimmed non-empty percentage syntax with a numeric percentage followed by `%`. No unit conversion is required.

## 7. Additional code findings

### 7.1 Existing column setters are intentionally non-fluent today

Current methods are:

```php
public function setColumnWidths(array $widths): void
public function setColumnWidthRatios(array $ratios): void
```

TABLE-LAYOUT-01 must not opportunistically change their return types to `self`; doing so would mix API cleanup with the geometry milestone.

New width convenience methods may still return `self`, consistent with most element convenience setters, without altering existing public signatures.

### 7.2 `setColumnWidths()` has legacy first-row side effects

Besides storing table-owned absolute column widths, `setColumnWidths()` currently also calls `RichTableCell::setWidth()` on cells in the first row when present.

That cell method stores an internal `__column-width` style key. TABLE-LAYOUT-01 should not refactor or remove this compatibility behavior while adding table-level width semantics. Column geometry has already been characterized through SR-07 and is outside the new table-width implementation slice.

### 7.3 Row-style materialization is currently explicit, not generic

`hasSupportedRowStyle()` currently checks only for `min-row-height`, and `getOwnStyleRequirements()` explicitly materializes that property.

Adding fixed height therefore requires a small deliberate extension of both recognition and property selection; merely leaving `row-height` in the stored row array will not materialize it.

### 7.4 Cell vertical alignment should remain a mapper addition

`RichTableCell::setStyle()` already delegates cell-owned options to `StyleMapper::mapTableCellStyleOptions()`. Native `style:*` keys pass through, while unprefixed `vertical-align` currently disappears because no mapper case exists.

The smallest compatible change remains a single mapper case:

```text
vertical-align -> style:vertical-align
```

No new cell state or style requirement type is needed.

## 8. Contract-ready decisions

The four TABLE-LAYOUT-01B open questions are resolved as follows:

```text
1. Width setter integration
   -> merge into element-owned tableStyleOptions
   -> preserve unrelated properties
   -> remove competing width property
   -> regenerate generated style name
   -> reject reference-only named-style state

2. Raw/native conflict
   -> setStyle() behavior unchanged
   -> later convenience setter owns only width concern and may replace raw width key(s)

3. Exact + minimum row height
   -> reject in addRow()
   -> InvalidArgumentException

4. Relative table width input
   -> string percentage only, e.g. '60%'
   -> no numeric shorthand
```

The named-style rejection should use `LogicException` to preserve reference/definition separation.

## 9. Exit decision

The bounded code-level questions left by TABLE-LAYOUT-01B are resolved.

TABLE-LAYOUT-01B is ready for **TABLE-LAYOUT-01C — Change Contract**.

The Change Contract can now freeze behavior without further architecture invention, subject only to normal review of exact exception messages and focused contract tests before production implementation.

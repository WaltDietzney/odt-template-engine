# TABLE-LAYOUT-01B — Public API Design Pass

Status: PROPOSED API DESIGN / PRE-CONTRACT

Parent milestone: `TABLE-LAYOUT-01`

Related:

- `TABLE_LAYOUT_01B_ARCHITECTURE_SYNTHESIS.md`
- `TABLE_LAYOUT_01B_CURRENT_TABLE_API_INVENTORY.md`
- `TABLE_LAYOUT_01B_PUBLIC_STYLE_SEMANTICS_CORRECTION.md`
- `TABLE_LAYOUT_01B_TABLE_STYLE_SCOPE_EVIDENCE.md`
- `STYLE_API_02F_MAPPER_REGISTRY_CLEANUP_CHANGE_CONTRACT.md`

## 1. Purpose

This pass defines the preferred public authoring shape for TABLE-LAYOUT-01 before the Change Contract is written.

The governing product decision is:

> `RichTable::setTableStyle(array $options)` is the master semantic table-style authoring method. Dedicated table geometry methods are convenience methods over the same semantic state and mapping path.

The design must preserve the existing distinction between:

- named style reference;
- element-owned generated table style;
- legacy/raw native table property pass-through.

No production implementation is authorized by this document.

## 2. Current API precedent

The current code provides several relevant conventions.

### 2.1 Named style references are explicit

```php
$table->setTableStyleName('ExistingStyle');
```

means reference semantics.

It clears element-owned table options and does not itself author a definition.

This remains authoritative.

### 2.2 Existing `RichTable::setStyle()` is compatibility-sensitive

Current `setStyle(array $style)` stores the supplied array directly as element-owned table properties and generates an identity from that array.

It currently has no table-level friendly mapper.

Because existing callers and samples may pass native-prefixed keys, TABLE-LAYOUT-01 must not silently redefine this method into a new semantic vocabulary.

It remains a compatibility / advanced low-level surface unless a future separate deprecation decision is made.

### 2.3 Cell style authoring establishes the target pattern

`RichTableCell::setStyle()` already uses friendly engine-level options and maps them through `StyleMapper`.

That is the stronger precedent for the new table-level authoring API:

```text
friendly options
    -> StyleMapper
    -> element-owned semantic state
    -> StyleRequirement
    -> ODF
```

### 2.4 Element-specific names are preferred when ambiguity exists

The repository already uses names such as:

```php
setTableStyleName(...)
setParagraphStyle(...)
setParagraphStyleOptions(...)
```

This supports explicit table-specific naming where a generic term would be ambiguous.

## 3. Master method

The preferred public master method is:

```php
$table->setTableStyle([
    'width' => '15cm',
    'alignment' => 'center',
]);
```

For relative width:

```php
$table->setTableStyle([
    'relative-width' => '60%',
    'alignment' => 'center',
]);
```

### 3.1 Why `setTableStyle()`

The name is intentionally explicit.

It distinguishes:

- table-level styling from cell styling;
- table-level alignment from paragraph/text alignment;
- semantic table style authoring from named style reference via `setTableStyleName()`.

It also avoids overloading the existing compatibility-sensitive `setStyle()` method.

### 3.2 Initial friendly option vocabulary

TABLE-LAYOUT-01 should initially define only the table-level options justified by current evidence and 1.0 scope:

```text
width
relative-width
alignment
```

Their native projection is:

```text
width          -> style:width
relative-width -> style:rel-width
alignment      -> table:align
```

No broader table CSS-like vocabulary is approved by symmetry.

Additional table-family properties require separate evidence before being added.

## 4. Table mapper

The preferred mapping boundary is a new stateless mapper:

```php
StyleMapper::mapTableStyleOptions(array $options): array
```

Its initial semantic mapping is:

```text
width          -> style:width
relative-width -> style:rel-width
alignment      -> table:align
```

### 4.1 Advanced native escape hatch

For consistency with existing mappers, already-native prefixed keys may pass through when intentionally supplied.

However:

- documentation and samples should use friendly keys;
- prefixed keys are not the primary public vocabulary;
- the existing `RichTable::setStyle()` low-level path remains available for compatibility.

### 4.2 Materializer remains unaware of friendly names

The materializer must continue receiving normalized ODF property groups.

It must not learn `width`, `relative-width`, or `alignment` as application concepts.

## 5. Semantic state

The new master method and convenience methods must share **one element-owned table semantic state**.

There must not be parallel stores such as:

```text
tableStyleOptions
tableGeometryOptions
tableAlignmentOptions
```

The preferred model is:

```text
friendly table options
    -> table mapper
    -> one normalized element-owned table property set
    -> generated table style identity
    -> StyleRequirement
```

The current `tableStyleOptions` storage can remain the normalized native property store if implementation review confirms that this gives the cleanest compatibility path.

A second mutable geometry state is not justified.

## 6. Master method semantics

`setTableStyle(array $options)` should be an explicit switch to element-owned generated table-style mode.

That means:

```php
$table->setTableStyleName('NamedStyle');
$table->setTableStyle([...]);
```

is permitted because the second call explicitly selects a different authoring mode.

The second call should:

1. map the friendly options;
2. replace the current complete element-owned table-style state;
3. generate a style identity from the normalized mapped state;
4. stop using the prior named-style reference for that table.

This mirrors the existing conceptual distinction already present between reference mode and element-owned definition mode.

### 6.1 Empty array

For consistency with current element APIs, the contract should decide explicitly whether:

```php
$table->setTableStyle([]);
```

clears element-owned table styling and the style reference.

The preferred behavior is to mirror current `RichTable::setStyle([])`: clear both element-owned table properties and the table style name.

That keeps the master method deterministic.

## 7. Convenience methods

The following convenience methods are recommended:

```php
$table->setTableWidth('15cm');
$table->setRelativeTableWidth('60%');
$table->setTableAlignment('center');
```

These are not separate authoring systems.

They are concern-specific mutations of the same element-owned table style governed by `setTableStyle()`.

### 7.1 Naming

`setTableWidth()` is preferred over `setWidth()` because:

- `RichTable` contains cells and columns that also have width concepts;
- explicit naming makes generated application code easier to understand;
- it avoids ambiguity with generic element width APIs.

`setRelativeTableWidth()` is preferred over `setTableRelativeWidth()` because it reads naturally alongside `setTableWidth()` while keeping the semantic distinction explicit.

`setTableAlignment()` is preferred over `setAlignment()` because whole-table placement is distinct from:

- paragraph horizontal alignment;
- content alignment inside cells;
- cell vertical alignment.

### 7.2 Shared state behavior

Conceptually:

```php
$table->setTableStyle([
    'width' => '15cm',
    'alignment' => 'center',
]);
```

and:

```php
$table
    ->setTableWidth('15cm')
    ->setTableAlignment('center');
```

must result in the same normalized element-owned table properties and the same semantic output.

Convenience methods should preserve unrelated friendly concerns already present.

For example:

```php
$table
    ->setTableAlignment('center')
    ->setTableWidth('15cm');
```

must retain both intents.

## 8. Absolute and relative table width

Absolute and relative overall width are competing semantic concerns.

At the friendly API level:

```text
width
relative-width
```

must not remain simultaneously active through convenience calls.

Therefore:

```php
$table->setRelativeTableWidth('60%');
$table->setTableWidth('15cm');
```

should result in absolute width intent only.

Likewise the reverse call order should result in relative-width intent only.

The master array method should reject a friendly array that contains both:

```php
[
    'width' => '15cm',
    'relative-width' => '60%',
]
```

rather than invent precedence inside one declarative request.

This rule applies to friendly semantic input. Existing raw/native compatibility input is not retroactively normalized.

## 9. Table alignment

Whole-table alignment is mandatory for 1.0.

The friendly option is:

```php
'alignment' => 'center'
```

and the convenience method is:

```php
$table->setTableAlignment('center');
```

The initial accepted values should be limited to Writer/ODF semantics established in current evidence:

```text
left
center
right
margins
```

Native mapping:

```text
alignment -> table:align
```

The API must not use `text-align` for this purpose because that term is already semantically associated with paragraph/content alignment.

## 10. Named style reference interaction

Convenience methods must not silently mutate a reference-only named style into an element-owned definition.

Therefore:

```php
$table->setTableStyleName('NamedStyle');
$table->setTableWidth('15cm');
```

should fail with a state-related exception unless the caller explicitly switches to element-owned mode.

Recommended behavior:

```text
setTableStyleName(...)
    -> reference mode

setTableStyle(...)
    -> explicit switch to generated definition mode

setTableWidth(...)
setRelativeTableWidth(...)
setTableAlignment(...)
    -> mutate generated definition mode
    -> reject reference-only mode
```

A `LogicException` remains the best fit for a valid operation attempted in an incompatible object state.

This preserves:

```text
reference != definition != mutation
```

## 11. Row-height API

The row-level master surface already exists:

```php
$table->addRow($cells, $rowStyle);
```

TABLE-LAYOUT-01 should extend that semantic option array rather than introduce a new row object.

Accepted friendly keys:

```php
['row-height' => '2cm']
['min-row-height' => '2cm']
```

Native mapping:

```text
row-height     -> style:row-height
min-row-height -> style:min-row-height
```

If both friendly keys are supplied in one row style array, the call should reject the contradictory request with `InvalidArgumentException`.

No convenience row methods are required for 1.0.

## 12. Cell vertical-alignment API

The cell-level master surface already exists:

```php
$cell->setStyle([
    'vertical-align' => 'middle',
]);
```

The existing `StyleMapper::mapTableCellStyleOptions()` should gain:

```text
vertical-align -> style:vertical-align
```

Initial friendly values:

```text
top
middle
bottom
automatic
```

No dedicated convenience methods such as `alignTop()` or `setVerticalAlignment()` are required for TABLE-LAYOUT-01.

This avoids expanding the API when the established cell style array already provides the correct owner and vocabulary.

## 13. Element-owned style scope

The new semantic master method and its convenience methods must use the corrected element-owned table-style channel:

```text
family:        table
scope:         automatic
document part: content.xml
property group: style:table-properties
```

This follows:

- Writer concrete-table evidence;
- the SR-07 ownership rule;
- the STYLE-API-02F distinction between reference and element-owned definition.

The existing common/`styles.xml` materialization of element-owned `RichTable::setStyle()` is a compatibility-sensitive implementation residue and must be handled explicitly in the Change Contract.

## 14. Relationship to existing `setStyle()`

The preferred API distinction is:

```text
setTableStyle([...])
    -> primary semantic table-style authoring
    -> friendly mapper vocabulary
    -> target automatic/content.xml ownership

setStyle([...])
    -> existing low-level / compatibility path
    -> existing raw/native vocabulary and replacement behavior
    -> not the recommended 1.0 table-layout API
```

The Change Contract must decide whether the old raw path retains its current common/`styles.xml` scope temporarily for compatibility or is migrated together with all element-owned definitions.

That migration decision must be based on characterization and sample compatibility, not method-name aesthetics.

The public documentation should not teach `setStyle()` with namespace-qualified properties as the normal way to author table geometry.

## 15. API equivalence requirements

The following pairs should produce semantically equivalent element-owned geometry.

### Absolute width + alignment

```php
$table->setTableStyle([
    'width' => '15cm',
    'alignment' => 'center',
]);
```

Equivalent convenience form:

```php
$table
    ->setTableWidth('15cm')
    ->setTableAlignment('center');
```

Native intent:

```xml
<style:table-properties
    style:width="15cm"
    table:align="center"/>
```

### Relative width + alignment

```php
$table->setTableStyle([
    'relative-width' => '60%',
    'alignment' => 'right',
]);
```

Equivalent convenience form:

```php
$table
    ->setRelativeTableWidth('60%')
    ->setTableAlignment('right');
```

Native intent:

```xml
<style:table-properties
    style:rel-width="60%"
    table:align="right"/>
```

The engine should not synthesize Writer's calculated companion absolute width for relative-width authoring in the first implementation.

## 16. Validation direction

The Change Contract should freeze narrow semantic validation.

### Table width

```text
non-empty ODF-compatible length string
```

No general unit conversion layer.

### Relative table width

```text
percentage string such as "60%"
```

No implicit integer/float percentage shorthand in the first implementation.

### Table alignment

Allowed:

```text
left
center
right
margins
```

Invalid friendly values should raise `InvalidArgumentException`.

### Row heights

Non-empty length strings.

Simultaneous friendly exact/minimum height: `InvalidArgumentException`.

### Cell vertical alignment

Allowed:

```text
top
middle
bottom
automatic
```

Invalid friendly values: `InvalidArgumentException` or the repository's established mapper validation convention if inspection before contract freeze shows a stronger precedent.

## 17. Compatibility-sensitive tests required before implementation

The Change Contract should require characterization of:

1. `setStyle()` + `setTableStyleName()` call order;
2. reference-only mode followed by convenience mutation;
3. master `setTableStyle()` replacing reference mode;
4. master method replacement semantics;
5. convenience methods merging separate concerns;
6. convenience width forms replacing each other;
7. friendly width + alignment equivalence against Writer-native output;
8. repeated `save()`;
9. existing absolute/relative column APIs combined with table width/alignment;
10. Sample 11 after migration to recommended semantic syntax;
11. existing SR-07 common-style/reference compatibility.

## 18. Proposed public 1.0 surface

Subject to Change-Contract review, the preferred TABLE-LAYOUT-01 public API is:

```php
$table->setTableStyle([
    'width' => '15cm',
    'alignment' => 'center',
]);

$table->setTableStyle([
    'relative-width' => '60%',
    'alignment' => 'center',
]);

$table->setTableWidth('15cm');
$table->setRelativeTableWidth('60%');
$table->setTableAlignment('center');

$table->setColumnWidths(['4cm', '8cm']);
$table->setColumnWidthRatios([2, 1, 1]);

$table->addRow($cells, [
    'row-height' => '2cm',
]);

$table->addRow($cells, [
    'min-row-height' => '2cm',
]);

$cell->setStyle([
    'vertical-align' => 'middle',
]);
```

The first three table convenience methods are facades over the same semantic authority as `setTableStyle()`.

## 19. Explicitly rejected alternatives

For TABLE-LAYOUT-01 the following are rejected:

```php
$table->setAlignment('center');      // ambiguous owner
$table->setWidth('15cm');            // ambiguous table/column/element meaning
$table->setStyle([
    'table:align' => 'center',       // raw ODF as recommended application syntax
    'style:width' => '15cm',
]);
```

Also rejected:

- a second table geometry state object merely to support convenience methods;
- a table-layout service that reproduces Writer geometry calculations;
- table-level `text-align` as a synonym for whole-table alignment;
- numeric percentage shorthand without a demonstrated need;
- convenience methods that mutate externally referenced named styles.

## 20. Design-pass conclusion

The preferred API model is now coherent:

```text
PRIMARY MASTER API
    setTableStyle([...])

CONVENIENCE FACADES
    setTableWidth(...)
    setRelativeTableWidth(...)
    setTableAlignment(...)

EXISTING GEOMETRY
    setColumnWidths(...)
    setColumnWidthRatios(...)

ROW MASTER API
    addRow(..., row-style-array)

CELL MASTER API
    RichTableCell::setStyle(...)
```

All table-level friendly forms share one semantic state and one mapper.

The remaining TABLE-LAYOUT-01B work before Change Contract freeze is no longer general API invention. It is limited to:

1. accepting/adjusting this public vocabulary and call-order semantics;
2. freezing compatibility treatment of existing raw `RichTable::setStyle()`;
3. confirming exact exception/validation conventions;
4. deciding the final implementation slice grouping.

No production implementation is authorized by this design pass.

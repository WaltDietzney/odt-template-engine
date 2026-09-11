# TABLE-LAYOUT-01B — Compatibility / Call-Order Review

Status: REVIEWED / PRE-CONTRACT

Parent milestone: `TABLE-LAYOUT-01`

Related:

- `TABLE_LAYOUT_01B_API_DESIGN_PASS.md`
- `TABLE_LAYOUT_01B_ARCHITECTURE_SYNTHESIS.md`
- `TABLE_LAYOUT_01B_CURRENT_TABLE_API_INVENTORY.md`
- `TABLE_LAYOUT_01B_TABLE_STYLE_SCOPE_EVIDENCE.md`
- `STYLE_API_02F_MAPPER_REGISTRY_CLEANUP_CHANGE_CONTRACT.md`
- `SR-07_SEMANTIC_TABLE_STYLE_REQUIREMENTS_CHANGE_CONTRACT.md`

## 1. Purpose

This review checks the proposed TABLE-LAYOUT-01 public API against the current `RichTable` state machine, existing compatibility surfaces, and established naming conventions.

The goals are:

1. define deterministic call-order behavior;
2. avoid accidental mutation of named style references;
3. keep one element-owned table-style authority;
4. decide whether the current raw `setStyle()` path should retain or migrate its current style scope;
5. review the proposed public method names for clarity and consistency before the Change Contract is frozen.

No production implementation is authorized by this document.

## 2. Current `RichTable` style state machine

Current code already has two observable modes.

### 2.1 Reference mode

```php
$table->setTableStyleName('NamedStyle');
```

Current effect:

```text
tableStyleName    = NamedStyle
tableStyleOptions = []
```

The element emits a table style reference and owns no local table definition.

### 2.2 Element-owned definition mode

```php
$table->setStyle([...]);
```

Current effect:

```text
tableStyleOptions = supplied properties
tableStyleName    = generated identity
```

The element owns a generated table definition.

### 2.3 Clear state

```php
$table->setStyle([]);
```

currently clears both:

```text
tableStyleOptions = []
tableStyleName    = null
```

This is useful existing behavior and should remain a compatibility baseline.

## 3. Proposed semantic master method

The proposed primary application-facing authoring method is:

```php
$table->setTableStyle([
    'width' => '15cm',
    'alignment' => 'center',
]);
```

It explicitly selects element-owned generated-definition mode.

Therefore it may replace a prior named reference deliberately:

```php
$table->setTableStyleName('NamedStyle');
$table->setTableStyle([
    'width' => '15cm',
]);
```

Result:

```text
prior reference discarded intentionally
new element-owned generated table style becomes authoritative
```

This is not accidental mutation. The master method itself is the explicit authoring-mode switch.

## 4. Proposed convenience methods

Recommended convenience methods after naming review:

```php
$table->setTableWidth('15cm');
$table->setTableRelativeWidth('60%');
$table->setTableAlignment('center');
```

They are partial mutations of the same element-owned table style used by `setTableStyle()`.

They must not create separate geometry state.

## 5. Naming review

### 5.1 Master method: `setTableStyle()` — ACCEPT

Alternatives considered:

```text
setTableStyle()
setTableStyleOptions()
setTableLayout()
setTableOptions()
```

Decision: **`setTableStyle()` is preferred.**

Reasons:

- it is explicit about the semantic owner;
- it pairs naturally with `setTableStyleName()`;
- the argument type distinguishes authored options from a named reference;
- it avoids the broader implication of `setTableLayout()`, which could be read as column distribution, row structure, page placement, or a future layout engine;
- it avoids the vague `setTableOptions()`;
- `setTableStyleOptions()` would be precise but unnecessarily verbose for the master API.

The distinction remains clear:

```php
setTableStyleName(string $name) // reference
setTableStyle(array $options)   // element-owned semantic definition
```

### 5.2 Absolute width: `setTableWidth()` — ACCEPT

Alternatives considered:

```text
setWidth()
setTableWidth()
setAbsoluteTableWidth()
```

Decision: **`setTableWidth()` is preferred.**

Reasons:

- `setWidth()` is ambiguous in a table object that also owns columns/cells;
- `setAbsoluteTableWidth()` overstates the normal case and is needlessly verbose;
- `setTableWidth()` is clear and pairs with existing `setColumnWidths()`.

### 5.3 Relative width: rename to `setTableRelativeWidth()`

Previously proposed:

```php
setRelativeTableWidth('60%')
```

Preferred after review:

```php
setTableRelativeWidth('60%')
```

Reasons:

- all whole-table convenience methods then group naturally under `setTable...`;
- IDE/autocomplete discoverability improves:
  - `setTableWidth()`
  - `setTableRelativeWidth()`
  - `setTableAlignment()`
  - `setTableStyle()`
  - `setTableStyleName()`
- it makes the semantic owner appear before the qualifier;
- it avoids visually separating the relative-width method from the other table-specific methods.

Alternatives rejected:

```text
setRelativeWidth()       // owner ambiguous
setTableWidthPercent()   // percentage representation leaks into method meaning
setTableWidthRatio()     // conflicts conceptually with column ratios
setTableRelativeSize()   // too broad
```

### 5.4 Whole-table placement: `setTableAlignment()` — ACCEPT

Alternatives considered:

```text
setAlignment()
setTableAlignment()
setTableAlign()
setTablePlacement()
setHorizontalTableAlignment()
```

Decision: **`setTableAlignment()` is preferred.**

Reasons:

- `setAlignment()` is ambiguous with cell/paragraph alignment;
- `setTableAlign()` is less idiomatic application vocabulary and resembles the native QName;
- `setTablePlacement()` is broader than the actual ODF alignment semantic;
- `setHorizontalTableAlignment()` is accurate but unnecessarily long;
- `setTableAlignment()` communicates the user intent clearly and maps naturally to `table:align` internally.

### 5.5 Master option names — ACCEPT WITH CURRENT VOCABULARY

Preferred friendly array vocabulary:

```php
[
    'width' => '15cm',
    'relative-width' => '60%',
    'alignment' => 'center',
]
```

The names intentionally do not repeat `table-` inside `setTableStyle()`; the method already establishes the owner.

`alignment` is preferred over `align` because it is application vocabulary rather than a shortened/native-looking token.

## 6. Call-order contract matrix

The following matrix is recommended for TABLE-LAYOUT-01C.

| Earlier call | Later call | Result |
| --- | --- | --- |
| none | `setTableStyle([...])` | create element-owned definition |
| none | any table convenience setter | create element-owned definition |
| `setTableStyle([...])` | `setTableStyle([...])` | later master call replaces complete local table style |
| `setTableStyle([...])` | convenience setter | mutate only that concern, preserve unrelated local concerns |
| convenience setter(s) | `setTableStyle([...])` | master call replaces complete local table style |
| convenience setter(s) | another convenience setter | merge independent concerns; competing width mode replaces prior width mode |
| `setTableStyleName(...)` | `setTableStyle([...])` | explicit switch from reference to local definition |
| `setTableStyleName(...)` | convenience setter | reject with `LogicException` |
| local definition | `setTableStyleName(...)` | named reference wins; clear local definition |
| raw `setStyle([...])` | `setTableStyle([...])` | friendly master replaces complete local definition |
| friendly/local definition | raw `setStyle([...])` | raw compatibility call replaces complete local definition |
| raw `setStyle([...])` | convenience setter | mutate mapped native concern; preserve unrelated raw properties |
| any style state | `setTableStyle([])` | clear table style/reference completely |
| any style state | existing `setStyle([])` | preserve current clear semantics |

This produces a small, predictable state machine rather than order-dependent hidden merging.

## 7. Why convenience setters reject reference-only mode

Example:

```php
$table->setTableStyleName('InvoiceTable');
$table->setTableWidth('15cm');
```

Possible interpretations would be ambiguous:

1. mutate `InvoiceTable` globally;
2. clone it and create a local derivative;
3. discard it and create a local style containing only width;
4. overlay local width over the reference.

None of those semantics currently exists.

Therefore convenience methods must reject reference-only mode with `LogicException`.

If the caller wants local style ownership, the explicit switch is:

```php
$table->setTableStyle([
    'width' => '15cm',
]);
```

This keeps `reference != definition != mutation` intact.

## 8. Raw `setStyle()` compatibility decision

The review recommends **not** maintaining two different scope/document-part rules based on whether the local definition came from `setStyle()` or `setTableStyle()`.

That would require source-mode tracking and would create two competing element-owned style authorities.

Instead, TABLE-LAYOUT-01C should treat the following as one semantic category:

```text
element-owned generated RichTable definition
```

regardless of whether its properties originated from:

- legacy/raw `setStyle()`;
- new friendly `setTableStyle()`;
- new convenience methods.

The target materialization should therefore be:

```text
scope:         automatic
document part: content.xml
family:        table
```

for **all element-owned generated table definitions**.

The compatibility promise for existing raw `setStyle()` should be:

- accepted raw/native property input remains accepted;
- replacement/clear behavior remains;
- visible/rendered semantics are preserved or corrected;
- the historical common/`styles.xml` placement is **not** preserved as a public compatibility contract because it contradicts the already-approved SR-07 ownership rule.

This is the cleanest resolution of the scope mismatch and avoids architecture debt.

## 9. Raw/friendly mixing

Because both raw and friendly authoring ultimately normalize into one native element-owned property set, mixing can be deterministic.

Example:

```php
$table->setStyle([
    'fo:margin-left' => '1cm',
    'table:align' => 'left',
]);

$table->setTableAlignment('center');
```

Result:

```text
fo:margin-left = 1cm       preserved
table:align    = center    replaced by friendly concern
```

Likewise:

```php
$table->setStyle([
    'style:rel-width' => '60%',
    'table:align' => 'center',
]);

$table->setTableWidth('15cm');
```

Result:

```text
style:rel-width removed
style:width     = 15cm
table:align     = center
```

The convenience method owns its semantic concern; it does not wipe unrelated raw/native properties.

## 10. Width conflict rules

### Master friendly array

Reject:

```php
$table->setTableStyle([
    'width' => '15cm',
    'relative-width' => '60%',
]);
```

with `InvalidArgumentException`.

A single declarative request must not carry contradictory friendly width intent.

### Sequential convenience calls

Allow deterministic replacement:

```php
$table->setTableRelativeWidth('60%');
$table->setTableWidth('15cm');
```

Result: absolute width only.

Reverse order: relative width only.

### Raw input

Existing raw `setStyle()` remains permissive. If a caller deliberately supplies both native width properties, TABLE-LAYOUT-01 does not retroactively reinterpret or reject that legacy/advanced input.

## 11. Alignment call-order behavior

Alignment is independent of width.

Therefore:

```php
$table
    ->setTableWidth('15cm')
    ->setTableAlignment('center');
```

and:

```php
$table
    ->setTableAlignment('center')
    ->setTableWidth('15cm');
```

must produce equivalent semantic state.

A later `setTableAlignment()` simply replaces the previous whole-table alignment value.

Allowed friendly values remain:

```text
left
center
right
margins
```

## 12. Empty master style

Recommended:

```php
$table->setTableStyle([]);
```

means clear table-level styling/reference completely.

This matches existing `setStyle([])` behavior and gives callers a deterministic way to return to an unstyled table state.

No individual `clearTableWidth()` / `clearTableAlignment()` methods are required for 1.0.

A caller needing a different set of concerns can replace the complete local state through `setTableStyle([...])`.

## 13. Existing public signatures that remain untouched

TABLE-LAYOUT-01 must not opportunistically change:

```php
setColumnWidths(array $widths): void
setColumnWidthRatios(array $ratios): void
```

even though the new convenience methods are fluent.

Likewise existing cell convenience and row APIs remain in place.

This avoids mixing geometry capability work with unrelated signature cleanup.

## 14. Subclass / protected compatibility

The proposed methods are additions and do not require changing protected method signatures.

Production implementation should prefer small private/protected helpers only where necessary, for example one helper that refreshes generated table style identity from normalized local properties.

Existing public/protected surfaces should not be renamed or narrowed as part of TABLE-LAYOUT-01.

## 15. Required characterization before production change

Before changing table-style scope or adding the new API, focused tests should freeze current behavior for:

1. `setTableStyleName()` clearing current local options;
2. raw `setStyle([...])` replacing a prior named reference;
3. raw `setStyle([])` clearing style/reference;
4. raw `setStyle([...])` replacement rather than merge;
5. current generated table definition scope/document part;
6. existing Sample 11 materialization;
7. repeated save behavior.

Then contract tests should define the new behavior for:

1. `setTableStyle()` master replacement;
2. master method switching from reference mode;
3. convenience methods rejecting reference mode;
4. convenience merging independent concerns;
5. width-mode replacement;
6. friendly/raw mixed state;
7. automatic/content.xml scope for all element-owned generated table definitions.

## 16. Review decision

The proposed public API is internally coherent with one naming adjustment:

```php
$table->setTableStyle([...]);

$table->setTableWidth('15cm');
$table->setTableRelativeWidth('60%');
$table->setTableAlignment('center');
```

The previous candidate `setRelativeTableWidth()` should be replaced by `setTableRelativeWidth()` for method-family consistency and discoverability.

Recommended master vocabulary:

```php
$table->setTableStyle([
    'width' => '15cm',
    'relative-width' => '60%',
    'alignment' => 'center',
]);
```

with `width` and `relative-width` mutually exclusive in one friendly master call.

The compatibility strategy should preserve raw input capability but **not** preserve the incorrect common/`styles.xml` placement of element-owned table definitions as a permanent behavior guarantee.

The review therefore considers the API/call-order model **ready for Change-Contract drafting**, subject to user approval of the final names and the all-element-owned scope migration.

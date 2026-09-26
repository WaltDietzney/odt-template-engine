# TABLE-LAYOUT-01C — Change Contract

Status: FINAL GO

Parent milestone: `TABLE-LAYOUT-01`

Predecessors:

- `TABLE_LAYOUT_01A_CLOSEOUT.md`
- `TABLE_LAYOUT_01B_ARCHITECTURE_SYNTHESIS.md`
- `TABLE_LAYOUT_01B_API_DESIGN_PASS.md`
- `TABLE_LAYOUT_01B_COMPATIBILITY_CALL_ORDER_REVIEW.md`

Supporting evidence and architecture:

- `TABLE_LAYOUT_01A_EVIDENCE_FINDINGS.md`
- `TABLE_LAYOUT_01B_CURRENT_TABLE_API_INVENTORY.md`
- `TABLE_LAYOUT_01B_PUBLIC_STYLE_SEMANTICS_CORRECTION.md`
- `TABLE_LAYOUT_01B_TABLE_STYLE_SCOPE_EVIDENCE.md`
- `SR-07_SEMANTIC_TABLE_STYLE_REQUIREMENTS_CHANGE_CONTRACT.md`
- `STYLE_API_02F_MAPPER_REGISTRY_CLEANUP_CHANGE_CONTRACT.md`

## 1. Purpose

TABLE-LAYOUT-01C freezes the public semantics, ownership, compatibility boundaries, call-order behavior, validation direction, implementation slices, and completion criteria for the remaining table-layout capabilities required for 1.0.

The contract finishes deliberately deferred table-layout authoring semantics on top of the existing semantic style architecture.

The governing rule is:

> Application code expresses table-layout intent in engine vocabulary. Structured elements own document-local semantic state. Mapping projects that state to native ODF properties. Writer remains responsible for final physical layout resolution.

This contract authorizes implementation only within the boundaries below.

## 2. Mandatory 1.0 scope

TABLE-LAYOUT-01 must provide and preserve:

- explicit absolute table width;
- explicit relative table width;
- whole-table alignment / placement;
- absolute column widths;
- relative column widths;
- exact/fixed row height;
- minimum/growable row height;
- vertical cell alignment.

Already-established capabilities remain regression baselines:

```text
setColumnWidths()
setColumnWidthRatios()
65535 Writer interoperability normalization
min-row-height
table/cell/paragraph/text ownership split
named table-style reference semantics
```

The missing work is table-level friendly authoring, exact row height, vertical cell alignment, and the bounded correction of generated table-style scope.

## 3. Public table-level authoring API

### 3.1 Master method

The primary table-level authoring method is:

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

`setTableStyle(array $options): self` is the authoritative friendly master API for an element-owned generated table style.

It replaces the complete current element-owned table-style state.

### 3.2 Friendly table option vocabulary

The approved 1.0 vocabulary is intentionally narrow:

```text
width
relative-width
alignment
```

Native projection:

```text
width          -> style:width
relative-width -> style:rel-width
alignment      -> table:align
```

No broader table-style vocabulary is authorized by symmetry.

### 3.3 Convenience methods

The approved table-level convenience methods are:

```php
$table->setTableWidth('15cm');
$table->setTableRelativeWidth('60%');
$table->setTableAlignment('center');
```

These methods are facades over the same semantic authority as `setTableStyle()`.

They must not create separate table geometry/alignment state.

### 3.4 Naming decisions

The following names are explicitly rejected for this milestone:

```text
setWidth()                 ambiguous owner
setRelativeWidth()         ambiguous owner
setAlignment()             ambiguous owner
setRelativeTableWidth()    weaker method-family grouping
setTableWidthPercent()     representation-specific
setTableWidthRatio()       conflicts conceptually with column ratios
setTableAlign()            native-looking abbreviation
setTablePlacement()        broader than the contracted semantic
setTableLayout()           broader than table-style authoring
```

The accepted method family intentionally groups whole-table concerns under `setTable...`.

## 4. Mapping boundary

TABLE-LAYOUT-01 introduces a stateless table-level mapping boundary:

```php
StyleMapper::mapTableStyleOptions(array $options): array
```

Its approved friendly mappings are:

```text
width          -> style:width
relative-width -> style:rel-width
alignment      -> table:align
```

The materializer continues to receive normalized native property groups. It must not learn friendly application keys.

Existing native-prefixed input remains an advanced/compatibility escape hatch where already accepted. Documentation and samples should prefer friendly engine vocabulary.

## 5. Single element-owned table-style authority

The implementation must maintain one normalized element-owned table property state.

Conceptually:

```text
setTableStyle(...)
table convenience setters
legacy/raw setStyle(...)
        ↓
one normalized element-owned table property set
        ↓
one generated style identity
        ↓
StyleRequirement
```

TABLE-LAYOUT-01 must not introduce parallel mutable state such as separate table-width, alignment, or geometry stores merely to support convenience methods.

The current `tableStyleOptions` state may remain the normalized native store if implementation review confirms it is sufficient.

## 6. Named style references remain distinct

```php
$table->setTableStyleName('NamedStyle');
```

remains reference semantics.

The governing distinction remains:

```text
reference != definition != mutation
```

A named reference must not be silently mutated or cloned by convenience geometry methods.

## 7. Call-order contract

### 7.1 Master method selects local-definition mode

This is valid:

```php
$table->setTableStyleName('NamedStyle');

$table->setTableStyle([
    'width' => '15cm',
]);
```

The second call explicitly switches from named-reference mode to an element-owned generated definition. The prior reference is discarded.

### 7.2 Convenience mutation of reference-only mode is rejected

This is invalid:

```php
$table->setTableStyleName('NamedStyle');
$table->setTableWidth('15cm');
```

Likewise for `setTableRelativeWidth()` and `setTableAlignment()`.

The operation must fail with `LogicException` because the engine must not invent clone, overlay, global mutation, or implicit reference-discard semantics.

### 7.3 Named style reference after local definition wins

This is valid:

```php
$table->setTableStyle([
    'width' => '15cm',
]);

$table->setTableStyleName('NamedStyle');
```

The named reference becomes authoritative and local table-style properties are cleared, preserving current `setTableStyleName()` behavior.

### 7.4 Master calls replace complete local state

```php
$table->setTableStyle([
    'width' => '15cm',
    'alignment' => 'center',
]);

$table->setTableStyle([
    'relative-width' => '60%',
]);
```

Final friendly intent contains relative width only. Alignment from the earlier master call is not retained.

### 7.5 Convenience calls merge independent concerns

```php
$table
    ->setTableWidth('15cm')
    ->setTableAlignment('center');
```

must preserve both concerns.

Call order between independent convenience concerns must not change semantic output.

### 7.6 Competing width modes replace one another sequentially

```php
$table->setTableRelativeWidth('60%');
$table->setTableWidth('15cm');
```

results in absolute width only.

The reverse order results in relative width only.

### 7.7 Empty master style clears table styling

```php
$table->setTableStyle([]);
```

clears both element-owned table properties and table style reference.

Existing `setStyle([])` clear behavior remains compatible.

No dedicated clear-width/alignment methods are required for 1.0.

## 8. Raw `RichTable::setStyle()` compatibility contract

Existing:

```php
$table->setStyle([
    'style:width' => '15cm',
    'table:align' => 'center',
]);
```

remains a supported low-level / compatibility surface.

TABLE-LAYOUT-01 does not silently reinterpret `setStyle()` as the new friendly vocabulary.

Its existing replacement and clear semantics remain.

However, raw and friendly authoring share the same element-owned semantic authority.

### 8.1 Raw then friendly master

A later `setTableStyle([...])` replaces the complete raw local definition.

### 8.2 Friendly then raw master

A later `setStyle([...])` replaces the complete friendly/local definition.

### 8.3 Raw then convenience

A convenience method mutates only its semantic concern and preserves unrelated raw properties.

Example:

```php
$table->setStyle([
    'fo:margin-left' => '1cm',
    'table:align' => 'left',
]);

$table->setTableAlignment('center');
```

Final normalized state retains `fo:margin-left` and replaces `table:align` with `center`.

### 8.4 Raw contradictory native input

TABLE-LAYOUT-01 does not retroactively validate or normalize arbitrary contradictory native properties supplied through the compatibility path.

Friendly semantic validation applies to friendly authoring input.

## 9. Absolute versus relative table width

Absolute and relative table width are competing friendly intents.

### 9.1 Master-array conflict

This is invalid:

```php
$table->setTableStyle([
    'width' => '15cm',
    'relative-width' => '60%',
]);
```

It must raise `InvalidArgumentException`.

The engine must not invent precedence inside one declarative friendly request.

### 9.2 Convenience replacement

Sequential convenience calls are intentional mutation and therefore use last-width-mode-wins semantics as defined in section 7.6.

### 9.3 Writer normalization boundary

The engine authors the requested semantic property.

For relative width it must not synthesize Writer's calculated companion absolute `style:width` merely because Writer may later serialize one.

Writer remains responsible for final layout calculation/normalization.

## 10. Whole-table alignment

Whole-table alignment is mandatory for 1.0.

Friendly forms:

```php
$table->setTableStyle([
    'alignment' => 'center',
]);

$table->setTableAlignment('center');
```

Approved values:

```text
left
center
right
margins
```

Native projection:

```text
alignment -> table:align
```

Whole-table alignment is distinct from:

```text
paragraph horizontal alignment
cell vertical alignment
```

No generic `setAlignment()` API is authorized.

## 11. Row-height contract

The existing row authoring surface remains the master API:

```php
$table->addRow($cells, $rowStyle);
```

Approved friendly row keys are:

```php
['row-height' => '2cm']
['min-row-height' => '2cm']
```

Native projection:

```text
row-height     -> style:row-height
min-row-height -> style:min-row-height
```

Exact/fixed and minimum/growable height are distinct semantics.

A single friendly row style containing both is invalid and must raise `InvalidArgumentException`.

Unsupported row keys retain the existing compatibility behavior unless a focused implementation review proves that this contract cannot be satisfied without changing it. Such a conflict requires return to contract review rather than silent cleanup.

No row object or dedicated row-height convenience API is introduced for 1.0.

## 12. Cell vertical-alignment contract

The existing cell master surface remains:

```php
$cell->setStyle([
    'vertical-align' => 'middle',
]);
```

`StyleMapper::mapTableCellStyleOptions()` is extended so that:

```text
vertical-align -> style:vertical-align
```

Approved friendly values:

```text
top
middle
bottom
automatic
```

Vertical cell alignment remains a `table-cell` property and must not alter paragraph horizontal alignment.

No dedicated `setVerticalAlignment()` / `alignMiddle()` convenience API is required by TABLE-LAYOUT-01.

## 13. Element-owned generated table-style scope correction

Writer evidence and the already-approved SR-07 ownership rule agree that a generated style owned by a concrete structured table belongs to:

```text
family:        table
scope:         automatic
document part: content.xml
property group: style:table-properties
```

Current `RichTable::setStyle()` materializes such element-owned definitions as common styles in `styles.xml`.

TABLE-LAYOUT-01 explicitly authorizes correcting that mismatch.

### 13.1 Unified rule

All element-owned generated `RichTable` definitions use automatic/`content.xml` ownership regardless of whether their normalized properties originated from:

- legacy/raw `setStyle()`;
- friendly `setTableStyle()`;
- table convenience methods.

The implementation must not preserve different scope rules by tracking which public entry point created the same semantic state.

### 13.2 Compatibility boundary

The compatibility guarantee for raw `setStyle()` is its accepted native input and behavioral authoring semantics, not the accidental historical common/`styles.xml` placement.

This correction must not alter:

- genuinely authored reusable common table styles;
- named style reference semantics;
- common-style precedence;
- unrelated style families.

This is restoration of SR-07 ownership semantics, not a style-system redesign.

## 14. Column geometry remains independent

Existing:

```php
$table->setColumnWidths([...]);
$table->setColumnWidthRatios([...]);
```

remain authoritative and unchanged.

The following distinction is contractual:

```text
overall table width
    !=
absolute column width
    !=
relative column width
```

Therefore TABLE-LAYOUT-01 must not:

- rewrite column widths when overall table width changes;
- synthesize overall table width from columns;
- require absolute column totals to equal explicit table width;
- reinterpret the established 65535 relative-column normalization.

Existing return signatures and compatibility side effects of the column APIs are not cleanup targets.

## 15. Validation contract

Validation is narrow and semantic. TABLE-LAYOUT-01 does not introduce a general CSS/ODF validator.

### 15.1 Absolute table width and row heights

Friendly values must be non-empty ODF-compatible length strings.

Implementation should reuse an established validation helper/convention if one exists and fits the contract rather than creating a broad new unit system.

### 15.2 Relative table width

Friendly input is a percentage-shaped string such as:

```text
60%
```

No integer/float shorthand is required for 1.0.

### 15.3 Table alignment

Allowed friendly values are exactly:

```text
left
center
right
margins
```

Invalid values raise `InvalidArgumentException`.

### 15.4 Row-height conflict

Simultaneous friendly `row-height` and `min-row-height` raises `InvalidArgumentException`.

### 15.5 Cell vertical alignment

Allowed friendly values are:

```text
top
middle
bottom
automatic
```

Invalid values follow the repository's established mapper validation convention. If no stronger existing convention applies, use `InvalidArgumentException`.

## 16. Style identity and lifecycle

Generated table style identity must be derived from the normalized element-owned native property state, consistent with existing generated-style identity behavior.

Equivalent friendly master and convenience forms must therefore resolve to semantically equivalent normalized state and style output.

Repeated semantic collection/materialization must not multiply the same generated table definition in the target automatic-style context.

TABLE-LAYOUT-01 does not otherwise redefine general repeated `setElement()`, `render()`, or `save()` lifecycle semantics.

## 17. Required API equivalence

These forms must be semantically equivalent:

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

Expected native intent:

```xml
<style:table-properties
    style:width="15cm"
    table:align="center"/>
```

Likewise:

```php
$table->setTableStyle([
    'relative-width' => '60%',
    'alignment' => 'right',
]);
```

and:

```php
$table
    ->setTableRelativeWidth('60%')
    ->setTableAlignment('right');
```

must be semantically equivalent.

## 18. Compatibility characterization gate

Before production behavior is changed, focused characterization must freeze at least:

1. `setTableStyleName()` clearing current local table options;
2. raw `setStyle([...])` replacing a prior named reference;
3. raw `setStyle([])` clearing style/reference;
4. raw `setStyle([...])` replacement rather than merge;
5. current generated table definition scope/document part;
6. current Sample 11 table-style materialization;
7. repeated save behavior for an element-owned table definition;
8. coexistence of current raw table properties with absolute/relative column APIs.

Characterization records current behavior; it does not turn the incorrect common/`styles.xml` scope into a required target behavior.

## 19. Implementation slices

Implementation proceeds in small reviewable slices.

### Slice 1 — Table-level semantic authoring + ownership correction

Responsibilities:

- add the compatibility characterization gate from section 18;
- add `StyleMapper::mapTableStyleOptions()`;
- add `setTableStyle()`;
- add `setTableWidth()`;
- add `setTableRelativeWidth()`;
- add `setTableAlignment()`;
- implement call-order/reference-mode rules;
- implement width-mode conflict/replacement semantics;
- preserve raw `setStyle()` compatibility;
- unify element-owned generated table definitions under automatic/`content.xml`;
- preserve named/common style semantics;
- prove friendly master/convenience equivalence;
- verify coexistence with both column geometry APIs;
- verify repeated save/reopen stability.

This slice intentionally keeps the scope correction and new table-level authoring together because both must use one element-owned table semantic authority. Splitting them must not create a temporary second table-style system.

### Slice 2 — Exact row height

Responsibilities:

- extend the existing row semantic producer for `row-height`;
- preserve `min-row-height`;
- reject simultaneous friendly exact/minimum height;
- preserve unrelated unsupported-row-key compatibility behavior;
- verify automatic/content-local row definition materialization;
- add focused integration and Writer visual regression.

### Slice 3 — Vertical cell alignment

Responsibilities:

- extend `mapTableCellStyleOptions()` for `vertical-align`;
- validate approved friendly values;
- preserve native/raw cell compatibility;
- preserve paragraph horizontal alignment independently;
- verify semantic table-cell materialization;
- add focused integration and Writer visual regression.

### Slice 4 — Integration / compatibility / closeout

Responsibilities:

- combined table width + alignment + absolute columns;
- combined relative table width + relative column ratios;
- exact/minimum row-height regressions;
- vertical cell alignment;
- mixed raw/friendly table authoring;
- named-reference call-order regressions;
- repeated save/reopen;
- PublicSampleSmokeTest;
- focused SR-07/STYLE-API regression;
- full automated preflight;
- LibreOffice visual regression;
- documentation closeout.

## 20. Test requirements

Tests should cover at minimum:

### Table-level API

- master absolute width;
- master relative width;
- master alignment;
- master replacement semantics;
- master empty clear;
- convenience absolute width;
- convenience relative width;
- convenience alignment;
- convenience independent merge;
- convenience competing width replacement;
- friendly width conflict rejection;
- invalid alignment rejection;
- master/convenience equivalence.

### Reference/raw compatibility

- named reference -> master local definition;
- named reference -> convenience rejection;
- local definition -> named reference;
- raw master -> friendly master;
- friendly master -> raw master;
- raw master -> convenience concern mutation;
- raw clear semantics;
- automatic/content.xml migration for element-owned generated definitions;
- authored/common/reference semantics unchanged.

### Row/cell

- exact row height materialization;
- minimum row height regression;
- exact/minimum conflict;
- vertical cell alignment mapping/materialization;
- invalid vertical alignment;
- horizontal paragraph alignment unaffected.

### Geometry/lifecycle

- overall width does not rewrite absolute columns;
- overall relative width does not rewrite column ratios;
- 65535 ratio normalization unchanged;
- repeated save/reopen stable;
- no duplicate generated semantic table definition.

## 21. Manual LibreOffice regression

Automated XML tests do not replace visual verification.

At closeout, inspect representative generated ODTs in LibreOffice Writer for:

- absolute table width;
- relative table width;
- left/center/right/margins table alignment as practical;
- absolute columns within explicit table width;
- relative columns within relative table width;
- exact row height;
- minimum row height;
- vertical cell alignment;
- at least one table containing an image or structured content where row-height behavior is visually relevant.

Existing local `samples/output/*.odt` files remain local regression artifacts and must not be committed, restored, deleted, or regenerated incidentally.

## 22. Preflight

After relevant implementation slices, run as applicable:

- focused TABLE-LAYOUT tests;
- existing TABLE-LAYOUT-01A characterization tests;
- relevant SR-07 / STYLE-API tests;
- relevant integration tests;
- `PublicSampleSmokeTest`;
- full `composer test`;
- PHP lint for `src/` and `tests/`;
- `composer validate` when relevant;
- `git diff --check`;
- documentation build/checks when relevant;
- manual LibreOffice regression.

Pre-existing local sample-output/template modifications and research/tmp artifacts must not be included accidentally.

## 23. Explicit non-goals

TABLE-LAYOUT-01 does not authorize:

- a PHP table layout engine;
- Writer geometry reimplementation;
- automatic text/content measurement;
- page-width or page-margin calculation;
- auto-fit algorithms;
- automatic reconciliation of table width with column totals;
- automatic distribution of unspecified columns;
- row pagination/keep rules;
- merged-cell redesign;
- new row objects;
- new table-specific StyleContext;
- global table-style registries;
- broad reusable named table-style authoring;
- named-style cloning/overlay semantics;
- general CSS/ODF validation;
- generic unit conversion;
- changing existing column API signatures;
- cleanup/removal of public/protected compatibility facades;
- expansion of the remaining direct `StyleWriter` compatibility path;
- frame positioning;
- page-style authoring;
- template authoring UX;
- unrelated sample cleanup.

## 24. Stop conditions

Implementation must stop and return to contract review if evidence shows that:

1. friendly and raw table authoring cannot share one normalized element-owned state without breaking established compatibility;
2. moving generated table definitions to automatic/`content.xml` breaks genuinely authored/common style semantics rather than only correcting element-owned ownership;
3. convenience methods would require implicit mutation/cloning of named referenced styles;
4. exact row height cannot be added through the existing row semantic layer without a second mutable row subsystem;
5. vertical cell alignment cannot remain cleanly owned by the table-cell family;
6. a proposed implementation requires Writer layout calculations in PHP;
7. compatibility requires changing existing column semantics or the 65535 normalization rule;
8. implementation requires unrelated public/protected API cleanup.

Such findings require evidence and an explicit contract amendment before proceeding.

## 25. Completion criteria

TABLE-LAYOUT-01 is complete only when:

1. `setTableStyle()` is the friendly master table-level authoring method.
2. `setTableWidth()`, `setTableRelativeWidth()`, and `setTableAlignment()` are convenience facades over the same semantic authority.
3. friendly `width`, `relative-width`, and `alignment` map to the correct native table properties.
4. absolute and relative friendly width intent have deterministic conflict/replacement semantics.
5. named table-style references remain reference-only and convenience mutation of reference-only mode is rejected.
6. raw `RichTable::setStyle()` remains available as a compatibility/advanced surface with its replacement/clear semantics preserved.
7. all element-owned generated table definitions use automatic/`content.xml` ownership, while genuinely authored common styles and references retain their semantics.
8. existing absolute column widths remain unchanged.
9. existing relative column ratios and 65535 Writer normalization remain unchanged.
10. exact `row-height` is supported through the existing row authoring surface.
11. existing `min-row-height` behavior remains supported.
12. contradictory friendly exact/minimum row-height intent is rejected.
13. friendly cell `vertical-align` is supported through the existing cell style surface.
14. cell vertical alignment remains independent from paragraph horizontal alignment.
15. master and convenience table-level forms produce semantically equivalent normalized output.
16. repeated save/reopen does not duplicate the generated semantic table definition or lose layout intent.
17. focused compatibility, integration, sample-smoke, full-suite, lint/diff, and relevant documentation checks pass.
18. representative LibreOffice visual regression passes or any intentional difference is explicitly documented and approved.
19. no incidental sample-output/template artifacts, lock files, research artifacts, or tmp files are committed.

## 26. Approved decisions / FINAL GO

The following decisions are frozen by TABLE-LAYOUT-01C:

1. `setTableStyle(array)` is the primary friendly table-level style/layout authoring method.
2. The friendly table option vocabulary for this milestone is `width`, `relative-width`, and `alignment`.
3. The convenience API is `setTableWidth()`, `setTableRelativeWidth()`, and `setTableAlignment()`.
4. Master and convenience forms share one normalized element-owned table-style state and one semantic materialization authority.
5. `setTableStyleName()` remains named-reference semantics. Master authoring may explicitly replace reference mode; convenience mutation may not.
6. `setStyle()` remains the low-level/raw compatibility surface and shares the same element-owned definition authority without being redefined as the recommended friendly API.
7. Element-owned generated table definitions are automatic styles in `content.xml`. Their current common/`styles.xml` placement is an implementation/compatibility residue, not a permanent public contract.
8. Friendly absolute and relative table widths are mutually exclusive in one master call; sequential convenience setters use last-width-mode-wins semantics.
9. Whole-table alignment is mandatory for 1.0 and uses the table-specific public semantic `alignment` / `setTableAlignment()`.
10. Existing column-width and column-ratio semantics remain independent and unchanged, including Writer's 65535 normalization.
11. Exact row height extends the existing row style surface; minimum row height remains distinct.
12. Vertical cell alignment extends the existing cell style surface and remains distinct from paragraph horizontal alignment.
13. TABLE-LAYOUT-01 does not introduce a layout engine, named-style mutation/overlay, new global registries, new row objects, or broad style-system redesign.
14. Implementation proceeds only through the four slices in section 19 and is subject to the characterization gate and stop conditions above.

With these decisions accepted, TABLE-LAYOUT-01C is **FINAL GO for Slice 1**, with Slices 2–4 proceeding after review of the preceding slice and their stated validation gates.

# TABLE-LAYOUT-01 — Closeout Checkpoint

Status: IMPLEMENTATION COMPLETE / FINAL LOCAL PREFLIGHT + TEMPLATE COMMIT PENDING

Branch: `architecture/table-layout-01`

Primary contract:

- `TABLE_LAYOUT_01C_CHANGE_CONTRACT.md`

Supporting implementation/review documents:

- `TABLE_LAYOUT_01A_CLOSEOUT.md`
- `TABLE_LAYOUT_01B_ARCHITECTURE_SYNTHESIS.md`
- `TABLE_LAYOUT_01B_API_DESIGN_PASS.md`
- `TABLE_LAYOUT_01B_COMPATIBILITY_CALL_ORDER_REVIEW.md`
- `TABLE_LAYOUT_01C_SLICE1_REVIEW.md`

## 1. Implemented 1.0 table-layout surface

TABLE-LAYOUT-01 now provides:

```php
$table->setTableStyle([
    'width' => '15cm',
    'alignment' => 'center',
]);

$table->setTableStyle([
    'relative-width' => '60%',
    'alignment' => 'right',
]);

$table->setTableWidth('15cm');
$table->setTableRelativeWidth('60%');
$table->setTableAlignment('center');

$table->addRow($cells, [
    'row-height' => '2cm',
]);

$table->addRow($cells, [
    'min-row-height' => '1.2cm',
]);

$cell->setStyle([
    'vertical-align' => 'middle',
]);
```

Existing authoritative APIs remain:

```php
$table->setColumnWidths([...]);
$table->setColumnWidthRatios([...]);
```

## 2. Semantic ownership/result

Element-owned generated table definitions now use:

```text
family:        table
scope:         automatic
document part: content.xml
property group: style:table-properties
```

This corrects the earlier common/`styles.xml` residue and aligns concrete generated tables with the SR-07 ownership rule.

Named style references remain reference-only and authored reusable common table styles remain distinct.

## 3. Public authoring model

`setTableStyle()` is the primary friendly master method.

The friendly table-level vocabulary is:

```text
width
relative-width
alignment
```

Convenience methods are facades over the same normalized local table-style state.

The older `RichTable::setStyle()` remains available as a low-level/native compatibility path.

## 4. Width and alignment behavior

Contracted behavior is implemented:

- friendly absolute and relative table width are mutually exclusive in one master call;
- sequential convenience calls use last-width-mode-wins behavior;
- whole-table alignment accepts `left`, `center`, `right`, and `margins`;
- overall table width remains independent from absolute/relative column geometry;
- Writer's established 65535 normalization for relative column ratios remains unchanged.

## 5. Row and cell semantics

Exact/fixed row height is supported through the existing row-style argument:

```text
row-height -> style:row-height
```

Minimum/growable height remains:

```text
min-row-height -> style:min-row-height
```

Supplying both friendly height modes in one row style is rejected.

Cell vertical alignment is now friendly-mapped as:

```text
vertical-align -> style:vertical-align
```

with:

```text
top
middle
bottom
automatic
```

Cell vertical alignment remains independent from paragraph horizontal alignment.

## 6. Compatibility behavior

The implementation preserves the key compatibility contracts:

- raw `RichTable::setStyle()` remains accepted;
- raw `setStyle([])` continues to clear local style/reference state;
- `setTableStyleName()` remains reference semantics;
- convenience methods reject mutation while reference-only mode is active;
- the explicit master `setTableStyle()` may intentionally switch from reference mode to a local generated definition;
- column API signatures remain unchanged;
- unsupported unrelated row-style keys remain ignored.

## 7. Automated validation status

Focused TABLE-LAYOUT rounds have passed during implementation, including:

- Slice 1 table-style API/call-order/materialization tests;
- Slice 2 exact/minimum row-height tests;
- Slice 3 vertical-cell-alignment tests;
- combined Slice 4 integration preflight;
- PublicSampleSmokeTest during the focused rounds;
- PHP syntax checks;
- `composer validate`;
- `git diff --check`.

The first repository-wide `composer test` after Slice 4 exposed exactly two stale historical expectations:

1. `StyleApi02FADocumentOwnershipTest` still expected generated `RichTable` definitions in common/`styles.xml`;
2. `TableLayout01AGeometryLifecycleCharacterizationTest` still expected exact row height to remain unsupported.

Both tests have now been aligned with the approved TABLE-LAYOUT-01C contract.

A final repository-wide rerun is still required before declaring FINAL GO because that rerun cannot be performed through the GitHub editing channel.

## 8. LibreOffice manual regression

A dedicated manual showcase script was added:

```text
samples/sample_26_tableLayout.php
```

The locally authored companion template is:

```text
samples/templates/template_26_tableLayout.odt
```

with placeholders:

```text
{{absolute_table}}

{{relative_table}}
```

Manual LibreOffice inspection confirmed:

- visible absolute table-width behavior;
- table placement/alignment;
- exact row heights;
- minimum row-height growth behavior;
- clearly distinct top/middle/bottom vertical cell alignment;
- independent horizontal text alignment;
- relative table width;
- visible 2:1:1 relative column proportions.

The visual result was accepted as successful.

Sample 11 remains the existing absolute-column-width regression/example.

## 9. Column-style identity finding

While preparing a single document containing both absolute and relative column geometry, the existing positional column-style identity scheme exposed a conflict:

```text
co0 / co1 / ...
```

are document-global semantic identities, so different generated definitions with the same positional name correctly conflict in `StyleContext`.

This is not fixed inside TABLE-LAYOUT-01 because SR-07 explicitly left generated column-name allocation/collision policy unresolved.

The finding is now recorded as:

```text
TABLE-COLUMN-IDENTITY-01
```

in `docs/FUTURE_DEVELOPMENT.md`.

It is a bounded future architecture topic, not a reason to weaken semantic conflict detection.

## 10. Sample 26 repository state

The PHP showcase script is committed.

The ODT template was created locally during manual regression and must be added from the local worktree before the sample is made part of the public sample smoke range.

Do not regenerate or replace unrelated sample output files while doing this.

Required local commit scope:

```text
samples/templates/template_26_tableLayout.odt
```

The generated output:

```text
samples/output/output_26_tableLayout.odt
```

remains a local regression artifact and must not be committed.

## 11. Remaining closeout steps

Before TABLE-LAYOUT-01 can be marked COMPLETE / FINAL GO and merged to `develop`, perform exactly these final actions:

1. add and commit only `samples/templates/template_26_tableLayout.odt`;
2. optionally extend `PublicSampleSmokeTest` from 25 to 26 only after the template is present in the branch;
3. rerun full `composer test`;
4. rerun PHP lint for `src/` and `tests/`;
5. rerun `composer validate`;
6. rerun `git diff --check`;
7. confirm no local sample outputs, unrelated template experiments, `research/`, `tmp/`, or LibreOffice lock files enter the commit;
8. final diff review;
9. PR `architecture/table-layout-01 -> develop`.

## 12. Current verdict

TABLE-LAYOUT-01 implementation is complete and the intended ODF/Writer behavior is visibly confirmed.

No unresolved production-code defect is currently known.

The milestone is deliberately left at:

```text
IMPLEMENTATION COMPLETE / FINAL LOCAL PREFLIGHT + TEMPLATE COMMIT PENDING
```

rather than being falsely marked FINAL GO before the locally created binary template and final repository-wide rerun are recorded.

# TABLE-LAYOUT-01C — Slice 1 Contract Review

Status: CONDITIONAL PASS / CONTRACT-ALIGNMENT REQUIRED

Parent milestone: `TABLE-LAYOUT-01`

Contract: `TABLE_LAYOUT_01C_CHANGE_CONTRACT.md`

Reviewed branch: `architecture/table-layout-01`

## 1. Review scope

This review compares the implemented TABLE-LAYOUT-01 Slice 1 changes against the approved Change Contract.

Reviewed production files:

- `src/Elements/RichTable.php`
- `src/Utils/StyleMapper.php`

Reviewed new/changed focused tests:

- `tests/Elements/TableLayout01ACurrentBehaviorCharacterizationTest.php`
- `tests/Elements/TableLayout01CSlice1CompatibilityCharacterizationTest.php`
- `tests/Elements/TableLayout01CSlice1TableStyleApiTest.php`
- `tests/Integration/TableLayout01AGeometryLifecycleCharacterizationTest.php`
- `tests/Integration/TableLayout01CSlice1LifecycleCharacterizationTest.php`

Relevant pre-existing regression tests inspected:

- `tests/Integration/RichTableSemanticOwnershipTest.php`
- `tests/Integration/StyleApi02FP0TableStyleCompatibilityTest.php`
- `tests/Document/TableStyleRequirementMaterializerTest.php`
- `tests/Integration/TableStyleSemanticsCharacterizationTest.php`
- `tests/Integration/PublicSampleSmokeTest.php`

The focused TABLE-LAYOUT test round has been reported green after the ownership-test corrections.

## 2. Diff scope

Relative to the Change Contract commit, Slice 1 changes are limited to:

- `RichTable` table-style authoring/state behavior;
- `StyleMapper` table-level semantic mapping;
- TABLE-LAYOUT characterization/API/lifecycle tests.

No unrelated production subsystem was modified.

**Review:** PASS.

The implementation does not introduce a new table context, layout service, global registry, row object, or Writer layout engine.

## 3. Master API

Contract:

```php
$table->setTableStyle([
    'width' => '15cm',
    'alignment' => 'center',
]);
```

Implementation:

- adds `setTableStyle(array $options): self`;
- maps friendly table-level options through `StyleMapper::mapTableStyleOptions()`;
- replaces the complete element-owned local table-style state;
- explicitly replaces named-reference mode when the master method is called;
- clears style/reference for an empty array.

**Review:** PASS.

## 4. Friendly mapper

Contracted mapping:

```text
width          -> style:width
relative-width -> style:rel-width
alignment      -> table:align
```

Implementation matches this mapping.

Native-prefixed keys are passed through as an advanced compatibility escape hatch, consistent with the API design pass. Unknown unprefixed table options are rejected.

**Review:** PASS.

The materializer remains unaware of friendly option names.

## 5. Convenience API

Contracted methods:

```php
setTableWidth()
setTableRelativeWidth()
setTableAlignment()
```

All three are implemented as mutations of the same `tableStyleOptions` state used by raw `setStyle()` and friendly `setTableStyle()`.

No parallel geometry/alignment state was introduced.

**Review:** PASS.

## 6. Width-mode semantics

The implementation matches the contract:

- master `width` + `relative-width` together -> `InvalidArgumentException`;
- `setTableWidth()` removes `style:rel-width`;
- `setTableRelativeWidth()` removes `style:width`;
- sequential convenience calls therefore implement last-width-mode-wins;
- column-width and column-ratio state is untouched.

**Review:** PASS.

## 7. Whole-table alignment

The implementation accepts exactly:

```text
left
center
right
margins
```

and maps to:

```text
table:align
```

The API remains explicitly table-specific through `setTableAlignment()`.

**Review:** PASS.

## 8. Named-reference call-order semantics

Current implementation correctly preserves the contract distinction:

```text
setTableStyleName(...)
    -> reference mode

setTableStyle(...)
    -> explicit switch to local generated definition

convenience mutation while reference-only
    -> LogicException

local definition -> setTableStyleName(...)
    -> named reference wins and local options are cleared
```

**Review:** PASS.

The production code does not invent clone, overlay, or mutation semantics for named styles.

## 9. Raw `setStyle()` compatibility

Raw `setStyle()` remains available and continues to:

- replace complete local table properties;
- clear style/reference when passed an empty array;
- accept native properties directly.

Friendly convenience mutation works on the same normalized local property state and preserves unrelated raw properties.

**Review:** PASS.

This satisfies the single-authority requirement.

## 10. Element-owned style ownership correction

The implementation changes the `RichTable` generated table definition from:

```text
common / styles.xml
```

to:

```text
automatic / content.xml
```

while reference-only requirements remain references.

This matches the Change Contract and restores the ownership rule already established by SR-07.

Focused lifecycle tests confirm that generated table properties now materialize in `content.xml`.

**Review:** PASS for the production change.

## 11. Authored common styles and references

Pre-existing architecture tests already provide useful protection:

- `RichTableSemanticOwnershipTest::testUnknownTableStyleNameProducesReferenceOnlyRequirement()`;
- `testReferenceOnlyInsertionPreservesReferenceWithoutFabricatingDefinition()`;
- `testAuthoredCommonTableDefinitionRemainsAuthoritative()`;
- `TableStyleRequirementMaterializerTest::testCommonTableDefinitionUsesTablePropertiesInStylesXml()`;
- `testExistingTableDefinitionRemainsAuthoritative()`.

The Slice 1 production diff does not modify the common-style materializer.

**Review:** architecturally PASS, but these tests must be included in the Slice 1 regression run before closeout.

## 12. Blocking stale regression expectation

One pre-existing test still encodes the superseded ownership behavior:

```text
tests/Integration/StyleApi02FP0TableStyleCompatibilityTest.php
```

It currently expects an element-owned `RichTable::setStyle()` definition to be:

```text
SCOPE_COMMON
PART_STYLES
```

That expectation now directly contradicts the approved TABLE-LAYOUT-01C contract:

```text
SCOPE_AUTOMATIC
PART_CONTENT
```

This is not evidence that production code should be reverted. The test describes an older STYLE-API-02 boundary that TABLE-LAYOUT-01C explicitly supersedes for generated element-owned table definitions.

**Review:** BLOCKING TEST ALIGNMENT REQUIRED before Slice 1 can be declared closed.

The test should retain its original purpose—element-local ownership and no process-global registry—but assert the newly approved automatic/content-local placement.

## 13. Focused contract-test coverage gaps

The new Slice 1 tests cover most of the contract well, but several explicit Change Contract cases are not yet independently asserted.

The following should be added or made explicit before Slice 1 closeout:

1. relative friendly master and relative convenience forms produce equivalent normalized state;
2. all three convenience methods reject reference-only mode, not only `setTableWidth()`;
3. raw master -> friendly master replacement;
4. friendly master -> raw master replacement;
5. friendly local definition -> `setTableStyleName()` reference transition;
6. invalid absolute table-width input;
7. friendly API materialization through the full save path, not only raw `setStyle()`, for at least one absolute and one relative case.

These are test-coverage gaps, not identified production defects.

**Review:** ALIGNMENT REQUIRED.

## 14. Validation implementation

Current validation is narrow and local:

- absolute width accepts explicit length strings;
- relative width accepts percentage strings;
- alignment is allow-listed.

This is consistent with the contract's prohibition on building a general CSS/ODF validator.

The small assignment to local `$options['alignment']` inside `validateFriendlyTableStyleOptions()` has no observable effect because the method receives the array by value and normalization occurs afterward. It is harmless but unnecessary.

**Review:** PASS with minor cleanup opportunity; not a blocker.

## 15. Style identity

Both master and convenience paths ultimately call the same local replacement helper and use the same normalized native properties for `StyleMapper::generateStyleName()`.

The existing focused test confirms equal generated identity for the absolute-width + alignment master/convenience pair.

**Review:** PASS, with the relative-equivalence test gap noted above.

## 16. Column compatibility

Production changes do not touch:

- `setColumnWidths()`;
- `setColumnWidthRatios()`;
- positional column style naming;
- 65535 Writer normalization;
- legacy first-row width side effect.

Focused tests preserve absolute and relative column coexistence.

**Review:** PASS.

## 17. Out-of-contract behavior

No production change was found that:

- alters row-height behavior;
- adds vertical cell alignment;
- changes column signatures;
- changes page layout;
- changes frame/image behavior;
- creates named-style overlay semantics;
- removes public/protected compatibility facades.

This is important because row height and cell vertical alignment belong to later slices.

**Review:** PASS.

## 18. Public Sample 11

Sample 11 still uses the older raw/native table-style syntax and historically contains the problematic `table:width` spelling.

This does not block the internal Slice 1 production architecture because the Change Contract places public-sample migration/closeout in the later integration/closeout work.

However, Sample 11 must not remain the final 1.0 teaching surface for table width.

It should eventually demonstrate the friendly API, for example:

```php
$table->setTableStyle([
    'width' => '15cm',
    'alignment' => 'left',
]);
```

or equivalent convenience calls.

**Review:** DEFERRED TO PUBLIC SAMPLE / CLOSEOUT WORK, not a Slice 1 production blocker.

## 19. Required Slice 1 regression set

Before Slice 1 closeout, run at minimum:

```text
tests/Elements/TableLayout01ACurrentBehaviorCharacterizationTest.php
tests/Elements/TableLayout01CSlice1CompatibilityCharacterizationTest.php
tests/Elements/TableLayout01CSlice1TableStyleApiTest.php
tests/Integration/TableLayout01AGeometryLifecycleCharacterizationTest.php
tests/Integration/TableLayout01CSlice1LifecycleCharacterizationTest.php

tests/Integration/RichTableSemanticOwnershipTest.php
tests/Integration/StyleApi02FP0TableStyleCompatibilityTest.php
tests/Document/TableStyleRequirementMaterializerTest.php
tests/Integration/TableStyleSemanticsCharacterizationTest.php
tests/Integration/RichTableColumnSemanticOwnershipTest.php
tests/Integration/RichTableRelativeColumnWidthSemanticOwnershipTest.php
tests/Integration/PublicSampleSmokeTest.php
```

The stale STYLE-API-02 test must first be contract-aligned.

A full `composer test` belongs to later preflight/closeout but remains useful after Slice 1 alignment if runtime is acceptable.

## 20. Review verdict

### Production architecture

**PASS.**

The Slice 1 production implementation matches the central TABLE-LAYOUT-01C decisions:

- correct master API;
- correct convenience family;
- single local table-style authority;
- deterministic call order;
- no named-style mutation;
- correct native mappings;
- automatic/content-local generated ownership;
- column semantics untouched.

### Slice 1 closure

**NOT YET FINAL.**

Before Slice 1 can be formally closed:

1. align the stale `StyleApi02FP0TableStyleCompatibilityTest` expectation with TABLE-LAYOUT-01C;
2. add the focused contract-test cases listed in section 13;
3. run the expanded Slice 1 regression set from section 19;
4. review any failures as compatibility evidence rather than automatically changing production behavior.

No contract amendment is currently indicated.

No production rollback is indicated.

The correct next action is a small **Slice 1 contract-alignment pass**, followed by the expanded regression run.

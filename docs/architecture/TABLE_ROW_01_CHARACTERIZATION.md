# TABLE-ROW-01 — Native Writer Table Population Characterization

**Status:** Characterization complete / Change Contract ready  
**Base:** `develop` at/after B02 merge  
**Purpose:** S03 bounded prerequisite

## 1. Decision context

TABLE-ROW-01 was already identified in PRODUCT-01C as the future named-table row audit. Phase E deliberately left native table `populate` semantics at **RESEARCH NEEDED** and explicitly refused to infer them from `RichTable` or structured placeholder insertion.

S03 now establishes the concrete need. The intended authoring model is pragmatic:

> LibreOffice Writer owns the named table, its geometry, styles and authored row structure. The engine replaces the table's application-owned tabular data while preserving the native table object and selected authored rows.

This is not whole-`table:table` replacement and does not introduce a universal named-element abstraction.

## 2. Existing repository baseline

Current `develop` already provides:

- `OdtTemplate::table(string)` and `TableTarget`;
- strict typed resolution through `TypedTargetResolver`;
- `TableDescriptor` with name, document part, row count, column count and containing Section;
- source-oriented `inspectTemplate()` and working-document `inspect()`;
- Section clone/instantiation with nested native identity rewriting;
- `RichTable` / `RichTableCell` for PHP-owned generated tables.

The current table target remains read-only. Phase E records native table `populate` as unresolved.

PRODUCT-01C already distinguishes:

```text
visible foreach
    template-language repetition

section instantiate
    native Section structural repetition

table row instantiate/populate
    table-specific structural mutation
```

TABLE-ROW-01 preserves that distinction.

## 3. Normative ODF characterization

ODF 1.2 Part 1 establishes the relevant native facts:

- `table:table-row` represents a table row and may occur directly in `table:table` or inside `table:table-header-rows`, `table:table-rows`, or row groups;
- a row owns `table:table-cell` and `table:covered-table-cell` children;
- cells may be empty and may contain paragraphs and other text content, including nested tables;
- cells may span rows/columns;
- rows and cells may use repetition attributes;
- table identity is `table:table/@table:name`.

Architecture consequence:

> A native Writer table is not necessarily one fixed XML nesting shape. Population logic must operate on logical table rows/cells while preserving their actual native containers and authored structure.

## 4. Writer/repository evidence

Existing Writer reference evidence already shows direct rows:

```xml
<table:table table:name="Tabelle1" ...>
    <table:table-column .../>
    <table:table-row ...>
        <table:table-cell ...>
            <text:p ...>Formatted cell text</text:p>
        </table:table-cell>
    </table:table-row>
</table:table>
```

Other Writer-authored fixtures use `table:table-header-rows` and `table:table-rows`.

Therefore both direct and grouped row forms are real Writer shapes.

### Current inspection defect/limitation

`DocumentInspector::tableRows()` currently iterates only child containers named:

- `table:table-rows`;
- `table:table-header-rows`.

It does not include a direct `table:table-row` child.

Consequently a valid Writer-authored named table can resolve correctly while `TableDescriptor::rowCount()` is wrong.

This must receive a characterization test and bounded correction before table population relies on inspection counts.

## 5. Revised population model

Earlier PRODUCT-01C intentionally left "template-row identity" unresolved. S03 does not require a separately named prototype row for the ordinary case.

The table itself is the stable named template object.

Population works from the Writer-authored row structure:

```text
named Writer table
    ↓
capture logical row structure
    ↓
classify preserved rows and mutable rows
    ↓
retain preserved rows unchanged
    ↓
reuse/clone mutable row structure as needed
    ↓
replace mutable cell payloads
    ↓
remove surplus mutable rows
    ↓
validate
```

This avoids introducing a bookmark-as-row convention, a universal row identity, or a fragile mandatory prototype-row marker.

## 6. Row classification

### 6.1 Native header rows

Rows inside `table:table-header-rows` are native header rows.

Default population semantics:

> Native header rows and their content are preserved.

A later explicit option may permit header-content replacement, but that is not required for the first S03 slice.

### 6.2 Preserved ordinary rows

An explicit `keepRows` option protects selected ordinary source rows, including their content.

Conceptual example only:

```php
[
    'keepRows' => [0, 1, 5],
]
```

The public method/options shape is finalized by the Change Contract, not by this characterization document.

**Index rule:** preserved row indices refer to the immutable source/Writer table row sequence captured before mutation. They do not refer to the changing post-mutation row positions.

This permits title rows, explanatory rows, subtotal/footer rows, or other ordinary Writer rows to remain untouched.

### 6.3 Mutable rows

Ordinary rows not protected by native-header semantics or `keepRows` form the mutable data-row pool.

The application data owns:

- resulting mutable-row count;
- row order;
- replacement cell payloads.

Writer owns the reusable row structure and formatting.

## 7. Structure preservation

Population must preserve, unless the contract explicitly says otherwise:

- the `table:table` node and `table:name`;
- table style reference;
- table columns and geometry;
- native header-row containers/content by default;
- preserved ordinary rows;
- row style references;
- cell style references;
- paragraph/text style structure used as the insertion context;
- surrounding Section containment;
- unrelated nested/native objects outside the mutable payload.

The operation must not rebuild the table through `RichTable`.

## 8. Payload replacement semantics

"Clear/strip content" means clear the application-owned payload, not destroy the cell's formatting structure.

For a simple Writer cell such as:

```xml
<table:table-cell table:style-name="BodyCell">
    <text:p text:style-name="BodyText">Old value</text:p>
</table:table-cell>
```

the desired semantic transformation is equivalent to retaining the cell and paragraph formatting context while replacing the text payload:

```xml
<table:table-cell table:style-name="BodyCell">
    <text:p text:style-name="BodyText">New value</text:p>
</table:table-cell>
```

The first implementation must not define arbitrary mixed-content subtree surgery.

## 9. Row growth and shrink behavior

Given N mutable Writer rows and M data rows:

- `M == N`: refill existing mutable rows;
- `M < N`: refill the first required mutable structures and remove surplus mutable rows;
- `M > N`: refill existing mutable rows and clone reusable Writer-authored mutable row structure for the additional rows;
- `M == 0`: remove mutable rows while retaining native headers and explicitly preserved rows.

For the bounded first slice, additional rows use the existing mutable Writer row structure as their style/layout source. If multiple mutable source rows have materially different structures, the implementation must either apply a documented deterministic pattern or reject the table until that behavior is characterized. It must not guess silently.

## 10. Supported first-slice topology

The minimum S03 prerequisite supports a deliberately bounded ordinary table case:

- one uniquely resolved named table in `content.xml`;
- direct and/or grouped logical rows;
- optional native header rows;
- optional `keepRows`;
- rectangular mutable rows with a stable logical cell count;
- scalar/string cell payloads;
- Writer-owned row/cell/paragraph styling;
- growth, shrink, and zero-data cases.

## 11. Explicit first-slice rejection cases

The first implementation should reject rather than guess when mutable rows contain semantics not yet characterized, including:

- merged/spanned mutable cells and covered-cell topology;
- `table:number-rows-repeated` on mutable rows;
- repeated cells where expansion/rewrite would change logical addressing;
- formulas or typed value semantics requiring recalculation/preservation rules;
- nested tables in mutable cells;
- frames/images/resources in mutable cells;
- protected cells;
- incompatible mutable row shapes.

These are capability limits, not evidence against the basic population model.

## 12. Lifecycle and identity

The operation acts on the current working DOM.

Required lifecycle semantics:

- strict typed table resolution; no first-match mutation;
- content tables only for the bounded slice;
- `inspectTemplate()` remains source-oriented;
- `inspect()` reflects the populated working table;
- retained target handles must resolve against current context rather than cache stale DOM nodes;
- repeated invocation must replace/reconcile current population rather than append indefinitely;
- `load()` / `refresh()` restore source state according to existing lifecycle semantics;
- save/reopen must preserve semantic structure in LibreOffice even if Writer normalizes XML grouping/style names.

Section clone/instantiate interaction must use the resulting working table identity and must not weaken existing ambiguity rules.

## 13. Characterization test gate

Before production mutation code, add focused tests for:

1. direct `table:table-row` inspection;
2. grouped `table:table-rows` inspection;
3. `table:table-header-rows` classification;
4. logical column counting with ordinary repeated columns/cells where inspection already claims support;
5. named-table strict duplicate/missing resolution;
6. Writer row clone preserving row/cell/paragraph style references;
7. shrinking mutable rows;
8. growing mutable rows;
9. zero-data population;
10. `keepRows` source-index stability;
11. repeated population on one working document;
12. save/reopen in LibreOffice.

Rendering-sensitive cases require manual LibreOffice regression.

## 14. Characterization conclusion

**GO.**

The existing typed table identity architecture is sufficient. S03 does not require a universal row marker or whole-table replacement.

The bounded semantic capability is:

> Populate the data region of an existing named Writer-authored table while preserving the table object, native header rows by default, explicitly protected source rows, and Writer-owned structural formatting.

This is sufficiently characterized to write the TABLE-ROW-01 Change Contract.

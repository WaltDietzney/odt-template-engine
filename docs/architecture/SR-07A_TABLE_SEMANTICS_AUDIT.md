# SR-07A — Table / Table-Cell Semantics Audit

Status: FINAL GO

Base: `develop` at `045c5a1936904af5dd67856812a0821d102900c0`

This document records the evidence gathered before a SR-07 Change Contract is written. It is not an approved API and it does not authorize table-layout behavior changes.

## 1. Scope

SR-07 concerns the semantic migration of table-related ODF style requirements used by structured insertion.

Potential native ODF style families are:

- `table`
- `table-column`
- `table-row`
- `table-cell`

SR-07 must keep style-family migration separate from behavioral table-layout work, including explicit table width, explicit/reliable column width, relative width, row/minimum height, and cell vertical alignment.

The purpose of SR-07A is to identify current producers, current materialization paths, active legacy/static state, DOM-side effects, style/structure boundaries, and compatibility surfaces before deciding which families belong in the semantic `StyleRequirement` model.

## 2. Current structured ownership

`RichTable` is an `OdtElement` and overrides `ownedElements()` to yield its `RichTableCell` children.

`RichTableCell` is also an `OdtElement` and yields its contained `Paragraph` or `RichText` when that content is an `OdtElement`.

The structured ownership chain is therefore already compatible with transitive semantic collection:

```text
RichTable
    -> RichTableCell
        -> Paragraph / RichText
            -> nested structured dependencies
```

This is an important existing foundation. SR-07 should reuse it rather than introduce a table-specific traversal model.

## 3. Current RichTable behavior

### 3.1 Table identity and style reference

`RichTable` creates a generated `table:name`. An application may set a table-level style reference through `setTableStyleName()`.

`toDomNode()` emits:

```xml
<table:table table:name="..." table:style-name="...">
```

when a style name was supplied.

The definition corresponding to that name is not owned by `RichTable`. The public sample path currently registers it separately through the process-global `StyleMapper::registerTableStyle()` registry.

### 3.2 Column definitions

`RichTable` has two distinct column paths.

With explicit column widths, `toDomNode()` calls `StyleWriter::writeColumnStyles()` before creating the table. That method directly appends `style:style` definitions with family `table-column` and a `style:table-column-properties` child to `office:automatic-styles` in the DOM passed to it. The table then emits `table:table-column` elements referencing those generated style names.

With ratio-based columns, `RichTable` emits structural `table:table-column` elements using `table:number-columns-repeated`; no style definition is involved in that path.

Without either explicit widths or ratios, it emits one structural column element repeated to the detected logical column count.

This establishes an immediate semantic distinction:

```text
column structure / repetition
        !=
column style definition (family table-column)
```

### 3.3 Cell style definitions as a DOM side effect

During `RichTable::toDomNode()`, the table iterates cells, calls `RichTableCell::toStyleDomNode()`, collects the resulting style nodes, and directly appends them to `office:automatic-styles` of the same DOM.

This means normal table rendering currently mixes:

- element-tree rendering;
- style-definition discovery;
- style-definition materialization.

That is unlike the semantic paragraph/text/graphic path established by SR-01 through SR-06.

### 3.4 Row-level style input

`addRow(array $cells, array $style = [])` stores a row-level style array, but the current `toDomNode()` implementation does not apply it to `table:table-row` and does not produce a `table-row` style definition.

Characterization and sample mapping found no active `table-row` style producer. The row-style argument is therefore a dormant compatibility surface. SR-07 must preserve that API behavior but does not need to introduce semantic `table-row` requirements in the implementation slice.

### 3.5 Width side channel

`setColumnWidths()` stores the widths on `RichTable` and also calls `setWidth()` on cells of the first row.

`RichTableCell::setWidth()` writes an internal `__column-width` option into the cell style. `StyleOptionSplitter` recognizes this key as cell-side compatibility data, while `StyleMapper::mapTableCellStyleOptions()` does not materialize it as a native table-cell property. The effective explicit-width output is instead produced separately by `StyleWriter::writeColumnStyles()` as family `table-column`.

This is evidence that width is already treated as a cross-layer compatibility/layout concern rather than a true `table-cell` style property.

SR-07 must not collapse this into the `table-cell` family merely because the convenience API currently passes through a cell object.

## 4. Current RichTableCell behavior

### 4.1 Mixed convenience style input

`RichTableCell::setStyle()` first calls `StyleOptionSplitter::split(..., 'table-cell')`.

The splitter separates the mixed convenience array into:

- cell properties;
- paragraph properties;
- text properties.

Cell appearance such as background, padding, and border stays on the cell. Text formatting is delegated to contained paragraph/text content. Paragraph alignment and paragraph behavior are delegated to the paragraph layer.

This separation is architecturally valuable and should be preserved.

### 4.2 Legacy/static table-cell registration

After mapping the cell portion, `RichTableCell::setStyle()` generates a style name and immediately calls `StyleMapper::registerTableCellStyle()`.

`registerStyles()` repeats this registration behavior.

`StyleMapper` stores table-cell styles in static process-global state.

This is a legacy/compatibility registration path and is not document-local semantic ownership.

### 4.3 Native family and property group

`RichTableCell::toStyleDomNode()` creates:

```xml
<style:style
    style:name="..."
    style:family="table-cell"
    style:parent-style-name="Default">
    <style:table-cell-properties ... />
</style:style>
```

The native semantic identity is therefore clearly:

```text
family: table-cell
property group: style:table-cell-properties
```

This is a strong candidate for semantic `StyleRequirement` production.

### 4.4 Structural spans

`colspan` and `rowspan` are emitted directly as:

- `table:number-columns-spanned`
- `table:number-rows-spanned`

on `table:table-cell`.

These are structural cell semantics, not style properties, and must remain outside `StyleRequirement`.

## 5. Current StyleMapper / StyleWriter compatibility paths

### 5.1 Table-cell registry

`StyleMapper` stores table-cell styles in static process-global registries. `StyleWriter::writeAllStyles()` later materializes all registered table-cell styles into `styles.xml` `office:styles` as family `table-cell`.

At the same time, `RichTable::toDomNode()` can directly materialize the same conceptual cell definition into the current rendering DOM's `office:automatic-styles`.

The engine therefore currently has at least two table-cell definition paths with different document-part/scope behavior.

This is a central SR-07 compatibility boundary.

### 5.2 Table registry

`StyleMapper::registerTableStyle()` stores table styles in static process-global state. `StyleWriter::writeAllStyles()` materializes them into `styles.xml` `office:styles` as family `table` with a `style:table-properties` property group.

`RichTable` itself only carries a `table:style-name` reference and does not semantically own the definition.

This is a legacy definition/reference split that the SR-07 Change Contract must address explicitly.

### 5.3 Table-column direct writer

`StyleWriter::writeColumnStyles()` does not use a registry. It directly mutates the supplied document and writes automatic family `table-column` definitions with names such as `co0`, `co1`, ... .

Repository call-surface review found one production call site: `RichTable::toDomNode()` for the explicit-width path. The writer has no document-local semantic conflict model and its generated names are positional rather than definition-derived.

Characterization also confirms that an authored automatic `table-column` definition named `co0` is not authoritative to this writer: calling it again for `co0` appends a second same-name definition instead of reusing or rejecting the authored definition.

### 5.4 Table-row

No active static registry, direct materializer, sample producer, or characterized `RichTable` producer exists for family `table-row` in the inspected repository paths.

`table-row` is therefore outside the active SR-07 semantic migration slice. The dormant `RichTable::addRow(..., $style)` argument remains compatibility-sensitive and must not be removed or silently activated.

## 6. Normal structured insertion path

`OdtTemplate::setElement()` now collects semantic `StyleRequirement` values before DOM insertion and materializes them through the document-local style context.

However, after semantic paragraph/text/graphic migration it still calls `getStyleDefinitions()` for `HasStyles` elements and the element's `toDomNode()` remains free to perform table-style side effects.

`RichTableCell::getStyleDefinitions()` returns its cell definition through this compatibility interface, while `RichTable::toDomNode()` also writes cell and column styles directly.

SR-07 therefore enters a mixed state:

```text
semantic paragraph/text/graphic requirements
        +
HasStyles compatibility definitions
        +
static table/table-cell registries
        +
direct table/cell/column DOM materialization
```

The purpose of SR-07 is to reduce this mixed table-style path without changing layout semantics.

## 7. Final responsibility classification

| Current behavior | SR-07 classification |
| --- | --- |
| `table:name` | STRUCTURE / IDENTITY |
| `table:style-name` | STYLE REFERENCE candidate |
| table style definition | SEMANTIC STYLE candidate, family `table` |
| `table:table-column` | STRUCTURE |
| `table:number-columns-repeated` | STRUCTURE |
| explicit column-width style definition | SEMANTIC STYLE candidate, family `table-column`; width behavior itself remains unchanged |
| `table:table-row` | STRUCTURE |
| stored row style array | DORMANT COMPATIBILITY; no semantic migration in SR-07 |
| `table:table-cell` | STRUCTURE |
| `table:number-columns-spanned` | STRUCTURE |
| `table:number-rows-spanned` | STRUCTURE |
| cell background/border/padding | SEMANTIC STYLE candidate, family `table-cell` |
| paragraph alignment inside a cell | PARAGRAPH semantic responsibility |
| text font/color/emphasis inside a cell | TEXT semantic responsibility |
| physical resources nested inside cell content | RESOURCE responsibility through existing structured ownership |

## 8. Characterization questions and evidence

The focused `TableStyleSemanticsCharacterizationTest` suite is green with **14 tests and 149 assertions**. No production code was changed by SR-07A.

### 8.1 Confirmed characterization findings

- Normal `setElement()` insertion exposes styled table-cell definitions through the current compatibility channels while `RichTable::toDomNode()` still performs direct automatic-style materialization. Table-cell ownership therefore has more than one active path.
- Legacy `assign()` / `render()` processing causes the same table-cell style name to appear twice in `styles.xml`: once in `office:automatic-styles` through the direct `RichTable::toDomNode()` side effect and once in `office:styles` through later static-registry finalization in `StyleWriter::writeAllStyles()`.
- The process-global `StyleMapper` registries for both table and table-cell styles can leak styles from document A into later finalization of document B.
- `load()` does not clear those static table registries; document-local lifecycle reset and static compatibility state therefore remain distinct.
- Explicit column widths materialize family `table-column` definitions in `content.xml` `office:automatic-styles`.
- Generated column style names are positional (`co0`, `co1`, ...), and the writer can reuse such names without semantic conflict resolution.
- An existing authored automatic `co0` table-column definition remains in place, but `StyleWriter::writeColumnStyles()` appends another `co0`; both definitions and both widths remain present.
- The row-style argument accepted by `RichTable::addRow(..., $style)` is dormant: it produces neither a `table-row` style definition nor a row `table:style-name` reference.
- Existing authored common table and table-cell definitions remain authoritative when `StyleWriter::writeAllStyles()` encounters a static registration with the same identity.
- `RichTableCell::toStyleDomNode()` creates `style:table-cell-properties` without namespace-aware DOM identity. The in-memory node has `nodeName === 'style:table-cell-properties'` and `namespaceURI === null`, even though serialized XML may still look prefix-correct.
- `RichTableCell::getStyleDefinitions()` exposes its compatibility cell definition, while `RichTable::getStyleDefinitions()` does not recursively aggregate definitions from owned cells. Semantic `ownedElements()` traversal and the legacy compatibility definition API therefore have different traversal semantics.
- Repeated `save()` after one `setElement()` is not fully idempotent: the common table-cell definition grows from one to two definitions; the automatic table-cell definition and automatic `co0` in `content.xml` remain single, and no `co0` appears in `styles.xml`.
- Repeated `setElement()` on an already replaced placeholder is accepted by the current lifecycle. Both table/cell contents remain present; each cell style appears once in common `styles.xml` and once automatically in `content.xml`, while `co0` appears twice in `content.xml` and not in `styles.xml`.

These observations describe current behavior only. They do not authorize correcting dual materialization, static leakage, positional naming, duplicate definitions, dormant row-style input, namespace construction, or repeated lifecycle behavior as incidental cleanup.

### 8.2 Coverage review

| # | Status | Evidence / architectural consequence |
| --- | --- | --- |
| 1 | SUFFICIENT | Normal insertion and the direct `RichTable::toDomNode()` side-effect path are identified. A synthetic phase-by-phase interception test would add implementation detail without changing the migration decision. |
| 2 | CONFIRMED | Current normal insertion records table-cell definitions in automatic `content.xml` and common `styles.xml` channels. |
| 3 | CONFIRMED | Repeated `save()` and repeated `setElement()` behavior are explicitly characterized for cell and column definitions. |
| 4 | CONFIRMED | Separate-process characterization confirms cross-document leakage for both table and table-cell static registries. |
| 5 | CONFIRMED | Static table registry state remains observable after `load()`. |
| 6 | CONFIRMED | Authored common table/table-cell definitions remain authoritative in `writeAllStyles()`; authored automatic `table-column` names do not prevent duplicate `co0` output from `writeColumnStyles()`. |
| 7 | CONFIRMED | Positional `co0` reuse without conflict resolution is characterized. |
| 8 | CONFIRMED | Repository audit found one production call site for `writeColumnStyles()`, the explicit-width path in `RichTable::toDomNode()`, writing automatic styles to the supplied content DOM. |
| 9 | SUFFICIENT / COMPATIBILITY-SENSITIVE | `RichTableCell::toStyleDomNode()` is actively consumed by `RichTable`; the generic template compatibility path also recognizes `toStyleDomNode()`. External subclass reliance cannot be disproven and need not be to preserve the facade. |
| 10 | CONFIRMED / COMPATIBILITY-SENSITIVE | `RichTableCell::getStyleDefinitions()` is part of `HasStyles` and the template compatibility registration path consumes `getStyleDefinitions()`. Migration must preserve the compatibility facade until it can be narrowed safely. |
| 11 | CONFIRMED | Legacy `assign()` / `render()` confirms direct automatic-style materialization plus static common-style finalization, including dual `styles.xml` table-cell definitions. |
| 12 | CONFIRMED | No active `table-row` style producer was found in the characterized RichTable path or mapped samples; the row-style argument is dormant compatibility surface. |
| 13 | CONFIRMED | Samples 11–15, 19, and 20 are mapped below and a focused visual subset is selected. |

No remaining SR-07A evidence gap blocks a Change Contract.

### 8.3 Public sample / family matrix

| Sample | `table` | `table-column` | `table-cell` | `table-row` | Primary relevance |
| --- | --- | --- | --- | --- | --- |
| 11 | yes | yes | yes | no | Registered table style, explicit column widths, styled cells; strongest combined SR-07 regression sample. |
| 12 | no | no | yes | no | Cell styling plus paragraph/text responsibility separation. |
| 13 | no | no | yes | no | Styled cells with structural colspan/rowspan and multiple table shapes. |
| 14 | no | no | no | no | Paragraph/tab-stop example; tabular appearance without `RichTable`; useful negative boundary evidence. |
| 15 | no | no | yes | no | Simple styled-cell path used by integration tests. |
| 19 | no | no | yes | no | HTML importer creates native `RichTable` / `RichTableCell`; HTML table style participates as cell-default compatibility input. |
| 20 | no | no | yes | no | Ratio-based columns remain structural and do not create `table-column` style definitions. |

Recommended SR-07 visual-regression subset after implementation:

- **Sample 11** — mandatory combined `table` + `table-column` + `table-cell` coverage.
- **Sample 13** — structural span regression while cell-style ownership changes.
- **Sample 19** — HTML-import compatibility path.
- **Sample 20** — ratio-column structural boundary and styled-cell coverage.

Sample 15 remains useful for automated integration tests but adds little visual coverage beyond the selected subset. Sample 14 is negative boundary evidence rather than a table-style visual target.

## 9. Compatibility surfaces to preserve during SR-07

Treat these as compatibility-sensitive:

- `RichTable::setTableStyleName()`
- `RichTable::setColumnWidths()`
- `RichTable::addRow(..., $style)` even though row style is currently not materialized
- `RichTableCell::setStyle()` and fluent cell style helpers
- `RichTableCell::getStyleDefinitions()`
- `RichTableCell::toStyleDomNode()`
- `RichTableCell::registerStyles()`
- `StyleMapper::registerTableCellStyle()` / table-cell getters
- `StyleMapper::registerTableStyle()` / table getters
- `StyleWriter::writeAllStyles()` table/table-cell finalization
- `StyleWriter::writeColumnStyles()`
- legacy `assign()` / `render()` behavior
- repeated render/save lifecycle behavior

Protected or public extension points must not be removed merely because a semantic replacement exists.

## 10. Explicit non-goals

SR-07 must not use the semantic migration to silently fix or redesign:

- table width behavior;
- column width quality;
- relative width semantics;
- virtual 12-column ratio behavior;
- row/minimum height;
- vertical cell alignment;
- colspan/rowspan behavior;
- header-row behavior;
- table naming;
- authoring APIs;
- unrelated text/paragraph behavior.

Unexpected legacy behavior remains characterization evidence unless a later Change Contract explicitly authorizes a behavior change.

## 11. Architectural direction supported by SR-07A

SR-07A supports the following scope for the Change Contract:

```text
RichTable / RichTableCell
        |
        +-- structure
        |     table / row / cell / spans / repetition
        |
        +-- semantic style requirements
        |     table
        |     table-column
        |     table-cell
        |
        +-- dormant compatibility
        |     row-style argument (no semantic table-row producer)
        |
        +-- owned child requirements
              paragraph / text / resources / nested content
```

The semantic style path should use the existing document-local `StyleRequirement` / `StyleContext` infrastructure rather than a table-specific context.

The Change Contract must decide scope/document-part semantics for `table`, `table-column`, and `table-cell` from the characterized behavior and ODF semantics. In particular, it must distinguish common authored-definition precedence from the current automatic positional `coN` behavior rather than silently treating those paths as equivalent.

Static table/table-cell registries and direct DOM writers are compatibility paths to narrow, not authoritative semantic ownership. Their public/protected observability must be preserved where required while document-local semantic ownership becomes authoritative for the migrated path.

## 12. SR-07A completion gate

**FINAL GO.**

The completion conditions are satisfied:

- the table-family producer/materializer matrix is complete for the active SR-07 scope;
- active, legacy, direct-DOM, and compatibility paths are identified;
- the focused characterization suite is green with 14 tests / 149 assertions;
- repeated lifecycle and authored-column collision behavior are characterized;
- repository call-surface review for `writeColumnStyles()` is complete;
- public samples 11–15, 19, and 20 are mapped to the families they exercise;
- `table-row` is bounded as dormant compatibility rather than an active semantic migration target;
- no production behavior change was mixed into SR-07A;
- sufficient evidence exists to write the SR-07 Change Contract.

SR-07A is complete. The next architecture step is the SR-07 Change Contract; implementation must not begin before that contract is reviewed.
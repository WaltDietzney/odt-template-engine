# SR-07A — Table / Table-Cell Semantics Audit

Status: AUDIT IN PROGRESS

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

The purpose of SR-07A is therefore to identify current producers, current materialization paths, active legacy/static state, DOM-side effects, style/structure boundaries, and compatibility surfaces before deciding which families belong in the semantic `StyleRequirement` model.

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

Accordingly, `table-row` is a potential ODF family but there is no verified active producer in `RichTable` from this API at present.

This must be characterized before SR-07 decides whether `table-row` belongs in the implementation scope or should be explicitly bounded as future work.

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

This is a central SR-07 characterization target.

### 5.2 Table registry

`StyleMapper::registerTableStyle()` stores table styles in static process-global state. `StyleWriter::writeAllStyles()` materializes them into `styles.xml` `office:styles` as family `table` with a `style:table-properties` property group.

`RichTable` itself only carries a `table:style-name` reference and does not semantically own the definition.

This resembles a legacy definition/reference split and requires explicit characterization before migration.

### 5.3 Table-column direct writer

`StyleWriter::writeColumnStyles()` does not use a registry. It directly mutates the supplied document and writes automatic family `table-column` definitions with names such as `co0`, `co1`, ... .

The method has no document-local semantic conflict model and its generated names are positional rather than definition-derived.

This is likely the most direct table-family equivalent of the pre-semantic style materialization patterns migrated in earlier SR slices, but SR-07 must first preserve its actual behavior through tests.

### 5.4 Table-row

No equivalent active static registry or `RichTable` producer has yet been identified for family `table-row` in the inspected path.

Treat `table-row` as UNKNOWN until repository-wide characterization confirms the active and legacy surfaces.

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

## 7. Initial responsibility classification

The following classification is evidence-based but still subject to characterization tests.

| Current behavior | Classification for SR-07A |
| --- | --- |
| `table:name` | STRUCTURE / IDENTITY |
| `table:style-name` | STYLE REFERENCE candidate |
| table style definition | SEMANTIC STYLE candidate, family `table` |
| `table:table-column` | STRUCTURE |
| `table:number-columns-repeated` | STRUCTURE |
| explicit column-width style definition | SEMANTIC STYLE candidate, family `table-column`; layout behavior itself out of scope |
| `table:table-row` | STRUCTURE |
| stored row style array | UNKNOWN / currently unused in inspected path |
| `table:table-cell` | STRUCTURE |
| `table:number-columns-spanned` | STRUCTURE |
| `table:number-rows-spanned` | STRUCTURE |
| cell background/border/padding | SEMANTIC STYLE candidate, family `table-cell` |
| paragraph alignment inside a cell | PARAGRAPH semantic responsibility |
| text font/color/emphasis inside a cell | TEXT semantic responsibility |
| physical resources nested inside cell content | RESOURCE responsibility through existing structured ownership |

## 8. Characterization questions required before a Change Contract

SR-07A is not complete until tests answer at least the following questions.

1. Which table/table-cell definitions are written by `setElement()` before and after `RichTable::toDomNode()`?
2. Does one cell definition appear in `content.xml`, `styles.xml`, or both under current normal insertion?
3. Are repeated `setElement()` / `save()` operations idempotent for table, table-cell, and table-column definitions?
4. Does static `StyleMapper` table/table-cell state leak from document A into document B during `save()`?
5. Does `load()` reset only document-local state while static table registries remain observable?
6. Which existing target-document definition wins when an authored table/table-cell/table-column style has the same name as a generated/registered definition?
7. What happens when two generated column-width definitions reuse positional names such as `co0` in the same document?
8. Is `StyleWriter::writeColumnStyles()` writing to the intended ODF part/scope for all current call sites?
9. Is `RichTableCell::toStyleDomNode()` required by public/protected compatibility behavior outside `RichTable`?
10. Is `getStyleDefinitions()` for `RichTableCell` currently relied on by `OdtTemplate::registerStyles()` in a way that must survive migration?
11. Does the legacy `assign()` / `render()` structured path produce tables through the same static registries and direct DOM materialization?
12. Are there active producers or tests for family `table-row`, or is the row-style argument only dormant compatibility surface?
13. Which public samples 11–15, 19, and 20 exercise each table family and which of them should form the visual regression subset for SR-07?

## 9. Compatibility surfaces to preserve during SR-07

Until characterization proves otherwise, treat these as compatibility-sensitive:

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

Unexpected legacy behavior should be characterized and documented first.

## 11. Preliminary architectural direction

No Change Contract is approved yet, but the inspected code supports the following likely target shape:

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
        |     table-row only if an active producer is proven
        |
        +-- owned child requirements
              paragraph / text / resources / nested content
```

The semantic style path should use the existing document-local `StyleRequirement` / `StyleContext` infrastructure rather than a table-specific context.

Whether table definitions are common or automatic, and whether they belong in `styles.xml` or `content.xml`, must be decided from existing behavior and real LibreOffice-authored ODF evidence. That decision belongs after characterization, not in this audit.

## 12. SR-07A completion gate

SR-07A reaches FINAL GO only when:

- the table-family producer/materializer matrix is complete;
- active, legacy, and compatibility paths are identified;
- the characterization questions above have test evidence;
- relevant samples are mapped to the families they exercise;
- no behavior change has been mixed into the audit;
- enough evidence exists to write the SR-07 Change Contract.

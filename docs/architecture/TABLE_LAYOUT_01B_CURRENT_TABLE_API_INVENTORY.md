# TABLE-LAYOUT-01B — Current Table API Inventory

Status: EVIDENCE / CURRENT-STATE INVENTORY

Parent milestone: `TABLE-LAYOUT-01`

Purpose: reconstruct the current table-facing API, semantic ownership, mapping/materialization pipeline, compatibility paths, and intentionally deferred layout semantics before TABLE-LAYOUT-01C freezes any new contract.

This document is descriptive. It does not authorize production changes and does not select final new API names.

## 1. Why this inventory exists

The current table implementation is the result of several architecture generations. STYLE-CONTEXT-01, STYLE-API-02, and SR-07 deliberately moved ownership and materialization into document-local semantic infrastructure before all table-layout semantics were redesigned.

That sequencing matters. Several apparently inconsistent table surfaces are not evidence that the architecture work failed; they are compatibility or provisional surfaces deliberately left in place while ownership was corrected first.

The governing rule for this inventory is therefore:

> Reconstruct current intent and compatibility before designing the missing table-layout surface.

In particular, do not infer target API semantics merely from current raw ODF-looking option keys or old sample syntax.

## 2. Architectural history recovered

### 2.1 STYLE-API-02 target

STYLE-API-02 established three relevant principles:

1. ordinary application authoring should use friendly element-centric options;
2. named style references are distinct from definitions;
3. `StyleMapper` should be stateless and map friendly authoring options to normalized ODF property arrays.

For table families specifically, STYLE-API-02 deliberately retained element-specific options and structural APIs as primary and did not introduce a generic named table-style registry or a generic `defineStyle($family, ...)` API.

### 2.2 STYLE-API-02F table decision

STYLE-API-02F removed process-global style ownership from `StyleMapper` and explicitly distinguished:

```text
named table style reference
    !=
element-owned generated table style definition
```

It fixed the ownership direction:

```text
RichTable
    -> normalized table properties
    -> StyleRequirement
    -> StyleContext
```

but intentionally did not settle the final public method name or complete the friendly table-level option mapping. The conceptual `RichTable::setStyle([...])` example in that contract was an ownership example, not a decision that raw ODF QNames are the desired long-term application syntax.

### 2.3 SR-07 scope boundary

SR-07 then migrated semantic ownership for the `table`, `table-column`, `table-row`, and `table-cell` families while explicitly keeping behavioral table-layout work separate.

The SR-07 audit names the deferred concerns directly:

- explicit table width;
- explicit/reliable column width behavior;
- relative width;
- row/minimum height;
- cell vertical alignment.

This confirms that TABLE-LAYOUT-01 is the intended follow-up milestone for those semantics rather than a reopening of style ownership.

### 2.4 SR-07 row activation

The second argument of `RichTable::addRow(array $cells, array $style = [])` was historically dormant. SR-07E1 characterized it before activating exactly one supported key:

```text
min-row-height
    -> style:min-row-height
    -> family table-row
    -> automatic/content.xml
```

Other row keys remained ignored for compatibility. Fixed `row-height` was explicitly not activated.

### 2.5 SR-07H ratio correction

SR-07H established that `setColumnWidthRatios()` describes relative physical column widths, not structural virtual columns. The public API remained unchanged, but its semantic representation was corrected to `table-column` requirements using `style:rel-column-width` and LibreOffice Writer's 65535 interoperability normalization.

This is an important example of the architecture rule:

> Public semantic meaning is more authoritative than a defective legacy representation.

## 3. Current public `RichTable` surface

The current `RichTable` object combines structure, table-style reference/definition state, table-column geometry, narrowly activated row geometry, and convenience table construction.

### 3.1 Structure and identity

| Public method | Current meaning | Owner/state | ODF effect | Status |
| --- | --- | --- | --- | --- |
| `__construct()` | creates generated table identity | `tableName` | `table:name` | established structure |
| `setTableName(string): self` | overrides table identity | `tableName` | `table:name` | established structure |
| `addRow(array, array = []): self` | appends logical row, wraps non-cell values | `rows` | `table:table-row` + cells | established; second argument compatibility-sensitive |
| `setHeaderRowCount(int): self` | marks initial rows as headers | `headerRowCount` | `table:table-header-rows` | established structure |

`ownedElements()` yields every `RichTableCell`, so nested style/resource semantics participate in generic structured traversal rather than a table-specific collector.

### 3.2 Table style reference

```php
$table->setTableStyleName('ExistingTableStyle');
```

Current meaning:

```text
reference an existing/current-document table style
```

Internal state:

```text
tableStyleName = supplied name
tableStyleOptions = []
```

`getOwnStyleRequirements()` emits a table-family reference requirement. No definition is fabricated.

Status: **modern/authoritative reference semantics**. This is aligned with STYLE-API-02 and SR-07.

### 3.3 Element-owned table style definition

```php
$table->setStyle([...]);
```

Current behavior:

- stores the supplied array directly in `tableStyleOptions`;
- generates a style name from that array;
- emits a common `table` definition in `styles.xml` with property group `style:table-properties`;
- does not currently run the array through a table-specific friendly-option mapper.

Empty input clears both table style options and style name.

Status: **modern ownership path, incomplete/deferred authoring semantics**.

The ownership architecture is intentional and current. The raw/unmapped shape of the option array must not be mistaken for the final public style language.

There is currently no `StyleMapper::mapTableStyleOptions()` and no `StyleOptionSplitter` `table` context.

### 3.4 Absolute column widths

```php
$table->setColumnWidths(['5cm', '3cm', '2cm']);
```

Current meaning:

```text
one explicit physical width per logical table column
```

Current semantic ownership:

```text
RichTable
    -> table-column StyleRequirement
    -> automatic/content.xml
    -> style:table-column-properties
    -> style:column-width
```

The method currently returns `void`, not `self`.

Compatibility side effect: when a first row already exists, it calls `RichTableCell::setWidth()` on corresponding first-row cells. That writes internal `__column-width` compatibility data to those cells. This is not the authoritative native column-width materialization.

`toDomNode()` also retains a guarded direct compatibility fallback through `StyleWriter::writeColumnStyles()` if the expected column style definitions are not already present in the target DOM.

Status: **semantic column API established; compatibility remnants still present**.

TABLE-LAYOUT-01 must not casually remove the first-row side channel or direct writer fallback while changing unrelated geometry semantics.

### 3.5 Relative column widths

```php
$table->setColumnWidthRatios([2, 1, 1]);
```

Current meaning:

```text
relative width weights of actual logical columns
```

Current semantic ownership:

```text
RichTable
    -> normalized table-column StyleRequirement
    -> automatic/content.xml
    -> style:rel-column-width
```

For positive integer weights the current normalization uses the Writer-compatible 65535 space with final-column remainder assignment.

Example:

```text
[2, 1, 1]
    -> 32766* / 16383* / 16386*
```

The method currently returns `void`.

Status: **modern/authoritative semantic geometry API after SR-07H correction**.

Relative ratios do not create artificial spans or virtual columns. Real colspan/rowspan remain structural cell semantics.

### 3.6 Row-level style argument

```php
$table->addRow($cells, [
    'min-row-height' => '0.8cm',
]);
```

Current recognized row key:

```text
min-row-height
```

Materialization:

```text
RichTable row
    -> table-row StyleRequirement
    -> automatic/content.xml
    -> style:table-row-properties
    -> style:min-row-height
```

A structural `table:style-name` is added to that row using deterministic `<tableName>_ro<index>` identity.

Unsupported row-style keys remain silently ignored. That behavior was an explicit SR-07E compatibility decision, not evidence that arbitrary row styles are supported.

Status: **narrow modern semantic producer on a compatibility-sensitive array surface**.

Fixed/exact row height remains a deliberate TABLE-LAYOUT-01 gap.

### 3.7 Array/table-preset convenience layer

Current public methods include:

```text
buildTableFromArray()
addCustomStyle()
getCustomStyle()
setSummaryKeywords()
```

`buildTableFromArray()` chooses role-based cell style arrays such as `header`, `row`, `row-alt`, `summary`, and `highlight`, creates `RichTableCell` objects, and sends those style arrays through the normal cell style path.

This layer does not define a table-family style. Its word "style" describes collections of **cell/content presentation presets**, not the table-level ODF `table` style family.

Status: **existing convenience API / naming overlap**. This distinction matters when evaluating a possible future method name such as `setTableStyle(...)`.

## 4. Current public `RichTableCell` surface

`RichTableCell` is significantly further along in the target style architecture than `RichTable` table-level styling.

### 4.1 Content and ownership

A cell accepts string, `Paragraph`, or `RichText` content. String input is wrapped into a `Paragraph` so paragraph/text semantics can be separated correctly.

`ownedElements()` yields the contained structured element, producing this ownership chain:

```text
RichTable
    -> RichTableCell
        -> Paragraph / RichText
            -> nested elements/resources
```

This chain is modern and authoritative.

### 4.2 Mixed friendly style arrays

Normal cell authoring may use:

```php
new RichTableCell('Total', [
    'background' => '#12324a',
    'border' => '0.5pt solid #0b1f2d',
    'padding' => '0.15cm',
    'text-align' => 'right',
    'bold' => true,
    'color' => '#ffffff',
]);
```

The pipeline is:

```text
RichTableCell::setStyle()
    -> StyleOptionSplitter::split(..., 'table-cell')
        -> cell options
        -> paragraph options
        -> text options
    -> StyleMapper::mapTableCellStyleOptions(cell)
    -> cell StyleRequirement

paragraph options
    -> contained Paragraph/RichText

text options
    -> contained Paragraph/RichText text semantics
```

This is exactly the friendly element-centric authoring model accepted by STYLE-API-02.

### 4.3 Responsibility split

Current intended ownership is:

```text
RichTableCell
    background
    border
    padding

Paragraph inside cell
    horizontal text alignment
    paragraph margins/spacing/flow

Text inside paragraph
    bold/italic/color/font properties
```

This split must remain authoritative when adding vertical alignment: vertical **cell** alignment is not paragraph `text-align` and not table placement.

### 4.4 Cell structural spans

Public methods include `setColspan()` / `setRowspan()` and compatibility/convenience aliases used by samples such as `colspan()` / `rowspan()`.

These materialize directly as structural attributes:

```text
table:number-columns-spanned
table:number-rows-spanned
```

Status: **established structural semantics**, outside style/layout mapping.

### 4.5 Fluent appearance methods

Current public fluent helpers include background, border-side, and padding-side methods. They update local native-normalized cell property state and refresh generated style identity.

The method name `registerStylesAndRefresh()` is historical: after STYLE-API-02F it no longer owns process-global registration; it remaps local properties and recomputes style identity. Its public visibility/name is compatibility residue, not a target architectural concept.

### 4.6 Cell `setWidth()`

`RichTableCell::setWidth(string)` writes `__column-width` to internal cell style state.

It is not native table-cell width semantics and is not the authoritative explicit-column producer. Its current practical role is tied to the `RichTable::setColumnWidths()` first-row compatibility side channel.

Status: **legacy/compatibility internal convention exposed through a public method**. TABLE-LAYOUT-01 should characterize/preserve it unless explicitly deciding a migration; it should not be used as the conceptual basis for table width.

### 4.7 `alignCenter()` / `alignLeft()` / `alignRight()`

These methods operate on the cell's contained paragraph by assigning paragraph style names such as `CenterPara`, `LeftPara`, and `RightPara`.

They mean **horizontal content alignment inside a cell**, not vertical cell alignment and not whole-table placement.

This existing naming makes a generic future `RichTable::setAlignment()` especially ambiguous from the user's perspective.

## 5. StyleOptionSplitter inventory

`StyleOptionSplitter` currently supports contexts:

```text
paragraph
table-cell
```

There is no table-level context.

For `table-cell`, it deliberately sorts a mixed convenience array into:

```text
cell
paragraph
text
```

Known native-prefixed properties are preserved as an advanced compatibility escape hatch. Unknown semantic keys are also retained on the native context rather than rejected.

This is important for compatibility but should not be confused with the preferred public language.

TABLE-LAYOUT-01 must decide whether table-level friendly options need a dedicated table mapper, a splitter extension, direct explicit methods, or a combination. The current architecture does not already answer that question.

## 6. StyleMapper inventory

Current `StyleMapper` is stateless. Relevant mapping methods include:

```text
mapParagraphStyle()
mapTextStyleOptions()
mapTableCellStyleOptions()
mapFrameStyleOptions()
mapImageStyleOptions()
parseInlineStyle()
generateStyleName()
```

There is no current:

```text
mapTableStyleOptions()
mapTableRowStyleOptions()
mapTableColumnStyleOptions()
```

This absence is consistent with the architecture history:

- column widths/ratios have dedicated structural/geometry APIs;
- the row API was activated only for one explicitly contracted key;
- cell style arrays already needed a mixed responsibility mapper;
- friendly table-level style/layout option semantics were left for later.

The absence of `mapTableStyleOptions()` is therefore a current capability gap, not evidence that public callers should use ODF QNames indefinitely.

## 7. Semantic style materialization

`StyleRequirementMaterializer` now explicitly supports all four table-related families:

```text
table
table-column
table-row
table-cell
```

It receives already-normalized native property groups and writes them verbatim into the correct ODF style container according to requirement family/scope/document part.

Mapping is deliberately outside the materializer.

This yields the intended architecture:

```text
application semantics
    -> element mapping / dedicated semantic producer
    -> StyleRequirement
    -> StyleContext
    -> StyleRequirementMaterializer
    -> ODF
```

Therefore TABLE-LAYOUT-01 should not add public semantics directly to the materializer or make it understand friendly names.

## 8. Remaining StyleWriter compatibility path

The current `StyleWriter` has been reduced to one direct helper:

```php
StyleWriter::writeColumnStyles(DOMDocument $doc, array $columnWidths)
```

It writes automatic `table-column` definitions (`co0`, `co1`, ...) directly into the supplied DOM.

`RichTable::toDomNode()` uses this only for absolute widths and only when its `hasAllColumnStyles()` guard does not find the expected definitions.

Normal semantic ownership is already via `StyleRequirement`; this direct writer is a compatibility/fallback path.

Status: **bounded compatibility debt; not target authoring architecture**.

TABLE-LAYOUT-01 should avoid expanding it or using it as a model for new table-width/row/cell capabilities.

## 9. HtmlImporter table path

`HtmlImporter` converts HTML `<table>` structures into native `RichTable` / `RichTableCell` objects.

For each HTML cell it:

1. parses CSS-like style input;
2. builds styled paragraph content separately;
3. filters a narrow set of cell-level keys such as background, borders, and padding;
4. passes those through `RichTableCell::setStyle()`;
5. maps HTML colspan/rowspan to cell structural span APIs;
6. appends the cells through `RichTable::addRow()`.

The importer does **not** currently use HTML table width/alignment to author a `RichTable` table-family style. Table-level parsed CSS is mainly used as default cell-style input.

Status: useful evidence that the stable generated-table abstraction is the structured `RichTable`/cell pipeline, while table-level CSS/layout authoring remains incomplete.

## 10. Sample API inventory

### Sample 11 — combined table/style/column regression

Sample 11 currently uses:

```php
(new RichTable())->setStyle([
    'table:width' => '15cm',
    'table:align' => 'left',
    'style:rel-width' => '100%',
]);
```

plus mixed friendly cell style arrays and `setColumnWidths()`.

This is the strongest combined historical table regression sample, but its table-level style syntax must be classified carefully:

- it demonstrates the current element-owned table-definition ownership path;
- it contains raw/ODF-like QNames rather than the STYLE-API-02 friendly-option target;
- `table:width` is suspicious against the TABLE-LAYOUT-01A ODF evidence, which identifies `style:width` as the native table-width property;
- the sample continuing to render does not prove that every supplied table-level property is effective.

Conclusion: **preserve as compatibility evidence; do not treat as target API specification.**

Before TABLE-LAYOUT implementation, the actual contribution of each Sample-11 table-level key should be characterized independently if changing/removing that path is contemplated.

### Sample 12 — explicit cell/paragraph responsibility

Sample 12 constructs `Paragraph` objects with paragraph alignment and applies background/padding/border to cells.

Conclusion: strong evidence for the modern responsibility split.

### Sample 13 — broad cell/structure convenience surface

Sample 13 exercises:

- friendly cell style arrays;
- fluent cell alignment;
- colspan/rowspan aliases;
- multiple tables nested in `RichText`;
- `buildTableFromArray()`;
- custom role-based style sets;
- summary-keyword configuration.

It demonstrates that apparently complex table output survives through composition of structure + cell/paragraph/text styling without requiring a broad table-level style language.

### Sample 15 — mixed cell convenience arrays

Sample 15 repeatedly uses `background`, `text-align`, `border`, and similar friendly options. It confirms the supported author-facing cell mental model.

### Sample 19 — HTML conversion

Sample 19 reaches table generation indirectly through `HtmlImporter`; HTML cell presentation is projected into the same RichTable/RichTableCell/Paragraph architecture.

### Sample 20 — relative geometry

Sample 20 exercises `setColumnWidthRatios([2, 1, 1])` plus friendly cell styles. After SR-07H this is an important semantic geometry oracle rather than a legacy virtual-grid example.

## 11. Why the samples still work after the architecture rebuild

The architecture migration preserved user-facing behavior while changing ownership underneath it.

The important transition was approximately:

```text
OLD
application options
    -> element/global StyleMapper registries/direct DOM writers
    -> ODF

NEW
application options
    -> structured element local state
    -> semantic StyleRequirement
    -> document-local StyleContext
    -> materializer
    -> ODF
```

The samples did not need wholesale rewrites because the element-facing surfaces were deliberately kept or migrated behind the same calls. Where legacy mechanisms remained observable, they were either characterized and preserved or isolated as compatibility paths.

This is particularly clear for cells: their friendly arrays survived while global registry ownership was removed. For tables, `setTableStyleName()` retained reference semantics, `setStyle()` became element-owned definition state, explicit columns migrated to semantic requirements, and ratio semantics were corrected without changing the public `setColumnWidthRatios()` call.

The current hybrid appearance is therefore largely the expected result of staged migration.

## 12. Current API classification matrix

| Surface | Semantic owner | Mapper / producer | Requirement / ODF | Classification |
| --- | --- | --- | --- | --- |
| `setTableName()` | table structure | direct | `table:name` | authoritative structure |
| `setHeaderRowCount()` | table structure | direct | `table:table-header-rows` | authoritative structure |
| `setTableStyleName()` | table style reference | semantic requirement | `table` reference | authoritative |
| `setStyle([...])` on `RichTable` | element-owned table definition | **no table mapper** | common `table` / `style:table-properties` | ownership authoritative; authoring syntax provisional/raw-compatible |
| `setColumnWidths()` | table-column geometry | `RichTable` producer | `style:column-width` | authoritative semantics + compatibility side paths |
| `setColumnWidthRatios()` | table-column geometry | 65535-normalizing producer | `style:rel-column-width` | authoritative after SR-07H |
| `addRow(..., ['min-row-height'=>...])` | table-row geometry | narrow `RichTable` producer | `style:min-row-height` | authoritative narrow producer |
| unsupported row style keys | none | ignored | none | compatibility behavior |
| cell `setStyle()` | cell + delegated paragraph/text | splitter + mappers | table-cell + paragraph + text requirements | primary friendly API |
| cell background/border/padding setters | table-cell | local normalized state | table-cell requirement | established convenience |
| cell `alignLeft/Center/Right()` | paragraph inside cell | paragraph style reference | paragraph alignment | established but naming-specific convenience |
| cell `setWidth()` / `__column-width` | compatibility side channel | none authoritative | no native cell-width property | legacy/compatibility |
| colspan/rowspan | cell structure | direct | span attributes | authoritative structure |
| `buildTableFromArray()` style sets | cell/content presentation roles | normal cell path | nested requirements | convenience API; not table-family style definition |
| HtmlImporter tables | structured table/cell conversion | cell/paragraph mapping | same semantic pipeline | supported producer path |
| `StyleWriter::writeColumnStyles()` | compatibility fallback | direct writer | automatic table-column | bounded compatibility |

## 13. Deferred questions that TABLE-LAYOUT-01 now legitimately owns

The inventory does not decide the final API, but it narrows the design problem substantially.

TABLE-LAYOUT-01 now needs to decide only the user-facing semantics that were deliberately deferred, while using the existing ownership architecture:

```text
1. overall table width
   - absolute
   - relative

2. whole-table placement/alignment insofar as required by professional table geometry

3. exact/fixed row height
   - distinct from existing minimum height

4. vertical cell alignment
   - distinct from paragraph horizontal alignment

5. whether/how friendly table-level option arrays complement dedicated geometry methods
```

The following are **not** open architecture questions for this milestone:

```text
style ownership
StyleContext ownership
semantic table family identities
column ratio meaning
65535 normalization
cell/paragraph/text responsibility split
span structure
named table style reference meaning
```

## 14. Implications for future API design — evidence, not decision

The recovered architecture strongly constrains later design:

1. Normal application syntax should use friendly engine-level terms, not require ODF QNames such as `style:*` or `table:*`.
2. Raw/native prefixed input may remain an advanced compatibility escape hatch where already supported, but must not define the recommended API vocabulary.
3. A generic ambiguous alignment method is risky because existing cell alignment methods already mean paragraph/content alignment while whole-table alignment is a different semantic owner.
4. Table-level style arrays, if retained/expanded as a friendly API, need an explicit mapping boundary comparable to the existing cell path; the current `RichTable::setStyle()` does not provide that mapping today.
5. Dedicated table geometry APIs may coexist with a friendly table-style/options surface only if they share one semantic state/authority rather than creating competing representations.
6. The existing `buildTableFromArray()` "styles" are role-based cell presets and must not be conflated with a table-family style API merely because of naming similarity.
7. Exact row height should extend the already-owned table-row semantic layer rather than invent a new row subsystem.
8. Vertical cell alignment belongs to the table-cell property family and can reuse current cell ownership/materialization; it is not paragraph alignment.
9. New production logic should flow through semantic requirements and the existing materializer, not expand the remaining direct StyleWriter compatibility path.

## 15. Compatibility-sensitive oddities to freeze or inspect before touching them

The following current details deserve explicit characterization before any implementation slice changes them:

- Sample 11's `table:width` / `table:align` / `style:rel-width` combination and the actual rendered/materialized contribution of each property;
- `RichTable::setStyle()` replacement/clearing behavior;
- interaction of `setStyle()` with `setTableStyleName()` and call order;
- `setColumnWidths()` returning `void`;
- `setColumnWidthRatios()` returning `void`;
- first-row `RichTableCell::setWidth()` / `__column-width` side effect;
- guarded `StyleWriter::writeColumnStyles()` fallback;
- unsupported row-style keys being silently ignored;
- public `registerStylesAndRefresh()` historical naming/visibility;
- cell alignment helpers relying on named paragraph styles such as `CenterPara`;
- custom/predefined `buildTableFromArray()` role-style behavior.

None of these should be opportunistically cleaned while TABLE-LAYOUT-01 adds missing semantics unless a separate explicit contract says otherwise.

## 16. Inventory conclusion

The current table architecture is not a blank slate.

The large prior refactor successfully established the hard part:

```text
structured ownership
    + document-local semantic style ownership
    + family-correct StyleRequirements
    + generic materialization
    + friendly cell/paragraph/text style splitting
```

What remains unfinished is mainly the **public semantic projection of table geometry and table-level options**.

The most important recovered distinction is:

```text
STYLE/LAYOUT AUTHORING LANGUAGE
    !=
ODF PROPERTY NAMES
    !=
SEMANTIC OWNERSHIP / MATERIALIZATION
```

SR-07 and STYLE-API-02 intentionally solved the third layer first and deferred parts of the first layer. TABLE-LAYOUT-01 is now positioned to finish that work without rebuilding the architecture underneath it.

Before a TABLE-LAYOUT-01C Change Contract is written, the recommended next evidence step is a very small focused characterization of the remaining compatibility-sensitive table-level authoring path, especially Sample 11 / `RichTable::setStyle()` behavior. That characterization should answer what the current raw keys actually do, not decide the target friendly API in advance.

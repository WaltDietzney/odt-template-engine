# TABLE-LAYOUT-01A — Evidence Findings

Status: RESEARCH FINDINGS / PRE-CONTRACT

Parent: `TABLE_LAYOUT_01A_NATIVE_WRITER_ODF_GEOMETRY_EVIDENCE.md`

Base: `develop` after PAGE-FLOW-01 and completed SR-07

## 1. Purpose

This document records the first TABLE-LAYOUT-01A findings from the combined LibreOffice Writer oracle `TL01A.odt`, the existing repository oracle `TABLE-02`, the current engine implementation, and already-established SR-07 relative-column evidence.

It is deliberately cumulative: TABLE-LAYOUT-01 must reuse established repository knowledge rather than rediscovering or replacing it. In particular, the Writer-compatible 65535 relative-column normalization established during SR-07H remains architecture evidence and current implementation baseline.

This is not a Change Contract and authorizes no production implementation.

## 2. Evidence provenance

### 2.1 Existing SR-07 baseline

SR-07 established semantic ownership for the `table`, `table-column`, `table-row`, and `table-cell` style families.

SR-07H additionally established, through Writer behavior and LibreOffice source investigation, that Writer uses a 16-bit relative-width space for table-column ratios:

```text
USHRT_MAX = 65535 = 2^16 - 1
```

For positive integer ratio inputs, the engine therefore normalizes the logical weights into a total of exactly 65535 and assigns rounding remainder to the final column.

Example:

```text
[2, 1, 1]
    ->
32766* / 16383* / 16386*
```

This 65535 normalization is a **LibreOffice interoperability rule**, not an ODF normative requirement. ODF defines relative column weights through `style:rel-column-width`; it does not prescribe this Writer-internal normalization space.

The current `RichTable::normalizedRelativeColumnWidths()` implementation still materializes this established rule. TABLE-LAYOUT-01 must preserve it unless new evidence demonstrates a bounded incompatibility.

### 2.2 Existing repository Writer oracle

`tests/fixtures/libreoffice-reference/extracted/TABLE-02/content.xml` already demonstrates a Writer-authored full-width single-column table with:

```xml
<style:table-properties style:width="17cm" table:align="margins"/>
<style:table-column-properties
    style:column-width="17cm"
    style:rel-column-width="65535*"/>
<style:table-row-properties style:min-row-height="0.318cm"/>
```

This remains useful prior evidence and is not superseded by the new combined oracle.

### 2.3 New combined Writer oracle

The manually authored `TL01A.odt` contains the planned TABLE-LAYOUT-01A cases in one Writer document. The cases remain distinguishable through their table/style identities and geometry.

The LibreOffice version was not recorded with the first oracle and remains an evidence metadata gap. No claim in this document depends on exact Writer version-specific behavior beyond the observed serialization.

## 3. Findings: table width and placement

### 3.1 Default/full-width Writer table

The first table is serialized with an absolute table width and margin alignment:

```xml
<style:table-properties style:width="17cm" table:align="margins"/>
```

Its equal columns carry both absolute and relative geometry. One shared/repeated column style is approximately:

```xml
<style:table-column-properties
    style:column-width="8.5cm"
    style:rel-column-width="32767*"/>
```

Combined with the existing TABLE-02 oracle, this strengthens the evidence that Writer commonly materializes full-width tables as explicit physical width plus `table:align="margins"`, rather than representing full width merely by omitting geometry.

This is Writer serialization evidence, not a rule that the engine must always generate both forms.

### 3.2 Absolute narrow table

The requested approximately 10 cm left-aligned table is serialized as:

```xml
<style:table-properties style:width="9.999cm" table:align="left"/>
```

Its two columns are approximately `4.999cm` and `5.001cm`.

The requested approximately 10 cm centered table is serialized as:

```xml
<style:table-properties style:width="9.94cm" table:align="center"/>
```

Its columns are approximately `4.935cm` and `5.004cm`.

Writer therefore preserves table width and placement as distinct semantics, while also normalizing physical values. The engine must not require byte- or decimal-identical round trips for Writer-authored geometry where semantic geometry is preserved.

### 3.3 Relative table width

The requested 60% table is serialized with **both** a Writer-calculated absolute width and the relative-width request:

```xml
<style:table-properties
    style:width="10.199cm"
    style:rel-width="60%"
    table:align="left"/>
```

Its equal columns likewise carry both absolute and relative values:

```xml
<style:table-column-properties
    style:column-width="5.099cm"
    style:rel-column-width="2891*"/>
```

and

```xml
<style:table-column-properties
    style:column-width="5.099cm"
    style:rel-column-width="2892*"/>
```

Architecture finding:

```text
relative table-width intent
    !=
absence of absolute Writer geometry
```

Writer may retain the semantic percentage while simultaneously materializing a current physical width. TABLE-LAYOUT-01 must therefore distinguish semantic authoring intent from Writer-normalized supporting geometry.

No decision is made yet about whether engine-generated relative table width should emit only `style:rel-width` or both properties.

## 4. Findings: column geometry

### 4.1 Absolute columns inside an absolute table

The 12 cm table with requested 4 cm / 8 cm columns is serialized approximately as:

```text
table width: 12cm
column A:     4.001cm
column B:     7.999cm
```

The physical sum remains 12 cm while Writer normalizes individual values slightly.

This confirms the existing architecture distinction:

```text
TABLE WIDTH
    !=
COLUMN WIDTH
```

while also showing that the two geometries are physically coordinated by Writer.

### 4.2 2:1:1 visible proportions do not by themselves prove relative serialization

The three-column 2:1:1 case is serialized with an approximately 17 cm table and column geometry around:

```text
8.999cm / 4.001cm / 4.000cm
```

Writer did not preserve `style:rel-column-width` on this particular case; the resulting proportions are represented through absolute widths.

This does **not** contradict SR-07H. The SR-07H engine path deliberately authors `style:rel-column-width`, while this manual Writer operation produced equivalent visible proportions through absolute geometry.

The correct conclusion is narrower:

> Visible column proportions are not sufficient evidence that Writer will serialize relative-column semantics. The authoring operation and resulting native property must be distinguished.

The established engine ratio API therefore remains based on `style:rel-column-width` and the 65535 Writer interoperability rule unless a later focused round-trip test shows that Writer rejects or rewrites that representation incompatibly.

## 5. Findings: row height

The Writer row-height dialog exposes a numeric `Height` value plus a `Fit to size` / `dynamically adjust` choice rather than separate fields named exact height and minimum height.

The combined oracle resolves the native mapping:

```text
2 cm + dynamic adjustment disabled
    -> style:row-height="2cm"

2 cm + dynamic adjustment enabled
    -> style:min-row-height="2cm"
```

This is an important UI-to-ODF mapping and should be retained as TABLE-LAYOUT evidence.

Architecture consequence:

```text
EXACT/FIXED ROW HEIGHT
    -> table-row family
    -> style:row-height

MINIMUM/GROWABLE ROW HEIGHT
    -> table-row family
    -> style:min-row-height
```

The current engine supports only the second concept explicitly in `RichTable::addRow(..., ['min-row-height' => ...])`. Exact row height is therefore a genuine TABLE-LAYOUT capability gap rather than a synonym for existing minimum-height support.

The first oracle does not establish a need to generate `style:use-optimal-row-height`; no such requirement should be invented from symmetry.

## 6. Findings: vertical cell alignment

The tall three-cell row produces separate `table-cell` styles:

```text
Writer top/default -> style:vertical-align=""
Writer middle      -> style:vertical-align="middle"
Writer bottom      -> style:vertical-align="bottom"
```

ODF normatively permits `top`, `middle`, `bottom`, and `automatic`, but this Writer oracle uses an empty serialized value for the top/default case rather than explicit `top`.

Therefore two facts must remain separate:

1. native ownership is unambiguous: vertical cell alignment belongs to `style:table-cell-properties`;
2. Writer's default/top serialization requires care and must not be generalized from the ODF value list alone.

The current `StyleMapper::mapTableCellStyleOptions()` does not expose a characterized convenience mapping for vertical alignment. Raw native `style:vertical-align` is already structurally possible because native `style:` keys pass through the mapper.

TABLE-LAYOUT-01 should preserve that escape hatch while deciding whether a convenience option is justified.

## 7. Current engine comparison

Current `develop` already provides:

```text
RichTable::setColumnWidths()
    -> style:column-width

RichTable::setColumnWidthRatios()
    -> style:rel-column-width
    -> Writer-compatible 65535 normalization for positive integer ratios

RichTable::addRow(..., ['min-row-height' => ...])
    -> style:min-row-height

RichTable::setStyle([...])
    -> semantic table-family style requirement

RichTableCell::setStyle([...])
    -> semantic table-cell style requirement plus paragraph/text splitting
```

The evidence identifies the following actual milestone gaps without yet choosing APIs:

```text
explicit table width authoring semantics
relative table width authoring semantics
exact/fixed row height
characterized vertical cell alignment convenience
```

Absolute column width, relative column ratio, and minimum row height are existing capabilities to preserve and integrate, not features to redesign from scratch.

## 8. Compatibility questions still requiring characterization tests

Before a TABLE-LAYOUT Change Contract, repository-native tests should characterize only the paths that a later implementation could alter:

- `RichTable::setStyle()` with raw/native `style:width`, `style:rel-width`, and `table:align`;
- exact `row-height` passed through current row APIs, including whether it is currently ignored;
- `RichTableCell::setStyle(['style:vertical-align' => ...])` and convenience-like non-native input if any;
- coexistence of table-level width with `setColumnWidths()`;
- coexistence of table-level relative width with `setColumnWidthRatios()`;
- save/reopen stability of generated table, column, row, and cell style references;
- repeated render/save behavior where the same structured table is materialized more than once.

These are characterization targets, not authorization to change behavior.

## 9. Provisional architecture synthesis

The evidence now supports the following semantic model strongly enough for Change Contract preparation:

```text
Table
├── table width
│   ├── absolute width
│   └── relative width
├── placement/alignment
└── owns logical columns
    ├── absolute column width
    └── relative column weight

Row
├── exact/fixed height
└── minimum/growable height

Cell
└── vertical content alignment

Writer
├── resolves available physical geometry
├── may materialize both semantic and calculated width hints
├── normalizes physical decimal widths
└── computes final rendered layout
```

The governing TABLE-LAYOUT principle should remain consistent with PAGE-FLOW:

> The engine describes native ODF geometry semantics; LibreOffice/Writer computes and may normalize the final physical layout.

This principle is provisional until the characterization slice confirms current compatibility behavior.

## 10. Next gate

The next step is **not production implementation**.

TABLE-LAYOUT-01A should now add focused characterization tests for the current engine paths listed above. After those tests establish compatibility boundaries, the evidence is sufficient to prepare TABLE-LAYOUT-01B architecture synthesis / Change Contract.

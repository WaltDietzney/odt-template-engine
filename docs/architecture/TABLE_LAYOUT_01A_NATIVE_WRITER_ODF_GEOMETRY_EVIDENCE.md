# TABLE-LAYOUT-01A — Native Writer / ODF Geometry Evidence

Status: RESEARCH / EVIDENCE COLLECTION

Base: `develop` after PAGE-FLOW-01

Parent milestone: `TABLE-LAYOUT-01`

## 1. Purpose

TABLE-LAYOUT-01A establishes the native ODF and LibreOffice Writer evidence required before any table-layout Change Contract or public API decision.

The scope is deliberately limited to the five geometry concerns already defined by the 1.0 roadmap:

- explicit table width;
- absolute column widths;
- relative column widths;
- exact and minimum row height;
- vertical cell alignment.

SR-07 already established semantic style ownership for the `table`, `table-column`, `table-row`, and `table-cell` families. TABLE-LAYOUT-01A must not reopen that ownership architecture.

This document records normative ODF facts, existing Writer evidence, and the focused Writer fixture matrix still required. It is not a Change Contract and does not approve a new API.

## 2. Governing questions

The evidence phase must answer:

1. How does Writer represent an automatically sized/full-width table versus an explicitly sized table?
2. How do `style:width`, `style:rel-width`, `table:align`, and table margins interact in Writer-authored ODT?
3. Does Writer preserve absolute table width independently from column widths, and what invariants exist between them?
4. How are absolute and relative column widths serialized in tables with and without explicit table width?
5. How does Writer distinguish fixed row height from minimum row height?
6. Which values and property locations represent top/middle/bottom vertical cell alignment?
7. Which geometry values survive save/reopen unchanged and which are normalized by Writer?
8. Which current engine behaviors already match the Writer oracle and which require bounded correction or new capability?

## 3. Normative ODF evidence

### 3.1 Table width belongs to the table style family

ODF defines table geometry on `style:table-properties`.

Relevant native properties are:

```text
style:width
style:rel-width
fo:margin-left
fo:margin-right
fo:margin-top
fo:margin-bottom
table:align
```

`style:width` expresses an absolute table width. `style:rel-width` expresses table width as a percentage of the containing area.

`table:align` has the values:

```text
left
center
right
margins
```

The `margins` value means that the table fills the available space between the left and right margins.

Architecture consequence for investigation:

```text
TABLE WIDTH
    is table-family style semantics
    and is not the same concept as column width
```

No public API follows from this observation yet.

### 3.2 Column width belongs to the table-column style family

ODF distinguishes:

```text
style:column-width       absolute length
style:rel-column-width   relative weight
```

Both belong to `style:table-column-properties`.

For relative columns, the specification defines each logical column width from its relative weight, the sum of all relative weights, and the absolute width available to those columns.

This supports the SR-07 distinction already adopted by the engine:

```text
TABLE WIDTH
    !=
ABSOLUTE COLUMN WIDTH
    !=
RELATIVE COLUMN WIDTH
    !=
COLUMN STRUCTURE
```

TABLE-LAYOUT-01A must determine how Writer establishes the absolute width available to relative columns in actual text documents and how it serializes any supporting absolute hints.

### 3.3 Border and padding participate in physical width

ODF states that row height and column width include the space required for borders or padding. Consequently, the sum of column widths is the total physical table width even though each cell's content box is smaller.

This is important for professional layout: the engine must not later interpret column width as merely the text/content-box width.

### 3.4 Row height has three distinct native concepts

ODF provides:

```text
style:row-height
style:min-row-height
style:use-optimal-row-height
```

`style:row-height` represents fixed row height.

`style:min-row-height` represents a minimum height while allowing the row to grow when content requires more space.

`style:use-optimal-row-height` controls automatic recalculation when row content changes.

SR-07 already activated and visually verified `style:min-row-height`. TABLE-LAYOUT-01A must now characterize the Writer distinction between exact/fixed and minimum/automatic row behavior rather than assuming symmetry from the attribute names.

### 3.5 Vertical cell alignment is table-cell style semantics

ODF defines `style:vertical-align` on `style:table-cell-properties` with the values:

```text
top
middle
bottom
automatic
```

This property concerns vertical placement of content within a cell. It is distinct from paragraph horizontal alignment and from the unrelated paragraph-level use of `style:vertical-align`.

Architecture consequence:

```text
CELL VERTICAL ALIGNMENT
    -> table-cell family

PARAGRAPH HORIZONTAL ALIGNMENT
    -> paragraph family
```

The current `RichTableCell` mixed convenience-style splitting must preserve that semantic separation.

## 4. Existing LibreOffice Writer oracle already in the repository

The existing `TABLE-02` LibreOffice reference fixture provides useful baseline evidence even though it was not authored specifically for TABLE-LAYOUT-01.

Writer serialized its single-column table approximately as:

```xml
<style:style style:name="Tabelle1" style:family="table">
    <style:table-properties
        style:width="17cm"
        table:align="margins"/>
</style:style>

<style:style style:name="Tabelle1.A" style:family="table-column">
    <style:table-column-properties
        style:column-width="17cm"
        style:rel-column-width="65535*"/>
</style:style>

<style:style style:name="Tabelle1.1" style:family="table-row">
    <style:table-row-properties
        style:min-row-height="0.318cm"/>
</style:style>
```

This proves at least one Writer-authored combination in which:

- overall width is expressed on the table style;
- the table is aligned using `table:align="margins"`;
- the single logical column carries both an absolute and a normalized relative width;
- the default Writer-created row carries a minimum row height.

It does **not** yet prove that every Writer table uses both absolute and relative column hints, that `margins` always implies a full available width, or how Writer behaves for explicitly narrow/centered/percentage tables. Those questions require focused fixtures below.

## 5. Focused Writer fixture matrix

Use simple documents with no unrelated formatting. A 2-column or 3-column table with short labels is sufficient unless the case explicitly requires tall content.

The fixture names are proposed evidence identifiers, not permanent public API names.

| Fixture | Writer authoring intent | Primary question |
| --- | --- | --- |
| `TL01A-01-default-table.odt` | Insert a plain 2x2 Writer table and change nothing | What does Writer consider the default table width/alignment and which absolute/relative column hints are emitted? |
| `TL01A-02-absolute-width-left.odt` | 2-column table, overall width `10cm`, left aligned | Which combination of `style:width`, `style:rel-width`, margins, and `table:align` represents a narrow absolute table? |
| `TL01A-03-absolute-width-center.odt` | Same `10cm` table, center aligned | Does centering alter width or only table alignment/margins? |
| `TL01A-04-relative-width.odt` | 2-column table, relative width `60%` if Writer exposes that mode | Does Writer emit `style:rel-width`, retain an absolute `style:width` fallback, or normalize another way? |
| `TL01A-05-absolute-columns.odt` | Overall table width `12cm`; columns `4cm` and `8cm` | Are absolute column widths preserved exactly, and what relative hints accompany them? |
| `TL01A-06-relative-columns.odt` | 3 columns with visible `2:1:1` proportions; no artificial spans | How does current Writer serialize relative column geometry and how does it relate to overall table width? |
| `TL01A-07-row-heights.odt` | Three rows: normal; minimum `2cm`; fixed/exact `2cm` | Which Writer UI states map to `min-row-height`, `row-height`, and `use-optimal-row-height`? |
| `TL01A-08-cell-vertical-align.odt` | One visibly tall row with three cells aligned top / middle / bottom | Does Writer serialize exactly `style:vertical-align="top|middle|bottom"` and does paragraph alignment remain independent? |

### 5.1 Authoring discipline

For all fixtures:

- use the same page style and margins;
- use plain text only;
- avoid merged cells, nested tables, frames, manual line breaks, and custom paragraph styles unless required by the case;
- save directly as ODT;
- reopen once in Writer and save again before final XML extraction so the fixture reflects Writer's stable round-trip representation;
- record the LibreOffice version used for the experiment.

For the row-height and vertical-alignment fixtures, make the geometry visually obvious. A `2cm` or similarly large row is preferable to a subtle difference.

## 6. Evidence to extract from every fixture

For each document inspect at least:

```text
content.xml
    office:automatic-styles
        style:style family="table"
        style:style family="table-column"
        style:style family="table-row"
        style:style family="table-cell"

    table:table
        table:style-name
        table:table-column references
        table:table-row references
        table:table-cell references
```

Record:

- style names and families;
- property-group names;
- `style:width` / `style:rel-width`;
- `table:align`;
- left/right margins when present;
- `style:column-width` / `style:rel-column-width`;
- `style:row-height` / `style:min-row-height` / `style:use-optimal-row-height`;
- `style:vertical-align`;
- whether Writer changes any value after reopen/save.

The XML should be compared semantically, not by style-name identity or document byte equality.

## 7. Hypotheses to test — not decisions

The following are explicit hypotheses only:

1. Writer's ordinary full-width table will likely combine an absolute computed `style:width` with `table:align="margins"` rather than representing "automatic width" as an absence of geometry.
2. Narrow absolute tables will likely retain `style:width` while alignment/margins determine placement independently.
3. Writer may emit both `style:column-width` and `style:rel-column-width` for the same column even when only one authoring concept is directly manipulated.
4. Relative table width may be accompanied by an absolute Writer-calculated width for interoperability or layout stability.
5. Fixed and minimum row-height UI modes will map to different native attributes and may also affect `style:use-optimal-row-height`.
6. Cell vertical alignment should require only a table-cell property and should not modify the paragraph's horizontal alignment semantics.

Any hypothesis contradicted by Writer evidence must be withdrawn rather than reconciled by assumption.

## 8. Current engine comparison points

After the Writer fixtures are understood, compare them against current `develop` behavior without changing production code.

Existing engine surfaces relevant to the comparison are:

```text
RichTable::setStyle()
RichTable::setColumnWidths()
RichTable::setColumnWidthRatios()
RichTable::addRow(..., ['min-row-height' => ...])
RichTableCell::setStyle()
StyleMapper::mapTableCellStyleOptions()
StyleRequirement / StyleContext materialization
```

Known baseline:

- absolute column widths already use semantic `table-column` requirements;
- relative column widths already use semantic `table-column` requirements and the Writer-compatible 65535 normalization established by SR-07H;
- minimum row height already uses semantic `table-row` requirements;
- explicit overall table-width authoring has not yet been established as a dedicated public semantic capability;
- vertical cell alignment is not yet an explicitly characterized convenience option in the current table-cell mapping path.

Do not infer missing API design from these gaps during 01A.

## 9. Characterization required before a Change Contract

Once the Writer matrix is available, add repository-native characterization for any current behavior that could be changed by TABLE-LAYOUT-01, especially:

- table-level native properties passed through `RichTable::setStyle()`;
- current handling of `style:width`, `style:rel-width`, and `table:align` if supplied as raw/native properties;
- interaction between explicit table width and existing column width APIs;
- exact-row-height input if any current compatibility path already accepts it;
- vertical-align input passed through `RichTableCell::setStyle()`;
- repeated `setElement()` / `save()` and save/reopen for newly characterized geometry;
- authored style definitions with the same semantic identity.

Unexpected behavior must first be documented as current behavior. Refactoring and semantic correction should remain separate.

## 10. Acceptance gate for TABLE-LAYOUT-01A

TABLE-LAYOUT-01A is complete when:

1. the eight Writer cases above, or an evidence-equivalent smaller matrix, have been authored and inspected;
2. ODF normative semantics and Writer serialization behavior are clearly separated in the findings;
3. table width, column width, row height, and cell vertical alignment have unambiguous native ownership/property locations;
4. Writer round-trip normalization relevant to the engine has been recorded;
5. current engine behavior has been characterized where a later change would affect compatibility;
6. remaining ambiguities are stated explicitly rather than silently resolved;
7. enough evidence exists to write `TABLE-LAYOUT-01B` as architecture synthesis / Change Contract preparation.

No production implementation is authorized by this document.

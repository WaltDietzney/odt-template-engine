# TABLE-LAYOUT-01 — Research and Design

Status: DRAFT / RESEARCH START

Base: `develop`

## Purpose

TABLE-LAYOUT-01 is the next mandatory 1.0 milestone after PAGE-FLOW-01. Its scope is professional table geometry:

- explicit table width;
- absolute column widths;
- relative column widths;
- row/minimum height;
- vertical cell alignment.

This document starts the evidence phase. It is not a Change Contract and does not approve public API changes.

## Governing architecture boundary

SR-07 already established semantic style ownership for the `table`, `table-column`, `table-row`, and `table-cell` families. TABLE-LAYOUT-01 must therefore investigate ODF/Writer geometry semantics and rendering reliability without reopening style ownership.

The milestone should preserve the broader project rule:

> Semantics before implementation.

## Existing baseline to preserve

Current `develop` already contains important table behavior that TABLE-LAYOUT-01 must treat as baseline unless evidence justifies a bounded correction:

- explicit column widths are represented as automatic/content-local `table-column` style requirements using `style:column-width`;
- relative column ratios are represented as automatic/content-local `table-column` requirements using `style:rel-column-width`;
- positive integer ratio weights are normalized into LibreOffice Writer's 65535 relative-width space;
- ratio columns are real logical columns and no longer use the historical virtual-column grid;
- `min-row-height` is represented as an automatic/content-local `table-row` style requirement using `style:min-row-height`;
- genuine colspan/rowspan remain structural cell semantics;
- `RichTable` owns table/column/row requirements and `RichTableCell` owns cell requirements;
- Writer remains responsible for physical page layout and rendering.

## Initial research questions

The first evidence slice should answer, from ODF structure and LibreOffice-authored documents:

1. What native ODF properties control overall table width, and how do absolute and relative/percentage forms interact with table alignment/margins?
2. How does Writer serialize an explicitly sized table versus an automatically sized table?
3. What is the semantic relationship between overall table width and absolute `style:column-width` values?
4. What is the semantic relationship between overall table width and `style:rel-column-width` values?
5. Which row properties represent exact height versus minimum height, and what Writer UI choices map to them?
6. Which native cell property controls vertical alignment, which values are Writer-compatible, and how does it interact with paragraph alignment inside the cell?
7. Which current public methods/options already express these concepts, and which concepts are missing entirely?
8. Which legacy compatibility paths or incidental side effects must be characterized before any behavior change?
9. What happens across repeated `setElement()`, `render()`, `save()`, save/reopen, and authored-style collisions?
10. Which table behaviors require manual LibreOffice regression because XML correctness alone is insufficient?

## Explicit non-goals for the research start

Do not yet:

- invent a new public table-width API;
- redesign `RichTable`, `RichTableCell`, or `StyleContext` ownership;
- broaden ratio input semantics;
- redesign rowspan/colspan;
- introduce pagination logic for tables;
- fold FRAME-LAYOUT or PAGE-STYLE authoring into this milestone;
- clean up unrelated legacy code while investigating table geometry.

## Planned evidence artifacts

The evidence phase should produce focused LibreOffice-authored fixtures or extracted XML comparisons for:

- automatic table width;
- explicit absolute table width;
- relative/percentage table width if Writer exposes a stable native representation;
- absolute columns inside an explicitly sized table;
- relative columns inside automatic and explicitly sized tables;
- exact row height;
- minimum row height;
- top/middle/bottom cell vertical alignment.

Repository-native characterization tests should accompany any surprising current behavior before a Change Contract is written.

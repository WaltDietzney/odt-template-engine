# SECTION-AUTHORING-01 — Writer reference and implementation handoff

Status: native reference inspected, 2026-11-17. Architecture/API approval and implementation pending. Baseline: Forgejo develop `d05ab87b86ceb88210b24e9ab0c541c10446c3e7`. Forgejo is the leading development repository.

## Evidence

Owner-authored `tests/fixtures/libreoffice-reference/odt/SECTION-AUTHORING-01-reference.odt` (ODF 1.3), inspected from the original upload. Repository placement by owner completed; verified SHA-256 `9d3414253440f49d1c395c172bf1067bf896224f9a7257a9e66acc03d15d4862` before accepting the fixture. Confirm embedded image redistribution rights and remove personal metadata if needed before publication; any sanitized derivative must have its own checksum. No fixture regeneration required for architecture work.

All 15 named Sections and 12 automatic Section styles occur in content.xml. The following records observed Writer serialization, not a comprehensive ODF conformance claim.

| Section | Style | Observed evidence |
| --- | --- | --- |
| SimpleSection | Sect1 | Three paragraphs; one column |
| TwoColumnsEqual | Sect2 | Two columns, 32767*/32768*, zero gap |
| TwoColumnsUnequal | Sect3 | 3826*/5811*, adjacent end/start indents 0.249cm each |
| ThreeColumns | Sect4 | Three 21845* columns; solid separator 0.035cm, #013552, 100% height |
| ColumnBreak | Sect5 | Two columns; second paragraph P2 has fo:break-before=column |
| IndentedSection | Sect6 | Left 0.499cm, right 2.2cm |
| ColoredSection | Sect7 | Background #7a2f02 |
| ImageSection | Sect8 | Transparent background; embedded PNG via style:background-image and xlink:href/type/actuate |
| NestedOuter | Sect9 | Background #d9d9d9; contains NestedInner |
| NestedInner | Sect10 | Background #f2f2f2; left 3cm, right 1.499cm |
| CombinedSection | Sect11 | Three equal columns; left 1.499cm, right 1cm; solid blue #0000b3 separator |
| ProtectedSection | Sect1 | text:protected=true |
| HiddenSection | Sect1 | text:display=none |
| ConditionalSection | Sect1 | text:display=condition; text:condition uses ooow expression |
| NotesSection | Sect12 | text:notes-configuration for footnote (start 1, Roman I) and endnote |

## Native ownership

- `text:section` owns `text:name`, `text:style-name`, child blocks and nested Sections; protection/display/condition are element attributes.
- `style:style style:family="section"` owns `style:section-properties`. Observed attributes: `style:editable`, `text:dont-balance-text-columns`, `fo:margin-left/right`, `fo:background-color`.
- `style:columns` owns `fo:column-count` and optional `fo:column-gap`; `style:column` owns `style:rel-width`, `fo:start-indent/end-indent`. Relative widths are weights, not guaranteed physical content widths. Explicit adjacent indents form the observed 0.498cm gap.
- `style:column-sep` owns separator properties. Fixture uses style:width/color/height/style; non-default vertical alignment not evidenced.
- `style:background-image` references a package resource; empty background-image elements also occur for solid colors. Do not treat a background as an inline ImageElement.
- `text:notes-configuration` is nested under section-properties; actual notes are not present.
- Column break belongs to paragraph style (`fo:break-before="column"`), not Section identity. Writer owns physical flow; columns are not independent content nodes.

## Reuse and boundaries

SECTION-01/02/03, PAGE-FLOW-01, TEMPLATE-AUTHORING-01D, STYLE-CONTEXT-01, STYLE-API-02 and PAGE-STYLE-AUTHORING-01 are established baseline. Reuse inspection, identity, clone/instantiate/collections, declarative controls, existing structured materialization, document-local style and package resource services. No repeat of completed nesting, scope, rollback or pagination research. Existing S01b/C04 and SECTION-03 fixtures remain regression evidence. Public SectionTarget targets existing content.xml structures; authoring must not silently broaden its contract.

H01 / ODT-CONSTRUCTION-01 B1–B3/B5: close only missing authoring capabilities: direct body insertion, new native named Sections and nested owned blocks, Section style definition/reference, column configuration, existing paragraph break integration, generated template rediscovery. Existing replaceContent block whitelist does not document top-level text:section; review integration rather than assume support. Inspect OdtElement, StructuredElementMaterializer, DocumentStyles, StyleRequirementMaterializer, StyleMapper/Writer and existing package image materializers when drafting the change contract. No new public names approved. Reusable column layout across Section/page styles is a design candidate, not a commitment.

## Scope and acceptance

Proposed first scope: identity, nested content, style, columns/balancing/width/gap/separator, indents and color; background image, protection/display, native conditions, external links/DDE and notes require explicit scope decisions. Preserve authored unsupported properties. Native Writer conditions are distinct from engine declarative controls.

Fixture limitations: no explicit true dont-balance-text-columns, no CombinedSection background color, no actual foot/endnotes, mostly empty paragraphs; not a visual text-flow acceptance fixture. Password protection, external links, separator vertical alignment and image placement variants not demonstrated. Do not repeat broad research; add focused filled-content regression cases only as needed for implementation. Acceptance: generate, save, Writer open/save, reload, inspect and populate 0/1/n including nested collections; no empty headings when owned by removed prototype, no data loss, no repair warnings. Existing automated suite and visual gate remain required.

See [construction plan](ODT_CONSTRUCTION_01_PLAN.md), [H00 crosswalk](H00_CV_BASELINE_INTEGRATION.md), [future development](../FUTURE_DEVELOPMENT.md) and [Section API](../api-reference/writer-native/sections.md).

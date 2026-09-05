# SR-07 — Semantic Table Style Requirements Change Contract

Status: PROPOSED — REVIEW REQUIRED

Base: `develop` after SR-07A merge `b5f46af9ca1d9e3c4d14b47dcb08c227de0e1f1d`

Evidence base: `docs/architecture/SR-07A_TABLE_SEMANTICS_AUDIT.md`

This contract defines the semantic migration boundary for table-related ODF styles. It authorizes no implementation until reviewed and accepted.

## 1. Objective

SR-07 moves active table-style ownership from mixed static/direct-DOM compatibility paths into the existing document-local semantic `StyleRequirement` / `StyleContext` pipeline.

The active semantic families in this slice are:

- `table`
- `table-column`
- `table-cell`

Family `table-row` is not an active SR-07 semantic migration target because SR-07A found no active producer. The existing row-style argument remains a dormant compatibility surface.

The goal is ownership migration, not table-layout redesign.

## 2. Semantics before implementation

SR-07 distinguishes four concerns that must remain separate:

```text
TABLE STRUCTURE
    table / column / row / cell nodes
    spans
    repeated columns
    names and structural identity

SEMANTIC STYLE REQUIREMENTS
    table
    table-column
    table-cell

NESTED CONTENT REQUIREMENTS
    paragraph
    text
    resources
    nested structured elements

COMPATIBILITY PATHS
    static StyleMapper registries
    HasStyles definitions
    toStyleDomNode()
    StyleWriter legacy finalization/direct writers
```

A value does not become a style requirement merely because it currently passes through a style-shaped convenience API.

## 3. Family contracts

### 3.1 `table`

A table style definition is a semantic style definition with:

```text
family: table
property group: style:table-properties
```

A `table:style-name` on the structural `table:table` node is a semantic style reference.

Existing authored common table definitions remain authoritative when the same semantic identity already exists in the target document.

SR-07 does not redesign the public `setTableStyleName()` API or invent a new table-layout API.

### 3.2 `table-cell`

A styled `RichTableCell` owns a semantic style definition with:

```text
family: table-cell
property group: style:table-cell-properties
```

The approved semantic property projection is the existing mapped cell-style responsibility, including properties such as background, border, and padding.

Paragraph properties remain paragraph requirements. Text properties remain text requirements. Structural colspan/rowspan attributes remain structural.

`RichTableCell` semantic requirements must be discoverable through the existing `ownedElements()` traversal of its owning `RichTable`.

### 3.3 `table-column`

Explicit column widths currently materialized by `StyleWriter::writeColumnStyles()` are represented semantically as `table-column` style definitions with:

```text
family: table-column
scope: automatic
document part: content.xml
property group: style:table-column-properties
```

The width value remains the existing explicit width value. SR-07 changes ownership/materialization, not width calculation or layout behavior.

The structural `table:table-column` node continues to reference the corresponding column style name.

Ratio-based columns and `table:number-columns-repeated` remain structural and do not create semantic `table-column` style requirements merely because they affect layout.

## 4. Scope and document-part semantics

SR-07 must preserve the currently meaningful ODF scope split while removing accidental duplicate ownership.

### 4.1 Common definitions

Existing externally registered table and table-cell definitions that represent common styles remain compatible with `styles.xml` `office:styles` semantics.

Authored common definitions are authoritative on semantic identity collision.

### 4.2 Structured element-owned cell definitions

A `RichTableCell` definition produced from the cell's own style is document-local semantic ownership discovered before element materialization.

The implementation must choose one authoritative semantic definition channel for that owned definition and must not rely on `RichTable::toDomNode()` to discover/materialize it.

The target scope/part must preserve the effective normal structured-insertion behavior without retaining duplicate compatibility ownership. Existing characterization tests define the legacy observable baseline; any intentional duplicate reduction must be explicit in the implementation slice and tests.

### 4.3 Automatic column definitions

Explicit column-width definitions remain automatic definitions in `content.xml`.

Generated positional names such as `co0` are legacy behavior, not semantic identity design. The migration must prevent a semantic requirement from silently creating duplicate same-identity definitions inside one target automatic-style container.

This does not authorize redesigning the public width API or promising new collision behavior to callers outside the migrated semantic path.

## 5. Document-local ownership

The authoritative semantics for migrated table requirements are document-local.

`StyleContext` remains the document-local authority. SR-07 must extend the existing generic semantic pipeline rather than introduce a table-specific context or process-global current-document pointer.

Semantic requirement collection occurs before structured DOM materialization.

`RichTable::toDomNode()` should become responsible for table structure and references, not semantic style discovery or semantic style-definition side effects for migrated families.

`RichTableCell::toDomNode()` remains responsible for structural cell output and nested content.

## 6. StyleRequirement / materializer extension

The existing `StyleRequirement` model is extended only as necessary to support:

- family `table`
- family `table-column`
- family `table-cell`

The generic semantic identity remains based on the established requirement dimensions, including family, name, scope, and document part.

`StyleRequirementMaterializer` must support the native property groups:

- `style:table-properties`
- `style:table-column-properties`
- `style:table-cell-properties`

Materialization must use namespace-aware DOM creation consistent with the semantic materializer architecture.

Existing target definitions are authoritative according to the semantic identity and target-container rules already established by the style pipeline. Automatic `table-column` handling must not reproduce the legacy direct writer's duplicate `co0` behavior within the semantic path.

## 7. Producer responsibilities

### 7.1 RichTable

`RichTable` may produce:

- semantic table style references when it carries a table style name;
- semantic table definitions only where the table object actually owns the definition;
- semantic `table-column` definitions for explicit widths it owns;
- owned child requirements transitively through `RichTableCell`.

A mere `setTableStyleName()` reference must not fabricate a definition when none is owned by the table.

### 7.2 RichTableCell

`RichTableCell` produces its own semantic `table-cell` definition when it has mapped cell-style properties.

It must not absorb paragraph/text properties into that requirement.

Its current generated style name remains the structural reference unless a later explicitly reviewed slice changes naming semantics.

### 7.3 External/static registrations

`StyleMapper::registerTableStyle()` and `registerTableCellStyle()` remain compatibility APIs. Their static registries are not authoritative semantic ownership for unrelated documents.

Migration of normal structured insertion must not require callers to stop using those APIs.

## 8. Compatibility contract

SR-07 is a Legacy Reduction slice, not a compatibility purge.

The following surfaces remain available unless a later contract explicitly changes them:

- `RichTable::setTableStyleName()`
- `RichTable::setColumnWidths()`
- `RichTable::addRow(..., $style)`
- `RichTableCell::setStyle()` and fluent helpers
- `RichTableCell::getStyleDefinitions()`
- `RichTableCell::toStyleDomNode()`
- `RichTableCell::registerStyles()`
- `StyleMapper::registerTableStyle()` and getters
- `StyleMapper::registerTableCellStyle()` and getters
- `StyleWriter::writeAllStyles()` compatibility behavior
- `StyleWriter::writeColumnStyles()` as a compatibility callable unless a later explicit decision removes it
- legacy `assign()` / `render()` entry points

Protected/public facade wrappers should be retained where polymorphic or external compatibility may depend on them.

Static registries may remain observable as compatibility state, but migrated normal structured insertion must not treat unrelated process-global registry contents as authoritative document ownership.

## 9. Lifecycle contract

SR-07A characterized several surprising lifecycle behaviors:

- repeated `save()` can duplicate a common table-cell definition;
- repeated `setElement()` on an already replaced placeholder is accepted and can retain both tables while duplicating `co0`;
- static table/table-cell registrations can leak across documents.

SR-07 must distinguish ownership fixes from behavior fixes.

### 9.1 Required migration effect

Document-local semantic requirements produced by the migrated structured element path must not multiply merely because semantic collection/materialization is repeated for the same semantic identity.

Cross-document semantic leakage is not permitted.

### 9.2 Behavior not automatically changed

SR-07 does not redefine the public meaning of repeated `setElement()` or repeated `save()`.

Where duplicate output is solely an artifact of the legacy style-ownership path being replaced, narrowing that duplication may be an explicit consequence of semantic ownership migration. Such changes must be covered by focused tests and called out in the implementation slice; they must not be generalized into unrelated lifecycle redesign.

## 10. `table-row` boundary

`table-row` is explicitly excluded from active semantic implementation in SR-07.

The existing row-style argument remains accepted and dormant as characterized.

SR-07 must not silently activate it, reinterpret it, remove it, or create new row-style APIs.

If table-row semantics are needed later, they require their own evidence and contract.

## 11. Explicit non-goals

SR-07 does not redesign or fix:

- table width behavior;
- explicit column width calculation;
- relative width semantics;
- ratio-based / virtual-column layout behavior;
- row height or minimum height;
- vertical cell alignment;
- colspan/rowspan semantics;
- header-row behavior;
- table naming;
- HTML table semantics beyond preserving the existing importer path;
- public authoring syntax;
- unrelated paragraph/text/resource behavior;
- general repeated `setElement()` semantics;
- general repeated `save()` semantics.

Known layout work remains separate from this style-ownership migration.

## 12. Implementation slices

Implementation should proceed in small independently reviewable slices.

### SR-07B — Semantic family support

- extend semantic materialization for `table`, `table-column`, and `table-cell`;
- add focused unit/integration tests for family/property-group/scope/document-part behavior;
- no producer migration yet where avoidable.

### SR-07C — RichTableCell semantic ownership

- make `RichTableCell` produce semantic `table-cell` requirements;
- collect through existing ownership traversal;
- preserve paragraph/text separation;
- narrow direct cell-style materialization from `RichTable::toDomNode()` for the migrated normal path while retaining compatibility facade behavior;
- characterize any intentional duplicate reduction.

### SR-07D — RichTable / table-column semantic ownership

- migrate explicit-width `table-column` definitions to semantic requirements;
- preserve structural column references and width values;
- keep ratio columns structural;
- preserve `writeColumnStyles()` compatibility callable while removing it as authoritative normal structured-insertion ownership where possible;
- handle semantic identity collisions without reproducing duplicate `co0` definitions in the semantic path.

### SR-07E — Table definition/reference integration

- integrate semantic family `table` definitions/references with document-local ownership;
- preserve external/static registration compatibility;
- preserve authored common definition precedence;
- do not fabricate definitions for reference-only table style names.

### SR-07F — Compatibility and lifecycle closeout

- audit remaining static table/table-cell consumption;
- narrow cross-document leakage in migrated normal paths;
- preserve legacy/public facades;
- verify repeated save/render behavior against characterization and explicitly document any ownership-driven differences;
- run focused and full automated regression.

### SR-07G — Visual regression and closeout

Use at least:

- sample 11 — combined table/table-column/table-cell;
- sample 13 — spans + cell styling;
- sample 19 — HTML import path;
- sample 20 — ratio-column structural boundary.

Compare rendered LibreOffice output against the established baseline or pre-change reference. Any visual difference requires explanation; automated XML/tests do not replace this check.

## 13. Required validation

For relevant implementation slices run, as applicable:

- focused semantic style tests;
- `TableStyleSemanticsCharacterizationTest`;
- relevant integration/API contract tests;
- `PublicSampleSmokeTest`;
- full `composer test`;
- PHP lint for `src/` and `tests/`;
- `composer validate` when dependency/configuration relevance warrants it;
- `git diff --check`;
- documentation build/checks for documentation changes;
- LibreOffice visual regression for the selected sample subset at closeout.

No implementation slice may modify or normalize `samples/output/*.odt` as incidental work.

## 14. Acceptance criteria

SR-07 is complete only when:

1. `table`, `table-column`, and `table-cell` are supported by the document-local semantic style pipeline for the migrated structured path.
2. `RichTable` no longer needs direct semantic style-definition side effects for migrated normal insertion.
3. `RichTableCell` semantic ownership is collected transitively through existing structured ownership.
4. explicit column widths preserve current structural/layout values while their style definitions use semantic document-local ownership.
5. ratio-based columns remain structural.
6. authored common table/table-cell definitions remain authoritative where characterized.
7. semantic automatic definitions do not create duplicate same-identity `co0` definitions in the migrated path.
8. static compatibility APIs remain available without becoming authoritative ownership for unrelated documents.
9. `table-row` remains outside the active semantic slice and its dormant API behavior is preserved.
10. focused, full, sample-smoke, and visual regression gates pass or any intentional ownership-driven difference is explicitly documented and approved.

## 15. Review questions before approval

Before this contract becomes FINAL GO, explicitly review these decisions:

1. Is the active family scope correctly limited to `table`, `table-column`, and `table-cell`?
2. Is excluding `table-row` while preserving its dormant API the correct compatibility boundary?
3. Is `table-column` correctly fixed as automatic/content.xml for the explicit-width path?
4. Should element-owned `table-cell` definitions retain automatic/content.xml semantics as the authoritative normal path, with common/static output treated as compatibility only?
5. Is the distinction between a table style reference and an owned table definition strong enough to avoid fabricating definitions?
6. Is duplicate suppression in the semantic path accepted as ownership correction rather than an unrelated behavior redesign?
7. Are the compatibility facades sufficiently protected for external subclasses/callers?
8. Are SR-07B through SR-07G small enough to review and test independently?

Until these questions are accepted, this contract remains PROPOSED and production implementation must not begin.
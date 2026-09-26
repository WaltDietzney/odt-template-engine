# SR-07 — Semantic Table Style Requirements Change Contract

Status: FINAL GO

Base: `develop` after SR-07A merge `b5f46af9ca1d9e3c4d14b47dcb08c227de0e1f1d`

Evidence base: `docs/architecture/SR-07A_TABLE_SEMANTICS_AUDIT.md`

This contract defines the approved semantic migration boundary for table-related ODF styles. Implementation is authorized only within the boundaries and staged gates defined here.

## 1. Objective

SR-07 moves table-style ownership from mixed static/direct-DOM compatibility paths into the existing document-local semantic `StyleRequirement` / `StyleContext` pipeline and completes the semantic table-family model.

The semantic table families in this slice are:

- `table`
- `table-column`
- `table-row`
- `table-cell`

SR-07A found no active row-style producer even though `RichTable::addRow(..., $style)` already exposes a dormant row-style input. SR-07 deliberately goes one step beyond pure ownership migration for this family: it integrates `table-row` as a first-class semantic family and activates the existing dormant row-style surface in a controlled, explicitly reviewed slice.

The goal remains semantic ownership and model completeness, not a general table-layout redesign.

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
    table-row
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

A value does not become a style requirement merely because it currently passes through a style-shaped convenience API. Conversely, a native ODF style family is not excluded from the semantic model merely because the current legacy implementation failed to materialize it.

The approved ownership rule is:

```text
element-owned / generated style
    -> automatic style in the owning document part

authored / explicitly reusable style
    -> common style where that API or authored definition carries common-style semantics

reference only
    -> no fabricated definition
```

This rule is applied concretely by the family contracts below; it is not authorization to migrate unrelated style families during SR-07.

## 3. Family contracts

### 3.1 `table`

A table style definition is a semantic style definition with:

```text
family: table
property group: style:table-properties
```

A `table:style-name` on the structural `table:table` node is a semantic style reference.

Existing authored common table definitions remain authoritative when the same semantic identity already exists in the target document.

`setTableStyleName()` is reference-only. It must not fabricate a style definition when the table does not own one. A definition may be materialized only when there is an actual authored, registered, or element-owned definition source.

SR-07 does not redesign the public `setTableStyleName()` API or invent a new table-layout API.

### 3.2 `table-column`

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

### 3.3 `table-row`

A row style definition is a semantic style definition with:

```text
family: table-row
property group: style:table-row-properties
```

A `table:style-name` on the structural `table:table-row` node is the corresponding style reference.

`RichTable::addRow(..., $style)` already exposes row-style input but SR-07A characterized it as dormant: the style is stored but not materialized and no row style reference is emitted.

SR-07 explicitly authorizes controlled activation of that existing API surface. Before producer implementation, the accepted row-style input must be mapped narrowly to real ODF `table-row` properties. Unsupported or non-row concerns must not be passed through opportunistically.

This activation is an intentional behavior change and must be covered by focused characterization/contract tests. It is not incidental refactoring.

The first implementation must remain conservative. It should support only row properties whose mapping and ODF semantics are established from the current API/code surface and targeted ODF evidence. A broader row-layout API is outside SR-07.

### 3.4 `table-cell`

A styled `RichTableCell` owns a semantic style definition with:

```text
family: table-cell
scope: automatic
document part: content.xml
property group: style:table-cell-properties
```

The approved semantic property projection is the existing mapped cell-style responsibility, including properties such as background, border, and padding.

Paragraph properties remain paragraph requirements. Text properties remain text requirements. Structural colspan/rowspan attributes remain structural.

`RichTableCell` semantic requirements must be discoverable through the existing `ownedElements()` traversal of its owning `RichTable`.

## 4. Scope and document-part semantics

SR-07 preserves the meaningful ODF scope split while removing accidental duplicate ownership and introducing row-style semantics deliberately.

### 4.1 Common definitions

Existing externally registered table and table-cell definitions that represent common reusable styles remain compatible with `styles.xml` `office:styles` semantics.

Authored common definitions are authoritative on semantic identity collision.

If a future/compatibility row style is externally authored or registered as common, the same semantic identity and authored-definition precedence principles apply. SR-07 does not require inventing a new static row-style registry merely to mirror legacy table/table-cell state.

### 4.2 Structured element-owned cell definitions

A `RichTableCell` definition produced from the cell's own style is document-local semantic ownership discovered before element materialization.

Its authoritative normal structured-insertion channel is:

```text
scope: automatic
document part: content.xml
```

The semantic path must not rely on `RichTable::toDomNode()` to discover or materialize that definition.

The current common `styles.xml` output caused by static registration is not a second semantic truth for an element-owned cell style. Static/common output remains a compatibility concern for explicitly registered reusable styles and legacy paths.

Removing duplicate common/automatic ownership from the migrated element-owned path is an approved ownership correction and must be covered by focused tests.

### 4.3 Automatic column definitions

Explicit column-width definitions remain automatic definitions in `content.xml`.

Generated positional names such as `co0` are legacy behavior, not semantic identity design. The migration must prevent a semantic requirement from silently creating duplicate same-identity definitions inside one target automatic-style container.

SR-07 does not introduce a new name-allocation or collision-renaming strategy. If an existing target definition already owns the same semantic identity, that target definition remains authoritative under the established semantic materializer rules.

This does not authorize redesigning the public width API or promising new collision behavior to callers outside the migrated semantic path.

### 4.4 Row definitions

The exact scope/document-part semantics for generated element-owned `table-row` definitions must be established before the row producer is implemented.

The architectural expectation is that row styles generated for concrete structured table rows behave like document-local automatic styles associated with the content part, but SR-07E must confirm that against ODF/LibreOffice-authored evidence rather than assume symmetry with columns or cells.

The row semantics decision must be documented and reviewed before producer implementation begins.

## 5. Document-local ownership

The authoritative semantics for migrated table requirements are document-local.

`StyleContext` remains the document-local authority. SR-07 extends the existing generic semantic pipeline rather than introducing a table-specific context or process-global current-document pointer.

Semantic requirement collection occurs before structured DOM materialization.

`RichTable::toDomNode()` should become responsible for table structure and references, not semantic style discovery or semantic style-definition side effects for migrated families.

`RichTableCell::toDomNode()` remains responsible for structural cell output and nested content.

Row style definitions must likewise be collected/materialized semantically before row DOM output; row DOM generation then emits structure plus the resolved style reference.

## 6. StyleRequirement / materializer extension

The existing `StyleRequirement` model is extended only as necessary to support:

- family `table`
- family `table-column`
- family `table-row`
- family `table-cell`

The generic semantic identity remains based on the established requirement dimensions, including family, name, scope, and document part.

`StyleRequirementMaterializer` must support the native property groups:

- `style:table-properties`
- `style:table-column-properties`
- `style:table-row-properties`
- `style:table-cell-properties`

Materialization must use namespace-aware DOM creation consistent with the semantic materializer architecture.

Within a target context, the same semantic identity is materialized at most once. Existing target definitions are authoritative according to the semantic identity and target-container rules already established by the style pipeline.

Automatic `table-column` handling must not reproduce the legacy direct writer's duplicate `co0` behavior within the semantic path. Likewise, migrated element-owned `table-cell` styles must not intentionally reproduce duplicate automatic/common ownership merely for compatibility with an accidental legacy side effect.

SR-07 does not add automatic collision renaming such as inventing `co1`, hashes, or another naming scheme when a conflicting identity/name is encountered. Naming/collision policy beyond semantic de-duplication remains separate future work.

## 7. Producer responsibilities

### 7.1 RichTable

`RichTable` may produce:

- semantic table style references when it carries a table style name;
- semantic table definitions only where the table object actually owns the definition;
- semantic `table-column` definitions for explicit widths it owns;
- semantic `table-row` definitions/references for row styles accepted through the existing row API after the row property contract is established;
- owned child requirements transitively through `RichTableCell`.

A mere `setTableStyleName()` reference must not fabricate a definition when none is owned by the table.

### 7.2 Row producer semantics

The row-style producer must be derived from the existing `RichTable` row model rather than introducing a separate row context object solely for styles.

The implementation must:

- preserve existing row ordering and structural rendering;
- map only approved row-style options;
- create a semantic `table-row` definition when approved row properties are present;
- emit the corresponding row style reference structurally;
- avoid absorbing cell, paragraph, text, or unrelated layout responsibilities into the row requirement;
- leave rows without row-style input behaviorally unchanged.

If the current row representation cannot support this cleanly without introducing duplicated mutable state or a speculative abstraction, the implementation slice must stop and return to contract review rather than force the design.

### 7.3 RichTableCell

`RichTableCell` produces its own semantic automatic/content-local `table-cell` definition when it has mapped cell-style properties.

It must not absorb paragraph/text properties into that requirement.

Its current generated style name remains the structural reference unless a later explicitly reviewed slice changes naming semantics.

### 7.4 External/static registrations

`StyleMapper::registerTableStyle()` and `registerTableCellStyle()` remain compatibility APIs. Their static registries are not authoritative semantic ownership for unrelated documents.

Migration of normal structured insertion must not require callers to stop using those APIs.

SR-07 does not introduce a static `table-row` registry solely for architectural symmetry unless concrete compatibility evidence requires one.

## 8. Compatibility contract

SR-07 is primarily a Legacy Reduction slice, with one explicitly authorized API completion: activation of the dormant row-style input.

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

The new semantic pipeline must be decoupled from compatibility facades rather than removing those facades merely because the normal path no longer depends on them.

Public and protected compatibility-sensitive wrappers remain available where external callers, polymorphism, or subclasses may depend on them. In particular, compatibility APIs such as `getStyleDefinitions()`, `toStyleDomNode()`, and `writeColumnStyles()` are not removed as incidental cleanup during SR-07.

Static registries may remain observable as compatibility state, but migrated normal structured insertion must not treat unrelated process-global registry contents as authoritative document ownership.

The behavioral exception is explicit: row-style input that was previously accepted but ignored may become effective according to the newly documented row property contract. This change must be recorded in tests and closeout documentation.

## 9. Lifecycle contract

SR-07A characterized several surprising lifecycle behaviors:

- repeated `save()` can duplicate a common table-cell definition;
- repeated `setElement()` on an already replaced placeholder is accepted and can retain both tables while duplicating `co0`;
- static table/table-cell registrations can leak across documents.

SR-07 distinguishes ownership fixes from behavior fixes.

### 9.1 Required migration effect

Document-local semantic requirements produced by the migrated structured element path must not multiply merely because semantic collection/materialization is repeated for the same semantic identity.

Cross-document semantic leakage is not permitted.

The same rule applies to newly activated `table-row` semantic requirements.

### 9.2 Behavior not automatically changed

SR-07 does not redefine the public meaning of repeated `setElement()` or repeated `save()`.

Where duplicate output is solely an artifact of the legacy style-ownership path being replaced, narrowing that duplication is an approved consequence of semantic ownership migration. Such changes must be covered by focused tests and called out in the implementation slice; they must not be generalized into unrelated lifecycle redesign.

## 10. `table-row` activation contract

`table-row` is a full semantic member of the SR-07 model.

The existing row-style argument is no longer treated as permanently dormant. SR-07 explicitly authorizes its controlled activation.

Before producer implementation, the SR-07E row semantics gate must answer and document:

1. which currently accepted row-style keys are intended to map to native `style:table-row-properties`;
2. which keys are unsupported, ambiguous, or belong to another responsibility;
3. whether generated row styles are automatic/content-local or require another scope/part based on ODF/LibreOffice evidence;
4. how row style names are generated and referenced without introducing a new global registry merely for symmetry;
5. how rows without style input remain byte/structure-equivalent where practical;
6. which focused tests and at least one visual sample prove the new behavior.

Activation must be narrow and evidence-driven. SR-07 must not broaden the dormant `$style` array into an unrestricted row-layout DSL.

**Semantics gate:** SR-07E producer code must not begin until these row-property and scope/document-part decisions are documented and explicitly reviewed. If the evidence contradicts this contract's architectural expectation, implementation stops and the contract is revisited first.

## 11. Explicit non-goals

SR-07 does not redesign or fix:

- table width behavior;
- explicit column width calculation;
- relative width semantics;
- ratio-based / virtual-column layout behavior;
- broad row-layout or row-sizing APIs beyond the narrowly mapped existing row-style input;
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

Implementation proceeds in small independently reviewable slices.

### SR-07B — Semantic family support

- extend semantic materialization for `table`, `table-column`, `table-row`, and `table-cell`;
- add focused unit/integration tests for family/property-group/scope/document-part behavior;
- no producer migration yet where avoidable.

### SR-07C — RichTableCell semantic ownership

- make `RichTableCell` produce semantic automatic/content-local `table-cell` requirements;
- collect through existing ownership traversal;
- preserve paragraph/text separation;
- narrow direct cell-style materialization from `RichTable::toDomNode()` for the migrated normal path while retaining compatibility facade behavior;
- characterize and test the approved duplicate-ownership reduction.

### SR-07D — RichTable / table-column semantic ownership

- migrate explicit-width `table-column` definitions to semantic requirements;
- preserve structural column references and width values;
- keep ratio columns structural;
- preserve `writeColumnStyles()` compatibility callable while removing it as authoritative normal structured-insertion ownership where possible;
- enforce semantic de-duplication without introducing a new collision-renaming strategy.

### SR-07E — Table-row semantics and producer integration

SR-07E is internally gated:

**SR-07E1 — Row semantics decision**

- perform the focused row-property semantics review required by section 10;
- document the accepted mapping and scope/document-part decision;
- review that decision before producer code begins.

**SR-07E2 — Row producer implementation**

- begin only after SR-07E1 receives explicit GO;
- activate the existing `RichTable::addRow(..., $style)` surface only for approved native row properties;
- produce semantic `table-row` requirements and structural row references;
- preserve rows without style input;
- add focused API/integration tests and a visual regression case for the newly effective behavior.

### SR-07F — Table definition/reference integration

- integrate semantic family `table` definitions/references with document-local ownership;
- keep `setTableStyleName()` reference-only unless an actual definition source exists;
- preserve external/static registration compatibility;
- preserve authored common definition precedence;
- do not fabricate definitions for reference-only table style names.

### SR-07G — Compatibility and lifecycle closeout

- audit remaining static table/table-cell consumption;
- narrow cross-document leakage in migrated normal paths;
- preserve legacy/public/protected facades where compatibility may depend on them;
- verify repeated save/render behavior against characterization and explicitly document ownership-driven differences;
- include row-style activation in compatibility/release notes for the architecture closeout;
- run focused and full automated regression.

### SR-07H — Visual regression and closeout

Use at least:

- sample 11 — combined table/table-column/table-cell;
- sample 13 — spans + cell styling;
- sample 19 — HTML import path;
- sample 20 — ratio-column structural boundary;
- one focused row-style sample/test fixture established during SR-07E.

Compare rendered LibreOffice output against the established baseline or pre-change reference. Any visual difference requires explanation; automated XML/tests do not replace this check.

## 13. Required validation

For relevant implementation slices run, as applicable:

- focused semantic style tests;
- `TableStyleSemanticsCharacterizationTest`;
- row-style contract/integration tests introduced by SR-07E;
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

1. `table`, `table-column`, `table-row`, and `table-cell` are supported by the document-local semantic style pipeline.
2. `RichTable` no longer needs direct semantic style-definition side effects for migrated normal insertion.
3. element-owned `RichTableCell` styles are authoritative automatic definitions in `content.xml` and are collected transitively through existing structured ownership.
4. explicit column widths preserve current structural/layout values while their style definitions use automatic/content-local semantic ownership.
5. ratio-based columns remain structural.
6. the existing row-style input is mapped narrowly to native `table-row` semantics and produces explicit row style references without absorbing unrelated responsibilities.
7. rows without row-style input preserve existing behavior.
8. a table style reference does not fabricate a definition; authored or otherwise genuinely owned definitions remain authoritative according to established semantic identity rules.
9. the same semantic identity is materialized at most once in a target context; accidental legacy duplicates are not deliberately reproduced.
10. SR-07 introduces no new general collision-renaming/name-allocation strategy.
11. static compatibility APIs and compatibility-sensitive public/protected facades remain available without becoming authoritative ownership for unrelated documents.
12. the intentional activation of row-style behavior is documented, tested, and visually reviewed.
13. SR-07E1 receives explicit semantic GO before SR-07E2 producer implementation begins.
14. focused, full, sample-smoke, and visual regression gates pass or any intentional ownership-driven difference is explicitly documented and approved.

## 15. Approved architecture decisions

The pre-implementation review is complete. The following decisions are accepted:

1. The semantic table-family model comprises `table`, `table-column`, `table-row`, and `table-cell`.
2. The existing dormant row-style API is intentionally activated within SR-07 under the narrow SR-07E semantics gate.
3. Row-style mapping is evidence-driven and limited to genuine ODF `table-row` properties; SR-07 does not create a broad row-layout DSL.
4. Explicit-width `table-column` definitions are automatic styles in `content.xml`; ratio/repeated columns remain structural.
5. Element-owned `table-cell` definitions are authoritative automatic styles in `content.xml`; common/static table-cell output remains authored/reusable or compatibility territory.
6. Style reference and style definition are distinct. `setTableStyleName()` does not fabricate a definition.
7. Semantic identity materializes at most once in a target context. Removing accidental legacy duplicates is an accepted ownership correction, not a general lifecycle redesign. Existing authored target definitions remain authoritative; SR-07 does not invent a new collision-renaming strategy.
8. Existing public/protected compatibility-sensitive facades are retained where callers or subclasses may depend on them, while the new semantic path is decoupled from those facades as authoritative ownership.
9. SR-07B through SR-07H remain independently reviewable slices. SR-07E is explicitly split by a semantics gate: SR-07E1 decision first, SR-07E2 producer implementation only after explicit GO.

With these decisions accepted, this Change Contract is **FINAL GO** for SR-07B and the subsequent slices subject to their stated gates.
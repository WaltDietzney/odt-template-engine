# TABLE-LAYOUT-01B — Architecture Synthesis / Change-Contract Preparation

Status: REVISED ARCHITECTURE SYNTHESIS / PRE-CONTRACT

Parent milestone: `TABLE-LAYOUT-01`

Predecessor: `TABLE_LAYOUT_01A_CLOSEOUT.md`

Supporting evidence:

- `TABLE_LAYOUT_01A_EVIDENCE_FINDINGS.md`
- `TABLE_LAYOUT_01B_CURRENT_TABLE_API_INVENTORY.md`
- `TABLE_LAYOUT_01B_PUBLIC_STYLE_SEMANTICS_CORRECTION.md`
- `TABLE_LAYOUT_01B_TABLE_STYLE_SCOPE_EVIDENCE.md`
- `SR-07_SEMANTIC_TABLE_STYLE_REQUIREMENTS_CHANGE_CONTRACT.md`
- `STYLE_API_02F_MAPPER_REGISTRY_CLEANUP_CHANGE_CONTRACT.md`

## 1. Purpose

TABLE-LAYOUT-01B translates the completed TABLE-LAYOUT-01A evidence, recovered STYLE-API/SR-07 architecture decisions, current table API inventory, and focused Writer regressions into the smallest coherent design space that can later be frozen in a Change Contract.

This phase does **not** authorize production implementation.

The purpose of this revised synthesis is specifically to avoid two mistakes:

1. treating historical raw ODF QName input as the intended public table-layout API;
2. treating the current common-style placement of element-owned `RichTable` styles as target architecture merely because existing samples still open successfully.

TABLE-LAYOUT-01 must finish deliberately deferred table-layout authoring semantics on top of the architecture already built. It must not rebuild the style system.

## 2. Governing principle

TABLE-LAYOUT-01 follows this rule:

> The public engine API expresses table-layout intent in engine vocabulary. The semantic style layer owns native ODF family/property semantics. LibreOffice/Writer resolves and may normalize final physical geometry.

This yields three separate layers:

```text
APPLICATION / AUTHORING SEMANTICS
    table width
    relative width
    row height
    minimum row height
    vertical cell alignment
    potentially whole-table placement where justified

            ↓

SEMANTIC OWNERSHIP / MAPPING
    table
    table-column
    table-row
    table-cell

            ↓

NATIVE ODF
    style:width
    style:rel-width
    table:align
    style:column-width
    style:rel-column-width
    style:row-height
    style:min-row-height
    style:vertical-align
```

ODF QNames are implementation/materialization vocabulary. Existing prefixed input remains compatibility/advanced escape-hatch behavior where already supported; it is not the preferred public language.

## 3. Architecture already established and not reopened

TABLE-LAYOUT-01 starts from completed architecture.

### 3.1 Semantic table families

SR-07 established semantic ownership for:

```text
table
table-column
table-row
table-cell
```

TABLE-LAYOUT-01 does not invent new style families or a table-specific style context.

### 3.2 Document-local semantic pipeline

The target path remains:

```text
application intent
    -> structured element local state
    -> StyleRequirement
    -> StyleContext
    -> StyleRequirementMaterializer
    -> ODF
```

New table-layout behavior must not expand the remaining direct `StyleWriter` compatibility path.

### 3.3 Named style reference semantics

`RichTable::setTableStyleName()` remains reference semantics.

The governing distinction remains:

```text
reference != definition != mutation
```

A named style reference must not silently become an element-owned generated definition merely because a later convenience call wants geometry.

### 3.4 Cell / paragraph / text responsibility split

The current `RichTableCell` path is architecture baseline:

- cell-owned properties remain on the `table-cell` family;
- paragraph concerns remain paragraph semantics;
- text concerns remain text semantics;
- `StyleOptionSplitter` and `StyleMapper` preserve friendly application-facing options while projecting them to the correct owner.

TABLE-LAYOUT-01 must extend this model rather than bypass it.

## 4. Existing table geometry that remains authoritative

### 4.1 Absolute column widths

`RichTable::setColumnWidths()` remains the established semantic surface for native:

```xml
<style:table-column-properties style:column-width="..."/>
```

Its current public return type and compatibility side effects are not cleanup targets in this milestone.

### 4.2 Relative column widths

`RichTable::setColumnWidthRatios()` remains the established semantic surface for:

```xml
<style:table-column-properties style:rel-column-width="...*"/>
```

The existing LibreOffice interoperability rule remains authoritative:

```text
USHRT_MAX = 65535 = 2^16 - 1
```

Example:

```text
[2, 1, 1]
    ->
32766* / 16383* / 16386*
```

This is a Writer interoperability rule established during SR-07H, not an ODF normative constant.

### 4.3 Minimum row height

The existing engine-level row option:

```php
$table->addRow($cells, ['min-row-height' => '2cm']);
```

continues to map to:

```xml
<style:table-row-properties style:min-row-height="2cm"/>
```

### 4.4 Raw/native compatibility paths

Existing prefixed table and cell properties that currently pass through must be treated as compatibility/advanced surfaces.

Characterization of those paths freezes current behavior for compatibility review; it does **not** promote them to recommended authoring syntax.

## 5. Writer/ODF geometry evidence

TABLE-LAYOUT-01A established the native model:

```text
Table
├── absolute width        -> style:width
├── relative width        -> style:rel-width
├── placement/alignment   -> table:align / relevant margins
└── logical columns
    ├── absolute width    -> style:column-width
    └── relative weight   -> style:rel-column-width

Row
├── exact/fixed height    -> style:row-height
└── minimum/growable      -> style:min-row-height

Cell
└── vertical alignment    -> style:vertical-align
```

Writer evidence also establishes that semantic intent and Writer serialization are not identical:

- Writer may normalize entered physical dimensions;
- Writer may retain a relative table width while also writing a calculated absolute `style:width`;
- Writer may represent visibly proportional columns through absolute geometry in some authoring cases;
- fixed and minimum row height are distinct semantics;
- Writer's UI/default serialization must not be mistaken for the engine's public vocabulary.

The engine should author native intent and leave physical layout resolution to Writer.

## 6. Recovered table API architecture

The current API inventory shows that the table subsystem is intentionally mixed because previous architecture milestones solved ownership before finishing table-layout authoring semantics.

### 6.1 Structure is already mature

Authoritative existing structural surfaces include:

- `setTableName()`;
- `setHeaderRowCount()`;
- row/cell construction;
- colspan/rowspan;
- nested structured content.

These are outside TABLE-LAYOUT redesign.

### 6.2 Table-level style ownership is modern, authoring syntax is not finished

Current `RichTable::setStyle([...])` stores an element-owned table definition locally.

That ownership direction is correct.

However, the current option array is effectively a raw/native table-property path; there is no table-level friendly mapper comparable to `mapTableCellStyleOptions()`.

Therefore:

```text
RichTable element ownership
    = architecture baseline

current raw setStyle() vocabulary
    = compatibility/provisional authoring surface
```

TABLE-LAYOUT-01 must not confuse these two facts.

### 6.3 Cell authoring is the stronger API precedent

`RichTableCell::setStyle()` already demonstrates the intended pattern:

```text
friendly options
    -> splitter / mapper
    -> semantic owner
    -> StyleRequirement
    -> materialization
```

Any new table-level friendly option array, if approved later, should follow this pattern rather than expose ODF QNames directly.

## 7. Element-owned table-style scope mismatch

A focused Writer regression uncovered a concrete mismatch in the current implementation.

### 7.1 Writer concrete-table behavior

Writer-authored concrete table geometry is observed as:

```text
family:        table
scope:         automatic
document part: content.xml
property group: style:table-properties
```

Examples include absolute width, relative width, and placement.

### 7.2 Current `RichTable::setStyle()` behavior

The current element-owned table definition is emitted as:

```text
family:        table
scope:         common
document part: styles.xml
property group: style:table-properties
```

The properties themselves are transported correctly. The mismatch is scope/document-part.

### 7.3 Historical contract already decided the ownership rule

SR-07 already approved:

```text
element-owned / generated style
    -> automatic style in the owning document part

authored / explicitly reusable style
    -> common style where the authored API carries common-style semantics

reference only
    -> no fabricated definition
```

STYLE-API-02F likewise distinguished a named reference from an element-owned generated table definition and deliberately deferred only the exact public authoring syntax.

Therefore the current common/`styles.xml` placement of an element-owned generated `RichTable` definition is best classified as a compatibility/implementation residue, not as target architecture.

### 7.4 This is a bounded correction, not a style-ownership redesign

TABLE-LAYOUT-01 may need to correct the element-owned table definition to automatic/`content.xml`, but only under an explicit Change Contract with compatibility tests.

It must not alter the semantics of:

- authored reusable common table styles;
- named style references;
- common style precedence;
- unrelated style families.

This is restoration of the already-approved SR-07 ownership rule, not a new style architecture.

## 8. Genuine TABLE-LAYOUT-01 capability gaps

The mandatory 1.0 milestone scope is:

- explicit table width;
- absolute column widths;
- relative column widths;
- row/minimum height;
- vertical cell alignment.

Absolute and relative column semantics are already implemented and remain regression baseline.

The actual missing or unfinished capabilities are therefore:

### 8.1 Explicit overall table width

The engine needs a friendly authoring semantic for absolute table width that ultimately owns:

```xml
style:width="..."
```

The exact public method/array syntax is **not frozen in 01B yet**.

### 8.2 Relative overall table width

The engine needs a friendly authoring semantic for relative table width that ultimately owns:

```xml
style:rel-width="..."
```

Writer may later calculate and persist a companion absolute width. The engine should not reproduce Writer's layout calculation unless later evidence requires it.

The exact public syntax is not frozen yet.

### 8.3 Exact/fixed row height

The existing row option surface should be extended semantically so that:

```php
['row-height' => '2cm']
```

means:

```xml
style:row-height="2cm"
```

This is distinct from the existing `min-row-height` semantic.

No row object or new row subsystem is justified.

### 8.4 Vertical cell alignment

The cell layer needs friendly semantic mapping for:

```php
['vertical-align' => 'middle']
```

to:

```xml
style:vertical-align="middle"
```

This belongs to `table-cell`, not paragraph alignment.

Existing native `style:vertical-align` compatibility behavior remains available.

## 9. Whole-table placement/alignment

Writer evidence shows:

```text
table width
    !=
table placement/alignment
```

Native placement includes `table:align`, but the mandatory TABLE-LAYOUT-01 backlog does not independently list a broad table-alignment feature.

The public authoring form for whole-table placement is therefore **not automatically approved** merely because the native property exists.

Two constraints are already clear:

1. normal application code should not be required to use `table:align`;
2. a generic method name such as `setAlignment()` would be semantically ambiguous because table/cell/paragraph alignment are different owners.

Whether TABLE-LAYOUT-01 must add a friendly whole-table placement semantic to make explicit width professionally useful belongs to the next API-design step. If it is included, the name and mapping must be table-specific and must share the same table semantic state rather than create a parallel subsystem.

Until then, existing raw/native alignment remains a compatibility escape hatch.

## 10. Array API versus dedicated geometry methods

The inventory shows two plausible public forms:

```text
A. dedicated intent-bearing methods
B. a friendly table-level option array mapped through a table mapper
```

These are not mutually exclusive, but they must not create separate authorities.

If both are approved later, the architectural rule must be:

```text
friendly table option array
dedicated table convenience methods
            ↓
      one table semantic state
            ↓
      one mapping/projection path
            ↓
      StyleRequirement
```

The exact method names and whether a new friendly table option array is required for 1.0 remain deliberately open until the API-design step.

This synthesis therefore supersedes earlier 01B examples that prematurely proposed `setWidth()` / `setRelativeWidth()` as final names.

## 11. Interaction semantics already constrained

Even before final API naming, several semantic rules are established.

### 11.1 Table width versus column geometry

```text
TABLE WIDTH
    !=
ABSOLUTE COLUMN WIDTH
    !=
RELATIVE COLUMN WIDTH
```

Therefore:

- overall table width must not silently rewrite column widths;
- column APIs must not synthesize overall table width;
- TABLE-LAYOUT-01 need not validate that absolute column totals exactly equal an explicit table width;
- Writer remains responsible for physical reconciliation/normalization.

### 11.2 Absolute versus relative table-width intent

At the friendly semantic level, absolute and relative overall width are competing authoring intents.

The final API should define deterministic replacement/rejection behavior rather than silently author contradictory friendly intent.

This rule does not retroactively normalize arbitrary raw/native compatibility input.

### 11.3 Exact versus minimum row height

These are distinct semantics.

The friendly row path should support one or the other for a row. Simultaneous friendly `row-height` and `min-row-height` should be rejected rather than given invented precedence.

### 11.4 Vertical versus horizontal alignment

```text
cell vertical alignment
    -> table-cell family

paragraph horizontal alignment
    -> paragraph family

whole-table placement
    -> table family
```

The public API must keep those concepts visibly distinct.

## 12. Compatibility boundaries

TABLE-LAYOUT-01 must preserve established behavior unless the Change Contract explicitly authorizes a bounded correction.

### Preserve

```text
setColumnWidths()
setColumnWidthRatios()
65535 Writer normalization
min-row-height
cell/paragraph/text responsibility split
native prefixed escape hatches
setTableStyleName() reference semantics
repeated save/materialization stability
sample-visible structure/span behavior
```

### Characterize before changing

```text
RichTable::setStyle() common/styles.xml output
setStyle() replacement/clearing behavior
setStyle() <-> setTableStyleName() call order
Sample 11 table-level raw/native properties
first-row __column-width compatibility side effect
StyleWriter::writeColumnStyles() fallback
unsupported row-style keys being ignored
```

### Bounded correction candidate

```text
element-owned generated RichTable definition
    common/styles.xml
        ->
    automatic/content.xml
```

This candidate exists because Writer evidence and the already-approved SR-07 ownership rule agree. It still requires an explicit TABLE-LAYOUT-01 Change Contract and focused compatibility tests before production code changes.

## 13. Validation philosophy

TABLE-LAYOUT-01 should validate engine-level semantic input narrowly without becoming a general CSS/ODF validator.

Likely contract rules include:

- non-empty ODF-compatible length strings for absolute widths/heights;
- percentage-shaped strings for relative table width unless API review approves another convention;
- explicit allowed values for friendly cell vertical alignment;
- no silent coercion between exact and minimum row height;
- no automatic unit conversion layer;
- continued advanced/raw escape hatches where already supported.

Exact public signatures and exception conventions belong to the next API-design/contract step.

## 14. Non-goals

TABLE-LAYOUT-01 must not expand into:

- a PHP table layout engine;
- automatic measurement of text content;
- page-width/page-margin calculation;
- generic auto-fit algorithms;
- automatic distribution of unspecified columns;
- automatic reconciliation of table width with column totals;
- row pagination rules;
- merged-cell redesign;
- a new style context;
- a new global table registry;
- reopening the completed cell/paragraph/text ownership architecture;
- broad reusable table-style authoring APIs not required by the milestone;
- frame positioning;
- page-style authoring;
- template authoring UX;
- speculative cleanup of compatibility paths.

The bounded element-owned table-style scope correction described above is not considered a broad ownership redesign because it restores the already-approved SR-07 ownership rule.

## 15. Revised implementation-slice direction

The Change Contract should authorize only small slices after public semantics are accepted.

### Slice 1 — Element-owned table-style scope correction + explicit table-width semantics

Potential responsibilities:

- characterize current common/`styles.xml` behavior before changing it;
- move element-owned generated table definitions to automatic/`content.xml` if approved by contract;
- add the accepted friendly absolute/relative table-width semantics;
- preserve named/common style semantics;
- verify coexistence with absolute and relative column APIs;
- verify repeated save/reopen stability.

The scope correction and width feature belong together only if the contract confirms that reliable table width depends on the corrected element-owned definition channel. They should not be split in a way that temporarily creates a second parallel table-style authority.

### Slice 2 — Row height

- add exact/fixed row-height mapping to the existing row semantic layer;
- preserve minimum row height;
- reject contradictory friendly height intent;
- add materialization and visual Writer regression.

### Slice 3 — Vertical cell alignment

- add friendly vertical-alignment mapping;
- preserve raw/native compatibility;
- keep paragraph horizontal alignment independent;
- add mapping/materialization/visual regression.

### Slice 4 — Integration/preflight

- explicit table width + column width/ratio combinations;
- public samples;
- repeated save/reopen;
- focused SR-07/STYLE-API compatibility tests;
- full test suite;
- lint/diff checks;
- manual LibreOffice regression.

The number and exact grouping of implementation slices are not frozen until the Change Contract.

## 16. Remaining decisions before TABLE-LAYOUT-01C

The evidence/architecture layer is now substantially resolved.

The remaining work is primarily public API design and compatibility contract shaping:

1. What is the preferred engine-level public representation of absolute and relative overall table width?
2. Does TABLE-LAYOUT-01 require friendly whole-table placement/alignment for 1.0, and if so what unambiguous table-specific semantic name/options should it use?
3. Should table-level authoring expose both a friendly option-array form and dedicated convenience methods, or only the minimum surface required for 1.0?
4. How do those friendly forms interact with existing raw/native `RichTable::setStyle()` without converting it into the recommended API?
5. How is named-style reference state protected from accidental mutation?
6. What exact compatibility behavior is promised when element-owned generated table definitions move from common/`styles.xml` to automatic/`content.xml`?
7. What exact validation/exception conventions are used for conflicting row-height intent and invalid alignment/percentage values?

These questions should now be answered from API consistency, current code behavior, and 1.0 scope—not by inventing new ODF semantics.

## 17. Exit criterion for TABLE-LAYOUT-01B

TABLE-LAYOUT-01B is ready to close when:

1. the friendly public table-layout surface is accepted;
2. whole-table placement/alignment is explicitly included or deferred;
3. compatibility behavior for the element-owned table-style scope correction is defined;
4. named/reference/common style semantics remain protected;
5. exact row height and vertical cell alignment semantics are accepted;
6. the result can be written as a precise Change Contract without further architecture invention.

No production implementation is authorized by this document.

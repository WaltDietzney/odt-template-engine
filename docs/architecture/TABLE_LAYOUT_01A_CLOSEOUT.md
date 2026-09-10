# TABLE-LAYOUT-01A — Closeout

Status: COMPLETE / READY FOR TABLE-LAYOUT-01B

Parent milestone: `TABLE-LAYOUT-01`

## 1. Purpose

This document closes the TABLE-LAYOUT-01A evidence and characterization phase.

TABLE-LAYOUT-01A was intentionally limited to establishing native ODF semantics, observed LibreOffice Writer serialization, existing SR-07 architecture evidence, and current engine compatibility boundaries. It authorizes no production implementation and does not itself approve a public API.

The detailed evidence remains in:

- `TABLE_LAYOUT_01A_NATIVE_WRITER_ODF_GEOMETRY_EVIDENCE.md`
- `TABLE_LAYOUT_01A_EVIDENCE_FINDINGS.md`

## 2. Evidence sources accepted

The phase is based on four evidence layers:

1. ODF 1.2 normative semantics for table, table-column, table-row, and table-cell geometry;
2. existing repository Writer oracle `tests/fixtures/libreoffice-reference/extracted/TABLE-02/content.xml`;
3. the manually authored combined Writer oracle `TL01A.odt` used during the research phase;
4. established SR-07 / SR-07H architecture evidence and current implementation behavior.

Existing knowledge is retained rather than rediscovered. In particular, the SR-07H relative-column interoperability rule remains authoritative baseline evidence:

```text
USHRT_MAX = 65535 = 2^16 - 1
```

For positive integer ratios the engine normalizes `style:rel-column-width` values into that Writer-compatible 65535 space and assigns the rounding remainder to the final column.

This is a LibreOffice Writer interoperability rule, not an ODF normative requirement.

## 3. Native semantic model established

TABLE-LAYOUT-01A establishes the following semantic ownership model:

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

These concerns belong to the already-established SR-07 style families and do not reopen style ownership architecture.

## 4. Writer serialization findings retained

The Writer evidence shows that semantic authoring intent and Writer-materialized physical geometry are not identical concepts.

Important observed behavior includes:

- ordinary full-width tables may carry explicit physical `style:width` together with `table:align="margins"`;
- relative table width may be serialized together with a Writer-calculated absolute `style:width`;
- relative and absolute column hints may coexist in some Writer-authored tables;
- a visually proportional column layout may also be materialized by Writer using absolute column widths only;
- Writer may normalize entered decimal measures during save/reopen;
- Writer's row-height UI maps dynamic growth to `style:min-row-height` and fixed height to `style:row-height`;
- Writer's default/top cell alignment was observed as an empty `style:vertical-align` value in the research oracle, while ODF normatively permits explicit `top`.

No implementation requirement is inferred merely from Writer emitting calculated helper geometry.

## 5. Characterization results

Two focused characterization layers were added without changing production code.

### 5.1 Element-level characterization

`tests/Elements/TableLayout01ACurrentBehaviorCharacterizationTest.php`

Verified locally:

```text
OK (6 tests, 27 assertions)
```

This freezes the following current behavior:

- `RichTable::setStyle()` can pass native table geometry properties through the semantic table-style requirement path;
- table width and absolute column widths remain independent style requirements;
- relative column ratios retain the established 65535 Writer normalization;
- the row convenience path recognizes `min-row-height` but currently ignores `row-height`;
- native `style:vertical-align` is accepted as a cell-owned property;
- unprefixed `vertical-align` is not currently mapped by the convenience mapper.

### 5.2 Lifecycle/materialization characterization

`tests/Integration/TableLayout01AGeometryLifecycleCharacterizationTest.php`

Verified locally:

```text
OK (4 tests, 78 assertions)
```

This freezes current materialization/lifecycle behavior for the relevant geometry paths, including coexistence of table and column geometry, repeated save stability, and the current absence of exact-row-height materialization through the row convenience path.

## 6. Compatibility boundaries for TABLE-LAYOUT-01B

The following are existing capabilities and must be preserved unless a separately documented incompatibility is demonstrated:

```text
absolute column widths
relative column ratios
Writer-compatible 65535 ratio normalization
minimum row height
native table-family style pass-through
native table-cell style pass-through
```

The following are genuine milestone gaps or unresolved semantic API questions:

```text
explicit table-width authoring semantics
relative table-width authoring semantics
exact/fixed row-height authoring
cell vertical-alignment convenience semantics
```

A later implementation must not silently turn these gaps into retroactive assumptions about current behavior.

## 7. Governing principle

TABLE-LAYOUT-01 continues the same architecture direction established by PAGE-FLOW-01:

> The engine describes native ODF geometry semantics; LibreOffice/Writer computes and may normalize the final physical layout.

The engine should express authoring intent using native ODF structures and should not attempt to reproduce Writer's full layout engine.

## 8. Deferred / explicitly unresolved questions

TABLE-LAYOUT-01A does not decide:

- the final public API names for table width, row height, or vertical alignment;
- whether relative table-width authoring should emit only `style:rel-width` or also a calculated `style:width`;
- whether Writer's empty top/default vertical-alignment serialization should influence the engine's semantic API;
- broader style API topics tracked separately by `STYLE-API-02` / `STYLE-CONTEXT-01`;
- template-format-preservation or authoring-UX work outside this milestone.

These questions belong to architecture synthesis and Change Contract preparation, not evidence collection.

## 9. Exit decision

TABLE-LAYOUT-01A is complete.

The evidence is sufficient to proceed to **TABLE-LAYOUT-01B — architecture synthesis / Change Contract preparation**.

The next phase should derive the smallest compatible semantic surface from the evidence above, explicitly separate existing behavior from new behavior, and define implementation slices before production code is changed.

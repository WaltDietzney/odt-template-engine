# TABLE-ROW-01 — Native Writer Table Population Change Contract

**Status:** COMPLETE / FINAL GO  
**Completion:** bounded population implemented, automated preflight green, manual LibreOffice edit/save/reopen regression passed, canonical L12 sample added

**Status:** Accepted design baseline / implementation authorized after characterization tests  
**Milestone:** TABLE-ROW-01  
**Parent:** TEMPLATE-AUTHORING-01F / S03 prerequisite  
**Base:** `develop` after TABLE-ROW-01 characterization

## 1. Objective

Add one bounded native-table mutation capability required by S03:

> Populate an existing named Writer-authored table from application row data while preserving Writer-owned table structure and formatting.

The native `table:table` object survives. This is not whole-table replacement.

The capability must support practical professional templates without requiring the template author to rebuild table layout in PHP or mark one special prototype row.

## 2. Architectural ownership

### Writer/template owns

- `table:table` identity and name;
- table style and geometry;
- column structure and widths;
- native header rows;
- row/cell styles;
- paragraph/text formatting context;
- explicitly preserved ordinary rows;
- surrounding Section/layout context.

### Application owns

- the data supplied for mutable rows;
- resulting mutable-row count;
- mutable-row order;
- permitted cell payload values.

The engine owns deterministic reconciliation between those two.

## 3. Public target boundary

The capability belongs to the existing typed native table target family.

`TableTarget` remains the semantic entry point for one uniquely resolved native named table.

The implementation must remain additive and table-specific. It must not:

- create a generic mutable DOM target;
- add `replaceNamedElement()`;
- reinterpret `RichTable` as native-table mutation;
- route population through Section replacement;
- change classic foreach semantics.

Exact PHP naming may be refined during implementation review, but the intended public shape is a concise table population operation, conceptually:

```php
$template
    ->table('report.performance.results')
    ->populate($rows, [
        'keepRows' => [0, 4],
    ]);
```

`populate()` is the preferred working name because the native table object is preserved while its application-owned data region is populated. Renaming it requires an explicit review reason, not incidental implementation preference.

## 4. Input contract

The first slice accepts row-major application data.

Conceptually:

```php
$rows = [
    ['Participants enrolled', '240', '248', 'Achieved'],
    ['Program completion', '80%', '87%', 'Above target'],
];
```

Each application row maps left-to-right onto the logical cells of one mutable Writer row.

The first slice supports scalar/string-compatible cell payloads only.

It does not infer arbitrary PHP objects or nested arrays as ODF structures.

## 5. Header contract

Native rows contained by `table:table-header-rows` are header rows.

Default:

```text
keep native header rows and their content
```

Header rows do not consume application data rows and are not part of ordinary `keepRows` indexing.

A future explicit header-replacement option may be added only after separate semantics are required and characterized. It is not part of the minimum S03 prerequisite.

## 6. keepRows contract

`keepRows` preserves selected **ordinary source rows** completely, including their authored content.

Example:

```php
[
    'keepRows' => [0, 1, 5],
]
```

### Index semantics

Indices are zero-based positions in the ordinary, non-header source row sequence as it exists before the population operation begins.

They are source-structure indices, not positions in the mutating/result table.

Therefore row deletion/insertion during reconciliation must not change which authored rows are protected.

### Meaning

A kept row:

- remains in the table;
- retains its content;
- retains its styles/structure;
- consumes no application data item;
- is never used as disposable data content.

This supports Writer-authored title, explanatory, subtotal, footer, separator, or fixed-information rows without inventing new native row identities.

Invalid/out-of-range `keepRows` indices must fail deterministically rather than be ignored.

## 7. Mutable row contract

All ordinary source rows not listed in `keepRows` form the mutable row structure available to population.

Population must reconcile that structure with application data:

```text
data count == mutable row count
    refill

data count < mutable row count
    refill required rows
    remove surplus mutable rows

data count > mutable row count
    refill available rows
    clone reusable mutable row structure for additional data rows

data count == 0
    remove mutable rows
    preserve native headers and keepRows
```

At least one mutable source row is required when non-empty application data needs row structure. A table containing only preserved/header rows cannot silently invent a row design.

## 8. Row-template/reuse rule

TABLE-ROW-01 deliberately does **not** introduce a separately named prototype row.

For a simple homogeneous Writer table, existing mutable row structure is the row template.

For the first implementation:

- if mutable source rows are structurally compatible, their Writer-authored structure may be reused/cloned deterministically;
- if multiple mutable source rows have materially different structural shapes and the required growth behavior is ambiguous, population must reject the table with a typed/clear error.

A future pattern/repeating-style policy may broaden this behavior, but S03 must not require speculative row-pattern machinery.

## 9. Cell replacement contract

Population replaces the permitted payload inside an existing/cloned Writer cell while preserving the Writer-owned formatting context.

For the first slice, a supported simple cell has a stable paragraph/text insertion context.

The implementation must preserve where applicable:

- `table:style-name` on the cell;
- row style;
- paragraph style;
- text formatting structure that is outside the replaced scalar payload.

The operation must not replace a whole cell merely to change a scalar value when doing so would discard Writer-owned formatting.

## 10. Logical row/cell traversal

The implementation must not assume that all Writer rows live inside `table:table-rows`.

It must characterize and support at least:

- direct `table:table-row` children;
- rows inside `table:table-rows`;
- rows inside `table:table-header-rows`.

Header classification must survive these differences.

The existing `DocumentInspector::tableRows()` direct-row omission is a prerequisite defect to characterize and correct in a bounded way.

Inspection and mutation should share one coherent logical understanding of rows rather than diverging into incompatible counting models.

## 11. Validation before mutation

Population must validate the complete operation before destructive mutation.

At minimum validate:

- unique named target;
- target is in `content.xml`;
- valid `keepRows` indices;
- usable mutable source row exists when data is non-empty;
- application row widths match the supported logical cell count;
- mutable row topology is supported;
- no unsupported span/repetition/formula/resource/protection semantics are present.

Failure must leave the working table unchanged.

## 12. Atomicity

The operation is atomic at table-operation scope.

If validation or materialization fails:

- no partial row deletion remains;
- no partial cloned rows remain;
- no half-filled table remains.

Implementation may stage cloned/reconciled row content before replacing the live mutable region, or use an equivalent rollback-safe strategy.

Do not introduce a document-global transaction framework solely for TABLE-ROW-01.

## 13. First-slice unsupported topology

Reject, with a useful deterministic error, mutable rows involving semantics not yet contracted:

- merged/spanned cells;
- covered-cell topology requiring reconstruction;
- repeated mutable rows;
- repeated cells where logical rewrite is ambiguous;
- formulas/typed-value semantics beyond simple string payload;
- nested tables;
- frames/images or package resources in mutable payload;
- protected cells;
- incompatible heterogeneous mutable row shapes.

Native headers or kept rows may contain richer content if they remain completely untouched and do not make logical table traversal ambiguous.

## 14. Inspection correction

Before or with the bounded implementation, characterize and correct the existing inspection limitation for direct Writer rows.

After correction:

- `TableDescriptor::rowCount()` reports logical rows for supported direct/grouped Writer forms;
- `columnCount()` remains semantically meaningful for the first logical row under its documented rules;
- existing grouped-row fixtures remain compatible;
- no unrelated inspection semantics are changed.

This correction is part of TABLE-ROW-01 because population must not depend on a known incorrect row model.

## 15. Lifecycle contract

### inspectTemplate()

Remains immutable/source-oriented and describes the Writer-authored template contract.

Population does not rewrite source inspection evidence.

### inspect()

Observes the current working DOM after population and therefore reflects the current logical row count.

### Repeated population

Calling population again on the same working document must reconcile the table to the new dataset. It must not append another independent copy of previously populated rows.

The implementation must preserve enough operation-local/source structural knowledge to do this deterministically without creating a second global mutable document model.

### load()/refresh()

Existing document lifecycle semantics remain authoritative. No stale DOM node may be retained as the public table target's identity.

### save/reopen

A populated document must remain valid/editable in LibreOffice. Semantic structure and appearance are the regression oracle; raw XML byte identity is not required.

## 16. Section interaction

A named table may be contained in a named Section.

TABLE-ROW-01 does not alter Section ownership semantics.

Population of a table inside an existing Section is allowed when the table resolves uniquely in the current working document.

If Section clone/instantiation has rewritten nested table identities, population must target the resulting working identity explicitly and preserve strict ambiguity handling.

TABLE-ROW-01 does not add source-to-clone provenance APIs.

## 17. RichTable relationship

`RichTable` remains the API for PHP-owned/generated tables.

Native table population is a different ownership model:

```text
RichTable
    PHP owns table structure and generation

TableTarget population
    Writer owns table structure
    PHP owns bounded row data
```

The implementation must not serialize a new `RichTable` and swap it into the Writer document as a shortcut.

## 18. Mapping/automation relationship

Phase E correctly left table `populate` at RESEARCH NEEDED.

TABLE-ROW-01 establishes the missing native semantics.

Adding table population to declarative mapping/automation is **not automatically part of this implementation slice**. First prove the direct typed-target operation. Automation integration may follow as a bounded S03 slice once capability/error/atomicity semantics are stable.

## 19. Compatibility

Required compatibility:

- existing `OdtTemplate::table()` resolution remains valid;
- existing `TableTarget::descriptor()` remains valid;
- existing public `RichTable` behavior remains unchanged;
- existing Section/bookmark/frame APIs remain unchanged;
- classic foreach behavior/render order remains unchanged;
- no protected compatibility facade is removed;
- existing grouped Writer-table inspection remains green;
- no sample output artifacts are modified unless explicitly required by a later sample task.

## 20. Characterization and implementation slices

### Slice 0 — characterization gate

Add focused tests for:

- direct Writer rows;
- grouped body rows;
- native header rows;
- `keepRows` source-index interpretation;
- simple scalar payload preservation of styles;
- grow/shrink/zero cases;
- repeated operation lifecycle;
- unsupported topology rejection.

No broad refactor.

### Slice 1 — logical table structure

Introduce the smallest internal collaborator/model needed to enumerate logical header/body rows and supported cells consistently for inspection and mutation.

Do not expose DOM nodes publicly.

Correct direct-row inspection as part of this bounded model.

### Slice 2 — bounded population mutation

Implement validation, atomic reconciliation, cell payload replacement, row removal and row cloning.

Expose the bounded typed-target operation.

### Slice 3 — lifecycle / LibreOffice regression

Verify:

- repeated population;
- save;
- reopen;
- save again;
- `inspect()`;
- `inspectTemplate()`;
- representative Section containment.

### Slice 4 — S03 integration decision

Use the capability in the Writer-authored S03 template.

Only then decide whether Phase-E mapping/automation needs a table-populate action for the showcase.

## 21. Tests and preflight

At completion run at least:

- focused TABLE-ROW-01 tests;
- relevant addressability/inspection tests;
- relevant Section lifecycle tests;
- PublicSampleSmokeTest;
- full `composer test`;
- PHP lint for `src/` and `tests/`;
- `git diff --check`;
- documentation build when available;
- ODT ZIP/XML integrity;
- manual LibreOffice visual regression and save/reopen for the Writer-authored table fixture.

## 22. Explicit non-goals

TABLE-ROW-01 does not implement:

- whole-table replacement;
- universal named-element mutation;
- generalized cell addressability;
- arbitrary row markers/bookmark-as-row identity;
- native table foreach syntax;
- formulas/recalculation;
- merged-cell mutation;
- nested-table mutation;
- image/frame replacement inside mutable cells;
- generalized rich-cell subtree replacement;
- global style redesign;
- `RichTable` redesign;
- CLASSIC-FOREACH-SCOPE-01;
- SectionElement;
- general NAMED-OBJECT-OPERATIONS-01.

## 23. S03 consequence

S03 may now use two complementary native ownership levels:

### Output A — same report, different results

- preserve Writer report Sections;
- preserve named Writer tables;
- populate their bounded data rows;
- preserve native headers and selected fixed rows;
- update other supported fields/bookmarks/frames.

### Output B — substantially different report

- use Section replacement/instantiation/removal for larger semantic report changes;
- use table population only where a Writer-designed table survives but its data changes.

This preserves the architectural principle:

> Writer designs the document. The engine supplies and restructures only the application-owned content explicitly covered by a native capability contract.

## 24. Acceptance criteria

TABLE-ROW-01 is complete when:

1. direct and grouped Writer rows are inspected correctly;
2. one named content table can be populated from row-major scalar data;
3. native header rows remain unchanged by default;
4. `keepRows` preserves selected ordinary source rows and content;
5. body rows grow, shrink, and reach zero deterministically;
6. table identity, geometry and Writer-owned styles survive;
7. unsupported topology fails before destructive mutation;
8. the operation is atomic;
9. repeated population does not append stale data;
10. `inspect()` reflects working state while `inspectTemplate()` remains source-oriented;
11. LibreOffice save/reopen remains valid and visually stable;
12. full project preflight remains green.

TABLE-ROW-01 is complete. S03 implementation may proceed.

The accepted scalar payload boundary is deliberately narrow: a mutable cell has one simple Writer paragraph with one unambiguous scalar carrier. Direct paragraph text, an empty paragraph, one simple styled `text:span`, or an empty single styled span are supported while preserving the existing formatting carrier. Multiple/fragmented formatted text runs are rejected atomically rather than assigning the replacement value to an arbitrary run.

Repeated `populate()` calls, including calls that change `keepRows`, resolve keep-row indices against the immutable original Writer/source ordinary-row sequence.

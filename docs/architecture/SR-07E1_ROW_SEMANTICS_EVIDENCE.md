# SR-07E1 — Row Semantics Evidence

**Status:** EVIDENCE GATE / IMPLEMENTATION NOT YET AUTHORIZED
**Base commit:** `5e907ba379dcea4f6d2ed6d724b1c3de2ea02842`
**Branch:** `architecture/sr-07e1-row-semantics-evidence`

## 1. Question and scope

This slice determines whether the dormant `RichTable::addRow(array $cells,
array $style = [])` surface has an evidence-based, conservative contract for
SR-07E2. No production code or row producer was changed.

The findings below distinguish current repository behavior, native ODF/LibreOffice
evidence, and proposed architecture. Proposed behavior is not implemented.

## 2. Current repository behavior

`addRow()` stores the second argument in the row record as `style`, but
`RichTable::toDomNode()` currently ignores that value. It emits a
`table:table-row` with no `table:style-name`, and does not create a
`table-row` style definition. Rows without a style argument and rows with an
ignored non-empty style therefore have the same row-level XML structure.

The characterization tests in `TableRowSemanticsCharacterizationTest` freeze
this behavior. The existing SR-07 table suites continue to show no active row
producer.

## 3. Repository evidence

| Evidence | Finding |
| --- | --- |
| Non-empty `addRow(..., $style)` call sites | None found in `samples/` or `tests/`; all current calls use the first argument only, except the characterization call itself. |
| Public/table documentation | `docs/rich-documents/tables.md` lists explicit row-height control as a current limitation. It documents columns and ratios, not a row-style key contract. |
| `StyleMapper` | No `mapTableRowStyleOptions()` or equivalent row-specific mapper was found. |
| `StyleWriter` | No row registry or row-style finalization path was found. |
| Static row registry | None found. |
| HTML importer / `buildTableFromArray()` | No row-style data is passed to `addRow()`. Cell styling is separate. |
| Subclasses / compatibility | `addRow()` is public and chainable. The accepted style parameter is therefore compatibility-sensitive even though its rendering effect is currently dormant. |

The parameter name alone is not evidence for any particular key or native
property.

## 4. Native ODF and LibreOffice evidence

### 4.1 Existing LibreOffice-authored fixture

The repository's extracted `TABLE-02-formatted-cell.odt` is identified by the
semantic reference matrix as LibreOffice-authored. Its `content.xml` contains
an automatic, content-local row style:

```xml
<style:style style:name="Tabelle1.1" style:family="table-row">
  <style:table-row-properties style:min-row-height="0.318cm"/>
</style:style>
...
<table:table-row table:style-name="Tabelle1.1">
```

The style is under `office:automatic-styles` in `content.xml`. There is no
corresponding generated row style in `styles.xml`; `styles.xml` contains the
default row-family declaration with:

```xml
<style:default-style style:family="table-row">
  <style:table-row-properties fo:keep-together="auto"/>
</style:default-style>
```

This establishes the native family, property-group name, automatic scope,
and content-part ownership for an element-owned row style. It also shows
that `style:min-row-height` is a real emitted property.

### 4.2 Local LibreOffice execution

`/usr/bin/libreoffice` is present. A local conversion attempt was made under
`tmp/sr07e1/` with a private LibreOffice profile and a minimal HTML table
source. The headless process exited unsuccessfully because the environment
could not initialize its desktop/dconf runtime (`dconf-CRITICAL` and no ODT
output was produced). This is recorded as an environment limitation, not as
additional authored evidence.

The checked-in extracted `TABLE-02` fixture remains the authoritative
LibreOffice-authored evidence used here. No repository sample output was
modified.

### 4.3 Candidate native properties

The existing fixture proves `style:min-row-height`. The ODF property-group
name `style:table-row-properties` is also present in the fixture. The default
row style proves `fo:keep-together` is valid in that group, but repository/API
evidence does not establish that `keep-together` should be accepted as an
`addRow()` key. No authored fixture or current engine mapping establishes
`style:row-height`, pagination break properties, or a public row key.

## 5. Candidate mapping decisions

| API candidate | Native ODF property | Repository evidence | LibreOffice evidence | Decision | Reason |
| --- | --- | --- | --- | --- | --- |
| `min-row-height` | `style:min-row-height` | No current call site or mapper | `TABLE-02` emits it in `style:table-row-properties` | **DEFER** | Native validity is evidenced, but the dormant API has no established key contract. |
| `row-height` | `style:row-height` | No evidence | No fixture evidence | **DEFER** | Do not infer a public key from a likely ODF name. |
| `height` | `style:row-height` or another mapping | No evidence | No fixture evidence | **REJECT** | Ambiguous convenience key; would invent mapping semantics. |
| `keep-together` | `fo:keep-together` | No row mapper or call site | Valid in the default row property group | **DEFER** | ODF validity does not establish that the dormant API owns pagination semantics. |
| `break-before` / `break-after` | no established row mapping | No evidence | No authored row evidence | **REJECT** | Page-flow responsibility is not established as a row-style surface. |
| cell/paragraph/text keys | cell/paragraph/text properties | Existing split responsibilities | Not row properties | **REJECT** | These belong to nested content or cell semantic families. |

**Conclusion:** No first implementation key is currently defensible from the
repository/API evidence. SR-07E2 should not silently activate a broad DSL.
An explicit, reviewed API contract is required before implementation. If
architecture review elects to activate the surface, `min-row-height` is the
smallest native candidate to review first, but it remains **DEFER** here.

## 6. Scope and document-part decision

**Observed native evidence:** generated row style `Tabelle1.1` is family
`table-row`, automatic, in `content.xml` under `office:automatic-styles`, with
`style:table-row-properties`.

**E1 recommendation for E2:** if a narrowly mapped element-owned row style is
approved, use:

```text
family:       table-row
scope:        automatic
documentPart: content.xml
propertyGroup: style:table-row-properties
```

This confirms rather than contradicts the expectation in the finalized SR-07
contract. Common/authored definitions remain a separate compatibility concern.

## 7. Naming and structural reference

The current row storage has no row identity or style-name field, and no row
style naming convention exists elsewhere in the repository. A future E2
implementation should therefore choose the smallest deterministic
table-local convention derived from row position, for example a table-owned
name such as `ro0`, only after review accepts that convention.

Alternatives are an explicit caller-provided name in the row style contract,
or a deterministic name derived from the table's existing generated name and
row index. A global counter, static registry, hash allocator, and row-only
state object are not recommended. The choice remains open because no current
API evidence selects among these options.

## 8. Rows without style

Rows without style input must remain unchanged: no `table:style-name`, no
semantic `table-row` requirement, and no generated row definition. A styled
row must not cause default row requirements to appear on unstyled rows.

## 9. Compatibility implications

`addRow()` is public and chainable; the dormant second parameter must remain
accepted. `RichTable` subclasses could override it, although no repository
subclass was found. `StyleMapper`, `StyleWriter`, `RichTableCell`, and nested
paragraph/text responsibilities provide no row producer evidence. No legacy
row registry or protected row hook requires preservation beyond the public
method's current signature and behavior.

## 10. Proposed exact SR-07E2 contract

Before implementation review must explicitly accept all of the following:

1. the exact supported API key(s), starting with at most one native candidate;
2. the mapping to `style:table-row-properties` without reusing cell/paragraph
   mapping;
3. automatic/content-local scope and document-part;
4. a deterministic name/reference convention with no global state;
5. no requirement for rows without style input;
6. behavior for unsupported keys (reject, ignore, or compatibility error);
7. one XML integration test and one visual regression sample.

The current evidence gate recommends **no E2 producer implementation yet**.
It recommends contract review first because the repository does not define a
row key.

## 11. SR-07E2 focused test plan

After explicit contract GO, tests should cover the accepted key and native
attribute, semantic collection through `RichTable::ownedElements()` where
applicable, automatic/content-local materialization, row reference/name,
unchanged unstyled rows, unsupported keys, repeated semantic registration,
and compatibility of existing table/cell/paragraph content.

## 12. Visual regression plan

Use Sample 11 as the nearest existing combined table/table-column/table-cell
baseline after E2, with a minimal focused row-style fixture added only after
the contract is accepted. Sample 13 is a useful cell-style regression but is
not row-style evidence. If a new row-height behavior is activated, the new
fixture must compare the full rendered page against a known-good baseline.

## 13. Open questions and contradictions

- No current public row-style key is evidenced.
- `style:min-row-height` is native/LibreOffice-evidenced but not API-evidenced.
- Fixed row height and pagination controls were not authored by the current
  engine and could not be independently created by headless LibreOffice in
  this environment.
- The finalized contract expects automatic/content-local generated rows; the
  existing fixture confirms that expectation.
- Naming remains undecided because current row storage has no identity field.

No contradiction with the finalized SR-07 contract was found. The contract's
explicit semantics gate is active.

## 14. Final gate recommendation

**GO FOR CONTRACT REVIEW**

This is not authorization for SR-07E2 implementation. E2 remains blocked until
the exact row-property and naming decisions above receive explicit
architecture review.

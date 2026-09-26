# SR-07G — Compatibility and Lifecycle Closeout

## Status and scope

SR-07G narrows normal `OdtTemplate` table and table-cell compatibility
finalization. Semantic document-local ownership remains authoritative. This
slice does not begin SR-07H, D5F, or D5G work.

The intentional SR-07E behavior remains active: an
`addRow(..., ['min-row-height' => '...'])` value produces an automatic,
content-local `table-row` requirement with
`style:table-row-properties/style:min-row-height`. Unsupported row keys remain
ignored.

## Audit findings

`StyleMapper::$tableStyles` is written by `StyleMapper::registerTableStyle()`
and read by `getRegisteredTableStyles()` and `StyleWriter::writeAllStyles()`.
`StyleMapper::$tableCellStyles` is written by
`registerTableCellStyle()` and read by its getter and the same writer. The
registries remain process-global compatibility state; `load()` does not clear
them.

Before SR-07G, normal `save()` passed no table allow-list to
`StyleWriter::writeAllStyles()`, so every static table and table-cell entry
could be copied into the current `styles.xml`. Element-owned semantic cells
were separately excluded from common output, but unrelated static residue was
still eligible. `refresh()` likewise used the broad default writer path.

Direct `StyleWriter::writeAllStyles($dom)` remains broad by default. This is a
public compatibility surface and is intentionally not narrowed.

## Normal finalization pipeline

```text
setElement()
  -> collect/register semantic table, column, row, and cell requirements
  -> materialize semantic definitions
  -> insert structural table references
  -> save()/refresh()
  -> scan current table/table-cell structural references
  -> allow only referenced legacy names
  -> exclude semantic-owned automatic table-cell names
  -> StyleWriter compatibility finalization
```

The scan is document-local and read-only. It checks both current DOM parts and
recognizes only `table:table` references for table styles and
`table:table-cell` references for cell styles. It does not use names from
another family as adoption evidence.

## Compatibility boundary

`StyleWriter::writeAllStyles()` has two optional trailing allow-lists. Existing
callers using default arguments retain the historical broad behavior. Normal
`OdtTemplate` finalization supplies the current-document allow-lists unless the
legacy `assign()/render()` structured path has been used.

The legacy structured path remains broad because its existing behavior places
compatibility cell definitions in the styles DOM and historically relies on
the writer's static registries. No public or protected API was removed or
renamed.

## Table behavior

For normal semantic insertion, a registered table style is already adopted by
`RichTable` as a common `styles.xml` semantic definition. The allow-list also
ensures that a currently referenced legacy table style can be finalized, while
an unrelated static registration is omitted. Existing authored definitions
remain authoritative through the existing style-existence guard.

An unknown `setTableStyleName()` remains a reference only; no definition is
fabricated.

## Table-cell behavior

Element-owned `RichTableCell` definitions remain automatic/content-local and
are excluded from common legacy output. Static compatibility definitions are
eligible in normal finalization only when a current structural table-cell
reference exists. An unrelated static cell registration therefore does not
leak into a later normal document.

Direct static registration plus default `StyleWriter` invocation remains broad
and continues to materialize reusable common table-cell styles.

## Lifecycle observations

- Repeated normal `save()` remains idempotent for semantic table and cell
  definitions; existing style guards prevent multiplication.
- Normal `setElement()` no longer imports unrelated static table/table-cell
  residue into the output.
- `assign()/render()/save()` keeps its characterized legacy behavior,
  including its broad legacy writer path.
- `refresh()` applies the same reference filtering for normal semantic state;
  legacy structured state continues to use its compatibility behavior.
- `load()` still resets document-local semantic state but does not clear the
  static StyleMapper registries.
- No process-global current-document pointer or registry reset was introduced.

## Changed characterization behavior

The former normal-finalization leakage expectations are intentionally replaced:

| Case | Before SR-07G | After SR-07G | Reason |
| --- | --- | --- | --- |
| Unrelated static table style | Leaked into later normal document | Omitted | Ownership-driven current-document filtering |
| Unrelated static table-cell style | Leaked into later normal document | Omitted | Ownership-driven current-document filtering |
| Semantic element-owned table-cell style | Automatic/content plus possible residue | Automatic/content only | Preserve semantic authority |

Static registry visibility itself remains global and characterized. Direct
default `StyleWriter` behavior remains unchanged.

## Deferred concerns

The following remain outside SR-07G:

- static registry ownership redesign or reset policy;
- full `assign()/render()` and `refresh()` lifecycle redesign;
- protected-hook redesign;
- table naming/collision allocation;
- table-column, table-row, or table-cell semantic redesign;
- D5F/D5G lifecycle architecture;
- visual regression and final SR-07H approval.

## SR-07H visual targets

Run the full-document visual comparison for Sample 11, Sample 13, Sample 19,
and Sample 20, plus the focused `min-row-height` row-style proof from SR-07E2.
The visual gate remains open; SR-07G is intended to preserve layout while
removing unrelated legacy style definitions.

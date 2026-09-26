# ARCH-07G — Page-Layout Compatibility Resolution

## 1. Status

ARCH-07G resolves the historical PageLayout coupling that was unrelated to
page-layout semantics. `PageLayoutOdtTemplate` remains a thin public
convenience/compatibility facade over `OdtTemplate` and `PageLayoutManager`.

`AbstractOdtTemplate` and the remaining general state mirrors are intentionally
not removed in this slice.

## 2. Pre-change PageLayout structure

The public PageLayout API already had the intended delegation shape:

```text
PageLayoutOdtTemplate::setPageMargins()/setPageLayout()
        ↓
PageLayoutManager
        ↓
OdtDocumentContext::stylesDom()
```

However, the subclass also overrode `adjustBulletIndentation()`. That method
was called by inherited `OdtTemplate::save()` as a finalization hook and read
the historical `$domStyles` mirror directly. It was not a page-layout
operation.

## 3. Responsibility map

| Responsibility | Current owner before ARCH-07G | Actual domain owner | Compatibility relevance | ARCH-07G action |
|---|---|---|---|---|
| page margins | `PageLayoutOdtTemplate` facade | `PageLayoutManager` | public API and samples | retain facade, retain delegation |
| page size/orientation | `PageLayoutOdtTemplate` facade | `PageLayoutManager` | public API/tests | retain facade, retain delegation |
| locate master-page layout properties | `PageLayoutManager` | `PageLayoutManager` | package/document scoped | unchanged |
| list-label indentation finalization | `PageLayoutOdtTemplate` override | existing template finalization helper | protected save dispatch | remove unrelated subclass override; use existing base behavior |
| document DOM ownership | inherited mirrors | `OdtDocumentContext` | protected compatibility | unchanged |
| package persistence | `OdtTemplate`/`OdtPackage` | `OdtPackage` | public save behavior | unchanged |

## 4. Public facade semantics

`setPageMargins()` remains a convenience operation that builds the four
margin options and dispatches through `setPageLayout()`. Its `static` return
type and override-friendly call remain unchanged.

`setPageLayout()` remains the thin facade operation that constructs a
`PageLayoutManager` with the current `documentContext()` and delegates all
validation and XML mutation to it. No page-layout algorithm was copied into
the facade.

## 5. `adjustBulletIndentation()` findings

The override existed because earlier finalization code used broad XML
replacement that could affect unrelated `fo:margin-left` attributes, including
page margins. The PageLayout subclass supplied a narrower implementation that
targeted `style:list-level-label-alignment` nodes.

The base implementation in the current repository is already narrowed to the
same list-label nodes. It therefore provides the required list indentation
behavior without PageLayout-specific logic. The subclass override did not
augment the base behavior; it replaced it with the same responsibility and
the same resulting values.

Consequently, keeping the override would preserve implementation inheritance,
not a meaningful PageLayout variation point.

## 6. Production changes

`PageLayoutOdtTemplate` now contains only its page-layout convenience methods.
The following were removed from that class:

- the duplicate namespace constants used only by the override;
- the direct `$domStyles`-based `adjustBulletIndentation()` override.

No changes were made to `PageLayoutManager`, `OdtTemplate::save()`, or the
base finalization algorithm.

## 7. Resulting PageLayoutOdtTemplate role

The resulting structure is:

```text
PageLayoutOdtTemplate extends OdtTemplate
        ├── setPageMargins()
        └── setPageLayout()
                ↓
        PageLayoutManager
                ↓
        OdtDocumentContext
```

The inheritance now expresses a useful relationship: a PageLayout template is
an ordinary template with additional page-layout convenience operations. It
does not exist to obtain DOM access or to alter unrelated finalization.

## 8. State dependency

PageLayout-specific code no longer reads `$domStyles`, `$domContent`, or
`$domMeta` directly. `setPageLayout()` already uses the protected
`documentContext()` boundary through `PageLayoutManager`.

The general inherited finalization helpers still use compatibility mirrors.
That remaining dependency belongs to the later base-class/state resolution and
is not silently claimed as solved by ARCH-07G.

## 9. Protected compatibility implications

The protected `adjustBulletIndentation()` override identity on
`PageLayoutOdtTemplate` is intentionally removed. The observable save
behavior and list-label output remain provided by the existing base
finalization hook.

The dynamic dispatch seam itself remains: `save()` still calls
`$this->adjustBulletIndentation()`, so subclasses that intentionally override
the hook continue to be dispatched. ARCH-06C's probe remains valid because it
overrides the hook on its own test subclass and delegates to the inherited
implementation.

This is a deliberate structural pre-1.0 change to a historical subclass
override, not a public API change. No public PageLayout method was removed or
renamed.

## 10. Behavior compatibility

The following remain unchanged and are covered by existing tests:

- PageLayout construction;
- margin mutation;
- page width and height mutation;
- portrait/landscape orientation;
- `setPageMargins()` polymorphic dispatch;
- normal list finalization;
- `render()` and `save()` behavior;
- package persistence and ODT structure.

## 11. Style boundary

This slice does not introduce `StyleContext`, redesign `StyleMapper` or
`StyleWriter`, or change list-style APIs. It only removes duplicate
PageLayout-local finalization code whose domain was already outside PageLayout.

## 12. Tests and validation

No test needed structural adjustment. The existing PageLayout and finalization
tests distinguish observable XML behavior from test-subclass dispatch and both
remain green.

Focused validation covered PageLayout, finalization, ARCH-06C compatibility,
processing, structured insertion, API, lifecycle, and public sample suites.
The full suite is the required final gate.

## 13. Remaining blockers before `AbstractOdtTemplate` removal

The following remain outside ARCH-07G:

- inherited style/default/finalization helper ownership;
- remaining DOM/path compatibility mirrors;
- protected compatibility decisions for external subclasses;
- final `AbstractOdtTemplate` removal or bridge decision;
- future StyleContext and AssetContext work.

ARCH-07G leaves PageLayoutOdtTemplate ready to participate in those later
decisions as a thin facade rather than as a historical finalization subtype.

Semantics before implementation.

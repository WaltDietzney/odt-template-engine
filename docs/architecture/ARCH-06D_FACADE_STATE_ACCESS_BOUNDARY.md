# ARCH-06D Facade / State-Access Boundary

**Status:** Boundary review complete; documentation-only outcome
**Milestone:** ARCH-06 — Reassess `AbstractOdtTemplate`
**Branch:** `architecture/arch-06-abstract-template`

## 1. Ausgangsproblem

ARCH-06B defined the compatibility-base target for `AbstractOdtTemplate`:
keep the existing public/protected surface, do not add abstract methods, and
do not establish a second document-state owner. ARCH-06C then characterized
the protected dispatch and the relationship between historical template
properties and `OdtDocumentContext`.

ARCH-06D examined whether the existing code needs a small production change to
express that boundary more clearly.

The result is deliberately conservative:

> The existing `OdtTemplate::documentContext()` seam already expresses the
> authoritative document boundary for composed services. No safe, necessary
> production change was identified for this slice.

Changing direct base-class DOM access mechanically would either change a
protected compatibility surface or introduce an artificial dependency from
`AbstractOdtTemplate` to package state.

## 2. State-access inventory

### `AbstractOdtTemplate`

| Access | Classification | Finding |
| --- | --- | --- |
| `$domStyles` in image/style/default-style methods | A/C — compatibility-sensitive mirror access | Existing protected implementation directly mutates the styles DOM; subclasses may rely on the property. No replacement boundary exists in the base itself. |
| `$domContent` in list-style and structured insertion methods | A/C — compatibility-sensitive mirror access | Existing protected/public methods operate on the content DOM and are part of inherited behavior. |
| `$domContent` / `$domStyles` in `setValuesInDom()` and inspection | A/C — compatibility-sensitive mirror access | Active facade callbacks and two-region behavior are characterized by ARCH-06C. |
| `$templatePath`, `$tempDir` declarations | A — historical compatibility state | The base declares the properties but does not own package lifecycle. Their removal would affect external subclasses. |
| `$log`, `$debugMode` | A/D — compatibility state | Public debug behavior remains inherited; it is not document ownership. |
| package/context access | E — not available directly | The base has no package property and no `documentContext()` method. Adding one would require a new contract or duplicated state. |

### `OdtTemplate`

| Access | Classification | Finding |
| --- | --- | --- |
| `$package` construction/reset/save/cleanup | B — authoritative package access | Correctly owned by `OdtPackage` and retained in the concrete lifecycle facade. |
| `documentContext()` | B/C — authoritative service boundary and protected seam | Returns `$this->package->context()`. It is used by `MetadataManager`, `PageLayoutManager`, and compatibility probes. |
| `$templatePath`, `$tempDir` in `synchronizePackageState()` | D — lifecycle synchronization | Mirrors package values after construction and load. Required to keep inherited compatibility properties aligned. |
| `$domContent`, `$domStyles`, `$domMeta` in `synchronizePackageState()` | D — lifecycle synchronization | Mirrors the current context documents after construction and load. ARCH-06C proves object identity alignment. |
| `$domContent` / `$domStyles` in render and legacy operations | A/B — compatibility facade access | Public render continues to dispatch through protected methods using the historical mirrors. Replacing calls mechanically would risk protected overrides. |
| `$tempDir` in `setImage()` / `replaceImageByName()` | B/A — package-resource compatibility path | Existing image APIs use the workspace mirror and retain characterized behavior. Resource/lifecycle redesign is out of scope. |
| `$valueStack`, `$repeatStack` | B — concrete facade state | These are render-assignment state and do not belong in the base or document context. |

## 3. Authoritative ownership

The current ownership contract remains:

```text
OdtPackage
    package path, workspace, resources, manifest, persistence, cleanup

OdtDocumentContext
    content.xml, styles.xml, meta.xml DOMs

OdtTemplate
    valueStack, repeatStack, render orchestration and compatibility facade

AbstractOdtTemplate properties
    historical/protected compatibility mirrors and helpers
```

`OdtPackage` and `OdtDocumentContext` are the authoritative owners. The
template hierarchy must not create a second package, workspace, DOM registry or
resource state.

## 4. Compatibility mirrors

The following remain unchanged:

- `AbstractOdtTemplate::$templatePath`;
- `AbstractOdtTemplate::$tempDir`;
- `AbstractOdtTemplate::$domContent`;
- `AbstractOdtTemplate::$domStyles`;
- `OdtTemplate::$domMeta`;
- the redeclared compatibility properties on `OdtTemplate`;
- `synchronizePackageState()`.

ARCH-06C characterizes that:

- after construction, content/styles/meta mirrors reference the context DOMs;
- render mutates those same instances;
- `load()` replaces the context documents and resynchronizes the mirrors.

These properties are not promoted to new public API. They remain protected
compatibility surface until a separate migration decision exists.

## 5. `documentContext()` boundary

Current definition:

```php
protected function documentContext(): OdtDocumentContext
{
    return $this->package->context();
}
```

This method is defined on `OdtTemplate`, is protected, and is not overridden
in the repository. It is used by:

- `MetadataManager` construction in `setMeta()` and `getMeta()`;
- `PageLayoutManager` construction in `PageLayoutOdtTemplate`;
- ARCH-06C state probes.

It always returns the current package-owned context, including after
`load()`/`refresh()` because those paths reset/synchronize the package before
subsequent service access.

The method is already the correct narrow service-access seam. ARCH-06D does
not duplicate it in `AbstractOdtTemplate`, make it public, or turn it into a
new abstract method.

## 6. Concretely changed accesses

None.

No production access was safely movable without one of the following risks:

- bypassing a protected facade method or subclass override;
- changing the meaning of inherited protected DOM properties;
- forcing `AbstractOdtTemplate` to own or know about `OdtPackage`;
- introducing a second context accessor with competing semantics;
- changing style, image, text-box or legacy lifecycle behavior.

This is an intentional documentation-only outcome, not an incomplete
mechanical refactor.

## 7. Deliberately unchanged accesses

### Base-class DOM operations

The style, default-style, structured-insertion and template-helper methods in
`AbstractOdtTemplate` continue to use `$domContent` and `$domStyles`. These are
protected compatibility paths. ARCH-06C proves the mirrors remain aligned
with the authoritative context, so replacing them wholesale would add risk
without clarifying ownership.

### OdtTemplate render operations

`render()` continues to call protected methods such as:

- `fixBrokenVariables()`;
- `replaceNl2brInDom()`;
- `replaceListsInDom()`;
- `setValuesInDom()`;
- `renderTextBoxes()`;
- `applyRepeatingInDom()`;
- `applyConditionalsInDom()`.

The direct mirror arguments are retained to preserve dynamic dispatch and
existing two-DOM ordering.

### Package and resource operations

`OdtTemplate` continues to use `OdtPackage` for reset, persistence, image
resources, manifest synchronization and cleanup. No package access was moved
into the base and no resource API was redesigned.

### Style operations

`StyleMapper`, `StyleWriter`, automatic styles, paragraph/text/list/image
styles and finalization remain unchanged. `STYLE-CONTEXT-01` remains the
appropriate future boundary for style ownership.

## 8. Protected polymorphism

ARCH-06C remains the compatibility gate. The following dispatch paths remain
intact:

```text
public render()
    → protected fixBrokenVariables()
    → protected setValuesInDom()

public setElement()
    → protected replacePlaceholderWithDom()

public save()
    → protected injectImageStyles()
    → protected adjustBulletIndentation()

PageLayoutOdtTemplate public layout/save flows
    → protected documentContext()/adjustBulletIndentation()
```

Existing condition, evaluator, foreach and row-replacement seams remain
protected and untouched. No public operation was rerouted directly to a
service in ARCH-06D.

## 9. Lifecycle implications

No lifecycle behavior changed.

The existing sequence remains:

```text
construct
    → OdtPackage + OdtDocumentContext
    → synchronizePackageState()
    → prepare template

render
    → mutate current mirrored/context DOMs through protected facade hooks

save
    → finalize styles
    → OdtPackage::saveAs()

load / refresh
    → package reset/persist behavior
    → synchronizePackageState()
```

ARCH-06C and the existing lifecycle suites cover construction, render, save,
repeated save, load and refresh behavior.

## 10. Remaining debt

- Protected DOM/path mirrors remain duplicated declarations on base and
  concrete facade.
- `documentContext()` is a concrete-facade seam rather than a base contract.
- `PageLayoutOdtTemplate` retains historical bullet-indentation coupling.
- Style logic remains distributed across the base, `StyleMapper`, `StyleWriter`
  and element paths.
- `ensureTableCellStyleNodesExist()` remains a likely stale/buggy,
  repository-unused compatibility path.
- External subclass usage is unknown.

These are documented debt items, not ARCH-06D fixes.

## 11. Implications for ARCH-06E

ARCH-06E should not automatically extract another responsibility merely to
produce a code change. If implementation proceeds, the next candidate should
be exactly one bounded, independently characterized responsibility that does
not require changing mirror ownership or protected dispatch.

The strongest current candidate is a narrowly scoped compatibility/document
helper boundary only if a concrete repeated consumer is identified. Broad
style extraction is not a suitable ARCH-06E follow-up; it belongs to
`STYLE-CONTEXT-01` / `STYLE-API-02` planning.

Before ARCH-06E, the project should decide whether the absence of a safe
state-access code change is itself the desired stable boundary. No production
refactoring is authorized by this document.

## 12. Explicit non-goals

ARCH-06D does not:

- remove or deprecate `AbstractOdtTemplate`;
- add abstract methods or public APIs;
- remove compatibility mirrors;
- add a new service or context object;
- redesign package lifecycle or assets;
- extract styles or implement `STYLE-CONTEXT-01`;
- repair or remove `ensureTableCellStyleNodesExist()`;
- redesign `TemplateProcessor`, structured materialization or target resolution;
- implement named-object operations, cloning, removal or template instances;
- address format preservation or authoring UX.

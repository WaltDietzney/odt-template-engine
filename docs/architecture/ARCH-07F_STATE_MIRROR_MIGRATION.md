# ARCH-07F — State-Mirror Migration

## 1. Status

ARCH-07F migrates internal facade access from historical state mirrors to the
authoritative package/document owners where this can be done without changing
the remaining compatibility surface.

The slice deliberately does not remove the mirrors. The inherited style,
finalization, and PageLayout implementation still reads them, so removal would
couple ARCH-07F to the later base-class and PageLayout resolution slices.

## 2. Pre-change state inventory

| State | Current mirror | Authoritative owner | Readers before ARCH-07F | Writers | Compatibility relevance | ARCH-07F action |
|---|---|---|---|---|---|---|
| `domContent` | `AbstractOdtTemplate::$domContent`, redeclared by `OdtTemplate` | `OdtDocumentContext::contentDom()` | Most inherited XML/style helpers; `OdtTemplate` render, structured/image paths; `PageLayoutOdtTemplate` does not read it directly | `synchronizePackageState()` after package construction/load; DOM mutations through facade/helpers | High: protected subclasses and ARCH-06C probes access it | Migrate direct `OdtTemplate` reads to context; retain mirror for inherited compatibility |
| `domStyles` | `AbstractOdtTemplate::$domStyles`, redeclared by `OdtTemplate` | `OdtDocumentContext::stylesDom()` | Most inherited style/finalization helpers; `OdtTemplate` render, structured/image paths; `PageLayoutOdtTemplate::adjustBulletIndentation()` | `synchronizePackageState()` after package construction/load; DOM mutations through facade/helpers | High: protected subclasses and PageLayout behavior | Migrate direct `OdtTemplate` reads to context; retain mirror for inherited/PageLayout compatibility |
| `domMeta` | `OdtTemplate::$domMeta` | `OdtDocumentContext::metaDom()` | No direct production consumer found; ARCH-06C state probe reads it | `synchronizePackageState()` after construction/load | Medium: protected compatibility and state identity characterization | Retain synchronized mirror; no internal access remains to migrate |
| `templatePath` | `AbstractOdtTemplate::$templatePath`, redeclared by `OdtTemplate` | `OdtPackage::templatePath()` | `AbstractOdtTemplate::loadXmlFile()` can read it indirectly only through workspace-related legacy code; no repository call site found | `synchronizePackageState()` | Protected external-subclass risk | Retain synchronized mirror; no lifecycle redesign |
| `tempDir` | `AbstractOdtTemplate::$tempDir`, redeclared by `OdtTemplate` | `OdtPackage::workspacePath()` / `OdtPackage::path()` | inherited `loadXmlFile()`; direct `OdtTemplate` image/path operations before this slice | `synchronizePackageState()` | Protected external-subclass risk; inherited legacy helper | Migrate direct facade path access to `OdtPackage::path()`; retain mirror for inherited compatibility |

The authoritative objects were already present before ARCH-07F. The problem
was not that the mirrors contained different DOM objects in normal lifecycle
operation; ARCH-06C characterized their identity with the context. The
problem was that facade code continued to express the mirror as the owner.

## 3. Authoritative owners

Ownership remains binding:

```text
OdtPackage
    source template, workspace, package resources, manifest, persistence

OdtDocumentContext
    content.xml, styles.xml, and meta.xml DOM state

OdtTemplate
    assignment/render-session state and facade orchestration
```

`OdtPackage` exposes the document context and package path boundary. No new
context, proxy, magic property, or duplicate mutable state was introduced.

## 4. Internal access migrations

The following `OdtTemplate` operations now obtain document state from
`documentContext()`:

- template normalization and default content-list preparation;
- structured element style-node and materializer coordination;
- repeating-data processing;
- image placeholder and named-image DOM mutation;
- `render()` processing of content and styles;
- direct style writing in `save()` and `refresh()`.

Image workspace access now uses `OdtPackage::path('Pictures')`, and inherited
XML loading uses `OdtPackage::path()` as its package-backed path boundary when
called by the concrete facade.

The migrated code does not copy a DOM from the context. It passes the context's
current `DOMDocument` directly to existing collaborators and protected hooks.

## 5. `synchronizePackageState()`

### Before

Construction and `load()` obtained package/context values and assigned all
five historical mirrors:

```text
OdtPackage/context
        ↓
synchronizePackageState()
        ↓
templatePath, tempDir, domContent, domStyles, domMeta
```

Facade and inherited methods then commonly read the mirrors.

### After

The lifecycle synchronization remains intentionally in place because valid
compatibility consumers still read the mirrors:

```text
construction/load
        ↓
OdtPackage/context
        ├── authoritative facade access via documentContext()/package
        └── synchronized compatibility mirrors
```

It still runs after construction and after `load()`'s
`resetFromTemplate()`. `refresh()` retains its existing persist-then-load
behavior. No lifecycle transition was reordered or removed.

Removing this method now would leave inherited and PageLayout consumers with
stale/uninitialized compatibility state.

## 6. Compatibility mirrors

All five properties remain protected and synchronized. They are no longer the
preferred internal access path of `OdtTemplate`, but they remain a deliberate
temporary compatibility layer:

- `domContent` and `domStyles` are still needed by inherited style,
  finalization, and legacy helpers, and by `PageLayoutOdtTemplate`.
- `domMeta` has no active direct production consumer, but its protected state
  identity is characterized and removal belongs with the broader base-class
  compatibility decision.
- `templatePath` and `tempDir` are historical protected properties. Their
  package equivalents are authoritative, but inherited legacy access and
  external subclassing make removal inappropriate in this slice.

No mirror is written independently. The only lifecycle writer remains
`synchronizePackageState()`, and normal DOM mutations operate on the same
context objects referenced by the mirrors.

## 7. Lifecycle preservation

The following behavior remains unchanged and is covered by the existing
characterization/lifecycle tests:

- construction extracts the package and prepares the current context;
- `render()` mutates the current context DOMs;
- `save()` persists the current package state;
- repeated render/save behavior remains unchanged;
- `load()` restores the original template and resynchronizes mirrors;
- `refresh()` persists core documents and then performs the existing load
  reset behavior;
- content, styles, and metadata remain coherent through the context.

## 8. Protected compatibility impact

No protected property was removed, renamed, or made less visible. Existing
ARCH-06C subclasses can still inspect the mirrors and observe identity with
`documentContext()`.

This slice reduces internal dependence on the mirrors without attempting to
solve external protected-subclass compatibility. Property removal remains a
later, explicitly documented decision after inherited helpers and PageLayout
are resolved.

## 9. PageLayout dependencies

`PageLayoutOdtTemplate::adjustBulletIndentation()` still reads
`$this->domStyles`. It is intentionally classified as a later PageLayout
migration dependency. ARCH-07F does not change its inheritance, dispatch, or
style behavior.

## 10. Tests and validation

No new characterization test was required. Existing ARCH-06C state identity
tests and lifecycle/API/processing/structured/PageLayout tests cover the
observable contracts needed for this bounded access migration.

The full suite remains the required gate. Package/XML and visual checks remain
required because lifecycle and DOM access can affect persisted output, even
though this slice introduces no intended document semantics change.

## 11. Remaining state debt before `AbstractOdtTemplate` removal

The following work remains:

- migrate or relocate inherited style/default/finalization helpers that still
  read `domContent`/`domStyles`;
- resolve the PageLayout mirror dependency;
- decide the protected compatibility policy for `domMeta`, `templatePath`, and
  `tempDir`;
- remove or bridge the remaining base-class state only after its consumers are
  handled;
- preserve package/context ownership while resolving those compatibility
  decisions.

ARCH-07F therefore makes internal ownership clearer without claiming that the
historical base-class state has already disappeared.

Semantics before implementation.

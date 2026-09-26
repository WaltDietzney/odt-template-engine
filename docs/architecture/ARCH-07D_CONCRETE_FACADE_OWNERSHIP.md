# ARCH-07D — Concrete Facade Ownership

## 1. Status

ARCH-07D is the first production-code slice of ARCH-07. It moves one bounded
public facade responsibility without removing `AbstractOdtTemplate`, changing
state ownership, or redesigning styles and assets.

The migrated responsibility is `OdtTemplate::setElement()`.

## 2. Starting point

Before ARCH-07D, `setElement()` was declared on `AbstractOdtTemplate` even
though it was an application-facing operation used through `OdtTemplate`.
The method coordinated several existing collaborators and compatibility hooks:

```text
OdtTemplate public API
        ↓ inherited implementation
AbstractOdtTemplate::setElement()
        ├── style compatibility helpers / StyleMapper
        ├── package resource callback
        ├── protected placeholder callbacks
        └── StructuredElementMaterializer
```

This was implementation inheritance, not an abstract-template contract.

## 3. Responsibility migrated

`setElement()` is now declared directly by `OdtTemplate`.

This is a genuine ownership change because the public operation is no longer
inherited from the semantically misleading abstract base. Its implementation
remains intentionally narrow and continues to use the existing lower-level
owners.

The facade coordinates:

1. collecting required element style definitions;
2. invoking existing style compatibility helpers;
3. copying structured image resources through the package-backed callback;
4. adding an optional style DOM node;
5. invoking `StructuredElementMaterializer` for content and styles DOM paths;
6. passing callbacks through `$this` so protected dynamic dispatch remains
   observable.

The facade does not become the owner of ZIP persistence, manifest lifecycle,
DOM state, style registries, or materialization semantics.

## 4. Call-chain comparison

### Before ARCH-07D

```text
caller
  → OdtTemplate::setElement() [inherited]
  → AbstractOdtTemplate::setElement()
  → inherited style/resource preparation
  → StructuredElementMaterializer::insert()
  → protected facade/base callbacks
```

### After ARCH-07D

```text
caller
  → OdtTemplate::setElement()
  → existing style/resource compatibility helpers
  → StructuredElementMaterializer::insert()
  → protected callbacks through $this
```

The materializer and all existing callback paths remain in place. No new
document context or resource registry was introduced.

## 5. Compatibility bridge and base-class role

For normal library usage, `OdtTemplate` now owns the public `setElement()`
operation directly. `AbstractOdtTemplate` remains present for its other
inherited public/protected compatibility surface.

The base class still contains:

- protected DOM and path mirrors;
- scalar/structured replacement support;
- placeholder callbacks;
- style/default-style helpers;
- finalization helpers;
- inspection and debugging behavior;
- legacy compatibility paths.

`setElement()` itself is not duplicated as a broad compatibility implementation
in the base. Direct external subclasses of `AbstractOdtTemplate` that relied
on inheriting `setElement()` are a documented pre-1.0 compatibility risk and
require an explicit migration decision in a later slice. No evidence of such a
repository-internal subclass exists.

This slice therefore establishes real facade ownership without pretending that
the entire base has already been removed.

## 6. Protected dispatch preservation

The moved implementation deliberately calls protected methods through `$this`:

- `registerStyles()`;
- `ensureTextStylesExist()`;
- `ensureParagraphStylesExist()`;
- `ensureTableCellStyleNodesExist()`;
- `copyImageResource()`;
- `fixBrokenVariables()`;
- `replacePlaceholderWithDom()`;
- `hasPlaceholder()`.

`StructuredElementMaterializer` continues to receive callbacks, rather than
calling the inherited implementation directly. This preserves the
ARCH-06-characterized dispatch behavior for structured placeholder replacement
and normalization.

No protected hook was removed, privatized, or bypassed in this slice.

## 7. State ownership

Ownership remains unchanged:

```text
OdtPackage
    package/workspace/resources/manifest/persistence

OdtDocumentContext
    content.xml/styles.xml/meta.xml DOMs

OdtTemplate
    assignment/render-session state and public facade orchestration
```

`setElement()` continues to use the existing synchronized compatibility DOM
properties. `domContent`, `domStyles`, `domMeta`, `templatePath`, and `tempDir`
were not removed or redefined. No second source of truth was added.

## 8. Style and resource boundaries

The slice preserves existing style behavior and treats style preparation as an
existing compatibility dependency:

- no `StyleContext` was introduced;
- `StyleMapper` static state was not redesigned;
- `StyleWriter` was not changed;
- style registration ordering was preserved;
- `ensureTableCellStyleNodesExist()` was not repaired or removed.

Structured image resources continue to use the package-backed
`copyImageResource()` path. `setElement()` does not call
`replaceImageByName()`, and named image replacement remains the separate typed
target-resolution path.

## 9. Deliberately not migrated

ARCH-07D does not change:

- `AbstractOdtTemplate` removal or renaming;
- `PageLayoutOdtTemplate` inheritance or `adjustBulletIndentation()`;
- DOM/path mirror ownership or visibility;
- template processing or normalization extraction;
- package lifecycle or save/load/refresh behavior;
- named image replacement semantics;
- `TemplateProcessor`;
- `TemplateTargetResolver`;
- `MetadataManager` or `PageLayoutManager`;
- style architecture or static style state;
- asset architecture;
- legacy helper disposition;
- public API names or signatures.

## 10. Test evidence

No new test was required. ARCH-07C established that the existing
characterization net was sufficient for this bounded migration.

Relevant focused suites passed:

- ARCH-06 compatibility characterization: 4 tests, 21 assertions;
- ARCH-05 structured insertion/image suites: included in 21-test focused run,
  173 assertions;
- API, integration, lifecycle, PageLayout, and public sample suites: included
  in 24-test focused run, 351 assertions.

The complete suite passed:

- 105 tests;
- 853 assertions.

The tests cover structured insertion, `setElement()` behavior in content and
styles paths, structured values, image resources, inline/block replacement,
text boxes, protected callback dispatch, package lifecycle, styles, and public
samples.

## 11. Package and XML validation

A temporary structured-insertion output was generated outside the repository.
Validation confirmed:

- `unzip -t` passed for the ODT package;
- `content.xml` was well formed and contained the inserted structured text;
- `styles.xml` was well formed;
- `META-INF/manifest.xml` was well formed.

No generated output under `samples/output/` was overwritten.

## 12. Visual regression

The existing `samples/output/` artifacts were copied to `/tmp` for a focused
LibreOffice conversion attempt covering Samples 09, 16, and 21. LibreOffice
exited with status 1 without producing PDFs, including when an isolated
temporary user profile was supplied. The environment emitted `javaldx` and
`dconf` warnings; this is an execution-environment failure, not evidence of a
visual result.

No baseline files were changed or regenerated. Because this slice only moves
the already-characterized `setElement()` implementation and all automated
ODT/XML checks passed, the visual check is recorded as blocked rather than
reported as successful.

## 13. Next architectural implication

ARCH-07D proves that a concrete public facade operation can be moved out of
`AbstractOdtTemplate` without changing its lower-level ownership boundaries.
The base remains a compatibility container, but new facade behavior should not
be added there.

The next contract-driven work must address the remaining bounded facade
dependencies and state access only after deciding which additional
characterization is needed. PageLayout, mirror removal, and final base-class
resolution remain separate concerns.

Semantics before implementation.

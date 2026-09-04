# SR-06F.1 Legacy Graphic Compatibility Audit

## Scope and evidence

This note records the current implementation on branch
`architecture/sr-06f-compatibility-closeout` at baseline
`b560df267d3556ef56501f0878f21ed9b6287b21`.

It is an evidence-gathering artifact for SR-06F.1. It does not remove legacy
code, approve the current mixed ownership model, or define the SR-06F.2/6F.3
implementation. The executable characterization is
`tests/Integration/StyleContextLegacyGraphicCompatibilityAuditTest.php`,
together with the existing SR-06 graphic, fill-image, resource, and lifecycle
tests.

## Current pipelines

### Normal `setElement()`

```text
OdtElement
  -> StyleRequirementCollector::collectSemantic()
  -> StyleContext::registerRequirement()
  -> FillImageRequirementCollector::collect()
  -> OdtDocumentContext fill-image registry / fill-image materializer
  -> StyleRequirementMaterializer for semantic definitions
  -> StyleRequirementCollector::collect()
       -> legacy paragraph/text/frame/image/fill-image arrays
       -> StyleContext legacy graphic state
       -> legacy paragraph/text ensure path where applicable
  -> StructuredElementMaterializer::insert()
       -> element::toDomNode()
  -> second legacy collection/registration in `setElement()`
  -> save(): document graphic injection, semantic font finalization,
     StyleWriter compatibility finalization, package persistence
```

The normal path is therefore not semantic-only for graphics. `DrawTextBox`
and `CircularImageElement` can expose semantic graphic requirements while the
post-materialization legacy collector still registers graphic/fill-image
compatibility state. `ImageElement` has no semantic style requirement and
continues through the legacy image channel.

### Legacy `assign()` / `render()`

```text
assign()/setValues()
  -> valueStack
  -> render()
  -> setValuesInDom(content.xml, valueStack)
       -> OdtElement::toDomNode()
       -> registerLegacyGraphicRequirements()
  -> setValuesInDom(styles.xml, valueStack)
       -> OdtElement::toDomNode()
       -> registerLegacyGraphicRequirements()
  -> legacyStructuredValuesMaterialized = true
  -> save()/refresh()
       -> injectImageStyles()
            -> injectLegacyImageStyles()
       -> injectDocumentGraphicStyles()
       -> StyleWriter::writeAllStyles(..., legacy flag)
```

This path does not call `collectSemantic()` or the semantic fill-image
collector. Its graphic compatibility state is registered through
`registerLegacyGraphicRequirements()` into `StyleMapper`'s legacy registries;
the document `StyleContext` graphic arrays remain unused by this path.

## Legacy channel inventory

| Channel / API | Definition or registration | Read/materialization point | Current classification |
|---|---|---|---|
| Legacy `frame` | `StyleRequirementCollector::collect()` reads `getOwnFrameStyleRequirements()`; `setElement()` registers `StyleContext::registerFrameStyle()` | `injectDocumentGraphicStyles()` writes the registered graphic style; legacy `StyleWriter` also supports `StyleMapper::getFrameStyles()` when the legacy structured flag is set | `REQUIRED_NORMAL` for current DrawTextBox compatibility; `REQUIRED_LEGACY` for assign/render; `COMPATIBILITY_FACADE` for the public/protected registration surface |
| Legacy `image` | `ImageElement::getOwnImageStyleRequirements()`; `registerLegacyGraphicRequirements()` calls `StyleMapper::registerImageStyle()`; normal `setElement()` collector registers `StyleContext::registerImageStyle()` | Normal path: `injectDocumentGraphicStyles()`; legacy path: `injectLegacyImageStyles()` and legacy registries | `REQUIRED_NORMAL` for ImageElement; `REQUIRED_LEGACY` for assign/render |
| Legacy `fill-image` | Post-`toDomNode()` collector reads `getOwnFillImageRequirements()`; `StyleContext::registerFillImage()`; assign/render calls `StyleMapper::registerFillImage()` | Normal path: `injectDocumentGraphicStyles()`; legacy path: `injectLegacyImageStyles()`; physical resource/manifest handling is separate | `REDUNDANT_NORMAL` as a second registration for CircularImageElement after semantic SR-06E registration, but `REQUIRED_LEGACY` for assign/render; public compatibility facade remains |
| `getFillImageRequirements()` | Historical recursive array API on `OdtElement`; CircularImageElement returns its own mutated requirement after `toDomNode()` | Legacy collector and private legacy registration helper | `COMPATIBILITY_FACADE`; timing-sensitive and not equivalent to typed semantic dependencies |
| `getOwnFillImageRequirements()` | Default empty hook; CircularImageElement returns its own legacy array after fill-name mutation | Legacy `StyleRequirementCollector::collect()` | `COMPATIBILITY_FACADE` |
| `CircularImageElement::$fillImageName` | Set in `toDomNode()` from `resolvedFillImageName()` | Enables subsequent `getFillImageRequirements()` / `getOwnFillImageRequirements()` calls | `REQUIRED_LEGACY` for assign/render collection; `REDUNDANT_NORMAL` for semantic discovery itself, but mutation remains observable compatibility state |
| `StyleContext::registerFrameStyle()` | Stores raw legacy frame definitions, name-keyed | `frameStyles()` and `injectDocumentGraphicStyles()` | `COMPATIBILITY_FACADE` and `REQUIRED_NORMAL` for current legacy carrier |
| `StyleContext::registerImageStyle()` | Stores raw legacy image definitions, name-keyed | `imageStyles()` and `injectDocumentGraphicStyles()` | `COMPATIBILITY_FACADE` and `REQUIRED_NORMAL` for ImageElement |
| `StyleContext::registerFillImage()` | Stores raw legacy fill-image definitions, name-keyed | `fillImages()` and `injectDocumentGraphicStyles()` | `COMPATIBILITY_FACADE`; redundant alongside the typed normal registry for CircularImageElement |
| `frameStyles()` / `imageStyles()` / `fillImages()` | Read-only accessors for raw document-local compatibility state | `injectDocumentGraphicStyles()` and tests/subclasses | `COMPATIBILITY_FACADE` |
| `registerLegacyGraphicRequirements()` | Reads legacy element methods after `toDomNode()` in assign/render; registers into global `StyleMapper` registries | `injectLegacyImageStyles()` and `StyleWriter` | `REQUIRED_LEGACY` |
| `injectImageStyles()` | Protected save hook; delegates to `injectLegacyImageStyles()` only when legacy structured values were materialized | `save()` | `COMPATIBILITY_FACADE`; protected override point |
| `injectLegacyImageStyles()` | Reads legacy static image/fill registries; writes graphic styles/fill-image declarations | `save()` via `injectImageStyles()` | `REQUIRED_LEGACY`; protected compatibility hook |
| `injectDocumentGraphicStyles()` | Reads `StyleContext` frame/image/fill arrays and writes styles DOM | `save()` and `refresh()` | `REQUIRED_NORMAL` for current document-local graphic state |
| `$legacyStructuredValuesMaterialized` | Set by `setValuesInDom()` for structured assign/render values; reset by `load()` | Selects legacy image injection and StyleWriter legacy frame inclusion | `REQUIRED_LEGACY` lifecycle discriminator |
| post-materialization legacy collector | Second `collect()` loop in `setElement()` after insertion; registers all legacy families | Immediate registration; consumed at save/refresh | `REQUIRED_NORMAL` for unmigrated compatibility families; `REDUNDANT_NORMAL` only for semantic CircularImage fill-image registration itself |
| `setValuesInDom()` | Calls `toDomNode()` and legacy registration for each DOM part | `render()` | `REQUIRED_LEGACY` |
| `save()` / `refresh()` | Save invokes both legacy/document graphic finalizers; refresh invokes document graphic finalizer and StyleWriter | Package persistence or reload | `REQUIRED_NORMAL` / `REQUIRED_LEGACY` lifecycle boundaries |

The classifications are per current path. A channel can consequently be
`REDUNDANT_NORMAL` for semantic CircularImage ownership while remaining
`REQUIRED_LEGACY` for `assign()/render()`.

## Producer dependency matrix

| Producer | Semantic channel before DOM | Legacy channel after DOM / render | Physical consumer today |
|---|---|---|---|
| `DrawTextBox` through `setElement()` | One semantic `graphic` `StyleRequirement` from `getOwnStyleRequirements()`; child semantic requirements are traversed | `frame` from `getOwnFrameStyleRequirements()`; nested children are collected by the legacy collector | Semantic graphic style is materialized by `StyleRequirementMaterializer`; legacy frame state is also registered and written through document graphic injection. Existing tests show both names can remain physically present for compatibility carriers. |
| `DrawTextBox` through `assign()/render()` | None; legacy path does not call semantic collection | `registerLegacyGraphicRequirements()` reads `getFrameStyleRequirements()` during each `setValuesInDom()` pass | `StyleWriter::writeAllStyles()` with legacy frame inclusion; `injectDocumentGraphicStyles()` is not the source for this path unless other state was registered. |
| `ImageElement` through `setElement()` | None in the current producer | `image` from `getOwnImageStyleRequirements()`; image asset collection is separate | `StyleContext::imageStyles()` -> `injectDocumentGraphicStyles()`; no semantic style definition owns it. |
| `ImageElement` through `assign()/render()` | None | `registerLegacyGraphicRequirements()` registers image style globally during both DOM passes | `injectLegacyImageStyles()` writes the graphic style; existing legacy tests protect this. |
| `CircularImageElement` through `setElement()` | Semantic graphic style plus typed `FillImageRequirement` before DOM rendering | After `toDomNode()`, legacy image and fill-image arrays become available; collector registers them into `StyleContext` | Semantic fill-image materialization and semantic graphic materialization occur, while legacy compatibility state is also present. Existing declaration checks avoid a second physical fill-image node. |
| `CircularImageElement` through `assign()/render()` | None | `toDomNode()` mutates `$fillImageName`; legacy image/fill arrays are then registered into static `StyleMapper` state | `injectLegacyImageStyles()` and StyleWriter compatibility path; existing legacy circular-image tests protect graphic/fill/resource output. |

## Lifecycle observations

- `setElement()` materializes semantic style/fill requirements eagerly, before
  structured DOM insertion. Its later legacy collection observes the element
  after `toDomNode()` and therefore sees CircularImageElement's mutated fill
  name and legacy image style.
- CircularImageElement's semantic fill requirement is available before DOM
  materialization, whereas `getFillImageRequirements()` is empty before
  `toDomNode()` and populated afterward. This makes the two APIs lifecycle
  distinct, not interchangeable.
- `assign()` only stores values. `render()` invokes `setValuesInDom()` once for
  each core DOM. The same element can therefore be rendered twice per call,
  and legacy registration is repeated; name-keyed legacy registries make this
  idempotent for equivalent definitions.
- `save()` calls `injectImageStyles()`, then `injectDocumentGraphicStyles()`,
  then StyleWriter. With `$legacyStructuredValuesMaterialized` true, the
  legacy image/fill path is active and StyleWriter receives the legacy frame
  flag. With `setElement()` only, the protected image hook is a no-op and the
  document-owned graphic injector consumes `StyleContext` state.
- `refresh()` does not call `injectImageStyles()`; it invokes document graphic
  injection and StyleWriter, persists core documents, and then calls `load()`.
  `load()` resets document-local semantic/style/fill-image state and clears
  `$legacyStructuredValuesMaterialized`. Legacy static StyleMapper state is a
  separate compatibility concern and is not reset by OdtTemplate construction
  or load.
- Existing tests characterize repeated render/save stability, load reset, and
  document isolation. No F.1 change alters those behaviors.

## Protected/public compatibility observations

The following public or protected surfaces are compatibility-sensitive and
must not be removed merely because a later semantic path makes part of their
normal behavior redundant:

- public `OdtTemplate::setElement()`, `assign()`, `setValues()`, `render()`,
  `save()`, `refresh()`, `load()`, `setImage()`, and `replaceImageByName()`;
- public `OdtElement::getFillImageRequirements()` and
  `getOwnFillImageRequirements()`;
- public `DrawTextBox::getFrameStyleRequirements()`,
  `getOwnFrameStyleRequirements()`, and `getStyleDefinitions()`;
- public ImageElement/CircularImageElement legacy requirement accessors;
- protected `setValuesInDom()`, `injectImageStyles()`,
  `injectLegacyImageStyles()`, `ensureTextStylesExist()`,
  `ensureParagraphStylesExist()`, `registerStyles()`, and related template
  hooks. `DocumentFinalizationArch03CTest` already demonstrates subclassing
  `injectImageStyles()`.

`StyleContext::registerFrameStyle()`, `registerImageStyle()`,
`registerFillImage()`, and their accessors are public and remain usable by
legacy/document-local callers. `StyleMapper` registration methods and
`LegacyStyleRegistry` are also compatibility surfaces. F.1 does not change
dispatch or visibility.

## Classification and proposed next boundaries

### `REQUIRED_NORMAL`

- semantic `graphic` style materialization for current DrawTextBox and
  CircularImageElement producers;
- document-local `StyleContext` frame/image/fill channels where current normal
  integration still consumes them;
- ImageElement's current legacy image style path, because ImageElement has not
  yet migrated to semantic graphic requirements;
- structured resource/package handling for image assets.

### `REQUIRED_LEGACY`

- `assign()/render()` structured values and their post-`toDomNode()` legacy
  registrations;
- `injectLegacyImageStyles()` and StyleWriter legacy frame inclusion;
- CircularImageElement's fill-name mutation and static legacy registrations for
  the legacy lifecycle.

### `COMPATIBILITY_FACADE`

- raw StyleContext graphic registration/accessor APIs;
- legacy element requirement accessors;
- protected finalization hooks and StyleMapper/LegacyStyleRegistry APIs.

### `REDUNDANT_NORMAL`

- the second normal-path registration of CircularImageElement's fill-image
  declaration into raw `StyleContext::fillImages()` after the typed semantic
  `FillImageRequirement` has already been registered and materialized. It is
  physically idempotent today, but it remains state-visible compatibility data.

### `UNKNOWN / NEEDS_FURTHER_CHARACTERIZATION`

- whether the normal DrawTextBox legacy frame carrier can be removed after all
  semantic graphic-style consumers and subclass overrides are audited;
- whether ImageElement should be migrated or remain a compatibility-only
  producer;
- whether legacy static StyleMapper state can be narrowed without changing
  direct callers or cross-document behavior;
- whether `refresh()` should eventually share exactly the same legacy
  finalization policy as `save()`.

### Proposed F.2 / F.3 boundaries

F.2 should define the narrow ownership cut for normal `setElement()` graphic
and fill-image channels. It should remove only redundant normal registration or
physical consumption after proving that frame/image/resource compatibility
channels remain available. It must preserve all public/protected facades.

F.3 should separately characterize or migrate the legacy `assign()/render()`
path, including the two-DOM `setValuesInDom()` lifecycle, static StyleMapper
registries, and StyleWriter's legacy finalization flag. It should not be
implicitly altered by F.2 cleanup.

## Explicit non-changes for SR-06F

SR-06F must not, based on this audit alone:

- remove or rename legacy requirement APIs, StyleMapper, LegacyStyleRegistry,
  or protected OdtTemplate hooks;
- disable `collect()` wholesale or replace the D5 ownership traversal;
- change `setImage()` or `replaceImageByName()` behavior;
- migrate table/table-cell, page, list, font, resource, or unrelated style
  families;
- fix CircularImageElement's known visual rendering defect;
- bulk-import global legacy state into document contexts;
- change save/render/refresh output or lifecycle semantics before a dedicated
  compatibility slice proves the change safe;
- touch `samples/output/*`, `tmp/`, or LibreOffice lock files.

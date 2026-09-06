# D5F-C — Lifecycle Orchestration Consolidation

Status: **IMPLEMENTATION RECORD — ORCHESTRATION ONLY**

Base: `architecture/d5f-change-contract` at `a576fc8`

## 1. Scope

D5F-C makes the normal `OdtTemplate::setElement()` lifecycle explicit without
changing lifecycle semantics, public APIs, compatibility ownership, or native
element rendering. The authoritative semantic/dependency/resource path is now
represented by private orchestration phases on `OdtTemplate`.

No new lifecycle service, context, DTO, or public API was introduced.

## 2. Previous setElement() shape

The previous method interleaved these responsibilities in one body:

```text
semantic requirement collection and registration
font discovery
fill-image collection and materialization
semantic style materialization
legacy paragraph/text compatibility
HasStyles compatibility registration
physical resource preparation
StructuredElementMaterializer insertion
legacy post-materialization collection
```

The behavior and ordering were already characterized by D5F-B. D5F-C keeps
that ordering while making the boundaries visible.

## 3. Current explicit phases

The normal path now reads conceptually:

```text
setElement()
  -> prepareStructuredSemanticState()
       -> collectSemantic() once
       -> register semantic requirements
       -> discover/register font-face requirements
       -> prepareStructuredFillImageDependencies()
       -> materializeStructuredSemanticStyles()
  -> registerStructuredLegacyParagraphTextCompatibility()
  -> registerStructuredHasStylesCompatibility()
  -> prepareStructuredResources()
  -> materializeStructuredElement()
       -> existing StructuredElementMaterializer
       -> existing protected facade callbacks
       -> element->toDomNode()
  -> finalizeStructuredCompatibility()
       -> existing second legacy collector pass
```

The extracted private methods are deliberately small:

- `prepareStructuredSemanticState()`;
- `prepareStructuredFillImageDependencies()`;
- `materializeStructuredSemanticStyles()`;
- `registerStructuredLegacyParagraphTextCompatibility()`;
- `registerStructuredHasStylesCompatibility()`;
- `prepareStructuredResources()`;
- `materializeStructuredElement()`;
- `finalizeStructuredCompatibility()`.

The semantic collector result is retained locally for semantic ownership
classification. The legacy collector remains a separate compatibility
projection and is still collected in both its pre- and post-materialization
positions.

## 4. Responsibility boundaries

Semantic style requirements remain owned by `StyleContext` through the
existing `StyleRequirementCollector::collectSemantic()` path. Font and typed
fill-image dependencies remain document-local through `OdtDocumentContext`.
Physical image assets remain package concerns and are prepared through
`OdtPackage::copyImageResourcesAtomically()`.

`StructuredElementMaterializer` was not broadened. It still owns only
placeholder normalization, native `toDomNode()` insertion, and replacement
through the callbacks supplied by `OdtTemplate`.

## 5. Compatibility deliberately retained

D5F-C does not remove or reinterpret:

- the legacy `StyleRequirementCollector::collect()` projection;
- legacy paragraph/text registration and existence checks;
- `HasStyles::getStyleDefinitions()` registration;
- the second post-materialization legacy collector pass;
- frame/image/fill-image compatibility registration;
- ImageElement post-render option synchronization;
- CircularImageElement legacy fill-image and graphic state;
- protected placeholder and replacement callback dispatch;
- repeated save, document isolation, or assign/render behavior.

The post-materialization pass is now explicitly named as compatibility
finalization, but it remains behaviorally present. It is not treated as an
authoritative semantic discovery phase.

## 6. Protected dispatch

`materializeStructuredElement()` preserves the existing callbacks:

- `normalizeStructuredPlaceholder()`;
- `replacePlaceholderWithDom()`;
- `hasPlaceholder()`.

They continue to dispatch through the `OdtTemplate` facade, preserving the
existing protected extension surface for subclasses.

## 7. D5F-D handoff

The next slice may narrow post-materialization operations only where existing
characterization proves them redundant. In particular, D5F-D must separately
review ImageElement's synchronized legacy options and CircularImageElement's
legacy post-render state.

Decisions about removing legacy collector projections, StyleMapper/StyleWriter
bridges, protected hooks, legacy `assign()`/`render()`, or compatibility
carriers remain D5G work.

## 8. Validation result

The D5F-B characterization suite and relevant SR-06/SR-07/resource suites
remain green. The complete Composer suite passes with the repository's known
PHPUnit warning and deprecation. No rendering-relevant XML or production
element behavior was intentionally changed, so no visual regression workflow
was run for this orchestration-only slice.

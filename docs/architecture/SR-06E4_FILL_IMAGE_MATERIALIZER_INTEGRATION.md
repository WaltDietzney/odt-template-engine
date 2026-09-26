# SR-06E.4 — Fill-Image Declaration Materializer and setElement Integration

Status: implementation slice

Depends on:

- SR-06E.1 Fill-Image Dependency Contract + Characterization
- SR-06E.2 Document-Local Dependency Model
- SR-06E.3 Producer / Transitive Collection

## Purpose

SR-06E.4 connects the semantic fill-image dependency model to normal structured insertion.

The slice removes the normal `setElement()` path's dependency on `CircularImageElement::toDomNode()` mutation for discovering and materializing the required `draw:fill-image` declaration.

It does not remove the legacy compatibility path. That cleanup belongs to SR-06F.

## Required sequence

Normal structured insertion must perform the following sequence before rendering the element subtree:

```text
semantic style collection
        +
fill-image dependency collection
        ↓
document-local registration
        ↓
semantic graphic style materialization
        +
fill-image declaration materialization
        ↓
physical resource preparation
        ↓
structured element DOM materialization
```

The fill-image declaration must therefore be available in `styles.xml` before `toDomNode()` is called.

## Materializer responsibility

`FillImageRequirementMaterializer` owns only native declaration materialization.

It must:

- accept one document-local `FillImageRequirement`;
- target `styles.xml`;
- materialize a native namespaced `draw:fill-image` under `office:styles`;
- emit `draw:name` and the required `xlink:*` attributes;
- be idempotent;
- reuse an existing target-document declaration with the same `draw:name`;
- leave an existing target declaration authoritative even if its `xlink:href` differs.

It must not:

- copy bitmap files;
- update the manifest;
- materialize graphic styles;
- own drawing geometry;
- inspect concrete element types;
- use process-global registration state.

## setElement integration

`OdtTemplate::setElement()` must collect `FillImageRequirement` instances through `FillImageRequirementCollector` and register them in the current `OdtDocumentContext` before structured element materialization.

After registration, the current document-local requirements are materialized through `FillImageRequirementMaterializer`.

This integration is deliberately adjacent to semantic style preparation. It must not be folded into `StyleRequirementMaterializer`, because `draw:fill-image` is not a `style:style` family.

## Target-document authority

A target template may already contain:

```xml
<draw:fill-image draw:name="PhotoFill" xlink:href="Pictures/authored.png" .../>
```

If a document-local semantic dependency requests the same identity with another `href`, the existing target-document declaration remains authoritative for materialization.

The document-local registry still detects incompatible pending definitions registered by the engine itself. Target-document authority and pending-definition conflict detection are separate concerns.

## Compatibility boundary

SR-06E.4 intentionally retains:

- `CircularImageElement` legacy fill-image mutation in `toDomNode()`;
- legacy `getOwnFillImageRequirements()` / `getFillImageRequirements()` arrays;
- `StyleContext::$fillImages` compatibility state;
- the post-materialization legacy collector pass;
- legacy `setValuesInDom()` behavior;
- legacy finalization helpers.

For normal `setElement()` these paths are no longer required to make the native fill-image declaration exist. Their reduction belongs to SR-06F.

## Resource boundary

Physical bitmap handling remains unchanged:

```text
FillImageRequirement
    └── href = Pictures/photo.png

StructuredResourceCollector
    └── source path = /local/path/photo.png

OdtPackage
    ├── copy Pictures/photo.png
    └── synchronize manifest
```

The materializer must not receive an absolute source path.

## Tests

E.4 requires focused coverage for:

1. native declaration materialization;
2. idempotent repeated materialization;
3. existing target declaration authority;
4. direct `CircularImageElement` registration/materialization during `setElement()` before save;
5. nested/transitive `CircularImageElement` dependency preparation;
6. existing target declaration authority through `setElement()`;
7. previous E.1–E.3 and SR-06D compatibility suites remain green.

## Non-goals

E.4 does not:

- remove legacy fill-image registries;
- remove compatibility finalization;
- change normal image behavior;
- change circular-image geometry;
- alter graphic style identity;
- redesign `setElement()` orchestration generally;
- perform SR-06F cleanup;
- replace SR-06G visual regression.

## Exit criterion

SR-06E.4 is complete when normal `setElement()` processing can prepare a circular image's named `draw:fill-image` declaration entirely from semantic dependency collection before DOM rendering, while target-document authority, document-local conflicts, legacy compatibility, resource handling, and existing rendering behavior remain intact.

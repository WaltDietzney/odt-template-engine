# SR-06D.2 — Graphic Style Materializer

Status: implementation slice

Branch: `architecture/sr-06d2-graphic-style-materializer`

Depends on:

- `SR-06D1_GRAPHIC_RESOLUTION_CONTRACT.md`
- `SR-06C1_GRAPHIC_PRODUCER_SEMANTICS_CONTRACT.md`
- `SR-06C5_INTEGRATION_COMPATIBILITY_PREFLIGHT.md`

## 1. Purpose

SR-06D.2 enables native XML materialization for semantic `StyleRequirement` definitions with `family = graphic`.

SR-06D.1 established that the existing generic `StyleContext` resolution model already supplies the required authority, ambiguity, identity, conflict, and lifecycle semantics for graphic requirements. D.2 therefore changes only the XML materialization boundary.

The central change is:

> Semantic graphic definitions are no longer inert in `StyleRequirementMaterializer`.

## 2. Materialization model

Graphic definitions use the same native semantic materializer as paragraph and text definitions.

A semantic common graphic definition such as:

```text
kind          = definition
scope         = common
family        = graphic
documentPart  = styles.xml
name          = <semantic style name>
parent        = Frame
propertyGroup = style:graphic-properties
```

is materialized as a native `style:style` with:

```xml
<style:style style:name="..." style:family="graphic" style:parent-style-name="Frame">
    <style:graphic-properties .../>
</style:style>
```

Semantic property groups and attributes are already native ODF data and are written verbatim. D.2 does not remap them through `StyleMapper` or legacy registries.

## 3. Existing-document authority

Materialization preserves an existing matching target-document definition.

If the selected target container already contains the same `style:name` and `style:family`, the materializer does not add a duplicate and does not overwrite the authored definition.

This is the materialization counterpart of the document-authority semantics characterized in D.1.

D.2 does not introduce structural equality comparison or merge behavior for an authored style and a pending semantic definition.

## 4. Scope and document part

Graphic definitions retain the generic semantic scope/document-part model.

- common graphic definitions target `styles.xml`;
- automatic graphic definitions may target the appropriate automatic-style container according to their `documentPart`;
- common definitions targeting `content.xml` are rejected consistently with common paragraph/text definitions.

Current SR-06C producers emit common graphic definitions in `styles.xml`. Support for the generic automatic path is retained because scope and document part are dimensions of `StyleRequirement`, not producer-specific hard-coded behavior.

## 5. Supported native graphic properties

D.2 does not define new producer semantics. It materializes the native properties already approved by SR-06C producers, including the currently exercised categories:

- `fo:background-color`;
- `draw:fill`;
- `draw:fill-color`;
- `fo:border*`;
- `fo:padding*`;
- `draw:stroke` and approved stroke attributes;
- `draw:fill-image-name`;
- `draw:fill-image-width`;
- `draw:fill-image-height`;
- `style:repeat`.

The materializer remains generic at the XML level. Producer classification remains responsible for deciding which properties belong to semantic graphic identity.

## 6. Circular-image dependency boundary

`CircularImageElement` semantic graphic definitions contain `draw:fill-image-name`.

D.2 writes that attribute as part of `style:graphic-properties`, but it does not own or resolve the referenced `draw:fill-image` declaration.

Therefore D.2 deliberately permits this transition state:

```text
semantic graphic style
    └── draw:fill-image-name = X

legacy compatibility path
    └── draw:fill-image declaration X
```

Semantic fill-image dependency ownership remains SR-06E.

## 7. Compatibility boundary

D.2 does not make semantic graphic materialization the sole rendering authority for structured drawing elements.

The existing C-era compatibility paths remain present:

- `DrawTextBox` still has its legacy frame-style requirement path;
- normal `ImageElement` remains legacy-only for its current geometry/placement-derived image style;
- `CircularImageElement` retains legacy image/fill-image state after DOM materialization;
- `OdtTemplate::setElement()` still performs the existing compatibility collection/registration passes;
- legacy finalization remains active.

D.3 is responsible for validating and documenting the `setElement()` authority transition once semantic graphic styles are actually written before element DOM materialization.

## 8. Materializer implementation

The bounded implementation is intentionally small:

1. remove the temporary SR-06C inert return for `family = graphic`;
2. add `graphic` to the materializer's supported semantic families;
3. generalize the common-style validation message so the same rule applies to paragraph, text, and graphic;
4. keep namespace handling, native property-group writing, duplicate preservation, and target-container behavior unchanged.

No graphic-specific mapper, registry adapter, or separate materializer service is introduced.

## 9. Required tests

Focused tests cover at minimum:

1. common graphic definition writes `style:family="graphic"` and `style:graphic-properties` to `styles.xml`;
2. `style:parent-style-name="Frame"` is preserved;
3. DrawTextBox-style native appearance attributes are written with their correct namespaces;
4. CircularImage-style bitmap/stroke properties are written verbatim;
5. an existing authored graphic definition remains authoritative and is not duplicated or overwritten;
6. repeated materialization is idempotent;
7. automatic graphic materialization follows the generic automatic-style path;
8. invalid common/content ownership is rejected.

Existing paragraph/text materializer tests remain required regression coverage.

## 10. Explicit non-goals

SR-06D.2 does not:

- change `StyleContext` resolution semantics;
- change graphic producer identity or classification;
- change `OdtTemplate::setElement()` orchestration;
- remove legacy frame/image/fill-image registries;
- add semantic fill-image dependency requirements;
- materialize `draw:fill-image` declarations semantically;
- change `draw:style-name` selection on drawing objects;
- redesign `StyleWriter` or `StyleMapper`;
- change image/frame geometry, positioning, anchor, wrapping, or layout APIs;
- fix CircularImage visual rendering;
- begin SR-06E/F or SR-07.

## 11. Exit condition

SR-06D.2 is complete when:

1. semantic graphic definitions materialize natively through `StyleRequirementMaterializer`;
2. existing target-document graphic definitions remain authoritative;
3. repeated materialization remains idempotent;
4. paragraph/text materialization behavior remains unchanged;
5. D.1 graphic resolution characterization remains green;
6. C.2-C.5 producer and compatibility regressions remain green;
7. full automated regression remains green apart from already-known unrelated warnings/deprecations.

At that point D.3 can examine the next boundary:

> Semantic graphic definitions now exist in the target document before structured drawing DOM materialization; which path is authoritative when the structured element and legacy compatibility finalization also participate?

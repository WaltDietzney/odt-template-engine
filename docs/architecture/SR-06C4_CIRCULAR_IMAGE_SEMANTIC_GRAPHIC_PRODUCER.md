# SR-06C.4 — CircularImageElement Semantic Graphic Producer

Status: implementation slice

Branch: `architecture/sr-06c4-circular-image-semantic-producer`

Depends on:

- `SR-06C1_GRAPHIC_PRODUCER_SEMANTICS_CONTRACT.md`
- SR-06C.2 DrawTextBox semantic graphic producer
- SR-06C.3 ImageElement semantic producer outcome

## 1. Purpose

SR-06C.4 migrates the semantic graphic-style ownership of `CircularImageElement` without migrating graphic materialization, fill-image declaration ownership, physical image resources, or drawing-object structure.

Unlike normal `ImageElement`, the circular-image convenience element owns a real graphic appearance definition: a bitmap fill referencing a named fill-image declaration.

The slice therefore makes that semantic graphic definition available deterministically before DOM materialization.

## 2. Previous lifecycle coupling

Before SR-06C.4, `CircularImageElement` derived the following values only inside `toDomNode()`:

- fill-image declaration name;
- bitmap graphic-style properties;
- generated graphic style name.

Consequently, before `toDomNode()`:

- `getImageStyleRequirements()` returned no legacy image style;
- `getFillImageRequirements()` returned no fill-image declaration;
- no semantic graphic requirement could be discovered independently of DOM materialization.

That lifecycle is acceptable for the remaining legacy compatibility path, but it is not suitable as the source of semantic style discovery.

## 3. Semantic graphic definition

`CircularImageElement::getOwnStyleRequirements()` now derives one semantic `StyleRequirement` before DOM materialization.

The requirement uses:

- `kind = definition`;
- `scope = common`;
- `family = graphic`;
- `documentPart = styles.xml`;
- `parentStyleName = Frame`;
- property group `style:graphic-properties`.

The semantic graphic properties are exactly:

```text
draw:fill = bitmap
draw:fill-image-name = <resolved fill-image declaration name>
draw:fill-image-width = 100%
draw:fill-image-height = 100%
style:repeat = stretch
draw:stroke = none
```

These properties describe the appearance semantics of the circular bitmap-filled shape.

## 4. Fill-image dependency boundary

`draw:fill-image-name` remains part of the semantic graphic style because the style definition explicitly references that named declaration.

The `draw:fill-image` declaration itself is not converted into a semantic style family in SR-06C.4.

Its document-local dependency lifecycle remains deferred to SR-06E.

Likewise, the physical image path, package `Pictures/` resource, and manifest state remain resource concerns rather than graphic-style properties.

## 5. Drawing structure and geometry remain separate

The following remain outside semantic graphic-style identity:

- `draw:custom-shape`;
- ellipse enhanced geometry;
- object width and height;
- anchor;
- z-index;
- animation setting;
- physical image path and filename as resource identity;
- package resource copying.

Changing width, height, anchor, or other object placement inputs therefore does not create a different semantic graphic definition when the bitmap-fill dependency is unchanged.

## 6. Deterministic pre-materialization derivation

The fill-image declaration name and semantic graphic property set are now derived by side-effect-free helper methods.

`getOwnStyleRequirements()` does not require `toDomNode()` to run first and does not mutate the legacy registration state.

This resolves the producer lifecycle problem identified in SR-06A/SR-06B while preserving the existing legacy behavior.

## 7. Legacy compatibility

SR-06C.4 does not migrate legacy graphic or fill-image materialization.

`toDomNode()` still:

1. establishes the legacy fill-image requirement state;
2. establishes the legacy circular graphic-style requirement state;
3. emits the existing `draw:custom-shape` and ellipse enhanced geometry;
4. references the generated style name through `draw:style-name`.

The legacy graphic definition deliberately uses the same bitmap-fill semantic property set, so the generated semantic and legacy style names are currently equivalent for `CircularImageElement`.

That equivalence is an implementation consequence of this producer's current clean appearance definition; it does not collapse the architectural distinction between semantic requirements and legacy materialization.

Before `toDomNode()`, the legacy `getImageStyleRequirements()` and `getFillImageRequirements()` behavior remains unchanged and returns no requirements.

## 8. Materialization boundary

The semantic `graphic` requirement is registered through the SR-06C pipeline, but graphic semantic materialization remains intentionally inert until SR-06D.

SR-06C.4 therefore does not:

- teach `StyleRequirementMaterializer` to emit graphic styles;
- replace the legacy style writer/injection path;
- switch fill-image declaration ownership to `StyleContext`;
- introduce a semantic `fill-image` family.

## 9. Tests

`CircularImageElementSemanticGraphicProducerTest` verifies that:

1. the bitmap graphic definition exists before DOM materialization;
2. its semantic fields and graphic properties match the approved C.1 contract;
3. shape geometry and placement do not affect semantic style identity;
4. semantic identity remains stable across `toDomNode()`;
5. the existing legacy image-style and fill-image requirement lifecycle remains intact;
6. the emitted shape continues to reference the legacy-compatible style identity.

Existing SR-06B characterization remains responsible for the broader custom-shape, resource, manifest, and fill declaration behavior.

## 10. Non-goals

SR-06C.4 does not:

- fix or redesign circular-image rendering;
- redesign custom-shape geometry;
- change public image options;
- change ordinary `ImageElement` semantics;
- materialize semantic graphic styles;
- create a fill-image dependency object;
- remove legacy image/fill-image registries;
- change resource copying or manifest handling;
- redesign the `toDomNode()` lifecycle generally.

## 11. Exit condition

SR-06C.4 is complete when the semantic bitmap graphic definition is discoverable before DOM materialization, remains independent of shape geometry/placement, preserves the current legacy output path, and all focused and regression tests pass.

After this slice, SR-06C.5 can perform the producer-level integration and compatibility preflight before SR-06D begins graphic requirement resolution/materialization.

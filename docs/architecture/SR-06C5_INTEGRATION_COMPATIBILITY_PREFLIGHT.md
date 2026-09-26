# SR-06C.5 — Integration / Compatibility Preflight

Status: implementation preflight

Branch: `architecture/sr-06c5-integration-compatibility-preflight`

Depends on:

- SR-06C.1 graphic producer semantics contract
- SR-06C.2 DrawTextBox semantic graphic producer
- SR-06C.3 ImageElement semantic producer outcome
- SR-06C.4 CircularImageElement semantic graphic producer

## 1. Purpose

SR-06C.5 validates SR-06C as one coherent migration stage.

The slice does not introduce a fourth producer or extend semantic materialization. It verifies that the producer semantics introduced by C.2-C.4 coexist with the current document-local StyleContext, the semantic and legacy collectors, structured element insertion, legacy graphic style registration, fill-image declaration handling, package resources, save/finalization, and document reset lifecycle.

The central preflight question is:

> Can semantic graphic producer discovery be enabled now while legacy graphic materialization remains authoritative until SR-06D/E/F, without changing rendered compatibility behavior?

## 2. Approved producer outcomes

SR-06C closes with three intentionally different producer outcomes.

### DrawTextBox

`DrawTextBox` owns a semantic `graphic` definition when approved appearance properties exist.

Its semantic identity excludes drawing-object structure, size, placement, flow, overlap, and unclassified geometry. The existing legacy frame style remains authoritative for current DOM rendering.

### ImageElement

`ImageElement` owns no semantic graphic definition with the currently supported normal-image API.

Its current legacy image style identity is composed from geometry, anchor, wrapping, placement, and related state. SR-06C deliberately does not preserve that registry shape as a fake semantic graphic style.

### CircularImageElement

`CircularImageElement` owns a semantic bitmap-fill `graphic` definition before DOM materialization.

The definition contains only the approved bitmap-fill appearance semantics. Custom-shape geometry, shape dimensions, anchor, z-index, physical bitmap resource, and the named `draw:fill-image` declaration remain separate concerns.

## 3. Transition architecture validated by this slice

During SR-06C the document pipeline intentionally contains both semantic and compatibility channels:

```text
OdtElement producer
        |
        +--> semantic requirements
        |       |
        |       v
        |   StyleContext semanticDefinitions
        |       |
        |       +--> graphic intentionally inert in StyleRequirementMaterializer
        |
        +--> current DOM materialization
        |       |
        |       v
        |   existing draw:* structure / legacy draw:style-name
        |
        +--> post-materialization legacy requirements
                |
                +--> frameStyles
                +--> imageStyles
                +--> fillImages
                        |
                        v
                existing legacy finalization
```

This dual path is transitional, not the final graphic architecture.

## 4. Collector compatibility

`StyleRequirementCollector::collectSemantic()` and the legacy `collect()` method remain deliberately separate.

The preflight verifies that:

- DrawTextBox is visible to the semantic collector and remains visible as legacy `frame` state;
- ImageElement remains absent from semantic graphic discovery while retaining legacy `image` requirements;
- CircularImageElement is visible semantically before DOM materialization while its legacy image/fill-image state remains lifecycle-dependent as characterized.

No semantic families named `frame`, `image`, or `fill-image` are introduced.

## 5. setElement compatibility

`OdtTemplate::setElement()` currently:

1. collects semantic requirements;
2. registers them in the document-local StyleContext;
3. invokes semantic materialization, where `graphic` is intentionally inert until SR-06D;
4. preserves existing paragraph/text compatibility preparation;
5. copies physical resources;
6. materializes the structured element;
7. re-collects legacy requirements and registers frame/image/fill-image state.

SR-06C.5 verifies this ordering with `CircularImageElement`, because it exercises all relevant boundaries simultaneously:

- semantic graphic definition before DOM materialization;
- legacy graphic style after DOM materialization;
- named fill-image declaration after DOM materialization;
- physical bitmap resource through the package resource channel.

## 6. Save/finalization compatibility

The preflight verifies that a saved circular-image document still contains the expected legacy-rendered artifacts:

- `draw:custom-shape` references the expected graphic style name;
- styles.xml contains the graphic style used by the shape;
- styles.xml contains the named fill-image declaration;
- the graphic style references that declaration;
- the physical image exists under `Pictures/`;
- the manifest contains the physical image resource.

This test does not claim that semantic `graphic` requirements are materialized. The saved result remains intentionally produced through the current legacy graphic compatibility path.

## 7. Document lifecycle compatibility

`StyleContext` is document-local state.

The preflight verifies that `OdtTemplate::load()` resets both:

- semantic graphic definitions;
- legacy image and fill-image registries.

No process-global semantic state is introduced by SR-06C.

## 8. Production-code conclusion

No additional production-code change is required by C.5 if the compatibility preflight passes.

That is an intentional result. C.5 is a system-validation slice: changing production code merely to make the slice look implementation-heavy would mix validation with new behavior and would weaken the semantic boundary established by C.1-C.4.

Any failure uncovered by the preflight must be classified before correction:

- producer defect belonging to C.2-C.4;
- existing legacy behavior requiring characterization;
- SR-06D materialization concern;
- SR-06E fill-image dependency concern;
- SR-06F legacy retirement concern.

C.5 must not silently absorb D/E/F work.

## 9. Explicit non-goals

SR-06C.5 does not:

- materialize semantic `graphic` requirements;
- change the inert graphic bridge in StyleRequirementMaterializer;
- add semantic fill-image dependency objects;
- remove legacy frameStyles, imageStyles, or fillImages;
- unify pre- and post-materialization collector passes;
- change draw:style-name resolution;
- redesign StyleWriter;
- change StyleMapper graphic mappings;
- fix CircularImageElement rendering quality;
- redesign repeated render/save lifecycle beyond regression validation;
- alter public APIs;
- begin SR-07 work.

## 10. Preflight test

`tests/Integration/Sr06CGraphicProducerCompatibilityPreflightTest.php` validates:

1. all three approved producer outcomes through the collectors;
2. coexistence of semantic and legacy graphic channels through `setElement()`;
3. saved CircularImageElement legacy style/fill/resource compatibility;
4. reset of semantic and legacy graphic document state through `load()`.

The producer-specific C.2-C.4 tests and the SR-06B characterization suite remain required regression coverage.

## 11. Final validation checklist

Before SR-06C FINAL GO, run at minimum:

```bash
vendor/bin/phpunit tests/Integration/Sr06CGraphicProducerCompatibilityPreflightTest.php
vendor/bin/phpunit tests/Integration/DrawTextBoxSemanticGraphicProducerTest.php
vendor/bin/phpunit tests/Integration/ImageElementSemanticGraphicProducerTest.php
vendor/bin/phpunit tests/Integration/CircularImageElementSemanticGraphicProducerTest.php
vendor/bin/phpunit tests/Integration/StyleContextGraphicDrawingBoundaryCharacterizationTest.php
vendor/bin/phpunit tests/Document/GraphicStyleRequirementContractTest.php

find src tests -name '*.php' -print0 | xargs -0 -n1 php -l
git diff --check origin/architecture/sr-06c4-circular-image-semantic-producer...HEAD
composer test
```

The known PHPUnit test-runner deprecation in `BookmarkTargetReplacementTest` is outside SR-06C scope unless it changes during this slice.

Because SR-06C intentionally preserves legacy graphic rendering, no new visual rendering semantics are introduced by C.5 itself. A manual LibreOffice regression remains prudent before merging the complete SR-06C stack into the integration branch, especially for the circular-image sample path, but visual repair is explicitly outside this slice.

## 12. Exit condition

SR-06C is ready for FINAL GO when:

1. C.1-C.4 producer contracts remain satisfied;
2. the C.5 integration preflight passes;
3. full automated regression remains green apart from already-known unrelated warnings/deprecations;
4. no hidden production-code adaptation is required to keep semantic and legacy graphic channels compatible;
5. the transition boundary to SR-06D/E/F remains explicit.

At that point SR-06C has completed its responsibility:

> Active drawing elements know what semantic graphic style requirements they own, without yet changing how those requirements are resolved, materialized, or how legacy graphic compatibility state is retired.

# SR-06E.5 — Semantic Independence / Compatibility Preflight

## Status

Preflight slice. No production behavior change is intended.

## Purpose

SR-06E.1 through SR-06E.4 introduced a document-local semantic dependency path for ODF `draw:fill-image` declarations:

```text
OdtElement producer
    -> FillImageRequirement
    -> FillImageRequirementCollector
    -> OdtDocumentContext registry
    -> FillImageRequirementMaterializer
    -> styles.xml draw:fill-image
```

SR-06E.5 proves that this path is functionally independent of the legacy fill-image mutation and `StyleContext::fillImages()` compatibility channel.

The slice also verifies that the existing `CircularImageElement` compatibility state can continue to coexist during the transition without producing duplicate target declarations.

## Independence criterion

The semantic path is considered independent only if a producer can satisfy all of the following conditions simultaneously:

1. it exposes a semantic graphic `StyleRequirement` referencing a named fill image;
2. it exposes a typed `FillImageRequirement` before DOM materialization;
3. it exposes its physical image through the structured resource contract;
4. it never exposes a legacy `getOwnFillImageRequirements()` definition;
5. `OdtTemplate::setElement()` still materializes the semantic graphic style and `draw:fill-image` declaration;
6. the physical bitmap is copied and present in the saved package manifest;
7. the legacy `StyleContext::fillImages()` registry remains empty.

If those conditions hold, the new dependency path cannot be explained by the legacy `CircularImageElement::toDomNode()` mutation or by legacy fill-image registration.

## Compatibility criterion

`CircularImageElement` currently retains legacy compatibility behavior:

- `toDomNode()` still establishes legacy fill-image state;
- the post-materialization legacy collector can still register that definition in `StyleContext`;
- the semantic dependency registry is populated independently before that point.

During SR-06E.5 both states may coexist.

The required compatibility invariant is:

> Coexistence must not produce more than one `draw:fill-image` declaration for the same `draw:name` identity.

Legacy reduction belongs to SR-06F, not SR-06E.5.

## Test strategy

`tests/Integration/FillImageSemanticIndependencePreflightTest.php` introduces a test-only semantic producer.

The producer deliberately does not override the legacy fill-image requirement hook, so its inherited legacy result remains empty before and after `toDomNode()`.

It provides only:

- semantic graphic style requirement;
- typed fill-image dependency;
- structured image resource;
- a minimal drawing node referencing the semantic graphic style.

The preflight proves three cases.

### 1. Semantic-only in-memory materialization

After `setElement()`:

- the typed fill-image registry contains the dependency;
- `draw:fill-image` exists in `styles.xml`;
- the semantic graphic style exists;
- legacy `StyleContext::fillImages()` remains empty;
- the producer's legacy fill-image hook remains empty.

### 2. Semantic-only saved package

After `save()`:

- the fill-image declaration remains present;
- its `xlink:href` targets the expected `Pictures/...` entry;
- the semantic graphic style references the fill-image identity;
- the physical bitmap exists in the ODT package;
- the manifest contains the bitmap path;
- no legacy fill-image state was required.

### 3. CircularImage compatibility coexistence

For the current `CircularImageElement`:

- the semantic dependency registry contains the fill-image identity;
- the legacy `StyleContext` compatibility registry may contain the same identity after DOM materialization;
- `styles.xml` still contains only one declaration for that identity.

## Non-goals

SR-06E.5 does not:

- remove `CircularImageElement::$fillImageName`;
- remove legacy `getOwnFillImageRequirements()` methods;
- remove `StyleContext::registerFillImage()` or `StyleContext::fillImages()`;
- change the second legacy collector pass in `OdtTemplate::setElement()`;
- change `injectDocumentGraphicStyles()` legacy fill-image finalization;
- change the legacy `assign()` / `render()` structured-element path;
- change drawing geometry or LibreOffice rendering;
- change physical resource ownership.

Those cleanup decisions belong to SR-06F.

## GO criteria

SR-06E.5 can receive FINAL GO when:

1. the semantic-only independence preflight passes;
2. the existing SR-06E.1 through SR-06E.4 tests remain green;
3. the SR-06D graphic integration preflight remains green;
4. the complete PHPUnit suite passes apart from the already known deprecation;
5. PHP lint and `git diff --check` pass;
6. no production source file changed in this slice;
7. no sample output artifacts were modified.

A successful E.5 therefore establishes the architectural prerequisite for SR-06F compatibility reduction without performing that reduction prematurely.

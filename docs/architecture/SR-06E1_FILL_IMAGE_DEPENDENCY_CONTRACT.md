# SR-06E.1 — Fill-Image Dependency Contract and Characterization

Status: architecture contract and characterization slice

Branch: `architecture/sr-06e1-fill-image-dependency-contract`

Depends on:

- `SR-06_SEMANTIC_GRAPHIC_STYLE_REQUIREMENTS_CHANGE_CONTRACT.md`
- `SR-06D4_INTEGRATION_COMPATIBILITY_PREFLIGHT.md`
- `STYLE_REQUIREMENT_SR05_FONT_DEPENDENCIES_CHANGE_CONTRACT.md`

## 1. Purpose

SR-06D established document-local semantic `graphic` style resolution, materialization, and bounded rendering authority. A remaining lifecycle gap is the named bitmap-fill declaration referenced by semantic graphic properties such as:

```xml
<style:graphic-properties
    draw:fill="bitmap"
    draw:fill-image-name="cv_photo_example"
    .../>
```

The referenced name is not a `style:style` family. It identifies a separate ODF drawing declaration:

```xml
<draw:fill-image
    draw:name="cv_photo_example"
    xlink:href="Pictures/example.png"
    xlink:type="simple"
    xlink:show="embed"
    xlink:actuate="onLoad"/>
```

SR-06E makes this dependency explicit and document-local without moving physical package resources into style ownership.

E.1 defines the semantics and characterizes the current compatibility lifecycle. It introduces no production behavior change.

## 2. Current lifecycle finding

`CircularImageElement` already knows the symbolic fill-image name while producing its semantic graphic `StyleRequirement`.

However, its legacy fill-image declaration is not exposed until `toDomNode()` mutates element state:

```text
getOwnStyleRequirements()
        |
        +--> graphic style contains draw:fill-image-name = X

getOwnFillImageRequirements() before DOM
        |
        +--> []

        toDomNode()
            |
            +--> stores legacy fill-image state X

getOwnFillImageRequirements() after DOM
        |
        +--> X => { name, path, filename }
```

`OdtTemplate::setElement()` therefore materializes the semantic graphic style before the legacy fill-image dependency becomes registered. The declaration is recovered later by the post-materialization legacy collector and written during compatibility finalization.

This works today, but it is an implicit ordering dependency and is not the desired semantic architecture.

## 3. Semantic distinction

SR-06E MUST preserve three separate concepts:

```text
Graphic Style
    |
    +--> symbolic fill-image reference X
             |
             v
       Fill-Image Declaration X
             |
             +--> package href Pictures/example.png
                         |
                         v
                 Physical Bitmap Resource
```

These layers have different ownership.

### 3.1 Graphic style

The graphic style remains a `StyleRequirement` with family `graphic`.

`draw:fill-image-name` is a graphic property and may participate in semantic graphic style identity.

### 3.2 Fill-image declaration

A `draw:fill-image` declaration is a named document dependency. It is not a semantic style family and MUST NOT be represented as `StyleRequirement(family = fill-image)` merely to mirror the historical collector.

The implementation MAY introduce a dedicated immutable document-local requirement/value object and a narrowly scoped registry/materializer.

### 3.3 Physical bitmap resource

The source bitmap, copied `Pictures/...` entry, and manifest lifecycle remain resource/package concerns.

SR-06E MUST NOT move physical resource ownership into `StyleContext` or a fill-image declaration registry.

`StructuredResourceCollector` / `OdtPackage` remain responsible for package preparation.

## 4. Dependency identity

The semantic identity of a fill-image declaration is its ODF `draw:name` within the relevant document declaration space.

For a document-local pending definition:

- registering the same name with an equivalent declaration is idempotent;
- registering the same name with an incompatible definition is a deterministic conflict;
- order of registration MUST NOT silently select first-wins or last-wins semantics.

The source filesystem path is input needed to prepare/materialize the declaration and package resource, but it is not itself the ODF reference identity.

If two pending definitions use the same `draw:name` but resolve to incompatible target href/resource semantics, they conflict even if their PHP source paths differ only indirectly.

## 5. Target-document authority

An existing named `draw:fill-image` declaration in the target document is authoritative.

Semantic materialization MUST:

1. detect an existing declaration by `draw:name` in the valid target location;
2. reuse it rather than append a duplicate;
3. not overwrite its authored `xlink:href` merely because a pending dependency with the same symbolic name exists;
4. leave later replacement/editing semantics to a separately designed structured operation.

This follows the same preservation principle used by semantic styles while respecting that `draw:fill-image` is not a `style:style` family.

## 6. Document part and ODF location

Current engine output places `draw:fill-image` declarations in `styles.xml` under `office:styles`.

SR-06E preserves that current producer/materialization contract unless ODF evidence or an existing target declaration requires a separately approved broader placement model.

The first semantic implementation should therefore remain narrowly scoped to the declaration placement actually required by current `CircularImageElement` production.

E MUST NOT generalize document-part placement speculatively.

## 7. Producer contract

A producer that defines a semantic bitmap-fill graphic style must be able to expose the corresponding fill-image dependency before drawing DOM materialization.

For `CircularImageElement`, this means the dependency definition must be derivable from constructor/producer state without relying on mutation inside `toDomNode()`.

The existing legacy method:

```php
getOwnFillImageRequirements(): array
```

MUST retain its compatibility meaning/signature during SR-06. It must not be repurposed to return new semantic objects.

A new bounded semantic hook may therefore be introduced rather than breaking the legacy API.

Transitive dependency collection MUST follow `ownedElements()` and MUST NOT add concrete `CircularImageElement` traversal to `OdtTemplate`.

## 8. Reference discovery

A semantic graphic definition containing `draw:fill-image-name` establishes a symbolic dependency reference.

The implementation must make the relationship explicit enough to detect missing or conflicting definitions without depending on process-global state or DOM-materialization order.

The exact E implementation may separate:

- discovery of fill-image references from graphic `StyleRequirement` property groups; and
- producer-owned fill-image definitions carrying the information needed to materialize the named declaration.

E.1 does not yet choose whether these are represented by one collaborator or several. The implementation must remain smaller than a generic dependency framework unless additional families prove that abstraction necessary.

## 9. Document-local ownership and lifecycle

Semantic fill-image dependency state belongs to one `OdtDocumentContext`.

Required behavior:

- equivalent registration is idempotent;
- incompatible same-name pending definitions conflict deterministically;
- `load()` / core-document replacement resets pending semantic dependency state;
- two template instances cannot contaminate one another;
- repeated save/render does not duplicate declarations;
- no static/global semantic registry is introduced.

The existing `StyleContext::$fillImages` array remains a legacy compatibility channel until SR-06F. E MUST NOT silently relabel that array as the new semantic dependency architecture.

## 10. Materialization boundary

Fill-image declarations should be materialized by a dedicated, narrowly scoped collaborator rather than extending `StyleRequirementMaterializer` into a generic resource/drawing materializer.

A semantic fill-image materializer is responsible only for declaration XML such as:

- `draw:name`;
- `xlink:href`;
- `xlink:type="simple"`;
- `xlink:show="embed"`;
- `xlink:actuate="onLoad"`.

It is not responsible for:

- copying the image file;
- updating the manifest;
- shape geometry;
- frame placement;
- graphic style properties;
- normal `draw:image` rendering.

## 11. Compatibility boundary

During E, existing legacy channels may coexist with the semantic dependency path.

In particular:

- legacy `StyleContext::fillImages()` may remain populated;
- legacy finalization may remain available for direct/old paths;
- `CircularImageElement::getOwnFillImageRequirements()` remains supported;
- normal `ImageElement` behavior is unchanged;
- direct legacy `setValuesInDom()` remains a compatibility concern;
- reduction of redundant normal `setElement()` legacy fill-image registration belongs to SR-06F after E proves independence.

The semantic path should become sufficient for normal `setElement()` use before the legacy channel is reduced.

## 12. Characterization requirements for E.1

Before production migration, tests must capture at least:

1. the semantic graphic style already references its fill-image name before DOM materialization;
2. the legacy fill-image requirement is currently absent before `toDomNode()` and appears after it;
3. the legacy declaration definition contains the symbolic name and package filename/source information;
4. physical resource collection is available independently of that legacy fill-image lifecycle;
5. equivalent legacy document-local registration is idempotent and conflicting same-name registration is rejected;
6. existing target-document `draw:fill-image` declarations are not duplicated/overwritten by current finalization;
7. save output still contains the declaration, package bitmap, and manifest entry;
8. lifecycle reset behavior remains characterized by existing SR-06C/D tests.

These observations protect compatibility. They do not define the legacy order as the desired E architecture.

## 13. Proposed E slices

### SR-06E.1 — Dependency Contract + Characterization

- document semantic identity, ownership, authority, and resource boundary;
- characterize current pre/post-DOM dependency lifecycle;
- no production change.

### SR-06E.2 — Document-Local Dependency Model

- introduce the smallest dedicated immutable fill-image dependency representation;
- add document-local registry/resolution semantics;
- prove idempotence, deterministic conflict, reset, and existing-document authority;
- no producer migration yet.

### SR-06E.3 — Producer / Transitive Collection

- expose `CircularImageElement` fill-image definition before DOM materialization through a new semantic hook;
- collect transitively through `ownedElements()`;
- make graphic-reference-to-fill-definition relationship testable;
- retain legacy producer methods.

### SR-06E.4 — Declaration Materializer + `setElement()` Integration

- materialize required named declarations before drawing insertion using a dedicated collaborator;
- preserve physical resource handling through `StructuredResourceCollector` / `OdtPackage`;
- preserve existing target declarations;
- avoid broad `setElement()` cleanup.

### SR-06E.5 — Semantic Independence / Compatibility Preflight

- prove normal `setElement()` output no longer requires post-`toDomNode()` discovery to obtain the fill-image declaration;
- retain bounded legacy compatibility state for SR-06F;
- verify nested, save/repeat, reset, package and manifest behavior;
- no broad legacy removal.

This slice count may be reduced only if adjacent implementation steps remain trivially small and independently reviewable.

## 14. Explicit non-goals

SR-06E does not:

- make `fill-image` a `StyleRequirement` family;
- move bitmap resources into `StyleContext`;
- redesign `StructuredResourceCollector` or `OdtPackage`;
- change normal `ImageElement` semantics;
- change DrawTextBox authority rules;
- redesign `StyleMapper` or `StyleWriter` generally;
- redesign `OdtTemplate::setElement()` orchestration;
- remove legacy fill-image APIs/registries (SR-06F);
- fix CircularImage visual rendering;
- redesign custom-shape geometry;
- begin SR-07 or D5F;
- replace the mandatory SR-06G LibreOffice visual regression.

## 15. Exit condition

SR-06E is complete when a semantic bitmap-fill graphic producer can provide its named fill-image dependency before DOM materialization; that dependency is document-local, conflict-safe, target-document-aware, and materialized independently of hidden legacy registration order; and the physical bitmap remains owned by the existing package/resource pipeline.

Only after that condition is proven may SR-06F reduce the normal semantic path's dependence on the historical `fillImages` compatibility channel.

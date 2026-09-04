# SR-06C.1 — Graphic Producer Semantics Contract

Status: approved implementation contract

Branch: `architecture/sr-06c1-graphic-producer-semantics`

Depends on:

- `SR-06_SEMANTIC_GRAPHIC_STYLE_REQUIREMENTS_CHANGE_CONTRACT.md`
- `SR-06A_GRAPHIC_DRAWING_SEMANTICS_AUDIT.md`
- `StyleRequirement` / `StyleContext` semantic style architecture
- SR-06B graphic/drawing boundary characterization

## 1. Purpose

SR-06C.1 defines the semantic producer contract that later SR-06C slices must implement for active drawing elements.

This slice does not migrate production producers yet. It fixes the semantic projection rules first so that `DrawTextBox`, `ImageElement`, and `CircularImageElement` can be migrated independently without reintroducing the historical `frame` / `image` taxonomy into the semantic style model.

The central rule is:

> A semantic graphic producer describes only the document-local ODF graphic style definition or reference owned by the element. Drawing structure, object placement/geometry, convenience input, dependency declarations, and physical resources remain separate semantic channels even where ODF serialization places some of their attributes under `style:graphic-properties`.

## 2. Semantic projection pipeline

Each producer MUST conceptually apply the following projection before constructing a `StyleRequirement`:

```text
public / legacy options
        |
        v
resolved native semantics
        |
        +-- drawing structure
        +-- object placement / geometry
        +-- semantic graphic-style properties
        +-- named declaration dependencies
        +-- physical package resources
        |
        v
semantic graphic-style properties only
        |
        v
StyleRequirement(family = graphic)
```

A historical mapper output MUST NOT be treated as the semantic projection merely because the mapper currently contains ODF-looking attributes.

## 3. ODF carrier versus architecture ownership

ODF 1.3 permits a broad set of attributes on `style:graphic-properties`, including appearance properties, wrapping/flow properties, positioning properties, dimensions, and anchor-related values.

SR-06C therefore distinguishes two questions:

1. **Can ODF serialize this value in `style:graphic-properties`?**
2. **Does this engine architecture assign the value to semantic graphic-style identity?**

The answer to the first question does not decide the second.

SR-06C keeps object structure and placement outside semantic graphic-style ownership where the engine already owns and emits those values as drawing-object/materialization state. The migration MUST NOT silently normalize their serialization location.

## 4. Common StyleRequirement contract

A semantic graphic definition produced by an active drawing element MUST use the established `StyleRequirement` value object with:

- `kind = definition` when the element owns a generated definition;
- `family = graphic`;
- the existing document-part and scope semantics of the migrated legacy definition;
- optional `parentStyleName` where required by existing semantics;
- property group `graphic-properties` containing only the approved semantic graphic property subset.

A producer that merely references an existing named graphic style MUST use `kind = reference` and MUST NOT synthesize a duplicate definition solely to satisfy the producer API.

No semantic families named `frame`, `image`, or `fill-image` may be introduced.

## 5. Identity contract

Semantic graphic style identity MUST be derived only from:

- StyleRequirement identity fields already defined by the semantic style architecture;
- the semantic graphic property definition;
- semantically relevant named declaration dependencies when they affect the style definition.

The following MUST NOT create a distinct semantic graphic style merely because they differ between drawing objects:

- object width / height;
- x / y coordinates;
- anchor;
- `draw:name`;
- z-index;
- image `xlink:href`;
- physical bitmap filename or package path by itself;
- enhanced geometry;
- convenience-only options such as `align`;
- horizontal/vertical object placement state owned by the drawing object.

Legacy generated style names may continue to vary because of those values during the migration. A legacy style name is therefore not automatically the semantic style identity.

For a bitmap-fill graphic style, the named fill-image reference may participate in the graphic style definition because the style explicitly refers to that declaration. This does not make the physical bitmap filename itself a style property.

## 6. Property classification

The table below is the approved C.1 ownership classification for the currently active producers.

| Property / concern | Semantic graphic style in SR-06C | Notes |
| --- | --- | --- |
| `fo:background-color` | yes | appearance |
| `draw:fill` | yes | appearance/fill semantics |
| `draw:fill-color` | yes | appearance |
| `draw:stroke` and stroke properties | yes | appearance |
| border properties | yes | appearance |
| padding properties | yes | retained as graphic style semantics for current producers |
| bitmap-fill reference properties | yes | style semantics; declaration lifecycle is SR-06E |
| bitmap-fill width/height/repeat properties | yes | fill semantics, not object geometry |
| `style:wrap` | no for current producer identity | flow/layout semantic; current drawing/materialization behavior remains authoritative in C |
| `draw:wrap-influence-on-position` | no for current producer identity | placement/flow |
| overlap/flow placement controls | no for current producer identity | placement/flow unless a later contract explicitly reclassifies them |
| `style:horizontal-pos` / `style:horizontal-rel` | no | object placement |
| `style:vertical-pos` / `style:vertical-rel` | no | object placement |
| `svg:width` / `svg:height` as object size | no | geometry |
| `svg:x` / `svg:y` | no | geometry/placement |
| `text:anchor-type` | no | drawing structure/placement |
| `draw:z-index` | no | drawing structure/stacking |
| `draw:name` | no | object identity |
| image `xlink:href` | no | resource reference |
| enhanced geometry | no | drawing structure |
| `align` | no | convenience input only |
| `svg:rx` / `svg:ry` from current frame options | undecided / excluded in C | current mapper mixing is not architecture evidence |
| unknown legacy mapper passthrough keys | no by default | require explicit semantic classification before migration |

This table is intentionally narrower than the set of attributes ODF allows on `style:graphic-properties`.

## 7. DrawTextBox producer contract

`DrawTextBox` is the first migration target.

Its future `getOwnStyleRequirements()` implementation may produce a semantic `graphic` definition from appearance-only frame options.

Included when present and supported:

- background/fill properties;
- border properties;
- padding properties;
- stroke/fill appearance properties explicitly supported by the current API.

Excluded:

- frame name;
- anchor;
- width/height;
- z-index;
- horizontal/vertical placement;
- wrap/flow placement options;
- `rx` / `ry` until separately classified;
- text-box content and child element ownership.

The existing `draw:frame` / `draw:text-box` materialization remains unchanged in SR-06C.

Legacy frame requirement methods remain compatibility facades until SR-06F or a separately approved closeout slice.

## 8. ImageElement producer contract

`ImageElement` MUST NOT reuse its historical image-style hash as semantic identity.

The current legacy image-style identity mixes width, height, anchor, wrapping, and placement. Those values are not sufficient evidence that a normal image owns a semantic appearance style.

For the currently supported normal-image API, SR-06C starts from the conservative rule:

> If no independently classified appearance property remains after structural, geometry, placement, convenience, and resource values are removed, `ImageElement` should produce no owned semantic graphic definition merely to preserve the legacy style registry shape.

This is a valid semantic outcome.

`align` resolution and the current `toDomNode()` state mutation MUST NOT become semantic producer inputs. If future image appearance options are added or identified, only their resolved semantic graphic properties may participate.

The `draw:frame`, `draw:image`, object size, anchor, placement, image href, and physical package bitmap remain outside the semantic requirement.

## 9. CircularImageElement producer contract

`CircularImageElement` remains a convenience element, not a new native ODF taxonomy.

Its future semantic graphic producer may own the bitmap-fill graphic style containing the currently required style semantics, including where applicable:

- `draw:fill = bitmap`;
- `draw:fill-image-name` referencing the named fill-image declaration;
- bitmap fill width/height semantics;
- repeat/stretch semantics;
- `draw:stroke = none`.

Excluded:

- custom-shape width/height;
- anchor;
- z-index;
- enhanced ellipse geometry;
- physical image href/path;
- package copying/manifest state;
- the `draw:fill-image` declaration itself.

The producer MUST be able to derive its semantic graphic style deterministically before `toDomNode()` materialization. Semantic discovery MUST NOT depend on `toDomNode()` having mutated producer state first.

The named fill-image declaration is a dependency of the graphic style and belongs to SR-06E. C may expose/reference the name required by the style, but MUST NOT introduce a semantic `fill-image` style family.

## 10. Style-name contract during C

SR-06C separates semantic identity from legacy reference identity.

Until SR-06D decides document-local resolution/materialization:

- semantic producer identity MUST follow this contract;
- existing legacy generated names and `draw:style-name` behavior remain unchanged unless a minimal compatibility adaptation is separately justified;
- C MUST NOT force DOM nodes to reference newly generated semantic names merely because semantic requirements now exist;
- C MUST NOT move existing graphic definitions between document parts.

This prevents producer migration from accidentally becoming materializer migration.

## 11. Tests required by C.1

C.1 adds durable tests for the semantic infrastructure assumptions used by C.2-C.4:

1. a `graphic` StyleRequirement can carry a `graphic-properties` group;
2. equivalent graphic definitions are idempotent in `StyleContext`;
3. the same semantic identity with a different graphic definition conflicts deterministically;
4. structure/placement values that are not present in the StyleRequirement cannot affect semantic registration identity;
5. the tests do not claim that current drawing elements already produce semantic requirements.

Producer-specific executable tests are intentionally deferred to the implementation slice that changes each producer:

- C.2: `DrawTextBox`;
- C.3: `ImageElement`;
- C.4: `CircularImageElement`.

Those tests MUST assert the property exclusions in sections 7-9 directly against the producer output.

## 12. Non-goals

SR-06C.1 does not:

- change `DrawTextBox`, `ImageElement`, or `CircularImageElement` production behavior;
- change `StyleMapper` mapping behavior;
- change DOM serialization;
- add graphic materialization to `StyleRequirementMaterializer`;
- add fill-image dependency objects;
- change physical resource handling;
- remove legacy registries or compatibility methods;
- fix `CircularImageElement` rendering;
- classify `rx` / `ry` beyond excluding them from C producer semantics;
- resume D5F or SR-07 work.

## 13. Acceptance criteria

SR-06C.1 is complete when:

1. this producer semantics contract is committed;
2. the approved property/identity boundary is explicit for all three active producers;
3. durable semantic graphic infrastructure tests pass without changing production drawing behavior;
4. SR-06B characterization remains unchanged and passing;
5. no drawing producer emits new semantic requirements yet;
6. C.2 can implement `DrawTextBox` from this contract without reopening the semantic boundary.

# SR-06 — Semantic Graphic Style Requirements Change Contract

Status: proposed architecture contract

Branch: `architecture/sr-06a-graphic-drawing-audit`

Depends on:

- `STYLE_REQUIREMENT_CHANGE_CONTRACT.md`
- `STYLE_REQUIREMENT_SR05_FONT_DEPENDENCIES_CHANGE_CONTRACT.md`
- `SR-06A_GRAPHIC_DRAWING_SEMANTICS_AUDIT.md`
- D5 semantic ownership contract

## 1. Purpose

SR-06 migrates graphic style definitions and references from the historical `frame` / `image` requirement paths to the semantic `StyleRequirement` model.

The slice is deliberately narrower than a redesign of the drawing element model.

SR-06 must establish the correct semantic boundary between:

- native drawing structure;
- placement and geometry;
- ODF `style:family="graphic"` definitions and references;
- fill-image dependencies;
- physical image resources;
- legacy compatibility behavior.

The migration must not make the current PHP class hierarchy the long-term drawing model by accident.

## 2. Architectural findings accepted as input

The SR-06A audit establishes the following working facts.

### 2.1 Historical engine families are not ODF style families

The current legacy collector distinguishes `frame`, `image`, and `fill-image` requirements.

For semantic style ownership this distinction is misleading:

- frame-related and image-related appearance ultimately uses ODF `style:family="graphic"`;
- `fill-image` is a separate drawing declaration/dependency, not a peer style family.

SR-06 therefore MUST NOT introduce semantic style families named `frame` or `image` merely to mirror the legacy implementation.

### 2.2 Drawing structure and graphic style are different semantic channels

A drawing object may contain structural semantics such as:

- `draw:frame`;
- `draw:image`;
- `draw:text-box`;
- `draw:custom-shape`;
- `draw:enhanced-geometry`;
- object identity;
- anchor;
- size;
- coordinates;
- z-index.

Those semantics are not graphic style definitions merely because the current option arrays or mappers mix them with style properties.

### 2.3 Resources are not styles

A physical bitmap in `Pictures/` and its package/manifest lifecycle belong to `OdtPackage` / structured resource handling, not to `StyleContext`.

A named `draw:fill-image` declaration may be a dependency of a graphic style, but it remains distinct from the physical package resource.

### 2.4 Existing native drawing representation is valuable

LibreOffice-converted Word templates demonstrate real `draw:custom-shape` structures using enhanced geometry such as `ooxml-rect`, `ooxml-ellipse`, `ooxml-straightConnector1`, and `ooxml-non-primitive`.

A visible concept such as a text box or image is therefore not guaranteed to have one unique native ODF representation.

Future structured manipulation should preserve an existing native ODF drawing representation unless the requested operation requires replacing that representation.

This preservation principle is architecture guidance for future drawing work. SR-06 MUST NOT introduce transformations that normalize existing custom shapes into engine-specific frame/image structures.

## 3. Scope

SR-06 covers only semantic graphic style requirements and the minimum dependency handling necessary to materialize them correctly.

In scope:

1. semantic representation of ODF graphic style definitions/references;
2. document-local collection and conflict handling through `StyleContext`;
3. migration of active `DrawTextBox`, `ImageElement`, and `CircularImageElement` graphic style production where semantically valid;
4. separation of true graphic properties from structural/convenience values currently present in legacy option maps;
5. semantic dependency discovery for named fill-image declarations required by graphic styles;
6. document-part-correct materialization of graphic styles and fill-image declarations;
7. bounded compatibility bridges for legacy APIs and direct `StyleWriter` behavior;
8. characterization and regression tests protecting existing rendering and lifecycle behavior.

## 4. Explicit non-goals

SR-06 MUST NOT:

- redesign the public drawing API;
- replace `DrawTextBox`, `ImageElement`, or `CircularImageElement` with a new class hierarchy;
- introduce a public `CustomShape` API;
- introduce a `DrawingObject` base class merely for taxonomy;
- solve general frame layout or positioning (`FRAME-LAYOUT-01`);
- redesign enhanced geometry;
- implement Word/DOCX parsing;
- implement general ODT import or round-trip editing;
- normalize Word-converted custom shapes;
- fix the known `CircularImageElement` blue-circle rendering bug unless a separately approved fix is required to preserve existing SR-06 semantics;
- redesign physical image resource ownership already assigned to `OdtPackage`;
- migrate table/table-cell style requirements (`SR-07`);
- resume D5F lifecycle integration;
- implement document-wide style defaults.

## 5. Semantic graphic style requirement

A semantic graphic style requirement MUST use the established `StyleRequirement` model rather than a new parallel requirement abstraction.

Conceptually it represents:

```text
Style Requirement
├── kind: definition | reference
├── scope: common | automatic
├── family: graphic
├── documentPart: content.xml | styles.xml
├── optional parent style
├── typed graphic property group
└── explicit dependencies
```

The exact PHP construction API may follow the existing StyleRequirement conventions, but SR-06 MUST preserve these semantics.

### 5.1 ODF family

The semantic family MUST be `graphic`.

Historical roles such as `frame` and `image` may remain in compatibility code during migration, but MUST NOT become new semantic ODF families.

### 5.2 Definition versus reference

An element may:

- define a graphic style requirement;
- reference an existing named graphic style;
- do both through dependency/resolution semantics already established by the style architecture.

A style reference MUST NOT force an unnecessary duplicate definition when the target document already provides the required style.

### 5.3 Placement

SR-06 MUST preserve the existing document-part placement semantics of graphic style definitions.

The migration MUST NOT move a style between `content.xml` and `styles.xml` merely because it is automatic or because a new materializer is more convenient.

Existing declarations in the target document remain authoritative under the same general rules established by SR-1 through SR-5.

## 6. Property boundary

SR-06 MUST classify producer input before constructing semantic graphic style requirements.

### 6.1 Graphic style properties

Properties that are genuinely part of `style:graphic-properties` may participate in semantic graphic style identity and materialization. Examples include, where valid for the relevant ODF context:

- fill semantics;
- fill color;
- stroke semantics;
- border/padding semantics;
- wrap and other properties verified as graphic properties;
- bitmap-fill reference properties;
- flow/overlap properties where verified as graphic properties.

The exact supported set MUST be grounded in ODF/LibreOffice semantics and characterization tests rather than inferred from historical mapper placement.

### 6.2 Structural drawing attributes

Structural values MUST NOT become graphic style properties solely because legacy arrays currently contain them. Examples include:

- `text:anchor-type`;
- `svg:width` / `svg:height` when used as object geometry;
- `svg:x` / `svg:y`;
- `draw:z-index`;
- `draw:name`;
- image `xlink:href`;
- enhanced geometry.

These values remain owned by drawing/materialization semantics outside the semantic graphic requirement.

### 6.3 Convenience input

Convenience options such as `align` MAY continue to exist in the public/legacy API, but MUST be resolved into native semantics before semantic requirement identity is calculated.

Convenience-only keys MUST NOT be serialized as ODF style properties.

### 6.4 Style identity

Semantic graphic style identity MUST be derived only from semantic style definition data and relevant definition dependencies.

Object-specific structure such as image dimensions, coordinates, names, anchor, or physical filename MUST NOT create a distinct graphic style unless ODF semantics actually require that information in the style definition.

Existing legacy style names and rendering behavior must be characterized before changing generated names where compatibility could be observable.

## 7. Producer contract

Active structured elements MUST eventually expose their own semantic graphic requirements through `getOwnStyleRequirements()` or the equivalent established semantic producer hook.

Transitive collection remains the responsibility of `StyleRequirementCollector::collectSemantic()` through `ownedElements()`.

`OdtTemplate` MUST NOT gain concrete-type traversal for graphic producers.

### 7.1 DrawTextBox

`DrawTextBox` may semantically produce a `graphic` style requirement for its appearance.

Its frame structure, placement, identity, and text-box content remain outside that requirement.

Legacy `getOwnFrameStyleRequirements()` / registration behavior may remain as a bounded compatibility facade while migration is incomplete.

### 7.2 ImageElement

`ImageElement` may semantically produce a `graphic` style requirement only from properties that genuinely belong to the graphic style definition.

Its `draw:frame`, `draw:image`, size, anchor, coordinates, image href, and physical bitmap remain separate concerns.

SR-06 MUST remove the architectural need for semantic graphic style discovery to depend on mutation performed inside `toDomNode()`.

If current rendering depends on such mutation, characterization tests MUST be written before restructuring the producer path.

### 7.3 CircularImageElement

`CircularImageElement` originated as a convenience feature for rendering circular application/CV photographs. It MUST NOT be treated as evidence that `CircularImage` is a fundamental native ODF element type.

Its current native structure consists of a custom shape, enhanced ellipse geometry, graphic bitmap-fill style, fill-image declaration, and physical image resource.

SR-06 may migrate only the graphic style and required fill-image dependency semantics.

The custom-shape geometry and future generalized CustomShape model remain outside SR-06.

## 8. Fill-image dependency contract

A graphic style using a bitmap fill may depend on a named `draw:fill-image` declaration.

SR-06 MUST model this dependency explicitly enough that materialization does not depend on hidden process-global registration order.

The following concerns remain distinct:

```text
Graphic Style
    │
    └── references named FillImage declaration
                         │
                         └── references package bitmap resource
```

Requirements:

1. fill-image dependency state is document-local;
2. same logical declaration may be registered idempotently;
3. same declaration identity with incompatible definitions MUST produce a deterministic conflict rather than silent first/last wins in the semantic path;
4. an existing compatible target-document declaration is authoritative and should be reused;
5. materialization must occur in the ODF-valid document part/location;
6. physical bitmap copying and manifest handling remain package/resource responsibilities;
7. SR-06 MUST NOT introduce a global current document or new static semantic registry.

The implementation MAY use a dedicated document-local dependency representation rather than pretending `draw:fill-image` is itself a `StyleRequirement` with family `fill-image`.

## 9. Materialization contract

Semantic graphic styles MUST be materialized by the semantic style materialization path or a narrowly scoped collaborator consistent with that architecture.

The materializer MUST:

- create/update only the required style definitions;
- emit `style:family="graphic"`;
- emit verified graphic properties under the correct property group;
- preserve parent style semantics where applicable;
- respect document-part placement;
- avoid duplicate equivalent definitions;
- reject incompatible same-identity pending definitions deterministically;
- leave structural drawing nodes untouched.

Fill-image declaration materialization MAY use a dedicated collaborator because `draw:fill-image` is not a `style:style` family.

`StyleRequirementMaterializer` MUST NOT become a generic God materializer for resources, geometry, frames, or package assets.

## 10. StyleContext ownership

Semantic graphic requirement and dependency state belongs to the current `OdtDocumentContext` through document-local services/state.

Lifecycle requirements follow STYLE-CONTEXT-01:

- `load()` / core-document replacement reset pending state for the new document;
- repeated `render()` / `save()` must not leak graphic requirements between documents;
- one template instance must not contaminate another;
- repeated equivalent registration is idempotent;
- conflicts are deterministic;
- no constructor reset may be used as a substitute for correct ownership.

## 11. Compatibility contract

Backward compatibility remains mandatory for the migration slice.

SR-06 MUST preserve, unless separately approved:

- public constructors and fluent APIs of existing drawing elements;
- existing `setElement()` behavior;
- explicit legacy `setValuesInDom()` behavior;
- protected compatibility surfaces where external subclasses may rely on them;
- direct `StyleWriter` compatibility behavior;
- repeated render/save behavior;
- existing content.xml/styles.xml placement semantics;
- package image handling;
- existing samples and templates.

Legacy frame/image/fill-image registries and facade methods may remain temporarily where required, but normal semantic `setElement()` processing should progressively cease depending on them.

Compatibility bridges MUST be explicit and bounded; they MUST NOT be presented as the new semantic architecture.

## 12. Native drawing preservation principle

SR-06 establishes the following forward architecture constraint:

> Existing native ODF drawing representation should be preserved unless the requested operation requires replacing that representation.

For example, a future operation that replaces the bitmap used by a Word-converted circular photograph should aim to preserve:

```text
existing CustomShape
existing Geometry
existing Placement
existing Graphic Style
        │
        └── replace only bitmap/resource dependency
```

SR-06 does not implement this operation, but MUST NOT introduce assumptions that would prevent it later.

In particular:

- a visible text box must not be assumed to always be `draw:frame` + `draw:text-box`;
- a visible image must not be assumed to always be `draw:frame` + `draw:image`;
- `CircularImageElement` must not define the future native CustomShape model;
- OOXML-derived enhanced geometry must remain representable/preservable by future structured work.

## 13. Required characterization before migration

Before changing each producer/materializer path, tests MUST capture the current relevant behavior.

At minimum characterize:

1. DrawTextBox graphic style definition/reference and placement;
2. DrawTextBox structural frame attributes remain structural;
3. ImageElement style identity inputs and current pre/post-materialization behavior;
4. ImageElement resource collection remains independent of style collection;
5. CircularImageElement custom-shape structure remains unchanged by SR-06;
6. CircularImageElement bitmap-fill graphic style dependency;
7. fill-image declaration materialization;
8. nested/transitive graphic requirement collection through D5 ownership;
9. same-definition idempotence;
10. same-name/different-definition conflict behavior in the semantic path;
11. repeated render/save lifecycle isolation;
12. legacy direct StyleWriter / setValuesInDom compatibility.

Where current behavior is semantically wrong but externally observable, characterize and document it before any separately approved behavior fix.

## 14. Proposed implementation slices

The following sequence keeps refactoring and behavior changes small.

### SR-06B — Characterization and semantic property boundary

- add focused characterization tests;
- codify graphic-vs-structural property classification;
- no production behavior redesign beyond test-enabling changes.

### SR-06C — Semantic graphic producers

- add `graphic` StyleRequirement production to active drawing elements;
- keep legacy producer facades during migration;
- ensure semantic requirement identity excludes structural/convenience-only data.

### SR-06D — Document-local graphic resolution/materialization

- register/resolve graphic requirements through StyleContext;
- materialize through semantic path;
- preserve target-document declarations and placement semantics.

### SR-06E — Fill-image dependencies

- introduce document-local fill-image dependency handling;
- materialize named fill-image declarations without global registration-order dependence;
- keep package bitmap resources separate.

### SR-06F — Compatibility closeout and legacy-path reduction

- remove normal `setElement()` dependence on legacy frame/image/fill-image registries where proven safe;
- retain bounded legacy bridges required by public/protected compatibility;
- verify no D5F/SR-07 work is pulled forward.

### SR-06G — Full preflight and visual regression

- focused tests;
- full `composer test`;
- PHP lint for `src/` and `tests/`;
- `composer validate` if relevant;
- `git diff --check`;
- documentation build if relevant;
- LibreOffice visual regression for all rendering-relevant affected samples;
- compare entire rendered output against known-good baselines, not only the changed feature.

## 15. Visual regression requirements

Because SR-06 affects drawing/style materialization, visual LibreOffice review is mandatory before final GO.

The gate must inspect all pages of affected samples against known-good baselines.

At minimum include representative cases for:

- text box/frame styling;
- normal image frame;
- circular image/custom-shape bitmap fill;
- CV/template cases using transitive drawing requirements;
- Samples 23/24/25 where affected by the implementation path.

The known CircularImage blue-circle behavior is pre-existing and is not automatically an SR-06 regression. Baseline comparison must distinguish pre-existing behavior from new change.

Automated XML assertions and successful LibreOffice opening do not replace visual comparison.

## 16. Exit criteria

SR-06 is complete only when all of the following are true:

1. active normal structured insertion uses semantic `graphic` StyleRequirements for graphic style definitions/references;
2. semantic graphic requirements are document-local;
3. transitive collection uses D5 semantic ownership traversal;
4. structural drawing attributes are not misclassified as graphic style properties;
5. semantic style identity excludes convenience-only and unrelated object/resource data;
6. fill-image dependencies are document-local and deterministic;
7. physical bitmap/package ownership remains in OdtPackage/resource handling;
8. normal `setElement()` no longer requires legacy global graphic registries for migrated cases;
9. required legacy compatibility paths remain functional and explicit;
10. existing drawing structure is not normalized or rewritten as a side effect of style migration;
11. focused and full automated validation passes;
12. required visual regressions pass against known-good baselines;
13. D5F remains paused until SR-07 and the subsequent reassessment/sequence defined by the roadmap.

## 17. Decisions intentionally deferred

The following remain future design questions:

- public `CustomShape` API;
- native Frame abstraction;
- whether Image/TextBox become native compositional child objects;
- geometry value-object/class design;
- generalized bitmap/gradient/hatch fill API;
- named drawing-object operations;
- Word/LibreOffice custom-shape round-trip editing;
- frame positioning/layout API;
- conversion of current convenience elements into factories/builders over a native model;
- document-wide style defaults.

These decisions MUST NOT be smuggled into SR-06 implementation.

## 18. Architecture principle carried forward

SR-06 adopts the following principle for subsequent drawing work:

> Native ODF drawing semantics are the stable representation boundary. Convenience APIs may construct or manipulate that representation, but must not become a competing document model.

Combined with D5:

> Elements retain meaningful ODF semantics; document/package context owns document-wide materialization of style and resource dependencies.

This contract therefore modernizes graphic style ownership without prematurely deciding the final drawing class hierarchy.

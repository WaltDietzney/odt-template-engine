# SR-06C.2 — DrawTextBox Semantic Graphic Producer

Status: implementation slice

Branch: `architecture/sr-06c2-draw-textbox-semantic-producer`

Depends on:

- `SR-06_SEMANTIC_GRAPHIC_STYLE_REQUIREMENTS_CHANGE_CONTRACT.md`
- `SR-06C1_GRAPHIC_PRODUCER_SEMANTICS_CONTRACT.md`
- SR-06B graphic/drawing boundary characterization

## 1. Purpose

SR-06C.2 migrates `DrawTextBox` into the semantic style producer model without changing its drawing structure, public API, placement behavior, or legacy rendering path.

The slice establishes the first active `StyleRequirement(family = graphic)` producer.

SR-06D remains responsible for semantic graphic resolution and materialization.

## 2. Producer behavior

`DrawTextBox::getOwnStyleRequirements()` produces a common graphic style definition in `styles.xml` only when the text box owns approved semantic graphic appearance properties.

The requirement uses:

```text
kind: definition
scope: common
family: graphic
documentPart: styles.xml
parentStyleName: Frame
property group: style:graphic-properties
```

The semantic style name is generated from the projected semantic graphic properties rather than from the complete legacy frame option map.

Therefore two text boxes with the same appearance but different structure or placement may share the same semantic graphic identity while still retaining different legacy frame style names during migration.

## 3. Approved DrawTextBox projection

Included in semantic graphic appearance where present:

- `fo:background-color`;
- `draw:fill`;
- `draw:fill-color`;
- `draw:stroke` and supported stroke properties;
- border properties;
- padding properties;
- bitmap-fill style reference/sizing properties already classified as style semantics by SR-06C.1.

Excluded from the semantic requirement:

- width and height;
- anchor;
- horizontal and vertical position/relation;
- object identity/name;
- z-index;
- overlap and flow placement state;
- `rx` / `ry`, whose future ownership remains intentionally undecided;
- convenience or unknown mapper output not explicitly classified as semantic graphic appearance.

A text box containing only structure/placement options produces no semantic graphic definition. The migration does not invent an empty style merely to reproduce the legacy frame registry shape.

## 4. Legacy compatibility

The following behavior remains authoritative in SR-06C.2:

- `registerFrameStyle()` continues to derive the legacy style name from the historical mapped frame options;
- `getOwnFrameStyleRequirements()` remains available;
- `getStyleDefinitions()` remains available;
- `toStyleDomNode()` remains unchanged in semantic responsibility;
- `toDomNode()` continues to reference the legacy frame style name;
- placement and drawing attributes remain emitted exactly through the existing drawing path;
- `registerStyles()` continues to serve the legacy compatibility path.

No public constructor or fluent API is changed.

## 5. Temporary materialization bridge

The existing `StyleRequirementMaterializer` currently supports only paragraph and text styles and deliberately rejects unsupported families.

Once `DrawTextBox` starts producing a semantic graphic requirement, normal `OdtTemplate::setElement()` collection registers that requirement in the document-local `StyleContext`. Passing it immediately to the current materializer would fail before SR-06D has implemented graphic materialization.

SR-06C.2 therefore introduces a narrowly bounded transition rule:

> Semantic `graphic` requirements may be registered during SR-06C, but `StyleRequirementMaterializer` leaves them inert until SR-06D.

This is not graphic materialization. The legacy graphic path remains responsible for rendered output.

SR-06D must replace this temporary inert handling with actual document-local graphic resolution/materialization and must review whether the transition branch can then be removed.

Unknown semantic families other than `graphic` continue to be rejected.

## 6. C.1 namespace correction

The established semantic materializer convention uses qualified ODF property-group names such as `style:paragraph-properties` and `style:text-properties`.

Accordingly the graphic group is `style:graphic-properties`.

The C.1 contract test initially used the conceptual shorthand `graphic-properties`; C.2 corrects that test fixture to the qualified semantic representation. This is a representation correction, not a change to the approved C.1 ownership semantics.

## 7. Tests

SR-06C.2 tests must prove:

1. `DrawTextBox` emits a semantic graphic definition from approved appearance properties;
2. structure, placement, overlap/flow, and unresolved geometry do not enter the semantic property group;
3. same appearance plus different drawing placement/geometry yields the same semantic graphic style name;
4. the corresponding legacy frame identities may still differ;
5. a structure-only text box produces no semantic graphic definition;
6. normal `setElement()` registers the semantic graphic requirement without attempting SR-06D materialization;
7. the rendered `draw:frame` still references the legacy frame style during this slice.

## 8. Non-goals

SR-06C.2 does not:

- migrate `ImageElement`;
- migrate `CircularImageElement`;
- materialize semantic graphic styles;
- resolve semantic graphic references against target-document styles;
- migrate fill-image declarations;
- change physical resource handling;
- remove legacy frame style collection/registration;
- redesign drawing classes or frame layout;
- decide `rx` / `ry` ownership;
- change visual rendering intentionally.

## 9. Exit condition

SR-06C.2 is ready for GO when focused tests and the relevant integration suite pass, source/test lint is clean, the diff contains no unintended rendering/lifecycle changes, and the legacy `DrawTextBox` output remains compatible.

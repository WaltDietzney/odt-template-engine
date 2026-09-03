# SR-06A — Graphic and Drawing Semantics Audit

Status: Architecture audit / design notes  
Milestone: SR-06 — Semantic Graphic Style Requirements  
Base: `develop` after SR-05  
Scope: Analysis only. This document is not a Change Contract and does not approve a public API or implementation model.

## 1. Purpose

SR-06 migrates the remaining graphic-style path from historical `frame`, `image`, and `fill-image` requirement channels toward the semantic style requirement model established by SR-01 through SR-05.

Before changing that path, SR-06A examines a broader issue exposed by the current implementation: graphic style semantics, drawing-object structure, placement, and physical resources are currently mixed inside several element classes and compatibility paths.

The audit therefore asks two related questions:

1. What belongs to the semantic `style:family="graphic"` requirement model?
2. What belongs instead to the native ODF drawing-object model and must not be absorbed into style requirements?

A secondary design question is whether the current PHP element classes represent durable native ODF concepts or historical convenience APIs.

The analysis follows the project rule **semantics before implementation**.

## 2. Current implementation inventory

### 2.1 `DrawTextBox`

`DrawTextBox` currently combines several responsibilities:

- creation of `draw:frame`;
- creation of `draw:text-box`;
- frame identity (`draw:name`);
- anchor, size, z-index, and placement;
- generation and reference of a graphic style;
- ownership and materialization of structured text content;
- legacy frame-style requirement production and registration.

Its native structure is approximately:

```text
[text:p wrapper when required]
└── draw:frame
    ├── draw:style-name
    ├── text:anchor-type
    ├── size / placement
    └── draw:text-box
        └── owned OdtElement content
```

The historical engine requirement name `frame` does not describe an ODF style family. The emitted style is `style:family="graphic"`.

### 2.2 `ImageElement`

`ImageElement` also creates a `draw:frame`, but its content is `draw:image`:

```text
draw:frame
├── draw:style-name
├── text:anchor-type
├── size / placement
└── draw:image
    └── xlink:href -> Pictures/...
```

The class combines:

- frame structure and placement;
- image resource identity;
- package asset discovery;
- convenience alignment semantics;
- historical `image` style requirements.

The current `imageOptions` structure contains both style-like properties and structural values such as size and anchor. `toDomNode()` also derives placement/wrap attributes and writes some of those values back into `imageOptions`. Consequently, the historical image-style requirement is partly coupled to materialization order.

This is transitional behavior to characterize and preserve during SR-06; it is not a target semantic model.

### 2.3 `CircularImageElement`

`CircularImageElement` was created for a concrete product requirement: **displaying application/CV photographs as circular images**.

It was not originally introduced as a general Custom Shape architecture.

Nevertheless, its implementation is structurally important because it does not emit the same ODF structure as `ImageElement`. Instead it creates:

```text
draw:custom-shape
├── draw:style-name
├── text:anchor-type
├── size / z-index
└── draw:enhanced-geometry
    └── ellipse geometry
```

The referenced graphic style uses a bitmap fill and depends on a named `draw:fill-image`, which in turn depends on the physical bitmap resource in the package.

Conceptually, the current implementation therefore performs:

```text
Circular application photo
    = Custom Shape
    + ellipse geometry
    + graphic style with bitmap fill
    + fill-image declaration
    + package bitmap resource
```

This makes `CircularImageElement` an important characterization case, but not evidence that `CircularImageElement` itself is a fundamental native element type.

## 3. Current requirement pipeline

The current graphic path intentionally spans two architectures.

`StyleRequirementCollector::collectSemantic()` traverses semantic ownership and collects `getOwnStyleRequirements()`. The source explicitly retains the older `collect()` path for graphic and compatibility families until those families receive semantic producer contracts.

The legacy collector distinguishes:

```text
paragraph
text
frame
image
fill-image
```

For SR-06, the important observation is:

```text
historical engine role        native ODF concept
------------------------------------------------------------
frame                     -> style:family="graphic"
image                     -> graphic style + drawing structure
fill-image                -> draw:fill-image dependency/declaration
```

`frame` and `image` must therefore not automatically become new semantic style families. `fill-image` is not a normal `style:style` family at all.

`OdtTemplate::setElement()` currently orchestrates both the semantic requirement pipeline and the legacy graphic pipeline. This transitional complexity must not be prematurely removed. D5F remains paused until the graphic and table-related semantic migrations are sufficiently complete.

## 4. Semantic channels

SR-06A distinguishes four channels that are currently partially mixed:

```text
Drawing Object
├── Structural semantics
│   ├── frame
│   ├── image
│   ├── text-box
│   ├── custom-shape
│   └── enhanced geometry
│
├── Placement / geometry
│   ├── anchor
│   ├── size
│   ├── x / y
│   ├── positioning relations
│   └── z-index
│
├── Graphic appearance
│   ├── fill
│   ├── stroke
│   ├── border
│   ├── padding
│   ├── wrap
│   └── related graphic properties
│
└── Dependencies / resources
    ├── bitmap
    ├── fill-image declaration
    └── package resource
```

The existence of a value in a current `frameOptions` or `imageOptions` array does not prove that the value belongs to a graphic style requirement.

## 5. Property classification

The following classification is the current SR-06A working result. Exact placement must remain grounded in ODF/LibreOffice reference cases where ambiguity exists.

| Current concept | Semantic level | SR-06 treatment |
| --- | --- | --- |
| `draw:style-name` | style reference on drawing object | semantic style reference |
| `style:family="graphic"` | graphic style definition | core SR-06 |
| `draw:fill` | graphic property | core SR-06 |
| `draw:fill-color` | graphic property | core SR-06 |
| `draw:stroke` | graphic property | core SR-06 |
| `draw:fill-image-name` | graphic property with dependency | SR-06 + dependency handling |
| border / padding graphic properties | graphic appearance | SR-06 where valid for the object/style |
| `style:wrap` | graphic/layout-related style property | SR-06, preserve ODF semantics |
| horizontal/vertical position relations | placement/style boundary | preserve and verify against reference fixtures |
| `text:anchor-type` | structural drawing-object attribute | not a graphic style requirement |
| `svg:width`, `svg:height` | object geometry | not a graphic style requirement |
| `svg:x`, `svg:y` | object placement | not a graphic style requirement |
| `draw:z-index` | drawing-object placement/order | not a graphic style requirement |
| `draw:name` | object identity | not a graphic style requirement |
| `xlink:href` | resource reference | not a graphic style requirement |
| `draw:fill-image` | drawing dependency/declaration | separate from `style:style` |
| `draw:enhanced-geometry` | Custom Shape geometry | drawing model, not graphic style |
| convenience `align` | public convenience input | must be translated, not preserved as native semantic state |

A key characterization concern follows: style identity must not silently continue to depend on unrelated structural values merely because historical option arrays mix the channels.

## 6. Existing drawing structures

The current engine already demonstrates two important native structures.

### 6.1 Frame composition

Both `DrawTextBox` and `ImageElement` independently implement substantial `draw:frame` semantics:

```text
Frame
├── placement
├── graphic style reference
└── content
    ├── draw:text-box
    └── draw:image
```

This is evidence of duplicated native structure. It is not yet a decision that a public or abstract PHP `Frame` class must be introduced.

### 6.2 Custom Shape composition

`CircularImageElement` demonstrates another native structure:

```text
CustomShape
├── geometry
├── placement
├── graphic style reference
└── dependencies through graphic style
```

A bitmap-filled ellipse is one configuration of this structure, not necessarily a fundamental element category of its own.

## 7. Word-to-ODT empirical countercheck

SR-06A was additionally checked against three real CV templates that originated as Word documents and were converted to ODT through LibreOffice. These files are used as empirical architecture evidence, not as normative ODF specification.

The converted templates contain extensive `draw:custom-shape` / `draw:enhanced-geometry` structures. Observed geometry types include:

- `ooxml-rect`;
- `ooxml-ellipse`;
- `ooxml-straightConnector1`;
- `ooxml-non-primitive`.

This matters because the Custom Shape structures are not irrelevant import residue. They represent visible layout objects in professional CV templates, including text-bearing rectangles, lines/connectors, and an application photograph represented through an ellipse-shaped drawing object.

The empirical cases show that a visually perceived concept is not necessarily tied to one ODF structure:

```text
Visual "text box"
├── draw:frame -> draw:text-box
└── text-bearing draw:custom-shape

Visual "image"
├── draw:frame -> draw:image
└── bitmap-filled draw:custom-shape

Circular application photo
└── draw:custom-shape
    + ellipse geometry
    + bitmap fill

Visual line
└── draw:custom-shape
    + connector geometry
```

This is an important constraint for the long-term structured element model.

## 8. Custom Shape design hypothesis

The empirical Word/LibreOffice cases strengthen the following native semantic hypothesis:

```text
CustomShape
├── identity
├── geometry
├── placement
├── graphic style reference
├── optional text style reference
└── optional structured text content
```

Dependencies such as bitmap fills belong to the referenced graphic style/resource channels rather than being intrinsic Custom Shape fields:

```text
CustomShape
    │
    └── graphic style reference
            │
            └── BitmapFill
                ├── fill-image declaration
                └── package bitmap resource
```

`Geometry` must not be reduced prematurely to a small enum such as rectangle/ellipse/line. Word-converted LibreOffice documents demonstrate richer `draw:enhanced-geometry`, including enhanced paths and non-primitive OOXML geometry. A future representation must be capable of preserving native geometry semantics needed for round-trip-safe manipulation.

No PHP API or class decomposition is approved by this hypothesis.

## 9. Native model versus convenience API

SR-06A distinguishes native document semantics from developer-facing convenience.

A future architecture may expose convenient operations such as:

```text
Image convenience
    -> Frame + Image resource reference

TextBox convenience
    -> one appropriate native text-bearing drawing representation

CircularImage convenience
    -> CustomShape + EllipseGeometry + BitmapFill(image)
```

This does **not** imply two competing element systems.

The intended direction is one native structured document model with optional convenience constructors/facades that create or configure that model.

A convenience type should not become a fundamental native type solely because a visible feature has a convenient product name.

Conversely, native ODF distinctions must not be collapsed merely because two structures look similar to the user.

## 10. Preservation principle for existing templates

The Word-converted fixtures expose a critical requirement for future structured manipulation:

> Existing native ODF drawing representation should be preserved unless the requested operation requires replacing that representation.

For example, if an imported text-bearing rectangle is a `draw:custom-shape`, replacing its text should not automatically reconstruct it as `draw:frame` + `draw:text-box`.

Likewise, replacing the bitmap of an existing circular application photograph should ideally preserve:

```text
existing CustomShape
existing Geometry
existing Placement
existing Graphic Style
        │
        └── replace only the bitmap/resource dependency
```

This principle aligns with the long-term named-template-object direction: manipulate the intended part of an existing LibreOffice-designed object instead of rebuilding its layout in PHP.

This is a design constraint, not yet an approved public operation such as `replaceNamedElement()`.

## 11. Minimal native drawing model hypothesis

The smallest currently useful conceptual model is:

```text
Frame
├── placement
├── graphic style reference
└── frame content
    ├── Image
    └── TextBox

CustomShape
├── identity
├── Geometry
├── placement
├── graphic style reference
├── optional text style reference
└── optional structured text content

GraphicStyle
├── native graphic properties
└── dependencies
    └── optional FillImage

Image
└── package resource reference
```

This is intentionally a semantic diagram, not a PHP class diagram.

In particular:

- it does not require a `DrawingObject` base class;
- it does not require every XML element to become a PHP class;
- it does not decide whether placement is a value object, capability, or plain internal representation;
- it does not decide whether `Geometry` is a class hierarchy;
- it does not remove existing convenience APIs;
- it does not redesign imported-object operations.

## 12. Architectural conclusions from SR-06A

The audit currently supports the following conclusions:

1. Historical `frame` and `image` requirement families are engine roles, not ODF style families.
2. Semantic graphic style definitions should converge on ODF `style:family="graphic"` rather than reproducing the historical family split.
3. `fill-image` is a separate dependency/declaration concept and must not be modeled as a peer semantic style family.
4. Drawing structure, placement, graphic appearance, and physical resources are separate semantic channels.
5. The current `DrawTextBox`, `ImageElement`, and `CircularImageElement` classes mix these channels for historical and convenience reasons.
6. `CircularImageElement` is a convenience solution for circular application photos; its internal Custom Shape composition is architecture evidence, not proof that the convenience class is a fundamental type.
7. `draw:custom-shape` is strategically important because real Word-to-ODT conversion uses it for visible professional document structures.
8. Text boxes and images are visual concepts that can have more than one native ODF representation.
9. Existing native drawing structures should be preserved during targeted manipulation whenever the requested operation does not require structural replacement.
10. A future native drawing model should support rich Enhanced Geometry without reducing imported OOXML-derived shapes to a small engine-specific shape enum.
11. Convenience APIs and native structured semantics should be separate layers over one document model, not competing element systems.
12. None of these conclusions authorizes a broad drawing-model refactor inside SR-06.

## 13. Boundary of SR-06

SR-06 remains **Semantic Graphic Style Requirements**.

Its implementation scope should be limited to establishing a clean semantic path for graphic style definitions/references and their immediate dependencies while preserving existing behavior and compatibility.

SR-06 must not silently absorb:

- FRAME-LAYOUT-01;
- a general Custom Shape API;
- a general Drawing Object hierarchy;
- named-object replacement APIs;
- Word/DOCX parsing;
- broad round-trip/import architecture;
- the known CircularImage rendering defect;
- D5F lifecycle cleanup.

The drawing-model findings in this document constrain SR-06 so that the graphic-style migration does not make later native drawing work harder.

## 14. Recommended next architecture step

Before implementation, SR-06 should derive a bounded Change Contract from this audit.

That contract should define at minimum:

- the semantic Graphic Style requirement representation;
- producer responsibilities for existing drawing elements;
- exact separation of structural placement from graphic style properties;
- dependency treatment for `draw:fill-image`;
- compatibility behavior for current `frame` / `image` / `fill-image` paths;
- lifecycle behavior before and after `toDomNode()` where current image requirements depend on materialization;
- characterization tests protecting current public APIs and existing sample behavior;
- explicit non-goals preventing the drawing-model research from turning SR-06 into a rewrite.

The broader native Drawing Object model should remain a documented follow-up architecture topic unless SR-06 implementation requires a narrowly scoped supporting abstraction.

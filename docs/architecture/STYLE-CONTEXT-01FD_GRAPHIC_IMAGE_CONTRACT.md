# STYLE-CONTEXT-01F-D — Graphic/Image Ownership Change Contract

## Status and scope

This document is the semantic contract for the future STYLE-CONTEXT-01F-D
implementation slices. It is based on the D1 characterization at
`44a476f988438a0fc87da96208a8dea7245990f6` and on the ownership/lifecycle
contracts in STYLE-CONTEXT-01B through 01FC.

No implementation is included in this slice. The contract covers:

* frame graphic styles;
* image graphic styles;
* fill-image declarations;
* physical image/package resources;
* transitive structured-element requirements; and
* graphic/image finalization across `save()`, `refresh()`, and `load()`.

It does not cover text, paragraph, table, list, layout, or general asset
architecture beyond the boundaries needed to state this contract.

## 1. Evidence versus decisions

### Inherited D1 evidence

D1 established that `DrawTextBox`, `ImageElement`, and
`CircularImageElement` currently register graphic-related state through static
`StyleMapper` registries. An unattached frame or image can therefore affect an
unrelated document. Image registration is overwrite-by-name. `save()` injects
image styles, whereas `refresh()` currently does not. `load()` does not clear
the global graphic/image registries.

D1 also established that a `DrawTextBox` directly renders its children, but
does not expose all child style and resource requirements at the current
`setElement()` boundary. Nested styled text can therefore reference a style
that is not materialized through the document-aware text path. A nested image
can be written to `content.xml` while its physical asset and manifest entry
are absent because top-level asset preparation is not transitive.

`ImageElement::toDomNode()` currently resolves positioning/wrap values into
`imageOptions`; subsequent materialization is stable. This element-state
mutation is an observed behavior, separate from global ownership.

### Decisions made by 01F-D

The active document-generation path will make graphic/image requirements
document-owned, propagate requirements over an inserted subtree, and finalize
only the current document's requirements. Context-free legacy APIs remain a
separate compatibility boundary.

### Deferred implementation details

This contract does not mandate a particular PHP interface, registry shape,
renderer, transaction abstraction, or service class. It does not decide which
existing helper is removed, retained, or made compatibility-only. Those are
implementation decisions for later slices, constrained by this document.

## 2. Ownership model

The ownership contract from STYLE-CONTEXT-01B is extended as follows:

```text
OdtDocumentContext
    └── StyleContext
          ├── paragraph requirements
          ├── text requirements
          ├── frame graphic style requirements
          ├── image graphic style requirements
          └── fill-image declaration requirements
```

These are conceptual families. They need not be stored in one generic array
or exposed through one generic method. Their ODF placement, definition shape,
and conflict rules may differ.

The authoritative owner of mutable requirements used by normal document
generation is the logical document represented by `OdtDocumentContext`.
`OdtPackage` remains responsible for package/workspace mechanics and
`OdtTemplate` remains the public facade and orchestration boundary.

A process-global registry must not determine which document receives a
requirement.

## 3. Physical assets are not styles

A physical image asset is not style state.

The separation is:

```text
Element subtree
    ↓
resource requirements
    ↓
document materialization boundary
    ↓
OdtPackage / package resource preparation
    ↓
Pictures/* and META-INF/manifest.xml
```

`StyleContext` may own the semantic `draw:fill-image` declaration that
references a bitmap, but it does not own the binary file, package path, or
manifest lifecycle. A later asset-specific architecture may define those
details; 01F-D does not create ASSET-CONTEXT.

A successful inserted subtree that references a physical image must result in
both a valid package resource and the corresponding manifest entry. A style
declaration alone is insufficient.

## 4. Native ODF competence remains with elements

The project continues to use:

```text
OdtElement
    ↓
toDomNode()
    ↓
native ODF/XML representation
```

An element remains responsible for knowing how to describe its native ODF
representation and for exposing the requirements needed by that
representation. Composite elements must include the requirements of owned
children.

The document/materialization boundary is responsible for adopting those
requirements into the target `OdtDocumentContext`, preparing package
resources, and coordinating finalization. The finalizer writes requirements
belonging to that current document and does not discover authoritative state
from unrelated global registries.

01F-D does not convert elements into passive DTOs and does not introduce a
central renderer or scene graph.

## 5. Transitive requirement closure

Requirements of an inserted structured element are transitive over its owned
child subtree. For example:

```text
DrawTextBox
    ├── own frame graphic style
    ├── Paragraph
    │     ├── paragraph requirement
    │     └── text style requirements
    └── ImageElement
          ├── image graphic style
          └── physical image asset
```

Inserting the textbox must make the complete closure available to the target
document/package. A child requirement must not disappear because the facade
received only the root object.

This applies to graphic styles, image styles, fill-image declarations,
physical resources, and future families where composition makes the same rule
relevant.

`OdtTemplate` must not gain hard-coded knowledge of every concrete element
class. The element/composite side must expose sufficient information for its
owned closure to be adopted. The exact interface or method shape is deferred
to implementation.

The closure is limited to owned descendants. Visual proximity or arbitrary
document siblings does not establish ownership.

## 6. Construction, mutation, and materialization

For the migrated active document path, constructing or mutating an unattached
`DrawTextBox`, `ImageElement`, or `CircularImageElement` changes no document.

Therefore:

```text
construct element A
construct document B
save B
```

must not emit A's graphic/image requirements into B. Fluent mutations before
assignment remain local to the element until an explicit insertion/adoption
operation.

`toDomNode()` must not make requirements authoritative for unrelated
documents through process-global registration. It may still resolve native
values needed to construct its node.

In particular, the current `ImageElement` mutation of resolved wrap and
position options may remain in 01F-D. Repeated materialization remains stable,
and the document-owned image-style requirement must correspond to the final
definition referenced by the produced ODF node. Whether element materializing
methods become pure belongs to a later element-state/layout decision.

Registration does not have to occur before native materialization if an image
style is only fully known after resolving layout/wrap values. The invariant is
that before finalization the target document owns the complete final
requirement set for the inserted subtree.

## 7. Frame graphic styles

Frame styles produced by `DrawTextBox` become document-owned requirements in
the active path.

Required semantics:

* equivalent same-name definitions are idempotent;
* same-name, different document-owned definitions raise an explicit conflict;
* an unattached textbox affects no document;
* repeated save does not create duplicate serialized declarations; and
* normal document finalization has one authoritative frame-style
  materialization path.

The current combination of static frame registration,
`getStyleDefinitions()`, `toStyleDomNode()`, and `StyleWriter` is an observed
duplicate path. 01F-D removes its necessity for active document generation,
but does not yet decide which individual helper is retained for explicit
legacy compatibility.

## 8. Image graphic styles

Image graphic styles produced by `ImageElement` and circular-image rendering
become document-owned requirements in the active path.

Required semantics:

* equivalent definitions are idempotent;
* same-name, different document-owned definitions conflict explicitly;
* document A's image styles cannot appear in document B;
* save order and interleaving do not affect ownership; and
* repeated save does not accumulate duplicate serialized image-style
  declarations.

The current static image registry may remain available for explicit legacy
compatibility. It must not remain authoritative for normal `OdtTemplate`
finalization.

## 9. Fill-image declarations

`CircularImageElement` is the runtime producer identified for
`StyleMapper::registerFillImage()`.

A fill-image declaration is separate from the referenced bitmap:

```text
draw:fill-image declaration  ≠  Pictures/image.png
```

The active document path must associate the declaration with the target
document and must prepare the referenced physical asset and manifest entry in
that same target package. No process-global fill-image registration may leak
into another document.

The native circular custom-shape representation, geometry, and visual
semantics are not redesigned by 01F-D.

## 10. Duplicate and conflict semantics

For document-owned graphic/image requirements:

```text
same name + equivalent definition
    → idempotent reuse

same name + different definition
    → explicit document-local conflict
```

This aligns with STYLE-CONTEXT-01B and the existing paragraph/text
document-local behavior. Deterministic generated names remain deterministic;
01F-D does not change the public naming algorithm.

Existing template-authored styles in `styles.xml` remain authoritative. The
implementation need not duplicate all authored styles in `StyleContext`; it
may consult the current DOM when materializing pending requirements. Existing
same-name DOM collision behavior must be characterized and preserved unless a
later explicit decision changes it.

The context-free `StyleMapper::registerImageStyle()` overwrite behavior
characterized by D1 is a compatibility exception, not the document-owned
contract.

## 11. Finalization and lifecycle

### `save()`

For migrated families, `save()` finalizes only requirements owned by the
current logical document. It must not import unrelated static graphic/image or
fill-image registrations. Repeated save of an unchanged document is
style-idempotent: no missing declarations, foreign styles, or new duplicates
solely because save ran again. Byte-identical ZIP archives are not required.

### `refresh()`

The public meaning of `refresh()` is not otherwise redesigned. Before its
existing persist/reset lifecycle proceeds, graphic/image finalization uses the
same current-document ownership semantics as `save()`:

```text
refresh()
    → finalize current graphic/image requirements
    → continue existing refresh lifecycle
```

The D1 `save()`/`refresh()` image-style injection asymmetry is therefore
removed for the migrated path, while the subsequent existing `load()`
behavior remains intact.

### `load()`

`load()` remains a document reset boundary. Pending/generated frame styles,
image styles, fill-image declarations, and document-owned resource
requirements from the prior working state must not remain or be implicitly
reapplied after reset. The context object may survive, but its pending state
must match the restored document.

Explicit legacy global registries remain separate compatibility state. Normal
`OdtTemplate` finalization must not import them after load.

### Legacy structured-value path

The existing `assign()`/render path and `setValuesInDom()` must ultimately
produce consistent structured insertion semantics with `setElement()`, while
retaining their public lifecycle behavior. 01F-D does not remove or redesign
these APIs. A bounded later slice may route both paths through the same
requirement-adoption boundary.

## 12. Compatibility boundary

The active path is document-owned:

```text
normal OdtTemplate generation
    → OdtDocumentContext / StyleContext
    → current document/package only
```

The explicit context-free compatibility path may remain global:

```text
StyleMapper / StyleWriter static calls
    → characterized legacy semantics
```

No global current-document pointer, constructor reset, save-order ownership,
load-time global clearing, or guessed static-call ownership is permitted.

Direct calls such as `StyleMapper::registerImageStyle()` followed by direct
`StyleWriter` use may retain overwrite/leakage behavior until separately
migrated. That compatibility path is not the authority for normal document
finalization.

## 13. Required invariants

The later implementation must preserve these invariants:

* **Isolation:** an operation on A cannot change B unless application code
  explicitly transfers an element, data, or requirement.
* **Unattached elements:** construction and pre-assignment mutation change no
  document.
* **Transitive requirements:** inserted subtrees contribute all owned styles,
  declarations, and physical resources.
* **Native competence:** elements continue to create native ODF through
  `toDomNode()`.
* **Finalization:** only current-document requirements are finalized.
* **Repeated save:** graphic/image finalization is idempotent.
* **Lifecycle reset:** `load()` removes pending document-owned graphic/image
  state from the prior working document.
* **No global owner:** no process-wide current-document registry is added.
* **Compatibility:** explicit legacy APIs retain characterized behavior until
  separately migrated.

## 14. Proposed implementation slices

The following sequence is a bounded recommendation, not a mandatory class
design:

### 01F-D3 — Requirement representation

Add the smallest document-owned representation for frame graphic styles,
image graphic styles, and fill-image declarations.

### 01F-D4 — Top-level adoption

Move normal top-level `DrawTextBox`, `ImageElement`, and
`CircularImageElement` insertion to document-owned registration. Stop normal
`OdtTemplate` finalization from treating their legacy registries as
authoritative.

### 01F-D5 — Transitive nested propagation

Expose/adopt child style and resource requirements for nested text, image, and
circular-image structures. Ensure package resources and manifests are prepared
at any supported nesting depth.

### 01F-D6 — Legacy structured-value path

Bring `assign()`/render/`setValuesInDom()` structured materialization onto the
same document-aware adoption boundary without changing public lifecycle
behavior.

### 01F-D7 — Finalization and lifecycle closeout

Unify graphic/image finalization for `save()` and `refresh()`, verify reset on
`load()`, repeated-save idempotency, conflict handling, and cross-document
isolation.

The implementation may combine or rename these slices if evidence supports a
smaller safe change, but it must not broaden into the non-goals below.

## 15. Explicit non-goals

01F-D is not:

* ASSET-CONTEXT;
* FRAME-LAYOUT-01;
* STYLE-API-02;
* table, list, font, text, or paragraph migration;
* named-object architecture;
* a generic renderer, DTO, scene graph, or styling DSL;
* a redesign of image anchor, wrap, sizing, or circular-image rendering;
* a general-purpose `remove()` or `clearSections()` API;
* a redesign of `refresh()` beyond finalization consistency;
* generic orphan resource cleanup; or
* a full resource manager.

No PHP API shape is frozen by this contract beyond the required observable
semantics.

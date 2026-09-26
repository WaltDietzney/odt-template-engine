# ARCH-05C Structured Elements Change Contract

**Status:** Historical pre-implementation change contract; implementation completed through ARCH-05H
**Milestone:** ARCH-05 — Structured elements / structured insertion
**Base branch:** `develop`
**Base HEAD:** `95681ef62b2b9a5d48a825a4406e20000c3f51b5`
**Architecture branch:** `architecture/arch-05-structured-elements`
**Contract commit basis:** `3f841728ebf5d6f3dd58e69c3bcb96fef270d60b`

## Implementation status

This document is the pre-implementation change contract. Its architectural
decisions and non-goals remain historical contract text; it is not rewritten
retrospectively.

The approved implementation is now complete through:

- **ARCH-05D** — structured behavior characterization;
- **ARCH-05E** — `StructuredElementMaterializer` extraction;
- **ARCH-05F** — typed target-resolution foundation;
- **ARCH-05G** — existing named-image integration;
- **ARCH-05H** — structured image resource decoupling through `OdtPackage`.

Independent final review and merge preflight remain the closeout activity.

## 1. Purpose

ARCH-05C converts the ARCH-05A audit and ARCH-05B semantic research into a
small, reviewable implementation contract. The contract separates:

* constructed `OdtElement` materialization;
* textual structured-placeholder insertion;
* typed resolution of existing native ODF template targets; and
* explicit preservation and operation semantics.

It does not implement production code, define final public method names, or
make all researched operations part of the first implementation slice.

## 2. Evidence and prior architecture

This contract is based on:

* [ARCH-05 structured-element design notes](ARCH-05_STRUCTURED_ELEMENTS_DESIGN_NOTES.md);
* [ARCH-05A audit](ARCH-05A_STRUCTURED_ELEMENTS_AUDIT.md);
* [ARCH-05B identity and replacement semantics](ARCH-05B_ELEMENT_IDENTITY_AND_REPLACEMENT_SEMANTICS.md);
* current `AbstractOdtTemplate`, `OdtTemplate`, `OdtPackage`,
  `OdtDocumentContext`, element classes, style utilities, integration tests,
  and public samples.

The current code confirms that `OdtElement` serializes constructed ODF
content. `RichText` can serialize multiple sibling nodes. `setElement()` is
an orchestration boundary for styles, assets, package resources and DOM
insertion. `replaceImageByName()` is an existing frame-based named-object
operation with compatibility-sensitive dimension behavior.

No contradiction requiring a change to the ARCH-05A/B semantic model was
found. One implementation constraint is made explicit below: the first
structured insertion extraction must preserve the current global
`StyleMapper` dependency rather than attempting `STYLE-CONTEXT-01` inside
ARCH-05.

## 3. Established semantic model

### Constructed content versus template targets

`OdtElement`, `RichText`, `Paragraph`, `RichTable`, `RichTableCell`,
`ListElement`, `ImageElement`, and `DrawTextBox` are constructed structured
content. They are not handles to existing authored objects.

A template target is an existing native ODF object addressed by a type-specific
identity mechanism and document region. Examples are:

```text
frame target -> draw:name
table target -> table:name
```

Technical IDs such as `xml:id` and `draw:id` are separate identity/reference
concerns. A universal name XPath is prohibited.

### Distinct operations

The implementation must keep these operations semantically separate:

1. scalar text placeholder replacement;
2. structured placeholder insertion of constructed content;
3. named-target content replacement;
4. named-target whole-object replacement;
5. exact clone;
6. template clone / template instance;
7. structural clone;
8. removal.

Only the first two are existing general structured/template operations. Named
image replacement is the existing specialized native-target primitive.

## 4. Scope

ARCH-05 initially addresses the structured-document boundary around existing
behavior:

* materializing constructed `OdtElement` content against an
  `OdtDocumentContext`;
* inserting structured content at textual placeholders;
* preserving inline versus block insertion semantics;
* coordinating required styles, assets, package files and manifest entries;
* processing the existing `content.xml` and `styles.xml` paths;
* establishing a minimal typed target-resolution seam for future native
  objects;
* characterizing and, only in a later slice, routing existing named image
  replacement through that seam without changing its public behavior.

The first production extraction is behavior-preserving. It is not a redesign
of the structured element classes or ODF layout model.

## 5. Explicit non-goals

The following remain outside ARCH-05 implementation scope:

* new template-language styling syntax;
* `TEMPLATE-FORMAT-PRESERVATION-01` implementation;
* `TEMPLATE-AUTHORING-UX-01` implementation;
* `STYLE-API-02` deprecation work;
* `STYLE-CONTEXT-01` or replacement of static style registries;
* broad style-system or ODF object-model redesign;
* a universal object registry;
* a full DOM wrapper hierarchy;
* arbitrary public XPath APIs;
* generalized exact, template, or structural cloning;
* Template Clone implementation or clone-local `TemplateProcessor` changes;
* page-layout, header/footer, master-page or pagination redesign;
* rewriting active textual foreach semantics;
* arbitrary interchangeability between paragraphs, tables, frames, images and
  other ODF families.

These topics may constrain the design or appear in future contracts, but they
must not expand the first ARCH-05 implementation slice.

## 6. Architectural responsibility boundary

The current `AbstractOdtTemplate` combines public facade behavior with
structured materialization. ARCH-05 should move the document-specific
materialization responsibility behind a small internal collaboration while
leaving public and protected compatibility seams in place.

The selected conceptual split is:

```text
OdtTemplate / AbstractOdtTemplate
    public facade, lifecycle, compatibility dispatch, orchestration
             │
             ├── structured materialization/insertion
             │       constructed OdtElement + document context
             │
             └── typed target resolution
                     existing native ODF identity + region
```

Structured materialization and target resolution are related but not the same
responsibility:

* materialization knows how to serialize constructed content and synchronize
  its dependencies;
* target resolution knows how to locate a typed existing ODF object;
* an operation layer may later coordinate a resolved target with a payload,
  but must not become a general-purpose document God Class.

The first extraction may use one narrowly scoped materialization collaborator
and a smaller resolver seam introduced only when a second target operation
requires it. No speculative hierarchy of target classes is required now.

## 7. Document state and dependency direction

`OdtDocumentContext` remains the owner of the core mutable DOM documents.
`OdtPackage` remains the owner of workspace, package files, manifest
synchronization, ZIP persistence and cleanup.

The structured collaboration may receive:

* an `OdtDocumentContext` for DOM access;
* a narrow package/resource collaborator for image and manifest operations;
* style collection/registration collaborators or the existing compatibility
  path while `StyleMapper` remains process-global.

It must not duplicate the DOMs, package workspace, assignment state,
`valueStack`, `repeatStack`, or static template-language state. It must not
take ownership of `OdtPackage`.

The target resolver should receive a supplied document region/DOM and target
type information rather than search an implicit global document.

## 8. Structured materialization contract

The materialization responsibility must preserve the following behavior of
the current `setElement()` path.

### 8.1 Serialization

* Call `OdtElement::toDomNode()` with the target `DOMDocument`.
* Accept a `DOMDocumentFragment` such as the one produced by `RichText`.
* Preserve child order and deep descendants when inserting fragments.
* Do not treat constructed elements as existing template targets.

### 8.2 Style dependencies

Collect and install the existing categories of requirements:

* text styles;
* paragraph styles;
* table-cell and column styles where current elements provide them;
* graphic/frame style definitions;
* automatic style nodes produced by current element paths.

The extraction must not create a second mutable style registry. Existing
`StyleMapper`/`StyleWriter` coupling remains a documented dependency until
`STYLE-CONTEXT-01`.

### 8.3 Package resources

Collect image assets from constructed elements, copy or replace the package
resource using the current package lifecycle, and preserve manifest
synchronization. A resource reference such as:

```text
xlink:href="Pictures/example.png"
```

is not valid without the corresponding workspace file and manifest entry.

### 8.4 XML regions

Preserve current processing of both:

* `content.xml`;
* `styles.xml`, including existing header/footer or style-owned placeholder
  paths.

The first extraction does not add generalized region traversal, but its
inputs must not hard-code the main body as the only possible region.

### 8.5 Placeholder insertion

Preserve the current distinction:

* inline-compatible replacements are inserted around text-node parts;
* block replacements use the containing `text:p` as the insertion site;
* paragraphs inside `draw:text-box` retain their existing special handling.

The materializer must not silently turn block insertion into arbitrary inline
insertion or reconstruct text in a way that changes current formatting and
surrounding-content behavior.

### 8.6 Existing structured-value path

`setValuesInDom()` currently distinguishes `OdtElement` values from scalar
values and routes structured values through `replacePlaceholderWithDom()`.
That compatibility path must remain supported even if the implementation is
delegated internally.

## 9. Typed target-resolution contract

Target resolution is type-aware and region-aware in concept:

```text
target type
    + native identity mechanism
    + document region
    + matched native node
    + operation capabilities
```

Initial identity rules are:

| Target type | Identity mechanism | Initial status |
|---|---|---|
| frame-backed image/text box | `draw:name` on the frame | existing image evidence; text-box future |
| table | `table:name` | future typed target |
| drawing shape/line | type-specific `draw:name` | research/future |
| paragraph/span/list/RichText | no established native user-name contract | excluded initially |

### Missing targets

New internal target operations should distinguish “not found” from successful
replacement. They must not silently create an object under an absent name.
The exact exception/result policy belongs in the slice-specific contract.
Existing public methods retain their current behavior until characterized;
ARCH-05C does not silently change them.

### Ambiguous targets

The resolver must not arbitrarily select one of multiple matching targets.
The initial implementation should either require a unique match or expose an
explicit, documented region/type scope. Duplicate-name behavior must be
characterized before a public operation depends on it.

### Type mismatch

An image-frame operation matching a `draw:frame` without a nested
`draw:image` is not a valid image target. New internal operations should
report an unsupported target or a typed mismatch rather than mutate unrelated
children. The legacy facade behavior remains a characterization item and is
not redefined by this contract.

### Region awareness

The resolver model must be able to receive body, styles/master-page-owned,
header, footer, or other supported document-region inputs. General traversal
of all regions is deferred; the architecture must not make it impossible.

## 10. Preservation policy

### Default content replacement

The default architectural rule is:

> Preserve the template-owned native container and replace only the defined
> payload.

For an image frame, this means preserving identity, anchor, position, wrap,
graphic style, z-index and dimensions by default, while changing the nested
image resource.

The existing `replaceImageByName()` facade currently writes width and height
values, including its default/option behavior. That behavior is
compatibility-sensitive and must remain available. A future cleaner
content-replacement semantic may distinguish explicit dimension overrides,
but ARCH-05 must not silently redefine the existing method.

For a text-box frame, future content replacement should preserve the outer
frame and replace or rebuild the defined children of `draw:text-box`.

### Whole-object replacement

Whole-object replacement is explicitly destructive. It replaces the selected
native element and does not promise to retain the old container's layout,
identity, styles or resources unless a later operation contract specifies how
those are transferred.

### Tables

Table operations must remain distinct:

* cell-content replacement;
* row rebuilding/repetition while retaining compatible table structure;
* whole-table replacement.

The initial scope does not promise table-target manipulation. A `RichTable`
serialized by `setElement()` is constructed content, not evidence that a
named authored table can be safely rebuilt.

### Cloning

Exact Clone, Template Clone / Template Instance, and Structural Clone remain
future semantic capabilities. Any later implementation must define identity,
technical IDs, nested targets, styles, package resources, references, anchors,
insertion position and clone-local placeholder evaluation before enabling
them.

## 11. Compatibility facade contract

Public APIs remain unchanged. In particular, ARCH-05 extraction must preserve:

* `setElement()`;
* `setImage()`;
* `replaceImageByName()`;
* `assign()` values containing `OdtElement` instances;
* `render()`, `save()`, `load()`, `refresh()` and cleanup lifecycle.

The following protected methods are compatibility-sensitive seams and must
remain callable/overridable where current workflows dispatch through them:

* `setValuesInDom()`;
* `replacePlaceholderWithDom()`;
* `replacePlaceholdersInNode()`;
* `replaceInText()`;
* `renderTextBoxes()`;
* `replaceImageInDom()`;
* `replaceImageInNamedDom()`;
* style registration helpers used by structured insertion.

The preferred strategy is a thin protected wrapper that delegates to an
internal collaborator while preserving historical `$this->method()` dispatch.
No service call may bypass a subclass override accidentally.

`replacePlaceholdersInNode()` and `replaceInText()` also support legacy row
substitution paths. They must not be moved into the structured materializer
merely because they occur near `setValuesInDom()`.

`renderTextBoxes()` remains a facade/document compatibility path until named
text-box content replacement is separately characterized.

## 12. Characterization-test prerequisites

Before behavior-preserving extraction, add tests for current behavior rather
than relying only on final ODT opening.

### Structured insertion

Characterize:

* a `Paragraph` replacement;
* a multi-sibling `RichText` fragment;
* `ListElement` insertion;
* `RichTable`/`RichTableCell` insertion;
* `ImageElement` insertion and package resource handling;
* `DrawTextBox` insertion;
* inline-compatible replacement where supported;
* block paragraph replacement and text-box special cases.

### XML regions

Verify insertion in `content.xml` and the existing `styles.xml` paths,
including multiple occurrences where current behavior supports them.

### Named image replacement

Characterize:

* lookup through `draw:frame/@draw:name`;
* nested `draw:image` replacement;
* width/height defaults and explicit options;
* aspect-ratio behavior where applicable;
* missing target behavior;
* malformed/non-image frame behavior;
* copied package resources and manifest entries;
* repeated replacement and both XML regions.

### Text boxes and lifecycle

Characterize dedicated text-box processing, repeated `render()`, repeated
`save()`, document reuse, package synchronization and cleanup where the
current public behavior supports them.

These tests are prerequisites for implementation slices, not a requirement to
expand current feature support.

## 13. Proposed implementation slices

### ARCH-05D — Structured behavior characterization

Add the tests defined in section 12. No production behavior changes.

### ARCH-05E — Structured insertion extraction

Extract the existing structured materialization and placeholder insertion
behind a narrowly scoped internal collaboration. Keep compatibility wrappers
in `AbstractOdtTemplate`/`OdtTemplate`.

Responsibilities include serialization, fragment handling, style collection,
asset/package coordination, manifest synchronization, and the current
content/styles XML insertion paths. Do not add named-target semantics in this
slice.

### ARCH-05F — Typed target-resolution foundation

Introduce only the minimum internal resolver seam needed to distinguish target
type, native identity, document region, unique match, ambiguity and type
mismatch. Do not create a universal object registry or public target API.

### ARCH-05G — Existing named image operation integration

Characterize and, if safe, route `replaceImageByName()` through the target
foundation while retaining its public signature, missing-target behavior and
dimension-option semantics. This is the first candidate native-target
operation because it already exists and has a clear frame/payload boundary.

### Later optional slices

Only after separate fixtures and contracts are approved:

* named text-box content replacement;
* typed table target operations;
* other drawing-object targets;
* whole-object replacement;
* removal;
* Exact Clone, Template Clone / Template Instance, and Structural Clone.

The next implementation step after this contract is ARCH-05D
characterization, not ARCH-05E extraction.

## 14. Template Clone extension constraint

The architecture must leave room for a future native template object to be
instantiated repeatedly with clone-local data:

```text
ExperienceBlock
    -> clone -> instance 1 + local item 1
    -> clone -> instance 2 + local item 2
    -> clone -> instance 3 + local item 3
```

This is distinct from textual `foreach`. It must not be implemented by ARCH-05
or force a redesign of `TemplateProcessor` now.

Target resolution, materialization and operation dispatch must not assume that
every target is used exactly once or that placeholders must be globally
renamed before local evaluation. Later clone support may normalize native
identities/resources while preserving the authored structure.

## 15. Architecture quality checks and trade-offs

### Avoiding a God Class

A single service combining serialization, target lookup, image replacement,
table editing, cloning, style registration and package lifecycle would simply
recreate `AbstractOdtTemplate` under a new name. The contract explicitly
rejects that design.

### Materialization versus resolution

Materialization and target resolution are adjacent but independently testable.
The first needs `OdtElement` and dependency coordination; the second needs
typed identity and ambiguity rules. They should not be coupled until an
operation requires both.

### Abstraction timing

There is one established native-target operation today: named image
replacement. Therefore the initial resolver should be minimal and internal.
Additional target abstractions should be justified by a second concrete target
type, not by speculative inheritance.

### State ownership

DOM state belongs to `OdtDocumentContext`, package state belongs to
`OdtPackage`, template-language assignment state remains in `OdtTemplate`, and
structured collaborators remain stateless where practical. No duplicated
mutable document or style state is introduced.

### Behavior versus extraction

ARCH-05 implementation slices must separate characterization from movement.
Existing paragraph-level insertion, text-box handling, image dimensions and
styles/resource side effects are compatibility behavior, even where a future
semantic model would prefer a cleaner operation.

### Professional authoring

The contract supports the CV benchmark: LibreOffice remains responsible for
frame layout, styles, table presentation and visual design; the application
supplies dynamic payloads or constructed content. It does not promise that
every complex layout can be manipulated through one generic API.

## 16. Explicit unresolved questions

The following remain slice-specific or future design questions:

1. What exact internal representation should a resolved target use?
2. What is the first supported region set beyond the currently exercised DOMs?
3. What exception/result policy should new operations use for missing and
   ambiguous targets?
4. How should legacy `replaceImageByName()` behavior coexist with a cleaner
   default-preserving image operation?
5. Which text-box content forms can be replaced without invalid ODF?
6. Which table operation, if any, is safe for the first named-table slice?
7. How should style deduplication and static `StyleMapper` state be handled
   after ARCH-05, without preempting `STYLE-CONTEXT-01`?
8. Which technical IDs and nested target names require normalization during
   cloning?
9. How should clone-local processing integrate with document-region and
   `TemplateProcessor` orchestration?
10. Which protected compatibility seams can eventually be deprecated, and
    under what major-version policy?

Unresolved questions must be answered by fixture-driven contracts, not by
assuming that all ODF nodes have identical replacement semantics.

## 17. Acceptance criteria for completing ARCH-05

ARCH-05 is complete only when the approved implementation slices demonstrate:

* existing structured placeholder behavior remains compatible;
* `OdtElement` fragments, paragraphs, lists, tables, images and text boxes
  retain valid serialization and dependency handling;
* `content.xml` and supported `styles.xml` insertion paths are covered;
* package resources and manifest entries remain synchronized;
* protected facade dispatch remains observable to subclasses;
* named image replacement retains public behavior, including dimensions,
  once routed through any new internal target seam;
* target resolution is typed and does not rely on a universal name XPath;
* missing, ambiguous and type-mismatch behavior is explicit for new
  operations;
* no new global mutable structured/style state is introduced;
* future target cloning remains possible without being implemented;
* no unrelated template-language, page-layout, style-context or ARCH-05
  feature is pulled into the milestone.

The acceptance criteria apply to implemented slices; this documentation-only
contract does not claim that they have already been met.

## 18. Recommendation for the first implementation slice

Begin with **ARCH-05D — Structured behavior characterization**. Add the
focused tests in section 12 while preserving all existing source and sample
behavior.

After those tests pass, proceed to ARCH-05E as a small extraction of the
existing `setElement()`/structured insertion responsibility. Defer new named
target operations, table semantics, text-box target replacement and all clone
forms until their own characterization and slice contracts exist.

ARCH-05C is therefore complete as a change contract and ready for review, but
it does not authorize production implementation in this task.

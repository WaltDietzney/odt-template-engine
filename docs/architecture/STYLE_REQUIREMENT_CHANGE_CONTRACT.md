# Style Requirement Change Contract

## Status

This document is a **Change Contract** for the next bounded architecture step in
STYLE-CONTEXT / D5. It is based on the completed Phase-1 ODF / LibreOffice
semantic study, the ODF document/materialization model, the model-to-engine gap
analysis, and the characterization of current style-requirement behavior.

This contract defines semantics and compatibility boundaries before
implementation. It does **not** prescribe final public APIs, final class names,
or a broad rewrite of the style subsystem.

D5F remains paused until the contract is implemented or explicitly superseded.

Relevant evidence and analysis:

- `ODF_LIBREOFFICE_SEMANTIC_REFERENCE_MATRIX.md`
- `ODF_LIBREOFFICE_PHASE1_RESEARCH_FINDINGS.md`
- `ODF_DOCUMENT_MATERIALIZATION_MODEL.md`
- `ODF_DOCUMENT_MODEL_ENGINE_GAP_ANALYSIS.md`
- `STYLE_REQUIREMENT_CURRENT_BEHAVIOR_CHARACTERIZATION.md`

## 1. Problem statement

The current structured style-requirement protocol transports approximately:

```text
family
name
definition[]
```

This protocol is sufficient for transitive ownership traversal, duplicate
visibility, and document-local conflict detection, but it is not sufficient to
preserve the ODF semantics established by the Phase-1 reference study.

In particular, the current protocol cannot explicitly express:

- common versus automatic style semantics;
- the owning ODF document part;
- parent-style dependencies;
- typed property groups;
- the distinction between a style reference and a style definition;
- dependencies such as fonts;
- the difference between structural frame/image attributes and graphic-style
  properties.

Because this information is absent, downstream code currently infers semantics
from the requirement family or from the code path that happened to produce the
requirement. That inference is the architecture gap addressed by this contract.

## 2. Contract goal

The goal is to enrich the **internal** structured style-requirement protocol so
that semantic information required for correct ODF materialization survives from
producer to document finalization.

The change must preserve the successful D5 design:

```text
one semantic ownership tree
        |
        +-- style-requirement projection
        +-- resource projection
        +-- native content materialization
```

The change is therefore a refinement of the requirement channel, not a
replacement of the ownership model.

## 3. Core semantic rule

The architecture shall follow this rule:

> A structured element owns the native ODF semantics of its content and the
> references that content expresses. The document/package layer owns the
> physical materialization and finalization of dependencies whose scope extends
> beyond that element.

A style-producing element must therefore provide enough semantic information to
allow the document layer to materialize the requirement correctly without
reconstructing meaning from concrete element types.

## 4. Required semantic dimensions

A style definition requirement must be able to represent the following semantic
dimensions.

### 4.1 ODF family

The style family identifies the semantic object being styled, for example:

- `paragraph`
- `text`
- `graphic`
- `table`
- `table-column`
- `table-row`
- `table-cell`
- `section`
- `page-layout`

The implementation may initially support only the families required by the
migration slice. The protocol must not hard-code an architecture in which only
paragraph/text/frame/image can ever exist.

### 4.2 Style scope / kind

The protocol must distinguish at least:

- **common** style definitions;
- **automatic** style definitions.

Master-page semantics are known to exist but are **not required to be migrated
through this protocol in the first implementation slice**. Existing page/master
handling may remain separate because it is already semantically explicit.

The terms common and automatic are semantic ODF categories, not aliases for
`styles.xml` and `content.xml`.

### 4.3 Owning document part

The protocol must be able to identify the ODF document part that owns the style
materialization when that cannot be derived safely from the style semantics.

Initial required values are:

- `content.xml`
- `styles.xml`

A style scope alone is not sufficient because automatic styles can legally and
empirically occur in either part.

### 4.4 Parent dependency

A requirement must be able to express an optional parent style name.

Example established by STYLE-05:

```text
common RefOverrideBase
        ^
        | parent
        |
automatic P1
```

Parent relationships must not be synthesized unconditionally by a writer when
the producing semantics require a different parent or no parent.

### 4.5 Typed property groups

A requirement must preserve the ODF property groups contributed by the style.
Examples include:

- paragraph properties;
- text properties;
- graphic properties;
- table-cell properties;
- table properties;
- table-column properties;
- table-row properties;
- page-layout properties.

Property group and style family are independent concepts.

The corrected TABLE-02 fixture is normative empirical guidance for this
architecture:

```text
paragraph family style
├── paragraph properties
└── text properties
```

The implementation must therefore not assume one property group per family.

## 5. Definition versus reference

The architecture must distinguish these two states:

### 5.1 Style definition requirement

The producing subtree supplies a style definition that the document may need to
materialize.

### 5.2 Style reference only

The producing content references a named style but does not own or provide its
definition.

This distinction is required for current behavior such as Sample 21:

```text
Paragraph('CVMainHeading')
    -> references CVMainHeading

separate registry/template/document source
    -> may provide the definition
```

A name-only paragraph must not be silently reinterpreted as a request to create
a new default style definition.

Equally, a missing referenced style must not be silently ignored if the active
materialization path requires the engine to provide it.

Resolution policy is defined below.

## 6. Requirement representation boundary

The new protocol is an **internal semantic protocol**.

The implementation may use an immutable value object, a typed array structure,
or another small representation. This contract does not mandate a production
class name.

However, the representation must satisfy these properties:

- immutable or treated as immutable after creation;
- explicit semantic fields rather than meaning encoded in array placement;
- deterministic equality for conflict detection;
- no direct dependency on `OdtTemplate`;
- no process-global mutable state;
- no concrete-element type tags used by downstream materializers.

A conceptual shape is:

```text
StyleRequirement
├── requirementKind: definition | reference
├── family
├── scope: common | automatic | null-for-reference
├── documentPart: content | styles | null-when-not-applicable
├── name
├── parentStyleName?
├── propertyGroups
└── dependencies
```

This is illustrative, not a class/API prescription.

## 7. Property representation contract

### 7.1 Preserve semantic grouping

Property maps must be grouped by ODF property domain before final
materialization.

Example:

```text
propertyGroups = {
    paragraph: {
        fo:text-align: center
    },
    text: {
        fo:color: #cc0000,
        fo:font-weight: bold
    }
}
```

### 7.2 Friendly options may remain producer-facing

Existing convenience APIs may continue to accept friendly options such as:

```text
bold
font-size
margin-bottom
text-align
```

The public and element-construction APIs do not need to become raw ODF APIs as
part of this change.

However, the semantic requirement boundary must not transport an ambiguous
mixed map if the correct property group is already knowable.

Mapping/splitting may occur in the producer or in a narrow semantic adapter, but
must occur **before** the requirement is accepted as fully specified by the
new protocol.

### 7.3 Native ODF attributes remain allowed

Advanced/native ODF property input may remain supported where compatibility
requires it. Such attributes must still be assigned to an explicit property
group rather than being accepted as an unclassified flat definition.

## 8. Scope and placement defaults

The first implementation slice may define explicit migration defaults for
existing structured APIs, provided they preserve current behavior.

### 8.1 Existing generated paragraph styles

Paragraphs that carry `paragraphStyleOptions` currently cause a generated or
explicit named paragraph definition in `styles.xml/office:styles`.

For the compatibility-preserving first slice, these requirements shall remain:

- scope: **common**;
- owning part: **styles.xml**;
- family: **paragraph**;
- parent: existing compatibility parent unless separately characterized;
- property groups: paragraph and, where supported by the producer semantics,
  text.

This preserves public behavior while removing downstream inference.

This contract does **not** claim that every programmatically generated paragraph
style is semantically ideal as a common style. Reclassifying convenience/direct
formatting as automatic styles is future behavior work and must be handled in a
separate change.

### 8.2 Existing inline text styles

For the compatibility-preserving first slice, inline text style requirements
created by the current `Paragraph::addText()` path shall continue to materialize
with the existing common-style behavior in `styles.xml` unless a dedicated
behavior-change slice is approved.

The requirement protocol must nevertheless carry explicit scope/part semantics
so that a later change can migrate direct formatting to automatic styles without
changing the ownership protocol again.

### 8.3 Existing frame/image style paths

Current frame/image behavior must be characterized and preserved before any
semantic relocation between common and automatic style containers.

The new protocol must explicitly encode the current intended scope and owning
part instead of deriving it from `frame` versus `image` pseudo-families.

The pseudo-families `frame` and `image` must not become permanent ODF families;
both ultimately refer to ODF `graphic` style family semantics.

## 9. Named style reference resolution

The new architecture shall introduce an explicit resolution concept for named
style references.

For a reference-only requirement, resolution order for the first migration
slice shall be:

1. an existing compatible style definition already present in the document;
2. a document-local pending definition registered through the new requirement
   path;
3. an explicitly supported legacy compatibility registry when the public or
   sample behavior currently relies on it;
4. otherwise unresolved.

Unresolved behavior must be characterized before changing public behavior.

For the first slice, an unresolved reference may preserve current output rather
than throwing if current behavior produces the reference. The condition must be
observable in tests/documentation so that later stricter validation is a
separate behavior decision.

This is specifically intended to preserve Sample 21 while removing the hidden
architectural assumption that global registration is the primary owner.

## 10. Document-local ownership

`StyleContext` or its successor remains document-local state.

The migration must preserve these lifecycle rules:

- no process-global "current document";
- loading/replacing the document resets pending document-owned requirements;
- one document cannot observe another document's pending style state;
- duplicate equivalent definitions are idempotent;
- conflicting definitions remain detectable;
- repeated save/render operations preserve characterized lifecycle behavior.

This contract favors **extending the semantic content stored by StyleContext**
over replacing its ownership role.

## 11. Conflict semantics

Conflict detection must operate on semantic definitions, not only raw input
arrays.

Two requirements with the same identity are equivalent only when all relevant
semantic fields agree, including where applicable:

- family;
- scope;
- owning part;
- parent dependency;
- property groups;
- relevant dependencies.

A same-name style with materially different semantics must remain an explicit
conflict in pending document-owned state.

Existing document definitions remain authoritative document data. Conflict
policy between pending requirements and pre-existing template definitions must
preserve current characterized behavior until a separate validation policy is
approved.

## 12. Font dependency contract

Fonts are dependencies of text properties, not side effects of a global text
style registry.

The new requirement protocol must make it possible to discover font
requirements from the semantic style definition before final materialization.

The first implementation does not need a complete independent Font Model if a
narrow dependency representation is sufficient.

However:

- font discovery must not depend solely on scanning `styles.xml`;
- the architecture must support font dependencies originating from styles owned
  by `content.xml`;
- font declarations may be materialized in the appropriate document part(s) as
  required by ODF/compatibility evidence;
- existing StyleWriter DOM scanning may remain temporarily as a compatibility
  fallback, but must not be the only semantic source for newly migrated paths.

## 13. Table-cell contract

Table-cell style ownership is currently still largely a legacy/global path.

The new style requirement protocol must be capable of representing
`table-cell` family requirements, but the first implementation slice does not
have to migrate all table/table-column/table-row behavior at once.

When table-cell migration is performed:

- cell properties remain table-cell property semantics;
- paragraph alignment remains paragraph-property semantics;
- text formatting remains text-property semantics;
- the existing `StyleOptionSplitter` domain split must be preserved or refined,
  not collapsed;
- no visual-equivalence shortcut may move properties between ODF domains.

TABLE-02 is the reference case for this boundary.

## 14. Graphic/frame/image contract

The current engine sometimes mixes:

- structural frame attributes;
- image/frame options;
- graphic style properties;
- engine-only metadata.

The new requirement boundary must separate these categories.

### 14.1 Structural data stays element-local

Examples include, depending on ODF semantics:

- `draw:frame` identity;
- `text:anchor-type`;
- explicit frame size/coordinates when they are structural attributes;
- `draw:image/@xlink:href`;
- text-box child content.

These belong to native element materialization and must not be smuggled through
an opaque graphic-style property map merely because the legacy option array
contains them.

### 14.2 Graphic-style properties use the graphic requirement

Properties belonging to `style:graphic-properties` must be represented in an
explicit graphic property group.

### 14.3 Engine metadata is not ODF style data

Keys used only for convenience or implementation such as generated style-name
hints or UI-oriented aliases must be removed/resolved before the semantic
requirement enters document-owned state.

## 15. Collector contract

`StyleRequirementCollector` remains responsible only for traversing the semantic
ownership tree and yielding requirements.

It must not become responsible for:

- deciding whether a style is common or automatic;
- deciding XML placement based on concrete element type;
- mapping arbitrary element option arrays by `instanceof`;
- writing XML;
- resolving package resources;
- updating global registries.

Requirement semantics must be supplied by the producer or by a narrow producer-
side adapter.

The collector remains stateless.

## 16. Materialization contract

Physical style materialization must operate on semantic requirement fields and
write to the correct ODF container.

At minimum the materializer/finalizer must be able to distinguish:

```text
common style
    -> styles.xml / office:styles

automatic content-owned style
    -> content.xml / office:automatic-styles

automatic styles-part style
    -> styles.xml / office:automatic-styles
```

The writer must create the appropriate `style:style` family and property-group
children without inferring semantic property domains from arbitrary attribute
names at the final step.

The implementation may initially retain existing helper methods behind a new
semantic facade. Refactoring every StyleWriter path is not required in one
slice.

## 17. Page layout and master page

`PageLayoutManager` already follows the observed page-layout/master-page
relationship and is outside the required first migration slice.

This contract must not force page layout through the general structured style
requirement protocol merely for conceptual uniformity.

Future unification is allowed only if it reduces complexity and preserves the
special semantics of page layout and master pages.

## 18. Resources and manifest

Physical resources remain outside StyleContext.

`StructuredResourceCollector` and `OdtPackage` keep their separate channel:

```text
element subtree
    -> resource discovery
    -> package preparation
    -> manifest synchronization
```

The style requirement redesign must not merge image binaries, package paths, or
manifest ownership into style state.

A graphic style may reference a fill-image declaration or other style resource,
but the physical file remains package-owned.

## 19. Backward compatibility contract

This architecture change is initially **behavior-preserving**.

The following are explicitly protected unless a later behavior contract says
otherwise:

- public `Paragraph`, `RichText`, `RichTableCell`, `DrawTextBox`, and
  `ImageElement` construction APIs;
- friendly style option arrays;
- existing named-style references;
- Sample 21's reusable semantic paragraph-style workflow;
- protected facade hooks that external subclasses may override;
- direct `StyleMapper` / `StyleWriter` compatibility use where already public or
  historically supported;
- repeated `render()` / `save()` lifecycle behavior;
- template styles already present in `styles.xml`;
- legacy structured `assign()/render()` compatibility path.

A behavior change such as moving current inline formatting from common styles in
`styles.xml` to automatic styles in `content.xml` is **not** part of this
contract's first implementation slice.

## 20. Legacy compatibility boundary

Legacy process-global registries may remain temporarily only as explicit
compatibility inputs.

They must not silently become the semantic owner of new document-local state.

Compatibility bridging must be directional:

```text
legacy input
    -> compatibility adapter
    -> document-local semantic requirement
```

not:

```text
document-local requirement
    -> global registry
    -> later rediscovery
```

Where direct `StyleWriter` usage depends on global registries, that legacy path
may remain isolated until separately migrated.

## 21. Implementation slices

The implementation should be incremental.

### Slice SR-1 — semantic requirement representation

Introduce the richer internal representation and tests for semantic equality,
property groups, parent, scope, owning part, definition/reference state.

No production materialization behavior changes.

### Slice SR-2 — paragraph and text producers

Adapt `Paragraph` and relevant `RichText` paths to produce semantic requirements
while preserving current common/styles.xml output.

Characterize and preserve name-only style references.

### Slice SR-3 — document-local registration and resolution

Extend/refine `StyleContext` to own semantic requirements and reference
resolution inputs. Preserve conflict and reset lifecycle semantics.

Include a compatibility bridge for legacy named paragraph definitions required
by Sample 21 or equivalent public behavior.

### Slice SR-4 — semantic materialization facade

Materialize migrated paragraph/text requirements based on explicit scope,
owning part, family, parent, and property groups.

Existing StyleWriter helpers may be reused internally where semantically safe.

### Slice SR-5 — fonts

Derive font dependencies from migrated semantic requirements and ensure correct
font-face materialization without relying solely on a styles.xml scan.

### Slice SR-6 — graphic requirements

Separate DrawTextBox/ImageElement structural data from graphic-style properties
and migrate their style requirements without changing rendering behavior.

### Slice SR-7 — table-cell closeout

Move table-cell style definitions into the document-local semantic requirement
channel while preserving StyleOptionSplitter domain semantics and legacy direct
StyleWriter compatibility.

D5F may be resumed earlier than SR-7 only if the remaining table-cell legacy
path cannot affect the D5F materialization lifecycle under review.

## 22. Required characterization before each migration

Before changing a family/path, tests must capture:

- produced content references;
- definition location/container;
- family;
- parent;
- property grouping;
- font/resource dependencies where relevant;
- repeated save/render behavior;
- template-existing style behavior;
- legacy API behavior where applicable.

Rendering-affecting changes require LibreOffice visual regression against a
known-good baseline in addition to XML/package tests.

## 23. Testing contract

At minimum the style-requirement migration must include focused tests for:

- semantic representation equality/conflict;
- common vs automatic distinction;
- content.xml vs styles.xml ownership;
- multiple property groups on one paragraph-family style;
- parent-style dependency;
- definition versus reference;
- name-only Paragraph reference;
- Sample 21 style-resolution path;
- transitive requirements through `ownedElements()`;
- duplicate equivalent requirement idempotency;
- duplicate conflicting requirement failure;
- document reset isolation;
- two documents in the same PHP process;
- repeated save/render where currently supported;
- font dependency discovery from semantic requirements.

Full repository preflight remains required after implementation slices.

## 24. Explicit non-goals

This contract does not authorize:

- a StyleWriter rewrite;
- removal of `StyleMapper` public compatibility APIs;
- removal of `toDomNode()`;
- a central renderer that switches on concrete element types;
- changing all current direct formatting to automatic styles;
- redesigning lists;
- redesigning page layout/master pages;
- redesigning metadata/settings;
- moving resources into StyleContext;
- changing public template syntax;
- implementing STYLE-API-02 or STYLE-CONTEXT-01 future topics beyond what is
  necessary for this bounded migration;
- fixing unrelated visual bugs such as the known circular-image rendering issue.

## 25. Acceptance criteria

This Change Contract is satisfied when:

1. the active structured requirement path can represent scope, family, owning
   part, parent, and typed property groups explicitly;
2. definition and reference semantics are distinguishable;
3. paragraph/text migrated paths no longer require the final writer to infer
   those semantics from pseudo-family or call-site context;
4. StyleContext remains document-local and conflict-preserving;
5. Sample 21's named paragraph-style workflow is preserved through an explicit
   resolution/compatibility path;
6. font dependencies can originate from semantic requirements rather than only
   post-hoc styles.xml scanning;
7. graphic migration has a defined boundary between frame structure and graphic
   style properties;
8. resource/package ownership remains separate;
9. no concrete-element type dispatch is added to OdtTemplate/materializers;
10. automated and required visual regressions pass for every rendering-affecting
    implementation slice.

## 26. Decision for D5F

D5F remains **PAUSED** at contract creation.

D5F may resume after the paragraph/text semantic requirement path and its
document-local materialization boundary are implemented and validated, because
those paths are currently the clearest evidence that lifecycle/materialization
work would otherwise continue on an under-specified style protocol.

Graphic and table-cell migration may proceed before or after D5F depending on
which families D5F actually touches, but any family entering D5F must first obey
this contract.

The guiding rule is:

> Do not continue materialization refactoring across a style path whose semantic
> requirement has not yet been made explicit.

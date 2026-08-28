# PRODUCT-01C — Addressable Document Model Design

## A. Status and purpose

PRODUCT-01C defines a semantic design for inspecting and addressing native ODF
structures. It does not implement a public target API, change production code,
modify tests, or freeze the illustrative PHP names used below.

The intended product loop is:

```text
inspect
    ↓
discover native identity
    ↓
resolve typed target
    ↓
read state and capabilities
    ↓
perform an allowed mutation
    ↓
validate
    ↓
save editable ODT
```

The central design decision is to make native ODF semantics visible through a
typed addressable model without creating a second document model beside
`OdtDocumentContext`.

## B. Repository and evidence reviewed

The design is based on:

- PRODUCT-01 and PRODUCT-01A;
- PRODUCT-01B fixture audit and findings/closeout;
- ARCH-05 structured-element and typed-target decisions;
- ARCH-07 facade and state-ownership closeout;
- `OdtTemplate`, `OdtPackage`, and `OdtDocumentContext`;
- `TemplateTarget` and `TemplateTargetResolver`;
- `StructuredElementMaterializer` and `TemplateProcessor`;
- `MetadataManager`, `PageLayoutManager`, image/frame handling, and table/
  structured-element classes;
- resolver, structured insertion, image, metadata, lifecycle, processing,
  PageLayout, style, and public-sample tests;
- PRODUCT-01B fixtures `product01b_01` through `product01b_11`.

The current implementation has authoritative mutable DOM state in
`OdtDocumentContext`, package/workspace ownership in `OdtPackage`, and facade
orchestration in `OdtTemplate`. `TemplateTargetResolver` is currently a
read-only resolver for `draw:frame[@draw:name]` and
`table:table[@table:name]`. `TemplateTarget` is a lightweight resolved DOM
element, not yet a general public target model.

The PRODUCT-01B fixtures provide the decisive native evidence:

- sections use `text:section/@text:name` and contain real block structures;
- bookmarks use paired `text:bookmark-start` and `text:bookmark-end` markers;
- tables use `table:table/@table:name`;
- frames use `draw:frame/@draw:name`;
- bookmarks can start or end inside paragraphs/list-item paragraphs and span
  heterogeneous structures;
- LibreOffice copy/paste rewrites nested names in an implementation-specific
  way and does not provide the desired section-instance contract.

## C. PRODUCT-01B constraints carried forward

The following constraints are binding:

1. A named section is a native structural container and a strong future
   template-object candidate.
2. A bookmark/range is a named selection over existing structure, not a
   detachable DOM container.
3. Named tables and frames remain typed native objects.
4. Native identity remains type-specific; there is no assumed global name
   namespace.
5. Technical identifiers such as `xml:id` are distinct from author-facing
   names.
6. Bookmark mutation must be topology-aware and must fail explicitly when a
   requested operation cannot be performed safely.
7. Clone and template-instance semantics are distinct from ordinary Writer
   copy/paste.
8. The engine must own deterministic identity rewriting for future clones or
   instances.
9. Existing visible template-language syntax and current public APIs remain
   valid and are not replaced by native addressing.
10. No universal `Block` abstraction is justified by the fact that several
    native objects have names.

## D. Semantic document model

The proposed conceptual model is a typed addressability layer over the one
document held by `OdtDocumentContext`:

```text
OdtTemplate
└── DocumentInspection / target access boundary
    ├── Section descriptors and targets
    ├── Bookmark/range descriptors and targets
    ├── Table descriptors and targets
    ├── Frame descriptors and targets
    ├── image/payload information
    ├── template expressions
    ├── styles and page-layout summaries
    └── diagnostics
```

This is a semantic view, not a second mutable DOM. A descriptor or handle
refers to the current DOM owned by the package's document context. It must not
copy an XML subtree into independent mutable state.

The first coherent inspection scope should be native named structures and
their immediate content/topology:

```text
named sections
bookmarks/ranges
named tables
named frames
frames with image or text-box payloads
diagnostics for duplicates, missing names and unsafe ranges
```

Variables, styles, metadata, page layout, assets, and all document structure
may later be included in the same inspection result, but they should not make
the first addressability slice an unbounded document-introspection project.

## E. Identity, descriptor, target, and DOM node

The design should distinguish four levels:

```text
Identity
    type-specific native name, for example section/name or table/name

Descriptor
    immutable, inspectable facts and diagnostics about the identity

Mutable target / handle
    typed operations authorized for that native target

Native DOM node/range
    internal implementation detail owned by OdtDocumentContext
```

An identity is not enough to describe capability. A `FirstName` bookmark and
an `ExperienceEntry` section may both have a name but expose materially
different operations. A descriptor can report location, child summary,
topology, warnings, and capabilities before any mutation is attempted.

Descriptors should be stable value-like results for inspection and machine
serialization. Mutable targets should be short-lived handles resolved against
the current document context. A handle must either be invalidated or re-
resolved after `load()`, `refresh()`, or any operation that replaces the core
DOM documents.

Illustrative, non-final usage:

```php
$report = $template->inspect();
$descriptor = $report->sections()->named('ExperienceEntry');
$target = $template->section('ExperienceEntry');
$target->inspect();
```

These names are conceptual. The important property is typed resolution and
inspection before mutation, not fluent syntax.

## F. Descriptor versus mutable-target decision

Descriptors and mutable targets should be separate.

This separation improves:

- **clarity:** facts and permitted operations are not mixed;
- **AI discoverability:** an agent can inspect all candidates without
  mutating the document;
- **safety:** topology and capability warnings are available before a write;
- **testability:** descriptor tests can use stable fixture facts while target
  tests focus on one mutation contract;
- **lifecycle correctness:** a handle can state which document lifetime it is
  bound to;
- **error quality:** resolution, capability, topology, and mutation failures
  remain distinguishable.

The target must not become a generic DOM wrapper. It should expose only
operations meaningful for its native type. Advanced XML access may remain an
explicit internal/developer escape hatch later, but raw XPath must not be the
ordinary addressability API.

## G. Capability model

Capabilities are concrete per target type. They should be represented as
inspectable facts, not as a speculative universal capability framework.

| Target | Always plausible | Conditional / topology-dependent | Not a default capability |
|---|---|---|---|
| Bookmark/range | exists, inspect, read text, safe text replacement, marker removal | selected-content replacement, clear/remove selected content, structured replacement | generic subtree clone |
| Section | inspect, read content, replace contained content, remove | complete-object replacement, clone, instantiate, nested identity rewrite | treating it as a bookmark range |
| Table | inspect name/dimensions/properties, read cells, access rows | add/remove rows, row clone/instance, cell content/style operations | generic section replacement |
| Frame | inspect geometry/name/payload, remove, supported payload replacement | image replacement, text-box content operations, geometry mutation | assuming every frame is an image |

`replaceContent()` and `replace()` must remain distinct where they are offered:

```text
replaceContent
    target/container identity survives; contained content changes

replace
    complete native object or selected object representation is replaced

remove
    native object or range disappears according to its type contract
```

The capability report should distinguish “unsupported” from “currently unsafe”
and from “not found”.

## H. Bookmark topology model

Bookmarks must be modeled as ranges with boundaries, not as containers.

Inspection should classify at least the following semantic topology categories
(names remain illustrative):

```text
INLINE
    both markers are within one paragraph/text flow

PARAGRAPH_SPANNING
    range crosses paragraph boundaries without crossing a higher-risk object

LIST_SPANNING
    range intersects list/list-item structure

TABLE_SPANNING
    range intersects or surrounds table structure

MIXED_BLOCK
    range spans heterogeneous block children such as paragraphs and tables

COMPLEX_OR_UNSAFE
    boundary placement or nesting cannot support the requested operation
```

The descriptor should expose, where relevant:

- marker locations and document part;
- containing paragraph/list/table/frame ancestors;
- whether each marker is inside a text node flow or at a block boundary;
- block/object types between the markers;
- nested, overlapping, unmatched, or duplicate-name diagnostics;
- operations safe for this topology;
- whether a requested operation would require container splitting.

Consequently, a simple operation such as the following may be supported for
an `INLINE` range:

```php
$template->bookmark('FirstName')->replaceText('Walter'); // conceptual
```

The same generic-looking call must not imply that structured replacement is
safe for every range. A structured replacement of a mixed or list-spanning
range should reject with a typed topology error unless a later dedicated
range-mutation contract explicitly defines the required container surgery.
Explicit failure is preferable to silently producing invalid ODF.

## I. Section clone and template-instance semantics

Sections are the strongest native candidate for a repeatable structured
template object because `text:section` defines a real subtree boundary.

The design should keep these operations semantically distinct:

```text
clone
    duplicate the section structure and content according to an exact,
    documented structural policy

instantiate
    duplicate a section as a new template instance, rewrite identities
    deterministically, and evaluate local template data
```

An implementation must not use LibreOffice copy/paste naming as its contract.
The exact clone policy and template-instance policy require later fixture and
implementation evidence, but both must account for:

- the section's `text:name`;
- nested `table:name` and `draw:name` values;
- bookmark names inside the section;
- technical `xml:id` values and references;
- style references;
- image/package references;
- local placeholders and repeat/condition scope.

Names must not be blindly duplicated. The engine should reserve a deterministic
identity allocation step, conceptually producing names such as
`ExperienceEntry`, `ExperienceEntry_1`, and `ExperienceEntry_2`, without
freezing that suffix format before implementation evidence and compatibility
review. The allocator must check the relevant native namespace and report a
collision rather than silently shadowing another target.

The model must distinguish:

```text
object identity       mutable section/table/frame/bookmark name
technical identity    xml:id or other document-internal identifier
shared asset          immutable package image/resource that may be reused
cloned structure      new mutable XML nodes with valid references
```

Shared image binaries may remain shared when the cloned frames intentionally
refer to the same resource. A cloned object must nevertheless have valid
native identity and references.

## J. Relationship to existing foreach and table rows

Native section instantiation and existing `foreach` are related but not the
same authoring model.

```text
visible foreach
    template-language region processing, already supported

section instantiate
    native structural template-object operation, future capability

table row instantiate
    table-specific structural operation, future capability
```

They may eventually share lower-level concerns such as local value scope,
placeholder evaluation, subtree cloning, identity allocation, and validation.
They must not be conflated in the first implementation:

- `TemplateProcessor` remains the owner of template-language transformations;
- section and row operations remain native structural mutations;
- a section target must not pretend a table row is a section;
- existing `assignRepeating()`/`foreach` behavior must remain compatible.

A future named table target may expose a table-specific template-row concept,
but PRODUCT-01C does not select row identity, marker convention, or a public
method name. Merged cells, nested lists/images, styles, and row-local scopes
require a dedicated audit.

## K. Table, frame, image, and text-box relationship

### Tables

Tables are typed native objects identified by `table:name`. A future table
descriptor should report dimensions, rows/cells, table style references,
nested named objects, and diagnostics. Row operations must remain table-
specific. Existing `RichTable`/`RichTableCell` and structured insertion are
constructed-content APIs, not replacements for native named-table addressing.

### Frames

Frames are typed native drawing objects identified by `draw:name`. A frame
descriptor should identify payload kind and relevant geometry/style references.
An image frame, text-box frame, and other drawing frame cannot share all
operations merely because they use the same native name attribute.

### Images

Existing `setImage()` and `replaceImageByName()` semantics remain separate:

```text
setImage
    existing placeholder/image path and package-resource preparation

replaceImageByName
    named frame resolution and frame payload mutation
```

The future frame target may expose image replacement only when inspection
proves that its payload is an image. Structured `ImageElement` resource
preparation remains package-owned and must not be routed through named-frame
mutation.

### Text boxes

A text-box frame should report that its payload is editable text content and
offer only text-box-compatible operations. It must not be silently treated as
an image or as an arbitrary section container. Named text-box mutation remains
deferred from ARCH-05 and this design.

## L. Inspection model

Inspection should produce a typed, serializable result rather than a debug
string or raw DOM dump. Conceptually:

```text
DocumentInspection
├── sections: SectionDescriptor[]
├── bookmarks: BookmarkDescriptor[]
├── tables: TableDescriptor[]
├── frames: FrameDescriptor[]
├── variables / template expressions
├── metadata summary
├── page-layout summary
├── styles summary
├── assets summary
└── diagnostics
```

The minimum first implementation result should cover the first four native
target families and diagnostics. The rest can be added by bounded follow-up
slices without changing the identity model.

Each descriptor should make these questions answerable:

- What native type is this?
- Which identity field owns its name?
- Where is it located: content, styles, or another document part?
- What children or payload does it contain?
- Which nested named targets exist?
- Which operations are supported, unsafe, or deferred?
- Are there duplicate or conflicting identities?
- Does a mutation require identity allocation or topology handling?

The inspector should report type-specific name collisions, for example a
section and table both named `Skills`, without declaring them invalid merely
because their native namespaces differ.

## M. Get/set symmetry

Read and write operations should converge on the same semantic target, but the
model must not expose every XML attribute as a setter.

Useful pairs include:

```text
bookmark: getText ↔ replaceText
section:   inspect/getContent ↔ replaceContent/remove/instantiate
table:     get dimensions/cells ↔ set cell content/add row later
frame:     get payload/geometry ↔ set supported geometry or image payload
document:  get metadata/layout ↔ existing metadata/layout setters
```

Getters should return semantic values and diagnostics, not DOM nodes. Setters
should exist only where the native semantics and compatibility contract are
clear. Effective appearance, inherited styles, and raw declared style
properties must remain distinguishable in any future style inspection.

## N. Error model

Deterministic typed exceptions are preferable for mutating developer APIs.
Inspection may report non-fatal diagnostics; mutation must not silently guess.

The conceptual error taxonomy is:

| Condition | Meaning |
|---|---|
| Target not found | No target of the requested native type and identity exists |
| Ambiguous target | Multiple nodes violate the resolver's uniqueness expectation |
| Wrong target type | Identity exists under another native type, but not the requested one |
| Unsupported operation | The target type does not define that operation |
| Unsafe bookmark topology | The range exists, but its boundaries cannot safely support the operation |
| Identity collision | A clone/instance name or technical identity would conflict |
| Invalid structured replacement | Replacement content cannot be represented at the requested location |
| Identity rewrite failure | Clone/instance references or names cannot be made valid |
| Stale target handle | The document context was replaced since resolution |

Convenience `find`/nullable resolution may be useful for discovery, while
strict `resolve`/typed target access should fail predictably. The distinction
must be explicit in documentation; a missing target must not be confused with
an unsupported operation or an unsafe range.

## O. Naming and namespaces

Native identity is type-specific:

```text
section  → text:name
bookmark → text:name on start/end markers
table    → table:name
frame    → draw:name
```

Therefore a section named `Skills` and a table named `Skills` may coexist. A
typed API should preserve this fact. Conceptually, these are separate lookups:

```php
$template->section('Skills'); // conceptual
$template->table('Skills');   // conceptual
```

Inspection should still surface same-spelling names as a useful warning or
cross-reference because they can confuse developers and agents. The warning
is not a false global collision.

Clone naming must allocate within each relevant native namespace and must
rewrite nested identities consistently. The engine should not adopt
LibreOffice's observed copy names as a stable public convention.

## P. Mapping to the current architecture

| Proposed responsibility | Current mapping | Decision |
|---|---|---|
| Public target access | `OdtTemplate` | Facade entry point; keep orchestration thin |
| Authoritative DOM | `OdtDocumentContext` | Reuse; no second document model |
| Package/resources/persistence | `OdtPackage` | Reuse; clone/image resources remain package-owned |
| Existing named frame/table lookup | `TemplateTargetResolver` | Extend in bounded typed slices; preserve strict ambiguity behavior |
| Resolved target value | `TemplateTarget` | Extend or supersede with typed descriptors/handles only after a contract; do not make it a universal mutable DOM wrapper |
| Constructed ODF insertion | `StructuredElementMaterializer` | Reuse for `OdtElement` materialization; not a native named-target resolver |
| Template-language processing | `TemplateProcessor` | Reuse; no section/bookmark semantics added |
| Metadata | `MetadataManager` | Existing owner; inspection can later expose its semantic values |
| Page layout | `PageLayoutManager` | Existing owner; inspection/setters remain layout-specific |
| Image/frame mutation | existing `OdtTemplate` + resolver/package paths | Preserve current `setImage()` and named-frame replacement; add typed frame capabilities later |
| Table/row operations | `RichTable` and future native table collaborator | Table-specific future slice; no generic block operation |
| Section/bookmark resolution | no current implementation | New bounded resolver capability justified later by PRODUCT-01C slices |
| Document inspection | no current implementation | New bounded inspection responsibility; not a raw DOM dump and not a generic context |
| Clone identity allocation | no current implementation | New bounded collaborator only when clone semantics are specified and tested |

The only potentially new collaborators justified by this design are bounded
inspection/resolution and, later, identity allocation. A separate service is
not justified for each descriptor type before a real second consumer or stable
operation contract exists.

## Q. Compatibility implications

The addressable model complements, rather than replaces, current APIs:

- `setElement()` remains constructed structured insertion;
- `replaceImageByName()` remains named-frame mutation;
- existing table APIs remain valid;
- placeholder syntax and `foreach` remain valid;
- repeated render/save and package/document lifecycle remain unchanged;
- protected polymorphism remains relevant to current facade workflows;
- `content.xml` and `styles.xml` remain owned by the existing context/package
  lifecycle.

No current API should be deprecated merely to introduce typed target handles.
Future deprecation, if desirable, needs its own compatibility contract after
the new model has proven useful. A target API must not route structured image
insertion through named-frame replacement or turn existing textual processing
into native section processing implicitly.

## R. Developer ergonomics

The model should provide:

- typed IDE completion for target families;
- concise common operations such as safe bookmark text replacement;
- explicit inspection before mutation;
- descriptors that explain why a target is unsafe or unsupported;
- predictable exceptions and nullable discovery where appropriate;
- a documented advanced escape hatch for unusual ODF cases, without making
  XPath the normal workflow.

A developer should not need to remember whether a frame is identified by
`draw:name` or a table by `table:name` for ordinary operations. The typed API
should retain that native distinction internally and in diagnostics.

## S. AI-agent ergonomics

The model is especially suitable for coding agents if it provides:

- machine-readable inspection output;
- deterministic type and identity fields;
- explicit capability lists and safety status;
- topology details for ranges;
- nested-target summaries;
- deterministic errors instead of silent fallback;
- a clear inspect → resolve → mutate → validate sequence;
- validation results for unresolved placeholders, duplicate identities,
  invalid references, and package/XML integrity.

An agent should be able to answer “is `Profile` a section, bookmark, table, or
frame?” and “can this range accept structured content?” without inventing
XPath. It should also be able to report that a requested operation is not
supported rather than attempting a best-effort DOM rewrite.

## T. Existing-document-to-template workflow

The target product workflow is:

```text
professional DOCX
    ↓ LibreOffice conversion
native ODT
    ↓ inspect
sections, bookmarks, tables, frames, assets, variables
    ↓ author/agent names native structures where appropriate
bookmarks for scalar fields
sections for repeatable structured regions
frames for images/text boxes
tables for tabular regions
    ↓ typed target operations
read / replace / instantiate / validate
    ↓
editable native ODT
```

This preserves the visually authored layout. A converted CV can retain its
sections, lists, tables, and frames while an application addresses a scalar
bookmark, repeats an experience section, updates a named image frame, or
operates on a table-specific row. The model does not require rebuilding the
document layout in PHP.

## U. Architecture alternatives

### Model A — Facade method explosion

```php
$template->replaceBookmarkText('FirstName', 'Walter');
$template->cloneSection('ExperienceEntry');
$template->setFrameImage('ProfilePhoto', $path);
```

Advantages are discoverable individual methods for a few common operations and
low initial conceptual machinery. The disadvantages are rapid public-method
growth, weaker type grouping, duplicated error semantics, poor capability
discovery, and awkward extension as tables, ranges, frames, and sections gain
different operations. It also encourages facade code to absorb native-object
semantics.

### Model B — Typed target handles

```php
$template->bookmark('FirstName')->replaceText('Walter');
$template->section('ExperienceEntry')->instantiate($data);
$template->table('Skills')->inspect();
$template->frame('ProfilePhoto')->replaceImage($path);
```

Advantages are native semantic types, IDE completion, explicit capabilities,
regular inspect/read/mutate flow, and a natural place for topology and
identity diagnostics. It can reuse the current resolver foundation while
keeping target operations type-specific. The cost is several descriptor/handle
contracts and lifecycle rules, which must be implemented in bounded slices.

### Model C — Generic target API

```php
$template->target('section', 'ExperienceEntry')->operate('instantiate', $data);
```

This offers a uniform entry point and could be useful for machine-generated
workflows. It weakens PHP type safety, hides capabilities behind strings,
encourages generic operation dispatch, and makes errors less discoverable. It
also risks recreating the rejected universal `Block` abstraction under a
different name.

### Recommendation among the models

Model B is the best direction. It provides regularity without erasing native
semantics. Model A may remain as carefully chosen convenience methods for
high-value operations, implemented on top of typed targets rather than as the
primary architecture. Model C may be a serialization or internal adapter
later, but should not be the main PHP API.

## V. Recommended model

Adopt a typed target-oriented model with a separate inspection/descriptor
layer:

```text
inspect document
    → typed descriptors and diagnostics
resolve typed target
    → bookmark/range, section, table, or frame handle
inspect target capabilities/state
    → safe, supported, deferred, or unsafe operations
mutate through type-specific operation
    → native DOM changes through OdtDocumentContext
validate package/document/identities
    → save native editable ODT
```

The target family is the semantic boundary. `SectionTarget` is not a
`BookmarkTarget`; a table row is not a section; an image frame is not a text
box. Similar method names such as `inspect()` or `remove()` are acceptable
only when their type-specific semantics are documented.

## W. Proposed implementation slices

The following sequence is intentionally design-led and does not authorize
implementation in PRODUCT-01C:

### ADDRESSABLE-01 — Inspection contract and descriptors

Define a machine-readable inspection result for existing named sections,
bookmarks/ranges, tables, and frames. Characterize duplicate names, native
identity fields, document-part locations, child summaries, and diagnostics.
No mutation API, clone behavior, or generic capability framework.

### ADDRESSABLE-02 — Typed resolution boundary

Extend the existing resolver direction to return type-specific descriptor/
handle information while preserving current frame/table behavior and
ambiguity errors. Establish stale-handle/lifecycle rules. Keep resolution
read-only.

### NAMED-RANGE-01 — Bookmark inspection and safe text mutation

Implement only lookup, topology inspection, text read, and replacement for
topologies proven safe by fixtures. Reject unsafe structured operations with a
typed error. Do not implement arbitrary range subtree replacement.

### SECTION-01 — Section inspection and read operations

Resolve named sections, inspect contained native structure and nested names,
and read content summaries. Establish container-preserving versus complete-
object semantics before mutation.

### SECTION-02 — Section content mutation

Add bounded `replaceContent`/`remove` behavior only after topology and ODF
validity are characterized. Keep complete-object replacement separate.

### SECTION-03 — Section clone and instance contract

Specify and implement structural clone, then template instance semantics as
separate slices. Add deterministic identity allocation, nested-name rewriting,
reference validation, local data scope, and visual/package regression.

### TABLE-ROW-01 — Named-table row template audit

Investigate template-row identity, row-local values, merged cells, nested
content, styles, and interaction with existing `foreach`. Implement only a
table-specific row contract once evidence supports it.

### FRAME-01 — Typed frame payload inspection

Expose frame geometry and payload classification, then add narrowly scoped
image/text-box operations. Preserve existing named image replacement and
package-resource ownership.

### ADDRESSABLE-FINAL — Cross-target validation and compatibility review

Review descriptors, handles, diagnostics, lifecycle invalidation, native
namespaces, package/XML validity, public API compatibility, and the inspect-
mutate-validate workflow. Only then consider convenience APIs or broader
document inspection.

Each slice should have focused characterization, full PHPUnit coverage,
package/XML checks, and visual regression whenever content topology, styles,
layout, frames, images, or pagination can change.

## X. Deferred work

Explicitly deferred:

- public typed target APIs themselves until the implementation slices begin;
- section cloning and template instances;
- bookmark structured mutation beyond proven safe text cases;
- table row cloning and row identity convention;
- named text-box mutation;
- whole-object replacement and generic removal semantics where not yet
  characterized;
- generalized capability frameworks;
- `STYLE-CONTEXT-01`, `STYLE-API-02`, and document defaults;
- `ASSET-CONTEXT` and long-running temporary asset lifecycle;
- template-format preservation and authoring UX;
- HTML importer expansion, DOCX conversion, and HTML-to-ODT product work;
- arbitrary public XPath/XML mutation.

The design also does not decide whether a future inspection result is one
large aggregate or several domain reports. It decides only that the result
must be typed, serializable, native-semantic, and diagnostics-aware.

## Y. Open questions requiring implementation evidence

The following questions are intentionally not guessed:

1. Which bookmark boundary topologies can safely support text replacement
   after real DOM mutation and LibreOffice round trips?
2. Can section content be replaced while preserving section styles and
   surrounding layout across representative documents?
3. Which technical identifiers must be regenerated for section clones, and
   which references are safely shared?
4. How should nested bookmark names be allocated when a section is
   instantiated?
5. What is the minimum reliable table-row template convention, especially for
   merged cells and nested content?
6. How should target handles behave after render, save, `load()`, or `refresh()`
   replaces DOM instances?
7. Should diagnostics be warnings in inspection but hard errors for mutation,
   and which cases are recoverable?
8. Which existing public style/image helpers should be reflected in future
   descriptors without prematurely defining `STYLE-CONTEXT-01` or
   `ASSET-CONTEXT`?
9. What convenience methods, if any, materially improve common workflows once
   typed targets exist?

## Z. Final recommendation

Proceed with **Model B: typed target handles backed by separate immutable
descriptors and a machine-readable inspection report**.

Use native type-specific identities and capabilities. Keep bookmark topology a
first-class safety constraint. Treat sections as future structured template
objects, bookmarks as named ranges, tables as typed tabular objects, and
frames as typed drawing/payload objects. Reuse `OdtDocumentContext`,
`OdtPackage`, `TemplateTargetResolver`, `StructuredElementMaterializer`, and
`TemplateProcessor` according to their existing ownership boundaries.

The next implementation/design step should be **ADDRESSABLE-01 — Inspection
contract and descriptors**, preceded by a focused characterization plan for
the fixture facts and lifecycle rules identified above. PRODUCT-01C itself
remains documentation-only and introduces no public API.

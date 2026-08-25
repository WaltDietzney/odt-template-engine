# ARCH-05B Element Identity & Replacement Semantics

**Status:** Semantic/design research complete; no production implementation
**Milestone:** ARCH-05 — Structured elements / structured insertion
**Repository basis:** `develop`
**Audited HEAD:** `95681ef62b2b9a5d48a825a4406e20000c3f51b5`

This document follows the findings in [ARCH-05A](ARCH-05A_STRUCTURED_ELEMENTS_AUDIT.md)
and defines terminology and semantic constraints for a future ARCH-05C change
contract. It deliberately does not define PHP classes, interfaces, public
method names, or implementation syntax.

## 1. Repository and audit basis

The repository was inspected at `develop` with ARCH-05A present. The existing
implementation confirms that `OdtElement` serializes constructed ODF content,
while `setElement()` coordinates structured insertion, styles, and package
resources. `replaceImageByName()` is the only established named-template-object
operation currently exposed by the facade.

The ODF specification provides type-specific identity and structural rules:
common drawing shapes may carry `draw:name`, drawing structures may also carry
technical `xml:id`/`draw:id` attributes, and tables may carry `table:name`.
These attributes must not be collapsed into one universal identity namespace.
[ODF 1.3 schema](https://docs.oasis-open.org/office/OpenDocument/v1.3/os/schemas/OpenDocument-v1.3-schema-rng.html)

LibreOffice's Navigator exposes named frames, drawing objects, sections,
bookmarks, and tables as document objects, but this UI behavior does not make
their XML identity mechanisms interchangeable.
[LibreOffice Navigator](https://help.libreoffice.org/latest/en-GB/text/swriter/guide/navigator.html?DbPAR=WRITER)

## 2. Terminology

### Constructed structured content

ODF structure created by an engine-side `OdtElement`, such as a `Paragraph`,
`RichText`, `ListElement`, `RichTable`, `ImageElement`, or `DrawTextBox`.
It has serialization behavior and may have style/resource dependencies, but
it is not a handle to an existing node in a template.

### Template target

An existing native ODF object or region in the authored template that can be
resolved independently of a visible textual placeholder. A target is
conceptual vocabulary only; it is not yet a PHP type.

### Target identity

The type-specific information used to resolve a target, including its
document region, ODF element family, and identity attribute. A target name is
not assumed to be globally unique across all ODF element families.

### Target type

The semantic ODF family being addressed, for example frame, text-box frame,
table, or drawing shape. The type determines valid operations and preservation
rules.

### Container

The template-authored node that owns layout, anchoring, visual style, and
possibly identity. For an image or text box this is normally the
`draw:frame`.

### Payload/content

The dynamic child or child sequence being supplied or replaced. Examples are
`draw:image` inside an image frame and flow content inside `draw:text-box`.

### Document region

A separately stored or structurally distinct ODF area in which a target may
occur, such as body content, a header/footer, or master-page/style content.
The current engine already processes both `content.xml` and `styles.xml` in
different paths.

### Content replacement

Replacing the defined payload of an existing target while retaining the
target's container and its preserved layout/identity attributes.

### Whole-object replacement

Replacing the selected native ODF element itself with another compatible
structure. This may discard template-authored layout and must never be an
implicit interpretation of content replacement.

### Clone

Creating a second target or structure from an existing authored subtree while
defining identity, references, styles, resources, and placement for the copy.
DOM deep cloning may be an implementation primitive, but is not the semantic
operation by itself.

### Removal

Deleting a target or its containing structure while applying the required
cleanup policy for references, anchors, styles, and package resources.

### Preservation policy

The explicit rule describing which template-owned attributes, children,
styles, identities, and resources survive an operation.

### Resource dependency

A package file or manifest relationship required by a target, such as an
image under `Pictures/` referenced by `xlink:href`.

### Style dependency

A referenced or generated ODF style required for valid rendering, including
automatic styles in `styles.xml` or `office:automatic-styles`.

## 3. Native ODF identity findings

### Frames, images, text boxes, and drawing objects

The repository's `replaceImageByName()` resolves:

```xpath
//draw:frame[@draw:name='...']
```

The named object is therefore the frame, not the nested `draw:image`. This is
consistent with the container/payload distinction: the frame owns layout and
the image owns the package-backed payload.

The ODF schema makes `draw:name` available through common drawing-shape
attributes. It also permits technical `xml:id` and `draw:id` attributes on
relevant drawing structures. Those IDs are not established by this audit as
the same thing as a user-visible LibreOffice object name. A future resolver
must not silently use one in place of the other.

`draw:text-box` is a content container inside `draw:frame`. It can contain
flow-oriented document structures, including paragraphs, lists, tables and
additional frames. Its `draw:chain-next-name` is a relationship for chained
text boxes, not a general replacement name.
[ODF text-box schema](https://docs.oasis-open.org/office/OpenDocument/v1.3/os/schemas/OpenDocument-v1.3-schema-rng.html)

LibreOffice assigns unique names when creating objects in ordinary authoring
flows, but the ODF schema itself does not provide a sufficient package-wide
uniqueness contract for a future engine. Duplicate or repeated names must be
treated as an unresolved condition that a future target resolver handles
explicitly, preferably by deterministic region/type scoping or a safe
ambiguity failure.

### Tables

ODF tables may carry `table:name`, and the engine's `RichTable::setTableName()`
emits that attribute. LibreOffice exposes table names as table objects in the
Navigator. Table naming is therefore a promising integration point, but it is
not interchangeable with `draw:name` and must be resolved as a table target.

The schema does not by itself establish the application's desired uniqueness
scope or rename behavior. ARCH-05C must define what happens when a table name
is missing, duplicated, or appears in more than one document region.

### Sections, bookmarks, and reference marks

ODF has named mechanisms for some textual/document structures, including
sections and bookmarks/reference marks. They are structurally different from
drawing frames and tables. They may be future target families, but this audit
does not establish stable cross-editor semantics for replacing or cloning
their contents.

### Paragraphs, spans, and lists

No comparable user-authored native naming model has been established for
ordinary `text:p`, `text:span`, or `text:list` nodes. Technical `xml:id`
attributes can exist on some ODF nodes, but their presence does not create a
reliable LibreOffice authoring workflow equivalent to naming a frame or table.
They should not be promoted to named targets without dedicated evidence.

## 4. Target-type ownership analysis

### Image frame

| Concern | Current/expected owner | Default preservation direction |
|---|---|---|
| identity | outer `draw:frame/@draw:name` | preserve |
| graphic style | `draw:frame/@draw:style-name` | preserve |
| anchor | `text:anchor-type` | preserve |
| dimensions | `svg:width`, `svg:height` | preserve unless explicit compatibility option changes them |
| position | `svg:x`, `svg:y`, horizontal/vertical position attributes | preserve |
| wrapping | frame style/position properties | preserve |
| stacking | `draw:z-index` | preserve |
| payload | nested `draw:image` | replaceable content |
| package resource | `xlink:href` and `Pictures/` file | replace together |

The existing `replaceImageByName()` changes the nested image reference and
also writes width/height values. A future content-replacement semantic must
preserve that public behavior, likely by treating dimension changes as an
explicit option rather than silently removing them. It must not redefine the
existing method in ARCH-05B.

### Text-box frame

The outer frame owns name, anchor, size, position, wrapping, graphic style and
z-index. The nested `draw:text-box` owns paragraphs, lists, tables, inline
content and nested frames. “Replace text-box content” should therefore mean
replacing the text-box's defined child flow while retaining the outer frame,
unless whole-object replacement is explicitly selected.

Text-box chains, nested drawing objects, styles, and flow-content region rules
must be characterized before content replacement is implemented.

### Table

At least three distinct operations are possible:

1. replace cell content while retaining the table, rows, columns and styles;
2. rebuild or replace dynamic rows while retaining the table container and
   compatible layout/style definitions;
3. replace the complete `table:table`.

These have materially different preservation and resource semantics. The
engine should not promise all three merely because `RichTable` can serialize a
new table. Table width, column styles, merged cells, header rows and cell
styles make whole-table replacement especially destructive.

### Drawing shapes and lines

Drawing shapes can have `draw:name`, style, position, size, z-index and
technical IDs. They may be addressable as named targets, but content
replacement is shape-specific and often means changing geometry or text rather
than replacing a generic payload. They remain a research candidate, not an
initial implementation target.

## 5. Operation semantics

### Text placeholder replacement

Scalar replacement such as `{{name}}` is template-language processing. It
changes textual content and does not identify an existing native object.

### Structured placeholder insertion

A textual marker such as `{{profile}}` identifies an insertion location for
constructed `OdtElement` content. Existing `setElement()` may replace an
inline-compatible node or the containing paragraph for block content, while
also coordinating styles and assets. It is not named-target resolution.

### Named-target content replacement

The target container survives. Only the target-defined payload is replaced,
and the preservation policy retains identity, layout and style attributes by
default. Examples are replacing `draw:image` inside a named frame or replacing
the children of a named frame's `draw:text-box`.

### Named-target whole-object replacement

The selected ODF element is replaced with a compatible constructed or
template-derived object. The default preservation rule is intentionally weak:
the old container's layout and identity do not survive unless the operation's
explicit contract says how they are transferred. Type compatibility is
required; arbitrary paragraph/table/frame/image interchangeability is not.

### Cloning

Cloning requires a policy for:

* duplicate or newly assigned `draw:name`/`table:name` values;
* `xml:id` and `draw:id` uniqueness;
* nested named targets and references between them;
* automatic and named styles;
* image files, `xlink:href`, and manifest entries;
* anchors, z-order, frame chains and external references;
* table spans, merged cells and row-local dynamic data.

Accordingly, named-target cloning is not generalized foreach and cannot be
specified as only `cloneNode(true)`.

#### Exact clone

An exact clone duplicates the existing ODF structure together with its
current content:

```text
template object
    -> clone
identical structure, layout, styles, and current content
```

This is useful as a precise semantic description, but it is not automatically
an independent template object. Duplicating `draw:name`, `table:name`,
`xml:id`, `draw:id`, or referenced IDs can create ambiguous targets or invalid
relationships. Exact cloning therefore requires explicit identity and
reference handling even when the DOM subtree is copied without modification.

#### Template clone / template instance

A template clone duplicates a LibreOffice-authored structure while retaining
its formatting, layout, and template placeholders. Each resulting instance is
then evaluated against an isolated clone-local data context.

For example, an authored experience block may contain:

```text
{{date}}  {{role}}  {{company}}  {{description}}
```

The same placeholder names can initially exist in every clone. They do not
necessarily need to be renamed if evaluation is performed against the local
row/context belonging to that clone:

```text
ExperienceBlock
    -> clone + evaluate with experience[0]
    -> clone + evaluate with experience[1]
    -> clone + evaluate with experience[2]
```

This is a design possibility, not an implementation decision. It would let
LibreOffice author one complete visual block while the engine supplies local
data. It also requires a clear boundary between clone-local evaluation and
the existing document-wide template processing passes.

#### Structural clone

A structural clone duplicates structure and formatting while deliberately
clearing or rebuilding dynamic payload/content. For example, a paragraph may
retain `text:style-name="CvRole"` while its text payload is cleared before new
content is inserted.

“Clear content” is type-dependent. Removing every descendant can remove
required paragraph, cell, list, or frame structure. A table cell may need its
`text:p` retained while only its text payload is replaced. Structural clone
must therefore not be defined as indiscriminate descendant deletion, and it
is not yet selected as a supported operation.

The three semantics are distinct:

| Clone semantic | Structure/layout | Existing content | Placeholder processing |
|---|---|---|---|
| Exact clone | preserved | preserved | none required |
| Template clone / template instance | preserved | template placeholders initially preserved | evaluated independently in a clone-local context |
| Structural clone | preserved selectively | cleared or rebuilt | optional/new content inserted |

These are semantic vocabulary, not three required public methods.

#### Template clone versus existing foreach

The active textual `foreach` implementation repeats a template-language block
and substitutes row values through its existing compatibility path. A future
template clone would instead repeat a native ODF target, such as a named frame,
text box, or table, and evaluate placeholders independently inside each
instance.

Both concepts have repeated data, a current-item context, and repeated
placeholder evaluation. They differ in the repetition boundary and ownership:

* textual foreach is delimited by `{{#foreach:...}}` and
  `{{#endforeach}}` markers;
* template cloning is delimited by an existing native ODF target;
* native cloning can preserve LibreOffice-authored tables, frames, styles and
  layout;
* native cloning must additionally normalize names, technical IDs, resources,
  references and insertion positions.

ARCH-05B does not change or consolidate the existing foreach implementation.

#### CV benchmark

For a professional CV, LibreOffice could author one complete experience block
containing `{{date}}`, `{{role}}`, `{{company}}`, and `{{description}}`, then a
future template-instance operation could materialize it once per experience
entry. The same model could apply to education blocks.

This would keep fonts, paragraph spacing, borders, table-cell styling,
positioning, and decorative layout template-owned wherever technically
possible. It supports the product principle that LibreOffice should remain
the visual template designer rather than PHP recreating its layout system.

The benchmark does not establish that template cloning must be implemented in
the first ARCH-05 production slice.

#### Clone questions deferred to ARCH-05C

Template cloning leaves the following unresolved:

* What happens to `draw:name` and `table:name` on each clone?
* How are `xml:id`, `draw:id`, and other technical references regenerated?
* Can nested named targets remain stable, become scoped, or require new names?
* Are image resources shared or duplicated, and how is the manifest updated?
* Are automatic styles reused or cloned?
* What happens to internal references, anchors, z-order, and frame chains?
* Where are subsequent clones inserted?
* How does cloning work in headers, footers, or other document regions?
* How does clone-local evaluation interact with `TemplateProcessor`?
* How is compatibility with the current textual foreach behavior maintained?

These are open semantic questions, not implied answers.

### Removal

Removal belongs to the same semantic family, but needs an explicit target
scope and cleanup policy. Removing a frame may remove a payload and its
container; removing a shared resource or style merely because one target was
removed would be unsafe without dependency tracking.

## 6. Capability matrix

The following is semantic vocabulary, not a PHP interface proposal.

| Target type | User-nameable | Content-replaceable | Whole-replaceable | Cloneable | Removable | Flow content | Resource-backed | Style-backed | Layout container |
|---|---:|---:|---:|---:|---:|---:|---:|---:|---:|
| image frame | yes, `draw:name` | yes, nested image | conditionally, same family | possible with rules | yes, with cleanup | no/limited | yes | yes | yes |
| text-box frame | yes, frame `draw:name` | yes, text-box children | conditionally, same family | possible with rules | yes, with cleanup | yes | optional | yes | yes |
| table | yes, `table:name` | possible, cell/row scope | possible, destructive | possible, complex | yes, with cleanup | yes | optional | yes | yes |
| drawing shape/line | often, `draw:name` | type-specific/uncertain | type-compatible only | unresolved | possible | optional | usually no | yes | yes |

“User-nameable” means that a native authoring workflow and XML identity have
been observed, not that uniqueness and cross-region resolution are already
defined. “Cloneable” means technically plausible after identity/resource
rules, not supported behavior.

## 7. Document-region implications

Target resolution must eventually be region-aware. The main body is not the
only valid location: headers, footers, first-page and later-page regions, and
master-page/style-owned content may be represented outside the ordinary body
flow. The current engine's structured insertion already searches both
`content.xml` and `styles.xml` in different paths.

ARCH-05 should not implement page-layout or header/footer behavior here, but a
future resolver must carry region information and must not assume a single
`content.xml` XPath is sufficient.

## 8. CV benchmark

| CV requirement | Semantically suitable direction | Main constraint |
|---|---|---|
| Avatar | named-frame image content replacement | preserve frame layout; retain explicit dimension-option compatibility |
| Profile/header box | named-frame text-box content replacement | preserve position, wrap, size, style and z-index |
| Experience table | structured `RichTable` insertion or future named-table operation | table layout, merged cells and style ownership need separate rules |
| Repeated experience/education blocks | future target cloning or existing template-language repetition | identity and row-local processing are unresolved |
| Decorative lines/shapes | future typed drawing target | geometry and shape-specific semantics are not generic |
| Header/footer content | future region-aware target resolution | page-style/master-page ownership is out of scope |

This model keeps LibreOffice as the visual template designer: layout remains
template-owned by default, while PHP supplies dynamic payloads or constructed
content where the operation explicitly allows it.

## 9. Compatibility constraints

Future implementation must preserve and characterize:

* `OdtElement::toDomNode()` and style/resource hooks;
* `setElement()` and `assign()` values containing `OdtElement` instances;
* `setImage()` and the dimension behavior of `replaceImageByName()`;
* `replacePlaceholderWithDom()` inline versus block behavior;
* dedicated text-box processing;
* structured replacement in `content.xml` and `styles.xml`;
* style registration, automatic styles, package resources and manifest
  synchronization;
* render, save, load and cleanup lifecycle;
* protected facade/subclass seams.

No compatibility-sensitive path is changed by ARCH-05B.

## 10. Unresolved questions for ARCH-05C

ARCH-05C must decide, with tests and representative ODT fixtures:

1. What target types are supported first?
2. What is the exact resolution scope for each type and document region?
3. What happens for missing, duplicate, or ambiguous names?
4. Are technical IDs ever accepted, or only user-visible names?
5. How are named frames in headers, footers, and unused master pages handled?
6. What content can replace text-box children without invalidating ODF?
7. Which table operations are safe enough for an initial contract?
8. How are dimensions and other options preserved for image replacement?
9. How are styles deduplicated and resources/manifest entries synchronized?
10. What identity and reference changes are required for cloning?
11. Which target operations belong in a document service versus the public
    facade?
12. Which existing protected methods must remain wrappers indefinitely?

## 11. Recommendation for ARCH-05C

ARCH-05C should define a small, evidence-backed change contract around typed
target resolution and explicit operations. It should not begin with a
universal `replaceElementByName()` API or treat all `OdtElement` subclasses as
interchangeable native objects.

The safest initial semantic direction is:

```text
constructed OdtElement content
        != existing template target

typed target + explicit operation
        -> preservation policy
        -> style/resource synchronization
        -> region-aware document mutation
```

The likely first implementation candidate is named-frame image content
replacement, because the repository already has `replaceImageByName()` and
the frame/payload boundary is clear. Text-box content replacement and table
operations should follow only after dedicated characterization fixtures and
compatibility rules exist.

No final public method names, PHP types, or implementation APIs are selected
by ARCH-05B.

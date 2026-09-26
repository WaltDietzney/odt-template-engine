# SECTION-02B — Safe Section Content Replacement

## A. Goal and evidence

SECTION-02B implements the first structural mutation on a native named ODF
section. It follows SECTION-02A, SECTION-01, PRODUCT-01B/C, and the existing
structured insertion architecture. The operation is:

```php
$template->section('Profile')->replaceContent($content);
```

The section container survives; only its children are replaced. The current
implementation accepts the existing `OdtElement` abstraction and deliberately
does not add a second content model.

## B. Supported replacement content

The proven green zone is resource-free block-capable materialization:

- `Paragraph`;
- `RichText` containing paragraphs, lists, and tables;
- `ListElement`;
- `RichTable`;
- other `OdtElement` values only when their materialized top-level nodes are
  legal section blocks and contain no image payload.

Top-level materialized nodes must be `text:p`, `text:h`, `text:list`,
`table:table`, or `draw:frame`. An empty `RichText` is accepted and leaves an
empty, still-addressable section.

Images and image-bearing content are rejected. Although existing image
materialization can create a frame, this target handle does not own the
package resource/manifest preparation boundary required for atomic section
replacement.

## C. Rejected and deferred content

The operation rejects atomically:

- inline-only top-level nodes such as `text:span`;
- malformed bookmark structures in replacement content;
- missing names on introduced native named objects;
- same-type identity collisions;
- image/resource-bearing content.

Raw XML, DOM nodes supplied by callers, HTML, strings, complete section nodes,
clone/instance content, and arbitrary implicit arrays are not accepted. Section
whole-object replacement, removal, cloning, instantiation, nested mutation,
Style Context, Asset Context, and Document Defaults remain deferred.

## D. Mutation semantics and materialization path

`SectionTarget` remains the typed public API. `SectionMutationService`:

1. strictly resolves the current unique section;
2. clones the document into a detached staging DOM;
3. materializes the `OdtElement` into that staging document;
4. validates legal top-level block structure, image absence, bookmark pairs,
   and native identity collisions;
5. replaces only children of the staged section;
6. removes the current section children; and
7. imports the validated children into the existing section node.

`StructuredElementMaterializer` remains the existing placeholder-oriented
materializer and is not made responsible for section lookup. The new service
reuses each element's `toDomNode()` contract while owning the section boundary.
No `TemplateProcessor` or `DocumentInspector` mutation coupling is introduced.

Materialized prefixed nodes from existing element renderers are copied into
the authoritative document with the known ODF namespace URIs. This preserves
namespace-aware inspection for newly introduced tables, frames, sections, and
text content without changing the element renderers.

## E. Section identity preservation

The original `text:section` DOM element is retained. Its `text:name`, style
and unknown attributes, parent, and sibling position are not rewritten. Old
children and their nested named objects disappear as a consequence of child
replacement; they are not merged or automatically preserved.

The target remains usable after a successful replacement. Its descriptor,
textual view, and nested named-object summary reflect current content.

## F. Nested identities and collision rules

Identity remains type-specific:

```text
section  → text:name
bookmark → text:name
table    → table:name
frame    → draw:name
```

Names inside the section being removed do not block their replacement. Names
outside it do. Two introduced objects of the same type also collide. A section
and table with the same spelling are allowed. No automatic renaming is done;
identity rewriting belongs to future clone/instantiate work.

New bookmark content is only accepted when it contains exactly one valid
collapsed marker or one paired start/end identity. The current public element
classes do not provide a bookmark-construction convenience API, so this path
is characterized but not a primary authoring feature.

## G. Style and resource boundary

Existing element materialization is reused without redesigning `StyleMapper`,
`StyleWriter`, or the style registry. Resource-bearing images are deferred
because `SectionTarget` cannot transactionally coordinate package copying and
manifest changes through its current context-only ownership boundary. No
`StyleContext` or `AssetContext` is introduced.

## H. Atomicity

All target, content, block, resource, bookmark, and identity validation occurs
before the original section children are deleted. Tests compare the original
serialized DOM after each represented failure. No partial section replacement
remains.

The staging DOM also prevents materialization failure from damaging the
authoritative content DOM. Package resources are not changed because asset
bearing content is rejected before commit.

## I. Empty content and read-after-write

An empty `RichText` is a valid replacement. It produces zero section children;
the native section remains resolvable and its `text()` view is empty. The
service does not insert an artificial paragraph.

After replacement, `descriptor()`, `text()`, and `nestedNamedObjects()` read
the current state. Repeated replacement is supported.

## J. Lifecycle and template-language sequencing

Targets remain identity-backed and resolve against the current
`OdtDocumentContext`. A target obtained before context replacement can replace
content in the current same-named section. `load()`/`refresh()` behavior stays
with the existing lifecycle implementation.

Section replacement does not invoke `TemplateProcessor`. If materialized text
contains template syntax, a later explicit `render()` may process that syntax.

## K. Tests and package validation

Focused tests cover paragraph, RichText, list, table, empty content, section
identity/attributes/siblings, old/new nested identities, collision behavior,
different-type name coexistence, resource rejection, inline rejection,
atomicity, and current-context target behavior. Existing addressability,
bookmark, processing, structured insertion, lifecycle, and public sample
tests remain part of the regression suite.

Representative successful replacements are saved as temporary ODT packages and
checked for ZIP integrity and XML well-formedness. Visual validation is
required for future rendered section changes; the known agent LibreOffice
environment limitation remains and no visual baseline is modified here.

## L. Compatibility and limitations

The API is additive:

```php
$section->replaceContent($element): SectionTarget
```

No existing API is removed, renamed, or deprecated. The supported set is
intentionally smaller than every type that can be used through placeholder
`setElement()`, because section-child legality and package atomicity are
separate contracts.

The next slice should address package/style preparation for resource-bearing
section content only if a transactional ownership boundary is first defined.
Whole-section replace/remove and clone/instantiate semantics must remain
separate decisions.

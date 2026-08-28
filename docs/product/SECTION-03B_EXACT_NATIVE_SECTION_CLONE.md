# SECTION-03B — Exact Native Section Clone

## A. Goal

SECTION-03B establishes the smallest native section-cloning foundation. It
duplicates one uniquely named `text:section` as a complete DOM subtree and
inserts the duplicate immediately after the source. It deliberately does not
rewrite identities and does not implement `instantiate()`.

The result is therefore an intermediate structural state, not a final valid
addressable template instance.

## B. Evidence reviewed

The implementation was checked against PRODUCT-01C, ADDRESSABLE-01/02,
SECTION-01, SECTION-02A-D, SECTION-03A, the ARCH-05 materialization documents,
the ARCH-07 ownership documents, and the native fixture
`samples/templates/sample_25_sectionClone.odt`.

The fixture is a real converted CV document rather than a rebuilt synthetic
template. Its `ExperienceEntry` section is nested below a drawing custom shape,
which is unusual but provides a valid parent and sibling insertion context.

## C. Sample-25 topology

The fixture contains exactly one `ExperienceEntry` section and one nested
`ActivityEntry` section. The outer section contains two paired bookmark ranges
(`Company` and `Activity`) and one collapsed bookmark (`FromTo`). The `Activity`
range intersects the split XML representation of `{{activity}}`: bookmark
markers occur between text spans. The section also contains lists, paragraph
and text-span style references, and the `{{note}}` and `{{position}}`
expressions. No `xml:id`, table, or drawing frame occurs inside the source
section; the document's drawing objects are outside this subtree.

This awkward topology is intentionally preserved and is not normalized before
cloning.

## D. Exact clone semantics

`SectionCloneService::cloneExact()` resolves a unique section by its native
`text:name`, validates the bounded technical-identity condition, performs
`DOMNode::cloneNode(true)`, and inserts the clone after the source's next
sibling position. `cloneExactSource()` is an internal orchestration/test seam
that permits repeated exact clones from an already selected source after the
name has become ambiguous.

The service does not rebuild the subtree through `Paragraph`, `RichText`,
`ListElement`, `ImageElement`, or `StructuredElementMaterializer`. This is
necessary to preserve designer-authored native structure.

## E. Insertion position and source preservation

The source remains in place and unchanged. The clone is its immediate sibling
under the same parent. Attributes, child order, text nodes, namespace
bindings, and descendant structure are copied by the native DOM clone.

## F. Nested sections and bookmarks

`ActivityEntry` remains inside each cloned `ExperienceEntry`; it is not
detached or promoted. Paired and collapsed bookmark forms are copied exactly.
Bookmark names and marker placement, including the markers intersecting the
split `{{activity}}` expression, are not moved, repaired, or renamed.

## G. Expressions and lists

Template expressions remain byte/structure-equivalent in the cloned subtree.
In particular, `{{note}}`, `{{position}}`, and the logically reconstructed
`{{activity}}` remain present with the original span fragmentation. List,
list-item, paragraph, heading, and text-span topology is cloned rather than
rematerialized.

## H. Styles, frames, and resources

Style references remain unchanged and no new `styles.xml` definitions are
created. Any frame or custom-shape descendants would be copied with their
native attributes, geometry, anchoring, and style references. Sample 25 has no
such descendant, so frame preservation is characterized as a consequence of
subtree cloning, not as a fixture-specific assertion.

Package resources are not copied or rewritten. A cloned subtree would continue
to reference the same immutable package resource. Sample 25 contains no
resource reference inside the source section.

## I. Technical identities

The fixture has no `xml:id` values. The service nevertheless rejects a clone
before insertion when the source contains duplicate `xml:id` values or when a
source `xml:id` already exists elsewhere in the document. No technical ID
allocator is introduced in this slice; deterministic rewriting belongs to
SECTION-03C.

## J. Temporary duplicate identities

Exact cloning intentionally duplicates native names, including
`ExperienceEntry`, `ActivityEntry`, and the bookmark names. This is accepted
only as an intermediate characterization state. The normal inspector reports
duplicate section names through `duplicate_native_name`. Bookmark markers are
aggregated into one bookmark descriptor by the existing inspector and report
their repeated marker form through `duplicate_bookmark_markers`. Strict section
resolution remains strict and raises `AmbiguousAddressableTargetException`.

The implementation does not weaken uniqueness rules or hide these diagnostics.

## K. Save and reopen

The exact clone is saved and reopened through `OdtPackage`. The package remains
ZIP-valid and `content.xml`, `styles.xml`, `meta.xml`, and
`META-INF/manifest.xml` remain well-formed. Both outer and nested section
copies remain discoverable after reopen. Since the fixture has no resources in
the cloned subtree, no package-resource duplication or cleanup is performed.

## L. Atomicity

The bounded preparation checks occur before insertion. If technical identity
validation fails, the source document is unchanged and no partial clone is
inserted. The operation does not alter package resources.

## M. API and ownership

No public `SectionTarget::clone()` method is added. The internal
`SectionCloneService` owns exact native subtree cloning; `OdtDocumentContext`
remains the authoritative DOM owner. This avoids freezing an intermediate
duplicate-identity API before SECTION-03C defines identity rewriting.

## N. Visual findings

The agent environment was not used to claim LibreOffice visual equivalence.
Local rendering remains required for the real CV fixture:

```sh
./tools/visual-regression/render-odt.sh /tmp/section-clone-exact.odt
```

The expected local check is that the cloned `ExperienceEntry` appears directly
after the source and retains the same native layout. No baseline or generated
visual artifact is part of this change.

## O. Tests and compatibility

Focused tests cover exact subtree and insertion preservation, nested sections,
bookmark forms and marker topology, split expressions, lists and styles,
duplicate diagnostics, strict ambiguity, repeated cloning, save/reopen, and
atomic technical-ID rejection. Existing section reads, section replacement,
bookmark replacement, structured insertion, image/resource behavior, lifecycle
behavior, and public sample smoke coverage remain separate regression targets.

No existing public API is removed, renamed, or changed.

## P. Limitations and SECTION-03C

This slice does not provide deterministic names, bookmark suffixes, technical
ID rewriting, nested identity allocation, data binding, or template evaluation.
It also does not define how shared resources should be rewritten or garbage
collected for instantiated copies.

SECTION-03C should add a preparation phase that allocates deterministic,
type-specific identities for the whole cloned subtree, rewrites references
where required, and commits only a valid final instance. It must build on this
native subtree clone rather than reconstructing the document through the
structured-content materializer.

SECTION-03B intentionally produces an intermediate structurally cloned state
that may contain duplicate addressable identities. It is not yet the final
`instantiate()` model.

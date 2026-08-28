# ADDRESSABLE-01 — Inspection Contract and Descriptors

## A. Goal and scope

ADDRESSABLE-01 introduces the first implementation slice of the addressable
native ODF model described by PRODUCT-01C. It is deliberately read-only. Its
purpose is to make the native named structures in one ODT discoverable in a
typed, machine-readable snapshot.

The implemented scope is limited to:

- named ODF sections;
- bookmarks and bookmark ranges;
- named native tables;
- named native drawing frames;
- diagnostics directly required to understand those structures.

This slice does not implement mutable target handles, bookmark mutation,
section cloning, row cloning, style inspection, asset inspection, metadata,
page-layout inspection, template-expression inspection, or arbitrary XML
access.

## B. Evidence reviewed

The implementation contract is based on:

- PRODUCT-01, PRODUCT-01A, PRODUCT-01B, and PRODUCT-01B findings/closeout;
- PRODUCT-01B fixtures `product01b_01_named_section_simple.odt` through
  `product01b_11_section_clone_identity.odt`;
- ARCH-05 typed native target and structured-insertion decisions;
- ARCH-07 facade and state-ownership decisions;
- `OdtTemplate`, `OdtPackage`, and `OdtDocumentContext`;
- `TemplateTarget`, `TemplateTargetResolver`,
  `StructuredElementMaterializer`, and `TemplateProcessor`;
- existing table, image/frame, lifecycle, API-contract, and public-sample
  tests.

The fixture inspection confirmed that sections use `text:name`, bookmark
ranges use paired `text:bookmark-start`/`text:bookmark-end` markers, tables use
`table:name`, and frames use `draw:name`. A collapsed `text:bookmark` form is
also supported by the implementation. Sections can contain tables, lists,
frames, and images. Bookmark boundaries can be embedded in paragraphs and
list-item paragraphs or span tables.

## C. Implemented inspection model

The public facade now provides a read-only entry point:

```php
$inspection = $template->inspect();
```

Each call creates a fresh `DocumentInspection` snapshot from the current
`OdtDocumentContext`. The result contains typed descriptor lists:

```text
DocumentInspection
├── SectionDescriptor[]
├── BookmarkDescriptor[]
├── TableDescriptor[]
├── FrameDescriptor[]
└── InspectionDiagnostic[]
```

The snapshot exposes typed enumeration and simple name lookup:

```php
$inspection->sections();
$inspection->bookmarks();
$inspection->tables();
$inspection->frames();
$inspection->diagnostics();

$inspection->section('ExperienceEntry');
$inspection->bookmark('FirstName');
$inspection->table('Skills');
$inspection->frame('ProfilePhoto');
```

These are inspection lookups returning descriptors, not mutable target handles.
The descriptor `toArray()` methods provide a straightforward machine-readable
representation for agents, diagnostics, JSON adapters, and test assertions.

## D. Descriptor types

### `SectionDescriptor`

Contains:

- native section name;
- document part (`content.xml`);
- basic descendant summary for paragraphs, headings, lists, tables, and frames;
- compact nested named table/frame references;
- descriptor-local diagnostics.

It does not expose the section DOM subtree and does not recursively serialize
all descendants.

### `BookmarkDescriptor`

Contains:

- native bookmark name;
- document part (`content.xml`);
- presence of a start marker;
- presence of an end marker;
- conservative topology classification;
- selected text where the range boundaries permit a snapshot;
- descriptor-local diagnostics.

Supported topology values are represented as stable strings and constants on
the descriptor:

```text
collapsed
inline
paragraph_spanning
list_spanning
table_spanning
mixed_block
malformed
```

### `TableDescriptor`

Contains:

- native table name;
- document part (`content.xml` or `styles.xml`);
- row count;
- first-row column count, including repeated columns where represented;
- containing section name when identifiable;
- descriptor-local diagnostics.

The descriptor does not expose cells as mutable objects and does not define
row-template semantics.

### `FrameDescriptor`

Contains:

- native frame name;
- document part (`content.xml` or `styles.xml`);
- conservative payload type: `image`, `text-box`, or `other`;
- width and height when directly available;
- containing section name when identifiable;
- descriptor-local diagnostics.

The payload classification does not imply that a future frame target supports
all image, text-box, or geometry operations.

### `NamedObjectReference`

Sections expose a compact nested summary for named tables and frames. Each
reference contains the native type, name, and document part. This is enough to
answer questions such as whether a section contains `Skills` or
`ProfilePhoto` without exposing a recursive DOM model.

## E. Descriptor immutability

All descriptors, nested references, diagnostics, and the inspection result are
PHP `readonly` classes. They contain scalar values and arrays of other
read-only value objects, never `DOMNode`, `DOMElement`, `DOMDocument`, or
package objects.

`OdtDocumentContext` remains the sole authoritative owner of the content and
styles DOMs. Descriptors are snapshots, not caches or alternate state owners.
They cannot be used to mutate the document.

The current implementation intentionally does not create mutable target
handles. That will be a separate resolution slice with explicit lifecycle
rules.

## F. Native identity semantics

The inspector preserves ODF's type-specific identity fields:

| Native type | Identity |
|---|---|
| Section | `text:name` |
| Bookmark/range | `text:name` on bookmark markers |
| Table | `table:name` |
| Frame | `draw:name` |

`xml:id` and `svg:title` are not treated as equivalent author-facing template
identities. A section, table, frame, and bookmark can have the same spelling;
the inspector reports them in their respective typed collections.

Duplicate names within one native type produce diagnostics. A same-spelling
section and table do not produce a fabricated global collision.

Unnamed sections, tables, frames, and bookmark markers are not emitted as
named descriptors. They produce `missing_native_name` diagnostics so the
document is not silently presented as fully addressable.

Tables and frames are inspected in both `content.xml` and `styles.xml`, with
the document part recorded in each descriptor. Sections and bookmarks are
currently inspected in `content.xml`, where the PRODUCT-01B evidence places
their native structures.

## G. Bookmark forms

The inspector supports both observed native forms:

```xml
<text:bookmark text:name="Cursor"/>
```

and:

```xml
<text:bookmark-start text:name="FirstName"/>
...
<text:bookmark-end text:name="FirstName"/>
```

A collapsed bookmark reports `hasStart = true`, `hasEnd = true`, topology
`collapsed`, and an empty text value. It is not treated as a range containing
unknown content.

An unpaired start/end marker reports a `malformed` topology and an
`unpaired_bookmark_marker` error diagnostic. Duplicate markers with the same
name receive a `duplicate_bookmark_markers` warning and are not silently
treated as a valid unique range.

## H. Bookmark topology model

The implementation uses a conservative bounded classification rather than a
general range algebra:

- `inline`: both boundaries are within the same paragraph or heading;
- `paragraph_spanning`: boundaries cross paragraph flow without detected list,
  table, or mixed block structure;
- `list_spanning`: a marker is inside a list/list-item structure;
- `table_spanning`: a marker is inside a table/table-cell structure;
- `mixed_block`: the range crosses heterogeneous block content such as a
  table or frame;
- `collapsed`: one native collapsed bookmark element;
- `malformed`: a range is missing one of its boundary markers.

List and table ancestor checks are intentionally conservative. A range whose
boundaries are embedded in those structures is not presented as a simple
paragraph range merely because its text looks simple.

The descriptor reports topology for future safety decisions. It does not claim
that any mutation is currently supported. In particular, a mixed or
list/table-spanning range must not be treated as a detachable subtree.

## I. Bookmark text inspection

For a paired range with valid document order, the inspector concatenates text
nodes between the two native boundary markers in document order. This is a
conservative textual snapshot, not a representation of structured content.
It does not insert paragraph or list separators and does not claim to
preserve formatting.

Text is available for inline, paragraph-spanning, list-spanning, and mixed
ranges when the marker order is valid. Consumers must use the topology before
assuming that the value is suitable for replacement. Structured replacement
and selected-content deletion remain deferred.

## J. Nested identity model

For each named section, the inspector reports nested named tables and frames as
compact references:

```text
Section ExperienceEntry
├── table Skills          content.xml
└── frame ProfilePhoto    content.xml
```

The summary is intentionally shallow. It does not recursively describe every
paragraph, list item, cell, style, asset, or nested DOM node. It provides the
minimum useful relationship for an agent deciding which later typed target to
inspect next.

## K. Diagnostics

`InspectionDiagnostic` is a small machine-readable value object containing:

- `code`;
- `severity` (`warning` or `error`);
- concise message;
- optional target type;
- optional target name.

Current diagnostics include:

| Code | Severity | Meaning |
|---|---|---|
| `missing_native_name` | warning | A named-structure type has no author-facing name |
| `duplicate_native_name` | error | More than one descriptor uses the same name within one type/inspected parts |
| `duplicate_bookmark_markers` | warning | A bookmark name has multiple native markers of one form |
| `unpaired_bookmark_marker` | error | A bookmark range is missing a start or end marker |

Diagnostics appear globally on `DocumentInspection`; bookmark-specific
diagnostics also remain on the affected `BookmarkDescriptor`. This supports
both a document-level health check and focused target inspection.

The model deliberately does not attempt to diagnose every ODF validity issue.
Package/XML validation remains a separate responsibility.

## L. Lifecycle and snapshot semantics

Inspection is fresh and read-only:

```text
construction → inspect() → snapshot A
load()/refresh() → inspect() → snapshot B
```

Every call constructs a new result from the current context DOMs. No
descriptor is cached across lifecycle transitions, and no handle can become a
hidden reference to a replaced DOM.

The implementation does not mutate the DOM, package workspace, assignments,
render state, styles, or resources. Repeated inspection of unchanged state is
stable in content and machine-readable output. Existing `load()`, `refresh()`,
render, save, and repeated lifecycle behavior remains unchanged.

## M. Relationship with `TemplateTargetResolver`

`TemplateTargetResolver` remains the strict, read-only resolver for the current
`TemplateTarget` frame/table foundation. It returns DOM-backed targets for
existing image/frame and table operations and preserves its ambiguity
behavior.

`DocumentInspector` has a different responsibility: it traverses the bounded
native addressability surface and returns immutable facts without exposing DOM
nodes. It therefore does not force sections or bookmarks into the current
`TemplateTarget` shape.

The native identity rules are shared conceptually, but the two components are
not merged mechanically. A later `ADDRESSABLE-02` slice may introduce a common
typed resolution boundary or extend the resolver with explicit section and
bookmark resolution after the descriptor contract is characterized.

## N. Public API added

One public facade operation was added:

```php
OdtTemplate::inspect(): DocumentInspection
```

No `bookmark()`, `section()`, `table()`, or `frame()` mutable target accessors
were added. No mutation methods were added. The inspection API is explicitly a
snapshot API and does not alter existing public workflows.

## O. Compatibility and limitations

Existing behavior remains unchanged for:

- construction and package loading;
- assignment and template processing;
- conditions and foreach;
- structured insertion;
- named image replacement;
- existing table APIs;
- metadata, PageLayout, render/save/load/refresh;
- protected processing and structured-insertion seams.

The new API is additive. It does not deprecate or rename existing APIs and
does not change `TemplateTargetResolver` behavior.

Known limitations are intentional:

- no mutable target handles;
- no section/bookmark mutation;
- no clone or instantiate semantics;
- no table-row operations;
- no complete recursive document model;
- no effective-style or asset analysis;
- no public raw XPath/XML escape hatch;
- no guaranteed semantic separators in bookmark text snapshots;
- no global namespace collision because native identity is type-specific.

## P. Tests

The focused test suite contains eight inspection tests covering:

1. empty/no-target documents;
2. section, named table, and named frame discovery;
3. nested named table/frame summaries;
4. collapsed and inline bookmarks;
5. paragraph-spanning ranges;
6. list- and table-involving ranges;
7. duplicate, missing, and malformed identities;
8. stable repeated inspection and facade lifecycle snapshots.

The focused ADDRESSABLE-01 and relevant compatibility/integration suites
passed:

```text
50 tests, 464 assertions
```

The full suite passed:

```text
113 tests, 889 assertions
```

The PRODUCT-01B fixture files were also inspected directly through the new
service. All eleven fixtures produced the expected section/bookmark/table/
frame families without unexpected diagnostics.

## Q. Next slice recommendation

The next slice should be:

> **ADDRESSABLE-02 — Typed resolution boundary**

It should define how immutable descriptors relate to future typed mutable
handles, preserve current frame/table resolver behavior, establish strict
versus nullable lookup semantics, and define stale-handle behavior after
`load()`/`refresh()`. It should remain read-only if the handle contract is not
yet sufficiently characterized for mutation.

Bookmark mutation, section mutation, cloning, table-row instantiation, and
frame payload mutation should follow only in their own evidence-based slices.

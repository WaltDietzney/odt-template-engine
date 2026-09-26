# SECTION-01 — Section Inspection and Read Operations

## A. Goal and evidence

SECTION-01 makes the existing typed `SectionTarget` useful for document
understanding without introducing section mutation. It builds on PRODUCT-01B,
PRODUCT-01C, ADDRESSABLE-01/02, the ARCH-05/07 addressability work, the
existing `SectionDescriptor`, `DocumentInspector`, and
`OdtDocumentContext`.

The native model remains a real ODF container:

```xml
<text:section text:name="ExperienceEntry">...</text:section>
```

The section is not reduced to a generic block and no raw DOM is exposed.

## B. Chosen read API

The smallest useful addition is:

```php
$section = $template->section('ExperienceEntry');
$text = $section->text();
$objects = $section->nestedNamedObjects();
```

`descriptor()` remains the authoritative read operation for the immutable
structural snapshot, including `childSummary()`, document part, and named
object references. The convenience methods do not expose paragraph-count,
table-count, or other one-method-per-field APIs.

`SectionTarget::text()` delegates to the bounded stateless `SectionReader`.
`nestedNamedObjects()` returns the current descriptor's typed references rather
than maintaining another traversal model.

## C. Textual view contract

The textual view is a conservative, plain-text projection of the current
section. It walks descendants in document order and emits one line for each
non-empty `text:p` or `text:h`. It also supports text directly in a table cell
or text box when no paragraph exists there. Empty blocks are omitted and each
emitted block is trimmed before joining with a single `\n`.

This means a section containing a heading, paragraph, list items, table cells,
and a text box can be read as deterministic lines such as:

```text
Experience
Builds documents
Alpha
Beta
A1
A2
Box text
```

List items are represented by their contained paragraphs. Table cells are
represented in document order, one line per textual cell; no table markup or
column serialization is implied. Text-box paragraphs participate at their
native document position. Images contribute no text and `svg:title`/`svg:desc`
are not treated as visible section text. Inline spans, bookmarks, and other
non-block inline elements contribute their descendant literal text.

The view is not an HTML serialization, rich-text model, layout measurement, or
lossless whitespace representation.

## D. Nested named objects

`SectionDescriptor::nestedNamedObjects()` and the SectionTarget convenience
method report named descendants in content-document order. The scope is any
named descendant of the section, not only immediate XML children. References
are compact immutable values containing type, native name, and `content.xml`.

The supported reference types are:

- `section` for nested named sections;
- `bookmark` for one reference per named bookmark, regardless of whether it is
  represented by one collapsed marker or a start/end pair;
- `table` for named native tables;
- `frame` for named drawing frames.

This is a shallow identity summary, not recursive content serialization.
Duplicate native names remain visible as references and continue to be handled
by strict typed resolution when independently resolved.

## E. Section and document-part semantics

The current engine discovers sections in `content.xml`, where LibreOffice
stores the native `text:section` containers used by the PRODUCT-01B fixtures.
`styles.xml` is not searched for sections. The section target resolves the
unique named container through the existing strict `TypedTargetResolver`.

Nested sections remain distinct: an outer descriptor reports the inner named
section, while `template->section('Inner')` independently resolves it. The
outer text view includes textual descendants in document order because the
view describes all text contained by that section.

The containing section boundary is the native section element. Siblings before
or after it are excluded from the target's text and nested-object view.

## F. Lifecycle and ownership

Each read operates against the current `OdtDocumentContext`. No descriptor,
DOM node, or text result is cached by a target. After `load()`, `refresh()`, or
another context replacement, an identity-backed target therefore resolves the
current section state; if the section is gone, strict resolution raises the
existing `TargetNotFoundException`.

Read operations do not mutate content, styles, metadata, package resources,
assignment state, or render state. The tests compare serialized content XML
before and after descriptor/text/nested-object reads.

## G. Ownership boundary

```text
OdtTemplate
    → SectionTarget (typed public handle)
        → SectionReader (stateless textual projection)
            → OdtDocumentContext (authoritative DOM)

DocumentInspector
    → immutable section and named-object descriptors

TypedTargetResolver
    → strict section identity resolution
```

`DocumentInspector` remains read-only and owns the existing descriptor
traversal. `SectionReader` does not resolve arbitrary targets and does not
duplicate descriptor construction. No new context or mutable state owner is
introduced.

## H. Compatibility and deferred mutation

All existing APIs remain unchanged, including `inspect()`, typed target
resolution, bookmark replacement, structured insertion, table/frame APIs,
template processing, metadata, PageLayout, and lifecycle operations. The new
methods are additive and read-only.

The following remain explicitly deferred to later slices: section content
replacement, whole-section replacement/removal, cloning, instantiation,
renaming, child mutation, nested-target mutation, style/asset mutation, and
table-row operations.

## I. Limitations

The textual view intentionally does not promise exact visual whitespace,
pagination, table geometry, image accessibility text, fields, tracked changes,
or a reversible document representation. Those semantics require separate
document-model decisions and must not be inferred from this convenience read.

## J. Tests and next step

Focused tests cover mixed section text, headings, lists, tables, text boxes,
nested tables/frames/bookmarks/sections, independent nested resolution, empty
sections, strict missing resolution, and no-DOM-mutation behavior. Existing
addressability, lifecycle, processing, structured-insertion, bookmark, and
sample tests remain part of the regression suite.

The next section slice should define a separate mutation contract for reading
and replacing a section's structural content. It must build on the native
section boundary and this read-only identity model rather than treating the
textual view as replacement content.

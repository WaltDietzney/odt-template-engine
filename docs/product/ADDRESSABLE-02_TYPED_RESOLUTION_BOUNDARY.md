# ADDRESSABLE-02 — Typed Resolution Boundary

## A. Goal

ADDRESSABLE-02 establishes the boundary between native ODF identity and future
document operations. It adds strict, read-only typed target resolution on top
of the ADDRESSABLE-01 inspection model.

The implemented path is:

```text
native identity
    ↓
strict typed resolution
    ↓
typed read-only handle
    ↓
current OdtDocumentContext
```

This slice does not implement bookmark, section, table, or frame mutation.
There are no `replaceText()`, `replaceContent()`, `remove()`, `clone()`,
`instantiate()`, `cloneRow()`, image replacement, or geometry operations on
the new targets.

## B. Evidence reviewed

The boundary is based on:

- PRODUCT-01, PRODUCT-01A, PRODUCT-01B, PRODUCT-01C;
- `ADDRESSABLE-01_INSPECTION_CONTRACT_AND_DESCRIPTORS.md`;
- PRODUCT-01B native fixtures and their XML structures;
- ARCH-05 typed target and structured-insertion documentation;
- ARCH-07 facade and state-ownership documentation;
- `OdtTemplate`, `OdtPackage`, and `OdtDocumentContext`;
- ADDRESSABLE-01 `DocumentInspector`, descriptors, and diagnostics;
- existing `TemplateTarget` and `TemplateTargetResolver`;
- structured insertion, image/frame, table, lifecycle, API-contract, and
  public-sample tests.

The current code confirms that `TemplateTargetResolver` is a read-only,
DOM-backed resolver specialized for existing named frames and tables, while
`DocumentInspector` returns immutable snapshots for sections, bookmarks,
tables, and frames.

## C. Pre-change resolution architecture

Before ADDRESSABLE-02, the repository provided two related but different
capabilities:

```text
OdtTemplate
    → DocumentInspector
        → immutable descriptor snapshot

OdtTemplate / image operations
    → TemplateTargetResolver
        → TemplateTarget containing a DOM node
```

The first path was suitable for discovery but had no live typed handle. The
second path supported existing frame/table implementation operations but was
not a general public addressability model and exposed DOM-backed targets to
internal callers.

ADDRESSABLE-02 adds a third, explicit boundary without changing the existing
resolver contract:

```text
OdtTemplate
    → TypedTargetResolver
        → BookmarkTarget / SectionTarget / TableTarget / FrameTarget
            → current OdtDocumentContext
                → fresh descriptor resolution
```

## D. Typed target model

The implementation provides four concrete read-only target handles:

- `BookmarkTarget`
- `SectionTarget`
- `TableTarget`
- `FrameTarget`

Each target exposes only:

- `name()`;
- `type()`;
- `descriptor()`.

The concrete return type of `descriptor()` is target-specific. A
`SectionTarget` returns a `SectionDescriptor`; a `BookmarkTarget` returns a
`BookmarkDescriptor`; and so on.

The shared `AbstractAddressableTarget` contains only identity/context mechanics
for these concrete classes. It is an implementation base, not a public
universal `Target` abstraction and does not define a generic operation method.

## E. Descriptor versus target semantics

The distinction from PRODUCT-01C and ADDRESSABLE-01 is preserved:

```text
$inspection->section('ExperienceEntry')
    immutable snapshot descriptor

$template->section('ExperienceEntry')
    identity-backed typed handle for the current document
```

Descriptors contain no DOM objects and cannot mutate the document. Targets do
not capture a descriptor at construction and do not store a `DOMElement` or
`DOMNode`. Calling `descriptor()` performs a fresh strict resolution against
the handle's current document context.

This means that a target is not merely a descriptor with mutation methods
attached. It is an identity-bound access boundary whose future operations will
be type-specific.

## F. Public facade API

The smallest coherent strict facade API is now:

```php
$bookmark = $template->bookmark('FirstName');
$section = $template->section('ExperienceEntry');
$table = $template->table('Skills');
$frame = $template->frame('ProfilePhoto');
```

These methods either return the requested concrete read-only target or throw a
typed resolution exception. No generic `$template->target($type, $name)` API
was added, and no nullable `find*()` aliases were added.

`$template->inspect()->section('Name')` already provides explicit nullable
discovery. Adding facade-level nullable aliases would duplicate that contract
without improving the first target boundary.

## G. Strict resolution semantics

Resolution behavior is deterministic:

| Situation | Result |
|---|---|
| Exactly one matching native identity | Typed target handle returned |
| No matching identity | `TargetNotFoundException` |
| Multiple matching identities within the requested type | `AmbiguousAddressableTargetException` |
| Malformed bookmark/range | `MalformedTargetException` |
| Same spelling under another native type | No error; type-specific lookup remains independent |

The resolver first uses the ADDRESSABLE-01 inspector to obtain descriptors,
then applies strict type/name matching. It does not silently select an
unrelated native object and does not expose a raw XPath fallback.

## H. Stale-target and lifecycle decision

The selected model is **identity-backed resolution**:

```text
target handle stores:
    target type
    native name
    one OdtDocumentContext reference

target handle does not store:
    DOMNode / DOMElement
    copied XML state
    independent descriptor snapshot
```

This was selected over the alternatives:

| Model | Assessment |
|---|---|
| Persistent DOM-backed handle | Rejected: detached nodes could appear valid after DOM replacement |
| Identity-backed handle | Selected: small, predictable, re-resolves current state |
| Generation-only handle | Not selected for this slice: useful later if mutation needs optimistic concurrency, but unnecessary for read-only handles |

After `load()` or `refresh()`, the same handle re-resolves its identity in the
same context. If the identity still exists, `descriptor()` reports current
state. If it no longer exists, `TargetNotFoundException` is raised. It never
silently operates on a detached old node.

If a later mutation removes or replaces the target, the existing handle will
likewise re-resolve and either observe the current same-named target or fail.
Mutation-specific stale/generation semantics can be added only when there is a
real operation that needs them.

## I. Document ownership and isolation

Each handle stores exactly one `OdtDocumentContext` reference. It cannot be
used to resolve another template's context, and no global target registry is
introduced.

Two templates can therefore independently resolve the same native name:

```text
Template A → Context A → Section "Profile"
Template B → Context B → Section "Profile"
```

Replacing the DOM documents in Context A has no effect on a handle bound to
Context B. The target classes do not copy or own document state.

## J. Bookmark resolution

`BookmarkTarget` represents a named bookmark/range identity and its current
`BookmarkDescriptor`. It does not pretend that a range is a detachable DOM
container.

The resolver accepts:

- collapsed `text:bookmark` descriptors;
- valid paired start/end marker descriptors.

Malformed or unpaired markers remain visible through `DocumentInspection`,
including their diagnostics, but strict typed resolution rejects them with
`MalformedTargetException`.

The target currently exposes no text replacement or range-content operation.
Its topology remains available through `descriptor()->topology()` for the
future `NAMED-RANGE-01` safety contract.

## K. Section resolution

`SectionTarget` represents a native `text:section` identified by
`text:name`. Its descriptor provides the section name, content summary, and
compact nested named table/frame references.

The section is treated as a structural container, distinct from a bookmark
range. No section content replacement, removal, clone, or template-instance
operation is implemented in this slice.

The identity boundary is intentionally ready for later SECTION slices without
committing to clone naming or subtree mutation behavior prematurely.

## L. Table resolution

`TableTarget` represents an existing native `table:table` identified by
`table:name`. It is distinct from constructed `RichTable` content:

```text
RichTable
    constructed content model for insertion

TableTarget
    existing native table identity in the document
```

The descriptor exposes current row/column summary and containing section where
known. No cell mutation, row cloning, table replacement, or changes to the
existing table APIs are introduced.

## M. Frame resolution

`FrameTarget` represents an existing native `draw:frame` identified by
`draw:name`. Its descriptor reports conservative payload type and basic
geometry where available.

It is distinct from constructed `ImageElement`/`DrawTextBox` content. Existing
`replaceImageByName()` behavior remains on its established path through the
existing resolver and package/resource handling. ADDRESSABLE-02 does not route
that operation through `FrameTarget` and does not add image or text-box
mutation methods.

## N. Relationship with `DocumentInspector`

Responsibilities remain separate:

```text
DocumentInspector
    enumerate and describe a snapshot

TypedTargetResolver
    strictly resolve one requested typed identity

Typed target
    hold identity/context boundary and provide fresh descriptor access
```

`TypedTargetResolver` reuses `DocumentInspector` for descriptor generation
rather than duplicating section, bookmark, topology, table, and frame
discovery rules. It does not make the inspector responsible for mutation.

## O. Relationship with `TemplateTargetResolver`

The existing `TemplateTargetResolver` remains unchanged and continues to
provide DOM-backed resolution for the current frame/table implementation
paths, including its existing ambiguity behavior.

`TypedTargetResolver` is the higher-level addressability boundary for typed
read-only handles. It currently uses descriptors rather than forcing the
existing `TemplateTarget` to become a universal mutable target.

The relationship is therefore:

```text
TemplateTargetResolver
    existing low-level frame/table implementation resolver

TypedTargetResolver
    typed identity-to-handle boundary for addressability
```

Future convergence may be appropriate once mutation contracts exist. It is not
safe to merge the two resolvers by adding speculative generic operations now.

## P. Error model

The new strict resolver uses a small structured exception hierarchy:

- `AddressableTargetException` — common target type/name access;
- `TargetNotFoundException`;
- `AmbiguousAddressableTargetException`;
- `MalformedTargetException`.

Each exception exposes `targetType()` and `targetName()`. This lets developers
and agents branch on a stable reason and identity without parsing exception
prose.

No `StaleTargetException` is needed for the selected read-only identity-backed
model: a changed or removed identity is resolved against current state and
produces either a current descriptor or `TargetNotFoundException`. A future
mutation contract may add generation-specific errors if required.

## Q. Capability exposure

No generalized capability framework was added. The concrete target type and
descriptor topology currently provide the useful read-only distinction.

Mutation capabilities remain deferred until their operation semantics are
actually implemented and characterized. In particular, a bookmark descriptor
can report `mixed_block` or `list_spanning`, but ADDRESSABLE-02 does not claim
that either topology supports structured mutation.

## R. Compatibility

The change is additive:

- existing `inspect()` behavior remains available;
- existing `TemplateTargetResolver` behavior is unchanged;
- `setElement()` is unchanged;
- `replaceImageByName()` is unchanged;
- table construction APIs are unchanged;
- placeholder syntax, foreach, metadata, PageLayout, and lifecycle behavior
  are unchanged;
- no existing API was deprecated or removed.

The new typed facade methods introduce no mutation and do not alter generated
ODT content. Package, DOM, style, asset, and render state remain owned by the
existing architecture.

## S. Tests

Added focused resolution tests covering:

- successful resolution of all four native types;
- type-specific same-name resolution;
- strict missing-target errors and structured identity fields;
- duplicate same-type ambiguity;
- malformed bookmark inspection versus rejected target resolution;
- descriptor refresh after document replacement;
- deterministic failure after identity removal;
- isolation of two independent document contexts;
- facade resolution without mutation methods;
- facade target behavior after `load()`.

The ADDRESSABLE-01 and ADDRESSABLE-02 focused tests pass:

```text
16 tests, 66 assertions
```

The broader relevant suite passes:

```text
50 tests, 464 assertions
```

The complete project suite passes:

```text
121 tests, 913 assertions
```

## T. Limitations and deferred mutation

Explicitly deferred:

- `NAMED-RANGE-01` bookmark text mutation and topology-safe operations;
- section content mutation and removal;
- section clone/template-instance semantics;
- deterministic nested identity rewriting;
- table cells and row cloning;
- frame image/text-box/geometry mutation;
- stale-generation semantics for concurrent or mutation-heavy workflows;
- nullable facade aliases beyond inspection lookup;
- broad document/style/asset inspection.

## U. Recommendation for NAMED-RANGE-01

The next slice should be:

> **NAMED-RANGE-01 — Bookmark inspection and safe text mutation**

It should use `BookmarkTarget` and its fresh descriptor, begin with topology-
characterized text replacement only, and reject unsupported structured range
operations explicitly. It must preserve the distinction between a bookmark
range and a section container and should validate the result through ODF/XML
checks before considering visual regression for changed document output.

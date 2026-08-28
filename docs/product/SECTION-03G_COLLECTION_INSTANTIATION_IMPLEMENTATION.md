# SECTION-03G — Collection Instantiation Implementation

## A. Goal and evidence

SECTION-03G implements the collection contract from SECTION-03F on top of the
existing SECTION-03E local section scope. The implementation was checked
against the SECTION-03A–F documentation, the addressable document model,
template-structure preservation, lifecycle behavior, and the native Sample-25
fixture.

## B. Public API

`SectionTarget::instantiateMany(array $items): array` is the additive public
entry point. Each item must be an associative array; callers continue to use
unsuffixed template keys. The result is an ordered list of live
`SectionTarget` instances. No declarative nested mapping was added.

## C. Architecture

Responsibilities remain bounded:

```text
SectionTarget
    → SectionCollectionInstantiationService
    → SectionInstantiationService
    → SectionCloneService
```

`SectionRemovalService` is an internal prototype-removal primitive. Existing
single-instance binding, identity rewriting, and structure-preserving text
replacement remain responsible for their original concerns.

## D. Collection lifecycle

The operation is `prototype → ordered instances → prototype removal`. The
prototype is located and validated first, instances are created in caller
order, and the prototype is removed only after every item succeeds.

## E. Zero-item behavior

An empty list creates no clone, removes the selected prototype, and returns
`[]`. The same rule applies to a local `ActivityEntry` collection. Only the
owned section subtree is removed.

## F. Single and N-item behavior

One item creates one detached-prepared instance and then removes the prototype.
N items return exactly N targets. The prototype is never bound in place, so the
collection path has uniform identity and structure behavior.

## G. Input ordering

The existing “insert after the last family member” logic is reused by repeated
single-instance calls. Consequently A, B, C remain A, B, C in the final DOM;
prototype removal does not invert or reorder them.

## H. Prototype removal

The internal removal service removes exactly one `text:section` node. It does
not remove siblings, styles, resources, headings, or static list items, and it
does not expose a general public `remove()` API.

## I. Atomicity

Collection creation records every inserted instance. If a later item fails,
created instances are removed in reverse order and the prototype remains
usable. Prototype removal is last; a removal failure is reported rather than
reported as success. This is a bounded rollback mechanism, not a general DOM
transaction framework.

## J. Identity allocation

Each instance uses the current SECTION-03C/03E allocator. Repeated creation
therefore produces deterministic native names and nested family names such as
`ExperienceEntry_1` and `ActivityEntry_1_1`. No process-global counter or
resource identity redesign was introduced.

## K. Returned targets and prototype lifecycle

Returned targets are identity-backed and immediately support `text()`,
`descriptor()`, and owner-scoped nested resolution. After successful
finalization, the original prototype target fails strict descriptor/text lookup
because its physical section no longer exists. After a failed collection, the
same target remains usable.

## L. Nested collections

The explicit workflow is:

```php
$experiences = $template->section('ExperienceEntry')->instantiateMany($jobs);
foreach ($experiences as $i => $experience) {
    $experience->section('ActivityEntry')->instantiateMany($activities[$i]);
}
```

Each local collection removes only its own `ActivityEntry` prototype. Local
resolution remains owner-scoped and global strict resolution is unchanged.

## M. Local prototype finalization

After local finalization, generated names such as `ActivityEntry_1_1` remain
inside `ExperienceEntry_1`, while `ActivityEntry_1` is absent. A second owner
has an independent family such as `ActivityEntry_2_1`; no activity crosses the
outer section boundary.

## N. Bookmark lifecycle

Bookmark nodes inside removed prototypes disappear with those sections.
Rewritten bookmarks in generated instances remain, including paired
`Company_*` and `Activity_*` markers and collapsed `FromTo_*` markers. No
bookmark or resource garbage collection is attempted.

## O. Static sibling behavior

Sample 25 has static activity list items outside `ActivityEntry`. Collection
finalization deliberately leaves them untouched. A fully dynamic template must
place the complete repeatable activity unit inside the prototype; changing the
fixture’s authoring is outside this implementation.

## P. Style and resource semantics

Instances continue to use native subtree cloning. Paragraph/list/span styles,
bookmark topology, custom-shape context and any referenced package resources
remain preserved or shared according to earlier contracts. No Style Context,
Asset Context, resource duplication, or orphan cleanup was introduced.

## Q. Load/save behavior

Finalized collections survive save/reopen using only physical ODT structure.
The prototype is absent, generated targets remain addressable, and no hidden
PHP lifecycle state is required.

## R. N×M acceptance

Focused integration coverage creates six outer records and local activity
collections with counts `3 / 1 / 4 / 0 / 2 / 5`. It verifies exact counts,
owner isolation, order, prototype absence, values, and clean inspection
diagnostics.

## S. Sample-25 handling

The public Sample-25 script now uses `instantiateMany()` for three experiences
and explicit local activity collections of 3/2/4 items. The generated output
is a local validation artifact and is not committed. The known static sibling
bullets remain by ownership rule.

## T. Compatibility

`instantiate()` remains prototype-preserving. `clone()`, nested `section()`,
template processing, bookmark operations, structured insertion, resource
handling, and strict document inspection remain available without renamed or
removed APIs. The sample explorer required no change because Sample 25 was
already registered.

## U. Limitations and deferred mapping

This slice does not add declarative recursive data, automatic prototype
resurrection, a collection-family handle, public `remove()`, table-row
repetition, local bookmark/table/frame APIs, Style/Asset Contexts, resource
garbage collection, foreach redesign, or frame layout changes. Late additions
after finalization require a future explicit collection handle or must occur
before finalization.

## V. Recommendation

Use explicit collection targets as the stable low-level primitive. Any future
convenience API should map nested arrays to these local operations without
weakening ownership boundaries or strict identity resolution.

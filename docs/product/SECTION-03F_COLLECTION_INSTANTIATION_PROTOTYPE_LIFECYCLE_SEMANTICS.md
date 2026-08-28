# SECTION-03F — Collection Instantiation and Prototype Lifecycle Semantics

## A. Problem

SECTION-03E proves explicit hierarchical instantiation, but `instantiate()` is
intentionally prototype-preserving. The current Sample-25 workflow therefore
leaves the document-level `ExperienceEntry` prototype and each local
`ActivityEntry` prototype visible. That is useful during construction but is
not a suitable final collection document: unresolved prototype expressions and
an extra visible item remain.

This slice defines the collection/finalization contract. It does not implement
`instantiateMany()`, `remove()`, or prototype finalization.

## B. Evidence reviewed

The review covered SECTION-03A–E, SECTION-02A–D, TEMPLATE-STRUCTURE-01A–D,
ADDRESSABLE-01/02, PRODUCT-01C, ARCH-05, ARCH-07, the current clone/index and
local-resolution implementation, lifecycle behavior, tests, and the native
Sample-25 template/output.

## C. Terminology

- **Prototype:** an authoring-time native `text:section` used as the source of
  instances.
- **Instance:** a uniquely named, cloned and data-bound section.
- **Collection prototype:** a prototype intended to produce zero or more
  sibling instances.
- **Local prototype:** a nested prototype owned by one outer section instance.
- **Finalized collection:** a collection whose requested instances succeeded
  and whose prototype is no longer visible.

`clone()` remains structural identity work, `instantiate()` remains one
prototype-preserving instance, and a future `instantiateMany()` is a separate
collection operation.

## D. Collection semantics

The future collection operation is conceptually:

```text
prototype + ordered data → N instances → remove prototype
```

The final visible count is exactly the input count. A zero-item collection
produces no instances and removes its collection prototype. A one-item
collection still uses the normal detached clone path; it does not mutate the
prototype in place.

## E. Prototype lifecycle

The phases are:

```text
AUTHORING → EXPANSION → FINALIZATION → FINAL
```

The prototype remains available during expansion. It is removed only after all
requested instances and their required nested work have succeeded. Removal is
not configurable in this first collection contract: finalization always hides
the collection prototype.

## F. Nested prototype lifecycle

Each outer instance owns its own nested prototype. For example, the local
`ActivityEntry_1` under `ExperienceEntry_1` is unrelated to `ActivityEntry_2`
under `ExperienceEntry_2`. After that owner's activity collection succeeds,
only its local `ActivityEntry_1` prototype is removed. Other owners and the
document-level prototype are unaffected.

## G. Zero-item semantics

`instantiateMany([])` should return `[]` and remove the selected prototype.
For an empty activity list, the section containing the repeatable list item is
removed so no unresolved activity bullet remains. This requires the template
author to make the `ActivityEntry` section contain the complete repeatable
unit, including its list/list-item structure.

## H. Ordering

Input order is document order. The expected sequence is prototype, instance A,
instance B, instance C during expansion, followed by removal of the prototype.
Nested activities use the same local ordering rule. Prototype removal removes
only its own node and does not reorder surrounding siblings.

## I. Exact-count contract

After finalization:

```text
visible instances = input records
```

For activity counts `3 / 1 / 4 / 0 / 2 / 5`, the document must contain six
outer instances and exactly those six local activity counts. There is no hidden
prototype, phantom item, or extra static repeatable item included by the
engine.

## J. Static sibling content

The current Sample-25 template has the `ActivityEntry` prototype followed by a
separate static list containing “Teams koordiniert ...” and “Agiles Arbeiten
...”. Those list items are outside the section and therefore are not owned by
the activity collection. A future collection operation must leave them alone;
the engine cannot safely infer that they are accidental duplicates.

## K. Authoring contract

For a fully data-driven collection, the repeatable section must contain exactly
one repeatable structural unit. The correct activity shape is:

```text
ExperienceEntry
└── ActivityEntry
    └── one bullet template
```

The shape with static bullet siblings outside `ActivityEntry` is valid native
ODF but not a fully dynamic activity-template model. Sample 25 is valuable
characterization evidence and should not be silently rewritten in this slice.

## L. `remove()` assessment

A bounded future `SectionTarget::remove()` would remove exactly one native
section from its current parent while preserving unrelated siblings. It would
not clean unused styles or package resources. Collection finalization needs
this capability internally or an equivalent node-removal primitive, but this
slice does not expose or implement it.

## M. `instantiateMany()` direction

The preferred future additive API is:

```php
$instances = $template
    ->section('ExperienceEntry')
    ->instantiateMany($items);
```

The return value should be `list<SectionTarget>` (or a small immutable result
if diagnostics later justify one). Input should initially be an explicit list
of associative scalar-binding arrays. Automatic type coercion and declarative
nested mappings are deferred.

## N. `instantiate()` versus `instantiateMany()`

These operations intentionally differ:

- `instantiate($data)` creates one instance and leaves the prototype available.
- `instantiateMany($items)` creates the complete requested collection and
  finalizes/removes the prototype.

The latter must not be implemented as a loop that leaves the prototype behind.

## O. Finalization

Prototype removal is collection-owned and terminal for that prototype identity.
After finalization, the original `ExperienceEntry` or local `ActivityEntry`
prototype no longer resolves. Generated instances remain addressable by their
physical names. A later addition should use a higher-level collection handle in
future work, not hidden PHP-memory prototype storage.

## P. Collection atomicity

The desired contract is all-or-nothing for one collection orchestration call:

```text
all instances succeed → remove prototype → commit
any preparation fails  → retain prototype and remove new instances
```

The existing detached clone path provides the right per-instance foundation.
A future implementation should stage or record created nodes and perform
prototype removal last. It should not introduce a general transaction
framework solely for this operation.

## Q. Nested collection atomicity

For a single high-level collection expansion, the preferred policy is also
all-or-nothing: if a later owner's nested activity collection fails, the outer
collection remains retryable rather than producing a partially finalized CV.
Whether a future explicit low-level API permits retaining earlier successful
outer instances must be decided separately; it is not implicit here.

## R. Addressability after finalization

The removed prototype must no longer resolve by its native name. Physical
instances such as `ExperienceEntry_1` and `ExperienceEntry_2` remain strict,
unique targets. No collection-family handle is added yet, so late additions
after finalization are intentionally unsupported through the prototype name.

## S. Late additions

Collection expansion is normally a construction-time operation. Callers needing
late additions should instantiate individually before finalization or use a
future collection object that explicitly owns the lifecycle. The engine must
not retain an invisible prototype or sidecar state after save/reopen.

## T. Save/reopen

After a finalized collection is saved and reopened, the prototype is physically
absent, generated sections and bookmarks remain addressable, and no unresolved
prototype expressions remain. All lifecycle semantics must be derivable from
the ODT itself rather than PHP process state.

## U. Bookmark lifecycle

Removing a prototype also removes its bookmark nodes. Rewritten bookmark names
inside generated instances remain, for example `Company_1`, `FromTo_1`, and
`Activity_1_1`. Paired/collapsed topology in those instances is retained. No
orphan cleanup or implicit bookmark rebinding is performed.

## V. Identity implications

All identities are allocated before finalization while the prototype is still
available. The current hierarchical physical naming remains suitable:
`ActivityEntry_1_1` belongs to the local family under `ExperienceEntry_1`.
After removal, no new index is allocated from the missing prototype in this
first contract. Native names remain globally unique and strict resolution is
not weakened.

## W. Sample-25 target final state

The desired future finalized Sample-25 result is three visible experience
instances with 3/2/4 actual activities. The document-level prototype and each
local activity prototype are absent. The current static list siblings remain
because they are outside the prototype; a clean all-data-driven public demo
would require an intentional fixture authoring correction in a later task.

## X. Zero-activity case

An experience with `activities = []` retains its position, company and date,
but has no activity prototype bullet. Empty list spacing is determined by the
native section/list structure; the engine should remove the owned repeatable
section rather than guess at neighboring paragraph spacing.

## Y. Zero-experience case

An empty experience collection removes `ExperienceEntry` but does not remove
the “BERUFSERFAHRUNG” heading because that heading is outside the prototype.
Conditional visibility of such a heading is an authoring responsibility.

## Z. Ownership boundary

A prototype owns only its section subtree. It does not own headers, separators,
decorative shapes, static siblings, neighboring list items, styles, or package
resources outside that subtree. Finalization must not delete those objects by
inference.

## AA. API ergonomics

The explicit future workflow remains observable and predictable:

```php
$prototype = $template->section('ExperienceEntry');
foreach ($jobs as $job) {
    $experience = $prototype->instantiate([
        'note' => $job['note'],
        'position' => $job['position'],
    ]);
    $activities = $experience->section('ActivityEntry');
    // instantiate activity records locally, then finalize that collection
}
```

Automatic nested array mapping is a later ergonomics layer, not part of the
first collection contract.

## AB. Collection and foreach

Native section collection instantiation is distinct from the existing template
language `foreach`. Foreach evaluates template-language control structures;
section collections repeat designer-authored native ODF objects. The two may
coexist but must not be silently merged.

## AC. Implementation test plan

The future implementation must cover zero/one/N items, exact visible counts,
input order, returned targets, prototype removal, nested 3/1/4/0/2/5 counts,
local isolation, bookmark retention/removal, save/reopen, lifecycle after
load/refresh, document validity, failure atomicity, static siblings remaining
untouched, and strict resolver behavior after finalization.

## AD. Limitations

This slice does not implement collection APIs, section removal, collection
transactions, recursive declarative mappings, automatic heading conditions,
table-row repetition, local target families beyond sections, resource/style
garbage collection, Style/Asset Contexts, foreach redesign, or frame layout.

## AE. Recommendation for the implementation slice

Implement a bounded `instantiateMany()` service only after its transaction and
prototype-removal mechanics are tested. It should use the existing detached
clone/local-scope services, remove prototypes last, return typed instances, and
make zero-item and nested ownership semantics explicit. Keep `instantiate()`
prototype-preserving and do not infer ownership of static siblings.

# SECTION-03 — Final review

## A. Scope and evidence

This review covers SECTION-03A through SECTION-03G and the supporting
TEMPLATE-STRUCTURE-01A through 01E work. It was checked against the current
implementation, focused tests, the real LibreOffice-authored Sample-25
template, its generated package, and the documented addressable model.

Reviewed commits include `5391ba2`, `552ed6b`, `399e97a`, `521960e`,
`ca0b80b`, `204bb59`, `39d5624`, `5ad8350`, and the current fixture-test
characterization correction.

## B. Architecture end state

The bounded public model is:

```text
OdtTemplate::section(name)
    → SectionTarget
        → inspect/read/replaceContent/clone/instantiate/instantiateMany
        → owner-scoped section(name)
```

`SectionCloneService` owns native cloning and identity rewriting;
`SectionInstantiationService` owns one detached data-bound instance;
`SectionCollectionInstantiationService` owns ordered collection expansion and
finalization; `SectionRemovalService` removes only the selected prototype.
Template projection/normalization/replacement preserve native ODF structure.

## C. Clone and identity verdict

Exact cloning preserves the native subtree, lists, styles, frames, sections,
bookmarks and split expressions. `clone()` remains the prototype-preserving
single-clone operation. The rewritten path allocates deterministic collision-
safe names for sections, nested sections, bookmarks, tables, frames,
technical IDs and expression identities. Existing suffixed/generated sections
are not silently treated as new prototypes.

Temporary duplicate identities are confined to the exact-clone intermediate
operation; rewritten and finalized documents are uniquely addressable.

## D. Instantiation and nested scope

`instantiate($data)` clones, rewrites, binds only own-scope expressions and
leaves its prototype available. Expressions in descendant sections remain
owned by those sections. `SectionTarget::section(name)` resolves the nearest
unique descendant relative to its owner, so separate experience instances have
independent ActivityEntry families while strict document-global resolution
remains strict.

## E. Collection and lifecycle verdict

`instantiateMany(array $items): array` creates exactly one returned/live target
per item in input order, then removes the prototype. Empty collections remove
the prototype and return `[]`; single collections still clone rather than bind
in place. Nested collection finalization removes only the local prototype.
The prototype target becomes invalid after successful removal, while failed
expansion rolls back inserted instances and leaves it usable.

The implementation provides bounded rollback for preparation/instance failure;
prototype removal is the final commit step. This is not a general DOM
transaction framework. No declarative recursive mapping or public general
`remove()` API was added.

## F. Template-structure preservation

The 01A–01D boundary distinguishes logical expressions from physical ODF
fragments. Safe canonical repairs preserve styles and sibling order; different-
style and bookmark-intersecting expressions retain their native topology when
physical repair would require guessing. Replacement mutates contributing text
nodes rather than rebuilding surrounding spans, lists, sections or markers.

01E establishes that literal spaces, punctuation, `text:s`, and no-separator
adjacency are authored content. The covered forms `{{a}}{{b}}`, `{{a}} {{b}}`,
`{{a}}-{{b}}`, and `{{a}}/{{b}}` remain semantically unchanged through the
relevant processing paths.

## G. Sample-25 benchmark

The current showcase assigns Max Mustermann, profession, phone, address and
email values, then expands three ExperienceEntry records with 3 / 2 / 4 local
activities. The source fixture's existing `adress` spelling is preserved. A
historical malformed `{{phone]]` authoring token was corrected to
`{{phone}}`; this is a fixture correction, not engine behavior.

Generated output contains three outer instances, no outer prototype, no local
ActivityEntry prototypes, rewritten native identities, and no unresolved
template expressions. The source header and native bookmark/list/section
structure remain represented in the package. Static siblings outside a
repeatable prototype remain an authoring-owned boundary and are not guessed or
deleted by the engine.

The user-provided LibreOffice review reports convincing CV rendering, visible
and editable native sections/bookmarks, and correctly rendered dynamic
experiences and activities. Remaining concerns are template-level: the phone
text box is narrow and the fixed-height sidebar is not robust for all content.

## H. Lifecycle and package review

The save/reopen tests resolve generated sections after finalized outer and
nested collections without PHP-only lifecycle metadata. Package validation for
Sample 25 passes `unzip -t` and XML parsing of `content.xml`, `styles.xml`,
`meta.xml`, and `META-INF/manifest.xml`. Inspection after finalization matches
the physical DOM and reports no clone-created duplicate identities.

## I. Test status and fixture coupling

The focused clone, instantiation, nested-scope, collection, replacement,
normalizer, inspector, lifecycle and public-sample tests pass. The full suite
was reproduced at 214 tests / 1446 assertions and initially failed only at
`SectionCloneTest::testExactClonePreservesNativeSubtreeAndInsertsAfterSource`:
the test expected four lists but the intentional Sample-25 cleanup now has one
list per cloned ExperienceEntry, producing two. The assertion was narrowed to
the current intentional fixture state; this is a stale characterization
assertion, not a production regression. The coupling itself is a testing
design warning: future architecture tests should use stable dedicated fixtures
where practical.

## J. Compatibility and deferred work

Existing public methods, bookmark operations, structured insertion, resources,
filters, control structures, repeated lifecycle behavior and SECTION-02 remain
in scope and regression-tested. Historical design documents retain their
stage-specific future wording where appropriate; current implementation docs
describe the completed collection behavior.

Deferred work includes declarative recursive mapping, generic remove/clear
operations, document import and identification, metadata integrity/signatures,
StyleContext, Frame Layout, table-row repetition, and broader rich structural
processing. `DOCUMENT-IMPORT-01` and template-authoring/layout guidance are
recorded as future directions.

## K. Final decision

**GO for SECTION-03 completion.** The required chain is complete:

```text
native section → inspect/address → exact clone → identity rewrite
→ own-data binding → nested scope → nested binding
→ ordered collection → prototype finalization → save/reopen native ODT
```

The GO is conditional only on the documented boundaries above: no visual claim
is made by agent-side rendering, and future declarative mapping, layout
redesign, import, and style architecture remain separate work.

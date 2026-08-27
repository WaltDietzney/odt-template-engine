# SECTION-03C — Deterministic Clone Identity Rewrite

## A. Goal

SECTION-03C turns the duplicate-identity intermediate state from SECTION-03B
into a uniquely addressable exact native section clone. The operation clones a
prototype subtree, rewrites the identities that belong to that subtree, and
only then inserts it into the live content DOM. It does not bind data and does
not implement `instantiate()`.

## B. Evidence reviewed

The implementation was checked against PRODUCT-01C, ADDRESSABLE-01/02,
SECTION-01, SECTION-02A-D, SECTION-03A/B, the FRAME-LAYOUT-01 documents, the
ARCH-05 structured materialization documents, and the ARCH-07 ownership
documents. The primary fixture is the real
`samples/templates/sample_25_sectionClone.odt`.

## C. Sample-25 topology

`ExperienceEntry` occurs once and contains `ActivityEntry`, the paired
`Company` and `Activity` bookmark ranges, the collapsed `FromTo` bookmark,
lists, style references, and the expressions `{{note}}` and `{{position}}`.
The logical `{{activity}}` expression is split across text spans with bookmark
markers intersecting its character stream. The source section contains no
`xml:id`, table, or drawing-frame descendant. The unusual custom-shape parent
is preserved as the insertion context.

## D. Clone-family and index semantics

`SectionTarget::clone()` is now the small public entry point. It returns a new
`SectionTarget` for the rewritten section. Only a prototype identity may be
cloned in this first contract; a name ending in a numeric clone suffix is
rejected. This avoids prematurely defining nested clone-family semantics.

The allocator derives its index from the current document, not process state.
It chooses the first positive index for which every identity in the source
subtree has a free same-type suffixed name. Thus repeated prototype clones
produce `_1`, `_2`, `_3`, while a pre-existing `_1` causes the next compatible
index to be selected. The same index is used consistently for all nested
identity families.

## E. Native section and nested-section rewrite

The cloned root `text:name` changes from `ExperienceEntry` to
`ExperienceEntry_1`. The nested `ActivityEntry` becomes `ActivityEntry_1` and
remains physically inside the cloned outer section. The source remains
`ExperienceEntry` and remains the reusable prototype.

## F. Bookmark, table, and frame rewrite

All cloned bookmark forms retain their exact marker topology while their
`text:name` values are rewritten consistently: `Company_1`, `FromTo_1`, and
`Activity_1`. Start and end markers receive the same mapped name; collapsed
bookmarks remain collapsed. `table:name` and `draw:name` are rewritten for
tables, frames, and named custom shapes inside the clone when present. Names
remain type-specific; equal spellings across different native types do not
collide.

No markers, structure, frame geometry, anchor, wrap, or drawing properties are
reinterpreted.

## G. Technical IDs

`xml:id` values inside the detached clone receive a deterministic suffixed
value, avoiding IDs already present outside the source. Local cloned attribute
references whose value is the old ID or `#old-id` are updated at the same time.
The source ID is unchanged. The current fixture has no technical IDs, so no
external-reference case is silently accepted; external technical references
remain a future characterization topic.

## H. Template-expression rewrite

The detached clone rewrites the bounded grammar already recognized by the
engine:

```text
{{name}}              → {{name_1}}
{{upper:name}}        → {{upper:name_1}}
{{date:value|Y-m-d}}  → {{date:value_1|Y-m-d}}
{{#if:active}}        → {{#if:active_1}}
{{#foreach:items}}    → {{#foreach:items_1}}
```

The filter/control keyword and options are not suffixed. Closing control
markers (`#else`, `#endif`, `#endforeach`) remain unchanged. Unsupported
expression forms fail before insertion rather than being rewritten as guessed
text.

## I. Split expressions and transparent markers

Logical expressions are reconstructed per bounded text-flow scope. Text spans
and bookmark marker elements are transparent for recognition; paragraph,
list-item, table-cell, section, and text-box boundaries are not crossed. The
rewriter changes only the text containing the closing `}}`, inserting the
suffix before those braces. Consequently the Sample-25 span fragmentation and
the `Activity` marker positions remain unchanged.

Expressions outside the cloned subtree are never rewritten. The source still
contains `{{note}}`, `{{position}}`, and `{{activity}}`.

## J. Styles, resources, and layout

The native subtree is cloned directly with `cloneNode(true)`. Paragraph,
character, list, frame, and custom-shape style references remain unchanged;
no style definitions are generated. Resource hrefs remain unchanged and
shared package assets are not copied. The clone operation does not alter frame
layout semantics or invoke the structured materializer.

## K. Atomic rewrite plan

The service resolves the unique prototype, creates a detached deep clone,
allocates one compatible index for all native names, prepares technical-ID and
template-expression rewrites, applies them to the detached clone, and inserts
the completed result immediately after the source. Any unsupported expression,
identity condition, or insertion failure occurs before the live DOM receives
the clone.

## L. Inspector, resolver, and returned target

After one rewritten clone, inspection exposes `ExperienceEntry` and
`ExperienceEntry_1`, the corresponding nested names, and suffixed bookmarks
without clone-created duplicate-name diagnostics. Strict resolution succeeds
for the returned `SectionTarget` and its descriptor/text read the current
rewritten document state.

The exact SECTION-03B service remains available as an internal characterization
operation and continues to demonstrate the intentionally ambiguous duplicate
state. It is not the public clone path.

## M. Save, reopen, and lifecycle

The returned target is identity-backed. Repeated prototype cloning derives
indices from the current DOM, so save/reopen and load/refresh do not rely on
PHP-held counters. Tests cover save/reopen, repeated clones, separate template
instances, and a target retained across `load()`.

## N. Already-cloned sections and nested ActivityEntry

Cloning `ExperienceEntry_1` is rejected in this slice. No `_1_1` naming or
nested repeated `ActivityEntry` instantiation is defined. Future nested
instantiation may build on the same identity-plan boundary but requires its own
semantics.

## O. Visual findings

The agent environment did not provide a LibreOffice render. Local visual
validation remains required with:

```sh
./tools/visual-regression/render-odt.sh /tmp/section-clone-rewritten.odt
```

Expected output is the original CV job block followed by an identical-looking
clone, with only native/template identities suffixed.

## P. Tests and compatibility

Focused tests cover first/second/third clone allocation, occupied-index gaps,
source immutability, nested sections, paired/collapsed bookmarks, split
expressions, filters, technical IDs, strict resolution, save/reopen, reload,
and prototype-only semantics. The full existing suite remains green. No
existing API was removed, renamed, or deprecated; `clone()` is the only
additive public API in this slice.

## Q. Limitations and SECTION-03D

This slice does not bind application data, evaluate cloned expressions, clone
rows, instantiate nested activities, allocate external technical references,
duplicate resources, or define a public `instantiate()` contract. It also does
not redesign TemplateProcessor, styles, assets, or frame layout.

SECTION-03D should define data-bound instantiation over the rewritten clone,
including local value scope, control structures, nested repeated sections,
external-reference handling, and final validation of the generated instance.

# SECTION-03E — Nested Section Instantiation and Hierarchical Scope

## A. Problem

SECTION-03D made one native section instance and bound scalar values inside it.
Sample 25 contains a repeatable `ExperienceEntry` with a nested repeatable
`ActivityEntry`. The nested section must be instantiated relative to the owning
experience, without leaking values or clone indices between experiences.

## B. Evidence reviewed

The implementation was checked against the SECTION-03A–D contracts, the
addressable document model, template-structure and structure-preserving
replacement work, and the native XML in
`samples/templates/sample_25_sectionClone.odt`.

## C. Sample-25 topology

`ExperienceEntry` is a named section inside a LibreOffice-authored custom-shape
text flow. It contains `note`, `position`, the paired `Company` bookmark, the
collapsed `FromTo` bookmark, and a nested `ActivityEntry`. `ActivityEntry` is a
section containing a list/list-item and a split, styled `activity` expression.
The `Activity` bookmark intersects that expression. The outer section and the
nested section are cloned as native DOM subtrees; lists, styles, bookmarks and
the unusual custom-shape parent are not rebuilt.

## D. Physical and semantic identity

Native names remain document-global and unique. A nested section also has a
local semantic owner: the containing `SectionTarget`. This avoids weakening
strict global resolution or allowing duplicate ODF names.

## E. Local ownership model

Expressions belong to the nearest containing named section. Outer binding
stops at nested section boundaries. Thus an outer experience can be created
with only `note` and `position`; its nested `activity` prototype remains
unresolved for later local processing.

## F. Nested section boundary

The existing logical text traversal now has an internal “owned by this
section” mode. It skips descendant `text:section` nodes for expression
discovery and replacement. No nested node is removed or flattened.

## G. Local target resolution

`SectionTarget::section(string $name)` is a small additive local resolver. It
searches only descendants of the current section. For a generated outer
section, the resolver maps the logical prototype name `ActivityEntry` to the
physical nested prototype `ActivityEntry_1` (or `_2`, etc.). Generated nested
instances are not mistaken for the prototype.

## H. Public API

The supported workflow is:

```php
$experience = $template->section('ExperienceEntry')->instantiate([
    'note' => 'Aktuelle Position',
    'position' => 'Senior Projektmanager',
]);

$activities = $experience->section('ActivityEntry');
$activities->instantiate(['activity' => 'Leitung eines Teams.']);
```

No general context object or builder DSL was introduced. The target retains
the document context and a bounded owner name internally.

## I. Clone-family semantics

The existing SECTION-03C outer naming contract is preserved. The source nested
prototype remains `ActivityEntry`; the nested prototype in `ExperienceEntry_1`
is `ActivityEntry_1`. Instances made from that local prototype are
`ActivityEntry_1_1`, `ActivityEntry_1_2`, and so on. The corresponding family
under `ExperienceEntry_2` is `ActivityEntry_2_1`, `ActivityEntry_2_2`, etc.

This hybrid scheme preserves the established `ActivityEntry_1` result while
making local ownership explicit. The first numeric component identifies the
outer family and the final component identifies the local nested instance.

## J. Physical naming strategy

Allocation still derives from the current DOM and never uses process-global
state. Native identity rewrite is reused from SECTION-03C. Styles and package
resources remain shared by reference. No resource or style duplication was
added.

## K. Expression ownership

Identity rewriting may recurse through the complete cloned subtree so native
names remain unique. Data binding is different: outer binding sees only
expressions owned directly by the outer section; nested binding sees the local
nested section's expressions.

## L. Outer binding behavior

`ExperienceEntry` can be instantiated without `activity`. Its clone retains
the rewritten nested prototype expression, such as `{{activity_1}}`, ready for
local nested instantiation. The document-level prototype remains unchanged.

## M. Nested binding behavior

Nested callers provide unsuffixed keys such as `activity`. The service maps
those keys to the generated expression identity (`activity_1_1`, for example)
and delegates evaluation/replacement to the existing structure-preserving
TemplateProcessor boundary.

## N. Identity and bookmark rewrite

Nested section names, bookmark names and expression identities are rewritten by
the existing SECTION-03C machinery. `Activity` becomes `Activity_1_1` for the
first nested instance. Marker order and paired/collapsed bookmark topology are
unchanged. Company and FromTo in the outer clone are likewise preserved.

## O. Insertion ordering

The nested clone service inserts after the last generated sibling in the local
family, producing prototype, A, B, C. Outer instantiation retains its existing
prototype-family ordering. A failed preparation occurs before insertion.

## P. Prototype retention

Both the document-level `ExperienceEntry` prototype and each local
`ActivityEntry` prototype remain visible and reusable. Automatic prototype
removal is deliberately deferred.

## Q. Target lifetime

Targets are identity-backed rather than DOM-node-backed. A retained outer target
can resolve its local prototype and instantiate repeatedly. After load/reopen,
the same relationship is derived from native containment and deterministic
physical names.

## R. Save/reopen

The generated Sample-25 package is ZIP-valid and all XML parts parse. Outer and
nested native names, bound text, unresolved prototypes and local containment
survive save/reopen. No PHP-only instance state is required.

## S. N×M behavior

The integration coverage creates three experiences with 3, 2 and 4 nested
activities. Values remain inside their owning outer sections, activity order is
caller order, and each local family starts at its own local index.

## T. Atomicity

Outer instantiation keeps SECTION-03D's detached clone transaction. Nested
instantiation also clones, rewrites and binds while detached; a binding failure
does not insert that nested clone. Earlier successful nested instances and the
local prototype remain unchanged.

## U. Ambiguity

Global resolution remains strict and physical-name based. Local resolution is
the supported way to address a nested prototype inside a generated outer
instance. The resolver does not expose DOM nodes or suppress duplicate-name
diagnostics.

## V. Style and resource behavior

Native paragraph, list, span, bookmark, custom-shape and frame structure is
cloned by reference-preserving DOM copy. No Style Context, Asset Context,
resource duplication, orphan cleanup, or frame-layout redesign was introduced.

## W. Sample-25 result

The public sample now demonstrates three outer instances with 3/2/4 activities
using local target calls. It leaves the manually authored template unchanged;
the generated ODT is a local output artifact and is not part of the change.

## X. Compatibility

Existing outer `instantiate()` and `clone()` APIs remain available. The only
new public capability is local nested section resolution. Outer binding now
correctly respects nested ownership, which is required by the hierarchical
contract; callers that want activity values instantiate the nested prototype
explicitly.

## Y. Limitations

This slice does not implement declarative recursive data, automatic prototype
removal, nested `_1_1` cloning from generated instances as a general public
contract, table-row repetition, local bookmark/table/frame APIs, resource
duplication policy, Style/Asset Contexts, foreach redesign, or frame-layout
changes. Cloning a generated suffixed target remains outside the supported
prototype workflow.

## Z. Future declarative instantiation

Future work may provide a convenience operation that accepts nested activity
arrays, but it should delegate to the explicit local target semantics proven
here. It must preserve ownership boundaries and local transaction behavior.

## AA. Recommendation

Use the explicit local section target as the foundation for the next bounded
slice. Any declarative recursive API should be designed only after its data
shape, prototype visibility and nested resource semantics are specified. Do
not weaken strict global resolution or introduce a general Context framework
without new evidence.

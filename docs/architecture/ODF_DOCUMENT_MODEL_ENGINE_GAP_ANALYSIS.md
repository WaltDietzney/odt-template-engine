# ODF Document Model / Engine Gap Analysis

## Status

This document compares the semantic model in
`ODF_DOCUMENT_MATERIALIZATION_MODEL.md` with the current engine architecture.
It is an architecture analysis, not a Change Contract and not an implementation
plan.

The comparison uses the current D5 work as the implementation baseline where
D5C–D5E have already introduced semantic ownership traversal, transitive style
requirement discovery, and transitive resource discovery. Existing legacy and
compatibility paths remain part of the analysis because backward compatibility
is an explicit project constraint.

The goal is to identify what is already aligned, what is only partially aligned,
and what must be characterized or redesigned before D5F continues.

## 1. Executive assessment

The current architecture is closer to the ODF semantic model than the visual
regressions initially suggested. The D5 ownership work is directionally sound:
`ownedElements()` provides one semantic composition tree, requirement collectors
project dependencies from that tree, and package resource preparation is already
separate from element DOM construction.

The main gap is not traversal. It is **semantic richness and placement awareness
inside the dependency model**.

The current style path mostly models a requirement as:

```text
family + name + flat definition array
```

The ODF study shows that a complete style dependency may additionally need to
preserve:

```text
style kind/scope
family
parent dependency
one or more typed property groups
owning document part
references to font declarations or other style resources
```

Therefore D5F must not simply make the existing flattened requirement pipeline
run earlier or more uniformly. Doing so would stabilize an incomplete semantic
model.

At the same time, the evidence does not justify a rewrite. Several current
boundaries are already correct and should be preserved.

## 2. Gap classification

This analysis uses six classifications:

- **ALIGNED** — responsibility matches the semantic model closely enough.
- **PARTIAL** — correct boundary exists but representation is incomplete.
- **SEMANTIC FLATTENING** — distinct ODF concepts are collapsed into one map or
  operation.
- **OWNERSHIP LEAK** — a component performs work that belongs to another
  document/package scope.
- **COMPATIBILITY PATH** — behavior is intentionally retained but should not
  define the target architecture.
- **CHARACTERIZE FIRST** — behavior must be protected/understood before change.

## 3. `OdtElement` and `toDomNode()`

### Current responsibility

`OdtElement` remains the structured-content abstraction. It exposes
`toDomNode()` for native content materialization, `ownedElements()` for semantic
composition, and several requirement methods for styles and image assets.
Concrete composites can override `ownedElements()` without changing their
rendering-oriented storage.

### Assessment: PARTIAL, directionally ALIGNED

The semantic ownership boundary is good. `ownedElements()` is exactly the kind
of single ownership view needed by the document model. The fact that
`toDomNode()` remains element-local is also compatible with the ODF evidence:
native content semantics should remain near the element.

The gap is that `OdtElement` currently exposes dependency requirements through
several representation-specific methods:

- text styles
- paragraph styles
- frame styles
- image styles
- fill images
- image assets

These methods already separate some channels, but they encode historical engine
categories rather than a general ODF dependency vocabulary. In particular,
there is no explicit concept of common vs automatic style, owning document part,
parent dependency, or typed property groups.

There is also residual compatibility traversal in methods such as
`getFrameStyleRequirements()`, `getImageAssets()`, and `getStyleDefinitions()`
that walks `$embeddedElements` independently of `ownedElements()`.

### Consequence

Do not remove `toDomNode()` as part of D5F. Do not add concrete-type traversal to
`OdtTemplate`. Instead, treat current requirement methods as an incomplete
projection interface whose semantics need to be mapped before expansion.

## 4. `StyleRequirementCollector`

### Current responsibility

The collector traverses `ownedElements()` and yields each requirement
individually as:

```text
family
name
definition
```

Duplicate names are intentionally preserved until `StyleContext` can detect a
conflict.

### Assessment: ALIGNED traversal, SEMANTIC FLATTENING payload

This is one of the strongest results of D5D. The collector correctly separates
semantic ownership traversal from the elements' own requirements and preserves
individual occurrences. Nothing in the ODF research invalidates this traversal
architecture.

The payload, however, is too weak for the newly established model. `family`
currently mixes several concepts:

- `paragraph` and `text` are ODF style families;
- `frame` and `image` are engine roles that both materialize as graphic styles;
- `fill-image` is a different style resource/declaration concept.

The collector also cannot express:

- common vs automatic style;
- content.xml vs styles.xml ownership;
- parent-style dependency;
- paragraph-properties vs text-properties inside one paragraph-family style;
- dependent font-face requirements.

### Consequence

Preserve the collector/traversal concept. Before D5F, characterize all existing
requirement payload shapes and determine which semantic distinctions are already
implicit in their arrays and which are genuinely absent.

## 5. `StyleContext`

### Current responsibility

`StyleContext` owns pending style requirements for one logical document. It has
separate registries for paragraph, text, frame, image, and fill-image
requirements. Equivalent re-registration is idempotent; same-name different
pending definitions conflict.

### Assessment: ALIGNED lifecycle, SEMANTIC FLATTENING representation

Document-local ownership and reset behavior are correct and should be preserved.
This was the central achievement of STYLE-CONTEXT-01.

The registries are nevertheless keyed primarily by engine family + style name
and store flat arrays. They cannot model STYLE-05 faithfully as a semantic
requirement unless parent information happens to be embedded ad hoc in the
array. They also cannot distinguish a common paragraph style from an automatic
paragraph style with the same family, nor can they identify the owning XML part.

The separate `frameStyles` and `imageStyles` registries are particularly
revealing: both are graphic-family styles in ODF, but the engine distinguishes
them by producer/use role. That distinction can be useful, but it must not be
mistaken for ODF style family.

### Consequence

Do not replace `StyleContext` merely because the current representation is
incomplete. Its document-local lifecycle is correct. The next contract should
decide whether the context is extended, delegates to richer requirement values,
or remains a compatibility facade over a richer internal model.

## 6. `StyleMapper`

### Current responsibility

`StyleMapper` translates high-level engine style options into ODF-like attribute
maps and retains several legacy static registries used by compatibility paths.

### Assessment: SEMANTIC FLATTENING + COMPATIBILITY PATH

The ODF research explains the Sample 08/19 class of problems observed before the
study: a mapper that treats all incoming style arrays as high-level semantic
options cannot safely remap arrays that already contain native ODF attributes.
The representation boundary between "engine style options" and "native ODF
properties" is not explicit enough.

Static registries are already being migrated away from normal document-owned
paths and should continue to be treated as compatibility state, not target
architecture.

### Consequence

Characterize the input dialects accepted by every public/protected mapper path:

1. high-level friendly options;
2. already mapped/native ODF attributes;
3. mixed legacy definitions.

Do not change mapping behavior during D5F until those dialects are protected by
characterization tests.

## 7. `StyleWriter`

### Current responsibility

`StyleWriter` writes text, paragraph, graphic, table-cell, table, and font
structures, primarily into `styles.xml`. It also contains legacy static
bookkeeping and a helper that writes table-column automatic styles to whichever
DOM is passed.

### Assessment: OWNERSHIP LEAK + SEMANTIC FLATTENING + COMPATIBILITY PATH

`StyleWriter` knows too many unrelated materialization policies at once. More
importantly, much of its normal style writing assumes `office:styles` in
`styles.xml`, which corresponds to common-style materialization. The ODF model
now proves that this is not sufficient for automatic styles.

It also derives font-face declarations by scanning style attributes in the
styles DOM. This is a pragmatic compatibility mechanism, but it is not a full
font dependency model and cannot by itself account for content-owned automatic
styles.

`writeColumnStyles()` already demonstrates the opposite placement: it writes
`office:automatic-styles` into the supplied DOM. This is evidence that the
engine has fragments of the correct semantics, but no single explicit placement
model.

### Consequence

Do not grow `StyleWriter` into the universal materializer. The gap analysis
supports separating semantic requirement description from physical placement.
Existing writer methods need characterization before any extraction because
public/protected compatibility may depend on them.

## 8. `StructuredResourceCollector`

### Current responsibility

Traverses `ownedElements()` and yields physical image assets produced directly
by each element. `OdtPackage` then prepares the files.

### Assessment: ALIGNED

This is the cleanest current implementation of the new model:

```text
element owns semantic reference/requirement
collector discovers transitive requirement
package owns physical resource
```

The collector is intentionally ignorant of package mutation. That is exactly the
required separation.

The remaining limitation is scope: it knows only image assets. That is not a
D5E defect; it is simply the currently characterized resource family.

### Consequence

Preserve this pattern. Future physical resource types should follow it only when
real requirements appear; do not generalize speculatively.

## 9. `OdtDocumentContext`

### Current responsibility

Owns mutable `content.xml`, `styles.xml`, and `meta.xml` DOMs plus the
per-document `StyleContext`. Replacing core documents resets pending style
requirements.

### Assessment: ALIGNED boundary, PARTIAL document model

The lifecycle and ownership boundary are correct. It is the natural home for
state whose lifetime is one logical document rather than one process.

The context currently exposes only three XML parts and one style context. The
semantic study identifies additional document-part concerns such as manifest,
settings, page/master dependencies, and possibly part-specific font/style
materialization. This does not mean all of them belong as mutable fields in
`OdtDocumentContext`; some correctly belong to `OdtPackage`.

### Consequence

Keep the context small. Do not turn it into a God context merely to mirror the
semantic model diagram. Add document-local collaborators only when the gap
analysis/Change Contract demonstrates a lifecycle need.

## 10. `OdtPackage`

### Current responsibility

Owns extraction/workspace lifecycle, package files, manifest synchronization,
core XML persistence, ZIP rebuilding, and cleanup. It copies image resources
atomically and ensures `mimetype` is first and uncompressed on save.

### Assessment: strongly ALIGNED, with a narrow manifest gap

The package boundary matches the empirical IMAGE-01 model very well. Physical
resources are prepared here, and manifest synchronization happens as package
finalization. The ZIP `mimetype` handling also reflects package-level ODF
semantics.

The current manifest synchronization is image/Pictures-specific and derives
entries by scanning the package directory at save time. That is sufficient for
the currently characterized image path but is not yet a general manifest
dependency model.

### Consequence

Do not move resource copying or manifest finalization back into elements or
`OdtTemplate`. If future resource types appear, evolve package finalization from
real use cases rather than introducing a generic package graph now.

## 11. Page layout / master page handling

### Current responsibility

`PageLayoutOdtTemplate` delegates to `PageLayoutManager`.
`PageLayoutManager` resolves a `style:master-page`, follows
`style:page-layout-name`, finds the corresponding `style:page-layout` in
`styles.xml`, and mutates `style:page-layout-properties`.

### Assessment: ALIGNED for mutation of existing structures

This code already follows the exact relationship confirmed by PAGE-01/02. It
understands master page and page layout as separate structures connected by a
reference rather than as a flat page-style map.

The manager is currently a mutation API for existing structures; it does not
claim to discover/materialize arbitrary new page/master dependencies. That is a
reasonable bounded responsibility.

### Consequence

Use this as a positive architecture example. D5F should not pull page layout into
`StyleContext` merely because page layout is technically an automatic style.
Its semantic lifecycle and API are already distinct.

## 12. Manifest / resource handling

### Current responsibility

Content elements generate references, `StructuredResourceCollector` discovers
image assets, `OdtPackage` copies them, and save-time package synchronization
adds missing `Pictures/` manifest entries.

### Assessment: ALIGNED pipeline, PARTIAL dependency identity

The channel separation is correct. What is not yet explicit is a stable semantic
resource identity independent of source basename/package path. Current atomic
copying uses `Pictures/` + basename and treats conflicting bytes at the same
path as an error.

That behavior is already characterized by D5E and should not be changed inside
D5F. Whether future resources need generated package names or richer identity is
a later resource-model decision.

## 13. `OdtTemplate::setElement()` orchestration

### Current responsibility

The facade currently:

1. collects style requirements;
2. eagerly ensures paragraph/text styles exist;
3. invokes legacy `HasStyles` registration;
4. collects/prepares physical resources;
5. invokes `StructuredElementMaterializer`;
6. collects requirements again and registers all families in `StyleContext`.

### Assessment: PARTIAL + OWNERSHIP LEAK

The method is already an orchestration boundary rather than a concrete-element
renderer, which is good. It delegates traversal and materialization rather than
switching on element types.

However, style handling is duplicated before and after materialization. The
first pass eagerly writes paragraph/text definitions while the second pass
registers the full requirement set. The legacy `HasStyles` path also remains in
the middle. This makes lifecycle ordering difficult to reason about and is a
likely target of D5F — but only after style semantics are made explicit enough.

The resource preparation order is much cleaner: discover -> prepare package ->
materialize content. D5E's atomic rollback behavior should remain intact.

### Consequence

D5F should primarily clarify lifecycle/orchestration, not invent a new rendering
architecture. Before changing the two style passes, add characterization tests
for ordering-sensitive behavior and failure rollback.

## 14. Legacy `setValuesInDom()` structured path

### Current responsibility

Structured values on the legacy assign/render path call `toDomNode()` directly,
then register legacy graphic requirements and replace the placeholder. A flag
later enables compatibility finalization.

### Assessment: COMPATIBILITY PATH + OWNERSHIP LEAK

This path intentionally bypasses the normal document-owned requirement/resource
pipeline. It is therefore not a model for D5F, but it is compatibility behavior
that cannot be silently removed.

### Consequence

Keep it explicitly marked as compatibility. Characterize protected-method
polymorphism and direct legacy structured rendering before routing it through
new lifecycle machinery.

## 15. Consolidated gap matrix

| Area | Classification | Keep | Gap before D5F |
| --- | --- | --- | --- |
| `ownedElements()` | ALIGNED | One semantic ownership tree | None in traversal concept |
| `toDomNode()` | PARTIAL/ALIGNED | Element-local native ODF semantics | Dependency side effects must stay out |
| `StyleRequirementCollector` | ALIGNED + FLATTENING | Transitive projection, occurrence preservation | Requirement payload lacks kind/part/property-domain semantics |
| `StyleContext` | ALIGNED + FLATTENING | Document-local lifecycle, conflict detection | Flat family/name/array model |
| `StyleMapper` | FLATTENING + COMPATIBILITY | Friendly option mapping where intentional | Native-vs-friendly input boundary unclear |
| `StyleWriter` | OWNERSHIP LEAK + COMPATIBILITY | Existing compatibility behavior | Common/automatic placement and font finalization mixed |
| `StructuredResourceCollector` | ALIGNED | Discovery separate from mutation | Only image family currently characterized |
| `OdtDocumentContext` | ALIGNED/PARTIAL | Small document-local state boundary | Do not over-expand; missing semantics belong only if lifecycle proves it |
| `OdtPackage` | ALIGNED/PARTIAL | Package files, ZIP, manifest, resource ownership | Manifest logic currently image-specific |
| `PageLayoutManager` | ALIGNED | Explicit master -> page-layout relationship | Creation/generalization not required for D5F |
| `setElement()` | PARTIAL + OWNERSHIP LEAK | Facade orchestration, no concrete-type switching | Duplicate style passes and mixed compatibility path |
| legacy structured render | COMPATIBILITY | Public/protected behavior | Must not define target lifecycle |

## 16. What D5F must not do

The gap analysis rules out several tempting shortcuts:

1. **Do not centralize native element rendering in `OdtTemplate`.** The problem is
   dependency semantics, not insufficient type switching.
2. **Do not simply make every style a `styles.xml` common style.** Automatic
   styles have document-part-specific ownership.
3. **Do not equate paragraph/text/graphic engine buckets with the complete ODF
   style model.** Kind, family, property domain, and producer role are different
   axes.
4. **Do not push package resources into `StyleContext`.** D5E already has the
   correct package ownership boundary.
5. **Do not replace document-local state with process-global registries.** The
   STYLE-CONTEXT work remains valid.
6. **Do not fix legacy rendering anomalies opportunistically.** Sample 08/19/21
   findings require their own characterization/semantic work.
7. **Do not create a universal context object containing every ODF channel.**
   The semantic model is a responsibility map, not a class diagram.

## 17. Required characterization before a D5F contract

A bounded pre-contract characterization should answer only the questions that
can change D5F design:

1. What exact definition shapes are currently emitted by paragraph, text,
   frame, image, fill-image, table and table-cell producers?
2. Which definitions are friendly StyleMapper options, which are already native
   ODF attributes, and which are mixed?
3. Which current normal-path definitions are intended to become common styles
   in `styles.xml`, and which represent object-local/direct formatting that
   should semantically be automatic styles?
4. Does any current structured element rely on style materialization occurring
   before `toDomNode()` rather than merely before final save?
5. Which protected/public compatibility methods depend on `StyleWriter` static
   registries or on legacy finalization ordering?
6. Which existing tests explicitly lock current style location (`styles.xml`
   versus `content.xml`) and which only assert visual/semantic behavior?
7. Are there current elements that reference a style name without exposing a
   corresponding requirement, as observed in the Sample 21 investigation?

These are characterization questions, not invitations to fix the behavior.

## 18. Architecture conclusion

The ODF study does **not** invalidate D5C–D5E. It validates their central idea:
one semantic ownership tree can feed multiple dependency/materialization
channels.

The study does, however, show that the style channel is not yet semantically
rich enough for D5F to finalize confidently.

The next architecture decision should therefore be narrower than a redesign of
structured materialization:

> Define the semantic contract of a style requirement and its placement before
> integrating the final D5 materialization lifecycle.

That contract must preserve compatibility and should be introduced in the
smallest slice that lets the normal `setElement()` path distinguish at least:

- style kind/scope;
- ODF family;
- typed property domains;
- parent dependency where present;
- owning document part/materialization target.

Whether this becomes a richer value object, an extended requirement structure,
or another bounded internal representation remains undecided until the required
characterization is complete.

D5F should remain paused until that contract exists.

# TEMPLATE-AUTHORING-01D — Declarative Structural Controls Change Contract

**Status:** COMPLETE / FINAL GO
**Milestone:** TEMPLATE-AUTHORING-01D
**Baseline:** `TEMPLATE_AUTHORING_01D_DECLARATIVE_CONTROLS_BASELINE.md`
**Implementation base:** `develop`

## 1. Purpose and scope

TEMPLATE-AUTHORING-01D adds bounded execution of native Writer Section
declarations recognized by the Phase-B TemplateContract. It turns the already
inspectable declarations `#foreach`, `#if`, and `#ifnot` into executable
structural template controls by orchestrating established SECTION-03 mechanics
and shared condition semantics.

Declarative execution is an additional structural capability of the library,
not a replacement for imperative document construction or mutation. Existing
imperative APIs remain first-class and freely composable with declarative
execution.

Required execution regions are:

- BODY content in `content.xml`;
- supported master-page header content in `styles.xml`;
- supported master-page footer content in `styles.xml`.

Phase D does not introduce a second renderer, inspection/scope model, mandatory
document-rendering pipeline, optional Phase-E mapping/automation layer, new
classic-control semantics, new condition grammar, new Writer-field families,
implicit aliases between representations, general named-object automation,
new Section clone/identity semantics, page-style authoring, FINALIZATION-01,
or arbitrary Section mutation outside TemplateContract-supported regions.

## 2. Semantic authorities and responsibility boundaries

The following authorities are binding:

| Responsibility | Authority |
| --- | --- |
| discovered declarative controls | Phase-B `TemplateContract` |
| control kind, dependency, scope and ownership | `TemplateContract` |
| source part, region and carrier provenance | Phase-B provenance |
| current physical document state | current Working DOM |
| Section cloning, instantiation and identity rewriting | SECTION-03 services |
| condition evaluation | existing shared `ConditionExpression` path |
| classic scalar/filter binding | existing `TemplateProcessor` semantics |
| native User Fields | accepted Phase-C semantics |

Phase D consumes TemplateContract descriptors as source-semantic execution
input, but performs mutation against the current Working DOM. Contract/native
object identifiers are source-semantic identities, not mutable DOM handles.

The executor must not derive a competing semantic control model from the
Working DOM or duplicate SECTION-03 mutation mechanics.

## 3. Declarative execution model

Execution is parent-first / outside-in.

For each root control, direct child controls are determined by the ownership and
scope information established by Phase B. A child executes only after its
parent has survived or produced the concrete Working-DOM instance in which that
child is owned.

For foreach execution:

1. use established SECTION-03 mechanics to create concrete Section instances;
2. establish the collection item as the effective item scope;
3. process supported classic scalar/filter expressions in that scope using
   existing binding semantics;
4. execute directly owned native child controls recursively inside that
   concrete instance.

Each parent instance owns its own recursive child execution. Nested prototypes
must not be resolved across sibling parent instances.

For conditions, existing condition semantics are authoritative:

- true `#if` / false `#ifnot`: preserve the native Section and continue with
  eligible owned children;
- false `#if` / true `#ifnot`: remove the complete native Section subtree
  and execute no descendants.

Phase D must not introduce a second persistent execution AST, scope tree,
ownership tree, clone mechanism, or identity allocator. A bounded transient
execution plan is permitted if TemplateContract remains the semantic authority.

## 4. Foreach data contract and failures

A `#foreach:key` requires its corresponding collection dependency in the
effective data scope.

| Input | Required behavior |
| --- | --- |
| supported collection with items | instantiate and execute |
| explicit empty collection `[]` | valid; remove prototype and produce zero instances |
| missing dependency | fail |
| `null` | fail |
| scalar or unsupported non-collection value | fail |

Each collection item is a named record scope and must therefore be represented
by an array with string keys. An empty item record is valid when execution does
not require an item dependency. Positional/list arrays, scalar items, `null`,
and objects are invalid. The engine does not introduce implicit item keys such
as `value`, `this`, `item`, or `@value`.

The same rules apply recursively to nested item scopes. The core must not
normalize missing, null, scalar, or object values into collections. Such
application-specific normalization belongs to an optional mapping layer.

A collection failure must provide enough context to identify the control or
dependency, effective scope/path, and failure reason. The contract does not
mandate a new exception hierarchy where existing exception infrastructure is
sufficient.

Condition missing-value behavior remains governed by existing
`ConditionExpression` semantics.

## 5. Conditional materialization

A successful condition preserves its native `text:section` carrier. Phase D
must not unwrap a true condition merely to consume template syntax.

Preservation includes, where present:

- native Section name;
- Section style;
- Section properties and ODF semantics;
- contained native objects;
- content not owned by Phase-D processing;
- eligible native child controls.

A false condition removes the complete Section subtree.

Phase D introduces no marker-renaming/finalization scheme. A surviving
condition Section retains its authored declaration name. Existing foreach clone
identity rewriting remains authoritative.

Successful structural execution is mutating. Phase D provides no general
idempotence or repeated-execution guarantee for already consumed/materialized
controls. Broader marker/export finalization remains outside Phase D.

## 6. Atomicity and lifecycle

Atomicity is bounded to one invocation that recursively processes the selected
declarative structural execution unit.

On failure, all mutations performed by that invocation must be rolled back to
the state immediately before that invocation began. Unrelated document
mutations performed before the invocation must remain intact.

The contract specifies the observable guarantee, not the rollback
implementation mechanism.

Existing local SECTION-03 rollback guarantees remain intact beneath this
execution-unit guarantee.

On success, `OdtTemplate` does not enter a new global rendered/materialized
state. Callers remain free to perform User Field, bookmark, image, scalar,
structured, or other imperative operations before saving.

Phase D defines no mandatory global processing order, document-wide render
transaction, automatic reload/finalization, or required save step.

## 7. Cross-part target resolution

The TemplateContract remains the source-semantic authority:

```text
ControlDescriptor
    -> carrierNativeObjectId
        -> NativeObjectDescriptor
            -> sourcePart
            -> regionKind
            -> regionOwner
            -> carrierKind
            -> ownerIds / authored identity
```

Phase D must provide a bounded internal bridge from this provenance to the
correct current Working-DOM Section target.

Required supported regions are BODY in `content.xml` and TemplateContract-
supported master-page header/footer regions in `styles.xml`.

Target resolution must be constrained by source part and region/carrier
provenance. Identical Section names in BODY, different master pages, headers,
or footers must not be resolved through document-global name matching.

The contract deliberately does not prescribe a new public target class or
method signature.

Existing SECTION-03 public and protected entry points must not silently broaden
from their established `content.xml` behavior to cross-part lookup. Part-aware
resolution is initially infrastructure for Phase-D execution. A future public
part-aware Section API requires its own decision.

Existing SECTION-03 identity-rewriting policy remains authoritative. If
cross-part work exposes uncertainty about whether a technical ODF identity is
document-, part-, or region-global, characterize the existing/ODF behavior
before changing policy.

## 8. Classic/native coexistence

Classic and native template representations may coexist.

Supported Phase-D composition includes native structural controls containing
classic scalar/filter expressions; those expressions bind in the effective
native data scope using existing classic semantics. Classic scalar/filter
expressions elsewhere remain independently usable.

Phase D defines no new orchestration guarantee for structural nesting across
representations (classic structural controls owning native structural controls,
or native structural controls owning classic structural controls). Existing
classic structural behavior is not redesigned as collateral work.

Native User Fields retain Phase-C ROOT/document-level binding semantics.
Physical placement in a repeated Section does not convert them into item-scoped
bindings.

Identically named dependencies in different representations are not implicitly
aliased by the core engine. An optional mapping layer may deliberately map one
application value to multiple template dependencies.

## 9. Diagnostics and failure semantics

Declarative execution must fail explicitly for at least:

- missing or invalid collection data;
- an expected carrier that cannot be resolved in the current Working DOM;
- ambiguous Working-DOM target resolution;
- an unsupported source region presented for execution;
- structural clone/instantiation failure;
- recursive child-control execution failure.

Diagnostics should preserve, where available, control kind, dependency or
expression, source part, region, carrier identity, effective data scope, and
reason.

A TemplateContract-recognized executable control must not be silently skipped
when its expected Working-DOM carrier cannot be resolved.

Any runtime failure inside an execution unit activates the atomicity guarantee
in section 6.

## 10. Compatibility contract

Phase D is additive. It must preserve:

- existing public Section APIs and target semantics;
- `section()`, `instantiate()`, and `instantiateMany()` behavior;
- existing `load()`, `save()`, and render lifecycle behavior;
- classic template processing;
- Phase-C User Field semantics;
- SECTION-03 clone, prototype, binding, rollback and identity semantics;
- existing sample behavior unless a sample is explicitly in scope.

Part-aware Phase-D infrastructure must not silently broaden existing SECTION-03
entry points beyond their established domain.

Protected methods that form externally overridable compatibility surfaces must
be preserved through facade wrappers where polymorphism requires it.

Unexpected legacy behavior discovered during implementation must first be
characterized and documented rather than opportunistically fixed unless the
accepted Phase-D semantics require a change.

## 11. Implementation slices

Phase D has exactly four planned implementation slices.

### D1 — Part-/region-aware Section resolution

Introduce the bounded internal bridge from TemplateContract provenance to
Working-DOM Section targets.

Before refactoring, add characterization coverage for relevant existing
SECTION-03 `content.xml` behavior.

D1 must demonstrate unambiguous internal resolution for BODY and supported
header/footer regions without changing existing public/protected Section
semantics. It does not implement the complete declarative executor.

### D2 — Conditional declarative execution

Implement `#if` and `#ifnot` execution using TemplateContract authority,
existing condition evaluation, parent-first ownership, Section-preserving true
semantics, subtree-removing false semantics, BODY/header/footer execution,
nested native conditions, and explicit diagnostics.

D2 must not introduce collection orchestration merely to accelerate D3.

### D3 — Foreach and recursive scope orchestration

Implement `#foreach` over existing SECTION-03 mechanics, including:

- strict collection validation;
- explicit empty collections;
- item scopes;
- classic scalar/filter binding;
- nested foreach;
- foreach -> condition and condition -> foreach;
- recursive parent-first execution;
- supported BODY/header/footer regions.

No new clone or identity mechanism may be introduced.

### D4 — Execution-unit atomicity and integration closure

Add the accepted execution-unit rollback guarantee and close Phase-D
integration, including:

- preservation of unrelated prior document mutations;
- stale/unresolvable carrier failures;
- classic/native coexistence regression;
- cross-part combinations;
- lifecycle and compatibility regression;
- complete Phase-D integration coverage.

D4 closes Phase D. Mapping-driven automation remains Phase E and must not be
pulled into this milestone.

## 12. Verification and completion criteria

Each slice requires:

- focused unit/integration tests;
- relevant characterization/regression tests;
- PHP lint for changed PHP files;
- `git diff --check`.

Before Phase-D completion also run:

- full `composer test`;
- relevant SECTION-03 regression;
- Template Inspection regression;
- Phase-C/User Field regression;
- classic TemplateProcessor regression;
- `PublicSampleSmokeTest`;
- `composer validate` if Composer metadata changes.

Rendering-sensitive work requires manual LibreOffice regression using real
LibreOffice-authored fixtures. Coverage must include at least:

- BODY `#if` / `#ifnot`;
- BODY `#foreach`;
- header/footer `#if` / `#ifnot`;
- header/footer `#foreach`;
- at least one nested native combination such as `#foreach -> #if`.

Saved output must be reopened and checked for correct header/footer page-style
ownership, preservation of Section properties/styles on true conditions,
absence of visible artifacts on false conditions, correct foreach repetitions,
layout stability, and absence of repair/corruption warnings.

Local `samples/output/*.odt` regression artifacts and LibreOffice lock files
must not be committed, restored, deleted, or regenerated unless explicitly in
scope.

Before merge, review the implementation diff against this accepted contract.
The final review must confirm:

- no unapproved public API;
- no BODY-only shortcut;
- no Phase-E mapping automation;
- no collateral classic-control redesign;
- no unapproved identity policy;
- required compatibility facades remain;
- no unexplained source-part/scope expansion.

### Completion definition

TEMPLATE-AUTHORING-01D is complete when the three Phase-B-recognized native
Section declarations `#foreach`, `#if`, and `#ifnot` execute with the
accepted D0 semantics in supported BODY and master-page header/footer regions,
including nested native execution, scoped classic scalar/filter binding, strict
collection validation, bounded execution-unit rollback, compatibility
preservation, automated regression coverage, and successful LibreOffice visual
regression.

Optional mapped-data automation and broader finalization remain explicitly
outside Phase D.

## 13. Completion record

TEMPLATE-AUTHORING-01D reached **FINAL GO** after D1–D4 implementation, full automated regression, and manual LibreOffice end-to-end validation with a real Writer-authored template.

Completion evidence includes:

- bounded part-/region-aware Section resolution for BODY, master-page header, and master-page footer regions;
- native `#if`, `#ifnot`, and `#foreach` execution with nested ownership and item scopes;
- classic scalar/filter binding inside foreach item scopes;
- strict foreach collection/item validation and empty-collection semantics;
- execution-unit rollback across `content.xml` and `styles.xml`;
- preservation of existing public SECTION-03 semantics and identity policy;
- final full automated suite: **845 tests, 5,754 assertions**, with only the 8 known pre-existing PHPUnit metadata deprecations;
- `PublicSampleSmokeTest`: **1 test, 199 assertions**;
- successful Writer open, save, close, and reopen for both materialization and removal/empty-collection cases, without repair/corruption warnings or observed layout instability.

The final manual regression found one D3 defect before closure: declarative WorkingTarget foreach cloning reversed physical document order although SECTION-03 defines input order as document order. The public SECTION-03 `instantiateMany()` path was already correct. The declarative path was corrected with a bounded per-foreach insertion anchor, preserving clone identity allocation, prototype lifecycle, nested ownership, rollback, and public compatibility. The regression was rerun successfully with BODY order `Anna -> Peter` and footer order `Footer A -> Footer B`, and remained stable after LibreOffice save/reopen.

Phase E optional mapping/automation and FINALIZATION-01 remain outside this completed contract.

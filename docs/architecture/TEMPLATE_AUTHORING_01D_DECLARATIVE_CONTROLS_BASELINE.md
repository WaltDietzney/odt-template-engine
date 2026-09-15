# TEMPLATE-AUTHORING-01D — Declarative Structural Controls Baseline

## Status

**Architecture handoff baseline / D0 research complete**

This document records the evidence already established before implementation of
TEMPLATE-AUTHORING-01D. Its purpose is to prevent future work, especially after
a chat or agent handoff, from reopening questions that are already answered by
the current repository.

Phase D has **not** approved a public execution API or a Change Contract yet.
D0 research is complete. The six D0 decisions below are the accepted research
baseline. The resulting Phase-D Change Contract is now **ACCEPTED** in
[`TEMPLATE_AUTHORING_01D_CHANGE_CONTRACT.md`](TEMPLATE_AUTHORING_01D_CHANGE_CONTRACT.md).

## Handoff rule

Before researching or designing TEMPLATE-AUTHORING-01D, read this document and
verify only facts that may have changed in the repository.

> **CLOSED means do not research or redesign again without contradictory
> repository or ODF evidence.**

The technical source of truth remains the current `develop` branch, especially
`src/`, `tests/`, the TEMPLATE-AUTHORING architecture documents, and the
LibreOffice reference fixtures.

## Architectural intent

Phase D makes native Writer Sections a declarative frontend over the existing
Section architecture. It must not introduce a second structural renderer.

The principal authoring direction is already established:

```text
#foreach:experience
#if:current
#ifnot:archived
```

The intended relationship is:

```text
declarative native Section controls
            ↓
bounded orchestration
            ↓
existing Section mechanics
```

Existing imperative Section APIs remain first-class and compatible.

## Core engine versus optional mapping automation

Template inspection and the growing set of addressable native objects enable a
useful higher-level workflow, but that workflow is **not an inherent lifecycle
responsibility of `OdtTemplate`**.

The core engine should expose composable capabilities and preserve programmer
control, for example:

```text
OdtTemplate
├── inspectTemplate()
├── scalar/classic binding
├── native User Field binding
├── section()/instantiate()/instantiateMany()
├── bookmark and other typed structured operations
├── declarative native Section-control execution
├── load()
└── save()
```

Applications remain free to compose those capabilities imperatively and in the
order appropriate to their own document workflow.

A separate optional mapping/automation layer may later use
`inspectTemplate()` to discover the template contract, map application or form
data to that contract, and orchestrate several engine capabilities. A typical
consumer could be a WordPress plugin, CV generator, form-driven document
service, or another application that benefits from pre-inspecting a template.

Conceptually:

```text
Application / WordPress plugin / CV generator
                    │
                    ▼
       optional mapping/automation layer
                    │
                    ▼
             ODT Template Engine
                    │
                    ▼
                   ODT
```

Such an optional layer may be shipped by this project as a convenience class or
service. That does **not** make mapping-driven automatic rendering mandatory for
normal library use, and it does not authorize a monolithic core render
lifecycle.

Consequences for TEMPLATE-AUTHORING-01:

- Phase D defines the bounded semantics required to execute native declarative
  structural controls; it does not prescribe processing of every template
  feature in one automatic document pass.
- Atomicity/lifecycle decisions in D0-3 must be scoped to one invocation that
  recursively processes the selected declarative structural-control tree or
  execution unit, not silently generalized to all document mutations.
- After declarative structural processing, callers remain free to perform
  bookmarks, images, User Fields, scalar binding, structured insertion, or
  other imperative operations before `save()`.
- Phase E may design an **optional** mapping-driven orchestration/convenience
  layer. It must preserve the lower-level APIs as first-class usage and must
  not turn a conceptual `render($mappedData)` example into a mandatory
  `OdtTemplate` lifecycle or public method signature without a separate
  accepted architecture decision.
- Inspection enables automation; inspection does not imply automation.

## Established SECTION-03 substrate

The current implementation already provides the structural mechanics required
by declarative repetition:

```text
OdtTemplate::section()
        ↓
SectionTarget
        ↓
SectionInstantiationService
        ↓
SectionCollectionInstantiationService
        ↓
SectionCloneService
        ↓
SectionRemovalService
```

Relevant implementation and integration evidence includes:

- `src/Document/SectionTarget.php`
- `src/Document/SectionInstantiationService.php`
- `src/Document/SectionCollectionInstantiationService.php`
- `src/Document/SectionCloneService.php`
- `src/Document/SectionRemovalService.php`
- `tests/Integration/SectionInstantiationTest.php`
- `tests/Integration/SectionCollectionInstantiationTest.php`
- `tests/Integration/SectionCloneTest.php`

### Established collection semantics

The repository already characterizes and tests:

- empty collections remove their local prototype and return no instances;
- `instantiateMany()` preserves input order;
- `instantiate()` remains prototype-preserving;
- `instantiateMany()` finalizes/removes its prototype after successful
  collection creation;
- an invalid item in the middle of a collection rolls back the whole
  collection operation;
- after such a failure the prototype remains usable;
- nested collections operate on the prototype local to their concrete owner
  instance;
- nested empty collections remove only that local prototype;
- independently sized nested collections are isolated from each other;
- finalized collections survive save/reopen;
- missing required clone-local scalar values fail atomically;
- extra values are ignored and invalid binding values are rejected;
- supported scalar filters use the existing TemplateProcessor semantics;
- prototype targets follow the current document context after `load()`;
- separate template instances remain isolated.

The `3/1/4/0/2/5` nested-collection integration case is deliberate stress
evidence for local prototype ownership and must not be replaced by assumptions.

### Nested foreach mechanics are CLOSED

A nested declarative foreach does **not** require a new nesting model.

The imperative substrate already supports the equivalent operation:

```php
$experiences = $template
    ->section('ExperienceEntry')
    ->instantiateMany($experienceItems);

foreach ($experiences as $experience) {
    $experience
        ->section('ActivityEntry')
        ->instantiateMany($activityItems);
}
```

`SectionTarget::section()` resolves the logical unsuffixed child name relative
to the concrete parent instance. Phase D therefore needs orchestration, not a
new nested-Section addressing mechanism.

## Established inspection and scope model

TEMPLATE-AUTHORING-01B already recognizes native Section declarations such as:

```text
#foreach:experience
#if:current
#foreach:projects
#ifnot:archived
```

and preserves their native carrier identity and ownership.

The unified template contract already represents:

- control kind;
- representation;
- support state;
- marker evidence;
- carrier native object identity;
- native owner chains;
- dependencies;
- root and collection-item data scopes;
- parent/created scope relationships;
- malformed declaration diagnostics.

Nested dependency paths are already characterized, including:

```text
experience[]
experience[].company
experience[].current
experience[].projects[]
experience[].projects[].project_name
experience[].archived
```

Phase D must reuse this established scope meaning. It must not create a competing
scope analyzer merely for execution.

Relevant evidence includes
`tests/Integration/TemplateAuthoring01BSlice3ClassicControlsDataScopesTest.php`
and
`tests/Integration/TemplateAuthoring01BSlice4NativeOwnershipDeclarativeCandidatesTest.php`.

## Established condition semantics

Condition grammar is not a Phase-D research topic.

`TemplateProcessor::evaluateCondition()` delegates to
`ConditionExpression`. Existing tests characterize simple boolean references
and comparisons such as:

```text
current
gender=="female"
status=="active"
score>=10
```

`ifnot` is the negation of the same evaluator semantics.

Visible classic controls and native declarative controls must share this
condition meaning. Phase D must not introduce an independent condition parser
or evaluator.

## Existing deliberate boundary in Section instantiation

`SectionInstantiationService` currently rejects structural control
expressions in a clone. This is intentional SECTION-03 behavior and is covered
by `SectionInstantiationTest::testConditionsAndForeachRemainExplicitlyUnsupportedAndAtomic()`.

Phase D must not casually weaken this guard.

The preferred architecture direction is a new bounded orchestration layer above
the existing Section services, conceptually:

```text
Declarative structural orchestration
        ├── foreach → existing Section collection mechanics
        ├── if      → bounded conditional Section finalization
        ├── ifnot   → bounded conditional Section finalization
        └── recurse into concrete generated instances
```

The exact service/API name is not approved. This diagram expresses
responsibility placement only.

## D0 research decisions

The six previously open D0 questions are now resolved. These decisions are the
research baseline for the Phase-D Change Contract. They are not yet public API
signatures.

### D0-1 — Automatic control-tree orchestration — GREEN

Native declarative controls execute **parent-first / outside-in**.

The Phase-B TemplateContract remains the semantic authority for control kind,
dependency, data scope, carrier identity, and native ownership. The current
working DOM remains the structural mutation authority.

A declarative executor must therefore orchestrate existing Section mechanics
rather than build a second persistent control tree:

```text
TemplateContract ownership/scope
        ↓
parent control
        ↓
existing Section operation
        ↓
surviving/generated concrete instance
        ↓
direct owned child controls
        ↓
recursive execution in that instance
```

Consequences:

- child controls execute only after their parent survives or produces a
  concrete instance;
- foreach children execute separately for every concrete parent instance;
- a false parent condition prevents all owned descendants from executing;
- existing `instantiateMany()`, relative `SectionTarget::section()`,
  clone/identity rewriting, and local prototype mechanics remain the mutation
  substrate;
- no second scope model, ownership analyzer, persistent render AST, clone
  mechanism, or identity allocator is introduced.

### D0-2 — Conditional Section finalization — GREEN

ODF/LibreOffice evidence shows that a `text:section` carrier can own native
Section semantics such as its name, Section style, condition/display state, and
Section properties. A surviving condition must therefore not be unwrapped merely
to consume template syntax.

The Phase-D structural result is:

```text
#if / #ifnot false
    → remove the complete native Section subtree

#if / #ifnot true
    → preserve the native Section container and its contents
```

For a true condition, Section-owned styles/properties, nested native objects,
and nested controls survive. Those nested controls are then eligible for
parent-first execution under D0-1.

Phase D introduces no special materialized-name scheme. A surviving condition
Section keeps its authored native name. Existing foreach clone identity
rewriting remains unchanged. Any broader export/finalization policy belongs to
FINALIZATION-01 rather than to conditional execution.

### D0-3 — Declarative execution-unit atomicity/lifecycle — GREEN

Atomicity is bounded to **one invocation that recursively processes the selected
declarative structural-control tree/execution unit**.

If that invocation fails, mutations made by that invocation are rolled back to
the state immediately before it began. Unrelated document mutations performed
before the invocation are not rolled back.

On success, the affected controls are structurally materialized, but
`OdtTemplate` does **not** enter a global rendered/materialized state. Callers
remain free to continue with User Fields, bookmarks, images, scalar binding,
structured insertion, or other imperative operations before saving.

Phase D therefore defines:

- no mandatory global processing order;
- no document-wide render transaction;
- no new global lifecycle state;
- no general idempotence/repeated-execution guarantee for an already consumed
  structural control;
- no new marker-finalization naming scheme.

Existing `load()` behavior remains unchanged. Optional mapping-driven
automation is a higher layer and may define its own orchestration contract.

### D0-4 — Missing collection data — GREEN

A native `#foreach:key` requires the corresponding collection dependency in
its effective data scope.

```text
key present with []
    → valid empty collection; zero instances

key present with collection items
    → valid collection

key absent
    → data-contract failure

key present with null/non-collection value
    → invalid collection input; failure
```

The same rule applies recursively in nested collection-item scopes. A nested
missing or invalid collection fails the containing declarative execution unit,
so D0-3 atomicity applies.

The core engine does not silently normalize missing/null/scalar values into
collections. Application-specific defaults or normalization belong to an
optional mapping layer.

Condition missing-value behavior remains governed by the existing shared
`ConditionExpression` semantics and is not redefined here.

### D0-5 — Execution source-part scope — GREEN

The earlier provisional BODY/`content.xml`-only preference is **superseded**.

ODF permits `text:section` in Writer header/footer content, and conditional
header/footer content is a practical authoring use case. Phase-B inspection
already preserves the source provenance required to distinguish such carriers:

```text
ControlDescriptor
    → carrierNativeObjectId
        → NativeObjectDescriptor
            → sourcePart
            → regionKind
            → regionOwner
            → carrierKind
            → ownerIds
```

`OdtDocumentContext`/`OdtPackage` already own mutable `content.xml` and
`styles.xml` DOMs. The current SECTION-03 services are historically
`contentDom()`-bound; that is an implementation limitation, not a semantic
restriction of declarative controls.

Phase D therefore treats declarative native Section controls as source-part
independent **within TemplateContract-supported inspected regions**:

- BODY content in `content.xml`;
- supported master-page header/footer content in `styles.xml`.

There is no control-kind-specific source-part matrix: `#if`, `#ifnot`, and
`#foreach` share the same eligibility rule where the inspected ODF region
supports the Section carrier.

Execution needs a bounded part-/region-aware bridge from source-semantic
identity to the current working DOM. The existing TemplateContract provenance
is sufficient input; Phase D does not need another inspection model. Concrete
working-DOM target API design belongs to the Change Contract/implementation
design and must preserve compatibility facades where required.

### D0-6 — Classic/native coexistence — GREEN

Classic and native representations may coexist in one document.

The supported composition boundary is:

| Composition | Phase-D position |
| --- | --- |
| native structural control containing classic scalar/filter expressions | **SUPPORTED** |
| classic scalar/filter expressions elsewhere in the document | **SUPPORTED / independent** |
| classic structural control owning native structural control, or the reverse | **no new Phase-D orchestration guarantee** |

Native structural controls own structural materialization and collection-item
scope creation. Classic scalar/filter expressions inside a concrete native
instance use the effective native data scope through the existing classic
binding semantics.

Phase D does not redesign classic structural controls or repair their known
nested-control runtime limitations as collateral work. Cross-representation
structural nesting must not acquire hidden precedence or double-execution rules.

Native User Fields retain the Phase-C document-level/ROOT binding semantics;
physical placement inside a repeated Section does not convert a User Field into
an item-scoped binding.

Identically named dependencies in different representations are not implicitly
aliased by the core engine. An optional mapping layer may deliberately map one
application value to multiple template dependencies.

## D0 evidence matrix

| Topic | Status after D0 |
| --- | --- |
| Writer Section as control carrier | **CLOSED** |
| Control discovery | **CLOSED** |
| Control grammar | **CLOSED** |
| Condition grammar/evaluation | **CLOSED** |
| Dependency discovery | **CLOSED** |
| Nested data scopes | **CLOSED** |
| Native ownership/carrier identity | **CLOSED** |
| Foreach Section instantiation mechanics | **CLOSED** |
| Nested foreach mechanics | **CLOSED** |
| Item-local scalar binding | **CLOSED** |
| Native/template identity rewriting | **CLOSED** |
| Foreach prototype removal | **CLOSED** |
| Empty collection semantics | **CLOSED** |
| Collection-local rollback | **CLOSED** |
| Save/reopen of finalized collections | **CLOSED** |
| User Field ROOT scope from Phase C | **CLOSED** |
| Automatic control-tree orchestration | **GREEN / D0-1** |
| Conditional Section finalization | **GREEN / D0-2** |
| Declarative execution-unit atomicity/lifecycle | **GREEN / D0-3** |
| Missing collection data policy | **GREEN / D0-4** |
| Cross-part execution in supported regions | **GREEN / D0-5** |
| Classic/native coexistence | **GREEN / D0-6** |

## D0 completion boundary

D0 is complete and the **Phase-D Change Contract is ACCEPTED**.

Implementation must proceed from
[`TEMPLATE_AUTHORING_01D_CHANGE_CONTRACT.md`](TEMPLATE_AUTHORING_01D_CHANGE_CONTRACT.md)
in exactly four planned slices D1–D4 without reopening CLOSED SECTION-03,
Phase-B inspection/scope, Phase-C User Field, shared condition semantics, or
GREEN D0 decisions without contradictory evidence.

## Explicit non-goals for D0/D

Unless one of the six open questions produces concrete contrary evidence, Phase
D must not:

- redesign SECTION-03;
- invent a new clone or identity-rewrite mechanism;
- invent a second dependency/scope model;
- invent a second condition evaluator;
- broaden Writer field support;
- implement or require a monolithic high-level `OdtTemplate::render($mappedData)` pipeline;
- solve FINALIZATION-01;
- introduce page-style authoring;
- broaden Section mutation beyond TemplateContract-supported inspected regions;
- change classic template semantics as collateral work;
- mix unrelated refactoring with declarative behavior changes.

## Required workflow from here

1. Verify this baseline and the accepted Change Contract against current `develop` when work resumes.
2. Start with D1 — part-/region-aware Section resolution.
3. Add characterization tests where the contract touches existing behavior that is not yet protected.
4. Preserve the accepted Change Contract; do not change semantics during implementation without explicit review.
5. Implement exactly the planned D1–D4 slices above the existing Section substrate.
6. Run focused tests and the full project preflight.
7. Perform LibreOffice regression for any rendering-sensitive transformation.
8. Review the final diff against the Change Contract before merge to
   `develop`.

## Handoff summary

A new chat or coding agent should be able to start Phase D with this rule:

> **Do not reopen SECTION-03 mechanics, Phase-B scope/ownership, Phase-C User
> Field semantics, or the six completed D0 decisions without contradictory
> evidence. D0 is complete and the Phase-D Change Contract is ACCEPTED. Start
> implementation with D1 from that contract. Preserve
> parent-first orchestration, Section-preserving true conditions, bounded
> execution-unit atomicity, strict collection dependencies, part-/region-aware
> execution in supported BODY and master-page regions, and the documented
> classic/native coexistence boundary. Mapping-driven automation remains an
> optional higher layer, not an inherent `OdtTemplate` lifecycle.**

Semantics before implementation.

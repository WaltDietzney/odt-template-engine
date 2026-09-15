# TEMPLATE-AUTHORING-01D — Declarative Structural Controls Baseline

## Status

**Architecture handoff baseline / D0 entry document**

This document records the evidence already established before implementation of
TEMPLATE-AUTHORING-01D. Its purpose is to prevent future work, especially after
a chat or agent handoff, from reopening questions that are already answered by
the current repository.

Phase D has **not** approved a public execution API or a Change Contract yet.
The remaining open decisions listed here must be resolved before implementation.

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

## Classic versus native controls

Classic paragraph-marker controls have existing runtime behavior and known
nested-control limitations. TEMPLATE-AUTHORING-01B records these through
`classic_nested_control_runtime_limitation` diagnostics.

Native Section controls should use the established Section architecture rather
than inherit structural limitations merely because the classic implementation
has them.

The compatibility principle is:

```text
shared semantic meaning
    └── ConditionExpression

representation-specific structural mechanics
    ├── classic markers → existing classic processor
    └── native Sections → Section architecture
```

How both representations participate in a future automatic render pass remains
an explicit Phase-D/E compatibility decision; do not silently choose precedence
or double-execute equivalent controls.

## Source-part boundary

Inspection is already cross-part and can discover native declarative Section
candidates in locations such as master-page header content in `styles.xml`.

The current Section mutation/instantiation substrate, however, resolves and
mutates Sections through `contentDom()`. It is therefore a
**content.xml execution substrate** today.

This difference is intentional evidence:

```text
inspection: content.xml + styles.xml
execution substrate: content.xml
```

Phase D must explicitly decide whether its bounded v1 execution scope is BODY /
`content.xml` only or whether a separate cross-part mutation extension is
justified. Cross-part execution must not be introduced accidentally as an
implementation detail.

The current preferred bounded direction is **BODY/content.xml-only for Phase D
v1**, unless new evidence demonstrates that 1.0 requires cross-part declarative
structural execution. This is still a scope decision to ratify in the Change
Contract, not an implemented capability.

## D0 evidence matrix

| Topic | Status before D0 |
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
| Automatic control-tree orchestration | **OPEN** |
| Conditional Section finalization | **OPEN** |
| Declarative execution-unit atomicity/lifecycle | **OPEN** |
| Missing collection data policy | **OPEN** |
| BODY-only versus cross-part execution | **OPEN — scope decision** |
| Classic/native coexistence in automatic execution | **OPEN — compatibility decision** |

## The only Phase-D questions that remain open

### D0-1 — Automatic control-tree orchestration

Specify how the executor walks declarative native controls and translates them
into existing imperative Section operations, including recursion into concrete
instances and deterministic processing order.

This is orchestration design. It is not permission to redesign Section cloning,
identity allocation, local binding, nested addressing, or collection
finalization.

### D0-2 — Conditional Section finalization

Define the native result of:

```text
#if:x    → true / false
#ifnot:x → true / false
```

The false branch can plausibly use bounded Section removal, but this must be
specified.

The true branch is especially important: decide whether the declarative carrier
Section remains with its semantic name, is renamed/finalized, is unwrapped, or
uses another evidenced native transformation. Do not choose this from API
convenience alone; inspect ODF/Writer behavior and lifecycle consequences.

### D0-3 — Declarative execution-unit atomicity and lifecycle

Existing `instantiateMany()` atomicity is operation-local. Automatic execution
introduces a larger unit:

```text
outer foreach
    → nested foreach
        → condition
            → possible later failure
```

Specify the atomicity boundary for one invocation that recursively processes a
declarative structural-control tree or selected execution unit. Do not infer a
transaction around unrelated document operations performed before or after that
invocation.

Also characterize repeated execution of that structural operation only as far
as Phase D requires. Do not use D0-3 to prescribe a global `OdtTemplate`
render lifecycle, ordering for scalar/User Field/bookmark/image operations, or
the optional Phase-E mapping/automation workflow. FINALIZATION-01 remains a
separate lifecycle/export concern.

### D0-4 — Missing collection data

Differentiate deliberately between:

```text
collection key present with []
collection key absent
collection key present with invalid/non-collection value
```

The existing empty-collection behavior is already fixed. What remains is the
automatic executor's policy for absent and invalid collection dependencies.

For conditions, existing `ConditionExpression` behavior is compatibility
evidence and should be reused unless the Change Contract explicitly documents a
reason not to.

### D0-5 — Execution source-part scope

Ratify BODY/`content.xml`-only execution for the bounded Phase-D v1, or provide
concrete evidence and a separate design for extending Section mutation across
document parts.

Do not confuse cross-part inspection or User Field binding with cross-part
Section instantiation.

### D0-6 — Classic/native coexistence

Specify what an automatic structural pass does when classic and native control
representations coexist. Preserve existing imperative APIs and classic
compatibility. Avoid both double execution and hidden precedence rules.

Phase D should solve only the structural-control boundary required for native
Section execution. Full ordering of all scalar/native-field/structured
operations belongs to Phase E.

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
- add cross-part Section mutation opportunistically;
- change classic template semantics as collateral work;
- mix unrelated refactoring with declarative behavior changes.

## Required workflow from here

1. Verify this baseline against current `develop` when work resumes.
2. Investigate only D0-1 through D0-6.
3. Add characterization tests where an open question touches existing behavior.
4. Record the accepted semantics in a Phase-D Change Contract.
5. Implement in small slices above the existing Section substrate.
6. Run focused tests and the full project preflight.
7. Perform LibreOffice regression for any rendering-sensitive transformation.
8. Review the final diff against the Change Contract before merge to
   `develop`.

## Handoff summary

A new chat or coding agent should be able to start Phase D with this rule:

> **Do not ask how Sections clone, how nested foreach is addressed, how item
> binding works, how identities are rewritten, how scopes are discovered, or
> how conditions are parsed. Those questions are already answered. Start with
> orchestration, conditional finalization, declarative execution-unit atomicity,
> missing collection data, source-part scope, and classic/native coexistence.
> Mapping-driven automation is an optional higher layer, not an inherent
> `OdtTemplate` lifecycle.**

Semantics before implementation.

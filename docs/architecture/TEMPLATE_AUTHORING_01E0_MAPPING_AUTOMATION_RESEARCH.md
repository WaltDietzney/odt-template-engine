# TEMPLATE-AUTHORING-01E0 — Mapping / Automation Architecture Research

## Status

**Research complete / architecture baseline proposed**

This document records the architecture research for TEMPLATE-AUTHORING-01E, the optional mapping / automation layer.

It is a research and decision baseline, not an implementation contract and not an approved public API. No class names or method signatures shown conceptually in this document are binding unless a later Change Contract explicitly approves them.

Phase E must remain optional. Existing imperative APIs remain first-class. This work must not define final document semantics or pre-empt FINALIZATION-01.

## 1. Context and problem

TEMPLATE-AUTHORING-01A through D established:

- reliable classic template processing and format preservation;
- unified read-only template inspection through `TemplateContract`;
- bounded Writer User Field binding;
- native declarative Section controls for `#if`, `#ifnot`, and `#foreach`;
- ROOT and nested collection-item scopes;
- part-/region-aware execution in `content.xml` and supported master-page header/footer regions;
- bounded execution-unit rollback for declarative controls.

The engine therefore already owns the relevant document semantics. What is missing is an optional application-facing layer that can connect an external application data model to the dependencies discovered in a template and then orchestrate only those existing capabilities whose mutation semantics are unambiguous.

The target problem is:

```text
Application data
       +
mapping definition
       +
TemplateContract
       ↓
resolution / validation / normalization
       ↓
resolved template data
       ↓
optional bounded automation
       ↓
existing engine capabilities
       ↓
Working ODT
```

Phase E is not a new renderer and must not become a second document model.

## 2. Governing constraints

The research is governed by the following existing architecture:

1. `TemplateContract` is the read-only semantic description of the source template.
2. Working-document mutation remains owned by `OdtDocumentContext` and existing document/template services.
3. Phase-D strict collection and scope semantics remain authoritative.
4. Native Writer User Fields remain ROOT/document-global even when physically located inside repeated structures.
5. Existing imperative APIs remain independently usable before and after optional automation.
6. No mandatory global render order may be imposed on normal library use.
7. Save, finalization, export semantics, and repeated-render lifecycle remain outside Phase E and belong to FINALIZATION-01.
8. Phase E must not introduce a renderer-neutral document AST, PHP pagination/layout model, or competing template/dependency/scope graph.

## 3. E0-1 — Separate mapping/resolution from mutation/execution

### Decision: GREEN

Mapping/resolution and document execution are separate responsibilities.

Mapping answers which application data satisfies which template dependency. It can and should operate without mutating the Working DOM.

Execution applies already resolved template-facing data through existing mutation capabilities.

```text
SOURCE / NON-MUTATING

TemplateContract
      +
Mapping Definition
      +
Application Data
      ↓
Mapping Resolution
      ↓
Resolved Mapping

──────────── mutation boundary ────────────

WORKING DOCUMENT / MUTATING

Resolved Mapping
      ↓
Automation
      ↓
existing engine services
```

A mapping error should therefore be discoverable before the first automation-owned document mutation.

This separation preserves the established distinction between source-oriented inspection and Working-DOM mutation.

## 4. E0-2 — TemplateContract remains the template authority

### Decision: GREEN

`TemplateContract` remains the sole semantic description of the template side of mapping.

Phase E must not introduce:

- a second template schema;
- a second dependency graph;
- a second scope graph;
- a competing native-object inspection model.

The existing contract already exposes dependencies with semantic identity, kind, name, scope, path, and evidence. Controls connect dependencies to their scopes and created collection-item scopes. Contract capabilities already include dependency-mapping readiness.

A separate mapping definition is still required, because the relationship between an external application model and a template is not a property of the template itself.

```text
TemplateContract      Application model/data
       \                    /
        \                  /
          Mapping Definition
```

The mapping definition describes the relationship. It does not redefine the template.

## 5. E0-3 — ROOT and collection mapping

### Decision: GREEN

The existing `DependencyDescriptor` and `DataScopeDescriptor` model is sufficient for template-side ROOT and arbitrarily nested collection-item scopes.

For example:

```text
Template:
experience[]
experience[].company
experience[].projects[]
experience[].projects[].name
```

can be mapped from:

```text
Application:
jobs[]
jobs[].employer
jobs[].projects[]
jobs[].projects[].title
```

as:

```text
jobs[]                  → experience[]
jobs[].employer         → experience[].company
jobs[].projects[]       → experience[].projects[]
jobs[].projects[].title → experience[].projects[].name
```

No additional template scope concept is required.

Application-side paths require a bounded application-path resolution mechanism because the core engine has no knowledge of external domain models.

Template paths may be useful as mapping-authoring or lookup syntax, but after resolution the semantic authority remains the corresponding `DependencyDescriptor`, not an independent path-string identity.

Collection mapping is hierarchical. Mapping a collection establishes an application/template item-scope relationship within which child dependencies can be resolved.

## 6. E0-4 — Validation, normalization, and scoped same-name resolution

### Decision: GREEN

Phase E has three distinct validation layers.

### 6.1 Static mapping validation

A mapping definition can be checked against the `TemplateContract` before concrete application data is supplied.

This includes verifying that mapping targets correspond to known dependencies and respecting the contract's existing dependency-mapping readiness/capabilities.

Phase E must not independently override a contract assessment of LIMITED or BLOCKED mapping readiness.

### 6.2 Concrete data resolution

With application data available, Phase E may verify:

- whether application paths resolve;
- whether collection sources are collections;
- whether collection items are named records where required;
- whether values can satisfy the mapped dependency shape.

This occurs before automation-owned mutation.

### 6.3 Core validation remains authoritative

Existing validation in core services remains active. Phase E may detect failures earlier, but it must not weaken or duplicate the authoritative Phase-C/Phase-D guards.

In particular, Phase-D semantics remain:

```text
missing collection ≠ empty collection
null collection    ≠ empty collection
scalar collection  ≠ empty collection
```

### 6.4 Normalization

Application-specific normalization is allowed before core execution only when it is explicit in the mapping/application layer.

For example, an application may explicitly define a null collection as an empty collection for its own domain semantics. Phase E must not silently introduce that conversion as universal core behavior.

The initial Phase-E design must avoid becoming a general-purpose ETL or transformation language.

### 6.5 Scoped same-name default

Within an explicitly established mapped scope, same-name dependencies may be resolved by a defined default.

Example:

```text
jobs[] → experience[]
```

may permit:

```text
jobs[].position → experience[].position
jobs[].current  → experience[].current
```

without redundant explicit child rules.

Resolution precedence is:

```text
1. explicit mapping
2. same-name resolution inside the explicitly mapped scope
3. unresolved
```

There is no unrestricted global name search across unrelated application branches.

## 7. E0-5 — Inspectable resolved mapping

### Decision: GREEN

A non-mutating, inspectable resolution result must exist between mapping resolution and document mutation.

For research purposes this concept is called **Resolved Mapping**. The name is not yet an approved PHP type.

It answers:

> How was this TemplateContract satisfied by these concrete application data under this mapping definition?

It may contain, conceptually:

- the target dependency;
- resolved value or collection;
- application source path/provenance;
- explicit versus scoped same-name resolution provenance;
- applied explicit normalization;
- resolution status;
- mapping diagnostics.

It should be capable of supporting a dry-run/preflight workflow.

Example:

```text
12 dependencies discovered
10 resolved by scoped same-name mapping
 1 resolved explicitly
 1 unresolved

UNRESOLVED:
experience[].end_date

No document mutation performed.
```

Mapping diagnostics describe the application-to-template relationship and must not be confused with `TemplateContractDiagnostic`, which describes source-template inspection issues.

### Explicit non-goals

Resolved Mapping is not:

- a document AST;
- an ODF/DOM model;
- a render tree;
- a layout model;
- a global execution plan;
- a save plan;
- a finalization model;
- a replacement for `TemplateContract`.

## 8. E0-6 — Bounded automation and orchestration

### Decision: GREEN

The optional Phase-E automation may automatically execute only contract-driven capabilities for which both data dependency and mutation semantics are already unambiguously defined by `TemplateContract` plus existing engine services.

For the bounded 1.0 Phase-E core, this includes:

1. classic scalar/filter bindings;
2. supported Writer User Field binding;
3. native declarative Section controls (`#if`, `#ifnot`, `#foreach`).

Inspection alone does not imply an automation action.

Therefore ordinary named Sections, bookmarks, tables, frames, images, arbitrary structured insertion, styles, page layout, and metadata are not automatically mutated merely because inspection discovers them.

Additional explicit action-mapping semantics may be considered later, but are not required for the bounded Phase-E core.

## 9. Execution ownership

Automation must not become a central renderer that independently reinterprets all template constructs.

Existing execution ownership must be preserved.

A contract evidence/binding has exactly one effective mutating owner during one automation invocation.

For example, classic scalar/filter bindings owned by a declarative foreach instance are already bound through the declarative Section execution path. A later generic classic-binding pass must not bind the same evidence again.

The orchestration design should therefore be ownership-driven rather than based on an invented universal sequence such as "foreach, then conditions, then variables".

Conceptually:

```text
Contract evidence
       ↓
effective existing owner
       ↓
one mutation path
```

The exact internal orchestration order requires specification in the Phase-E Change Contract and characterization tests. E0 does not approve an implementation order.

## 10. Writer User Field scope

Phase C remains authoritative.

Writer User Fields are ROOT/document-global dependencies in the supported 1.0 model. Physical containment inside a repeated Section does not make them collection-item-local.

Phase E must resolve and automate them according to their contract scope, not according to visual/XML containment.

## 11. Automation atomicity

### Decision: bounded automation-level atomicity is required

A Phase-E automation invocation should be one bounded atomic unit for the mutations owned by that invocation.

Semantics:

```text
success
→ all automation-owned mutations are retained

failure
→ Working Document is restored to its state immediately before
  the automation invocation
```

This is not a global `OdtTemplate` transaction and does not roll back prior imperative operations.

Example:

```text
imperative mutation A
imperative mutation B
──────── automation boundary ────────
automation mutations
failure
──────── rollback ───────────────────
state = after A + B, before automation
```

The technical snapshot/rollback mechanism is not decided by this research document.

Existing D4 atomicity remains valid inside the declarative executor. Phase E must compose with it rather than weaken it.

## 12. Lifecycle and FINALIZATION-01 boundary

Phase E does not define:

- loading lifecycle;
- mandatory global render lifecycle;
- save behavior;
- final document state;
- static materialization of all native Writer semantics;
- export behavior;
- repeated render/save semantics;
- close/reopen finalization semantics.

Automation ends when its own bounded mutations have completed.

Imperative APIs remain usable before and after automation.

Conceptually:

```php
$template = new OdtTemplate(...);

// optional imperative operations

$automation->apply(...);

// optional further imperative operations

$template->save(...);
```

This is illustrative only and does not approve `apply()`, an automation class, or any public signature.

FINALIZATION-01 remains responsible for final document/export semantics.

## 13. Practical architecture benchmark

A professional CV remains the primary generic stress test.

Application model:

```text
person
├── name
└── email

jobs[]
├── employer
├── position
├── active
└── projects[]
    └── title
```

Template contract:

```text
name
email
experience[]
experience[].company
experience[].position
experience[].current
experience[].projects[]
experience[].projects[].project_name
```

Representative mapping:

```text
person.name                   → name
person.email                  → email
jobs[]                        → experience[]
jobs[].employer               → experience[].company
jobs[].projects[]             → experience[].projects[]
jobs[].projects[].title       → experience[].projects[].project_name
```

Within the explicit `jobs[] → experience[]` scope relationship, same-name dependencies such as `position` and `current` may resolve through the scoped same-name default.

The architecture is successful if this case can be handled without CV-specific engine APIs, a second scope system, or rebuilding LibreOffice layout in PHP.

## 14. Consolidated E0 decisions

| ID | Status | Decision |
|---|---|---|
| E0-1 | GREEN | Mapping/resolution and mutation/execution are separate responsibilities. |
| E0-2 | GREEN | `TemplateContract` remains the sole template-side semantic authority. |
| E0-3 | GREEN | Existing dependency/scope semantics cover ROOT and nested collection mapping. |
| E0-4 | GREEN | Preflight validation, explicit application normalization, and scoped same-name resolution precede core execution without weakening it. |
| E0-5 | GREEN | An inspectable Resolved Mapping exists before mutation; it is not a document or global execution model. |
| E0-6 | GREEN | Optional automation is bounded to unambiguous contract-driven capabilities and owns only its own orchestration/atomicity boundary. |

## 15. Proposed Phase-E architecture

```text
                         ORIGINAL ODT
                              │
                              ▼
                       TemplateContract
                              │
                              │
Application Data ────→ Mapping Resolver ←──── Mapping Definition
                              │
                         validation
                        normalization
                         resolution
                              │
                              ▼
                       Resolved Mapping
                         + diagnostics
                              │
                    NO DOCUMENT MUTATION
══════════════════════════════╪══════════════════════════════
                       MUTATION BOUNDARY
                              │
                              ▼
                     Optional Automation
                              │
                 contract/evidence ownership
                              │
             ┌────────────────┼────────────────┐
             ▼                ▼                ▼
       User Fields      Declarative       appropriate
                         Controls       classic bindings
                              │
                              ▼
                         Working ODT
                              │
                    END AUTOMATION BOUNDARY
══════════════════════════════╪══════════════════════════════
                              │
                              ▼
                 imperative APIs remain usable
                              │
                              ▼
                       save / later
                      FINALIZATION-01
```

## 16. Explicit non-decisions

E0 does not approve:

- a public `OdtTemplate::render($mappedData)` lifecycle;
- any class name such as `MappingResolver`, `ResolvedMapping`, or `AutomationService`;
- any method name such as `apply()`, `execute()`, `automate()`, or `renderMapped()`;
- a mapping configuration syntax;
- a normalization/filter DSL;
- a universal execution order;
- automatic mutation of every inspected native object;
- generalized named-object action mapping;
- finalization or export semantics;
- implementation slices.

These belong to the Phase-E Change Contract or later milestones.

## 17. Next step

The next architecture step is to derive and review a **TEMPLATE-AUTHORING-01E Change Contract** from this research baseline before implementation.

That contract should at minimum define:

- the bounded Phase-E public/product capability;
- mapping-definition semantics and precedence;
- application-path semantics;
- static and concrete preflight behavior;
- resolved-mapping semantics and diagnostics;
- exact automation ownership and orchestration;
- automation-level rollback boundaries;
- compatibility and lifecycle constraints;
- implementation slices and validation gates.

Implementation must not begin before that contract is accepted.

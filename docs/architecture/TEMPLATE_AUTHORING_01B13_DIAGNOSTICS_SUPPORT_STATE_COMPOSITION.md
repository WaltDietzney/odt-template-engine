# TEMPLATE-AUTHORING-01B1.3 — Diagnostics / Support-State Composition

Status: ACTIVE DESIGN / NO PUBLIC API DECISION / NO PRODUCTION CHANGE

## Purpose

Define how one unified template contract reports structural problems, template-expression problems, semantic ambiguity, recognized-but-not-yet-executable declarations, compatibility limitations, source-coverage limitations, and render-blocking conditions.

The governing question is:

> How can the contract describe what is understood, what is safe, and what is executable without collapsing those questions into one global valid/invalid flag?

## 1. Governing rule

Three dimensions must remain distinct:

```text
A. diagnostic severity
B. semantic support state
C. execution readiness
```

A construct can therefore be recognized, structurally valid, and not yet executable without being invalid.

## 2. Existing diagnostic models

Document InspectionDiagnostic currently provides code, severity, message, target type, and target name.

TemplateStructureDiagnostic currently adds classification, repairable state, expression, and scope.

Both contain useful semantics. The future contract should compose them through a richer contract-level diagnostic model rather than flatten either destructively.

Existing public diagnostic DTOs do not need to be replaced merely to support the new contract.

## 3. Diagnostic envelope

A conceptual unified diagnostic may need:

```text
code
severity
message
category
execution_impact
repairability
subject_refs[]
provenance_refs[]
details
```

This is design vocabulary, not an approved DTO.

Categories may distinguish STRUCTURE, TEMPLATE_SYNTAX, SEMANTICS, COMPATIBILITY, COVERAGE, and AUTHORING.

Diagnostics should reference graph subjects and authored provenance rather than relying only on a target name, because duplicate names and non-native evidence sites are valid inspection cases.

## 4. Support state belongs to semantic graph nodes

A diagnostic explains a finding. Support state describes a semantic concept.

Conceptual support states:

```text
SUPPORTED
RECOGNIZED_NOT_EXECUTABLE
UNSUPPORTED
MALFORMED
AMBIGUOUS
```

Names remain provisional.

Example: before Phase D executes declarative controls, a Section named #foreach:experience may be understood as a repetition declaration and may yield the dependency experience[], while its control node remains RECOGNIZED_NOT_EXECUTABLE.

That state should not exist only as warning text; generic applications need machine-readable capability information.

## 5. Support state is not severity

Examples:

```text
recognized future declaration
    support: RECOGNIZED_NOT_EXECUTABLE
    severity: info/warning

duplicate native name
    native semantics understood
    severity: error

classic scalar placeholder
    support: SUPPORTED
    severity: none
```

A known runtime defect likewise does not make the intended dependency semantically unknowable.

## 6. Execution readiness is capability-specific

A global valid() flag is too coarse.

A template may be inspectable and mappable while not yet safe for high-level rendering.

Conceptual capabilities include:

```text
INSPECTION
DEPENDENCY_MAPPING
CLASSIC_RENDER
DECLARATIVE_RENDER
NATIVE_FIELD_BINDING
HIGH_LEVEL_RENDER
```

Example before Phase D:

```text
Section #foreach:experience

INSPECTION          READY
DEPENDENCY_MAPPING  READY
DECLARATIVE_RENDER  BLOCKED
HIGH_LEVEL_RENDER   BLOCKED or PARTIAL
```

Exact public capability names are deferred.

## 7. Inspectability versus executability

A construct can remain fully inspectable when execution is blocked.

The contract should still retain authored evidence, native carrier, semantic candidate, dependencies, source provenance, support state, and the reason execution is unavailable.

This is essential for authoring tools and generic data mapping.

## 8. Duplicate native identities

Duplicate native names do not destroy inspectability.

Example:

```text
Table OrderTable
Table OrderTable
```

The contract retains two native object nodes plus duplicate_native_name diagnostics.

Name-based targeting may be blocked, while scalar bindings inside both tables may remain inspectable and mappable.

Therefore readiness depends on the affected capability, not on one global failure switch.

## 9. Ambiguous ownership and partial graphs

When ownership or data scope cannot be resolved uniquely:

```text
support = AMBIGUOUS
severity = ERROR
affected semantic execution = BLOCKED
inspection = still usable
```

The inspector retains known evidence, candidate owners, native containment, and provenance. It must not guess.

A partial graph can therefore be returned with unresolved edges and diagnostics.

## 10. Coverage state

B1.2 requires reportable source coverage.

An intentionally uninspected source region is not automatically an error.

Coverage becomes diagnostically important when a requested semantic capability depends on a region that was not inspected or is unsupported.

Coverage limitation and template invalidity are therefore separate concepts.

## 11. Classic compatibility findings

A1 established several compatibility/runtime defects that the new contract must not encode as desired semantics.

### Classic IF around heterogeneous native structure

The control may be recognizable while the current paragraph-marker runtime cannot safely own sibling native blocks such as tables.

### Classic repeated native identities

The repeat dependency may be understood while legacy execution can duplicate native names unsafely.

### Nested IF inside FOREACH

The intended dependency can be projected as experience[].current even though the current classic runtime consumes nested control markers incorrectly.

The future contract must separate semantic dependency projection from compatibility execution diagnostics.

## 12. Recognition before execution

B1.3 explicitly supports staged capability growth.

Phase B may recognize candidate native declarations and expose dependencies before execution exists.

Phase C can promote approved native field families to executable.

Phase D can promote approved declarative controls to executable.

The inspection model should remain stable as support increases.

## 13. High-level render blocking principle

High-level render must not silently execute semantics that are malformed, ambiguous, unsupported, or recognized-only.

It should refuse the affected orchestration branch or capability according to the later execution contract.

Lower-level compatibility APIs may remain callable under their existing contracts.

Blocking must be subject- and capability-sensitive. An unrelated duplicate bookmark should not automatically prevent scalar rendering elsewhere.

## 14. Severity and repairability

Conceptual severity:

```text
INFO
WARNING
ERROR
```

Repairability remains orthogonal.

Potential conceptual repair states:

```text
AUTO_REPAIRABLE
AUTHOR_REPAIR_REQUIRED
NOT_REPAIRABLE_BY_ENGINE
NOT_APPLICABLE
```

B1.3 does not require replacing the current TemplateStructureDiagnostic repairable boolean.

## 15. Subject model

A unified diagnostic may concern one or multiple graph subjects.

Examples:

```text
expression topology
    -> Evidence E17

duplicate native name
    -> NativeObject N4 + NativeObject N9

ambiguous control ownership
    -> Control C2 + Evidence E22 + candidate owners

data-scope failure
    -> Binding B8 + unresolved scope
```

This is richer and safer than target-name-only diagnostics.

## 16. Overall validity

The future contract should avoid making valid() its primary semantic API.

A convenience global validity flag may exist later, but its meaning must be explicit.

A reasonable derived meaning would be: no contract-level error prevents reliable semantic interpretation.

That is different from saying every discovered construct is executable by every engine capability.

## 17. Example: declarative CV template before Phase D

```text
{{name}}

Section #foreach:experience
    {{company}}
    {{role}}
    Section #if:current
        Current
```

Possible Phase-B contract:

```text
dependencies
├── ROOT.name
└── ROOT.experience[]
    ├── company
    ├── role
    └── current

controls
├── foreach experience  RECOGNIZED_NOT_EXECUTABLE
└── if current          RECOGNIZED_NOT_EXECUTABLE

capabilities
├── inspection          READY
├── dependency mapping  READY
├── declarative render  BLOCKED
└── high-level render   BLOCKED
```

This is useful even before D.

## 18. Example: classic nested-control defect

Semantic projection remains:

```text
ROOT.experience[]
├── company
└── current
```

while the contract additionally exposes a compatibility finding such as classic_nested_control_runtime_limitation.

The graph must not claim current is global merely because the legacy runtime behaves incorrectly.

## 19. Diagnostic code compatibility

Once public, machine-readable diagnostic codes become tooling compatibility surface.

Implementation should therefore prefer stable codes, evolving human-readable messages, and structured subject/provenance references.

Application logic should not parse diagnostic message text.

## 20. Composition with current APIs

B1.3 does not require changing:

```text
DocumentInspection::diagnostics()
TemplateStructureInspection::diagnostics()
TemplateStructureInspection::valid()
```

The future source-template contract may adapt those findings and add higher-level semantic/capability diagnostics.

Existing DTOs should not be stretched to carry unrelated new concepts when composition is cleaner.

## 21. Diagnostics are not execution control flow

Runtime orchestration should derive decisions from structured semantic support/readiness, not by matching diagnostic strings.

```text
contract semantic state
        ↓
capability readiness
        ↓
render decision
```

Diagnostics explain that decision.

## 22. Decisions established by B1.3

Subject to design review, B1.3 proposes:

1. Severity, support state, and execution readiness are orthogonal.
2. Support state belongs primarily to semantic graph nodes, not only diagnostics.
3. Recognized-but-not-executable constructs remain fully inspectable.
4. Inspection readiness and render readiness are distinct.
5. Contract validity must not be reduced to one global boolean.
6. Capability-specific readiness is required.
7. Existing native/template diagnostics should be composed rather than flattened destructively.
8. Unified diagnostics need stable codes plus graph-subject/provenance references.
9. Duplicate native identities do not destroy inspectability.
10. Ambiguous ownership blocks affected execution while preserving partial graph evidence.
11. Coverage limitations are explicit and not automatically errors.
12. Classic runtime defects are compatibility findings and do not redefine intended data scope.
13. Phase B may recognize Phase C/D constructs before they become executable.
14. High-level render must not silently execute malformed, ambiguous, unsupported, or recognized-only semantics.
15. Diagnostics explain execution state; structured readiness drives execution policy.

## 23. Questions carried into B1.4

B1.4 — Compatibility & Public Surface Design must decide:

1. What public method exposes the source-template contract without breaking existing inspect().
2. What the root result type is.
3. Which graph projections receive convenience accessors.
4. How current DocumentInspection and TemplateStructureInspection remain available.
5. Whether the new contract adapts or independently projects existing descriptors.
6. What public support/readiness vocabulary is stable enough for 1.0.
7. What toArray() shape is appropriate for tooling.
8. Whether capability readiness is exposed directly or through queries.
9. What compatibility guarantees apply to evidence IDs and diagnostic codes.
10. Whether a convenience global valid() exists and exactly what it means.
11. How B stays extensible for Phase C native fields and Phase D declarative controls without speculative abstractions.
12. What minimum public surface is sufficient for 1.0 and generic CMS/plugin integrations.

## 24. B1.3 design thesis

A useful template contract must be able to say: I understand this declaration, without falsely saying: I can already execute it.

It must also be able to say: I can map the dependency correctly, while separately warning that a legacy runtime path has a known compatibility limitation.

That separation is essential if inspection is to remain stable while native fields, declarative controls, and high-level rendering are added in later phases.

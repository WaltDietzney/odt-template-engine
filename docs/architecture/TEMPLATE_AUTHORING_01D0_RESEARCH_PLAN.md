# TEMPLATE-AUTHORING-01D0 — Declarative Structural Controls Research Plan

## Status

**RESEARCH / CHARACTERIZATION — NO IMPLEMENTATION AUTHORIZED**

This document starts Phase D of TEMPLATE-AUTHORING-01.

The purpose of D0 is to determine the execution semantics for declarative
Writer Section controls before any automatic structural renderer is
implemented.

The governing rule remains:

> Semantics before implementation.

## 1. Scope

The Phase-D v1 research target is intentionally narrow:

```text
#foreach:<collection>
#if:<condition>
#ifnot:<condition>
```

carried by native Writer `text:section/@text:name`.

D0 does not authorize:

- automatic execution of these controls;
- a second foreach/condition renderer;
- `#elseif` / `#else` native Section syntax;
- `#with` or other object-name languages;
- fuzzy correction of malformed names;
- replacement of the existing visible classic template syntax;
- Phase-E high-level `render($mappedData)` orchestration.

## 2. Existing evidence baseline

Phase D starts with substantial evidence rather than from zero.

### RESEARCH-01A

Real Writer evidence established that names such as:

```text
#foreach:experience
```

survive LibreOffice authoring and ODF serialization unchanged.

The existing Section API already accepts that exact name and
`instantiateMany()` provides:

- ordered cloning;
- item-local classic scalar binding;
- deterministic native identity rewriting;
- prototype removal;
- empty-collection behavior;
- rollback after failed item creation;
- nested Section collection mechanics;
- save/reopen persistence.

Therefore Phase D must treat declarative foreach as orchestration over the
existing Section machinery, not as a second collection renderer.

### Phase B

Unified inspection already recognizes native Section candidates as:

```text
representation = NATIVE_SECTION_DECLARATION
support_state  = RECOGNIZED
```

and projects:

- `FOREACH`;
- `IF`;
- `IFNOT`;
- dependencies;
- collection-item scopes;
- nested native ownership;
- malformed declaration diagnostics.

Recognition deliberately does not imply executable support.

### Phase A / classic condition semantics

`ConditionExpression` represents the existing classic runtime grammar.
Native `#if` / `#ifnot` candidates already use that parser for dependency
inspection.

Phase D must characterize whether the same evaluator can be the semantic
authority for native execution without silently changing classic behavior.

### Phase C

Writer User Fields remain ROOT/document-global even when physically contained
inside a declarative foreach Section.

Phase D must preserve this distinction:

```text
classic scalar inside foreach -> item-local
native Writer User Field      -> ROOT/document-global
```

## 3. Central architecture hypothesis

The current evidence supports this candidate model:

```text
Writer Section declaration
        ↓
declarative control discovery
        ↓
scope/data resolution
        ↓
existing Section mutation mechanics
        ↓
existing classic scalar binding
```

For foreach:

```text
#foreach:experience
        ↓
resolve experience collection
        ↓
SectionTarget::instantiateMany()
```

The D0 task is to test the boundaries of this model, especially nesting and
lifecycle.

## 4. D0 research questions

### D-R1 — Existing execution substrate

Characterize the exact current behavior of:

- one top-level `instantiateMany()`;
- empty collections;
- invalid collection items;
- missing scalar values;
- rollback;
- prototype removal;
- repeated calls against an already-finalized prototype;
- save/reopen.

Goal: freeze the substrate that declarative foreach would orchestrate.

### D-R2 — Nested foreach scope and execution order

Use a Writer-authored structure equivalent to:

```text
#foreach:experience
    {{company}}

    #foreach:projects
        {{project_name}}
```

Characterize:

- whether the outer clone contains a correctly rewritten nested prototype;
- how the nested prototype is addressed from each outer instance;
- whether nested item data remains local to the correct outer item;
- required execution order;
- prototype removal at both levels;
- rollback behavior if a nested item fails.

The expected candidate order is outside-in for collection creation:

```text
instantiate outer item
    -> resolve nested prototype in that outer instance
    -> instantiate nested collection
```

This is a hypothesis, not yet a contract.

### D-R3 — Conditional Section mutation primitive

There is currently no approved declarative conditional execution primitive.

Characterize the smallest structure-preserving operation required for:

```text
#if:photo
#ifnot:photo
```

Questions:

- does false mean remove the entire native Section container;
- does true mean preserve the Section unchanged;
- should a true declarative marker name remain in output or be normalized;
- what happens to nested named objects;
- what happens to page/header-owned Sections;
- what rollback boundary is required.

Do not implement automatic conditions during this research.

### D-R4 — Condition grammar parity

Characterize native candidates against the existing `ConditionExpression`
runtime grammar:

```text
#if:photo
#if:status == active
#ifnot:archived
```

Determine:

- truthiness behavior;
- numeric/string comparisons;
- missing references;
- null/empty values;
- invalid expressions;
- exact `ifnot` inversion semantics.

The objective is one condition semantics shared by classic and native
representations, not a new evaluator.

### D-R5 — Missing-data policy

Phase D needs explicit semantics for absent data.

Characterize and decide separately for:

```text
missing foreach collection
present empty foreach collection
missing if dependency
present false if dependency
missing scalar required by an instantiated item
```

Do not silently equate authoring errors with intentionally empty data until the
contract decides that behavior.

The RESEARCH-01A typo `#foreach:expiriene` remains useful evidence: fuzzy
correction is forbidden.

### D-R6 — Declarative nesting matrix

Characterize at minimum:

```text
foreach -> foreach
foreach -> if
foreach -> ifnot
if      -> foreach
ifnot   -> foreach
if      -> if
```

For each combination determine:

- parent scope;
- child scope;
- execution order;
- behavior when the parent is removed;
- whether child controls are ever evaluated when their parent is false;
- whether generated physical Section suffixes affect logical declaration
  identity.

### D-R7 — Source-part boundary

Phase-B inspection can recognize declarative Sections in page-owned
header/footer regions.

Current structured Section mutation services are primarily content-DOM based.

Characterize this mismatch explicitly before promising executable header/footer
controls.

Possible outcomes include:

- Phase-D v1 execution is body-only while inspection remains broader;
- mutation services are generalized cross-part;
- page-owned declarative execution is deferred.

No outcome is preselected.

### D-R8 — Lifecycle and idempotence

Characterize interactions among:

```text
inspectTemplate()
declarative structural execution
classic render()
save()
load()
reopen saved output
repeated execution
```

Questions include:

- whether execution is destructive/finalizing for prototypes;
- whether a second execution should fail, no-op, or operate on generated
  instances;
- whether `load()` restores executable prototypes;
- whether saved output is intentionally no longer a reusable declarative
  template;
- whether source-oriented inspection remains stable in the current instance.

This question must be settled before Phase E.

### D-R9 — Atomicity and failure boundary

Determine the required transaction boundary for:

- one declarative control;
- one outer foreach including all nested controls;
- the whole declarative structural pass.

Existing `instantiateMany()` rolls back clones created by that collection
operation, but this does not automatically define rollback for a multi-control
declarative pass.

### D-R10 — Diagnostics and readiness transition

Phase B currently uses:

```text
RECOGNIZED
```

for native declarative candidates.

D0 must define what evidence is required before a control may become executable
support, and what diagnostics are needed for at least:

- missing dependency;
- wrong foreach data type;
- malformed declaration;
- unsupported condition expression;
- duplicate/ambiguous carrier;
- unsupported source part;
- nested execution failure;
- already-finalized/missing prototype.

No diagnostic vocabulary is approved by this research plan.

### D-R11 — Classic/native coexistence boundary

Characterize documents containing both:

```text
native Section #foreach:experience
classic {{#foreach:experience}} ... {{#endforeach}}
```

and analogous condition combinations.

The engine must not accidentally execute the same structural intent twice.

D0 must determine whether mixed structural representations in one ownership
scope are valid, unsupported, or diagnosable ambiguity.

### D-R12 — Phase-C interaction

Characterize native User Fields inside declarative Sections after actual
declarative cloning/removal semantics are known.

The required invariant is:

```text
native containment does not convert a ROOT User Field into item-local data
```

This extends the C3 evidence into the automatic structural lifecycle.

## 5. Evidence strategy

Prefer characterization tests and real LibreOffice-authored fixtures.

Suggested progression:

```text
D0-A  freeze existing Section collection lifecycle
D0-B  nested foreach mechanics
D0-C  conditional removal/preservation mechanics
D0-D  condition grammar and missing-data matrix
D0-E  source-part boundary
D0-F  lifecycle/idempotence/atomicity
D0-G  classic/native coexistence + Phase-C interaction
```

Where Writer serialization/layout matters, create or reuse real ODT fixtures
under `research/` first. Promote only stable evidence into
`tests/fixtures/libreoffice-reference/odt/`.

Generated `samples/output/*.odt` remain local regression artifacts unless an
explicit task concerns them.

## 6. D0 deliverables

D0 is complete only when it has produced:

1. characterization tests for the relevant existing mechanics;
2. real Writer evidence for any serialization/layout-sensitive claim;
3. an explicit result for D-R1 through D-R12;
4. a bounded Phase-D v1 semantic proposal;
5. a list of deliberately deferred controls/features;
6. a Phase-D Change Contract ready for review.

No production implementation should begin before that contract is accepted.

## 7. Initial recommendation

Start with D-R1 and D-R2.

They have the highest leverage because declarative foreach is already
mechanically plausible, while nested execution order is the first place where
automatic orchestration can diverge from the existing imperative API.

Only after that substrate is frozen should D-R3 introduce the conditional
removal/preservation question.

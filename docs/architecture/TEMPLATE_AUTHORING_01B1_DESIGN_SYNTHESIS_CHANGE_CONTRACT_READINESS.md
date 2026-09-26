# TEMPLATE-AUTHORING-01B1 — Design Synthesis / Change-Contract Readiness

Status: COMPLETE / READY FOR CHANGE CONTRACT / NO PRODUCTION CHANGE

## Purpose

Consolidate B1.0 through B1.4 into one accepted semantic and compatibility baseline and decide whether Unified Template Inspection is sufficiently defined to proceed to a bounded implementation Change Contract.

B1.0–B1.4 covered:

```text
B1.0  Contract Concepts & Taxonomy
B1.1  Contract Graph / Ownership & Data-Scope Model
B1.2  Source-Part Coverage & Provenance Model
B1.3  Diagnostics / Support-State Composition
B1.4  Compatibility & Public Surface Design
```

The synthesis does not implement the new inspection API.

## 1. Synthesis decision

B1 is coherent enough to proceed to a Change Contract.

The accepted design direction is:

```text
authored ODT source
    ↓
source-aware inspection
    ↓
TemplateContract
    ├── bindings
    ├── controls
    ├── native objects
    ├── data dependencies
    ├── provenance / ownership / data scopes
    ├── diagnostics
    ├── source coverage
    └── capability readiness
```

The contract is a typed semantic graph with public convenience projections. It is not a larger flat variable/name inventory.

## 2. Public API decision

B1 accepts the following compatibility-safe additive public entry point for the Change Contract:

```php
$contract = $template->inspectTemplate();
```

with root result type:

```php
OdtTemplateEngine\Template\TemplateContract
```

This decision supersedes the earlier conceptual shorthand of reusing inspect().

Existing APIs remain unchanged:

```php
$template->inspect(): DocumentInspection
$template->inspectTemplateStructure(): TemplateStructureInspection

$template->section(...);
$template->bookmark(...);
$template->table(...);
$template->frame(...);
```

No existing public method changes meaning in Phase B.

## 3. Lifecycle decision

inspectTemplate() describes the original authored template source.

It is semantically stable across mutations and render operations performed on the current working document.

Therefore:

```text
inspect()
    = live/current document state

inspectTemplateStructure()
    = focused original visible-expression topology

inspectTemplate()
    = unified original source-template contract
```

This distinction is mandatory public documentation for 1.0.

## 4. Source coverage decision

The version-1 contract inspects supported template-semantic content in:

```text
content.xml
styles.xml
```

styles.xml coverage is limited to authored document-content regions such as master-page-owned header/footer content. Style definition trees are not indiscriminately interpreted as template content.

Other package parts remain outside B unless a later concrete semantic requirement justifies them.

Coverage boundaries must be reportable.

## 5. Semantic graph decision

The contract distinguishes at least four relationship systems:

```text
native containment
control ownership
data-scope nesting
dependency references
```

They must not be collapsed into one parent/child hierarchy.

Every template has a conceptual ROOT data scope.

A repetition control:

- references a collection dependency in its parent data scope;
- creates a collection-item child scope;
- causes governed nested bindings/conditions to resolve against that item scope unless another repetition creates a deeper scope.

A conditional control normally inherits the current data scope rather than creating a new one.

## 6. Dependency decision

Logical dependencies are distinct from authored declaration sites.

Example:

```text
{{name}} in body
{{name}} in header

= two evidence sites
= one logical dependency ROOT.name
```

Dependency deduplication is scope-aware.

Thus:

```text
ROOT.name
experience[].name
```

are different dependencies even though both use the lexical name name.

Paths such as experience[].projects[].project_name are derived tooling projections, not primary internal identity.

## 7. Provenance decision

Every authored evidence site must be traceable without exposing DOM objects.

Provenance must be rich enough to distinguish:

- source part;
- authored region;
- master-page/header/footer context where relevant;
- representation kind;
- native owner chain;
- physical text-flow scope;
- authored order;
- raw declaration/native name;
- opaque evidence identity.

Evidence IDs are deterministic within the same unchanged source/contract snapshot but are not long-lived business identifiers and are not guaranteed to survive template edits.

Duplicate native names produce distinct evidence/native-object nodes plus diagnostics; they are never collapsed.

## 8. Diagnostics and support decision

B1 accepts the separation of:

```text
diagnostic severity
semantic support state
execution readiness
```

A construct can therefore be understood without yet being executable.

This is required so Phase B can inspect future Phase-C/D semantics without pretending they are already renderable.

Contract-level support/readiness must be machine-readable. Diagnostics explain findings but are not themselves the execution decision engine.

## 9. Validity decision

TemplateContract will not use a single global valid() result as its primary 1.0 semantic API.

Reasons:

- inspection may succeed while high-level rendering is blocked;
- one capability may be blocked while unrelated capabilities remain usable;
- recognized future declarations are not invalid merely because execution is deferred;
- duplicate native identities may affect only name-based operations.

Capability readiness plus diagnostics is the authoritative model.

## 10. Public root surface decision

The Change Contract should implement the following minimal root projections:

```php
$contract->bindings();
$contract->controls();
$contract->nativeObjects();
$contract->dependencies();
$contract->diagnostics();
$contract->coverage();
$contract->capabilities();
$contract->toArray();
```

These are accepted semantic responsibilities.

Exact descriptor class names, collection types, and helper lookups remain implementation-contract details.

## 11. Serialization decision

TemplateContract::toArray() is part of the intended 1.0 public tooling surface.

It must include an independent contract format version:

```text
contract_version = 1
```

At minimum the top-level serialized categories are:

```text
contract_version
coverage
bindings
controls
native_objects
dependencies
capabilities
diagnostics
```

Machine-readable keys, support/readiness values, diagnostic codes, and graph reference semantics become compatibility surface once released.

Human-readable messages are not stable machine contracts.

## 12. What B should implement now

B should implement the semantic contract infrastructure that is already supported by current repository evidence.

Required first-version coverage:

### 12.1 Visible scalar/filter bindings

Project currently supported visible scalar/filter/special expressions from source content regions into binding sites and logical dependencies.

### 12.2 Classic control discovery

Project classic IF/IFNOT/ELSEIF/ELSE/FOREACH control evidence and dependency intent where the grammar can be parsed reliably.

Classic controls retain representation provenance and compatibility diagnostics.

### 12.3 Source-native object discovery

Project Sections, Bookmarks, Tables, and Frames from supported source regions with provenance, duplicate preservation, and native containment where reliably derivable.

### 12.4 Source coverage

Inspect body content and supported master-page-owned content in styles.xml.

### 12.5 Data-scope graph

Derive ROOT and classic repetition item scopes for supported control structures.

### 12.6 Diagnostics/support/readiness

Compose existing structural/topology findings and add contract-level support/readiness where required.

## 13. What B must not implement prematurely

B must not execute or finalize semantics belonging to later phases.

Specifically, B does not implement:

- native Writer field binding execution;
- declarative Section control execution;
- high-level mapped-data rendering;
- finalization/export lifecycle changes;
- form-schema metadata;
- inferred application semantics from arbitrary native object names;
- mutation through contract descriptors.

## 14. Native declarative-control recognition decision

B1 accepts a bounded compromise:

> Phase B may recognize explicit native Section names that match the already researched declaration shape as candidate declarative controls, but must report them as RECOGNIZED rather than executable until Phase D approves their grammar and runtime semantics.

This applies only to explicit, mechanically recognizable declarations such as:

```text
#foreach:<name>
#if:<expression>
#ifnot:<expression>
```

Recognition must be conservative.

B must not:

- fuzzy-correct malformed declaration names;
- infer controls from ordinary Section names;
- execute the declaration;
- publish Phase-D-specific concrete subtype classes.

Rationale:

- early recognition makes inspectTemplate() useful for authoring and dependency mapping before D;
- the generic Control model already supports support/readiness separation;
- RESEARCH-01A proved Writer preserves such Section names mechanically;
- deferring all recognition to D would unnecessarily weaken B's contract value.

Phase D still owns the final declaration grammar and execution semantics and may narrow the recognized subset before 1.0 if evidence requires it.

## 15. Native field recognition decision

B should discover native field evidence only to the extent necessary for source inventory/provenance and diagnostics.

It must not classify a field as a binding dependency unless Phase C has approved that field family's semantic binding contract.

This preserves the B1.0 rule:

```text
authored evidence
    != automatically
template data requirement
```

## 16. Condition grammar decision

B0 proved the current TemplateExpressionProjector grammar is narrower than runtime evaluateCondition() semantics.

The Change Contract must therefore avoid baking the current projector regex in as the long-term condition grammar.

Before B claims complete condition dependency projection, condition parsing must be factored around one semantic grammar shared by inspection and runtime evaluation or otherwise proven equivalent.

A temporary characterization-compatible parser split may exist during early implementation slices, but the completed B contract must not silently report runtime-supported comparison conditions as unsupported.

## 17. Descriptor-family minimum

The first implementation does not need a deep subtype hierarchy.

B1 recommends a small immutable descriptor family sufficient to preserve the semantic graph:

```text
TemplateContract
BindingDescriptor
ControlDescriptor
NativeObjectDescriptor
DependencyDescriptor
DataScopeDescriptor
TemplateContractDiagnostic
TemplateContractCoverage
TemplateContractCapabilities
SourceProvenance
```

Names other than TemplateContract remain provisional until the Change Contract.

Prefer typed kind/state fields over speculative subclasses.

This keeps Phase C/D extensibility without locking unapproved semantics into public class inheritance.

## 18. Capability readiness in the first B implementation

Capability readiness belongs in the first B implementation, but only with a minimal truthful baseline.

At minimum:

```text
inspection
dependency_mapping
```

must be reportable.

Other capability keys should only be exposed when their semantics are stable enough to support real decisions.

Do not publish a large speculative capability registry merely because B1 discussed future categories.

Later C/D/E phases can add capability entries compatibly under contract_version 1 if the serialization rules explicitly allow additive capability keys.

## 19. First implementation slicing

B1 recommends the following bounded implementation sequence after the Change Contract.

### Slice 0 — Characterization / Compatibility Gate

Freeze:

- current inspect() lifecycle/return behavior;
- current inspectTemplateStructure() lifecycle/return behavior;
- source stability across render/mutation;
- current content.xml/styles.xml expression/native coverage gaps;
- diagnostic serialization baselines.

No production change.

### Slice 1 — Contract skeleton and source-region discovery

Introduce immutable TemplateContract root, contract_version 1, coverage projection, source-region/provenance model, and inspectTemplate() facade.

Initially project source-native objects and existing expression evidence without advanced control/data-scope derivation.

### Slice 2 — Binding and dependency projection

Project scalar/filter/special bindings and ROOT dependencies across supported content.xml and styles.xml authored content regions.

Deduplicate logical dependencies while preserving all evidence sites.

### Slice 3 — Classic control graph and data scopes

Project classic controls, pair supported markers, create repetition item scopes, and derive row-local dependencies.

Reconcile condition grammar with runtime semantics.

Attach compatibility findings from A1 where applicable.

### Slice 4 — Native object ownership and candidate declarative controls

Complete native containment/source-owner graph and conservatively recognize explicit Section-name declarative control candidates as RECOGNIZED/non-executable.

### Slice 5 — Diagnostics, readiness, and serialization stabilization

Compose diagnostics, finalize minimal capability readiness, stabilize toArray() contract_version 1, and run full compatibility/preflight.

Each slice remains independently testable and must not implement Phase-C/D execution.

## 20. Test contract readiness

The B Change Contract should require:

### Compatibility

- inspect() remains unchanged before/after B;
- inspectTemplateStructure() remains unchanged before/after B;
- typed target APIs remain unchanged;
- protected mutation hooks are not repurposed.

### Source lifecycle

- inspectTemplate() is stable across render/save/mutation of the working document;
- repeated inspectTemplate() calls are semantically equivalent for unchanged source;
- body and page-owned source evidence are both represented.

### Graph semantics

- repeated declaration sites map to one scoped dependency;
- same lexical name in ROOT and collection-item scope remains distinct;
- nested foreach creates nested item scopes;
- condition dependencies inherit the correct current data scope;
- native containment and data scope remain independent.

### Provenance

- evidence IDs are deterministic for unchanged fixture/source;
- duplicate native names preserve distinct nodes plus diagnostics;
- header/footer evidence retains master-page/carrier provenance;
- equal names across native object types remain distinct.

### Diagnostics/readiness

- recognized-not-executable native control candidates remain inspectable;
- ambiguous/malformed cases preserve partial graphs;
- diagnostics reference graph/provenance subjects;
- dependency mapping can remain READY when an unrelated capability is blocked.

### Serialization

- contract_version = 1;
- deterministic toArray() ordering;
- no DOM/XPath/process-local identities;
- machine-readable code/state values are stable under repeated inspection.

## 21. Manual LibreOffice regression requirement

Unified inspection itself is read-only and should not alter Writer output.

Nevertheless, at least one real Writer-authored template should be used to verify source discovery across:

- body;
- header/footer;
- named Section;
- named Table/Frame;
- visible scalar/filter expression;
- classic control;
- explicit candidate declarative Section name.

The manual check should verify that opening/saving is not required for inspection correctness and that the inspector reports authored structures as Writer actually stores them.

## 22. Change-Contract readiness decision

B1 considers Unified Template Inspection ready for a formal Change Contract.

The Change Contract should freeze:

1. public inspectTemplate(): TemplateContract entry point;
2. source-oriented lifecycle semantics;
3. contract_version 1 top-level serialization;
4. minimum descriptor/projection responsibilities;
5. source content.xml + supported styles.xml region coverage;
6. graph/data-scope semantics;
7. conservative native declaration recognition boundary;
8. diagnostic/support/readiness semantics;
9. compatibility preservation of all existing inspection/target APIs;
10. the implementation slice sequence above.

## 23. Explicitly deferred decisions

The following remain outside B and must not be pulled into the Change Contract accidentally:

- which native Writer field families become executable bindings -> Phase C;
- final declarative Section grammar and execution -> Phase D;
- mapped-data high-level rendering API and current render() compatibility -> Phase E;
- finalization/export lifecycle -> FINALIZATION-01;
- complete form schema / authoring metadata;
- mutation APIs over contract graph nodes.

## 24. B1 final architecture statement

> The authored ODT template is treated as a source-derived semantic contract rather than a bag of placeholders. The contract preserves declaration sites and ODF provenance, distinguishes native ownership from data scope, projects scoped logical dependencies, and reports semantic support separately from execution readiness. Version 1.0 should expose that model additively through inspectTemplate(): TemplateContract while preserving all existing inspection and imperative target APIs unchanged.

## 25. Next step

B1 is complete.

The next architecture artifact should be:

```text
TEMPLATE-AUTHORING-01B — Unified Template Inspection Change Contract
```

Only after that contract is reviewed and accepted should production implementation begin.

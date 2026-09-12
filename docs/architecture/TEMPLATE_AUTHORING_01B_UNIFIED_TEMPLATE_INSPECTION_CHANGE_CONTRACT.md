# TEMPLATE-AUTHORING-01B — Unified Template Inspection Change Contract

Status: ACCEPTED IMPLEMENTATION CONTRACT / IMPLEMENTATION MAY BEGIN

## 1. Purpose

Implement a unified, source-oriented, machine-readable template contract for authored ODT templates without changing the semantics of existing inspection, rendering, or imperative target APIs.

This contract translates the accepted B1.0–B1.4 design baseline and B1 synthesis into bounded production work.

Governing principle:

> Semantics before implementation. The authored ODT template is a semantic contract, not merely a bag of placeholder strings.

## 2. Source of truth and governing design

Implementation MUST conform to the repository state and the accepted architecture artifacts:

- TEMPLATE_AUTHORING_01B10_CONTRACT_CONCEPTS_TAXONOMY.md
- TEMPLATE_AUTHORING_01B11_CONTRACT_GRAPH_OWNERSHIP_DATA_SCOPE.md
- TEMPLATE_AUTHORING_01B12_SOURCE_PART_PROVENANCE_MODEL.md
- TEMPLATE_AUTHORING_01B13_DIAGNOSTICS_SUPPORT_STATE_COMPOSITION.md
- TEMPLATE_AUTHORING_01B14_COMPATIBILITY_PUBLIC_SURFACE_DESIGN.md
- TEMPLATE_AUTHORING_01B1_DESIGN_SYNTHESIS_CHANGE_CONTRACT_READINESS.md

If implementation evidence contradicts this contract, STOP and document the contradiction. Do not silently redesign the contract in code.

## 3. Public API contract

Phase B SHALL add exactly one new top-level inspection entry point to OdtTemplate:

```php
public function inspectTemplate(): TemplateContract
```

The root public result type SHALL be:

```php
OdtTemplateEngine\Template\TemplateContract
```

The method is additive. It does not replace or redefine any existing API.

## 4. Existing APIs that MUST remain semantically unchanged

The following public surfaces are compatibility constraints:

```php
$template->inspect(): DocumentInspection;
$template->inspectTemplateStructure(): TemplateStructureInspection;

$template->section(...);
$template->bookmark(...);
$template->table(...);
$template->frame(...);

$template->render();
$template->save(...);
```

In particular:

- inspect() remains a live/current-document native inspection.
- inspectTemplateStructure() remains the focused original-source visible-expression topology inspection.
- typed target APIs continue to address/mutate the current document.
- render() is NOT redefined to accept mapped data in Phase B.
- save/render lifecycle behavior is not changed by this milestone.
- protected compatibility facades/hooks MUST NOT be repurposed for unrelated source-inspection semantics.

Any required extraction behind an existing protected method must preserve polymorphic behavior where external subclasses could reasonably depend on it.

## 5. inspectTemplate() lifecycle semantics

inspectTemplate() SHALL describe the original authored source template.

Its semantic result MUST NOT depend on mutations already applied to the current working document.

For an unchanged source template:

```text
inspectTemplate()
render/mutate/save working document
inspectTemplate()
```

must produce semantically equivalent template contracts.

Implementation MAY cache immutable source-derived results, but caching is not public API semantics.

## 6. Source coverage

Contract version 1 SHALL inspect supported authored template content in:

```text
content.xml
styles.xml
```

### content.xml

Inspect supported authored body content.

### styles.xml

Inspect only supported document-content regions owned by master pages, including Writer header/footer content where present.

Do NOT treat all style definitions, automatic styles, properties, or arbitrary XML attributes as template content.

Coverage boundaries MUST be represented in the contract.

No semantic scanning of meta.xml, settings.xml, manifest metadata, embedded object XML, or arbitrary package parts is introduced by this milestone.

## 7. Semantic model

TemplateContract SHALL represent a typed semantic graph with these concept families:

```text
bindings
controls
native objects
logical dependencies
data scopes
provenance
diagnostics
coverage
capability readiness
```

The implementation MUST preserve the distinction between:

```text
authored evidence
template meaning
logical data requirement
```

A native name alone MUST NOT become an application-data dependency.

## 8. Required relationship systems

The implementation MUST NOT collapse the following into one tree:

```text
native containment
control ownership
data-scope nesting
dependency references
```

These relationships may overlap but have different semantics.

## 9. Data-scope semantics

Every contract has a conceptual ROOT data scope.

Visible bindings and conditions outside repetition resolve in the current scope, normally ROOT.

A supported foreach/repetition control:

1. references a collection dependency in its parent scope;
2. creates a collection-item child data scope;
3. governs nested bindings/conditions in that item scope unless a nested repetition creates another scope.

A conditional control normally inherits its current data scope and does not create a new data object scope.

Nested repetition MUST be representable.

## 10. Logical dependency semantics

Logical dependencies are distinct from authored evidence sites.

Example:

```text
body {{name}}
header {{name}}

two evidence sites
one logical dependency ROOT.name
```

Dependency deduplication MUST be scope-aware.

Therefore:

```text
ROOT.name
experience[].name
```

are distinct dependencies.

Human/tool paths such as:

```text
experience[].projects[].project_name
```

are derived projections and MUST NOT be the sole primary identity model.

## 11. Provenance requirements

Every authored evidence site participating in the contract MUST be traceable without exposing mutable DOM objects.

Source provenance MUST be capable of representing, where applicable:

- source part;
- authored region;
- region owner;
- concrete carrier kind;
- representation kind;
- native owner chain;
- physical/text-flow scope;
- raw declaration/native name;
- deterministic authored/source order;
- opaque evidence identity.

For master-page-owned content, provenance MUST retain sufficient context to distinguish master page and header/footer carrier.

## 12. Evidence identity

Evidence identity MUST be:

- unique within one contract snapshot;
- deterministic for the same unchanged source under the same contract/inspection algorithm;
- independent of DOM object identity;
- opaque to consumers;
- not promised to survive author edits.

DOMNode references, spl_object_id(), XPath expressions used as mutable handles, or other process-local identities MUST NOT appear in the public contract or serialization.

## 13. Native object semantics

Phase B SHALL project source-native objects for the currently established categories:

```text
Section
Bookmark
Table
Frame
```

Native object descriptors MUST preserve:

- object kind;
- native name where present;
- source/evidence identity;
- source provenance;
- duplicate occurrences;
- containment/ownership where reliably derivable.

Duplicate names MUST NOT collapse nodes.

Equal textual names across different native object types remain distinct.

Duplicate or ambiguous name conditions are diagnostic facts; they do not make source evidence disappear.

## 14. Binding semantics

Phase B SHALL project currently supported visible binding semantics from supported authored content regions.

This includes supported:

- scalar placeholders;
- filtered scalar placeholders;
- special/structural placeholders where current template semantics distinguish them.

Each authored binding site remains evidence.

Bindings reference logical dependencies in their current data scope.

Phase B MUST NOT invent application semantics from formatting, headings, bookmarks, frames, tables, or arbitrary names.

## 15. Classic control semantics

Phase B SHALL inspect supported classic controls, including the existing IF/IFNOT/ELSEIF/ELSE and FOREACH families where their grammar can be interpreted reliably.

The contract MUST preserve classic representation provenance.

A semantic control is distinct from its marker evidence.

Where a control has opening/closing or branch marker sites, the semantic control SHALL reference those evidence sites rather than pretending one marker is the whole control.

Known A1 compatibility/runtime limitations MUST be represented as compatibility findings and MUST NOT corrupt the intended data-scope model.

## 16. Condition grammar constraint

The completed Phase-B contract MUST NOT permanently use a condition grammar narrower than runtime-supported condition semantics.

Current inspection/projector limitations are characterization evidence, not the intended long-term semantic grammar.

Before Phase B claims complete classic condition dependency projection, implementation MUST either:

1. use a shared semantic condition parser/representation for inspection and runtime evaluation; or
2. prove by tests that separate parsers are semantically equivalent for the supported grammar.

Refactoring condition parsing MUST preserve existing runtime behavior. This milestone is not permission to redesign condition truth semantics.

## 17. Declarative native control candidates

Phase B SHALL conservatively recognize explicit native Section-name declarations matching the bounded researched forms:

```text
#foreach:<name>
#if:<expression>
#ifnot:<expression>
```

They SHALL be represented as recognized semantic control candidates and MAY contribute dependencies/data-scope information where the declaration can be parsed unambiguously.

They MUST NOT be executed in Phase B.

They MUST NOT be reported as already supported declarative-render execution.

Malformed names MUST NOT be fuzzy-corrected.

Ordinary Section names MUST NOT be inferred to be controls.

Phase D retains authority over final native declaration grammar and execution semantics.

## 18. Native field boundary

Phase B MAY discover native Writer field evidence for inventory/provenance purposes when encountered in supported regions.

Phase B MUST NOT classify a native field as a binding/data dependency merely because it exists.

Phase C decides which field families have binding semantics and execution behavior.

No Phase-C-specific public concrete subtype is introduced here.

## 19. Support state, diagnostics, and readiness

The contract MUST keep these dimensions separate:

```text
diagnostic severity
semantic support state
capability readiness
```

A construct can be understood for inspection/dependency mapping without being executable.

The implementation SHALL use a small stable support-state vocabulary. The implementation Change Contract baseline is:

```text
SUPPORTED
RECOGNIZED
UNSUPPORTED
MALFORMED
AMBIGUOUS
```

RECOGNIZED means semantically understood enough for inspection but not necessarily executable.

Capability readiness baseline:

```text
READY
LIMITED
BLOCKED
NOT_APPLICABLE
```

At minimum contract version 1 SHALL expose readiness for:

```text
inspection
dependency_mapping
```

Do not add speculative capability keys without a concrete consumer semantic.

## 20. Partial contracts and hard failures

Semantic authoring problems SHOULD produce a partial TemplateContract with diagnostics whenever source XML remains meaningfully inspectable.

Examples include:

- unsupported expressions;
- malformed declarations;
- duplicate native identities;
- recognized-but-not-executable controls;
- bounded ambiguity.

Hard exceptions remain appropriate when no meaningful contract can be constructed, including unreadable package/core source or XML parsing failure that prevents source inspection.

Exact exception classes SHOULD reuse established repository conventions where suitable; do not invent a broad exception hierarchy without need.

## 21. Global validity

TemplateContract SHALL NOT introduce a primary global valid() boolean in Phase B.

Consumers SHALL use capability readiness plus diagnostics.

This is intentional: inspection, dependency mapping, and later execution capabilities can have different readiness states.

## 22. Minimum public root projections

TemplateContract SHALL expose these read-only responsibilities:

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

Exact collection implementation MAY be decided during bounded implementation, but returned semantic objects MUST be immutable/read-only from the consumer perspective.

Do not expose DOM handles.

Do not introduce redundant public aliases without evidence.

## 23. Descriptor-family baseline

Prefer a small descriptor family with typed kind/state fields rather than a deep inheritance tree.

Expected minimum responsibilities are represented conceptually by:

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

Only TemplateContract is frozen by name in this contract.

Other exact class names are implementation-level decisions, but implementation MUST NOT publish speculative Phase-C/D concrete subclasses merely to mirror the conceptual taxonomy.

If a proposed public descriptor shape materially changes the semantic contract, STOP for review before publishing it.

## 24. Serialization contract version 1

TemplateContract::toArray() SHALL expose a deterministic machine-readable representation with:

```text
contract_version = 1
```

Top-level keys SHALL be:

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

The representation MUST be deterministic for an unchanged source.

It MUST NOT expose:

- DOM/XPath handles;
- process-local IDs;
- internal service/class names as schema;
- mutable object references.

Machine-readable diagnostic codes, state values, graph references, and serialized keys become compatibility surface once published.

Human-readable diagnostic messages are not stable machine API.

Within contract_version 1, prefer additive evolution. Incompatible serialization changes require an explicit contract-version decision.

## 25. Ordering

Public collections and toArray() output MUST use deterministic ordering.

Default ordering SHOULD follow authored/source order where that has semantic/explanatory value.

Derived logical collections such as dependencies MUST use a documented deterministic ordering that remains stable across repeated inspection.

Do not rely on incidental DOM hash/object order or associative-map insertion side effects unless explicitly constructed as the deterministic contract order.

## 26. Lookup semantics

No name/path convenience lookup is required for the first Phase-B implementation.

If one is added during Phase B, it MUST preserve ambiguity.

A lookup MUST NOT silently return the first matching source object when multiple contract nodes satisfy the query.

Any such API addition requires focused tests and review because it becomes public compatibility surface.

## 27. Architecture and service boundaries

The implementation SHOULD use small read-only services rather than growing OdtTemplate into a semantic-inspection God class.

OdtTemplate owns the public facade/lifecycle entry point.

Source discovery, provenance projection, semantic interpretation, graph construction, diagnostics composition, and serialization SHOULD be separated where responsibilities justify it.

Services SHOULD be stateless where practical.

Avoid:

- a large mutable inspection context containing duplicated document state;
- parallel mutable copies of source semantics;
- speculative generic graph frameworks;
- mutation through inspection descriptors;
- rebuilding Writer layout semantics in PHP.

Reuse established source/projector/inspection behavior where semantically correct, but do not force incompatible live-document abstractions into the source contract.

## 28. No behavior changes hidden inside refactoring

If implementation needs to extract condition parsing, source-region discovery, or descriptor construction from existing code:

1. characterize current behavior first;
2. preserve current public runtime semantics;
3. make the extraction separately reviewable where practical;
4. only then consume the extracted semantic service from unified inspection.

Unexpected legacy behavior MUST be characterized/documented rather than silently fixed unless this Change Contract explicitly requires the behavior change.

## 29. Implementation slices

Implementation SHALL proceed in bounded slices.

### Slice 0 — Characterization / Compatibility Gate

No production behavior change.

Freeze with tests:

- inspect() lifecycle and return behavior;
- inspectTemplateStructure() lifecycle and return behavior;
- original-source stability across render/mutation/save;
- content.xml versus styles.xml current coverage;
- relevant diagnostics/serialization baselines;
- current condition grammar/runtime versus projector behavior.

Exit criterion: existing behavior is sufficiently characterized to distinguish intentional Phase-B additions from regressions.

### Slice 1 — Contract Skeleton & Source-Region Discovery

Implement:

- inspectTemplate() facade;
- immutable TemplateContract skeleton;
- contract_version 1;
- coverage model;
- source-part/authored-region discovery;
- source provenance/evidence identity foundation;
- initial source-native object and expression evidence projection.

Do NOT yet claim complete scoped dependency/control semantics.

Exit criterion: source contract can deterministically report bounded source coverage/evidence from body and page-owned content without changing existing APIs.

### Slice 2 — Binding & Dependency Projection

Implement:

- supported visible binding descriptors;
- ROOT data scope;
- logical scalar/special dependencies;
- scope-aware dependency deduplication;
- evidence-site references from bindings to dependencies;
- body/header/footer shared ROOT semantics.

Exit criterion: generic tooling can reliably discover source binding requirements and all declaration sites outside repetition.

### Slice 3 — Classic Controls & Data Scopes

Implement:

- classic control semantic descriptors;
- marker/control evidence relationships;
- foreach collection dependencies;
- collection-item scopes;
- nested repetition scopes;
- condition dependencies in current scope;
- condition grammar reconciliation required by section 16;
- A1 compatibility findings without redefining intended scope.

Exit criterion: classic templates produce a truthful scoped dependency graph, including nested conditions/foreach, while current runtime behavior remains compatible.

### Slice 4 — Native Ownership & Declarative Candidates

Implement:

- native containment/owner graph completion;
- duplicate/ambiguity diagnostics;
- explicit native Section-name candidate recognition;
- RECOGNIZED/non-executable declarative control state;
- candidate dependency/data-scope projection only where unambiguous.

Exit criterion: source-native structure and candidate declarative semantics are explainable without Phase-D execution.

### Slice 5 — Diagnostics, Readiness & Serialization Stabilization

Implement/finalize:

- unified contract diagnostics;
- support-state composition;
- inspection/dependency_mapping readiness;
- partial-contract behavior;
- deterministic contract_version-1 toArray();
- compatibility/preflight suite.

Exit criterion: public Phase-B contract is stable, deterministic, documented, and ready to become the foundation for C/D/E.

## 30. Required automated tests

Each implementation slice MUST add focused tests.

The completed Phase B MUST prove at least:

### Existing compatibility

- inspect() behavior unchanged;
- inspectTemplateStructure() behavior unchanged;
- render()/save() behavior not changed by inspection;
- typed target APIs remain unchanged;
- relevant protected facade polymorphism remains intact.

### Source lifecycle

- inspectTemplate() uses original authored source;
- repeated calls are semantically equivalent;
- current-document mutations do not alter the contract;
- body and supported master-page-owned content are both discovered.

### Graph/data scope

- repeated evidence sites deduplicate to one dependency in the same scope;
- identical lexical names in ROOT and item scopes remain distinct;
- foreach creates item scope;
- nested foreach creates nested item scope;
- conditions inherit current scope;
- native containment does not redefine data scope.

### Provenance/native identity

- deterministic evidence identity for unchanged fixtures;
- duplicate native names remain separate nodes;
- equal cross-type names remain separate;
- master-page/header/footer provenance is retained;
- source order is deterministic.

### Diagnostics/readiness

- unsupported/malformed evidence can yield partial contracts;
- declarative candidates are RECOGNIZED but not executable;
- duplicate/ambiguous native identity is diagnosed without destroying inspection;
- dependency_mapping can remain READY when unrelated future execution is unavailable.

### Serialization

- contract_version is exactly 1;
- top-level keys match section 24;
- ordering is deterministic;
- no DOM/XPath/process-local values leak;
- repeated toArray() is stable for unchanged source.

### Condition semantics

- every currently runtime-supported classic condition form covered by Phase B is inspectable with equivalent dependency semantics;
- extraction/refactoring does not change characterized runtime evaluation behavior.

## 31. Integration and regression tests

Phase B SHALL include integration fixtures covering at minimum:

- body scalar/filter binding;
- header/footer binding in styles.xml;
- named Section;
- Bookmark;
- named Table;
- named Frame;
- duplicate native name;
- same name across native object types;
- classic IF;
- classic FOREACH;
- condition inside foreach;
- nested foreach where current syntax supports characterization;
- explicit #if Section candidate;
- explicit #foreach Section candidate;
- malformed/unsupported declaration;
- repeated logical dependency across body and page-owned content.

Reuse real Writer-authored fixtures where possible rather than constructing only synthetic XML.

## 32. Manual LibreOffice regression

Before Phase-B closeout, inspect at least one real Writer-authored ODT containing representative body and header/footer authoring structures.

Confirm:

- LibreOffice stores the expected source structures;
- inspectTemplate() discovers them without requiring Writer normalization/resave;
- inspection itself does not modify the ODT;
- no rendering/layout regression was introduced indirectly by shared refactoring.

Manual Writer verification complements automated tests; it does not replace them.

## 33. Standard preflight

Before final Phase-B review, run as applicable:

```text
focused PHPUnit tests
relevant integration tests
PublicSampleSmokeTest
full composer test
PHP lint for src/ and tests/
composer validate
git diff --check
documentation build/check if relevant
```

Also verify no LibreOffice lock files or unrelated samples/output artifacts are included.

samples/output/*.odt local regression artifacts MUST NOT be restored, regenerated, deleted, or committed unless the specific slice explicitly requires them.

## 34. Documentation obligations

Before Phase B is considered complete, documentation MUST explain the distinction between:

```text
inspect()
inspectTemplateStructure()
inspectTemplate()
```

The later TEMPLATE-AUTHORING-01 documentation stream MUST build on this contract to provide an author-facing/programmer-facing "How to create a template" / Template Philosophy guide.

Phase B itself need not complete that full guide, but public API documentation for inspectTemplate() and TemplateContract MUST not be deferred past Phase-B closeout.

## 35. Explicit non-goals

This Change Contract does NOT authorize:

- replacement/removal of classic template syntax;
- native field execution;
- declarative native control execution;
- mapped-data high-level render orchestration;
- changing render() to render($data);
- finalization/export lifecycle redesign;
- automatic form generation metadata;
- application validation policy;
- inference from visual formatting;
- arbitrary named-object-to-data mapping;
- contract-node mutation APIs;
- Writer layout reconstruction;
- TEMPLATE-FORMAT-PRESERVATION-01 work beyond characterized compatibility findings;
- TEMPLATE-AUTHORING-UX-01 expansion;
- STYLE-API-02 or STYLE-CONTEXT-01 work.

## 36. Stop conditions

Implementation MUST pause for architecture review if any slice reveals that:

1. original-source semantics cannot be obtained without changing existing lifecycle behavior;
2. content.xml/styles.xml source coverage conflicts with established PAGE-FLOW behavior;
3. a public API listed as unchanged would need a semantic change;
4. condition grammar cannot be unified/equated without changing runtime semantics;
5. native declarative candidate recognition requires committing to Phase-D execution semantics;
6. descriptor design requires exposing mutable DOM/source handles;
7. contract_version-1 serialization cannot represent required graph/provenance semantics without a material redesign;
8. a proposed fix would mix unrelated legacy behavior changes into the refactor;
9. sample/output artifacts would need to be normalized/committed merely to make tests pass;
10. implementation discovers a contradiction with the accepted B1 design baseline.

The correct response is evidence + architecture decision, not an opportunistic workaround.

## 37. Completion criteria

TEMPLATE-AUTHORING-01B is complete only when:

- inspectTemplate(): TemplateContract exists as specified;
- contract source lifecycle is proven;
- bounded content.xml/styles.xml coverage is implemented;
- bindings, classic controls, native objects, scoped dependencies, provenance, diagnostics, coverage, and minimal readiness are represented;
- declarative Section candidates are recognized but not executed;
- contract_version-1 serialization is deterministic;
- existing public APIs remain compatible;
- focused/full automated validation passes;
- manual LibreOffice regression is completed;
- public inspection documentation is updated;
- final diff/preflight review finds no unrelated scope creep.

## 38. Contract thesis

> Phase B adds a read-only semantic view of the authored ODT template. It preserves ODF provenance and native structure, derives scoped application-data dependencies from declared template semantics, distinguishes recognition from execution, and exposes the result through an additive TemplateContract API. It must make generic integrations possible without changing classic rendering behavior or pre-empting the native field, declarative-control, or high-level rendering phases that follow.

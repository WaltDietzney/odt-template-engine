# TEMPLATE-AUTHORING-01B1.4 — Compatibility & Public Surface Design

Status: ACTIVE DESIGN / PUBLIC SURFACE PROPOSAL / NO PRODUCTION CHANGE

## Purpose

Translate the semantic model established by B1.0–B1.3 into a minimal, backward-compatible public inspection surface for version 1.0.

B1.4 answers:

> How should applications access the authored template contract without changing the meaning of existing inspection APIs or exposing unstable implementation details?

This is still design work. No public API is implemented by this document.

## 1. Existing public surfaces are compatibility constraints

The following methods already exist and have distinct semantics:

```php
$template->inspect(): DocumentInspection
$template->inspectTemplateStructure(): TemplateStructureInspection
```

They must retain their meaning for 1.0.

### inspect()

Current meaning:

```text
current mutable document state
    -> native Sections / Bookmarks / Tables / Frames
    -> live DocumentInspection
```

B1.4 does not change its return type or reinterpret it as a source-template contract.

### inspectTemplateStructure()

Current meaning:

```text
original source content.xml
    -> visible template-expression topology
    -> TemplateStructureInspection
```

B1.4 does not remove or silently broaden this method.

It remains useful as a focused low-level authoring/topology inspection API.

## 2. New public entry point

B1.4 proposes one additive facade method for the unified authored template contract:

```php
$template->inspectTemplate(): TemplateContract
```

Rationale:

1. It preserves inspect() compatibility.
2. It clearly denotes template inspection rather than live document inspection.
3. It composes naturally with the existing inspectTemplateStructure() name.
4. It describes an operation, while the returned value describes the semantic result.
5. It leaves room for the existing focused structure inspector to remain available.
6. It avoids introducing both templateContract() and inspectTemplate() aliases in 1.0.

The earlier conceptual shorthand:

```php
$schema = $template->inspect();
```

is therefore superseded by the compatibility-safe proposal:

```php
$contract = $template->inspectTemplate();
```

This is an API design correction driven by repository evidence, not a change in product goal.

## 3. Root result type

B1.4 proposes the semantic root type name:

```php
TemplateContract
```

Conceptual namespace:

```text
OdtTemplateEngine\Template\TemplateContract
```

Why TemplateContract instead of TemplateInspection:

- the result is more than an inventory snapshot;
- it contains derived dependencies, controls, scopes, readiness, coverage, and provenance;
- generic applications consume it as a machine-readable contract between template and application data;
- inspection describes how it is produced, while contract describes what it means.

The type should remain immutable and DOM-free.

## 4. Minimum 1.0 root surface

The root contract should expose a deliberately small set of read-only projections.

Conceptually:

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

Exact PHP return types are deferred to the implementation Change Contract, but each method must return immutable descriptors/projections rather than mutable DOM handles.

### bindings()

All discovered semantic binding sites, including authored-site multiplicity.

### controls()

All discovered semantic controls, preserving representation provenance such as classic_marker versus native_section_declaration.

### nativeObjects()

Source-oriented native objects participating in the inspected template contract.

This is not the same as current live DocumentInspection. The result must preserve source identities, duplicates, document-part provenance, and containment relationships.

### dependencies()

Logical, deduplicated data requirements projected through data scopes.

Examples:

```text
name
experience[]
experience[].company
experience[].current
```

This is the primary mapping-oriented view for generic integrations.

### diagnostics()

Unified contract diagnostics as designed in B1.3.

### coverage()

Reports the bounded source-inspection coverage established in B1.2.

### capabilities()

Exposes capability-specific readiness rather than forcing consumers to infer execution state from diagnostic strings.

## 5. Convenience projections should not replace the graph

High-value public projections are useful, but the underlying semantic model remains graph-based.

Dependency paths are derived views rather than primary identity.

## 6. No direct source-DOM access

The public contract must not expose DOMNode, DOMElement, DOMDocument, XPath handles, or process-local object IDs.

The contract is inspection metadata, not a mutation backdoor.

Applications needing mutation continue to use established imperative APIs such as section(), bookmark(), table(), and frame().

## 7. Existing imperative target APIs remain unchanged

Important distinction:

```text
inspectTemplate()
    -> authored source contract

section()/bookmark()/table()/frame()
    -> mutable current document targets
```

A source-contract native object descriptor must not be mistaken for a live target handle.

## 8. Existing inspection APIs remain first-class

B1.4 does not deprecate inspect() or inspectTemplateStructure() for version 1.0.

Use inspect() for the current native document snapshot after mutations.

Use inspectTemplateStructure() for low-level visible-expression topology and normalization diagnostics.

Use inspectTemplate() for the authored, unified semantic template contract.

This three-way distinction is explicit and documented.

## 9. Relationship to existing result types

TemplateContract should not extend DocumentInspection or TemplateStructureInspection.

Composition/adaptation is preferred because the lifecycle, coverage, and semantic responsibilities differ.

Existing descriptor logic may be reused internally without implying substitutability.

## 10. Source-oriented native object descriptors

The contract likely needs source-oriented native object descriptors rather than exposing current live descriptors unchanged.

They should preserve contract/evidence identity, native type, native name, source part, authored region, owner chain, containment relationships, and relevant diagnostics/support state.

Exact descriptor class names belong to the Change Contract.

## 11. Dependency public view

Every logical dependency should expose at least logical name, dependency kind, data scope identity, derived path, declaration/reference sites, and relevant support/readiness implications.

Conceptual kinds include SCALAR, COLLECTION, and CONDITION_REFERENCE.

The exact enum model remains subject to the Change Contract.

## 12. Optional dependency tree projection

A dependency tree is valuable to generic applications:

```text
ROOT
├── name
├── email
└── experience[]
    ├── company
    └── current
```

The Change Contract should decide whether dependencies() alone can serve this need or whether a separate dependencyTree() projection is justified.

Prefer the smaller API when one representation is sufficient.

## 13. Lookup convenience methods

Name/path lookup helpers may be useful later, but ambiguity must be preserved.

A new contract lookup must not silently return the first item when the contract knows a query is ambiguous.

This differs deliberately from some existing live inspection convenience behavior.

## 14. Support-state public vocabulary

For 1.0, publish only a small stable vocabulary if implementation requires it.

Candidate semantic states:

```text
SUPPORTED
RECOGNIZED
UNSUPPORTED
MALFORMED
AMBIGUOUS
```

RECOGNIZED means understood for inspection but not necessarily executable.

Execution readiness remains separate.

Final naming belongs to the Change Contract.

## 15. Capability readiness public vocabulary

Candidate readiness states:

```text
READY
LIMITED
BLOCKED
NOT_APPLICABLE
```

This permits combinations such as dependency_mapping=READY, classic_render=LIMITED, declarative_render=BLOCKED.

Keep the vocabulary small because it becomes public tooling surface.

## 16. No speculative Phase-C/D public classes in B

B should remain extensible for native fields and declarative controls without publishing concrete classes that pretend those semantics are finalized.

Generic binding/control descriptors can carry kind, provenance, support state, dependencies, and ownership.

Phase C and D can add supported kinds within those families.

Do not publish concrete UserFieldBinding or DeclarativeForeachSection types during B.

## 17. toArray() as tooling contract

TemplateContract::toArray() should be part of the 1.0 public surface.

Conceptual top-level shape:

```php
[
    'contract_version' => 1,
    'coverage' => ...,
    'bindings' => ...,
    'controls' => ...,
    'native_objects' => ...,
    'dependencies' => ...,
    'capabilities' => ...,
    'diagnostics' => ...,
]
```

The exact nested shape belongs to the Change Contract.

## 18. Contract format version is not package version

The serialized contract needs its own format version.

Consumers should not infer schema compatibility from the Composer package version.

Example: contract_version = 1.

## 19. toArray() compatibility rule

Once 1.0 publishes TemplateContract::toArray(), keys and machine-readable enum/code values become compatibility surface.

Therefore:

- do not expose internal class names as schema;
- do not expose DOM/XPath;
- keep diagnostic codes stable;
- keep support/readiness values stable;
- prefer additive fields over destructive renames within one contract version;
- bump contract_version for incompatible serialized changes.

Human-readable diagnostic messages are not stable machine contracts.

## 20. Evidence identity compatibility

Evidence IDs are deterministic within the same unchanged source template and contract format/inspection algorithm, but are not permanent business identifiers and are not guaranteed to survive template edits.

Applications may use them to correlate items inside one inspection result.

Exact ID syntax remains opaque.

## 21. Diagnostic code compatibility

Machine-readable diagnostic codes become public tooling compatibility surface once released.

Applications may branch on codes, but should not branch on human-readable messages.

New codes may be added compatibly. Removal or semantic reinterpretation requires explicit compatibility handling.

## 22. Global valid() decision

TemplateContract should not require a primary valid() method for 1.0.

B1.3 established that inspection may be ready while rendering is blocked, one capability can be blocked while others remain usable, recognized future declarations are not necessarily invalid, and duplicate identities may affect only certain target operations.

Capability readiness plus diagnostics is the clearer contract.

## 23. Minimal public 1.0 proposal

```php
use OdtTemplateEngine\Template\TemplateContract;

$contract = $template->inspectTemplate();

$contract->dependencies();
$contract->bindings();
$contract->controls();
$contract->nativeObjects();
$contract->capabilities();
$contract->coverage();
$contract->diagnostics();
$contract->toArray();
```

Existing APIs remain unchanged:

```php
$template->inspect();
$template->inspectTemplateStructure();
$template->section(...);
$template->bookmark(...);
$template->table(...);
$template->frame(...);
```

## 24. Generic CMS/plugin workflow

Conceptually:

```php
$template = new OdtTemplate($path);
$contract = $template->inspectTemplate();

// Generic application maps its own data model to contract dependencies.
$mapping = $application->mapTo($contract->dependencies());

// High-level rendering belongs to Phase E.
```

This supports the intended small-MVC direction without turning the core engine into a form builder.

## 25. High-level render naming remains Phase E

B1.4 records an additional compatibility constraint: the current public render() already exists with no arguments.

Therefore the earlier conceptual render($mappedData) shorthand must not silently replace the existing method.

Phase E must decide on a compatibility-preserving approach: an additive safe signature, a new high-level method name, an orchestration service, or another bounded design.

## 26. Protected compatibility surfaces

inspectTemplate() should be implemented through dedicated read-only source-analysis services rather than repurposing protected mutation hooks.

Preserve protected facades where they represent polymorphic compatibility, but do not turn unrelated legacy hooks into general inspection extension points.

## 27. Failure behavior of inspectTemplate()

Inspection should prefer returning a partial contract with diagnostics when the ODT is structurally readable but contains unsupported or malformed template semantics.

Hard exceptions remain appropriate for package-level failures where no meaningful source contract can be constructed, such as an unreadable archive, missing required core XML, or invalid XML that cannot be parsed.

Exact exception policy belongs to the Change Contract.

## 28. Caching/lifecycle semantics

The public semantic guarantee is:

```text
inspectTemplate() describes the original authored source template
```

It must not change merely because the current working document was rendered or mutated.

Implementation may cache an immutable contract if safe, but caching is not part of the public API contract.

## 29. Public API documentation requirement

Version 1.0 documentation must clearly distinguish:

| API | View | Lifecycle |
| --- | --- | --- |
| inspect() | native current-document snapshot | mutable/live |
| inspectTemplateStructure() | visible source-expression topology | original source, focused |
| inspectTemplate() | unified authored template contract | original source, semantic |
| typed target APIs | mutable native targets | current document |

This distinction belongs in the future Template Authoring documentation, not only architecture docs.

## 30. Compatibility decisions proposed by B1.4

Subject to final B1 synthesis and Change Contract:

1. Keep OdtTemplate::inspect(): DocumentInspection unchanged.
2. Keep OdtTemplate::inspectTemplateStructure(): TemplateStructureInspection unchanged.
3. Add one public method: OdtTemplate::inspectTemplate(): TemplateContract.
4. Use immutable TemplateContract as the source-oriented semantic root type.
5. Use composition/adaptation rather than inheritance from current inspection result types.
6. Keep typed target APIs unchanged and distinguish source descriptors from live targets.
7. Expose root projections for bindings, controls, native objects, dependencies, diagnostics, coverage, capabilities, and serialization.
8. Keep dependency paths derived rather than primary identities.
9. Preserve ambiguity; do not silently first-match in new contract lookups.
10. Publish only a small stable support/readiness vocabulary after the Change Contract finalizes names.
11. Publish toArray() with independent contract_version.
12. Treat diagnostic codes and serialized machine values as compatibility surface.
13. Bound evidence-ID stability to unchanged-source contract semantics.
14. Do not require global valid() for 1.0.
15. Keep semantic authoring errors in partial inspectable contracts where possible.
16. Record current parameterless render() as a separate Phase-E compatibility constraint.
17. Avoid speculative concrete Phase-C/D public subtype classes in Phase B.

## 31. Questions carried into B1 synthesis

The B1 synthesis must decide whether B1.0–B1.4 are coherent enough to become the basis for a Change Contract.

Remaining items:

1. Confirm inspectTemplate() as the new public method name.
2. Confirm TemplateContract as the root type name.
3. Decide the minimum descriptor families required for the first implementation slice.
4. Decide whether capability readiness belongs in the first B implementation or can begin minimally and expand in C/D.
5. Define the smallest implementation slice that produces real value without implementing Phase C/D semantics prematurely.
6. Define characterization and compatibility tests for all three inspection APIs.
7. Decide whether B should recognize native declarative-control candidates immediately or defer their recognition to D while leaving the model extensible.
8. Establish the contract-version-1 serialization baseline.

## 32. B1.4 design thesis

Version 1.0 should add one clear authored-template inspection entry point rather than reinterpret existing APIs.

The compatibility-safe direction is:

```php
$contract = $template->inspectTemplate();
```

where TemplateContract is an immutable, source-derived semantic contract that can grow in capability while preserving the existing meanings of inspect() and inspectTemplateStructure().

This keeps the public API understandable, additive, and aligned with the product goal: a generic application can learn what an ODT template declares without having to understand or reconstruct Writer layout.

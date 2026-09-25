# TEMPLATE-AUTHORING-01B — Unified Template Inspection Closeout

Status: COMPLETE / GATE GREEN

## Scope

TEMPLATE-AUTHORING-01B establishes the read-only semantic contract of the original LibreOffice-authored template source.

It adds the public `OdtTemplate::inspectTemplate(): TemplateContract` facade while preserving the existing meanings of `inspect()`, `inspectTemplateStructure()`, `render()`, `save()`, and the imperative native target APIs.

Phase B does not execute native Writer fields, declarative native controls, or a mapped-data high-level render pipeline.

## Overall review result

The final Phase-B review against the accepted Change Contract and B1.0–B1.4 design baseline is green.

The implementation satisfies the required boundaries for:

- original-source lifecycle;
- bounded source coverage;
- authored evidence versus logical dependency identity;
- scope-aware dependency projection;
- classic control semantics;
- condition-grammar reconciliation;
- native ownership and duplicate identity;
- conservative declarative Section recognition;
- diagnostics/support/readiness separation;
- partial contracts;
- deterministic contract-version-1 serialization;
- compatibility with existing inspection/render/save APIs;
- public programmer-facing documentation;
- real Writer-authored integration evidence;
- automated and manual LibreOffice regression.

## Contract surface

The additive public entry point is:

```php
$contract = $template->inspectTemplate();
```

The result type is:

```text
OdtTemplateEngine\Template\TemplateContract
```

The public root projections are:

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

`contract_version` is exactly `1`.

## Lifecycle boundary

The three inspection APIs retain distinct semantics:

```text
inspect()
    current/live working document state

inspectTemplateStructure()
    focused original template-expression topology

inspectTemplate()
    unified semantic contract of the original authored template
```

`inspectTemplate()` remains semantically stable across rendering, saving, and mutations of the working document.

## Source coverage

Contract version 1 inspects:

- `content.xml` body content;
- page-owned header/footer content under Writer master pages in `styles.xml`.

Coverage explicitly excludes:

- `meta.xml`;
- `settings.xml`;
- manifest semantics;
- embedded-object semantic scanning;
- arbitrary style-definition scanning.

## Graph and dependency semantics

Phase B preserves the distinction:

```text
authored evidence
native containment
semantic control ownership
data-scope nesting
logical dependency references
```

These relationships are not collapsed into one parent/child tree.

Dependency identity is scope-aware. For example:

```text
name
experience[].name
```

remain distinct logical dependencies.

Foreach controls create collection-item scopes, nested foreach creates nested item scopes, and conditions inherit the current data scope.

## Native and declarative semantics

Sections, Bookmarks, Tables, and Frames are represented as source-oriented native objects with deterministic identities and provenance.

Duplicate native names remain separate evidence nodes and produce diagnostics rather than being silently collapsed.

Bounded Section declarations are recognized conservatively:

```text
#foreach:<name>
#if:<expression>
#ifnot:<expression>
```

They use support state `RECOGNIZED` and may contribute dependency/scope information when unambiguous.

They are not executed in Phase B. Phase D retains authority over final declarative grammar and execution semantics.

## Diagnostics and readiness

Diagnostic severity, semantic support state, and capability readiness remain separate.

Contract version 1 exposes readiness for:

```text
inspection
dependency_mapping
```

Readiness values are:

```text
READY
LIMITED
BLOCKED
NOT_APPLICABLE
```

Capability readiness is derived from semantic interpretation state. Diagnostics explain that state; diagnostic codes are not used as readiness control flow.

Partial contracts are supported where meaningful inspection remains possible.

No primary global `valid()` boolean is introduced.

## Condition grammar gate

The earlier characterized divergence between runtime-supported comparison conditions and the narrower focused inspector grammar was resolved during Phase B.

`ConditionExpression` is reused so runtime-supported classic condition forms covered by Phase B are inspectable with equivalent dependency semantics without redesigning runtime truth behavior.

## Serialization

`TemplateContract::toArray()` is deterministic and uses the frozen top-level shape:

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

No DOM/XPath handles, mutable source nodes, or process-local identities are exposed.

## Writer-authored evidence

A real LibreOffice-authored reference fixture is committed at:

```text
tests/fixtures/libreoffice-reference/odt/
TEMPLATE-AUTHORING-01B-inspection-contract.odt
```

Its provenance is documented in the fixture README.

The focused integration test:

```text
tests/Integration/TemplateAuthoring01BLibreOfficeFixtureInspectionTest.php
```

verifies body/header provenance, native objects, ownership, recognized declarative controls, scoped dependencies, readiness, determinism, and the absence of DOM/process-local identity leakage.

The executable demonstration:

```text
php tests/Fixtures/LegacySamples/sample_28_inspectTemplateContract.php
```

prints the semantic contract without rendering or mutating the fixture.

## Regression evidence

The complete automated Phase-B preflight was reported green, including:

- Slice 0–5 focused integration gates;
- `PublicSampleSmokeTest`;
- full `composer test`;
- PHP lint for `src/` and `tests/`;
- `composer validate`;
- `git diff --check develop...HEAD`;
- affected documentation build/check where available.

Manual LibreOffice visual regression was also completed successfully for representative classic/template-language, professional CV, table-layout, and frame-layout samples. All inspected outputs opened without repair warnings and rendered as expected.

## Documentation consistency review

The final overall review also corrected stale roadmap language that still used `inspect()` as the conceptual unified-inspection entry point.

The roadmap and future-development documents now reflect the accepted additive API:

```php
$contract = $template->inspectTemplate();
```

Phases A and B are marked complete, and the visual-regression preflight is marked complete with the actual Writer-authored fixture structure.

## Explicit non-goals preserved

Phase B did not introduce:

- native Writer-field execution;
- declarative Section execution;
- mapped-data `render($data)` orchestration;
- automatic form-schema policy;
- layout inference from formatting;
- contract-node mutation APIs;
- Writer layout reconstruction;
- unrelated style/page-authoring work.

## Final conclusion

TEMPLATE-AUTHORING-01B is complete.

The unified template contract is stable enough to serve as the semantic foundation for:

```text
TEMPLATE-AUTHORING-01C — Native Field Binding
TEMPLATE-AUTHORING-01D — Declarative Structural Controls
TEMPLATE-AUTHORING-01E — High-Level Render Pipeline
```

Further capability growth must build on this contract rather than silently redefining its lifecycle, provenance, scope, serialization, or compatibility semantics.

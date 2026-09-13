# TEMPLATE-AUTHORING-01B Slice 5 — Diagnostics, Readiness & Serialization Stabilization

Status: READY FOR FINAL VERIFICATION

## Scope

Slice 5 stabilizes the public Phase-B template contract after the semantic graph, source coverage, data-scope, native ownership, and declarative-candidate work completed in Slices 1–4.

It finalizes unified diagnostics, capability readiness, partial-contract behavior, contract-version-1 serialization, compatibility preflight coverage, and the public programmer-facing inspection documentation.

## Completed semantics

Slice 5 establishes:

- unified TemplateContract diagnostics with stable machine-readable codes;
- separation of diagnostic severity, semantic support state, and capability readiness;
- capability readiness for `inspection` and `dependency_mapping`;
- partial TemplateContract results when source semantics remain meaningfully inspectable;
- deterministic `contract_version = 1` serialization;
- the frozen top-level `toArray()` shape required by the change contract;
- preservation of source provenance for diagnostics from `content.xml` and page-owned `styles.xml` content;
- condition-grammar reconciliation so focused inspection no longer remains narrower than supported runtime conditions;
- a real LibreOffice-authored integration fixture and inspection runner;
- public documentation for `inspect()`, `inspectTemplateStructure()`, `inspectTemplate()`, and `TemplateContract`.

## Readiness composition

Capability readiness is no longer derived by matching diagnostic codes.

The inspector now maintains semantic capability state while interpreting source structure. Template-structure states classified as unsafe limit dependency mapping directly, and malformed native declarations limit dependency mapping at the point where semantic projection becomes incomplete.

Diagnostics are emitted alongside those semantic decisions as explanations. They are not read back as control flow for readiness.

Conceptually:

```text
semantic interpretation
    ├── capability readiness
    └── diagnostics
```

rather than:

```text
diagnostic code
    -> capability readiness
```

This closes the B1.3 requirement that diagnostics explain capability state while structured semantic state drives readiness.

## Partial contracts

Unsupported or malformed authored evidence does not automatically prevent inspection of known source semantics.

For example, a contract may report:

```text
inspection          READY
dependency_mapping  LIMITED
```

while still preserving valid bindings, native objects, provenance, coverage, and diagnostics.

Duplicate native names and recognized-but-not-executable declarative candidates do not automatically reduce dependency-mapping readiness when logical requirements remain deterministically known.

No primary global `valid()` boolean is introduced.

## Condition grammar gate

Phase B discovered and characterized an earlier divergence where runtime condition evaluation supported comparison expressions such as `gender=="female"` while focused template-structure inspection classified them as unsupported.

Slice 5 closes that architecture gate by reusing the shared `ConditionExpression` representation in template-expression projection.

The runtime truth semantics are not redesigned. Characterization tests were deliberately advanced from the old divergence to the newly aligned behavior.

## Serialization

`TemplateContract::toArray()` remains deterministic and exposes exactly:

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

`contract_version` is exactly `1`.

The serialized contract does not expose DOM/XPath handles, mutable source nodes, internal service names, or process-local object identities.

## Public documentation

Programmer-facing documentation is available at:

```text
docs/advanced/template-inspection.md
```

It documents the distinct responsibilities of:

```text
inspect()
inspectTemplateStructure()
inspectTemplate()
```

and the public TemplateContract projections, coverage, support/readiness semantics, partial contracts, source provenance, serialization, and the Phase-B inspection example.

The page is included in the Zensical documentation navigation.

## Writer-authored reference and executable sample

The captured LibreOffice reference fixture is:

```text
tests/fixtures/libreoffice-reference/odt/
TEMPLATE-AUTHORING-01B-inspection-contract.odt
```

Its provenance is documented in `tests/fixtures/libreoffice-reference/README.md`.

The focused integration test is:

```text
tests/Integration/TemplateAuthoring01BLibreOfficeFixtureInspectionTest.php
```

The executable demonstration is:

```text
samples/sample_28_inspectTemplateContract.php
```

The sample performs inspection only. It does not render, save, or mutate the authored reference document.

Observed output confirms, among other things:

- body and `styles.xml` header coverage;
- header provenance for `document_title`;
- scoped dependencies under `experience[]`;
- native Section/Table ownership;
- `RECOGNIZED` support state for native declarative candidates;
- `inspection = READY`;
- `dependency_mapping = READY`;
- no diagnostics for the valid Writer-authored fixture.

## Manual LibreOffice regression

The Phase-B visual regression preflight was performed against the representative existing samples:

- Sample 10 — classic Smarties/template-language document;
- Sample 21 — professional CV layout benchmark;
- Sample 26 — table layout regression;
- Sample 27 — frame layout regression.

All four outputs opened in LibreOffice without repair warnings and were visually reported as rendering as intended.

The visual gate complements the automated package/XML tests and is not treated as a replacement for them.

## Compatibility

The following public responsibilities remain distinct:

- `inspect()` — current mutable document inspection;
- `inspectTemplateStructure()` — focused original template-expression topology;
- `inspectTemplate()` — unified semantic contract of the original authored template.

Classic rendering and save lifecycle remain separate from inspection.

Phase B does not introduce native Writer-field execution, declarative native-control execution, mapped-data high-level rendering, or contract-node mutation APIs.

## Verification history

Before the final Slice-5 readiness/documentation refinement, the complete Phase-B automated preflight was locally reported green, including:

- Slice 0–5 focused gates;
- `PublicSampleSmokeTest`;
- full `composer test`;
- PHP lint for `src/` and `tests/`;
- `composer validate`;
- `git diff --check`.

The Writer-authored fixture integration test and manual LibreOffice visual regression were also reported green.

The final verification must re-run the affected Slice-5/fixture gates and the standard preflight after the readiness-composition and public-documentation changes.

## Exit criterion

Implementation work for Slice 5 is complete.

After the final verification remains green, update this status to:

```text
COMPLETE / GATE GREEN
```

Then proceed to the separate overall TEMPLATE-AUTHORING-01B phase review.

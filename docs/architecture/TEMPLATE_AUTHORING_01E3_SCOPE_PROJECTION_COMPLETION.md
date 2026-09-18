# TEMPLATE-AUTHORING-01E3 — E2-to-E3 Scope Projection Completion

## Status

**Bridge complete / GREEN. E3 dependency mutation is not implemented.**

## Starting point

E2-A/B/C independently resolved each TemplateContract dependency and retained its source, provenance, and hierarchical `ApplicationDataResolution` lineage. E3 required those sibling results joined under the existing template-side `DataScopeDescriptor` hierarchy before any mutation. The accepted E3 Change Contract requires this bridge and forbids re-traversing application data during execution.

## Implemented projection

- `DependencyScopeProjector` accepts only the source `TemplateContract` and its READY `ConcretePreflightResult`. It is stateless between calls, checks that each contract dependency is represented by the same READY preflight operation, and rejects inconsistent scope ownership or item lineage.
- `DependencyScopeProjection` is an immutable recursive view of one template scope. Dependency entries are keyed by template dependency ID; child collection item projections are keyed by their owning collection dependency ID and retain parent-local `itemIndex` values.
- `ProjectedDependencyValue` retains the template dependency descriptor, E2 mapping provenance, local data status, and concrete template-side value. Collection record arrays and raw application source paths are not exposed in this E3-facing value; their template-side child values are projected by dependency identity.

The projection is composed exclusively from existing `TemplateContract` dependency/scope evidence, `DependencyMappingResolution`, and the established `ApplicationDataResolution` tree. It does not accept raw application data and performs no additional resolution, mapping, fallback, or inspection.

## Characterized semantics

`DependencyScopeProjectorTest` exercises a real `TemplateContractInspector` and READY E2-C preflight. It verifies ROOT values, two parent items, sibling company/position values, explicit-over-same-name resolution, three nested collection levels, repeated child index zero under different parents, empty collections without phantom items, mutation isolation, and deterministic failure for inconsistent sibling lineage. A non-READY preflight is rejected.

The projection preserves EXPLICIT and SCOPED_SAME_NAME provenance without interpreting either again. Application record names do not become projected target identities: for example `jobs[].employer` supplies the projected template dependency `experience[].company`.

## Validation

- Scope projection + E2-A/B/C focused tests: 34 tests, 247 assertions, passed.
- All Mapping tests: 56 tests, 306 assertions, passed.
- TemplateContract / E1 projection / PublicSampleSmoke regression group: 11 tests, 352 assertions, passed.
- Full `composer test`: 903 tests, 6,082 assertions, passed; 8 existing PHPUnit metadata deprecations were reported.
- PHP lint for `src/` and `tests/`, `composer validate --no-check-publish`, strict documentation build, and `git diff --check`: passed.

## Compatibility and remaining boundary

No public/protected API, imperative behavior, `MappingResolutionResolver`, DOM state, rendering, save lifecycle, or sample output behavior was changed. E2-A/B/C responsibilities remain unchanged. No E3 consumer execution, User Field/scalar/filter/structural mutation, E4/E5 action execution, or E6 rollback was introduced.

The E2-to-E3 YELLOW prerequisite in `TEMPLATE_AUTHORING_01E3_DEPENDENCY_AUTOMATION_CHANGE_CONTRACT.md` is closed by this bridge and its tests. E3 dependency automation itself remains unimplemented and must continue to honor that contract.

## Final review

**GREEN / COMPLETE for the E2-to-E3 Dependency Scope Projection Bridge Slice only.**

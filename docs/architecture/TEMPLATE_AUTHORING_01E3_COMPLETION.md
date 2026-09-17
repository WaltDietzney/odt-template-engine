# TEMPLATE-AUTHORING-01E3 — Dependency Automation Completion

## Starting point

E1/E2 established mapping, canonical application-data resolution, concrete preflight, and the immutable `DependencyScopeProjection` bridge. E3 adds execution of READY dependency consumers without re-resolving mappings or traversing raw application data.

## Implementation

`OdtTemplate::automateDependencies()` delegates to the internal `DependencyAutomationExecutor`. It consumes a `TemplateContract` and READY `ConcretePreflightResult`; the executor projects resolved values, applies root Writer User Fields through `UserFieldBinder`, executes declarative controls through `DeclarativeConditionExecutor` and existing Section working-target/clone/removal services, then handles remaining classic scalar, filter, `nl2br`, `ul`, and `ol` consumers through `TemplateProcessor`.

Structural execution follows TemplateContract ownership and projected scope hierarchy. Nested collection item indices remain parent-local. Explicit application names end at the projection boundary; execution addresses template dependency identities. Source provenance bounds BODY and supported master-page header/footer localization. Protected `applyFilter()` and `evaluateCondition()` dispatch remains effective.

## Compatibility and limits

Legacy `assign()`, repeating assignment, `render()`, `save()`, and imperative mutation semantics are unchanged. E3 does not provide invocation-wide rollback; that remains E6. Native object actions remain E4, document capabilities remain E5, and finalization is not part of E3.

The E3 integration characterization covers User Fields, classic consumers, protected facade overrides, nested foreach/conditions, explicit application-to-template naming, empty collections, and styles.xml header/footer execution. `TemplateAuthoring01E3DependencyAutomationTest` passes 4 tests / 72 assertions. The focused Mapping, scope projection, User Field, D1-D4 structural, SECTION-03, and `PublicSampleSmokeTest` run passed 121 tests / 979 assertions; the standalone `PublicSampleSmokeTest` passed 1 test / 199 assertions.

## Preflight and review

The full `composer test` run passed 907 tests / 6,154 assertions with 8 PHPUnit deprecations. PHP lint passed across `src/` and `tests/`; `composer validate --no-check-publish`, `git diff --check`, and the strict Zensical documentation build passed. E3's mutation entry point accepts no raw application data and requires a READY concrete preflight. No sample output or local regression artifact is part of the E3 change.

**Review status:** GREEN — E3 implementation is complete for its accepted scope. E4/E5/E6 remain not started.

# TEMPLATE-AUTHORING-01C1 — User Field Semantic Analysis & Contract Projection

## Status

**IMPLEMENTED / VERIFICATION PENDING — NOT CLOSED**

This slice implements the inspection-only portion of the accepted TEMPLATE-AUTHORING-01C Change Contract.

It does not add working-document User Field mutation. That remains C2.

## Scope

C1 adds:

- bounded Writer User Field analysis across the existing Phase-B source regions;
- support for string User Field declarations and references;
- ROOT-scoped logical dependency projection;
- declaration/reference evidence projection through `BindingDescriptor`;
- cross-part logical deduplication;
- coexistence with classic ROOT bindings of the same name;
- User Field diagnostics/support-state composition;
- `native_field_binding` capability readiness;
- deterministic additive contract-v1 serialization.

C1 deliberately does not:

- add `setUserField()`;
- mutate working DOMs;
- modify `render()`, `assign()`, or `setValues()`;
- support Set/Get Variable;
- support non-string User Field binding;
- execute declarative Sections;
- implement Phase-E orchestration.

## Internal responsibility

The new `UserFieldAnalyzer` is a stateless semantic analysis service.

It distinguishes:

- authoritative declaration evidence;
- reference/display evidence;
- logical field identity;
- supported, unsupported, malformed, and ambiguous states.

`TemplateContractInspector` remains responsible for projecting those results into the public contract model.

No DOM node is exposed publicly.

## Logical identity

C1 implements:

```text
logical User Field identity = ROOT + field name
```

A User Field reference physically inside a native foreach Section remains ROOT-scoped.

Classic `{{customer}}` and a supported User Field named `customer` contribute evidence to the same logical ROOT dependency.

## Diagnostics implemented

C1 implements the accepted codes:

```text
orphan_user_field_reference
unsupported_user_field_type
ambiguous_user_field_declaration
conflicting_user_field_value
conflicting_user_field_type
```

Same-region conflicting authoritative declarations use `ambiguous_user_field_declaration`.

Cross-region/source-part value conflicts use `conflicting_user_field_value`.

Cross-region/source-part type conflicts use `conflicting_user_field_type`.

## Capability readiness

C1 adds:

```text
native_field_binding
```

with the accepted semantics:

- `NOT_APPLICABLE` when there is no relevant native User Field evidence;
- `READY` when all relevant User Fields are supported and unambiguous;
- `LIMITED` when supported fields coexist with problematic evidence;
- `BLOCKED` when relevant field evidence exists but no safe supported field is available.

Problematic field evidence also limits `dependency_mapping` while `inspection` remains available.

## Compatibility advancement

The C0 characterization tests that intentionally froze the pre-C1 native-field blind spot are advanced only where the accepted Change Contract explicitly changes behavior:

- supported string User Fields now project into the unified contract;
- Set/Get Variable remains outside the contract binding model;
- previously silent malformed/ambiguous User Field states now produce the accepted diagnostics;
- the serialized capability map now includes `native_field_binding`.

Existing classic template semantics remain unchanged.

## Verification gate

Before C1 closeout, run at minimum:

```bash
vendor/bin/phpunit \
  tests/Integration/TemplateAuthoring01C1UserFieldContractProjectionTest.php \
  --display-warnings

vendor/bin/phpunit \
  tests/Integration/TemplateAuthoring01C0FieldSemanticsCharacterizationTest.php \
  tests/Integration/TemplateAuthoring01C0UserFieldLifecycleCharacterizationTest.php \
  tests/Integration/TemplateAuthoring01C0UserFieldDiagnosticsCharacterizationTest.php \
  tests/Integration/TemplateAuthoring01C1UserFieldContractProjectionTest.php \
  --display-warnings

vendor/bin/phpunit \
  tests/Integration/TemplateAuthoring01BSlice1ContractSkeletonIntegrationTest.php \
  tests/Integration/TemplateAuthoring01BSlice2BindingDependencyProjectionTest.php \
  tests/Integration/TemplateAuthoring01BSlice3ClassicControlsDataScopesTest.php \
  tests/Integration/TemplateAuthoring01BSlice4NativeOwnershipDeclarativeCandidatesTest.php \
  tests/Integration/TemplateAuthoring01BSlice5DiagnosticsReadinessSerializationTest.php \
  --display-warnings
```

Also run PHP lint on the changed source/test files and `git diff --check develop...HEAD`.

C1 is not complete until this verification is green and the resulting contract output has been reviewed against the accepted Change Contract.

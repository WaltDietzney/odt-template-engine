# TEMPLATE-AUTHORING-01C2 — Explicit User Field Binding

## Status

**IMPLEMENTED / VERIFICATION PENDING — NOT CLOSED**

C2 implements the working-document mutation portion of the accepted Phase-C Change Contract.

## Scope

C2 adds:

- public `OdtTemplate::setUserField(string $name, string $value): void`;
- stateless `UserFieldBinder`;
- public `UserFieldBindingException`;
- closed reason vocabulary:
  - `NOT_FOUND`
  - `UNSUPPORTED_TYPE`
  - `MALFORMED`
  - `AMBIGUOUS`;
- validation-before-mutation;
- synchronized mutation of all matching supported declarations in bounded working-document regions;
- preservation of `text:user-field-get` display text;
- source-oriented `inspectTemplate()` stability;
- save/reopen, repeated binding, classic-render coexistence, and `load()` reset coverage.

C2 does not:

- bind User Fields through `assign()`, `setValues()`, or `render()`;
- update cached/materialized `text:user-field-get` text;
- support non-string User Fields;
- support Set/Get Variable;
- implement high-level mapped-data rendering;
- solve FINALIZATION-01.

## Mutation semantics

The public operation:

```php
$template->setUserField('customer', 'Maria');
```

operates on the current working `content.xml` and `styles.xml` DOMs.

It validates the entire logical field first. Only after successful validation are all authoritative string declarations mutated:

```xml
office:string-value="Maria"
```

References such as:

```xml
<text:user-field-get text:name="customer">Walter</text:user-field-get>
```

are intentionally left unchanged. Writer/LibreOffice reevaluation remains responsible for native display refresh.

## Atomic failure boundary

The operation fails without mutation when:

- the requested field name is empty;
- the field is not found;
- the field type is outside the Phase-C v1 string scope;
- the logical field is malformed;
- declaration evidence is ambiguous or conflicting.

Validation completes before mutation begins.

## Lifecycle

C2 preserves the accepted lifecycle:

```text
inspectTemplate()
    -> original authored source

setUserField()
    -> mutable working DOMs

save()
    -> persists current declaration state

load()
    -> restores original template state

new OdtTemplate(saved-output.odt)
    -> saved output becomes the new original source
```

Classic render remains independent of native field binding.

## Verification gate

Before C2 closeout, run:

```bash
vendor/bin/phpunit \
  tests/Integration/TemplateAuthoring01C2UserFieldBindingTest.php \
  --display-warnings

vendor/bin/phpunit \
  tests/Integration/TemplateAuthoring01C0FieldSemanticsCharacterizationTest.php \
  tests/Integration/TemplateAuthoring01C0UserFieldLifecycleCharacterizationTest.php \
  tests/Integration/TemplateAuthoring01C0UserFieldDiagnosticsCharacterizationTest.php \
  tests/Integration/TemplateAuthoring01C1UserFieldContractProjectionTest.php \
  tests/Integration/TemplateAuthoring01C2UserFieldBindingTest.php \
  --display-warnings

vendor/bin/phpunit \
  tests/Integration/TemplateAuthoring01BSlice1ContractSkeletonIntegrationTest.php \
  tests/Integration/TemplateAuthoring01BSlice2BindingDependencyProjectionTest.php \
  tests/Integration/TemplateAuthoring01BSlice3ClassicControlsDataScopesTest.php \
  tests/Integration/TemplateAuthoring01BSlice4NativeOwnershipDeclarativeCandidatesTest.php \
  tests/Integration/TemplateAuthoring01BSlice5DiagnosticsReadinessSerializationTest.php \
  --display-warnings
```

Also run:

```bash
php -l src/Template/UserFieldBindingException.php
php -l src/Template/UserFieldBinder.php
php -l src/OdtTemplate.php
php -l tests/Integration/TemplateAuthoring01C2UserFieldBindingTest.php

git diff --check develop...HEAD
```

C2 is not closed until the verification gate is green and the implementation is reviewed against Sections 14–18 and 20 of the accepted Change Contract.

# TEMPLATE-AUTHORING-01C3 — Writer Integration, Documentation & Final Closeout

## Status

**COMPLETE / GATE GREEN**

C3 is the final Phase-C slice.

Its purpose is to validate the completed C1/C2 behavior against real LibreOffice-authored ODT fixtures, publish the public API/documentation surface, run the full automated preflight, and finish with manual Writer regression.

## Implemented C3 assets

C3 adds:

- real-fixture integration gate:
  `tests/Integration/TemplateAuthoring01C3LibreOfficeUserFieldIntegrationTest.php`;
- public/manual sample:
  `tests/Fixtures/LegacySamples/sample_29_userFieldBinding.php`;
- public User Field documentation in `docs/advanced/template-inspection.md`;
- README feature/API exposure.

No new Phase-C production semantics are introduced by C3.

## Required LibreOffice-authored fixtures

Two already researched local fixtures must be promoted into the committed LibreOffice reference fixture set.

### Fixture 1 — body/header logical User Field

Source research file:

```text
research/02b-user-field-header.odt
```

Committed fixture path:

```text
tests/fixtures/libreoffice-reference/odt/
TEMPLATE-AUTHORING-01C-user-field-header.odt
```

It must contain:

- string User Field `customer`;
- repeated body references;
- same logical User Field in page-owned header content;
- Writer-authored declarations in the relevant source parts.

### Fixture 2 — foreach containment / ROOT scope

Source research file:

```text
research/08-foreach-section-user-field.odt
```

Committed fixture path:

```text
tests/fixtures/libreoffice-reference/odt/
TEMPLATE-AUTHORING-01C-foreach-user-field.odt
```

It must retain:

- native Section `#foreach:experience`;
- classic item-local placeholders such as `{{position}}` and `{{company}}`;
- native string User Field `company_globa` inside that Section;
- the Writer-authored ROOT declaration.

The historical shortened field name `company_globa` is part of the fixture evidence and must not be silently renamed during fixture promotion.

## C3 automated integration contract

The real-fixture test proves:

1. body/header declarations inspect as one ROOT `customer` dependency;
2. `native_field_binding` is READY;
3. binding `customer -> Maria` synchronizes authoritative declarations in `content.xml` and `styles.xml`;
4. cached get display text is not materialized by the engine;
5. `inspectTemplate()` remains source-stable;
6. saved output can be reopened as a valid supported template source;
7. foreach fixture projects `experience[].position` / `experience[].company`;
8. native `company_globa` remains ROOT-scoped;
9. Section instantiation creates two item-local classic values while cloning references to the same native User Field identity.

## Public sample

`sample_29_userFieldBinding.php` binds:

```text
customer = Maria
```

against the real body/header fixture and writes:

```text
tests/Fixtures/LegacySamples/output/output_29_userFieldBinding.odt
```

The generated output is a local regression artifact unless explicitly selected for commit. Existing project rules for `samples/output/*.odt` remain unchanged.

## Manual LibreOffice regression

After the automated gate is green:

1. run `php tests/Fixtures/LegacySamples/sample_29_userFieldBinding.php`;
2. open `tests/Fixtures/LegacySamples/output/output_29_userFieldBinding.odt` in LibreOffice Writer;
3. confirm there is no repair warning;
4. confirm body User Field references display `Maria`;
5. confirm header User Field reference displays `Maria`;
6. confirm the document remains editable and the User Field remains a native Writer field;
7. run/create the foreach result from the committed companion fixture and confirm:
   - first classic company = `Firma A`;
   - second classic company = `Firma B`;
   - native field value remains the same document-global value in both Section instances.

The pre-existing C-R3 screenshot/observation is research evidence; C3 requires the committed-fixture regression to be repeatable from repository state.

## Automated preflight

Both binary fixtures are now committed and the focused real-fixture integration gate is green. Continue with:

```bash
vendor/bin/phpunit \
  tests/Integration/TemplateAuthoring01C3LibreOfficeUserFieldIntegrationTest.php \
  --display-warnings

vendor/bin/phpunit \
  tests/Integration/TemplateAuthoring01C0FieldSemanticsCharacterizationTest.php \
  tests/Integration/TemplateAuthoring01C0UserFieldLifecycleCharacterizationTest.php \
  tests/Integration/TemplateAuthoring01C0UserFieldDiagnosticsCharacterizationTest.php \
  tests/Integration/TemplateAuthoring01C1UserFieldContractProjectionTest.php \
  tests/Integration/TemplateAuthoring01C2UserFieldBindingTest.php \
  tests/Integration/TemplateAuthoring01C3LibreOfficeUserFieldIntegrationTest.php \
  --display-warnings

vendor/bin/phpunit \
  tests/Integration/TemplateAuthoring01BSlice1ContractSkeletonIntegrationTest.php \
  tests/Integration/TemplateAuthoring01BSlice2BindingDependencyProjectionTest.php \
  tests/Integration/TemplateAuthoring01BSlice3ClassicControlsDataScopesTest.php \
  tests/Integration/TemplateAuthoring01BSlice4NativeOwnershipDeclarativeCandidatesTest.php \
  tests/Integration/TemplateAuthoring01BSlice5DiagnosticsReadinessSerializationTest.php \
  --display-warnings

vendor/bin/phpunit tests/Integration/PublicSampleSmokeTest.php --display-warnings

composer test

php -l src/Template/UserFieldAnalyzer.php
php -l src/Template/UserFieldBinder.php
php -l src/Template/UserFieldBindingException.php
php -l src/Template/TemplateContractInspector.php
php -l src/OdtTemplate.php
php -l tests/Integration/TemplateAuthoring01C3LibreOfficeUserFieldIntegrationTest.php
php -l tests/Fixtures/LegacySamples/sample_29_userFieldBinding.php

git diff --check develop...HEAD
```

Also run `composer validate` if Composer metadata has changed; C3 currently does not require such a change.

## C3 stop conditions

Do not close Phase C if:

- the promoted Writer fixtures differ semantically from the researched files;
- Writer shows a repair warning;
- body and header do not reevaluate the same bound value;
- foreach cloning localizes or duplicates authoritative User Field state unexpectedly;
- full `composer test` reveals compatibility regressions;
- public docs claim broader field support than the accepted string User Field scope.

## Phase-C completion criterion

TEMPLATE-AUTHORING-01C may be closed only after:

- the two real LibreOffice fixtures are committed;
- C3 focused integration is green;
- combined C0–C3 chain is green;
- Phase-B compatibility chain is green;
- PublicSampleSmokeTest is green;
- full `composer test` is green;
- lint and diff checks are green;
- manual LibreOffice body/header binding regression is green;
- manual LibreOffice foreach/ROOT-scope regression is green;
- final review confirms no deviation from the accepted Change Contract.

Only then may the Phase-C status be changed to COMPLETE.


## Final verification record

The final C3 verification was reported green on 2026-09-14:

- focused C3 real-fixture integration;
- combined C0–C3 integration chain;
- Phase-B Slice 1–5 compatibility chain;
- `PublicSampleSmokeTest`;
- full `composer test`: 804 tests, 5637 assertions, no failures or errors;
- PHP lint for the Phase-C source/test/sample files;
- `git diff --check`;
- manual LibreOffice body/header regression;
- manual LibreOffice foreach/ROOT-scope regression.

The full PHPUnit run reports eight test-runner deprecations for legacy
doc-comment metadata. They occur in pre-existing Bookmark/D5G/FrameLayout test
code and are unrelated to Phase C; they are therefore not a Phase-C closeout
blocker.

Manual Writer verification confirmed:

- generated ODT files open without repair warning;
- body and header User Field references reevaluate to the bound value;
- the User Field remains a native editable Writer field;
- foreach item-local classic values remain distinct;
- the native User Field remains document-global across cloned Section
  instances.

No C3 stop condition remains active.

## C3 closeout finding

C3 introduces no additional production semantics beyond the accepted C1/C2
implementation. The committed Writer fixtures, public sample, documentation,
automated preflight, and manual LibreOffice regression confirm the Phase-C
contract on real authored documents.

C3 is complete.

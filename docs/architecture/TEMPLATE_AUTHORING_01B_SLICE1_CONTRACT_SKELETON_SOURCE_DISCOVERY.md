# TEMPLATE-AUTHORING-01B Slice 1 — Contract Skeleton & Source-Region Discovery

Status: COMPLETE / GATE GREEN

## Purpose

Introduce the first production implementation of the source-oriented TemplateContract without implementing advanced dependency or control execution semantics.

This slice is governed by:

```text
TEMPLATE_AUTHORING_01B_UNIFIED_TEMPLATE_INSPECTION_CHANGE_CONTRACT.md
```

## Production scope

Slice 1 introduces:

```php
$template->inspectTemplate(): TemplateContract
```

and the immutable contract skeleton required by the accepted Change Contract.

Implemented source coverage:

```text
content.xml
    -> office:body / office:text

styles.xml
    -> office:master-styles
       -> style:master-page
          -> header/footer content carriers
```

Style-definition trees are not interpreted as template content.

## Contract version

The new contract serializes with:

```text
contract_version = 1
```

and the accepted top-level keys:

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

## Slice-1 semantics

### Binding evidence

Slice 1 projects existing visible binding evidence for:

```text
SCALAR
FILTERED_SCALAR
SPECIAL
```

from supported source regions.

This records authored binding sites and provenance only.

Scoped logical dependency projection is intentionally deferred to Slice 2.

Classic controls are not yet projected into the contract control graph.

### Native object evidence

Slice 1 projects source-native evidence for:

```text
Section
Bookmark
Table
Frame
```

from supported body and master-page-owned content regions.

Duplicate names are preserved as distinct source objects through distinct evidence identities.

Duplicate/ambiguity diagnostics are deferred to the later diagnostics slice.

### Provenance

Each projected item receives immutable source provenance containing the bounded Slice-1 fields:

- evidence identity;
- source part;
- region kind;
- region owner;
- carrier kind;
- representation kind;
- source order;
- physical scope where available;
- native owner chain where available.

Evidence IDs are deterministic for the same unchanged source and inspection algorithm and do not expose DOM identity.

### Coverage

The contract reports inspected body/master-page regions and explicitly reports bounded package areas outside Phase-B source inspection.

### Capabilities

Slice 1 reports the truthful minimal readiness baseline:

```text
inspection         READY
dependency_mapping BLOCKED
```

Dependency mapping is not claimed before Slice 2.

## Architecture boundaries preserved

Slice 1 does not change the meaning of:

```php
$template->inspect();
$template->inspectTemplateStructure();

$template->section(...);
$template->bookmark(...);
$template->table(...);
$template->frame(...);

$template->render();
$template->save(...);
```

inspectTemplate() reads fresh DOMs from the original archive using OdtPackage::sourceDom().

It does not inspect the mutable working DOM.

## Added production types

The current bounded implementation introduces:

```text
TemplateContract
TemplateContractInspector
TemplateContractCoverage
TemplateContractCapabilities
SourceProvenance
BindingDescriptor
NativeObjectDescriptor
```

These are deliberately small immutable/read-only values and a stateless source inspector.

No generic graph framework or mutable inspection context is introduced.

## Automated gate

Primary Slice-1 integration test:

```text
tests/Integration/TemplateAuthoring01BSlice1ContractSkeletonIntegrationTest.php
```

The gate checks:

1. inspectTemplate() returns TemplateContract;
2. contract_version and top-level serialization shape;
3. BODY + master-page header source-region coverage;
4. visible source binding discovery in content.xml and header content;
5. style-definition attributes are not treated as template expressions;
6. Section/Bookmark/Table/Frame source discovery in body and header;
7. same native names in different source regions remain distinct evidence;
8. evidence identities are unique and deterministic;
9. repeated inspectTemplate() serialization is deterministic;
10. render/save mutations of the working document do not change the source contract.

Slice 0 remains part of the compatibility gate.

## Deferred to Slice 2+

Slice 1 intentionally does not implement:

- logical dependency descriptors;
- dependency deduplication;
- ROOT dependency projection;
- classic control graph;
- foreach data scopes;
- condition dependency parsing;
- declarative Section candidate recognition;
- unified contract diagnostics;
- duplicate-name execution policy;
- Phase-C native-field binding semantics;
- Phase-D declarative execution.

## Local gate

Run:

```bash
vendor/bin/phpunit \
  tests/Integration/TemplateAuthoring01BSlice0CompatibilityGateTest.php \
  tests/Integration/TemplateAuthoring01BSlice1ContractSkeletonIntegrationTest.php \
  --display-warnings

php -l src/Template/TemplateContract.php
php -l src/Template/TemplateContractInspector.php
php -l src/Template/TemplateContractCoverage.php
php -l src/Template/TemplateContractCapabilities.php
php -l src/Template/SourceProvenance.php
php -l src/Template/BindingDescriptor.php
php -l src/Template/NativeObjectDescriptor.php
php -l src/OdtTemplate.php

git diff --check
```

## Exit criterion

Slice 1 may close only when:

- Slice 0 and Slice 1 tests are green;
- no new warnings are emitted;
- syntax checks are clean;
- git diff --check is clean;
- existing inspection/render semantics remain unchanged.

## Closeout

Slice 1 is complete.

The local closeout gate was reported green for:

- Slice 0 compatibility characterization;
- Slice 1 contract/source-discovery integration coverage;
- direct working-DOM mutation stability;
- syntax checks for the touched production/test files;
- `git diff --check develop...HEAD`.

The completed slice proves:

- `inspectTemplate()` is an additive public facade returning `TemplateContract`;
- contract version 1 and its top-level serialization shape are established;
- bounded authored source discovery covers body content in `content.xml` and page-owned header/footer content in `styles.xml`;
- source provenance and deterministic evidence identity foundations are present;
- initial visible binding and native-object evidence are projected without claiming scoped dependency/control semantics;
- `inspectTemplate()` remains semantically tied to the original authored source across render, save, and direct working-document mutation;
- existing `inspect()` and `inspectTemplateStructure()` lifecycle semantics remain unchanged;
- no Phase-C, Phase-D, or Phase-E execution semantics are introduced.

No Slice-2 dependency graph or Slice-3 control/data-scope semantics are claimed by this closeout.

After closeout, proceed to:

```text
TEMPLATE-AUTHORING-01B Slice 2 — Binding & Dependency Projection
```

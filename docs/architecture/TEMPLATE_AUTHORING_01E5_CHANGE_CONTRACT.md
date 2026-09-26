# TEMPLATE-AUTHORING-01E5 — Document Capability Automation Change Contract

## Status

**Accepted architecture contract / implementation-ready**

This Change Contract defines only TEMPLATE-AUTHORING-01E5 — Document Capability Automation.

E5 implements mutation for the Document Capability Target family already modeled and preflighted in E1/E2. The only approved Phase-E document capability is metadata.

This contract narrows `TEMPLATE_AUTHORING_01E_CHANGE_CONTRACT.md` for E5 and builds on the completed `METADATA-SEMANTICS-01`. It does not extend the approved metadata field set or redefine metadata semantics.

## 1. Purpose

E5 closes the third Phase-E target family:

```text
Automation Target
├── Dependency Target          ← E3
├── Native Object Action       ← E4
└── Document Capability        ← E5
```

A fully resolved and READY-preflighted Document Capability operation can be applied to the current Working Document.

Conceptually:

```text
application data
    person.name
        ↓
DocumentCapabilityMapping
    metadata.creator
        ↓
ConcretePreflightOperation READY
        ↓
E5 execution
        ↓
MetadataManager
        ↓
meta.xml
```

E5 is execution, not repeated resolution or preflight.

## 2. Semantic authorities

### 2.1 Mapping and resolution

`DocumentCapabilityMapping` and `DocumentCapabilityResolution` remain authoritative for the resolved association between an application source and a Document Capability.

E5 does not resolve application paths again.

### 2.2 Capability

`EngineCapabilityCatalog` remains authoritative for the Document Capability targets supported by Phase E and their payload semantics.

E5 does not introduce additional metadata fields or capability groups.

### 2.3 Concrete preflight

`ConcretePreflightResult` and its `ConcretePreflightOperation` entries are the immediate execution evidence.

E5 accepts only a fully READY concrete preflight.

### 2.4 Mutation

`MetadataManager` remains the sole document-local mutation owner for metadata.

An E5 executor must not mutate `meta.xml` directly through DOM/XPath.

## 3. Supported Document Capability

E5 supports exactly:

```text
group = metadata
```

with the canonical targets established by `METADATA-SEMANTICS-01`:

```text
title
subject
description
keywords
initial_creator
creator
language
creation_date
date
editing_cycles
editing_duration
generator
coverage
```

Payload semantics remain target-specific:

```text
STRING
LIST<STRING>
DATETIME
LANGUAGE
NON_NEGATIVE_INTEGER
DURATION
```

The legacy aliases `author` and `initial_author` remain part of imperative `MetadataManager` compatibility. They are not additional Phase-E Document Capability targets.

## 4. Execution input

The E5 executor receives neither raw application data nor a mapping definition for reinterpretation.

It operates on `ConcretePreflightResult` and the contained `DocumentCapabilityResolution` / `ConcretePreflightOperation` evidence.

Only operations in the `document_capability` target family are executed.

Dependency and Native Object Action operations in the same preflight do not belong to E5 and are ignored by the E5 executor.

## 5. READY execution boundary

E5 may execute only when the supplied `ConcretePreflightResult` is fully READY.

A preflight that is not fully READY causes no metadata mutation.

Every selected Document Capability operation must also be internally consistent:

- `targetFamily()` is `document_capability`;
- status is `READY`;
- resolution is a `DocumentCapabilityResolution`;
- the resolution belongs to the supplied mapping-resolution result;
- provenance is `EXPLICIT`;
- group and target agree with the operation identity;
- capability and payload semantics agree with the preflight evidence;
- the resolution contains a concrete `PRESENT` value and no collection-item resolution that is semantically invalid for the metadata target.

Contradictions between READY evidence and execution input are execution-integrity failures. They must not be silently reinterpreted.

## 6. No second validation or resolution layer

E5 must not duplicate E2's semantic resolution and payload validation.

In particular, E5 must not:

- resolve `ApplicationPath` again;
- reinterpret `MappingDefinition`;
- run `MetadataPayloadValidator` as a second preflight;
- normalize missing values;
- repair invalid values;
- reinterpret legacy aliases as Phase-E targets.

The executor may perform defensive consistency checks so that contradictory or manipulated READY input cannot produce a mutation different from the one preflighted.

This is execution integrity, not a second preflight.

## 7. Delegation to MetadataManager

After successful execution-integrity checks, actual mutation is delegated exclusively to `MetadataManager`.

Conceptually:

```text
metadata.creator + "Anna Example"
    ↓
MetadataManager::set([
    'creator' => 'Anna Example'
])
```

Multiple selected metadata operations may be delegated together where appropriate.

E5 does not own or reproduce ODF metadata representation rules.

The following remain `MetadataManager` responsibilities:

- `dc:*` versus `meta:*`;
- repeated `meta:keyword` elements;
- replacement of the complete keyword collection;
- namespace handling;
- concrete `meta.xml` mutation.

## 8. Multiple metadata operations and conflicts

A READY preflight may contain multiple different metadata targets. They may be applied together within one E5 invocation.

The same canonical metadata target must not receive competing mutations in one concrete automation invocation.

If the existing mapping/preflight layers do not already exclude such duplicates, E5 must detect them before its first mutation.

Execution order must not act as conflict resolution.

In particular:

```text
last mapping wins
```

is not an accepted Phase-E semantic.

## 9. Mutation boundary and E6

E5 mutates `meta.xml`.

E5 does not implement the Phase-E-wide atomic transaction. Outer automation atomicity belongs to TEMPLATE-AUTHORING-01E6.

E5 must therefore remain composable with the future E6 rollback boundary covering E3, E4, and E5 mutations. It must not introduce a competing global snapshot/rollback lifecycle.

All predictable E5-specific execution-integrity failures must nevertheless be detected before the first E5 mutation.

The boundary is:

```text
E5:
prepare complete execution plan
→ validate execution integrity
→ mutate metadata

E6:
atomic orchestration across E3 + E4 + E5
```

## 10. Public integration

Analogous to the existing E3/E4 facades, `OdtTemplate` may expose a narrow entry point for Document Capability Automation.

Conceptually:

```php
$template->automateDocumentCapabilities($preflight);
```

The concrete name remains an implementation decision provided it stays consistent with the established E3/E4 facade style.

The entry point must not:

- call `render()`;
- save;
- finalize;
- execute dependency automation;
- execute native-object actions;
- resolve application data again.

It executes only E5-owned READY Document Capability operations.

Because metadata does not require TemplateContract-based target localization, no `TemplateContract` parameter should be introduced merely for symmetry with E3/E4 unless implementation evidence demonstrates a real semantic need.

## 11. Imperative compatibility

Existing imperative behavior remains unchanged:

```php
$template->setMeta(...);
$template->getMeta();
```

In particular:

- `author` remains an imperative alias for `creator`;
- `initial_author` remains an imperative alias for `initial_creator`;
- unknown imperative metadata keys remain ignored;
- a legacy keyword string remains one keyword;
- Phase-E strictness must not globally tighten `MetadataManager::set()`.

Phase-E validation and imperative compatibility intentionally remain separate layers.

## 12. Non-goals

E5 does not implement:

- additional metadata fields;
- arbitrary/custom metadata;
- user-defined properties;
- a general Dublin Core extension;
- page layout;
- styles;
- document statistics;
- automatic generator/timestamp updates;
- native-object actions;
- dependency automation;
- save or finalization;
- a general document-service dispatcher architecture;
- reflection-based method invocation;
- arbitrary PHP method mapping;
- E6 atomicity;
- a universal `DocumentCapabilityExecutor` that automatically dispatches arbitrary future document services.

Metadata proves the third target family. E5 does not turn it into a generic service-invocation platform.

## 13. Expected implementation shape

The preferred minimal architecture is:

```text
OdtTemplate facade
       ↓
DocumentCapabilityAutomationExecutor
       ↓
MetadataManager
       ↓
OdtDocumentContext::metaDom()
```

The executor should remain small and preferably stateless.

It owns no independent metadata semantics, application data, or persistent document state.

A registry, handler framework, or generic capability dispatcher is not justified by E5.

## 14. Tests

E5 must at minimum characterize or test:

1. one READY metadata target is applied correctly;
2. multiple distinct READY metadata targets are applied together;
3. keywords preserve the collection semantics established by `METADATA-SEMANTICS-01`;
4. Creator and Initial Creator use canonical Phase-E target names;
5. every approved payload class reaches `MetadataManager` unchanged;
6. a non-READY preflight causes no mutation;
7. dependency/native operations are not executed by the E5 executor;
8. contradictory operation/resolution evidence is rejected before mutation;
9. competing mutations for the same metadata target are not resolved by ordering;
10. existing `setMeta()` / `getMeta()` behavior remains compatible;
11. save/reload preserves automated metadata;
12. `content.xml` and `styles.xml` remain unchanged under metadata-only E5 automation;
13. E5 triggers neither save nor render/finalization.

## 15. Preflight and completion

After implementation, verification includes:

- focused E5 tests;
- relevant metadata/mapping/preflight regression tests;
- `PublicSampleSmokeTest`;
- full `composer test`;
- PHP lint for `src/` and `tests/`;
- `composer validate`;
- `git diff --check`;
- strict documentation build when documentation is affected.

Because E5 mutates native ODT metadata, completion also requires a manual LibreOffice regression:

```text
Phase-E metadata automation
→ save
→ open in LibreOffice
→ inspect File Properties
→ save
→ close
→ reopen
→ inspect Properties again
```

At minimum, Creator, Initial Creator, Keywords, and representative typed metadata fields must be checked.

`samples/output/*.odt` remain local regression artifacts and must not be committed.

## 16. Completion criterion

E5 is complete when:

```text
READY Document Capability Resolution
→ bounded metadata execution
→ MetadataManager
→ correct meta.xml mutation
→ save/reload/LibreOffice stable
```

works without introducing:

```text
new metadata semantics
second resolution
second preflight
generic document-service dispatcher
save/finalization
E6 rollback/orchestration
```

The intended E5 implementation is deliberately small: READY evidence → execution-integrity checks → `MetadataManager`.

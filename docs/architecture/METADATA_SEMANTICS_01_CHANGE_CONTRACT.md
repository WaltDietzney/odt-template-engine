# METADATA-SEMANTICS-01 — Metadata Semantics Cleanup

**Status:** ACCEPTED / implementation-ready  
**Scope:** Metadata semantics cleanup before TEMPLATE-AUTHORING-01E5  
**Base:** TEMPLATE-AUTHORING-01E4 complete / green

## 1. Purpose

METADATA-SEMANTICS-01 corrects the metadata model before Phase E document-capability automation is implemented.

The slice aligns the existing metadata abstraction with the ODF metadata structure and the LibreOffice-relevant semantics established by repository inspection and the ODF 1.1 / ODF 1.2 specifications, while preserving existing public compatibility where required.

This is a prerequisite cleanup for TEMPLATE-AUTHORING-01E5. It is not part of E5 execution semantics.

## 2. Authorities and boundaries

The existing `MetadataManager` remains the document-local mutation owner for supported metadata.

`OdtTemplate::setMeta()` and `OdtTemplate::getMeta()` remain the public imperative metadata facade.

Phase E must later reuse this metadata owner rather than introduce a second metadata implementation.

This slice may correct metadata representation, canonical naming, validation semantics, tests, samples, documentation, and the Phase-E metadata capability/preflight model where the previous assumptions are contradicted by the established metadata semantics.

It must not introduce a generic metadata DSL, arbitrary XML metadata authoring, document finalization, or Phase-E execution.

## 3. Canonical metadata model

The canonical metadata identities are:

| Canonical key | ODF element | Semantic type |
| --- | --- | --- |
| `title` | `dc:title` | string |
| `subject` | `dc:subject` | string |
| `description` | `dc:description` | string |
| `keywords` | repeated `meta:keyword` | list<string> |
| `initial_creator` | `meta:initial-creator` | string |
| `creator` | `dc:creator` | string |
| `language` | `dc:language` | language |
| `creation_date` | `meta:creation-date` | dateTime |
| `date` | `dc:date` | dateTime |
| `editing_cycles` | `meta:editing-cycles` | nonNegativeInteger |
| `editing_duration` | `meta:editing-duration` | duration |
| `generator` | `meta:generator` | string |
| `coverage` | `dc:coverage` | LibreOffice / Dublin Core extended string metadata |

The semantic type is part of the target semantics. Metadata is not a generic PHP-scalar target family.

## 4. Creator naming

`dc:creator` represents the creator associated with the current/last-modified document state, while `meta:initial-creator` represents the original creator.

The canonical engine-facing names therefore become:

- `creator`
- `initial_creator`

The existing public input names:

- `author`
- `initial_author`

are compatibility aliases and must continue to be accepted by the imperative `setMeta()` path.

New documentation, samples, and Phase-E capability identities use the canonical names.

### 4.1 Open compatibility detail for `getMeta()`

The exact compatibility representation returned by `getMeta()` for the legacy `author` and `initial_author` keys is intentionally not predetermined by this contract.

Before changing returned keys, characterization tests must establish the existing public behavior and its compatibility implications.

The implementation must then choose the smallest compatibility-preserving representation consistent with canonical `creator` / `initial_creator` semantics. No silent removal of an established returned key is permitted.

The chosen behavior must be recorded in the completion document.

## 5. Keyword semantics

ODF represents keywords as repeatable `meta:keyword` elements. The canonical PHP representation is therefore:

```php
[
    'keywords' => [
        'finance',
        'report',
        '2026',
    ],
]
```

which represents:

```xml
<meta:keyword>finance</meta:keyword>
<meta:keyword>report</meta:keyword>
<meta:keyword>2026</meta:keyword>
```

`getMeta()` must expose all existing keyword elements through the canonical keyword collection.

When a keyword collection is set, it replaces the complete existing `meta:keyword` collection. Unrelated metadata remains unchanged.

### 5.1 Legacy string compatibility

Existing imperative calls that pass a string to `keywords` remain supported.

A string represents exactly one keyword value. The engine must not infer application structure by splitting strings on commas, semicolons, whitespace, or other delimiters.

For example:

```php
['keywords' => 'finance,report,2026']
```

represents one keyword whose value is `finance,report,2026`.

New samples and documentation use the canonical collection representation.

## 6. Typed metadata semantics

The canonical metadata model follows the target's ODF semantics rather than generic PHP scalar coercion.

The following categories apply:

- ordinary textual metadata: string;
- `keywords`: list of strings;
- `creation_date` and `date`: ODF/XML Schema dateTime lexical semantics;
- `language`: ODF language lexical semantics;
- `editing_cycles`: non-negative integer semantics;
- `editing_duration`: ODF/XML Schema duration lexical semantics;
- `coverage`: string.

Boolean and floating-point values are not generically valid metadata payloads merely because PHP can cast them to strings.

No new general-purpose value-object hierarchy is required by this slice. Validation should remain bounded to the supported metadata targets and their lexical/value constraints.

Existing permissive imperative behavior that is already public must be characterized before it is tightened. Phase-E capability/preflight semantics, which are not yet released as E5 execution behavior, must be corrected to the canonical target semantics.

## 7. Coverage

`coverage` remains supported.

It must not be documented as one of the predefined ODF 1.1 / ODF 1.2 metadata elements established for `office:meta`.

It is treated as LibreOffice/Dublin-Core-compatible extended metadata and must survive the engine's save/reload lifecycle.

This decision does not automatically add other Dublin Core or LibreOffice extended fields.

## 8. Existing extended metadata is not expanded

The presence or LibreOffice support of additional metadata such as contributor, identifier, publisher, relation, rights, source, or type does not make those fields part of this slice.

Adding further metadata capabilities requires a separate feature/architecture decision.

METADATA-SEMANTICS-01 corrects the currently supported metadata model; it does not turn `MetadataManager` into an arbitrary Dublin Core API.

## 9. Mutation semantics

`MetadataManager` remains authoritative for mutation of `meta.xml`.

For singular metadata fields, setting a mapped value updates the corresponding field while preserving unrelated metadata.

For `keywords`, setting the canonical collection replaces the complete keyword collection while preserving unrelated metadata.

Mutation applies to the current Working Document. It must not reload metadata from the original template or discard prior document-local mutations.

Saving/finalization remains outside this slice.

## 10. Phase-E correction

The existing Phase-E metadata capability catalog and concrete preflight currently reflect an overly broad generic-scalar assumption.

Where required, they must be corrected so that future E5 execution receives metadata operations whose payload semantics match this contract.

In particular:

- new Phase-E mappings use canonical `creator` and `initial_creator` identities;
- metadata payload compatibility is target-specific;
- `keywords` has collection semantics;
- bool/float are not generically accepted;
- `editing_cycles` follows non-negative-integer semantics;
- date/time, language, and duration targets follow their bounded lexical semantics;
- `coverage` remains an explicitly supported capability with its documented extended-metadata status.

This correction must not introduce E5 mutation execution.

## 11. Compatibility

The cleanup must preserve existing public metadata behavior unless this contract explicitly changes its canonical representation and a compatibility path is retained.

In particular:

- `setMeta(['author' => ...])` remains accepted;
- `setMeta(['initial_author' => ...])` remains accepted;
- string input for `keywords` remains accepted as one keyword;
- unknown imperative metadata keys continue to follow the existing compatibility behavior;
- no existing public metadata method is removed;
- save/reload lifecycle remains supported.

The distinction between permissive legacy imperative input and stricter canonical/Phase-E semantics is intentional where required for backward compatibility.

## 12. Characterization and tests

Before behavioral changes, existing metadata behavior must be characterized where compatibility is relevant.

The slice must cover at least:

- existing `author` / `initial_author` input and output behavior;
- existing string keyword behavior;
- reading multiple existing `meta:keyword` elements;
- writing one canonical keyword;
- writing multiple canonical keywords;
- replacement of an existing keyword collection;
- string keyword compatibility without delimiter splitting;
- canonical `creator` / `initial_creator` save/reload behavior;
- legacy creator aliases;
- valid and invalid dateTime payloads;
- valid and invalid language payloads;
- non-negative `editing_cycles`;
- valid and invalid duration payloads;
- `coverage` save/reload preservation;
- preservation of unrelated metadata;
- unknown-key imperative compatibility;
- corrected Phase-E metadata capability/preflight behavior.

Tests must inspect actual `meta.xml` structure where structural semantics matter, rather than relying only on returned PHP values.

## 13. Samples and documentation

Metadata documentation and affected public samples must be updated to teach the canonical model.

In particular:

- new examples use `creator` / `initial_creator`;
- keyword examples use lists when multiple keywords are intended;
- the distinction between canonical names and compatibility aliases is documented;
- ODF-specific dateTime, language, editing-cycle, and duration semantics are documented accurately;
- `coverage` is described as supported extended metadata rather than a predefined ODF 1.1/1.2 field.

Sample output ODT files under `samples/output/` are not to be modified, regenerated, restored, deleted, or committed as part of this slice.

## 14. LibreOffice regression

Because this slice changes native `meta.xml` semantics, automated XML tests are necessary but not sufficient.

A representative generated ODT must be manually checked in LibreOffice for:

- opening without repair/error;
- creator/initial-creator behavior where exposed by LibreOffice;
- multiple keywords;
- coverage preservation where exposed/supported;
- save → close → reopen stability.

Any LibreOffice rewriting of these metadata fields that materially affects the engine semantics must be recorded rather than silently normalized away.

## 15. Non-goals

METADATA-SEMANTICS-01 does not introduce:

- E5 document-capability execution;
- arbitrary/custom metadata authoring;
- a generic Dublin Core API;
- additional extended metadata fields;
- a metadata expression or transformation language;
- PHP date/time or duration value-object APIs;
- automatic keyword delimiter parsing;
- automatic editing-cycle lifecycle management;
- automatic metadata updates during save;
- document finalization/export behavior;
- Phase-E invocation-wide rollback;
- page-layout, style, content, or native-object changes.

## 16. Preflight

Before completion, run the relevant focused metadata and mapping/preflight tests plus the normal project preflight:

- focused metadata tests;
- affected Phase-E mapping/preflight tests;
- relevant integration tests;
- `PublicSampleSmokeTest`;
- full `composer test`;
- PHP lint for `src/` and `tests/`;
- `composer validate` where applicable;
- `git diff --check`;
- strict documentation build where relevant;
- manual LibreOffice regression described above.

No unrelated files or local sample-output artifacts may be committed.

## 17. Completion criteria

METADATA-SEMANTICS-01 is complete when:

1. canonical creator naming is implemented with required compatibility;
2. keyword collection semantics correctly represent repeated `meta:keyword` elements;
3. target-specific metadata semantics replace the generic scalar assumption for future Phase E execution;
4. coverage is accurately supported and documented;
5. existing public compatibility is characterized and preserved as required;
6. tests cover XML structure and save/reload behavior;
7. samples and metadata documentation teach the corrected model;
8. automated preflight is green;
9. manual LibreOffice save/close/reopen regression is green;
10. a completion record documents the final compatibility decisions, including the resolved `getMeta()` alias behavior.

After this slice is complete, TEMPLATE-AUTHORING-01E5 may proceed against the corrected metadata model.

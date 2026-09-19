# TEMPLATE-AUTHORING-01F Change Contract

**Status:** Proposed — requires review before implementation  
**Milestone:** TEMPLATE-AUTHORING-01F — Authoring Documentation & Samples  
**Target:** Version 1.0  
**Base:** Accepted Release 1.0 Planning Reconciliation and F0 Public Surface Audit

## 1. Objective

Phase F turns the completed version-1.0 authoring architecture into one coherent public product surface.

It must make the engine understandable and attractive to:

- PHP developers evaluating the library;
- LibreOffice template authors;
- application developers integrating structured document generation;
- coding/AI agents selecting supported APIs and workflows.

Phase F is successful when a new consumer can determine:

1. who should own a given piece of document structure;
2. which supported engine mechanism fits that ownership decision;
3. which canonical sample demonstrates it;
4. what lifecycle and limitations apply;
5. what a professional generated ODT can look like.

## 2. Non-goals

Phase F does not:

- reopen completed TEMPLATE-AUTHORING-01A–E semantics;
- add speculative authoring APIs to make a sample more convenient;
- implement FINALIZATION-01 early;
- broaden Writer User Fields beyond the accepted 1.0 string scope;
- add PAGE-STYLE-AUTHORING-01, DOCUMENT-DEFAULTS-01, TABLE-COLUMN-IDENTITY-01, or other deferred capabilities;
- promise arbitrary mutation of native objects where the current API is read-only or more narrowly bounded;
- turn the website alignment into a separate redesign project;
- redesign regression fixtures solely for appearance.

If a desired showcase exposes a real missing capability, implementation stops at a documented blocked/design point. The gap is reviewed explicitly instead of being hidden inside sample code.

## 3. Canonical authoring model

Phase F preserves the established three complementary authoring choices:

### 3.1 Simple template processing

Use visible template expressions for scalar values, filters, and lightweight logic where LibreOffice owns the surrounding document structure.

### 3.2 Programmatic ODT elements

Use RichText, Paragraph, ListElement, ImageElement, RichTable, and related structured elements where PHP genuinely owns a dynamic subtree.

### 3.3 Addressable native ODT structures

Use supported named/native structures such as Sections, bookmarks, tables, frames, and bounded Writer User Fields where LibreOffice should remain the visual/structural author and PHP needs semantic handles.

Inspection, dependency mapping, preflight, and optional automation operate across these authoring choices. They are not presented as a fourth competing authoring model.

## 4. Public sample taxonomy

Version-1.0 public examples are organized semantically.

### Learn

- L01 Variables & Filters
- L02 Conditions
- L03 Repeating Content
- L04 Rich Content
- L05 Lists
- L06 Images
- L07 Tables
- L08 HTML Import
- L09 Native Objects
- L10 Writer User Fields
- L11 Template Inspection

### Capabilities

- C01 Page & Flow Layout
- C02 Advanced Table Layout
- C03 Frame Layout
- C04 Structured Collections
- C05 Mapping & Automation

### Showcases

- S01 Professional CV
  - S01a generated-region ownership
  - S01b structured-template ownership
- S02 Professional Invoice
- S03 Automated Report

The F0 audit is authoritative for the initial migration map from historical samples into this taxonomy.

## 5. Naming contract

The preferred public naming convention is:

```text
sample_<ROLE><NN>_<descriptive_name>.php
template_<ROLE><NN>_<descriptive_name>.odt
output_<ROLE><NN>_<descriptive_name>.odt
```

Examples:

```text
sample_L01_variables_filters.php
sample_C02_table_layout.php
sample_S02_professional_invoice.php
```

Variant suffixes are permitted where one semantic showcase has intentionally distinct implementations, for example `S01a` and `S01b`.

The implementation slice must decide whether renaming existing public sample paths requires transitional documentation or compatibility handling. Historical numeric identity is not itself a version-1.0 product requirement.

## 6. Public sample unit

A canonical public sample consists of:

1. executable PHP source using recommended public APIs;
2. a self-contained public ODT template where a template is required;
3. a canonical generated ODT result path;
4. concise documentation describing the ownership model and primary lesson;
5. inclusion in the common public-sample registry/discovery mechanism;
6. automated smoke coverage;
7. LibreOffice validation appropriate to its rendering sensitivity.

A public sample must not depend on a test-only fixture for normal execution.

Its name, source, template, output, and documentation must communicate one primary lesson.

## 7. Single source of truth for sample discovery

The current numeric-range discovery rules in the Sample Explorer and PublicSampleSmokeTest must be replaced by one explicit definition of the public suite.

The implementation may use a small registry/manifest or another simple mechanism, but it must avoid duplicated hard-coded lists and filename heuristics across Explorer, tests, and documentation.

The canonical definition must expose at least:

- semantic ID;
- title;
- role/category;
- PHP entry point;
- template path when applicable;
- canonical output path;
- short purpose/description.

Do not introduce a general plugin/configuration framework merely for sample metadata.

## 8. Migration and retirement rules

Historical examples are evidence until their unique value has been accounted for.

A historical sample may be removed from the public surface only when:

1. its unique capability has been migrated to a canonical L/C/S sample or a focused regression test;
2. the target path uses the supported/recommended API;
3. relevant automated coverage is green;
4. rendering-sensitive behavior has received appropriate LibreOffice validation;
5. documentation no longer points to the retired path.

Development scripts such as `test.php`, debug/font experiments, and legacy lifecycle examples should not remain under the canonical public sample surface after their useful behavior has been preserved elsewhere.

No Phase-F cleanup may modify, restore, delete, regenerate, or commit local `samples/output/*.odt` regression artifacts unless the specific task explicitly concerns those outputs.

## 9. Template quality contract

### 9.1 Learning samples

Learning templates prioritize clarity. They should be visually coherent, readable, and intentionally authored, but remain small enough to understand quickly.

### 9.2 Capability samples

Capability templates may be larger where necessary to expose layout or structural semantics. They must make the demonstrated behavior visually inspectable.

### 9.3 Professional showcases

S01, S02, and S03 are product presentation artifacts. They must look like plausible professional documents rather than technical fixtures.

They should be suitable for screenshots and public presentation.

LibreOffice remains the primary visual template designer. Phase F must not reproduce its layout system in PHP merely to make a showcase attractive.

## 10. Showcase contracts

### S01 Professional CV

S01 demonstrates two legitimate ownership boundaries using a shared professional visual language:

- S01a: LibreOffice shell with PHP-owned generated regions;
- S01b: LibreOffice-owned native repeatable structures addressed/instantiated by PHP.

The variants should make the architectural choice visible without implying that one universally replaces the other.

### S02 Professional Invoice

S02 is the approachable business-document showcase.

It should naturally demonstrate a useful subset of:

- scalar values and filters;
- conditions;
- repeating line items;
- tables;
- images/logo;
- metadata;
- header/footer and existing page-layout behavior.

It must not become an excuse to introduce new calculation or layout APIs. Business totals may be prepared as application data where appropriate.

### S03 Automated Report

S03 is the structured-authoring and automation flagship.

It should demonstrate, within currently supported semantics:

- a LibreOffice-authored professional report structure;
- source-oriented template inspection;
- meaningful dependencies/native structures;
- mapping;
- concrete preflight;
- common atomic `automate()`;
- structured/repeatable content where supported;
- an editable ODT result.

Arbitrary table/frame/native-object mutation must not be invented for the showcase. If the intended report design requires an unsupported mutation, the design must use an existing supported ownership boundary or record the gap for explicit architecture review.

## 11. Documentation contract

Phase F must align:

- root README;
- Quick Start;
- How the Engine Works;
- Template Authoring Guide;
- feature documentation;
- Sample Guide;
- CV/showcase documentation;
- navigation where necessary.

The public documentation must:

- use the same L/C/S terminology;
- distinguish recommended APIs from compatibility/history;
- explain the supported lifecycle;
- update stale statements left from work that is now complete;
- link important features to canonical samples;
- avoid implying unsupported native-object mutation;
- make the bounded Writer User Field scope explicit;
- explain that automation does not implicitly render, save, or finalize the document.

Architecture/product evidence may remain available, but normal user guidance should not require readers to reconstruct the project's development history.

## 12. Sample Explorer and website contract

The Sample Explorer must consume the canonical public sample definition rather than numeric 01–25 discovery and filename-derived categories.

Sample-21-specific showcase JavaScript must be replaced by role-driven presentation.

The public discovery experience should emphasize:

1. real editable ODT output;
2. the three professional showcases;
3. the Learn path;
4. advanced Capabilities;
5. links into authoritative documentation.

Website work remains bounded alignment after canonical terminology, samples, and templates are stable.

## 13. AI-agent readability

A coding/AI agent using only public repository material should be able to distinguish:

- current recommended workflow;
- compatibility/legacy APIs;
- regression evidence;
- research evidence;
- public examples;
- supported versus unsupported native semantics.

Canonical examples should avoid mixed historical APIs and unnecessary alternative spellings.

Public sample metadata and documentation should make ownership decisions explicit rather than requiring inference from implementation details.

## 14. Repository boundary contract

The intended meaning of repository areas is:

```text
samples/   supported public examples
tests/     regression and behavioral evidence
research/  ODF / architecture research evidence
docs/      current guidance and clearly identified architecture evidence
```

Existing valuable `research/` material is preserved.

Historical sample templates/assets may be removed only after migration verification. Orphan detection belongs to cleanup, but deletion must remain evidence-based.

## 15. Implementation sequence

Phase F implementation should proceed in bounded slices without turning each file into a separate milestone:

1. establish the canonical sample registry/discovery mechanism and naming/migration foundation;
2. build/modernize Learn samples and their templates;
3. build/promote Capability samples;
4. create and visually validate the three professional showcases;
5. align README, user documentation, Sample Guide, and navigation;
6. align Sample Explorer and bounded website presentation;
7. retire verified historical sample/template clutter;
8. perform Phase-F final review and preflight.

The sequence may be adjusted to support practical LibreOffice template work, but semantics and migration evidence must precede deletion.

## 16. Validation

Each implementation slice uses validation appropriate to its scope.

At Phase-F completion, run at least:

- focused PHPUnit/integration tests;
- PublicSampleSmokeTest using the canonical suite;
- full `composer test`;
- PHP lint for `src/` and `tests/`, plus changed sample PHP as appropriate;
- `composer validate` if package metadata changes;
- `git diff --check`;
- documentation build;
- ODT ZIP/XML integrity checks for canonical generated samples;
- LibreOffice headless rendering where applicable;
- manual LibreOffice visual review of all professional showcases and rendering-sensitive capability samples;
- save/reopen checks for representative structured samples.

Phase-F validation does not replace the later RELEASE-1.0 integration preflight.

## 17. Compatibility

Phase F is primarily a public-surface and authoring-product milestone.

Public engine APIs and established lifecycle behavior remain backward compatible.

Renaming/removing repository sample files is not treated casually: documentation, Sample Explorer, tests, and external discoverability must move coherently. No engine API is removed merely because a legacy sample used it.

Protected compatibility facades and legacy engine behavior remain outside Phase F unless a concrete defect blocks a canonical sample.

## 18. Completion criteria

TEMPLATE-AUTHORING-01F is complete when:

- the L/C/S public suite is implemented and self-consistent;
- every canonical sample has a clear primary lesson and appropriate public template;
- S01, S02, and S03 meet the professional visual bar;
- completed A–E capabilities relevant to authoring are discoverable through docs/samples;
- Explorer, tests, and documentation agree on the public sample suite;
- historical/development sample clutter has been migrated or explicitly retained for a documented reason;
- public docs no longer present stale pre-completion architecture as current;
- human and AI-agent readers can identify recommended mechanisms without reconstructing repository history;
- required automated and LibreOffice validation is green;
- no unresolved Phase-F architecture gap has been hidden inside showcase implementation.

After Phase F, the mandatory 1.0 path continues to FINALIZATION-01 and then the RELEASE-1.0 integration preflight.

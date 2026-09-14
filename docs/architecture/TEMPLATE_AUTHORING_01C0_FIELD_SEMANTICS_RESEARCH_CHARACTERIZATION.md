# TEMPLATE-AUTHORING-01C0 — Field Semantics Research & Characterization

## Status

**COMPLETE / GATE GREEN — NO PRODUCTION CHANGE / CHANGE CONTRACT NEXT**

This document opens TEMPLATE-AUTHORING-01 Phase C from the completed Phase-B baseline.

Phase C is responsible for a bounded native Writer-field binding capability. C0 does not approve a field family, binding API, execution model, or finalization behavior. Its purpose is to consolidate existing empirical evidence, state the semantic questions that must be answered before a Change Contract, and freeze the relevant current behavior.

## 1. Architectural boundary

The completed Phase-B contract remains authoritative:

- `inspectTemplate()` describes the original authored template;
- `inspect()` describes the current working document;
- `inspectTemplateStructure()` remains the focused original expression-topology view;
- native containment, control ownership, data-scope nesting, and dependency references are distinct relationships;
- evidence sites are not logical dependencies;
- dependency identity is scope-aware;
- `content.xml` body and page-owned header/footer regions in `styles.xml` are the bounded contract-v1 source regions.

Phase C may extend this model for approved native-field semantics. It must not silently reinterpret Phase-B evidence or introduce Phase-D declarative-control execution.

## 2. Existing empirical Writer/ODF evidence

RESEARCH-01A already established useful evidence.

### 2.1 User Fields

Writer User Fields use a central declaration and one or more references:

```xml
<text:user-field-decl office:value-type="string" office:string-value="Walter" text:name="customer"/>
<text:user-field-get text:name="customer">Walter</text:user-field-get>
```

The controlled RESEARCH-01A fixture established that the declaration value is semantically authoritative for Writer evaluation. Changing the declaration while leaving reference character data unchanged caused Writer to display the changed value on normal open and during headless PDF conversion.

Therefore the visible character data of `text:user-field-get` must not be treated as the authoritative application value.

### 2.2 Set/Get Variable

Writer Set Variable / Show Variable has different semantics:

```xml
<text:variable-decl office:value-type="string" text:name="customer"/>
<text:variable-set text:name="customer" text:display="none" text:formula="ooow:Walter" office:value-type="string" office:string-value="Walter"/>
<text:variable-get text:name="customer">Walter</text:variable-get>
```

A get resolves against applicable preceding document-flow state. This is position-sensitive semantics, not merely a second spelling of a document-global binding.

### 2.3 Repeated structures

RESEARCH-01A established an important scope warning: cloning a Section containing User Field references clones references to the same central declaration. It does not automatically create item-local field values.

Consequently a User Field named `company` inside `#foreach:experience` must not automatically be interpreted as equivalent to the Phase-B dependency `experience[].company`.

The existing `{{...}}` mechanism remains the established portable item-local binding mechanism unless later evidence justifies another model.

## 3. Current repository behavior frozen by C0

At C0 start, the production `TemplateContractInspector` has no native-field binding projection.

The current contract:

- does not project `text:user-field-decl` or `text:user-field-get` as `BindingDescriptor` values;
- does not project User Fields as logical dependencies;
- does not project `text:variable-decl`, `text:variable-set`, or `text:variable-get` as bindings/dependencies;
- continues to project ordinary `{{...}}` bindings around such fields normally;
- remains source-stable because `OdtTemplate::inspectTemplate()` reads `OdtPackage::sourceDom()`, not the mutable working DOM.

This is a characterization baseline, not the desired Phase-C end state.

## 4. C0 semantic distinctions

The following identities must remain distinct until evidence proves a safe composition:

1. **field declaration identity** — the authored native declaration;
2. **field reference occurrence** — one source evidence site;
3. **field value authority** — the ODF location whose value drives Writer evaluation;
4. **logical application dependency** — the data requirement exposed by `TemplateContract`;
5. **data scope** — ROOT or a collection-item scope;
6. **materialized display text** — cached/current visible field text;
7. **document-flow state** — relevant to Set/Get Variable and not equivalent to lexical/native containment.

A field reference occurrence must not become the primary identity of a document-global User Field.

## 5. Source-part questions

Phase-B contract coverage includes body content from `content.xml` and page-owned header/footer content from `styles.xml`.

C0 deliberately does not assume that field declarations and references have identical placement rules. Phase C must establish empirically:

- where Writer serializes User Field declarations;
- whether body and header/footer references resolve one declaration consistently;
- how declarations behave when references exist only in page-owned content;
- whether duplicate/conflicting declarations can occur in valid or Writer-produced documents;
- what source provenance belongs to declaration evidence versus reference evidence.

Coverage must remain explicit. Phase C must not broaden contract-v1 scanning to arbitrary package parts without a separate decision.

## 6. Candidate 1.0 field families

### User Fields — primary candidate

Reasons:

- central authoritative value;
- natural document-global semantics;
- multiple references can share one logical value;
- Writer reevaluates them on open/headless PDF in existing evidence;
- useful complement to visible `{{...}}` placeholders.

Open issue: their document-global identity conflicts with automatic collection-item localization.

### Set/Get Variable — research candidate, not yet approved

Reasons for caution:

- position-dependent document-flow semantics;
- set and get are semantically different evidence kinds;
- dependency identity cannot be inferred safely from name alone without modeling flow;
- repeated/cloned structures may change the effective value seen by a get.

### Conditional/hidden field families — outside the initial C binding core

Conditional Text, Hidden Text, Hidden Paragraph, and Conditional Sections remain important RESEARCH-01 evidence, but they express evaluation/selection semantics rather than the smallest native value-binding core. They must not be pulled into C merely because Writer exposes them as fields.

## 7. Required C research gates

Before the Phase-C Change Contract, evidence must answer:

### Gate C-R1 — Writer-authored source model
Create or reuse real LibreOffice-authored fixtures and record exact ODF structures for User Fields and Set/Get Variable, including declaration/reference placement.

### Gate C-R2 — ROOT identity and repeated references
Prove how multiple User Field references in body/header/footer relate to one declaration and determine the correct evidence/dependency projection.

### Gate C-R3 — collection-scope boundary
Characterize User Field references inside repeated/nested native Sections. No automatic item-local dependency may be claimed without evidence.

### Gate C-R4 — mutation authority and lifecycle
Determine the minimum native mutation needed to bind a value correctly and characterize open/save, repeated bind/render/save, `load()`, source-oriented `inspectTemplate()`, live `inspect()`, and Writer reevaluation.

### Gate C-R5 — type/value semantics
Characterize at least the types proposed for 1.0. String support must not silently imply numeric, date, boolean, currency, or formula semantics.

### Gate C-R6 — interoperability/finalization boundary
Record behavior for native ODT/Writer and supported export paths. Phase C must not promise DOCX portability merely because Writer/PDF reevaluation works. FINALIZATION-01 remains the owner of the general finalized/static-document contract.

### Gate C-R7 — diagnostics and malformed/ambiguous states
Characterize missing declarations, duplicate/conflicting declarations, unsupported value types, and references whose native semantics cannot be mapped safely.

## 8. Change-Contract questions

Only after the research gates should Phase C decide:

- which native field family/families are supported in 1.0;
- whether an approved User Field becomes a `BindingDescriptor`, a dependency, or both;
- representation-kind vocabulary;
- declaration/reference provenance;
- ROOT-only versus broader scope support;
- dependency deduplication rules;
- support/readiness states;
- diagnostics;
- binding/mutation API and its relation to existing `setValues()`, `assign()`, and `render()`;
- repeated lifecycle semantics;
- deterministic serialization changes;
- whether `contract_version = 1` remains sufficient or a contract-version change is required.

No answer is implied by C0.

## 9. Explicit non-goals

C0 does not implement native-field execution, change `TemplateContractInspector`, change `TemplateContract::toArray()`, add a public field-binding API, execute declarative Sections, implement Phase E, solve FINALIZATION-01, deprecate `{{...}}`, treat display text as authoritative field data, or infer item-local semantics from native containment.

## 10. C0 exit criterion

C0 is complete when:

- existing RESEARCH-01 field evidence is consolidated against the current Phase-B architecture;
- the current native-field blind spot is frozen by characterization tests;
- original-source lifecycle behavior is protected;
- research gates for Writer-authored fixtures, scope, lifecycle, types, diagnostics, and interoperability are explicit;
- no production behavior has changed.

The completed evidence assessment is recorded in `TEMPLATE_AUTHORING_01C0_FIELD_RESEARCH_EVIDENCE.md`.

All research gates C-R1 through C-R7 are green for the bounded Phase-C v1 scope.

Phase C v1 is intentionally limited to Writer User Fields with `office:value-type="string"`. Broader value types, Set/Get Variable, and other Writer field families are deferred to version 1.1 or later unless a concrete earlier dependency emerges.

The next step is the Phase-C Change Contract.

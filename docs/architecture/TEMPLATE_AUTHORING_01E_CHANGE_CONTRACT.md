# TEMPLATE-AUTHORING-01E — Mapping / Automation Change Contract

## Status

**Accepted architecture contract / E1–E6 implementation and manual LibreOffice closure complete**

This Change Contract defines the approved implementation boundary for TEMPLATE-AUTHORING-01E, the optional application-facing mapping and automation layer.

E1–E6 implementation, automated integration evidence, the common atomic invocation, and the required manual LibreOffice Writer integration gate are recorded as complete in [`TEMPLATE_AUTHORING_01E6_COMPLETION.md`](TEMPLATE_AUTHORING_01E6_COMPLETION.md).

It is derived from `TEMPLATE_AUTHORING_01E0_MAPPING_AUTOMATION_RESEARCH.md` and supersedes conceptual E0 examples only where this contract makes a narrower implementation decision.

No conceptual class, enum, service, or method name in this document is automatically a public API commitment. Public/protected compatibility and the existing architecture remain authoritative during implementation.

## 1. Purpose and scope

Phase E provides an optional application-facing mapping and automation layer that:

1. resolves application data against source-derived template semantics and explicitly supported engine automation targets;
2. performs complete non-mutating resolution and preflight before the first automation-owned mutation;
3. exposes the resolved result, capability applicability, provenance, and diagnostics in machine-inspectable form;
4. optionally applies the resolved automation through existing bounded mutation owners;
5. preserves existing imperative APIs as first-class library usage.

Phase E supports three distinct target families:

```text
Automation Target
├── Dependency Target
├── Native Object Action Target
└── Document Capability Target
```

Phase E is not a new renderer, document model, layout engine, finalization layer, or general-purpose automation language.

## 2. Semantic authorities

The following authority boundaries are mandatory.

### 2.1 Template source authority

`TemplateContract` remains the sole semantic description of the authored source template used by Phase E.

It remains authoritative for dependencies, scopes, controls, native-object evidence, provenance, and template-side mapping readiness.

Phase E must not create a second template schema, dependency graph, scope graph, or native-object discovery system.

`NativeObjectDescriptor` remains source evidence. Engine-version-dependent automation actions or capability state must not be embedded into it.

### 2.2 Engine capability authority

Engine automation knowledge is a separate concern from source evidence.

Conceptually:

```text
TemplateContract
    → what this template contains/requires

Engine capability catalog
    → what this engine version knows how to automate

Capability projection
    → what can be done with this concrete template/target
```

The concrete PHP types and public API for the catalog/projection are implementation decisions subject to this contract.

### 2.3 Working-document authority

Working-document mutation remains owned by `OdtDocumentContext` and the existing typed targets/services.

Source-derived target identity is resolved before mutation. Working-DOM target lookup during execution is not a second inspection mechanism.

## 3. Mapping definition semantics

Phase E supports three mapping-rule semantics.

### 3.1 Dependency mapping

A dependency mapping connects an application source to a `TemplateContract` dependency.

```text
person.name → dependency("name")
jobs[]      → dependency("experience[]")
```

After target resolution, the semantic identity is the corresponding `DependencyDescriptor`, not an independent target path string.

### 3.2 Native-object action mapping

A native-object action mapping contains:

```text
application source
+
source-derived NativeObjectDescriptor
+
registered supported action
```

Examples of approved Phase-E semantics are:

```text
profile      → section("Profile")  + replace-content
signature    → bookmark("Signature") + replace-text
person.photo → frame("Portrait")   + replace-image
```

The action must be an engine-known capability with describable target kind, payload semantics, mutation owner, and applicability/preflight rules.

Phase E must not accept arbitrary action semantics or become a universal action DSL.

### 3.3 Document capability mapping

A document capability mapping connects application data to an explicitly supported bounded document service rather than to a `NativeObjectDescriptor`.

The approved Phase-E document capability is metadata mapping, for example:

```text
person.name → metadata.author
locale      → metadata.language
```

A document capability may participate in Phase E only when it has an unambiguous existing owner, defined mutation semantics, machine-identifiable target, describable payload semantics, preflight support, atomicity participation, and no need for a competing source-inspection or layout/rendering model.

### 3.4 No implicit content construction

Phase E does not infer new rendering semantics from application values.

In particular, an action accepting `OdtElement` does not authorize implicit conversion of arbitrary arrays/objects/scalars into `RichText`, `Paragraph`, `RichTable`, or another structured element.

## 4. Application paths and scope mapping

Application-side paths are a bounded mechanism for locating values in external application data. They do not define template identity or document actions.

The supported semantic model covers ROOT and arbitrarily nested named collection-item scopes, for example:

```text
person.name
jobs[]
jobs[].employer
jobs[].projects[]
jobs[].projects[].title
```

Collection mappings establish hierarchical source/target item-scope relationships.

Within an explicitly established mapped scope, same-name dependency resolution is permitted with this precedence:

```text
1. explicit mapping
2. same-name resolution inside the explicitly mapped scope
3. unresolved
```

There is no unrestricted global name search across unrelated application branches.

Phase E must not become a generic DTO/object-graph mapper or ETL/expression language.

## 5. Resolution, validation, normalization, and preflight

All mapping validation that can be performed before mutation must occur before the first automation-owned mutation.

### 5.1 Static validation

Without concrete application data, Phase E must be able to validate as applicable:

- dependency target existence/readiness;
- native-object target existence, kind, and uniqueness;
- registered action support;
- action applicability derivable from source evidence;
- document capability support;
- mapping scope consistency.

### 5.2 Concrete resolution

With application data, Phase E must additionally validate as applicable:

- application path resolution;
- collection source shape;
- named-record item requirements;
- dependency value/collection shape;
- action payload compatibility;
- concrete action applicability;
- supported document-capability payloads.

Core Phase-C/Phase-D validation remains authoritative and must not be weakened or bypassed.

The strict collection distinction remains:

```text
missing collection ≠ empty collection
null collection    ≠ empty collection
scalar collection  ≠ empty collection
```

Application-specific normalization is allowed only when explicit in the mapping/application layer. Phase E must not silently redefine core semantics.

### 5.3 Preflight diagnostics

Preflight must distinguish meaningful failure categories, including where applicable:

- unresolved application source;
- unknown dependency;
- unknown/ambiguous native target;
- wrong native target kind;
- unsupported action;
- supported but inapplicable action;
- incompatible payload;
- unsupported document capability/field;
- invalid collection/scope shape.

No preflight failure may leave an automation-owned document mutation behind.

## 6. Inspectable resolved result and diagnostics

A complete non-mutating, machine-inspectable resolution result must exist before execution.

The research term `Resolved Mapping` is not a binding PHP type name. The implementation may choose a more accurate name because the result now covers all three target families.

Conceptually the result contains:

```text
Resolved Automation Result
├── Dependency Resolutions
├── Native Object Action Resolutions
└── Document Capability Resolutions
```

It must expose enough information for dry-run/preflight inspection, including as applicable:

- semantic target identity;
- resolved value/collection/payload;
- application source path/provenance;
- explicit versus scoped same-name provenance;
- explicit normalization provenance;
- requested action/capability;
- supported/applicable state;
- expected payload semantics;
- resolution status;
- diagnostics.

The result is not a document AST, DOM/ODF model, render tree, layout model, save plan, finalization model, or replacement for `TemplateContract`.

Resolution and capability projection must not mutate the Working Document.

## 7. Execution ownership and orchestration

Phase E is an orchestrator, not a competing renderer.

Every dependency binding or explicit action/capability mutation has exactly one effective mutating owner during an automation invocation.

Existing owners remain authoritative. These include dependency consumers, typed native-object targets/services, and document services such as metadata.

The dependency-driven execution order remains defined where structural ownership requires it:

```text
Writer User Fields
→ declarative structural controls
→ remaining Classic bindings
```

Evidence already consumed by a declarative structural owner must not be rebound by a later generic classic-binding pass.

Explicit native-object/document actions are not assigned an invented universal global order. Ordering is introduced only where an established/documented target or DOM dependency requires it.

Frame image automation must reuse the established named-frame image replacement semantics. If implementation extracts those semantics behind a typed owner, existing public/protected compatibility facades must be preserved where required.

## 8. Automation atomicity and failure semantics

One Phase-E automation invocation is one bounded atomic unit for mutations owned by that invocation.

```text
success
→ all automation-owned mutations retained

failure
→ restore Working Document to the state immediately before
  this automation invocation
```

Prior imperative mutations are outside the rollback boundary and must remain intact.

The rollback boundary covers every Working-DOM/document-local mutable state changed by the selected automation capabilities. It must not be hard-coded only to the Phase-D `content.xml`/`styles.xml` state. Metadata automation means `meta.xml` must be covered when mutated.

The technical snapshot/rollback mechanism is not prescribed by this contract.

Existing Phase-D execution-unit atomicity remains valid inside declarative execution. Phase E composes with it and must not weaken it.

The original exception/failure remains authoritative; rollback must not silently replace it with unrelated behavior.

## 9. Lifecycle, compatibility, and Authoring-UX readiness

Phase E remains optional.

Normal library usage must not require a mandatory global `render()`/automation lifecycle. Imperative APIs remain independently usable before and after automation, and automation does not implicitly save or finalize the document.

Conceptually:

```text
load
→ optional imperative operations
→ optional Phase-E automation
→ optional imperative operations
→ save
```

Phase E does not define repeated automation/render semantics. The Phase-E 1.0 guarantee is one successful automation invocation per Working Document lifecycle unless a later accepted contract explicitly extends it.

Existing public APIs and compatibility-sensitive protected methods must be preserved unless separately approved. Refactoring and behavior changes should not be mixed without characterization.

Phase-E inspection, capability projection, resolution results, and diagnostics must be machine-inspectable enough for the separately planned pre-1.0 `TEMPLATE-AUTHORING-UX-01` milestone to consume them without duplicating ODF/XPath inspection or engine capability knowledge.

Phase E does not implement the Authoring UX itself.

FINALIZATION-01 remains responsible for final document/export semantics.

## 10. Approved implementation scope, research boundary, and non-goals

### 10.1 Phase-E implementation scope

The approved Phase-E scope is:

#### Dependency automation

- Classic scalar/filter bindings;
- supported Writer User Field binding;
- native declarative `#if` / `#ifnot` controls;
- native declarative `#foreach` controls;
- ROOT and nested collection-item scope mapping;
- convention/scoped same-name and explicit mapping;
- resolution, diagnostics, dry run, and bounded automation.

#### Native-object actions

- Section `replaceContent(OdtElement)` semantics exposed as explicit `replace-content` automation;
- Bookmark `replaceText(string)` semantics exposed as explicit `replace-text` automation;
- named Frame image replacement exposed as explicit `replace-image` automation, reusing existing image-replacement semantics.

Section clone/instantiate/instantiateMany remain existing imperative capabilities but are not exposed as Phase-E mapping actions in this contract.

#### Document capabilities

- bounded metadata mapping for the field set already supported by `MetadataManager`.

Phase-E preflight may reject unsupported metadata targets while the existing imperative `MetadataManager` compatibility behavior remains unchanged. Phase E must not make unknown metadata keys globally stricter by changing that existing service contract.

### 10.2 Research candidates / deferred semantics

#### Frame image replacement semantics: accepted boundary and E4 gate

**Accepted now:** Phase-E image replacement follows “preserve by default, override explicitly.” E2-C validates a bounded concrete replacement payload but does not define the complete application-facing `IMAGE_REPLACEMENT` authoring API or execute replacement. The current internal E2-C projection accepts a local image source and explicit `width`/`height` options; these are the currently bounded options, not a declaration that they exhaust future replacement instructions. Its positive-length check accepts decimal values with `cm`, `mm`, `in`, `pt`, `pc`, or `px`. This check is local to E2-C; the repository has no shared authoritative ODF-length parser, and this is not a universal ODF length API. The imperative `replaceImageByName()` remains unchanged, including its legacy `5cm × 3cm` defaults.

**Future semantic questions:** Before Frame `replace-image` execution is implemented, investigate width and height overrides; supported ODF-compatible length values/units; aspect-ratio preservation; width-only and height-only interactions with aspect preservation; conflicts when width, height, and aspect preservation are all supplied; and preservation of template dimensions when no dimensional override is requested. Further image adaptation concepts such as fit, contain, cover, and crop are research candidates only. These terms (including any keep-ratio-equivalent) are not approved public option names or API.

**E4 gate:** E4 must review and settle the bounded replacement-option semantics before implementing Phase-E Frame `replace-image`. It must reconcile preserve-by-default, explicit dimensions, aspect-ratio behavior, supported ODF lengths/units, and imperative compatibility. E4 must not inherit the imperative `5cm × 3cm` defaults as Phase-E defaults by accident.

The following are relevant architecture/product topics but do not have sufficiently approved semantics for Phase-E implementation:

- native named-table population;
- structured/RichText bookmark insertion;
- page-layout mapping;
- general style mapping;
- Section clone/instantiate/instantiateMany as declarative mapping actions.

These are not automatically post-1.0 topics. Where required for the pre-1.0 authoring/product path, they require separate research/semantic decisions rather than silent Phase-E scope growth.

`TEMPLATE-AUTHORING-UX-01` is a required separate pre-1.0 milestone downstream of the Phase-E semantic backend.

### 10.3 Explicit Phase-E non-goals

Phase E does not introduce:

- a universal action DSL;
- arbitrary PHP methods as mapping targets;
- automatic array/object-to-`OdtElement` conversion;
- a generic DTO/object mapper;
- a general ETL/expression/transformation language;
- a renderer-neutral document AST;
- a second template/dependency/scope/native-object model;
- a PHP pagination/layout engine;
- general page-layout or style-property authoring semantics;
- native table-population semantics;
- finalization/export semantics;
- repeated automation/render semantics;
- automatic mutation merely because an object was discovered by inspection;
- implementation of a LibreOffice extension, mapping GUI, AI authoring assistant, or other Authoring UX.

## 11. Planned implementation slices

Exactly six implementation slices are planned. Additional slices require an explicit architectural/implementation reason and must not silently broaden scope.

### E1 — Mapping Core + Capability Model

Non-mutating semantic foundations:

- bounded application-path representation;
- mapping-rule semantics for all three target families;
- engine capability description/catalog;
- template-specific capability projection;
- static contract/target/action/capability validation;
- machine-inspectable Authoring-UX-facing semantic surface.

No document mutation.

### E2 — Resolution + Complete Dry-Run Preflight

Concrete application-data resolution:

- ROOT and nested collection paths;
- explicit mapping precedence;
- scoped same-name resolution;
- strict missing/null/empty/scalar collection semantics;
- native action applicability and payload compatibility;
- metadata target/payload validation;
- provenance and diagnostics;
- complete inspectable dry-run result.

No document mutation.

### E3 — Dependency Automation

Mutating automation for the dependency core only:

```text
Writer User Fields
→ declarative structural controls
→ remaining Classic bindings
```

Existing owners and scope/part/region semantics remain authoritative.

### E4 — Native Object Action Automation

Mutating automation for the approved native actions:

- Section `replace-content`;
- Bookmark `replace-text`;
- Frame `replace-image`.

Existing semantics must be reused. Frame integration must preserve compatibility around the existing named-frame image API.

### E5 — Document Capability Automation

Mutating automation for metadata only.

This slice validates the third target family without introducing a generic dispatcher for arbitrary future document APIs.

### E6 — Automation Atomicity + Integration Closure

Complete the outer automation transaction and integration guarantees across all implemented target families, including rollback coverage for every mutated document-local state, compatibility/lifecycle validation, documentation closure, and manual LibreOffice regression.

## 12. Verification and completion criteria

### 12.1 E1 capability/mapping tests

Tests must cover at least:

- dependency target lookup;
- native-object target lookup;
- document-capability lookup;
- ROOT/nested scope validation;
- unknown dependency/native object;
- wrong native-object kind;
- supported/unsupported action;
- applicable/inapplicable action where source evidence permits assessment;
- payload-contract description;
- supported/unsupported metadata target;
- no mutation by capability projection.

### 12.2 Authoring-UX projection characterization

A representative fixture must expose at least:

```text
Dependency: name, experience[]
Section:    Profile
Bookmark:   Signature
Frame:      Portrait
Table:      Skills
```

The machine-inspectable projection must be able to represent that dependency mapping is supported; Section `replace-content`, Bookmark `replace-text`, and Frame `replace-image` are supported/applicable where the fixture permits; and native Table `populate` is unsupported.

This verifies the semantic backend for `TEMPLATE-AUTHORING-UX-01`; it does not test a UI.

### 12.3 E2 resolution tests

Tests must cover the established dependency/path/scope cases plus:

- explicit native-object action resolution;
- native target provenance;
- action applicability failure;
- payload mismatch;
- metadata mapping;
- unsupported metadata target;
- mixed dependency + native action + document capability resolution.

The Working Document must remain unchanged by E2.

### 12.4 Professional CV architecture benchmark

The generic professional-CV benchmark must exercise all three target families without CV-specific engine APIs or a second scope model.

Representative data/targets should include:

```text
person.name        → dependency name
jobs[]             → dependency experience[]
jobs[].employer    → experience[].company
person.photo       → Frame "Portrait" + replace-image
person.author      → metadata.author
```

The complete request must be resolvable as one dry run.

### 12.5 E3 dependency ownership regression

No dependency binding evidence may be consumed more than once.

Coverage must include ROOT classic binding, classic binding in foreach, nested foreach, if-in-foreach, foreach-in-if, Writer User Field physically inside repeated structures, header/footer supported regions, and mixed scopes.

### 12.6 E4 native-action tests

Section coverage must include named-target resolution, `replaceContent(OdtElement)`, container preservation, relevant nested-object behavior, and missing/wrong target failures.

Bookmark coverage must include bounded `replaceText`, marker preservation, range preservation, and rejection of unsupported structured payload semantics.

Frame coverage must include named image frames, existing sizing/options semantics, and rejection during preflight when `replace-image` is not applicable to the concrete frame structure.

Phase-E action behavior must not diverge from the corresponding established imperative semantics.

### 12.7 E5 metadata tests

Tests must include single/multiple metadata mappings, unsupported target, payload mismatch where applicable, preservation of unrelated metadata, and mixed dependency/native-action/metadata automation.

The existing imperative behavior for unknown metadata keys must remain characterized and compatible even though Phase-E preflight is stricter.

### 12.8 E6 atomicity matrix

Failure injection must demonstrate rollback across capability-family boundaries, including at least:

```text
Dependency succeeds
→ Native action fails
→ all automation-owned mutations restored

Dependency succeeds
→ Native action succeeds
→ Metadata fails
→ all automation-owned mutations restored
```

Rollback must preserve imperative mutations performed before the automation boundary.

A successful automation invocation must permit subsequent imperative mutation and save.

### 12.9 Automated preflight

Before final GO, run as applicable:

```bash
composer test
composer validate
git diff --check
```

plus PHP lint for `src/` and `tests/`, focused regression/integration tests for the touched architecture, and `PublicSampleSmokeTest`.

Relevant regression areas include TemplateContract inspection, typed targets, structured insertion, Writer User Fields, declarative Section controls, Phase-D behavior, MetadataManager, and named-frame image replacement.

`samples/output/*.odt` remain local regression artifacts and must not be committed/restored/deleted/regenerated unless the task explicitly concerns them. LibreOffice `.~lock.*#` files must never be committed.

### 12.10 Manual LibreOffice regression

At least two representative real-document checks are required.

The automation benchmark should exercise classic binding, Writer User Field binding, foreach/condition behavior, Section structured content, Bookmark text, Frame image replacement, and Metadata.

A part/region benchmark should exercise BODY plus supported header/footer declarative content.

For rendering-relevant changes, generate the document, open and inspect it in LibreOffice, save, close, and reopen it. There must be no repair/corruption warning or unexplained template residue.

### 12.11 Documentation closure

Before Phase E is complete, architecture documentation, ROADMAP, public usage documentation where an API is exposed, and deferred/future research must be updated as applicable.

The following topics must remain visible and must not disappear into implementation assumptions:

- native table population;
- structured bookmark insertion;
- page-layout mapping research;
- style-mapping semantics research;
- `TEMPLATE-AUTHORING-UX-01` as pre-1.0 work;
- FINALIZATION-01.

### 12.12 Completion definition

TEMPLATE-AUTHORING-01E is complete when application data can be resolved through convention and explicit mappings against dependency targets, supported native-object actions, and supported document capabilities; when the complete result and capability applicability are machine-inspectable without document mutation; and when that resolved automation can be applied atomically through existing semantic owners while preserving source-contract authority, scope semantics, compatibility, document integrity, and LibreOffice interoperability.

Phase-E completion must provide a sufficient machine-inspectable semantic backend for the separately planned pre-1.0 `TEMPLATE-AUTHORING-UX-01` milestone.

Phase-E completion does not imply native table-population semantics, structured bookmark insertion, general style/page-layout mapping, finalization, repeated automation/render semantics, or implementation of the Authoring UX itself.

## 13. Architecture benchmark and intended user value

A key product benchmark is a professional LibreOffice-authored document whose authored layout remains in ODT while application data controls multiple semantic target types.

For example, a report or CV may combine:

```text
{{company}}
Section "ExecutiveSummary"
Frame "RevenueChart"
Frame "Portrait"
Bookmark "Signature"
metadata.author
```

Phase E should make those supported targets addressable without rebuilding LibreOffice layout in PHP. The template author remains responsible for document design; the engine is responsible for bounded semantic discovery, mapping, validation, and mutation through established owners.

## 14. Implementation gate

Implementation of E1 may begin only after this Change Contract has been checked against the accepted E0 research baseline and no unresolved contradiction remains.

If implementation reveals a contradiction with this contract, unexpected legacy behavior must first be characterized and the architecture decision revisited rather than silently changing semantics during refactoring.

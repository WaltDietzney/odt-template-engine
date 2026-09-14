# TEMPLATE-AUTHORING-01C — Native Field Binding Change Contract

## Status

**ACCEPTED / IMPLEMENTATION AUTHORIZED**

This Change Contract defines the bounded Phase-C v1 capability that follows the completed TEMPLATE-AUTHORING-01C0 field-semantics research.

The accepted research baseline is:

- Writer User Fields are the only native field family in Phase C v1;
- only `office:value-type="string"` is supported in Phase C v1;
- User Fields are document-global / ROOT-scoped in the characterized model;
- physical declaration evidence can be duplicated across `content.xml` and page-owned `styles.xml` content;
- native containment inside a repeated Section does not create item-local User Field scope;
- authoritative field value is carried by `text:user-field-decl`, not by cached `text:user-field-get` display text;
- valid cross-part declarations of one logical field must be synchronized when bound;
- Set/Get Variable is document-flow state and is not part of the Phase-C v1 binding model;
- broader field types and field families are deferred to version 1.1 or later unless a concrete earlier dependency emerges.

The evidence is recorded in:

- `TEMPLATE_AUTHORING_01C0_FIELD_SEMANTICS_RESEARCH_CHARACTERIZATION.md`;
- `TEMPLATE_AUTHORING_01C0_FIELD_RESEARCH_EVIDENCE.md`;
- the C0 characterization tests;
- the earlier RESEARCH-01 field/conditional-content evidence.

---

## 1. Goal

Phase C adds a bounded native Writer-field capability without replacing classic `{{...}}` binding and without implementing the later high-level render pipeline.

The user-visible capability is:

1. `inspectTemplate()` can expose supported Writer string User Fields as authored binding evidence and logical ROOT dependencies;
2. application code can explicitly bind one supported User Field value in the current working document;
3. all authoritative declaration evidence sites belonging to that logical field are updated atomically;
4. existing Phase-B lifecycle, provenance, dependency, diagnostics, and deterministic serialization guarantees remain intact.

Conceptually:

```text
LibreOffice-authored User Field
        ↓
inspectTemplate()
        ↓
ROOT dependency
        ↓
explicit User Field binding
        ↓
working ODT declarations updated
        ↓
save()
        ↓
Writer/LibreOffice reevaluates native field display
```

Phase C does not make native fields the universal scalar-binding mechanism.

---

## 2. Public compatibility baseline

The following existing public behavior remains semantically unchanged:

```php
$template->inspect();
$template->inspectTemplateStructure();
$template->inspectTemplate();

$template->setValues(...);
$template->assign(...);
$template->render();
$template->save(...);

$template->section(...);
$template->bookmark(...);
$template->table(...);
$template->frame(...);
```

In particular:

- `setValues()` and `assign()` continue to mean classic/template-language value assignment;
- classic `{{name}}` placeholders are not silently reinterpreted as User Fields;
- `render()` does not automatically bind native User Fields in Phase C;
- no existing method begins mutating native User Fields merely because a key name matches;
- the later Phase-E high-level render orchestration remains responsible for deciding how one mapped application value may eventually drive multiple supported binding representations.

Phase C is additive.

---

## 3. Approved Phase-C v1 field family

### 3.1 Supported

The only supported native field family is:

```xml
<text:user-field-decl
    text:name="customer"
    office:value-type="string"
    office:string-value="Walter"/>

<text:user-field-get text:name="customer">Walter</text:user-field-get>
```

Requirements for a supported logical User Field:

- non-empty `text:name`;
- `office:value-type="string"`;
- all declaration evidence sites associated with the same logical name are semantically compatible;
- declaration values are non-conflicting at inspection/binding time.

A supported User Field may have zero, one, or many `text:user-field-get` references.

A declaration-only field is still a legitimate binding candidate because native Writer conditions may depend on the declaration value even when no visible get reference exists.

### 3.2 Not supported in Phase C v1

The following are outside the Phase-C v1 binding model:

- `text:variable-set` / `text:variable-get`;
- numeric User Fields;
- date/time User Fields;
- boolean semantics;
- currency User Fields;
- formulas;
- Conditional Text;
- Hidden Text;
- Hidden Paragraph;
- Conditional Sections;
- other Writer field families.

These are not declared impossible. They are deferred to version 1.1 or later unless a concrete earlier dependency is separately approved.

---

## 4. Logical identity and scope

### 4.1 Logical identity

For Phase C v1, logical User Field identity is:

```text
(ROOT scope, field name)
```

Example:

```text
ROOT.customer
```

Physical declaration count does not define logical identity.

This is valid Writer-authored evidence for one logical field:

```text
content.xml
└── user-field-decl customer = Walter

styles.xml / Standard / header
└── user-field-decl customer = Walter
```

Both declarations belong to one logical `ROOT.customer` field when type and value agree.

### 4.2 User Fields remain ROOT-scoped inside repeated native structure

Native containment does not change User Field scope.

Given:

```text
Section #foreach:experience

    {{company}}
    User Field company_global
```

the logical dependencies are:

```text
experience[].company
ROOT.company_global
```

not:

```text
experience[].company_global
```

Phase C must not derive User Field data scope from native owner chains, foreach ownership, or Section containment.

### 4.3 Coexistence with classic binding of the same name

If the same logical ROOT requirement is authored through both representations:

```text
{{customer}}
User Field customer
```

the TemplateContract contains multiple binding evidence sites but one logical dependency:

```text
ROOT.customer
```

Dependency deduplication is by the established Phase-B semantic identity, not by representation kind.

Phase C does not yet make one low-level mutation API update both representations automatically.

---

## 5. Field declaration evidence and reference evidence

Both authoritative declarations and references are contract evidence.

Phase C reuses `BindingDescriptor` rather than introducing a new top-level `native_fields` collection.

Approved binding kinds:

```text
NATIVE_USER_FIELD_DECLARATION
NATIVE_USER_FIELD_REFERENCE
```

### 5.1 Declaration binding descriptor

For a supported declaration:

- `kind = NATIVE_USER_FIELD_DECLARATION`;
- `variable_name = text:name`;
- `filter_name = null`;
- `filter_option = null`;
- `support_state = SUPPORTED`;
- `dependency_id` points to the logical ROOT dependency;
- `raw_text` carries the authored field name, not the mutable/default value.

The authoritative default value is not promoted to logical dependency identity.

A declaration is represented through `BindingDescriptor` for contract uniformity and evidence/provenance composition. This does not make declaration and reference semantically equivalent: the declaration is also the **authoritative native value carrier**, while references are authored occurrence/display evidence.

### 5.2 Reference binding descriptor

For a supported `text:user-field-get`:

- `kind = NATIVE_USER_FIELD_REFERENCE`;
- `variable_name = text:name`;
- `filter_name = null`;
- `filter_option = null`;
- `support_state = SUPPORTED`;
- `dependency_id` points to the same logical ROOT dependency;
- `raw_text` may contain the original materialized display text for diagnostic/inspection purposes.

The reference text is not authoritative field value state.

### 5.3 Evidence ordering

Evidence remains deterministic in source order using the existing source-region traversal.

When one logical field has evidence in multiple source parts, dependency `evidence_ids` preserve deterministic evidence order according to the established contract traversal.

---

## 6. Provenance

Phase C uses the existing `SourceProvenance` model.

Approved `representation_kind` values:

```text
NATIVE_USER_FIELD_DECLARATION
NATIVE_USER_FIELD_REFERENCE
```

Approved carrier kinds are the native ODF element names:

```text
text:user-field-decl
text:user-field-get
```

Provenance must retain:

- `source_part`;
- `region_kind`;
- `region_owner`;
- `carrier_kind`;
- `representation_kind`;
- deterministic `source_order`;
- `native_owner_chain` where applicable.

A User Field reference inside a Section may therefore retain native owner provenance while still being logically ROOT-scoped.

Provenance and data scope remain separate.

---

## 7. Source coverage

Phase C does not broaden the Phase-B package coverage boundary.

User Field inspection is limited to the already approved contract-v1 authored regions:

```text
content.xml
    office:body / office:text

styles.xml
    style:master-page
        -> header*
        -> footer*
```

Arbitrary style definitions, `meta.xml`, `settings.xml`, embedded objects, and other package parts remain outside semantic scanning.

If a future field family requires broader package coverage, that requires a separate architecture decision.

---

## 8. Dependency projection

A valid supported string User Field creates or contributes evidence to one logical dependency:

```text
kind  = VALUE
scope = ROOT
name  = <text:name>
path  = <name>
```

Example:

```text
User Field: customer
    -> Dependency ROOT.customer
```

Multiple declaration/reference occurrences do not create multiple dependencies.

A declaration-only supported User Field still creates the ROOT dependency.

Classic ROOT bindings with the same name deduplicate into the same dependency.

Unsupported, malformed, or ambiguous native field evidence must not silently create a fully supported logical dependency.

---

## 9. Support states

Phase C uses the existing semantic support-state vocabulary:

```text
SUPPORTED
RECOGNIZED
UNSUPPORTED
MALFORMED
AMBIGUOUS
```

For Phase-C User Fields:

### SUPPORTED

Use when:

- name is non-empty;
- type is `string`;
- declarations for the logical identity are compatible;
- references resolve to a compatible logical declaration set.

### UNSUPPORTED

Use for recognized User Field evidence with a non-string value type.

The evidence remains inspectable, but Phase C does not claim binding support.

### MALFORMED

Use for source evidence that cannot form a valid User Field binding, including:

- non-empty reference with no matching declaration;
- reference with an empty field name;
- structurally incomplete User Field evidence where a safe logical identity cannot be formed.

### AMBIGUOUS

Use when more than one plausible authoritative state exists for the same logical identity, including:

- conflicting declaration values across source parts;
- conflicting declaration types across source parts;
- conflicting duplicate declarations for the same name within one source region/part.

### RECOGNIZED

Phase C does not need `RECOGNIZED` for the supported string User Field path.

It remains part of the shared support-state vocabulary for other template constructs.

---

## 10. Empty declarations

C0 observed a real Writer-authored empty, unreferenced declaration artifact.

Therefore:

```xml
<text:user-field-decl
    text:name=""
    office:value-type="string"
    office:string-value=""/>
```

when unreferenced:

- does not create a dependency;
- does not make the whole contract malformed;
- does not reduce unrelated capability readiness;
- may be omitted from public binding projection;
- does not require a diagnostic in Phase C v1.

An empty-name reference is different and is MALFORMED.

This distinction prevents Writer authoring artifacts from creating false global failures.

---

## 11. Diagnostics

Phase C adds stable diagnostics for native User Field states.

Required codes:

```text
orphan_user_field_reference
unsupported_user_field_type
ambiguous_user_field_declaration
conflicting_user_field_value
conflicting_user_field_type
```

The diagnostic boundary is fixed as follows:

- `ambiguous_user_field_declaration` is used for conflicting duplicate authoritative declarations within the same source part/region where no unique declaration state can be selected safely;
- `conflicting_user_field_value` is used when declaration evidence belonging to one logical field identity agrees on supported type but disagrees on authoritative value across source parts/regions;
- `conflicting_user_field_type` is used when declaration evidence belonging to one logical field identity disagrees on `office:value-type` across source parts/regions.

Implementations must not choose among these conflicting states by source-order precedence or fuzzy reconciliation.

Diagnostics must include provenance when one concrete evidence site is the subject.

When a conflict spans multiple evidence sites, the diagnostic subject must use a deterministic logical subject identity or one deterministic primary evidence site and a message that explains the conflicting field name. DOM nodes must never leak into the contract.

Diagnostic severity, semantic support state, and capability readiness remain separate.

---

## 12. Capability readiness

Phase C retains:

```text
inspection
dependency_mapping
```

and adds:

```text
native_field_binding
```

Readiness vocabulary remains:

```text
READY
LIMITED
BLOCKED
NOT_APPLICABLE
```

### 12.1 inspection

Normally remains `READY` when partial native-field evidence can still be represented.

### 12.2 dependency_mapping

- `READY` when all relevant User Field dependencies can be mapped deterministically;
- `LIMITED` when unsupported/malformed/ambiguous User Field evidence prevents complete dependency mapping while other contract information remains usable;
- `BLOCKED` only when the broader contract cannot map dependencies meaningfully.

### 12.3 native_field_binding

- `NOT_APPLICABLE` when no native User Field evidence is present;
- `READY` when all projected native User Field dependencies are supported and unambiguous;
- `LIMITED` when some supported User Fields remain bindable but other native field evidence is unsupported/malformed/ambiguous;
- `BLOCKED` when native User Field evidence exists but no safe supported binding operation can be performed.

Readiness is derived from semantic analysis, not by reading diagnostic codes back as control flow.

---

## 13. Contract serialization

Phase C keeps:

```text
contract_version = 1
```

Reason:

- the top-level contract shape is unchanged;
- Phase C adds new binding kinds, representation kinds, diagnostics, and one additive capability key;
- no existing serialized field is removed or redefined incompatibly.

The frozen top-level shape remains:

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

Serialization must remain deterministic.

If implementation reveals that a field requirement cannot be represented additively without redefining existing serialized semantics, implementation must stop for architecture review rather than silently changing contract version 1.

---

## 14. Public binding API

Phase C adds one explicit low-level public mutation method:

```php
$template->setUserField(string $name, string $value): void;
```

This name is intentionally specific.

Phase C does not add a generic `setNativeField()` API because no generic native-field semantics have been approved.

### 14.1 Method responsibility

`setUserField()` binds one supported logical string User Field in the current working document.

It must:

1. analyze all matching User Field declaration evidence in the supported working document regions;
2. validate the entire logical field before mutation;
3. require a non-empty field name;
4. require supported string declarations;
5. reject conflicting/ambiguous declaration state;
6. update all authoritative matching declaration sites atomically;
7. leave `text:user-field-get` display text unchanged;
8. leave the original source used by `inspectTemplate()` unchanged;
9. preserve normal `save()`, reopen, and `load()` lifecycle semantics.

The method must not partially update `content.xml` and then fail before updating `styles.xml`.

### 14.2 Display text

Phase C deliberately does not materialize/update the character data of `text:user-field-get`.

The authoritative mutation is:

```xml
office:string-value="<new value>"
```

on all compatible `text:user-field-decl` evidence sites.

This preserves the native Writer model established by C0.

Writer/LibreOffice reevaluation may update visible field display on open/render.

Cross-viewer/static materialization belongs to FINALIZATION-01, not Phase C.

### 14.3 Relation to classic APIs

This does not change:

```php
$template->assign(['customer' => 'Maria']);
$template->render();
```

into native-field binding.

A caller that wants both low-level representations updated in Phase C must call both relevant APIs explicitly.

The later Phase-E orchestration may map one application value to both representations based on `TemplateContract`.

---

## 15. Binding failure semantics

Phase C introduces:

```text
OdtTemplateEngine\Template\UserFieldBindingException
```

as the public failure type for `setUserField()`.

The exception exposes:

```php
$exception->fieldName(): string
$exception->reason(): string
```

Required reason values:

```text
NOT_FOUND
UNSUPPORTED_TYPE
MALFORMED
AMBIGUOUS
```

The reason values form a **closed, stable machine-readable vocabulary** for Phase C v1. Implementations should centralize them as constants or an equivalent closed representation rather than distribute free-form strings. The concrete PHP representation remains an implementation detail.

The exact human-readable message is not a stable machine API.

Binding failure must be atomic: the working document remains unchanged for that field operation.

No fuzzy field-name matching is permitted.

---

## 16. Internal responsibility split

Implementation should preserve small responsibility boundaries.

A preferred bounded architecture is:

```text
UserFieldAnalyzer
    -> source/working DOM field evidence
    -> logical identity
    -> compatibility/ambiguity state

TemplateContractInspector
    -> projects analyzer results into
       BindingDescriptor
       DependencyDescriptor
       diagnostics
       capability readiness

UserFieldBinder
    -> validates one logical working-document field
    -> atomically mutates authoritative declarations

OdtTemplate
    -> public compatibility/additive facade
```

The class names above are illustrative, not mandatory. The **responsibility boundaries are contractual**: semantic field analysis, contract projection, mutation, and public facade responsibilities must remain separated.

Names and exact file placement may vary if repository evidence suggests a better fit, but the responsibilities must not collapse back into `OdtTemplate` or duplicate field-semantics logic between inspection and mutation.

The analyzer/binder should be stateless unless concrete evidence requires document-owned state.

---

## 17. Working DOM versus original source

The lifecycle boundary is mandatory.

### inspectTemplate()

Uses original authored source:

```php
$this->package->sourceDom('content.xml')
$this->package->sourceDom('styles.xml')
```

Native User Field contract semantics therefore remain stable across working mutations.

### setUserField()

Operates on the current working document:

```text
OdtDocumentContext
├── content DOM
└── styles DOM
```

### save()

Persists the current working declarations.

### load()

Restores the working package from the original template and therefore restores original User Field declaration values.

### reopen saved output

Creating a new `OdtTemplate` from a previously saved output makes that output the new instance's original source. Its `inspectTemplate()` result may therefore reflect the saved User Field declaration values while retaining the same logical field identity.

---

## 18. Determinism and atomicity

Phase C must preserve:

- deterministic evidence IDs for unchanged original source;
- deterministic dependency IDs;
- deterministic evidence ordering;
- deterministic diagnostics ordering;
- no process-local object identifiers in serialization;
- no DOM handles in public descriptors;
- atomic mutation of one logical User Field across all supported authoritative declaration sites.

Binding validation must complete before any declaration is changed.

---

## 19. Implementation slices

Phase C implementation is deliberately bounded to three implementation slices after completed C0 research.

### Slice C1 — User Field semantic analysis and contract projection

Implement:

- internal User Field semantic analysis;
- declaration/reference evidence projection;
- ROOT dependency projection;
- cross-part logical deduplication;
- classic/native same-name dependency deduplication;
- support-state composition;
- native User Field diagnostics;
- `native_field_binding` capability readiness;
- deterministic serialization with `contract_version = 1`.

No working-document mutation API in this slice.

### Slice C2 — Explicit User Field binding

Implement:

- `UserFieldBinder` or equivalent small service;
- atomic cross-part declaration mutation;
- `UserFieldBindingException`;
- public `OdtTemplate::setUserField(string, string): void`;
- repeated bind/save/reopen behavior;
- `load()` reset behavior;
- original-source inspection stability.

No automatic participation in `render()`.

### Slice C3 — Writer integration, compatibility, documentation, and closeout

Complete:

- focused integration test against a real LibreOffice-authored User Field fixture;
- body/header cross-part case;
- foreach containment / ROOT-scope regression;
- ambiguity/error cases;
- repeated lifecycle checks;
- public documentation for `setUserField()` and native User Field inspection;
- executable sample or research-to-sample promotion where useful;
- full automated preflight;
- manual LibreOffice regression for field reevaluation and representative existing rendering-sensitive samples;
- final Phase-C review/closeout.

No Phase-D or Phase-E implementation.

---

## 20. Required tests

At minimum Phase C must prove:

### Inspection

- one string User Field declaration creates a ROOT dependency;
- multiple references deduplicate to one dependency;
- body/header duplicated declarations with same name/type/value deduplicate logically;
- declaration/reference evidence remains distinct;
- a User Field inside `#foreach` remains ROOT-scoped;
- classic `{{customer}}` plus User Field `customer` share one ROOT dependency;
- declaration-only fields are inspectable dependencies;
- unsupported value types remain evidence but do not claim supported binding;
- orphan references are diagnosed;
- conflicting values/types are diagnosed;
- empty unreferenced declaration artifacts do not poison unrelated contract state;
- deterministic `toArray()` output;
- `contract_version = 1`.

### Binding

- supported string binding changes all matching declarations;
- no get display text mutation occurs;
- content/header declarations remain synchronized;
- bind/save/reopen persists;
- repeated binding persists;
- classic render after binding preserves the native field value;
- `load()` restores original authored values;
- `inspectTemplate()` remains original-source stable after binding;
- binding an unknown field fails atomically;
- unsupported type fails atomically;
- ambiguous declarations fail atomically.

### Compatibility

- existing Phase-B inspection tests remain green;
- existing classic render/save tests remain green;
- public sample smoke tests remain green;
- no change to classic placeholder behavior.

---

## 21. LibreOffice validation

Automated XML/package tests do not replace Writer validation.

Phase C must include a real LibreOffice-authored reference fixture containing at least:

- one supported string User Field in body content;
- repeated body references;
- the same logical User Field in a page-owned header or footer;
- one native repeated Section containing a User Field reference plus an item-local classic placeholder, either in the same fixture or a companion fixture.

Manual/headless validation must prove:

- no repair warning;
- bound declaration value reevaluates correctly in Writer;
- header/body reflect the same bound logical value;
- repeated Section instances retain the ROOT User Field value while item-local placeholders differ;
- representative existing samples still render correctly.

---

## 22. Finalization / interoperability boundary

Phase C guarantees native ODT User Field binding for the characterized Writer model.

It does not guarantee that all other consumers or export formats reevaluate ODF User Fields identically.

Specifically:

- LibreOffice/Writer reevaluation is part of the characterized path;
- headless PDF behavior has supporting evidence;
- DOCX conversion of broader ODF conditional semantics is not uniformly equivalent;
- static/materialized cross-viewer output remains a FINALIZATION-01 concern.

Phase C must not silently materialize or remove native field semantics in order to solve export compatibility.

---

## 23. Explicit non-goals

Phase C does not:

- replace `{{...}}`;
- change classic `assign()`, `setValues()`, or `render()` semantics;
- implement mapped-data `render($data)`;
- execute declarative Sections;
- support Set/Get Variable;
- support non-string User Field types;
- implement Conditional Text/Hidden Text/Hidden Paragraph/Conditional Sections;
- broaden semantic package-part coverage;
- introduce a generic native-field abstraction unsupported by evidence;
- materialize `text:user-field-get` display text;
- solve FINALIZATION-01;
- add fuzzy field-name matching;
- infer item-local scope from native containment.

---

## 24. Stop conditions

Implementation must stop for architecture review if repository/Writer evidence shows any of the following:

- valid Writer-authored string User Fields cannot be identified deterministically by the contracted logical identity;
- correct binding requires mutable state outside the bounded working `content.xml` / page-owned `styles.xml` regions;
- declaration-only fields cannot be distinguished safely from irrelevant artifacts;
- cross-part declarations cannot be synchronized atomically without changing package lifecycle semantics;
- supported User Field binding requires changing classic render behavior;
- contract-v1 serialization cannot accommodate the new evidence additively;
- a User Field inside repeated structure proves to require item-local semantics contrary to C0 evidence;
- Writer requires mutation of cached get display text for correct native reevaluation;
- Set/Get Variable behavior becomes an unavoidable dependency for the approved User Field capability.

Do not silently expand scope to resolve a stop condition.

---

## 25. Completion criterion

TEMPLATE-AUTHORING-01C is complete when:

- supported string User Fields are represented in `inspectTemplate()`;
- logical ROOT dependencies are correct and deduplicated;
- declaration/reference provenance is preserved;
- native User Field diagnostics/readiness are stable;
- explicit atomic `setUserField()` binding exists;
- binding lifecycle is characterized and tested;
- existing APIs retain their previous semantics;
- `contract_version = 1` remains deterministic;
- real Writer-authored integration evidence is green;
- full automated preflight is green;
- manual LibreOffice regression is green;
- public documentation is updated;
- Phase-C closeout review finds no unresolved contract deviation.

Only then may work proceed to TEMPLATE-AUTHORING-01D declarative structural controls.

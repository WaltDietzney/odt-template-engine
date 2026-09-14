# TEMPLATE-AUTHORING-01C0 — Field Research Evidence Assessment

Status: EVIDENCE PASS COMPLETE / OPEN GAPS IDENTIFIED / NO PRODUCTION CHANGE

## Purpose

This document evaluates the existing RESEARCH-01 Writer-field evidence plus two new LibreOffice-authored fixtures against the C0 research gates C-R1 through C-R7.

The goal is not to approve a Phase-C public API. The goal is to distinguish what is already empirically known from what still requires evidence before the Phase-C Change Contract.

## Evidence set

Existing Writer-authored research fixtures reviewed:

- `01-variable-set-get.odt`
- `02-user-field.odt`
- `03-conditional-text.odt`
- `03b-conditional-text-placeholders.odt`
- `04a-hidden-text.odt`
- `04b-hidden-paragraph.odt`
- `05 — conditional-sections.odt`
- `05 — conditional-sections-male.odt`
- `05 — conditional-sections-unknown.odt`
- `05 — conditional-sections-male.docx`
- `06 -foreachl-sections.odt`
- `07-foreach-section-placeholders.odt`

New C0 fixtures:

- `02b-user-field-header.odt`
- `01b-variable-set-get-flow.odt`

The new fixtures were authored with LibreOffice 24.2.7.2 on Linux x86_64.

SHA-256:

- `02b-user-field-header.odt`: `d9205962c8c145da451ef2c7d2a65880a2d09b5ac1075c8aaa1477f0f069f30e`
- `01b-variable-set-get-flow.odt`: `1a9f9710f45779b09ac33c8c5da2b9ac69398d1bb4262bbbf56a47eb5301100e`

## 1. User Field source model

The original User Field fixture contains one central declaration and two body references in `content.xml`:

```xml
<text:user-field-decls>
    <text:user-field-decl
        office:value-type="string"
        office:string-value="Walter"
        text:name="customer"/>
</text:user-field-decls>

<text:user-field-get text:name="customer">Walter</text:user-field-get>
<text:user-field-get text:name="customer">Walter</text:user-field-get>
```

The new header fixture adds the same User Field to the Standard master-page header.

Writer then serializes:

```text
content.xml
├── user-field-decl customer = Walter
├── user-field-get customer
└── user-field-get customer

styles.xml / Standard / header
├── user-field-decl customer = Walter
└── user-field-get customer
```

This is an important Phase-C finding:

> A document-global logical User Field can have multiple physical declaration evidence sites across package source parts.

Therefore these concepts are distinct:

```text
physical declaration evidence
logical native field identity
reference evidence
logical application dependency
```

A public contract must not treat one physical declaration node as the only possible identity of the logical field.

The declaration value remains semantically authoritative for Writer evaluation; the visible character data of `text:user-field-get` is materialized/display data.

## 2. Set/Get Variable source and flow model

The baseline Set/Get fixture contains:

```xml
<text:variable-decl
    office:value-type="string"
    text:name="customer"/>

<text:variable-set
    text:name="customer"
    text:display="none"
    text:formula="ooow:Walter"
    office:value-type="string"
    office:string-value="Walter"/>

<text:variable-get text:name="customer">Walter</text:variable-get>
```

The new flow fixture adds later Writer-authored Set/Get pairs for the same variable:

```xml
<text:variable-set text:name="customer" office:value-type="string">Walter</text:variable-set>
<text:variable-get text:name="customer">Walter</text:variable-get>

<text:variable-set text:name="customer" office:value-type="string">Maria</text:variable-set>
<text:variable-get text:name="customer">Maria</text:variable-get>
```

Writer visibly resolves the first get to `Walter` and the later get to `Maria`.

This empirically confirms:

> Set/Get Variable semantics are document-flow state, not document-global value identity.

The variable declaration introduces the name/type, while value authority is carried by applicable preceding `text:variable-set` state.

A name-only native-field binding abstraction would therefore lose essential semantics for Set/Get Variable.

## 3. Related native-condition evidence

The earlier research remains relevant as boundary evidence.

### Conditional Text

`03-conditional-text.odt` uses a User Field declaration and:

```xml
<text:conditional-text
    text:condition="ooow:customer == &quot;Walter&quot;"
    text:string-value-if-true="Bedingung ist WAHR"
    text:string-value-if-false="Bedingung ist FALSCH">
    Bedingung ist WAHR
</text:conditional-text>
```

`03b-conditional-text-placeholders.odt` demonstrates that authoritative true/false branch attributes can themselves contain classic `{{...}}` placeholders.

This is evaluation/selection semantics, not the smallest native value-binding core.

### Hidden Text / Hidden Paragraph

The existing fixtures prove Writer-owned conditional visibility for inline text and whole paragraphs.

These remain relevant to FINALIZATION-01 and possible later native semantic support, but they must not be folded into Phase C merely because they are represented as Writer fields.

### Conditional Sections

The existing female/male/unknown fixtures demonstrate Writer-owned structural selection driven by the User Field `gender`.

The DOCX conversion fixture confirms that LibreOffice converts the User Field itself to a Word `DOCVARIABLE` / document variable, while equivalent ODF conditional Section semantics are not preserved as equivalent Word structural conditions.

This reinforces the boundary:

> Native field binding and native structural condition interoperability are separate concerns.

## 4. C-R1 — Writer-authored source model

Status: **GREEN FOR CURRENT STRING CANDIDATES**

Established:

- User Field declaration and get representation;
- User Field declaration authority;
- User Field declaration duplication across `content.xml` and page-owned `styles.xml` header content;
- Set/Get Variable declaration, set, and get representation;
- direct Writer evidence for flow-sensitive Set/Get values.

Not established and not required to claim C-R1 green for the current candidate set:

- every Writer field family;
- every ODF value type;
- arbitrary package-part placement.

Conclusion:

The source model is sufficiently characterized to distinguish User Fields from Set/Get Variable before API design.

## 5. C-R2 — ROOT identity and repeated references

Status: **EVIDENCE GREEN / CONTRACT PROJECTION DECISION PENDING**

Established:

- two body User Field references can share one logical name;
- a header reference can use the same logical User Field name;
- Writer duplicates the corresponding declaration into the header source part;
- physical declaration count therefore cannot equal logical dependency count.

Strong contract implication:

```text
User Field customer
    -> one candidate logical ROOT dependency
    -> multiple declaration evidence sites
    -> multiple reference evidence sites
```

Still deferred to the Change Contract:

- exact descriptor shape;
- whether declaration evidence and reference evidence are both represented as bindings;
- whether one logical dependency is projected directly from the field identity;
- deterministic cross-part deduplication rules.

## 6. C-R3 — collection-scope boundary

Status: **GREEN**

A Writer-authored fixture `08-foreach-section-user-field.odt` placed both classic item-local placeholders and a native User Field reference inside the same native `#foreach:experience` Section.

The existing Section `instantiateMany()` path created two instances with different item-local data:

```text
instance 1
{{company}}              -> Firma A
User Field company_globa -> Global Company

instance 2
{{company}}              -> Firma B
User Field company_globa -> Global Company
```

LibreOffice opened the generated result without repair and visibly showed the same native User Field value in both cloned Sections while the classic placeholder value differed per item.

This empirically proves:

> Native containment inside a repeated Section does not confer collection-item data scope on a User Field.

The cloned `text:user-field-get` references retain the same native field identity. No item-local declaration is synthesized by Section cloning.

Therefore a future contract must not project:

```text
User Field company_globa inside #foreach:experience
    -> experience[].company_globa
```

merely because of native containment.

The supported working interpretation for the characterized model is:

```text
{{company}} inside #foreach:experience
    -> experience[].company

User Field company_globa inside #foreach:experience
    -> document-global / ROOT field identity
```

This result directly validates the Phase-B separation of native containment, control ownership, and data-scope nesting.

## 7. C-R4 — mutation authority and lifecycle

Status: **GREEN**

Established by prior RESEARCH-01:

- mutating the central User Field declaration changes Writer-rendered output;
- Writer reevaluates the changed declaration on normal open;
- headless PDF conversion reevaluates the changed declaration;
- the visible `user-field-get` character data is not authoritative.

Established by C0 lifecycle characterization:

- the logical User Field value is carried by declaration state rather than current `user-field-get` display text;
- synchronized declaration mutation across `content.xml` and `styles.xml` survives classic `render()`;
- save/reopen preserves the mutated declaration value;
- a second mutation on the reopened document also survives save/reopen;
- `load()` restores the original template source declarations;
- `inspectTemplate()` remains stable because it reads the original authored source rather than the mutable working DOM;
- a mutation applied to only one source part creates a persistent contradictory cross-part field state;
- therefore an approved logical User Field binding must synchronize all declaration evidence sites that participate in the same logical field identity.

The characterization deliberately leaves cached/materialized `text:user-field-get` character data unchanged while mutating the authoritative declaration. This matches the prior Writer evidence that Writer reevaluates the field from declaration state.

The C-R4 research gate is therefore green for the bounded string User Field candidate.

The exact public mutation API and whether native field binding participates in `render()`, a dedicated binding method, or Phase-E orchestration remain Change-Contract decisions rather than unresolved lifecycle evidence.

## 8. C-R5 — type/value semantics

Status: **OPEN BEYOND STRING / STRING EVIDENCE GREEN**

All characterized User Field and Set/Get examples relevant to C currently use:

```xml
office:value-type="string"
```

No evidence currently justifies claiming equivalent semantics for:

- numeric values;
- date/time values;
- booleans;
- currency;
- formulas.

Two safe paths remain available for the Change Contract:

1. deliberately approve **string User Fields only** for the bounded 1.0 slice; or
2. conduct additional Writer research before approving more types.

C0 does not choose between them.

## 9. C-R6 — interoperability / finalization boundary

Status: **BOUNDARY GREEN / BROAD INTEROPERABILITY NOT CLAIMED**

Established:

- User Field declaration changes are reevaluated by LibreOffice on normal open;
- headless PDF rendering respects the changed semantic value;
- the conditional-section DOCX experiment converts the User Field `gender` to a Word `DOCVARIABLE` / document variable;
- ODF conditional Section semantics do not survive the characterized DOCX conversion as equivalent structural conditions.

Conclusion:

Phase C may define native ODT/Writer field binding without promising that every semantic consumer/export path preserves all related Writer semantics.

General materialization/finalization remains owned by FINALIZATION-01.

## 10. C-R7 — diagnostics and malformed/ambiguous states

Status: **GREEN**

C-R7 is deliberately bounded to the current Phase-C primary candidate: User Fields.

Set/Get Variable diagnostics are not part of this gate because Set/Get Variable is not currently approved as an ordinary Phase-C binding model. If that field family is promoted later, its document-flow-specific malformed states require a separate characterization.

The dedicated characterization test is:

```text
tests/Integration/TemplateAuthoring01C0UserFieldDiagnosticsCharacterizationTest.php
```

It freezes the current engine behavior for five distinct source states:

| Case | Source state | Current behavior being characterized | Future semantic question |
| --- | --- | --- | --- |
| Orphan reference | `text:user-field-get` without declaration | package loads, source persists, no contract projection/diagnostic today | should become malformed/unsupported field evidence |
| Duplicate same-part declarations | same name/type, different values in one part | both declarations persist physically | ambiguity/conflict must not be silently deduplicated |
| Cross-part value conflict | same name/type, different values in `content.xml` and `styles.xml` | contradictory declarations persist | one logical field cannot be READY while authoritative values disagree |
| Cross-part type conflict | same name, incompatible value types | both typed declarations persist | logical identity is ambiguous/incompatible |
| Empty unreferenced declaration | empty authored declaration plus valid field | source persists | must not automatically poison an otherwise valid field contract |

This matrix is intentionally different from ordinary cross-part duplication with the same name, type, and value. C-R2 established that Writer can legitimately duplicate one logical User Field declaration across source parts. Therefore:

```text
same name + same type + same value across parts
    -> normal physical duplication candidate

same name + conflicting value/type
    -> ambiguity/conflict candidate
```

The empty unreferenced declaration is also treated cautiously because a real Writer-authored C-R3 fixture produced such an artifact during authoring. Its mere presence is therefore not sufficient evidence for a global malformed state.

The characterization test does not implement Phase-C diagnostics. It records that the current Phase-B inspector ignores these native-field states and that save/reopen preserves them, so the later Change Contract can define diagnostics without silently changing legacy behavior.

The characterization suite is green. C-R7 therefore has enough evidence for the User Field candidate to proceed to diagnostic design in the Phase-C Change Contract.

## 11. Gate summary

| Gate | Status | Result |
| --- | --- | --- |
| C-R1 | GREEN for current string candidates | User Field and Set/Get source semantics distinguished |
| C-R2 | EVIDENCE GREEN / contract decision pending | cross-part declarations and repeated references established |
| C-R3 | GREEN | cloning proves native containment does not localize User Field scope |
| C-R4 | GREEN | declaration mutation, render/save/reopen, repeated application, load reset, and cross-part synchronization characterized |
| C-R5 | STRING GREEN / broader types open | no basis for non-string support yet |
| C-R6 | BOUNDARY GREEN | ODT/Writer/PDF evidence exists; no broad DOCX semantic promise |
| C-R7 | GREEN | bounded User Field ambiguity states characterized; diagnostic policy remains a Change-Contract decision |

## 12. Architecture consequence

The evidence does **not** support one generic field-binding model for both principal candidates.

A conservative working direction is now stronger:

```text
User Field
    -> candidate document-global ROOT binding
    -> central semantic value
    -> multiple declarations/references as evidence

Set/Get Variable
    -> document-flow state
    -> not approved as ordinary ROOT binding
    -> requires a separate flow-aware model if supported later
```

This is still a research conclusion, not a public API decision.

## 13. Next evidence work

The highest-value remaining work is now narrow:

1. decide whether Phase C is intentionally string-only for 1.0 or whether C-R5 receives additional type research.

Set/Get Variable should not receive implementation work until there is a concrete requirement that justifies modeling document-flow state.

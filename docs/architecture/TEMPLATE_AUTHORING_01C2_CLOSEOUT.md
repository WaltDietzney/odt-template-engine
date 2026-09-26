# TEMPLATE-AUTHORING-01C2 — Explicit User Field Binding Closeout

## Status

**COMPLETE / GATE GREEN**

C2 implements the mutation portion of the accepted
`TEMPLATE_AUTHORING_01C_NATIVE_FIELD_BINDING_CHANGE_CONTRACT.md`.

The user-reported verification gate is green:

- focused C2 binding tests;
- combined C0–C2 chain;
- Phase-B Slice 1–5 compatibility chain;
- PHP lint for changed source/test files;
- `git diff --check develop...HEAD`.

## Contract review

The implementation was reviewed against Sections 14–18 and 20 of the accepted
Phase-C Change Contract.

### Section 14 — public binding API

Accepted.

```php
$template->setUserField(string $name, string $value): void;
```

is present as a specific additive facade.

The method does not alter `assign()`, `setValues()`, or classic `render()`
semantics.

The binder operates on the current working `content.xml` and `styles.xml`
DOMs.

### Section 14.1 — validation and mutation responsibility

Accepted.

`UserFieldBinder`:

1. rejects an empty field name;
2. analyzes the complete bounded working-document User Field state;
3. resolves the requested logical field exactly;
4. validates its support/ambiguity state before mutation;
5. collects all authoritative declaration nodes;
6. mutates every accepted declaration only after validation has completed.

There is no expected validation failure path after the first declaration
mutation.

### Section 14.2 — display text boundary

Accepted.

C2 mutates only:

```xml
office:string-value="<new value>"
```

on authoritative `text:user-field-decl` nodes.

It deliberately leaves `text:user-field-get` character data unchanged.

This preserves the native Writer reevaluation boundary and does not pull
FINALIZATION-01 materialization into Phase C.

### Section 14.3 — classic API independence

Accepted.

The focused compatibility test proves that:

```php
$template->assign(['customer' => 'Maria']);
$template->render();
```

does not bind a same-named native User Field.

Classic and native low-level mutation paths remain explicit and independent.

### Section 15 — binding failure semantics

Accepted.

`UserFieldBindingException` is public and exposes:

```php
$exception->fieldName();
$exception->reason();
```

The closed Phase-C v1 reason vocabulary is centralized as constants:

```text
NOT_FOUND
UNSUPPORTED_TYPE
MALFORMED
AMBIGUOUS
```

No fuzzy field-name matching is implemented.

Expected validation failures occur before mutation.

### Section 16 — internal responsibility split

Accepted.

Responsibilities remain separated:

```text
UserFieldAnalyzer
    -> semantic field analysis

UserFieldBinder
    -> working-document validation + mutation

OdtTemplate
    -> public facade
```

The binder reuses `UserFieldAnalyzer` rather than reimplementing field
compatibility semantics.

The bounded source-region discovery logic is locally repeated by inspector and
binder, but this is coverage plumbing rather than duplicated field-state
semantics. It does not currently justify an additional abstraction. Future
changes to the Phase-B coverage boundary must keep both callers aligned.

### Section 17 — lifecycle

Accepted.

Tests prove:

- `inspectTemplate()` remains source-oriented after binding;
- save persists the working declaration values;
- reopening a saved output permits a second binding;
- classic render preserves a previously bound native value;
- `load()` restores the original template field value;
- a saved output opened as a new `OdtTemplate` becomes the new original
  source.

### Section 18 — determinism and atomicity

Accepted for the bounded C2 mutation model.

All semantic validation completes before mutation begins.

Supported cross-part declarations are collected first and then updated as one
logical field operation.

Expected failures for empty/unknown names, unsupported types, and ambiguous
declarations leave the working field state unchanged.

C2 introduces no new public serialized identities or process-local handles.

### Section 20 — required binding tests

Covered by the focused C2 integration test and prior C0/C1 suites:

- supported string binding updates all matching declarations;
- get display text remains unchanged;
- body/header declarations remain synchronized;
- bind/save/reopen persists;
- repeated binding persists;
- classic render after binding preserves native value;
- `load()` restores original authored values;
- `inspectTemplate()` remains original-source stable;
- unknown field fails without mutation;
- unsupported type fails without mutation;
- ambiguous same-region and cross-part states fail without mutation;
- classic assignment does not implicitly bind a User Field.

## Compatibility finding

No reviewed C2 change affects:

- Phase-B contract semantics;
- classic placeholder replacement;
- structured target APIs;
- declarative Section execution;
- high-level render orchestration;
- finalization/export semantics.

## Review finding

No contract deviation requiring remediation was found.

One implementation note remains intentionally documented rather than
refactored: bounded source-region discovery currently exists in both
`TemplateContractInspector` and `UserFieldBinder`. The semantic User Field
analysis itself is shared through `UserFieldAnalyzer`, so there is no
duplicated field compatibility policy. Introducing another service solely to
deduplicate region plumbing would be premature at this slice.

## Completion criterion

C2 is complete because:

- the explicit public User Field API exists;
- mutation is limited to supported string User Fields;
- cross-part authoritative declarations are synchronized;
- display text is not materialized;
- stable failure semantics exist;
- expected failure paths are mutation-free;
- lifecycle behavior is covered;
- classic APIs retain previous semantics;
- C0–C2 and Phase-B compatibility gates are green.

## Next slice

Proceed to:

```text
TEMPLATE-AUTHORING-01C3 — Writer Integration, Documentation & Closeout
```

C3 owns the real LibreOffice-authored fixture integration, public documentation,
representative sample/preflight coverage, manual Writer regression, and final
Phase-C closeout.

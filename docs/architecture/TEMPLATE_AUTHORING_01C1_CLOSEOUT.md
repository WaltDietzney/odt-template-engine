# TEMPLATE-AUTHORING-01C1 — User Field Contract Projection Closeout

## Status

**COMPLETE / GATE GREEN**

C1 implements the inspection-only portion of the accepted
`TEMPLATE_AUTHORING_01C_NATIVE_FIELD_BINDING_CHANGE_CONTRACT.md`.

No C2 mutation API is included in this closeout.

## Reviewed baseline

The closeout review checked the implementation against:

- the accepted Phase-C Change Contract;
- completed C0 field-semantics research;
- C-R1 through C-R7 evidence;
- existing Phase-B contract semantics for bindings, dependencies, provenance,
  diagnostics, readiness, serialization, and source lifecycle.

The user-reported local verification gate is green:

- focused C1 projection tests;
- combined C0 + C1 tests;
- Phase-B Slice 1–5 compatibility chain;
- PHP lint for changed source/test files;
- `git diff --check develop...HEAD`.

## Accepted C1 outcomes

### 1. Bounded field family

C1 projects only Writer User Fields.

Supported binding semantics are limited to:

```text
office:value-type = string
logical scope      = ROOT
```

Set/Get Variable remains outside the binding model.

### 2. Semantic analysis responsibility

`UserFieldAnalyzer` provides a stateless semantic analysis boundary for native
User Field evidence.

It distinguishes:

- declarations;
- references;
- logical field identity;
- authoritative type/value compatibility;
- supported, unsupported, malformed, and ambiguous states.

`TemplateContractInspector` remains responsible for public contract projection.

This satisfies the accepted responsibility split without moving field semantics
into `OdtTemplate`.

### 3. Declaration/reference evidence

C1 projects:

```text
NATIVE_USER_FIELD_DECLARATION
NATIVE_USER_FIELD_REFERENCE
```

through the existing `BindingDescriptor`.

Declarations remain authoritative native value carriers while references remain
authored occurrence/display evidence.

No DOM nodes are exposed publicly.

### 4. ROOT dependency semantics

Supported User Fields project one logical ROOT `VALUE` dependency per field
name.

Multiple declarations/references deduplicate logically.

A classic ROOT binding and a supported User Field with the same name contribute
evidence to the same logical dependency.

A User Field physically contained inside a native foreach Section remains
ROOT-scoped and retains its native-owner provenance independently.

### 5. Source coverage and provenance

C1 does not widen Phase-B semantic coverage.

It analyzes only:

- `content.xml / office:body / office:text`;
- page-owned header/footer regions below `style:master-page` in `styles.xml`.

Native User Field evidence retains source-part, region, master-page owner,
native owner chain, deterministic source order, and native carrier kind.

### 6. Diagnostics and support states

The accepted diagnostic vocabulary is implemented:

```text
orphan_user_field_reference
unsupported_user_field_type
ambiguous_user_field_declaration
conflicting_user_field_value
conflicting_user_field_type
```

The reviewed implementation preserves the contract distinction between:

- same-region conflicting declarations -> `ambiguous_user_field_declaration`;
- cross-region/source-part value conflict -> `conflicting_user_field_value`;
- cross-region/source-part type conflict -> `conflicting_user_field_type`.

Empty unreferenced Writer declaration artifacts remain non-poisoning.

### 7. Capability readiness

C1 adds:

```text
native_field_binding
```

with the accepted readiness composition:

- no relevant native evidence -> `NOT_APPLICABLE`;
- only supported/unambiguous fields -> `READY`;
- supported fields plus problematic evidence -> `LIMITED`;
- relevant evidence but no safely supported field -> `BLOCKED`.

Problematic native field evidence limits dependency mapping while inspection
remains available.

### 8. Serialization and determinism

The top-level contract shape remains unchanged and:

```text
contract_version = 1
```

is retained.

New evidence kinds, diagnostics, and the additive capability key fit the
existing contract shape without redefining existing serialized fields.

The focused tests confirm deterministic repeated `toArray()` output and no
DOM/process-local identities in public serialization.

## Compatibility review

No reviewed C1 code changes:

- `assign()`;
- `setValues()`;
- classic `render()`;
- `save()`;
- imperative structured target APIs;
- Phase-D declarative execution;
- Phase-E high-level orchestration.

The C0 pre-C1 blind-spot characterization was advanced only where the accepted
Change Contract explicitly authorizes new User Field projection.

Set/Get Variable remains ignored by the native binding projection.

## Review findings

No contract deviation requiring remediation was found in the reviewed C1
implementation.

One implementation detail is explicitly accepted: User Field evidence is
projected after existing classic/native-structure region processing rather than
being interleaved with classic expression evidence. Evidence IDs, provenance,
dependency evidence lists, and serialization remain deterministic, and the
contract requires deterministic traversal rather than a single mixed DOM-order
stream across representation families.

## C1 completion criterion

C1 is complete because:

- semantic User Field analysis exists;
- supported declaration/reference evidence is projected;
- ROOT dependency semantics are implemented;
- cross-part and classic/native deduplication are represented;
- diagnostics and support states are implemented;
- `native_field_binding` readiness is implemented;
- contract-v1 serialization remains deterministic;
- the focused and Phase-B compatibility gates are green;
- no working-document mutation API has been pulled forward from C2.

## Next slice

Proceed to:

```text
TEMPLATE-AUTHORING-01C2 — Explicit User Field Binding
```

C2 owns working-document mutation, atomic cross-part declaration updates,
`UserFieldBindingException`, public `OdtTemplate::setUserField()`, and the
binding lifecycle defined by the accepted Change Contract.

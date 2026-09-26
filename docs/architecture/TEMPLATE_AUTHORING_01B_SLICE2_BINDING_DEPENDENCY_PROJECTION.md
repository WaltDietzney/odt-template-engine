# TEMPLATE-AUTHORING-01B Slice 2 — Binding & Dependency Projection

Status: COMPLETE / GATE GREEN

## Scope

Slice 2 adds logical dependency projection for visible authored binding evidence discovered by the unified template inspection contract.

The slice is intentionally bounded to dependencies whose semantic data scope can be established without implementing classic control ownership or collection-item scopes. Foreach scope semantics remain Slice 3 work.

## Completed semantics

Slice 2 establishes:

- an explicit semantic `ROOT` data scope;
- immutable logical dependency descriptors;
- binding-evidence to dependency references;
- logical dependency projection for supported scalar, filtered-scalar, and special bindings;
- scope-aware dependency identity;
- dependency deduplication across authored evidence sites;
- shared ROOT dependency semantics across body, page-owned headers, and page-owned footers;
- deterministic dependency identities and serialization;
- derived readable dependency paths that are not used as primary dependency identity.

For example, authored occurrences of `{{name}}` in body, header, and footer remain separate evidence sites while referring to one logical ROOT dependency.

Filtered forms such as `{{upper:name}}` refer to the same logical data requirement as `{{name}}`; filter metadata remains evidence-level information and does not create a second data dependency.

## Repetition boundary

Slice 2 does not invent ROOT semantics for bindings whose correct scope depends on a classic foreach block.

For example:

```text
{{name}}

{{#foreach:experience}}
    {{company}}
    {{name}}
{{#endforeach}}
```

Slice 2 may map the outer `{{name}}` to the ROOT `name` dependency, but the repetition-owned `{{company}}` and `{{name}}` evidence remain unresolved at the dependency-link level.

This is deliberate. Slice 3 owns:

- classic control projection;
- foreach collection dependencies;
- collection-item data scopes;
- nested repetition scopes;
- correct dependency resolution within those scopes.

A same-name ROOT dependency must not capture repetition-owned evidence. In particular, ROOT `name` and a future `experience[].name` are distinct logical dependencies.

When repetition-owned binding evidence prevents complete dependency mapping, capability readiness is reported as `LIMITED`, not `READY`.

For templates whose discovered binding requirements are fully resolvable in ROOT at this slice boundary, dependency mapping is `READY`.

## Compatibility

Slice 2 does not change the semantics of:

- `inspect()`;
- `inspectTemplateStructure()`;
- classic `render()`;
- `save()`;
- existing section/bookmark/table/frame APIs.

It does not introduce native-field execution, declarative native-control execution, or high-level mapped rendering.

The original-source lifecycle established by Slice 1 remains unchanged.

## Closeout verification

The local closeout gate was reported green after the final scope-collision correction.

Verified checks include:

- focused Slice 2 integration tests;
- combined Slice 0, Slice 1, and Slice 2 integration gates;
- PHP syntax checks for the touched Slice 2 implementation and test files;
- `git diff --check develop...HEAD`.

The focused Slice 2 gate verifies, among other things:

- multiple evidence sites can reference one ROOT dependency;
- body/header/footer evidence shares the same ROOT dependency where semantically appropriate;
- filtered and unfiltered occurrences of the same variable share a dependency;
- special bindings participate in logical dependency projection;
- dependency IDs and serialized output are deterministic;
- repetition-owned evidence remains unresolved rather than being falsely projected into ROOT;
- dependency readiness becomes `LIMITED` when that unresolved repetition boundary is present;
- a same-name ROOT dependency cannot capture repetition-owned evidence;
- bindings after a closed foreach block resume ROOT dependency projection.

## Exit criterion

Slice 2 is complete.

The implementation satisfies the Slice-2 boundary of the TEMPLATE-AUTHORING-01B change contract and the B1.1 graph/data-scope model without claiming Slice-3 control or collection-item semantics.

Proceed to:

```text
TEMPLATE-AUTHORING-01B Slice 3 — Classic Controls & Data Scopes
```

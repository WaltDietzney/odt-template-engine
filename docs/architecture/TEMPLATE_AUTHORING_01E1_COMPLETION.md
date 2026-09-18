# TEMPLATE-AUTHORING-01E1 — Completion Record

## Status

**E1 completed / E2 not started**

This record closes implementation slice E1 — Mapping Core + Capability Model — under `TEMPLATE_AUTHORING_01E_CHANGE_CONTRACT.md`.

E1 is a non-mutating semantic foundation. It does not resolve concrete application data, execute automation, mutate the Working Document, or change render/save/finalization lifecycle behavior.

## Implemented E1 semantic surface

E1 provides:

- bounded immutable application paths with named VALUE/COLLECTION segments and nested collection boundaries;
- three explicit mapping-rule families: dependency mapping, native-object action mapping, and document-capability mapping;
- a deterministic engine capability catalog with stable dependency capability ID `dependency.automation`;
- the approved native actions Section `replace-content`, Bookmark `replace-text`, and Frame `replace-image` with bounded payload semantics;
- the finite metadata target set already supported by `MetadataManager` without changing its imperative compatibility behavior;
- template-specific capability projection from `TemplateContract` plus engine capability knowledge;
- static mapping validation for dependency identity/shape/readiness, explicit collection-scope relationships, native target identity/kind/action/applicability, document capabilities, and conflicting effective targets;
- machine-readable diagnostics and deferred checks;
- an Authoring-UX characterization fixture exercising the real `TemplateContractInspector` path.

No same-name resolution, application-data traversal, concrete payload validation, dry-run resolution, mutation, rollback, table population, or Authoring UX implementation is part of E1.

## Capability projection closure

The E1 Authoring-UX fixture establishes the following machine-inspectable surface:

```text
Dependency name          -> dependency automation available
Dependency experience[]  -> dependency automation available
Section Profile          -> replace-content available
Bookmark Signature       -> replace-text available
Frame Portrait           -> replace-image available; applicability UNKNOWN
Table Skills              -> no populate capability
Document metadata         -> supported MetadataManager targets discoverable
```

`Portrait.replace-image` intentionally remains `UNKNOWN` at static projection time because the current source-derived `NativeObjectDescriptor` proves frame identity but does not prove that the frame contains replaceable image content. E1 does not introduce a second DOM/XPath inspection path to manufacture that evidence. The unresolved applicability is represented as a deferred check for later complete preflight.

## Architectural closure

E1 preserves the accepted authority boundaries:

- `TemplateContract` remains source semantic authority;
- engine capability knowledge remains separate from source evidence;
- `NativeObjectDescriptor` is not enriched with engine-version-dependent actions;
- application paths are distinct from template-side `DataScopeDescriptor` semantics;
- capability projection and static validation do not require a Working DOM;
- unsupported future operations are not registered as negative capabilities;
- existing public/protected imperative APIs and `MetadataManager` compatibility behavior remain unchanged.

E2 remains the next planned slice and has not started. It owns concrete application-data resolution, explicit-versus-scoped-same-name precedence, strict missing/null/empty/scalar collection semantics, concrete payload/applicability checks, provenance, diagnostics, and the complete non-mutating dry-run result.

## Verification evidence

Implementation and review were completed on branch `architecture/template-authoring-01e1-mapping-core` through commits:

- `5bcce3b8512f04b127236a31a16e4690b94d4211` — E1 mapping core and capability projection;
- `38d1efeb48a9a500c31fa1a3e41a6c0c9045eb32` — stable capability ID correction to `dependency.automation`.

Reported final implementation preflight before this documentation-only closure:

- focused E1 suite: 24 tests, 81 assertions, passed;
- full `composer test`: 869 tests, 5,835 assertions, passed, with 8 existing PHPUnit deprecations reported;
- PHP lint: passed;
- `composer validate --no-check-publish`: passed;
- strict documentation build: passed;
- `git diff --check`: passed;
- PublicSampleSmokeTest and relevant TemplateContract/native-target/metadata regression suites: passed during E1 implementation review.

The implementation diff and the follow-up capability-ID fix were independently reviewed against the accepted E1-A through E1-F semantics before this closure record was added.

No LibreOffice visual regression is required for E1 itself because E1 introduces no document mutation or rendering change. Rendering-relevant Phase-E slices retain the Change Contract's manual LibreOffice regression requirement.

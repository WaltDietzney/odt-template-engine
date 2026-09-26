# STYLE-API-02C — Canonical Document Style Facade Change Contract

Status: **ACCEPTED IMPLEMENTATION SLICE — SEMANTICS BEFORE IMPLEMENTATION**
Baseline: `develop` after merged STYLE-API-02B (PR #61)
Target branch: `architecture/style-api-02c-document-style-facade`

## 1. Purpose

STYLE-API-02B established the target public style model and approved a small,
document-oriented style authoring facade reached through `OdtTemplate::styles()`.

STYLE-API-02C is the first implementation slice of that contract. It introduces
only the smallest proven public capability:

```php
$template->styles()->defineParagraph('CVEntryTitle', [
    'margin-top' => '0.1cm',
    'margin-bottom' => '0.03cm',
]);
```

02C does not redesign the full style subsystem. Its purpose is to prove the
public facade boundary, document-local lifecycle, and named paragraph
definition semantics without introducing another registry or widening the API
speculatively.

## 2. Accepted architectural baseline

02C inherits these decisions from STYLE-API-02B:

- element-centric style options remain the primary application authoring API;
- named style references and named style definitions are distinct concepts;
- generated named definitions are document-scoped;
- `OdtTemplate::styles()` is the canonical public authoring/access boundary for
  reusable generated styles;
- the facade is not the semantic owner of style state;
- semantic ownership remains in `OdtDocumentContext` / `StyleContext`;
- the facade must follow the current logical document and must not retain stale
  document state across `load()`;
- `StyleMapper` process-global registration is not the target ownership model;
- no generic family-agnostic public `defineStyle()` API is introduced.

Current code already provides a stable document ownership boundary:

```text
OdtTemplate
    -> OdtPackage
        -> OdtDocumentContext
            -> StyleContext
```

`OdtDocumentContext::replaceCoreDocuments()` resets document-scoped
requirements and keeps the same context object while replacing its DOM state.
This lifecycle behavior is the foundation for the 02C facade contract.

## 3. Public API contract

### 3.1 `OdtTemplate::styles()`

02C introduces:

```php
$template->styles()
```

It returns a small document-style authoring facade.

The exact concrete class name may be `DocumentStyles` or another equally narrow
name. The behavioral contract is more important than the concrete name.

The facade must:

- represent the owning `OdtTemplate` instance;
- operate on that template's current logical document;
- delegate semantic ownership to the current `OdtDocumentContext` /
  `StyleContext`;
- own no duplicate mutable style registry;
- expose no raw `StyleContext` access;
- expose no XML writer/materializer controls;
- remain valid if retained by user code across `OdtTemplate::load()`.

### 3.2 `defineParagraph()`

02C introduces only:

```php
$template->styles()->defineParagraph(string $name, array $options): void;
```

The method name is canonical for this slice.

The public input is a friendly paragraph-style option array, consistent with
the existing application-facing paragraph style vocabulary. The facade is
responsible for translating that public authoring input into the semantic style
definition required by the document-local style pipeline.

02C must not require callers to construct `StyleRequirement` directly for this
application-authoring use case.

### 3.3 Named paragraph reference remains separate

Existing reference syntax remains conceptually distinct:

```php
$paragraph = new Paragraph('CVEntryTitle');
```

This means "use style `CVEntryTitle`".

It does not define or register the style.

The 02C definition API means "define generated style `CVEntryTitle` for this
logical document".

## 4. Semantic definition shape

The facade must delegate to the semantic document-local style model rather than
to `StyleMapper::registerParagraphStyle()` or another process-global registry.

The generated semantic definition must represent a paragraph style owned by the
current document.

The exact internal construction may use `StyleRequirement` directly or a small
internal helper, but there must be one semantic ownership path and no parallel
mutable style state.

The facade may use existing stateless paragraph option mapping where appropriate,
but mapping and ownership must remain separate responsibilities.

## 5. Duplicate and conflict semantics

02C adopts the STYLE-API-02B conflict contract.

For the same semantic paragraph-style identity:

- equivalent repeated definition is idempotent;
- conflicting repeated definition fails explicitly;
- silent last-write-wins is not allowed.

The implementation should delegate to `StyleContext` conflict semantics rather
than introducing a second duplicate/conflict policy in the facade.

Public exception type and exact wording may use the current semantic exception
behavior unless implementation evidence shows that a dedicated public exception
is necessary. 02C must not invent an exception hierarchy speculatively.

## 6. Authored template style semantics

A named paragraph style may already exist in the loaded ODT template.

02C must preserve the distinction between:

```text
authored template definition
```

and

```text
application-generated document-local definition
```

The facade must not silently overwrite an unrelated authored style definition.
Existing `StyleContext` resolution/materialization semantics remain authoritative
unless characterization shows a gap that must be handled explicitly.

02C tests must cover the authored-style case before implementation is considered
complete.

## 7. Lifecycle contract

The following usage is explicitly supported:

```php
$styles = $template->styles();
$template->load();
$styles->defineParagraph('Heading', [...]);
```

The retained facade must target the current logical document after `load()`.

It must not hold a stale `StyleContext`, old DOM reference, or copied registry
state from the previous logical document.

Preferred implementation direction:

```text
DocumentStyles
    -> resolves/delegates through owning OdtTemplate or a narrow current-context
       provider on each operation
```

A facade that stores a permanently captured old `StyleContext` is contrary to
this contract even if simple single-load tests pass.

## 8. Characterization required before implementation

02C requires focused characterization only for behavior touched by this slice.
It does not require closing unrelated STYLE-API-02 P1/P2 gaps.

Before or alongside the production change, tests must establish:

1. the current documented `StyleMapper::registerParagraphStyle()` + named
   `Paragraph` use case that the new API must replace functionally;
2. how authored paragraph styles are discovered/resolved in the current
   document;
3. equivalent duplicate semantic definition behavior;
4. conflicting duplicate semantic definition behavior;
5. document-context reset behavior across `OdtTemplate::load()`;
6. repeated `save()` / normal finalization behavior for a document-local named
   paragraph definition.

Characterization protects the useful behavior being migrated. It does not
require the new implementation to use the old process-global registry.

## 9. Implementation constraints

02C must remain a small slice.

Allowed production changes include only what is necessary to provide the new
facade and paragraph definition path, for example:

- a small document-style facade class;
- `OdtTemplate::styles()`;
- `defineParagraph()`;
- narrow internal delegation/mapping support required by that path;
- tests for the new public behavior and lifecycle.

02C must not:

- add `defineText()` merely for symmetry;
- add table, cell, graphic, frame, image, or fill-image definition APIs;
- expose `StyleContext` publicly;
- introduce a generic `StyleManager`;
- introduce a generic `defineStyle($family, ...)` method;
- remove or deprecate `StyleMapper::registerParagraphStyle()` in the same slice;
- retire `HasStyles`;
- clean up unrelated legacy getters/facades;
- refactor `StyleWriter` beyond what is strictly required for the new path;
- redesign paragraph option vocabulary;
- mix broad registry cleanup into the new facade implementation.

Those changes belong to later STYLE-API-02 slices.

## 10. Test contract

At minimum, focused automated tests must prove:

1. `OdtTemplate::styles()` returns a usable stable facade;
2. `defineParagraph()` creates a document-local named paragraph definition;
3. `Paragraph('Name')` can reference that generated definition;
4. no new process-global `StyleMapper` registration is required by the new
   path;
5. equivalent repeated definitions are idempotent;
6. conflicting repeated definitions fail explicitly;
7. an authored template style is not silently overwritten;
8. a retained facade follows the current logical document after `load()`;
9. styles from the prior logical document do not leak across `load()`;
10. repeated `save()` remains stable;
11. existing named-style compatibility behavior remains green.

Relevant existing integration and public sample smoke coverage should remain
green.

## 11. Validation / preflight

After implementation, normally run:

- focused 02C PHPUnit tests;
- relevant StyleContext / style-requirement tests;
- relevant named paragraph compatibility tests;
- PublicSampleSmokeTest;
- full `composer test`;
- PHP lint for changed/new PHP files and affected tests;
- `composer validate` if Composer metadata changes (not expected);
- `git diff --check`.

Because 02C changes style materialization behavior visible in generated ODT
files, a manual LibreOffice regression is required before final acceptance.

The regression should verify at least one generated named paragraph style in a
real ODT file and confirm that authored template styles continue to render
correctly.

## 12. Acceptance criteria

STYLE-API-02C is complete when:

1. `$template->styles()` is the canonical public document-style facade;
2. `$template->styles()->defineParagraph(...)` is implemented and documented at
   the API level required by the slice;
3. the new path is document-scoped and does not depend on process-global style
   registration;
4. the facade owns no duplicate mutable style state;
5. the facade remains valid across `load()` and targets the current document;
6. equivalent duplicate definitions are idempotent;
7. conflicting duplicate definitions fail explicitly;
8. authored template style semantics remain protected;
9. named paragraph references work with the generated document-local definition;
10. repeated save/finalization remains stable;
11. legacy registration APIs are unchanged in this slice;
12. focused, integration, full-suite, lint, diff, and manual LibreOffice
    validation are green.

## 13. Decision summary

The 02C target is deliberately small:

```text
Application code
    |
    +-- $template->styles()
            |
            +-- defineParagraph(name, options)
                    |
                    +-- semantic paragraph definition
                            |
                            +-- current OdtDocumentContext
                                    |
                                    +-- StyleContext
```

No new global registry is introduced.
No second style owner is introduced.
No additional style family is exposed merely for symmetry.

02C proves the public facade boundary and document lifecycle first. Later
STYLE-API-02 slices may then migrate old named-style registrations and retire
legacy infrastructure on top of that stable semantic foundation.

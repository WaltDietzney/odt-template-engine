# STYLE-API-02E — HasStyles Retirement Change Contract

Status: **ACCEPTED IMPLEMENTATION CONTRACT — SEMANTICS BEFORE IMPLEMENTATION**
Baseline: `develop` at `516de4d781a6700c4abbb69c7ec1c0ea01a7646a`
Contract branch: `architecture/style-api-02e-hasstyles-retirement-contract`

## 1. Purpose

STYLE-API-02E defines the intentional retirement of the historical `HasStyles`
architecture. It is based on the completed STYLE-API-02E investigation and
does not itself remove production code.

The contract separates four related but distinct surfaces:

1. the `HasStyles` interface;
2. `OdtElement implements HasStyles` and repeated concrete declarations;
3. `registerStyles()`;
4. `getStyleDefinitions()`.

They must not be removed as one undifferentiated compatibility mechanism.

The target architecture is the existing semantic requirement/resource pipeline:

```text
OdtElement
    -> semantic style/resource requirements
    -> OdtDocumentContext
    -> StyleContext
    -> semantic materializers
    -> native ODT DOM
```

The target is to retire the legacy contract after its observable behavior has
been characterized and any still-useful active behavior has been migrated.

## 2. Accepted architectural baseline

The following facts are established by current source, tests, and the
STYLE-CONTEXT / STYLE-API-02A–02D evidence:

- Normal semantic `OdtTemplate::setElement()` processing does not require the
  `HasStyles` contract.
- Modern structured insertion collects `StyleRequirement` values through
  `ownedElements()` and `getOwnStyleRequirements()` and owns them through the
  current `OdtDocumentContext` / `StyleContext`.
- Modern graphic, image, fill-image, table, table-cell, paragraph, and text
  paths already have semantic or resource-oriented replacement hooks.
- `registerStyles()` still performs direct legacy work for some elements,
  including static `StyleMapper` or frame/table-cell state changes.
- `getStyleDefinitions()` remains observable for some elements, especially
  `Paragraph`, `RichTableCell`, and `DrawTextBox`, but is not a universal
  semantic abstraction.
- Composite legacy methods in `RichText`, `RichTable`, and `ListElement` still
  contain interface-dependent guards or legacy traversal behavior.
- Public visibility and historical use do not by themselves require permanent
  retention of the legacy mechanism.

## 3. Inactive top-level compatibility branch

`OdtTemplate::registerStructuredHasStylesCompatibility()` contains an
unqualified check:

```php
if ($element instanceof HasStyles) {
    $this->registerStyles($element->getStyleDefinitions());
}
```

`OdtTemplate` is in the `OdtTemplateEngine` namespace and does not import
`OdtTemplateEngine\Contracts\HasStyles`. The unqualified name therefore
resolves to `OdtTemplateEngine\HasStyles`, which does not exist.

The existing STYLE-API-02A characterization proves that the intended top-level
`Contracts\HasStyles` dispatch is inactive for both normal and external
`OdtElement` implementations. The modern semantic path remains functional
independently.

This contract explicitly requires:

- do not add the missing import;
- do not repair the inactive branch;
- do not use the inactive branch as evidence that all legacy methods are dead;
- remove or leave the branch only through an explicit later implementation
  decision, without activating its historical behavior.

## 4. Separate retirement decisions

### 4.1 `src/Contracts/HasStyles.php`

Decision: **REMOVE**, after the staged migration gates in this contract pass.

The interface is legacy architecture rather than modern semantic authority. No
active normal semantic workflow requires its type identity. Its current
production role is limited to:

- inherited and repeated implementation declarations;
- interface-dependent guards in legacy composite methods;
- the inactive top-level check;
- public source-level compatibility and direct test assertions.

Deleting the interface immediately would break current class declarations,
imports, tests, and interface assertions. It may be deleted only after those
dependencies have been intentionally migrated or removed.

### 4.2 `OdtElement implements HasStyles`

Decision: **MIGRATE, THEN REMOVE**.

`OdtElement` must no longer implement `HasStyles` in the target state.
Concrete elements must also stop declaring or repeating the interface.

This change is not the first slice because interface-dependent composite guards
must be handled first. Removing the declarations while leaving those guards
would change their runtime conditions and could silently alter direct legacy
composite calls.

### 4.3 `registerStyles()`

Decision: **MIGRATE, THEN REMOVE WHERE REDUNDANT**.

The method is not the modern semantic authority. It still has observable direct
behavior, including:

- `Paragraph` registration of legacy text and paragraph styles;
- `RichTableCell` table-cell style refresh/registration;
- `DrawTextBox` frame-style registration into static compatibility state;
- recursive calls from `RichText` and `RichTable` when their legacy methods are
  directly invoked;
- public direct invocation and subclass override observability.

Useful behavior must first be represented by existing semantic requirement or
resource hooks, or explicitly classified as compatibility-only. No replacement
global registry may be introduced.

Empty or redundant implementations, such as the `ListElement` no-op, may be
removed once interface and direct-call characterization is complete.

### 4.4 `getStyleDefinitions()`

Decision: **MIGRATE, THEN REMOVE WHERE REDUNDANT**.

The method is not a coherent semantic abstraction across element families:

- `Paragraph` exposes legacy inline and paragraph definitions;
- `RichTableCell` exposes a table-cell definition;
- `DrawTextBox` exposes a frame definition;
- `ImageElement` returns an empty definition;
- `RichTable` inherits a traversal that does not represent its row/cell
  storage;
- `ListElement` effectively exposes no definitions.

The normal semantic path already has requirement and resource replacements.
Nevertheless, direct callers, compatibility tests, and some composite legacy
paths make removal observable. Each implementation and caller must therefore
be migrated or explicitly retired rather than mechanically deleted.

## 5. Mandatory P0 characterization gate

No production retirement implementation may begin until focused P0 tests have
been added and pass. Existing STYLE-API-02A inactive-dispatch tests may be
referenced and must not be duplicated unnecessarily.

### P0.1 Direct composite `registerStyles()` dispatch

Characterize:

- `RichText` dispatch to a child override of `registerStyles()`;
- `RichTable` dispatch to a cell override of `registerStyles()`.

The tests must distinguish interface-dependent dispatch from merely direct
method invocation and record any static registry side effects.

Decision protected: removing interface guards or methods must not silently
remove still-observable nested legacy behavior.

### P0.2 Concrete `getStyleDefinitions()` behavior matrix

Characterize direct behavior for:

- `Paragraph`;
- `RichTableCell`;
- `DrawTextBox`;
- `ImageElement`;
- `RichTable`;
- `ListElement`.

The matrix must record returned shape, style family, empty behavior, and any
state mutation or dependence on construction/order.

Decision protected: meaningful legacy projections must be migrated or removed
explicitly, while empty/inconsistent methods are not mistaken for semantic
requirements.

### P0.3 Modern versus legacy semantic equivalence

Characterize a normal structured insertion using semantic requirements and
verify that required styles are materialized without requiring public legacy
`registerStyles()` or `getStyleDefinitions()` dispatch.

This must cover representative paragraph/text and at least the relevant
composite/graphic/table paths already supported by the semantic pipeline.

Decision protected: retirement must preserve normal semantic insertion behavior
while allowing legacy implementation machinery to be removed.

### P0.4 External subclass and direct-call observability

Characterize public external-style subclasses that override the legacy methods,
including direct invocation and any invocation through `RichText` or
`RichTable` legacy traversal.

The existing STYLE-API-02A tests establish that normal `setElement()` does not
invoke the intended top-level `Contracts\HasStyles` branch. They do not by
themselves characterize every direct public or composite call.

Decision protected: any breaking removal of public methods or interface
identity is explicit rather than accidental.

## 6. Implementation slices

The implementation must remain staged and narrow.

### 02E-P0 — Characterization gate only

Add and run the focused P0 tests in Section 5. Do not remove the interface,
declarations, or legacy methods in this slice.

### 02E-A — Remove interface dependency from replaced internal traversal

After P0, remove or replace interface-dependent guards in composite/internal
control flow where semantic traversal or typed element ownership already
provides the required behavior.

This slice must not delete public legacy methods unless the specific method is
proven redundant and its direct behavior is separately covered.

Do not repair `OdtTemplate`'s inactive top-level branch.

### 02E-B — Migrate or remove `registerStyles()` paths

For each implementation:

1. identify its unique side effects;
2. map useful modern behavior to existing semantic/resource hooks;
3. preserve compatibility-only behavior only when explicitly required by the
   accepted decision for that slice;
4. remove redundant implementations and callers.

Do not create a replacement global style registry. General `StyleMapper`,
`StyleWriter`, or importer cleanup remains outside this slice.

### 02E-C — Migrate or remove `getStyleDefinitions()` paths

Migrate useful callers to semantic requirements/resource hooks or remove them
when their behavior is redundant. Handle `Paragraph`, `RichTableCell`, and
`DrawTextBox` explicitly rather than relying on the base implementation.

The inconsistent `RichTable` and empty `ImageElement`/`ListElement` behavior
must be characterized before removal.

### 02E-D — Remove interface declarations and the interface

When no interface-dependent runtime path or supported contract remains:

- remove `implements HasStyles` from `OdtElement` and concrete elements;
- remove remaining `use ... Contracts\HasStyles` imports;
- remove `src/Contracts/HasStyles.php`;
- remove dead interface assertions and guards.

The inactive `OdtTemplate` branch must not be made active during this slice.

### 02E-E — Test/sample/diagnostic cleanup

Only after production migration is complete, update or remove tests, samples,
and diagnostics that intentionally exposed the retired API.

Historical architecture evidence must not be rewritten to erase the migration
record. Any user-facing diagnostic or sample change must be limited to the
retired HasStyles surface.

## 7. Compatibility policy

Characterization protects understanding and enables an intentional decision. It
does not automatically preserve the old mechanism forever.

Breaking removal of public legacy methods or interface identity is acceptable
when all of the following are true:

- active semantics have been migrated to the semantic document-local path;
- direct, composite, and subclass behavior has been characterized;
- the removal is explicit in this contract and its implementation slice;
- no supported modern workflow depends on the legacy surface;
- compatibility tests are updated only after the production decision is made.

Methods must not be retained solely because they are public. Conversely, public
or protected override behavior must not disappear accidentally inside unrelated
cleanup.

## 8. Invariants for implementation

Every implementation slice must preserve:

- normal `setElement()` semantic output and lifecycle behavior;
- document-local `StyleContext` ownership;
- existing `StyleRequirement` conflict and materialization semantics;
- authored-template style authority;
- package-owned physical resources;
- current `load()` and repeated save behavior unless explicitly covered by a
  later contract;
- the non-dispatching behavior of the unqualified top-level `HasStyles` check;
- no global current-document pointer;
- no duplicate mutable semantic style registry.

## 9. Explicit non-goals

This contract does not authorize:

- general `StyleMapper` registry cleanup beyond what is directly required by
  HasStyles retirement;
- general `HtmlImporter` paragraph registration cleanup;
- `StyleWriter` cleanup;
- table-cell mapper consolidation;
- text registry cleanup;
- unrelated legacy getter or facade cleanup;
- new document-style families;
- `defineText()`, `defineTable()`, `defineGraphic()`, or generic
  `defineStyle()` APIs;
- template syntax changes;
- lifecycle redesign;
- unrelated sample or `samples/output/` changes;
- repair of the inactive `OdtTemplate` compatibility branch.

## 10. Evidence and known discrepancy

The STYLE-API-02A characterization and current source supersede the older
description of the top-level dispatch as active.

`STYLE_CONTEXT_01_FINAL_AUDIT.md` historically described HasStyles top-level
compatibility registration as active and classified it as retained subclass
compatibility. That description does not match the current namespace-resolved
behavior. The newer STYLE-API-02A characterization documents the discrepancy
and proves the branch is inactive.

The historical audit must not be rewritten in this contract slice. Future
implementation work must use the newer characterization and current code as
the behavioral baseline.

## 11. Contract acceptance criteria

STYLE-API-02E retirement implementation is complete only when:

1. all mandatory P0 characterization tests pass;
2. normal semantic insertion remains green and does not require HasStyles;
3. useful direct/composite legacy behavior has either been migrated or
   explicitly retired;
4. `registerStyles()` and `getStyleDefinitions()` have been independently
   assessed and removed only where redundant;
5. no active interface-dependent path remains;
6. `OdtElement` and concrete elements no longer depend on `HasStyles`;
7. `src/Contracts/HasStyles.php` is removed;
8. the inactive top-level compatibility branch has not been repaired;
9. compatibility and semantic tests accurately reflect the intentional new
   public surface;
10. unrelated StyleMapper, StyleWriter, importer, lifecycle, and layout work
    remains unchanged.

## 12. Recommended validation per implementation slice

At minimum, each production slice should run:

- focused HasStyles characterization tests;
- relevant semantic structured-element tests;
- relevant compatibility tests;
- `PublicSampleSmokeTest`;
- full `composer test` before final closeout;
- PHP lint for changed PHP files;
- `git diff --check`.

Rendering-visible changes require the existing LibreOffice regression workflow.
The architecture contract itself is documentation-only and requires strict
documentation validation plus `git diff --check`.

## 13. Final decision

`HasStyles` is legacy architecture targeted for retirement. The interface,
implementation declarations, `registerStyles()`, and `getStyleDefinitions()`
have separate retirement decisions and separate evidence gates.

The accepted endpoint is a semantic `StyleRequirement`/resource ownership model
through `OdtDocumentContext` and `StyleContext`, with no repaired top-level
HasStyles dispatch and no replacement global registry.

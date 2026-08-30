# STYLE-CONTEXT-01E — StyleMapper Compatibility Facade

## Purpose

STYLE-CONTEXT-01E isolates the remaining process-wide mutable registration state behind an explicitly legacy compatibility boundary without changing public behavior prematurely.

The goal of this slice is not to remove legacy static APIs. The goal is to stop treating `StyleMapper` itself as the architectural owner of mutable style-registration state.

## Starting point

STYLE-CONTEXT-01A through 01D established the following:

- mutable style-registration state belongs to one logical editable ODT document;
- `OdtDocumentContext` is the semantic lifetime boundary for document-owned style state;
- `StyleContext` now owns pending paragraph-style requirements for the migrated structured-element path;
- `OdtTemplate::setElement()` registers paragraph-style requirements in the current document's `StyleContext` before DOM materialization;
- existing DOM generation and persisted `styles.xml` output remain unchanged for that path;
- process-wide static `StyleMapper` registries still exist and are still read by `StyleWriter::writeAllStyles()`.

The remaining static registration state includes text, paragraph, table-cell, image, fill-image, frame, table, and font-related data. This slice must not migrate all of those families.

## Current compatibility problem

`StyleMapper` currently combines two different responsibilities:

1. pure or mostly pure mapping helpers, such as `mapParagraphStyle()` and `mapTableCellStyleOptions()`;
2. process-wide mutable legacy registration state, such as `registerParagraphStyle()` / `getParagraphStyles()` and related families.

Those responsibilities have different architectural lifetimes.

Mapping helpers are reusable value transformations. Mutable registrations are compatibility-sensitive application state.

Keeping both inside `StyleMapper` makes the global registry look like the natural owner even though STYLE-CONTEXT-01B established that it is not.

## Decision

Introduce a dedicated internal compatibility registry for legacy process-wide registrations and make `StyleMapper` a facade over that registry for the migrated legacy registration surface.

Conceptually:

```text
legacy application code
    ↓
StyleMapper::registerParagraphStyle(...)
    ↓ compatibility facade
LegacyStyleRegistry
    ↓
legacy finalization path
StyleWriter
```

while the modern document-aware path remains:

```text
structured element
    ↓
OdtTemplate::setElement()
    ↓
OdtDocumentContext::styleContext()
    ↓
current document styles.xml
```

The two paths must remain explicitly distinct in 01E.

## Scope

01E should migrate the smallest proven compatibility surface first: paragraph-style legacy registration.

The target is:

- move the storage responsibility for legacy paragraph registrations out of `StyleMapper` into a dedicated internal compatibility registry;
- keep `StyleMapper::registerParagraphStyle()` callable with the same signature;
- keep `StyleMapper::getParagraphStyles()` callable with the same signature and return shape;
- preserve current first-registration-wins behavior for duplicate legacy paragraph-style names;
- preserve current `StyleWriter::writeAllStyles()` behavior for legacy paragraph registrations;
- preserve the existing STYLE-CONTEXT-01A characterization of cross-document leakage for this legacy path during 01E;
- make the legacy-global nature explicit in class naming, documentation, and tests.

This is an architectural quarantine slice, not the final ownership fix for context-free legacy calls.

## Required compatibility behavior

For legacy paragraph registration, current behavior must remain unchanged in 01E:

- first registration of a name stores the definition;
- a later registration with the same name does not overwrite it;
- `getParagraphStyles()` returns the accumulated process-wide registry;
- `StyleWriter::writeAllStyles()` still sees those registrations;
- an explicitly registered legacy paragraph style can still leak into a later document in the same PHP process.

That leakage is undesirable architecture but is deliberately preserved until the finalization migration has an approved compatibility strategy.

The existing characterization test must therefore remain green and must not be inverted in 01E.

## Why not bind static calls to a current document

A context-free call such as:

```php
StyleMapper::registerParagraphStyle('Example', $definition);
```

contains no document identity.

01E must not introduce:

- a process-global current-document pointer;
- constructor-time resets;
- implicit last-created-document semantics;
- hidden thread/process-local binding;
- timing-dependent ownership guesses.

Those would disguise global state rather than solve ownership.

## Internal design constraints

The compatibility registry should:

- be internal infrastructure, not a new public application API;
- have a name that makes its legacy/process-wide semantics obvious;
- preserve existing array shapes exactly;
- avoid normalization or conflict-policy changes;
- avoid dependencies on `OdtDocumentContext` or `StyleContext`;
- contain only the narrowly migrated legacy family in this slice unless tests prove another family must move with it atomically.

`StyleMapper` should remain the public/static compatibility facade.

## Tests

Focused tests should prove:

1. `StyleMapper::registerParagraphStyle()` still exposes the registered style through `StyleMapper::getParagraphStyles()`;
2. duplicate same-name registration preserves the first definition exactly as before;
3. `StyleWriter::writeAllStyles()` still materializes a legacy registered paragraph style into `styles.xml`;
4. the existing cross-document leakage characterization remains green;
5. the document-scoped `setElement()` / `StyleContext` integration remains independent and green.

Tests for the compatibility registry itself are useful if they make the ownership boundary explicit, but behavior through the `StyleMapper` facade is the primary compatibility contract.

## Non-goals

01E does not:

- remove or deprecate `StyleMapper` public methods;
- change `StyleWriter` finalization ownership;
- eliminate the legacy cross-document leak;
- migrate text, image, frame, table, table-cell, fill-image, or font registries;
- change mapping helpers;
- change document-scoped `StyleContext` behavior;
- introduce a public style API;
- redesign `refresh()` or `save()`;
- change ODF output.

Those concerns belong to later STYLE-CONTEXT slices, especially finalization and multi-document regression work.

## Exit criteria

STYLE-CONTEXT-01E is complete when:

- legacy paragraph-style mutable storage no longer lives directly in `StyleMapper`;
- `StyleMapper` remains behaviorally compatible as a static facade;
- legacy `StyleWriter` output is unchanged;
- the intentional 01A leakage characterization still passes;
- the 01D document-scoped structured-element path still passes;
- full automated validation is green;
- no unrelated style family or rendering behavior changed.

## Architecture outcome

After 01E, the codebase should make the distinction visible in its structure:

> `StyleContext` owns modern document-scoped pending style requirements. A dedicated legacy registry owns compatibility-only process-wide registrations. `StyleMapper` is no longer the conceptual owner of both worlds.

This prepares STYLE-CONTEXT-01F to address finalization and multi-document isolation without first having to untangle mapping helpers from legacy storage.
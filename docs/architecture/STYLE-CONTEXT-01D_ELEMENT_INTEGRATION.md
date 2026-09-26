# STYLE-CONTEXT-01D — Structured Element Integration

## Purpose

STYLE-CONTEXT-01C introduced a document-owned `StyleContext` and proved ownership, conflict handling, cross-document isolation, and reset semantics without changing rendering behavior.

01D connects one real structured-element style path to that context. The slice remains deliberately narrow: **paragraph style requirements exposed by structured `OdtElement` instances inserted through `OdtTemplate::setElement()`**.

This is the first production integration of document-scoped style registration. It is not a migration of the whole style system.

## Existing path to preserve

Today `OdtTemplate::setElement()` collects:

```php
$paragraphStyles = method_exists($element, 'getRequiredParagraphStyles')
    ? $element->getRequiredParagraphStyles()
    : [];
```

and then writes those definitions directly to the current `styles.xml` through:

```php
$this->ensureParagraphStylesExist($paragraphStyles);
```

This path is already document-local in its XML output. 01D must preserve that output while making the pending requirement explicitly document-owned before materialization.

## 01D semantic flow

The target flow for this slice is:

```text
Paragraph / RichText
    ↓ getRequiredParagraphStyles()
OdtTemplate::setElement()
    ↓
current OdtDocumentContext::styleContext()
    ↓ registerParagraphStyle(name, definition)
current document only
    ↓
existing paragraph-style DOM materialization
    ↓
current styles.xml
```

The `StyleContext` becomes the authoritative owner of **pending paragraph requirements for this migrated path**. The existing `ensureParagraphStylesExist()` behavior remains the compatibility-preserving DOM writer during 01D.

## Required implementation behavior

### Registration before DOM mutation

For every paragraph style requirement returned by the element, `setElement()` must first register the requirement in the current document's `StyleContext`.

Equivalent duplicate requirements are idempotent according to 01C.

A same-name/different-definition conflict must fail before the new conflicting style is written into `styles.xml` or the structured placeholder is replaced.

### Materialization from document-owned state

After successful registration, paragraph styles required by the current element may still be passed to the existing DOM writer in this slice. 01D does not need to redesign style finalization.

However, the code should make the ownership transition visible and explicit: the style is first accepted by the document context, then materialized into that document.

A small document-style registration helper/service is acceptable if it keeps `OdtTemplate` orchestration readable. Avoid introducing a generic style framework in this slice.

### Cross-document isolation

Two `OdtTemplate` instances used in one PHP process must accumulate paragraph style requirements independently through `setElement()`.

Inserting an element with a named paragraph style into document A must not populate document B's `StyleContext`.

Existing output isolation must remain green.

### `load()` reset

The 01C reset contract remains authoritative. After a document reset via `load()`, paragraph requirements registered through prior `setElement()` calls must no longer be present in the document's pending `StyleContext`.

No extra constructor/global reset is allowed.

## Compatibility constraints

01D must not change:

- public `OdtTemplate::setElement()` signature;
- protected facade methods used by subclasses;
- `Paragraph`, `RichText`, or their public APIs unless strictly required by evidence;
- text-style registration behavior;
- `StyleMapper` static registration behavior;
- `StyleWriter` finalization behavior;
- image, frame, table, table-cell, font, fill-image, or list style handling;
- output placement of paragraph styles in `styles.xml`;
- repeated save/render behavior;
- the 01A explicit static leakage characterization.

The old static paragraph registry is therefore still allowed to leak through its separately characterized legacy path. 01D only migrates the structured-element `setElement()` paragraph-requirement path.

## Conflict semantics and existing DOM styles

01D conflict detection applies to multiple **pending registrations in the document-owned `StyleContext`**.

Do not silently broaden the slice into full comparison against every pre-existing paragraph style already authored in `styles.xml`. Existing DOM definitions remain authoritative document data and `ensureParagraphStylesExist()` currently skips an already existing style name.

The semantics of a pending requirement whose name collides with a different pre-existing DOM definition need separate characterization before changing compatibility behavior.

## Tests

Add focused integration coverage proving at least:

1. `setElement()` registers an element's named paragraph requirement in that template's `StyleContext`;
2. two templates receive independent requirements through the public structured-element path;
3. equivalent repeated requirements are accepted without duplication/conflict;
4. conflicting pending requirements with the same name fail before the second element is materialized;
5. the resulting paragraph style in saved `styles.xml` remains compatible with the existing output;
6. existing `StylePipelineP2BTest` and `StyleContextCharacterizationTest` remain green;
7. `load()` resets requirements previously collected through `setElement()` if practical through the existing facade test seam.

Tests may expose `documentContext()` through a test-only subclass of `OdtTemplate`; do not add a public production accessor solely for testing.

## Non-goals

01D does not:

- route `StyleMapper::registerParagraphStyle()` to `StyleContext`;
- remove any static registry;
- redesign `StyleWriter`;
- move all paragraph DOM generation into `StyleContext`;
- add a public style API;
- migrate inline text styles;
- migrate other ODF style families;
- change `refresh()` semantics.

## Completion criterion

01D is complete when a real `OdtTemplate::setElement()` paragraph-style requirement is first owned by the current document's `StyleContext`, then rendered with unchanged ODT semantics, with focused isolation/conflict/reset regression coverage and no unrelated style migration.

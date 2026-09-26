# STYLE-API-02I — Documentation / API Consistency Closeout

Status: **DOCUMENTATION CLOSEOUT — CURRENT STYLE API BASELINE**

## Purpose

STYLE-API-02I closes the STYLE-API-02 series by aligning public documentation, planning documents, and repository guidance with the architecture established by STYLE-API-02B through STYLE-API-02H.

This document records the current style API baseline. Earlier audits, characterization documents, and change contracts remain historical evidence and are not rewritten to pretend that their intermediate compatibility state never existed.

## Current public authoring model

### Application authoring

Use element constructors and fluent APIs with friendly style options.

Examples include:

- `Paragraph` text and paragraph options;
- `RichTable` / `RichTableCell` options;
- image/frame element options;
- normal structured insertion through `setElement()`.

### Document style authoring

Use the document-oriented style facade for generated reusable named paragraph styles:

```php
$template->styles()->defineParagraph('ReportHeading', [
    'margin-top' => '0.3cm',
    'margin-bottom' => '0.1cm',
]);

$paragraph = new Paragraph('ReportHeading');
```

The definition belongs to the current logical document.

A named paragraph reference can also target a style already authored in the LibreOffice/ODT template. A named reference does not define or register a style by itself.

### Custom structured-element extension

The canonical semantic extension hooks are:

- `getOwnStyleRequirements()`;
- `ownedElements()`;
- typed dependency/resource hooks;
- `getOwnImageAssets()` where physical resources are produced directly;
- `toDomNode()`.

Traversal belongs to collectors rather than duplicate paragraph/text subtree getter APIs.

## Current internal boundary

```text
Application element options
        │
DocumentStyles::defineParagraph()
        │
Custom StyleRequirement producers
        ▼
StyleRequirement / typed dependencies
        ▼
OdtDocumentContext / StyleContext
        ▼
collectors and materializers
        ▼
ODF XML / package resources
```

`StyleContext` is document-local semantic authority, not application-facing global state.

`StyleMapper` is a stateless mapping and identity utility.

`StyleWriter` is a narrow explicit-input serialization helper, not a style registry or application authoring API.

## Retired compatibility concepts

The current architecture no longer includes:

- `HasStyles`;
- `LegacyStyleRegistry`;
- StyleMapper paragraph/text registration and registry getter facades;
- process-global paragraph/text reference fallback in `StyleContext`;
- redundant paragraph/text array requirement getters on `OdtElement` and aggregate elements;
- protected generic `OdtTemplate::registerStyles(array)`.

These are intentional compatibility retirements from STYLE-API-02E through STYLE-API-02H.

## Remaining bounded compatibility

Some graphic/resource traversal remains for active legacy assign/render and section-mutation callers. This is bounded compatibility traversal, not a second style ownership model.

Lifecycle helpers such as `ensureTextStylesExist()` and `ensureParagraphStylesExist()` remain where they still serve template/document preparation.

## Documentation policy

Historical architecture documents under `docs/architecture/` retain the state and decisions that were true when they were written.

Current public documentation must describe the current API rather than historical compatibility mechanisms.

When historical documents and current public guides differ, this closeout and the current `develop` source code describe the active STYLE-API-02 baseline.

## Planning impact

STYLE-API-02 is complete architectural/API debt after this closeout.

Future style-related work should be capability-driven, for example:

- document defaults;
- frame positioning;
- table geometry;
- list layout;
- page/master-style and page-flow semantics.

It should not reintroduce process-global style registries or generic/symmetrical `define*()` methods without an independently justified ODF semantic requirement.

## Closeout result

The current style architecture has one coherent public story:

1. friendly element options for normal authoring;
2. document-local named paragraph definitions through `$template->styles()`;
3. semantic `StyleRequirement`-based extension for custom structured elements;
4. collector-owned traversal and document-local materialization internally.

This is the final STYLE-API-02 documentation baseline.

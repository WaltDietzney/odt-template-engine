# STYLE-CONTEXT-01A — Style State Audit and Characterization

## Purpose

This slice maps the current style-state ownership and lifecycle before any `StyleContext` implementation is designed.

The goal is characterization, not repair. In particular, this slice does **not** reset static state, change public APIs, or move style ownership yet.

## Current architecture baseline

The post-ARCH-07 / PRODUCT-01 architecture already gives document DOM state an explicit owner through `OdtPackage` and `OdtDocumentContext`. Style state is only partially aligned with that ownership model.

There are currently two distinct style paths:

1. document-local style collection/materialization used by normal structured elements;
2. process-wide static registries retained in `StyleMapper` and parts of `StyleWriter`.

That distinction is central to STYLE-CONTEXT-01.

## Static state inventory

### `StyleMapper`

`StyleMapper` is not only a mapper. It also owns mutable process-wide registries:

- `registeredTextStyles`;
- `textStyles`;
- `registeredParagraphStyles`;
- `registeredTableCellStyles`;
- `tableCellStyles`;
- `registeredImageStyles`;
- `registeredFillImages`;
- `registeredFonts`;
- public `frameStyles`;
- public `tableStyles`.

The public registration/read APIs include:

- `registerTextStyle()` / `setTextStyle()` / `getTextStyles()`;
- `registerParagraphStyle()` / `getParagraphStyles()`;
- `registerTableCellStyle()` / `getRegisteredTableCellStyles()`;
- `registerImageStyle()` / `getRegisteredImageStyles()`;
- `registerFillImage()` / `getRegisteredFillImages()`;
- `addFrameStyle()` / `getFrameStyles()`;
- `registerTableStyle()` / `getRegisteredTableStyles()`.

`mapTextStyleOptions()` also mutates global font registration as a side effect when a font family or monospace style is mapped. This means some methods named as pure mapping helpers are not actually stateless.

There is also an internal inconsistency worth preserving as evidence rather than silently fixing: table-cell state is represented by both `registeredTableCellStyles` and `tableCellStyles`, while the active register/get pair uses `tableCellStyles`.

### `StyleWriter`

`StyleWriter` also contains process-wide mutable state:

- `generatedTextStyles`;
- `fontsUsed`.

`writeAllStyles()` does not use `generatedTextStyles`, but it reads the global `StyleMapper` registries for text, paragraph, graphic/frame, table-cell, and table styles.

The older/specialized `writeTextStyles()` path uses `generatedTextStyles` to suppress duplicate writes across calls. Because that cache is process-wide rather than DOM-scoped, it can theoretically suppress a required style in a later document if that path is used across multiple documents.

`writeFontFaces()` similarly consumes process-wide `fontsUsed`. By contrast, the current `writeAllStyles()` font-face phase deliberately scans the current `styles.xml` DOM for font references, which is already closer to document-scoped semantics.

## Active document-local style path

The normal `Paragraph` / `RichText` path already contains a useful model for the future architecture.

`Paragraph` keeps its generated text styles and paragraph-style options on the element instance:

- `textStyleMap`;
- `paragraphStyle`;
- `paragraphStyleOptions`.

`RichText` aggregates those local requirements through:

- `getRequiredStyles()`;
- `getRequiredParagraphStyles()`.

`OdtTemplate::setElement()` then obtains those required styles and writes missing definitions directly into the **current document's** `styles.xml` using `ensureTextStylesExist()` and `ensureParagraphStylesExist()`.

This path does not require a process-wide style registry for the normal structured-element case.

The existing `StylePipelineP2BTest::testStylesFromOneDocumentDoNotAppearInTheNextDocument()` is important characterization evidence: styles collected through this ordinary structured-element path remain document-local across two `OdtTemplate` instances in one PHP process.

This means STYLE-CONTEXT-01 must not treat all style generation as equally broken. The confirmed problem is specifically the process-wide registration/finalization path.

## Confirmed leakage path

`OdtTemplate::save()` calls:

```php
StyleWriter::writeAllStyles($this->documentContext()->stylesDom());
```

`StyleWriter::writeAllStyles()` then reads the static `StyleMapper` registries.

There is no document identity associated with those registrations and no lifecycle boundary between two `OdtTemplate` instances. `OdtTemplate::__construct()` and `load()` do not reset the registries.

Therefore an explicitly registered style can be written into a later unrelated document in the same PHP process.

`StyleContextCharacterizationTest::testExplicitParagraphRegistrationLeaksIntoLaterDocumentInSameProcess()` records this behavior deliberately. The test runs in a separate PHPUnit process so that characterization of the leak does not contaminate unrelated tests.

This behavior is undesirable architecturally, but it is the current behavior and must remain characterized until an explicit ownership change is approved.

## Registration entry points inside elements

Several element classes still contain direct `StyleMapper` registration calls.

### `Paragraph`

`Paragraph::registerStyles()` pushes local text and paragraph definitions into the global registries.

`Paragraph::toDomNode()` also contains a fallback that calls `StyleMapper::registerTextStyle()` when styled text has no precomputed style name.

The normal `addText()` path already computes a style name and stores the definition locally, so the fallback is not necessarily the dominant path. It should be characterized before removal or rerouting.

### `ImageElement`

`ImageElement::setStyle()` and `ImageElement::toDomNode()` call `StyleMapper::registerImageStyle()`.

This is process-wide state, although image style injection/finalization uses additional compatibility logic in `OdtTemplate`. IMAGE/FRAME work must not be pulled into this slice; the finding only establishes that image-style ownership is part of the future StyleContext boundary analysis.

### Other style families

Frame, table, table-cell, fill-image, and font registries are also process-wide and need usage-path characterization in later slices before migration.

## Compatibility / legacy observations

The audit found a suspicious compatibility path in `OdtTemplate::setElement()`:

```php
if ($element instanceof HasStyles) {
    $this->registerStyles($element->getStyleDefinitions());
}
```

`OdtTemplate.php` currently imports `StyleWriter` and `StyleMapper` but not `OdtTemplateEngine\Contracts\HasStyles`. In that namespace, the unqualified `HasStyles` reference therefore does not obviously refer to the contract implemented by `RichText`, `Paragraph`, and other structured elements.

The subsequent `OdtTemplate::registerStyles()` implementation also expects definitions with `family` / `properties` structure and calls `StyleWriter::styleAlreadyExists()` and `StyleWriter::appendStyleToStylesXml()`. In the current `StyleWriter`, `styleAlreadyExists()` is private and no `appendStyleToStylesXml()` method is present.

This is a code/documentation contradiction that must **not** be silently repaired during 01A. The fact that current structured-element tests are green suggests this branch is inactive or bypassed in normal operation. A later characterization slice should prove its actual reachability before any compatibility decision.

## Ownership map

Current state can be summarized as:

```text
Element instance
  ├── local text/paragraph requirements
  │      ↓
  │   OdtTemplate::setElement()
  │      ↓
  │   current styles.xml DOM                 [document-local]
  │
  └── legacy/direct registration calls
         ↓
      StyleMapper static registries          [process-wide]
         ↓
      StyleWriter::writeAllStyles()
         ↓
      whichever document is being saved      [leakage risk]
```

The architectural target is not yet an approved class design, but the ownership requirement is clear:

```text
OdtDocumentContext / document owner
        ↓
 document-scoped style state
        ↓
 current document finalization
```

Legacy static entry points may later become compatibility facades, but they must not remain the authoritative owner of mutable document style state.

## Characterization conclusions

1. **Confirmed:** explicit static style registration is process-wide and can leak into later documents.
2. **Confirmed:** the normal RichText/Paragraph required-style path is already largely document-local and has regression coverage proving cross-document isolation.
3. **Confirmed:** `StyleWriter::writeAllStyles()` is the key bridge that converts global `StyleMapper` registry state into the current document.
4. **Confirmed:** mapping and registration responsibilities are mixed inside `StyleMapper`; at least font mapping has mutation side effects.
5. **Confirmed:** additional process-wide registries exist for table cells, images, fill images, frames, tables, and fonts.
6. **Confirmed:** `StyleWriter` itself contains global caches used by older/specialized paths.
7. **Needs characterization before change:** reachability and compatibility significance of element `registerStyles()` methods and the `OdtTemplate::registerStyles()` compatibility block.
8. **Needs characterization before change:** which image/frame/table/table-cell/fill-image registration paths are authoritative versus redundant compatibility paths.

## Contract for the next slice

STYLE-CONTEXT-01B should define ownership/lifecycle semantics before implementation.

It should answer at minimum:

- what constitutes one style context;
- whether the context belongs directly to `OdtDocumentContext`, `OdtPackage`, or another document-owned collaborator;
- how style registration behaves across `load()`, `render()`, `save()`, repeated `save()`, and `refresh()`;
- how legacy static registration APIs can remain compatible without becoming authoritative global state;
- how externally constructed elements hand style requirements to a document without receiving a global singleton;
- whether explicit named styles and generated automatic styles belong to the same context or separate registries;
- how duplicate names with conflicting definitions are handled within one document.

No implementation should begin until those semantics are documented.

## Scope of 01A

This slice intentionally changes only:

- architecture documentation;
- characterization coverage for the confirmed explicit-registration leak.

It does not change production behavior, public APIs, style output, or compatibility behavior.

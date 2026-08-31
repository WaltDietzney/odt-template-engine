# STYLE-CONTEXT-01F-C — Text Styles and Fonts

## Scope

This slice migrates the active structured-element text-style requirement path
and font discovery toward document-owned state. It does not migrate graphic,
image, frame, table, table-cell, list or other style families.

## Current evidence

`Paragraph::addText()` creates a named local entry in `textStyleMap`, and
`RichText::getRequiredStyles()` exposes those requirements. During
`OdtTemplate::setElement()`, the requirements are written to the current
styles DOM and are already isolated between simultaneous documents.

The same call also invokes the compatibility `registerStyles()` path, but the
normal `setElement()` flow does not need the global text registry for its
materialized output.

The remaining legacy paths are:

- `StyleMapper::registerTextStyle()` / `setTextStyle()` and
  `getTextStyles()`;
- direct `StyleWriter::writeAllStyles($dom)`;
- specialized `StyleWriter::writeTextStyles()` and `writeFontFaces()`.

`StyleMapper::mapTextStyleOptions()` currently records font families in a
process-wide array as a side effect. The main `writeAllStyles()` path instead
discovers referenced font names from the current styles DOM.

## Document-owned text requirements

`OdtDocumentContext::styleContext()` owns pending text-style requirements for
the current document. `StyleContext` stores raw text-style definitions keyed by
their ODF style name.

Registration is document-local:

- equivalent same-name definitions are idempotent;
- a same-name, different pending definition raises an explicit conflict;
- `replaceCoreDocuments()` / `load()` resets pending text requirements while
  retaining the `StyleContext` collaborator.

Existing styles in the loaded `styles.xml` remain authoritative. The existing
materializer continues to skip an already-present style name; comparing a
pending definition with an authored DOM style is outside this slice.

## Active materialization

`OdtTemplate::setElement()` registers every `getRequiredStyles()` definition
with the current `StyleContext` before immediate text-style materialization.
The existing DOM writer remains responsible for preserving the current
`styles.xml` placement and output shape.

This keeps current DOM observability and avoids a broad finalization rewrite.
The structured path does not depend on the process-wide text registry.

## Mapping and fonts

`StyleMapper::mapTextStyleOptions()` becomes a value transformation only. It
continues to return the same mapped ODF attributes, but no longer records
font-family or monospace choices in process-wide font state.

Font-face declarations for the active path are derived from font references in
the current document's styles DOM. This preserves authored declarations,
prevents one document's generated font usage from appearing in another, and
avoids font-file embedding concerns. Repeated finalization remains
idempotent.

`StyleMapper::getRegisteredFontsXml()` remains as an existing compatibility
method, but mapping no longer populates its legacy state. No new font
registration API is introduced.

## Legacy compatibility

The static text-style facade remains available:

- `registerTextStyle()`, `setTextStyle()` and `getTextStyles()` retain their
  existing process-wide storage and array/name behavior;
- direct `StyleWriter::writeAllStyles($dom)` continues to consume those
  registrations by default;
- the specialized `writeTextStyles()` / `writeFontFaces()` helpers remain
  unchanged compatibility paths with their existing static caches.

`OdtTemplate::save()` and `refresh()` no longer import global legacy text
styles into the current document. They retain the legacy writer behavior for
other families outside this slice and use only current-document text styles
for the migrated family.

This is an explicit boundary: direct context-free compatibility use remains
global, while normal document finalization is document-aware.

## Invariants

- text styles and fonts created for document A do not appear in document B;
- save order and interleaving do not change active document output;
- repeated save does not duplicate styles or font faces;
- authored styles and font-face declarations remain intact;
- structured text requirements reset on load/core-document replacement;
- list and paragraph behavior from earlier slices remains unchanged;
- no current-document pointer or constructor/save registry reset is used.

## Compatibility impact

Applications using `Paragraph`/`RichText` through `setElement()` retain their
style output and gain explicit document ownership. Applications that directly
call `StyleMapper` and `StyleWriter` retain the legacy path. Code relying on
`mapTextStyleOptions()` to populate `getRegisteredFontsXml()` is no longer
supported; mapping is intentionally side-effect free.

## Specialized writer helpers

The specialized helper methods are not part of normal `OdtTemplate` save
finalization in the active path. Their static generated-style/font caches are
left in place for compatibility characterization. They are not generalized
into a document registry or redesigned here.

## Non-goals

This contract does not migrate paragraph finalization further, remove
`StyleMapper`, redesign `StyleWriter`, or address text/image/frame/table/list
registries, style APIs, document defaults, assets, layout, or rendering
features. Those concerns belong to later STYLE-CONTEXT slices.

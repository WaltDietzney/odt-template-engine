# ARCH-03C — Document Finalization Boundary Decision

Status: **Decision complete — no production extraction**

## Decision

ARCH-03C selects **Option C**: do not extract a document-finalization
service yet.

The current save sequence is coherent at the level of facade orchestration,
but it is not safely document-scoped because style collection and image-style
registration depend on process-wide static `StyleMapper` state. Extracting a
class around the existing calls would preserve that coupling while implying a
cleaner boundary than the implementation currently provides.

The existing package boundary remains appropriate:

```text
OdtTemplate finalization orchestration
    ├── injectImageStyles()
    ├── StyleWriter::writeAllStyles()
    ├── adjustBulletIndentation()
    └── OdtPackage::saveAs()
```

No production finalization class is introduced by ARCH-03C.

## Current finalization flow

`OdtTemplate::save()` currently performs these operations in order:

1. `injectImageStyles()` updates `styles.xml` with registered graphic styles
   and bitmap fill-image declarations.
2. `StyleWriter::writeAllStyles($this->domStyles)` writes registered text,
   paragraph, graphic, table-cell and table styles, and derives font-face
   declarations from the current styles DOM.
3. `adjustBulletIndentation()` updates only
   `style:list-level-label-alignment` nodes in `styles.xml`.
4. `OdtPackage::saveAs()` synchronizes the image manifest, persists
   `content.xml`, `styles.xml` and `meta.xml`, then rebuilds the ODT ZIP.

`save()` does not require `render()` to have been called. When rendering is
used, template-language changes are already present in the content DOM before
this finalization sequence begins.

## Responsibility classification

### `injectImageStyles()` — technical ODT manipulation with static registry input

This operation reads `styles.xml` through the facade's compatibility DOM alias
and reads `StyleMapper::getRegisteredImageStyles()` and
`StyleMapper::getRegisteredFillImages()`. It writes graphic styles below
`office:automatic-styles` and fill-image declarations below `office:styles`.
It does not interpret template syntax, but its source state is not currently
owned by the document.

### `StyleWriter::writeAllStyles()` — technical ODT serialization with static registry input

This operation reads and modifies the `styles.xml` DOM. It consumes static
`StyleMapper` registries for text, paragraph, table-cell and table styles,
while also deriving font-face declarations from actual font attributes in the
current document. It serializes styles into `office:styles` and writes
font-face declarations in the document styles structure.

The legacy `writeTextStyles()` and `writeFontFaces()` paths also retain their
own static writer state and are not part of the active `save()` path.

### `adjustBulletIndentation()` — document-scoped post-processing

The active implementation reads and modifies only the current `styles.xml`
DOM. It targets `style:list-level-label-alignment` and must run after style
writing so generated list-level alignment nodes are included. It does not
read `StyleMapper` state.

### `OdtPackage::saveAs()` — package persistence

`OdtPackage` owns manifest synchronization, XML persistence, ZIP creation and
package cleanup. It should remain the serialization boundary and should not
absorb style collection or template-language behavior.

## Ordering constraints

The current order is observable and must remain:

- image styles are injected before central style writing, so existing graphic
  styles are recognized by duplicate checks;
- `StyleWriter::writeAllStyles()` runs before bullet post-processing, so list
  alignment nodes created or written by the style pipeline are available to
  the targeted adjustment;
- `OdtPackage::saveAs()` runs last, because it persists the final DOMs and
  synchronizes the package manifest.

Characterization coverage records the relative image-style and bullet-pass
ordering, save without render, and repeated-save behavior.

## StyleMapper / StyleWriter coupling

`StyleMapper` stores text, paragraph, table-cell, image, fill-image and table
registrations in static properties. `StyleWriter::writeAllStyles()` reads
those registries while writing one document's styles DOM. Therefore explicit
registrations can cross document boundaries in one PHP process, consistent
with the confirmed `STYLE-CONTEXT-01` finding.

Some element-generated styles are also registered before save and some style
nodes are written directly through compatibility paths in
`AbstractOdtTemplate`. `StyleWriter` consequently has both document-DOM
observations and process-wide registry inputs. A finalizer extracted now would
not be genuinely document-scoped without first defining style ownership.

## What intentionally remains in `OdtTemplate`

The following remain facade orchestration in ARCH-03C:

- the finalization call sequence;
- image-style injection coordination;
- style writing coordination;
- bullet-indentation post-processing;
- the call into `OdtPackage::saveAs()`.

The public `save()` API and all existing protected compatibility aliases and
methods remain unchanged. No package, metadata, page-layout, template-language
or image behavior is redesigned.

## Relationship to future architecture

`STYLE-CONTEXT-01` is a prerequisite for a sound document-finalization
boundary. A future document-scoped style registry should make the style inputs
explicit and associate them with one `OdtDocumentContext` or document
composition session without silently breaking public static registration APIs.

`DOCUMENT-DEFAULTS-01` should be considered alongside that work because
defaults may contribute styles and font declarations before finalization.

After those contracts exist, the safe migration path is:

1. characterize and isolate document-scoped style collection;
2. define how legacy static registrations are imported or retained;
3. extract only the finalization steps whose inputs are document-scoped;
4. keep `OdtTemplate::save()` as the compatibility facade delegating to that
   collaborator;
5. leave package persistence in `OdtPackage`.

ARCH-03C deliberately does not implement those later steps.

# ARCH-03 — Technical ODT Document Layer Audit

Status: **Characterization complete — no production-code changes**

## Purpose

ARCH-02 extracted the physical ODT package lifecycle into `OdtPackage` and grouped the mutable core XML documents in `OdtDocumentContext`.

That changes the next architectural question. The engine no longer needs to ask only how to split a large `OdtTemplate` class. It can now ask which remaining responsibilities belong to a reusable **technical ODT document layer** and which belong to the **template engine** itself.

This audit is deliberately performed before moving more production code.

The goal is to prepare the engine for future work including:

- document-wide defaults such as font family, font size, paragraph spacing, and page defaults;
- page styles and master pages;
- first-page versus following-page headers and footers;
- sections and semantic page breaks;
- additional ODT elements;
- a future shared document model and HTML preview/pagination layer.

The audit does not implement those features.

---

## 1. Baseline after ARCH-02

The current ownership boundary is now:

```text
OdtTemplate
    │
    ├── template-language behavior
    ├── public facade/orchestration
    ├── metadata API
    ├── image/content manipulation
    ├── style/document finalization
    └── compatibility aliases
            │
            ▼
       OdtPackage
            ├── source template
            ├── workspace
            ├── ZIP extraction/rebuild
            ├── XML persistence
            ├── manifest synchronization
            ├── package files/assets
            └── cleanup
                    │
                    ▼
           OdtDocumentContext
                    ├── content.xml DOM
                    ├── styles.xml DOM
                    └── meta.xml DOM
```

This is a meaningful improvement: physical package handling is no longer part of template-language processing.

However, a second boundary is still missing:

> Which code is generic ODT document manipulation, and which code is specifically template-language behavior?

That is the focus of ARCH-03.

---

## 2. Classification model

Remaining behavior is classified into five groups.

### A — Template-language responsibility

Behavior whose meaning comes from engine syntax such as `{{name}}`, filters, conditions, or loops.

### B — Technical ODT document responsibility

Behavior that manipulates native ODF/ODT structures and would still make sense without the template language.

### C — Public facade / orchestration

Application-facing methods that should remain stable while delegating internally.

### D — Compatibility / transitional infrastructure

Protected fields and methods retained because the current inheritance/API surface may still depend on them.

### E — Future document-composition responsibility

Concepts not yet implemented but for which the current architecture must leave a clean home.

---

## 3. `OdtTemplate` after ARCH-02

### 3.1 Public facade / orchestration — C

Representative public methods:

```text
__construct()
load()
setValues()
setRepeating()
setRepeatingData()
setMeta()
getMeta()
setImage()
replaceImageByName()
save()
refresh()
render()
assign()
assignRepeating()
cleanup()
```

These methods are part of the observable application surface even though their implementations belong to different internal responsibilities.

**Recommendation:** keep `OdtTemplate` as the stable public facade during the architecture phase. Reduce implementation responsibility by delegation rather than by replacing the public API.

### 3.2 Template-language state and processing — A

State:

```text
valueStack
repeatStack
values (legacy/compatibility state)
```

Representative behavior:

```text
replaceNl2brInDom()
replaceListsInDom()
applyConditionalsInDom()
evaluateCondition()
applyRepeatingInDom()
applyAllRepeatingBlocksInDom()
applyFilter()
normalizeTemplateDom()
renderTextBoxes()
render()
```

These methods exist because of the template syntax. They should ultimately move behind a focused `TemplateProcessor`/`TemplateRenderer` boundary.

They are **not** part of the technical ODT document layer.

### 3.3 Legacy/alternative template-processing paths — A / D

Known paths include:

```text
setRepeatingData()
applyConditionalsInDomTextBased()
applyRepeatingInDomTextBased()
splitConditionalsInTextNodes()
```

These must be characterized before consolidation. ARCH-03 must not copy both the active and legacy implementations into a new processor merely to make `OdtTemplate` smaller.

### 3.4 Metadata — B behind C

Public API:

```text
setMeta()
getMeta()
```

Technical responsibility:

```text
meta.xml
ODF/DC/meta namespaces
metadata node creation/update/read
```

Metadata does not depend on the template language. It is a clean technical ODT-document responsibility.

**Candidate owner:** `MetadataManager` operating on the document context.

The public methods should remain on `OdtTemplate` and delegate.

### 3.5 Images — mixed A/B/package concern

Public behavior:

```text
setImage()
replaceImageByName()
```

Current responsibilities are mixed:

```text
validate source image
copy image into Pictures/
measure image dimensions
generate draw:frame/draw:image XML
replace placeholder or named frame
apply anchor/wrap/dimensions
```

This should not be extracted as one monolithic image service without distinguishing responsibilities.

Recommended future split:

```text
OdtPackage / AssetManager
    └── physical package asset ownership

ImageRenderer / OdtDocumentEditor
    └── draw:frame and draw:image structures

TemplateProcessor / ElementInserter
    └── placeholder-driven placement where applicable
```

The public image API may continue to coordinate these collaborators.

### 3.6 Save/finalization — mixed B/C

Current `save()` still performs domain finalization before delegating package serialization:

```text
injectImageStyles()
StyleWriter::writeAllStyles()
adjustBulletIndentation()
OdtPackage::saveAs()
```

The package boundary is correct: ZIP/persistence is already delegated.

The remaining first three operations describe **ODT document finalization**, not template syntax.

This suggests a future technical document/finalizer responsibility, but ARCH-03 should avoid introducing a class merely to wrap three calls before style ownership is clearer.

### 3.7 Package compatibility bridge — D

The facade retains aliases such as:

```text
templatePath
tempDir
domContent
domStyles
domMeta
```

These remain transitional because `AbstractOdtTemplate`, `PageLayoutOdtTemplate`, and possible subclasses access inherited document state directly.

They are not a second conceptual owner. `OdtPackage`/`OdtDocumentContext` remain the intended source of document/package state.

---

## 4. `AbstractOdtTemplate` classification

ARCH-01 established that `AbstractOdtTemplate` is now closer to an ODF/DOM toolkit than a true abstract template model. After ARCH-02 this distinction is even clearer.

### 4.1 Namespace infrastructure — B

Representative behavior:

```text
prepareNamespaces()
ensureXmlnsAttributes()
```

This is generic ODF/XML infrastructure.

**Candidate owner:** a small `OdfNamespaces`/ODF XML helper, or document-layer utilities if a separate class is not justified.

Namespace URIs should eventually have a canonical definition instead of being duplicated across unrelated classes.

### 4.2 Style serialization and compatibility helpers — B / D

Representative behavior:

```text
injectImageStyles()
applyImageStyleProps()
ensureTextStylesExist()
ensureParagraphStylesExist()
insertAutomaticStyle()
ensureTableCellStyleNodesExist()
registerStyles()
adjustBulletIndentation()
```

These methods manipulate ODF styles and do not belong to template-language processing.

However, style ownership is already distributed across:

```text
AbstractOdtTemplate
StyleMapper
StyleWriter
RichTable/RichTableCell
image/frame paths
```

Therefore ARCH-03 should **classify but not prematurely consolidate** this area. The durable solution belongs with `STYLE-CONTEXT-01`.

### 4.3 Default ODT styles — B, later Phase B integration

Representative behavior:

```text
ensureDefaultListStyles()
ensureDefaultListStylesForContentXml()
ensureDefaultParagraphStyles()
```

These create engine-required/native ODF baseline styles.

They are not package I/O and not template syntax.

They are related to, but distinct from, future user-configurable `DOCUMENT-DEFAULTS-01`.

Important distinction:

```text
engine-required ODF defaults
        !=
application document defaults
```

A future document-scoped style/default context may initialize both, but they should not be conflated.

### 4.4 Basic placeholder replacement — A

Representative behavior:

```text
setValuesInDom()
replacePlaceholdersInNode()
replaceInText()
```

Although these methods are physically located in the base class, their semantics come from the template language.

They should ultimately move with the rest of template processing rather than into a generic ODT document editor.

### 4.5 Structured element insertion — B/C boundary, separate milestone

Representative behavior:

```text
setElement()
replacePlaceholderWithDom()
```

This behavior combines two concerns:

1. locate a template placeholder;
2. insert/import a native `OdtElement` DOM structure.

The insertion side is generic technical document work; placeholder lookup is template semantics.

This is strategically important for future elements, page breaks, sections, headers/footers, and page-style transitions.

**Recommendation:** keep this as a separate extraction milestone rather than burying it inside the template processor.

### 4.6 Template inspection and normalization — A

Representative behavior:

```text
extractTemplateVariables()
parseTemplateContent()
fixBrokenVariables()
```

These exist because LibreOffice may split template markers across spans/nodes. They belong conceptually to template inspection/normalization.

They may remain internal collaborators of a future `TemplateProcessor`; a separate public abstraction is not required.

### 4.7 Debugging — C/D

Representative state/behavior:

```text
debugMode
log
enableDebugMode()
getDebugLog()
```

Debugging is not a reason to preserve inheritance. It can remain facade-compatible until the base class is reassessed.

---

## 5. `PageLayoutOdtTemplate` classification

The existing page-layout API is one of the clearest technical-document responsibilities.

Current responsibility:

```text
master-page lookup
    ↓
page-layout-name
    ↓
page-layout
    ↓
page-layout-properties
```

Representative methods:

```text
setPageMargins()
setPageLayout()
findPageLayoutProperties()
xpathLiteral()
```

Classification: **B behind C**.

This logic is not template syntax. It belongs to the technical document layer and is a natural precursor to future master-page/page-style support.

**Candidate owner:** `PageLayoutManager` or, if future page-style work broadens the responsibility, a more general page/master-page document service.

Compatibility direction:

```text
PageLayoutOdtTemplate
        ↓ delegates
technical page-layout service
```

Do not remove the public compatibility class during this stage.

---

## 6. The technical ODT document layer

The audit identifies a coherent layer that did not have an explicit name before ARCH-02.

Conceptually:

```text
Template engine layer
├── template markers
├── values
├── filters
├── conditions
├── loops
└── placeholder semantics
        │
        ▼
Technical ODT document layer
├── metadata
├── ODF namespaces
├── native styles/finalization
├── image/frame DOM
├── structured ODF insertion
├── page layout/master-page access
└── future page/document structures
        │
        ▼
Document state
└── OdtDocumentContext
        │
        ▼
Physical package layer
└── OdtPackage
```

This layer should not become one giant `OdtDocumentManager` class. It is a **boundary**, not necessarily a single class.

Focused collaborators should be introduced only where responsibilities are coherent and independently testable.

---

## 7. Why this layer should be characterized before extracting TemplateProcessor

The previous roadmap placed template-language extraction immediately after ARCH-02.

ARCH-02 and the future requirements now reveal a useful intermediate step: define the technical ODT document boundary first.

Reasons:

1. Future page styles, headers/footers, sections, and semantic page breaks belong below template syntax.
2. Document-wide defaults need a document-scoped owner and must not become template-processor global state.
3. Structured element insertion mixes placeholder semantics with native ODF insertion and needs a deliberate seam.
4. PageLayout already demonstrates a coherent ODT-document service hidden behind inheritance.
5. Extracting TemplateProcessor first without defining this boundary risks moving generic ODF helpers into the processor simply because they currently participate in rendering.

Therefore this audit recommends a small roadmap re-sequencing rather than immediately moving template-language code.

---

## 8. Recommended ARCH-03 implementation slices

### ARCH-03A — Characterize technical ODT document layer — COMPLETE

This document is the output.

No production code is moved.

### ARCH-03B — Extract low-coupling technical document services

Recommended first candidates:

```text
MetadataManager
PageLayoutManager
canonical ODF namespace definitions where justified
```

Selection rule:

- independent of template syntax;
- document-scoped through `OdtDocumentContext`;
- public facade remains compatible;
- characterization tests exist before delegation.

Do **not** start with style consolidation or image splitting because they still cross several responsibilities.

### ARCH-03C — Reassess document finalization boundary

After the low-coupling services are extracted, inspect:

```text
injectImageStyles()
StyleWriter::writeAllStyles()
adjustBulletIndentation()
```

Decide whether a focused finalizer/document writer is justified or whether orchestration should remain on the facade until `STYLE-CONTEXT-01`.

No class should be created merely to reduce line count.

### Next architecture milestone — TemplateProcessor extraction

Once the technical document boundary is explicit, extract template-language processing:

```text
variables
filters
nl2br
list placeholders
conditions
loops
normalization
```

The public workflow remains:

```php
$template->assign(...);
$template->assignRepeating(...);
$template->render();
```

Legacy/alternative template-processing paths require characterization before consolidation.

---

## 9. Future requirements fit check

### Document defaults

A future document default configuration should attach to document lifetime rather than `StyleMapper` process-wide state.

Conceptual direction:

```text
OdtDocumentContext
├── core XML state
└── future document-scoped configuration/state
    ├── document defaults
    ├── style context
    ├── assets
    └── page/master-page state
```

This does **not** require turning `OdtDocumentContext` into a large service container. Separate document-scoped collaborators may reference the same context/lifetime.

### First-page vs following-page header

Required technical concepts:

```text
master pages
page styles
header/footer content
page-style transition
```

These belong to the technical ODT document layer, not to `TemplateProcessor`.

### Additional native elements

Future elements should be able to render native ODF structures without requiring package or template-language knowledge.

### HTML preview and pagination

The future shared document model remains above renderer-specific implementation:

```text
semantic document model
       ├── ODT technical renderer/document layer
       └── HTML renderer
```

Browser pagination must remain renderer-specific and should not leak into `OdtPackage` or ODF XML helpers.

---

## 10. Constraints for the next implementation step

ARCH-03B must preserve:

- all public `OdtTemplate` calls;
- `PageLayoutOdtTemplate` source compatibility;
- `OdtPackage` ownership established in ARCH-02;
- `OdtDocumentContext` as the owner of the three core DOMs;
- existing style behavior;
- existing image behavior;
- existing template-language behavior;
- public Samples 01–21;
- LibreOffice output behavior.

ARCH-03B must not implement:

- `DOCUMENT-DEFAULTS-01`;
- `STYLE-CONTEXT-01`;
- new page styles/master pages;
- headers/footers;
- sections;
- pagination;
- HTML renderer;
- table/frame/image layout fixes;
- `AbstractOdtTemplate` removal.

---

## 11. Completion conclusion

ARCH-03A confirms that the next extraction should not be driven by file size.

The meaningful boundary is now:

```text
Template syntax
      ↓
Technical ODT document services
      ↓
OdtDocumentContext
      ↓
OdtPackage
```

The safest next implementation slice is to extract **low-coupling technical document services**, beginning with metadata and page-layout behavior, while keeping the existing public facade intact.

This creates a clean foundation for later template-language extraction and for the larger document-composition features already recorded in the development roadmap.

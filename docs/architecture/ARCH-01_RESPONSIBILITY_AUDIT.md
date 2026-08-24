# ARCH-01 — Responsibility & Future Document Model Audit

Status: **Completed — architecture baseline**

This audit maps the responsibilities currently concentrated in `AbstractOdtTemplate`, `OdtTemplate`, and `PageLayoutOdtTemplate` and proposes extraction boundaries for the next development phase.

No production code is changed by ARCH-01. The purpose of this document is to establish a stable architectural map before refactoring begins.

## Executive conclusion

The current inheritance chain is functional but no longer represents the real domain boundaries of the engine:

```text
AbstractOdtTemplate
        ↑
   OdtTemplate
        ↑
PageLayoutOdtTemplate
```

`OdtTemplate` currently acts simultaneously as:

- public application facade;
- ODT package/workspace manager;
- XML document owner;
- template-language renderer;
- metadata manager;
- image/asset manager;
- manifest updater;
- document finalizer/serializer.

`AbstractOdtTemplate` is not primarily an abstract template model. It has evolved into a shared ODF/DOM toolkit containing:

- namespace helpers;
- style generation and compatibility paths;
- default list/paragraph style creation;
- placeholder replacement;
- structured element insertion;
- template-variable extraction and normalization;
- debugging helpers.

`PageLayoutOdtTemplate` contains a coherent page-layout responsibility, but it inherits the entire template implementation merely to access `styles.xml` and shared document state.

The recommended direction is therefore **composition behind the existing public facade**, not a larger inheritance tree.

The public `OdtTemplate` API should remain stable during the first extraction phases.

---

## 1. Current state ownership

### `AbstractOdtTemplate`

Current shared state:

```text
templatePath
tempDir
domContent
domStyles
log
debugMode
```

### `OdtTemplate`

`OdtTemplate` redeclares several properties already owned by the base class:

```text
templatePath
tempDir
domContent
domStyles
```

and adds:

```text
domMeta
values
valueStack
repeatStack
```

The duplicated declarations are an architectural smell: the base class and subclass do not have a clear ownership boundary for the document being processed.

### `PageLayoutOdtTemplate`

The page-layout subclass owns no independent document state. It reaches directly into inherited `domStyles`.

### Finding

The engine is missing one explicit concept that answers:

> What is the state of one ODT document currently being processed?

That concept is a strong candidate for ARCH-02. A document-scoped context/package object should eventually own the temporary workspace and loaded XML documents.

---

## 2. Responsibility map — `OdtTemplate`

### A. Public facade / application API

Methods:

```text
__construct()
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

Not all of these methods belong to one implementation responsibility, but they form part of the observable application-facing surface and must therefore be treated conservatively during refactoring.

Recommended ownership:

```text
OdtTemplate facade
```

The facade should delegate rather than continue implementing all behavior itself.

### B. ODT package / workspace lifecycle

Methods and behavior:

```text
__construct()
load()
loadXmlFile()
save()
saveMinifiedXml()
cleanup()
refresh()
```

Responsibilities currently mixed here:

- validate template path;
- create temporary directory;
- extract ZIP package;
- load `content.xml`, `styles.xml`, and `meta.xml`;
- write XML back to disk;
- rebuild the ODT ZIP package;
- preserve the special uncompressed-first `mimetype` entry;
- recursively add package files;
- remove the temporary workspace;
- reload package state.

Recommended future owner:

```text
OdtPackage / DocumentContext
```

This is the strongest first extraction boundary.

### C. Template-language state

State:

```text
valueStack
repeatStack
```

Public entry points:

```text
setValues()
setRepeating()
setRepeatingData()
assign()
assignRepeating()
render()
```

Recommended future owner:

```text
TemplateProcessor / TemplateRenderer
```

The public methods can remain on `OdtTemplate` and delegate.

### D. Template-language transformations

Methods:

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
```

These methods all operate on template semantics rather than physical ZIP/package concerns.

Recommended future owner:

```text
TemplateProcessor
```

Potential sub-responsibilities may later emerge for conditions/repeaters, but ARCH-03 should initially avoid unnecessary fragmentation.

### E. Alternative / legacy template-processing paths

Methods:

```text
applyConditionalsInDomTextBased()
applyRepeatingInDomTextBased()
splitConditionalsInTextNodes()
```

These coexist with the active paragraph/DOM processing path and should be treated as **legacy/uncertain until call-site and behavioral verification is complete**.

ARCH-03 must not automatically migrate both implementations into the new processor as if both were equally canonical.

Required action before extraction:

1. confirm repository call sites;
2. confirm external/public visibility implications;
3. retain compatibility only where necessary;
4. add characterization tests before deleting or consolidating behavior.

### F. Metadata

Methods:

```text
setMeta()
getMeta()
```

State dependency:

```text
domMeta
```

Recommended future owner:

```text
MetadataManager
```

This is a clean, low-coupling extraction candidate after package/context ownership is established.

The existing public methods should remain facade methods.

### G. Image placeholder replacement

Methods:

```text
setImage()
replaceImageInDom()
replaceImageByName()
replaceImageInNamedDom()
```

Responsibilities include:

- validating image paths;
- copying files into `Pictures/`;
- deriving physical dimensions;
- generating/replacing drawing/frame XML;
- applying anchor/wrap behavior;
- operating in `content.xml` and `styles.xml`.

This is currently both an **asset** responsibility and a **document-content insertion** responsibility.

Recommended future split, but not necessarily in one step:

```text
AssetManager
    └── package asset ownership / Pictures/

ImageRenderer or ElementInserter
    └── draw:frame / draw:image DOM structures
```

Do not extract this before ARCH-02 establishes package asset ownership.

### H. Manifest handling

Method:

```text
addImagesToManifest()
```

Responsibility:

- inspect package `Pictures/`;
- infer image MIME type;
- add missing `manifest:file-entry` declarations.

Recommended future owner:

```text
OdtPackage / ManifestManager
```

A separate `ManifestManager` should only be introduced if manifest behavior grows beyond a small package responsibility.

### I. Save/finalization orchestration

`save()` currently coordinates several independent subsystems:

```text
inject image styles
      ↓
write registered styles
      ↓
adjust list indentation
      ↓
update image manifest
      ↓
serialize XML
      ↓
rebuild ZIP
```

This method demonstrates why `OdtTemplate` has become a central coordinator.

Long-term target:

```text
OdtTemplate::save()
    ↓
DocumentFinalizer / package collaborators
```

The facade may continue to orchestrate a short sequence, but should not implement every step.

---

## 3. Responsibility map — `AbstractOdtTemplate`

### A. XML namespace infrastructure

Methods:

```text
prepareNamespaces()
ensureXmlnsAttributes()
```

Recommended future owner:

```text
OdfNamespaces / XML helper
```

Namespace URIs should eventually have one canonical definition rather than being repeatedly registered throughout unrelated classes.

This should remain a small utility responsibility, not become a general-purpose XML framework.

### B. Style serialization / compatibility

Methods:

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

Related existing collaborators:

```text
StyleMapper
StyleWriter
StyleOptionSplitter
HasStyles
```

Finding:

Style responsibility is still distributed across the base class, `StyleMapper`, `StyleWriter`, and element-specific direct DOM paths.

This confirms the P2-B finding and supports `STYLE-CONTEXT-01`.

Recommended direction:

```text
Document StyleContext
        ↓
StyleWriter / style serializers
```

Compatibility helpers should not be silently removed during the architectural extraction.

#### Specific audit warning: `ensureTableCellStyleNodesExist()`

The method signature accepts `$styleNodes`, while the current implementation iterates `$styleMap` and treats entries as raw style options. This mismatch strongly suggests an incomplete or stale compatibility path.

ARCH-01 does **not** change it because the method may be unused or externally reachable through inheritance, but it should be characterized before any style-pipeline consolidation.

This is a concrete candidate for the compatibility/dead-path review under `STYLE-API-02`.

### C. Default document styles

Methods:

```text
ensureDefaultListStyles()
ensureDefaultListStylesForContentXml()
ensureDefaultParagraphStyles()
```

Responsibility:

- guarantee engine-required baseline styles;
- create list styles in both `styles.xml` and `content.xml` contexts;
- create default heading/paragraph support.

Recommended future owner:

```text
DefaultStyleProvider / StyleContext initialization
```

Do not move these blindly into `OdtPackage`: they describe semantic/style defaults, not ZIP/package behavior.

### D. Basic value replacement

Methods:

```text
setValuesInDom()
replacePlaceholdersInNode()
replaceInText()
```

Recommended future owner:

```text
TemplateProcessor
```

This overlaps conceptually with `OdtTemplate`'s filter/condition/repeating implementation and is one of the clearest signs that template-language responsibility is split across the inheritance boundary.

### E. Structured element insertion

Methods:

```text
setElement()
replacePlaceholderWithDom()
```

Responsibility:

- locate placeholders in ODF DOM;
- ask an `OdtElement` for DOM representation;
- insert/import structured nodes into the correct document;
- ensure required styles are available.

Recommended future owner:

```text
ElementInserter / DocumentContentWriter
```

This is a distinct responsibility from template-language string replacement.

It is also strategically important because future elements such as sections, page breaks, headers, and page-style transitions will need a well-defined insertion/rendering boundary.

### F. Template inspection and normalization

Methods:

```text
extractTemplateVariables()
parseTemplateContent()
fixBrokenVariables()
```

Related subclass method:

```text
normalizeTemplateDom()
```

Responsibility:

- inspect template placeholders;
- recover placeholders split by LibreOffice spans/nodes;
- parse template markers.

Recommended future owner:

```text
TemplateNormalizer / TemplateInspector
```

For the first ARCH-03 extraction these can remain internal collaborators of `TemplateProcessor`; separate public concepts are not required.

### G. Debugging

Methods/state:

```text
debugMode
log
enableDebugMode()
getDebugLog()
```

Recommended future owner:

Initially keep facade-compatible behavior, but move storage into a document/process context or small diagnostic collaborator when the base class is dismantled.

Debugging is not a reason to preserve inheritance.

---

## 4. Responsibility map — `PageLayoutOdtTemplate`

Methods:

```text
setPageMargins()
setPageLayout()
findPageLayoutProperties()
xpathLiteral()
adjustBulletIndentation() override
```

The class has one coherent domain responsibility:

```text
master page
    ↓
page-layout-name
    ↓
page-layout
    ↓
page-layout-properties
```

This is a good domain boundary, but inheritance is currently used mainly to obtain access to `domStyles`.

Recommended future owner:

```text
PageLayoutManager
```

Compatibility path:

```text
PageLayoutOdtTemplate
    ↓ delegates
PageLayoutManager
```

The public class should remain during the architectural phase.

### Important finding

The subclass still overrides `adjustBulletIndentation()` even though the base implementation has already been corrected to operate only on list-label alignment nodes.

This duplication is now a maintenance risk rather than a meaningful page-layout specialization.

Do not remove it in ARCH-01, but verify equivalence during the extraction phase and consolidate the behavior into one owner.

---

## 5. Cross-cutting problems revealed by the audit

### 5.1 Document state has no single owner

`domContent`, `domStyles`, `domMeta`, `tempDir`, and package assets describe one processing session, but are distributed across inheritance state.

This blocks clean dependency boundaries.

### 5.2 Template language is split across two classes

Basic replacement and placeholder normalization live in `AbstractOdtTemplate`; conditions, filters, loops, special list placeholders, and rendering orchestration live in `OdtTemplate`.

This should become one coherent processing subsystem.

### 5.3 Style handling has multiple writers

The audit confirms the P2-B map:

```text
AbstractOdtTemplate compatibility helpers
StyleMapper static registries
StyleWriter
RichTable / RichTableCell direct style paths
image/frame direct paths
```

The architecture extraction should make a later document-scoped `StyleContext` easier, but ARCH-02 must not attempt to solve the entire style system at once.

### 5.4 Asset handling crosses package and rendering boundaries

Image methods currently both copy package files and generate document XML. HTML import has its own resolver and temporary-asset registry.

A future asset context needs explicit ownership and lifecycle.

### 5.5 Several compatibility/legacy paths are insufficiently characterized

Examples include:

```text
setRepeatingData()
refresh()
registerStyles()
ensureTableCellStyleNodesExist()
applyConditionalsInDomTextBased()
applyRepeatingInDomTextBased()
splitConditionalsInTextNodes()
```

They should not be deleted merely because repository call sites are absent or sparse. Public/protected visibility and external Composer users require a compatibility decision.

### 5.6 Page layout is only the beginning of page structure

Current page support edits properties of an existing master-page/page-layout relationship. It does not yet model:

- creation/selection of multiple page styles;
- first-page versus following-page master pages;
- dynamic header/footer content;
- sections;
- page-style transitions;
- semantic page breaks.

The future architecture must allow those concepts without adding them all to `OdtTemplate`.

---

## 6. Proposed target boundaries

The audit recommends the following **direction**, not a frozen class diagram:

```text
                       OdtTemplate
                    public facade/API
                           │
          ┌────────────────┼─────────────────┐
          │                │                 │
          ▼                ▼                 ▼
     OdtPackage      TemplateProcessor   ElementInserter
          │                │                 │
          │                │                 │
          ▼                ▼                 ▼
   DocumentContext   template language    OdtElement DOM
          │
          ├── content.xml
          ├── styles.xml
          ├── meta.xml
          ├── workspace
          ├── package assets
          └── manifest

Additional focused collaborators:

MetadataManager
PageLayoutManager
StyleContext       (Phase B)
AssetManager       (as ownership becomes clear)
```

The exact distinction between `OdtPackage` and `DocumentContext` should be decided in ARCH-02 after a small prototype/characterization step. Avoid creating both merely because both names are plausible.

---

## 7. Future document model fit check

The proposed boundaries were tested conceptually against the known future requirements.

### First-page header differs from following pages

Required concepts:

```text
PageStyle / MasterPage
Header content
Page-style transition
```

Fit:

- package/context provides `styles.xml` access;
- `PageLayoutManager` can evolve into page/master-page management or be joined by a dedicated manager;
- `ElementInserter` provides a structural rendering boundary;
- no need to add header-specific XML logic to the template-language processor.

**Result: architecture can accommodate the requirement.**

### Sections and explicit page breaks

Required concepts:

```text
Section
PageBreak
KeepWithNext
KeepTogether
PageStyle transition
```

Fit:

- these can become semantic document elements/rules;
- ODT-specific serialization can live behind element/rendering boundaries;
- package lifecycle remains unaffected.

**Result: architecture can accommodate the requirement.**

### More flexible tables, frames, and images

Fit:

- style ownership can move toward document-scoped state;
- assets can be separated from frame/image DOM generation;
- layout-specific element renderers no longer need to depend on the entire template class.

**Result: architecture improves the ability to address existing backlog items.**

### HTML preview and pagination

The ODT package layer must not become a dependency of an HTML renderer.

A later shared semantic document model can sit above renderer-specific implementations:

```text
Application data
       ↓
Document model
      / \
     /   \
 ODT     HTML
renderer renderer
```

Browser pagination can then use measured rendered geometry while ODT uses native ODF pagination semantics.

**Result: proposed extraction does not block a shared model and reduces coupling to ODT package details.**

---

## 8. What ARCH-02 should do

Recommended next milestone:

> **ARCH-02 — Extract ODT package / document context**

Scope should be deliberately narrow.

### In scope

- create a focused owner for temporary workspace lifecycle;
- extract ZIP opening/extraction;
- own loaded core XML documents;
- centralize XML file loading/serialization;
- extract final ZIP rebuild;
- preserve `mimetype` behavior;
- preserve cleanup behavior;
- keep `OdtTemplate` constructor/save API compatible;
- add characterization/integration tests around package lifecycle.

### Preferably in scope if it remains small

- manifest access/update as package behavior.

### Out of scope

- rewriting template language;
- replacing `StyleMapper` static state;
- redesigning image APIs;
- introducing page-style/header/footer APIs;
- removing `AbstractOdtTemplate`;
- introducing a shared HTML/ODT document model;
- changing public constructor or save semantics.

### Success criterion

After ARCH-02, this should still work unchanged:

```php
$template = new OdtTemplate($templatePath);
$template->assign($data);
$template->render();
$template->save($outputPath);
```

All public sample smoke tests and the full PHPUnit suite must remain green.

---

## 9. Refactoring sequence confirmed by ARCH-01

Recommended sequence after this audit:

```text
ARCH-01  Responsibility audit                  COMPLETE
   ↓
ARCH-02  ODT package / document context
   ↓
ARCH-03  Template-language processor
   ↓
ARCH-04  Structured element insertion
   ↓
ARCH-05  Reassess/remove AbstractOdtTemplate
   ↓
Phase B  StyleContext / asset lifecycle
   ↓
Phase C  Page styles / headers / sections
```

This sequence is preferred because later components need document state without inheriting the entire template engine.

---

## 10. Architectural decisions from this audit

1. `OdtTemplate` remains the public facade during the first refactoring phases.
2. Internal composition is preferred over adding more subclasses.
3. Physical ODT package lifecycle is the first extraction target.
4. Template-language processing is a separate responsibility from package lifecycle.
5. Structured `OdtElement` insertion is a separate responsibility from string/template-language replacement.
6. Metadata is a focused document service and should eventually leave `OdtTemplate` implementation code.
7. Page layout/page structure should become document-scoped services rather than deeper inheritance.
8. StyleContext remains a separate Phase B concern; no constructor-level static reset is introduced.
9. Compatibility/legacy methods require characterization before removal.
10. The architecture must support future page styles, dynamic headers/footers, sections, explicit page controls, and renderer-independent document semantics.

## Final ARCH-01 verdict

The proposed refactoring is justified by responsibility boundaries, not by file size alone.

The current classes combine concerns that will make future page-structure, layout, style, asset, and renderer work increasingly difficult. The first safe move is to extract the physical ODT package/document state while preserving the existing public facade.

**ARCH-01 COMPLETE — READY FOR ARCH-02 DESIGN CONTRACT.**

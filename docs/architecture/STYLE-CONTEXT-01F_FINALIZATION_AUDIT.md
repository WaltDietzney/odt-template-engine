# STYLE-CONTEXT-01F-A — Final Style-State Inventory and Target Architecture

## Purpose

This document replaces the earlier narrow STYLE-CONTEXT-01F finalization proposal with a complete inventory of mutable style state before the remaining migration is designed.

STYLE-CONTEXT-01A through 01E established the document ownership boundary and introduced `StyleContext`, but they deliberately migrated only one narrow path. The project has now chosen to complete the architecture semantically rather than stop after fixing paragraph leakage.

The goal is therefore stronger:

> All mutable style requirements created while editing an ODT document must have an unambiguous document owner. Process-wide mutable style registries must not remain part of the active document-generation architecture.

This audit is evidence and planning. It does not change runtime behavior.

## Repository baseline

Audit baseline:

- branch: `develop`
- commit: `13108b730b5a8ed2a89943525cbd6b89f3f47961`
- includes STYLE-CONTEXT-01A through 01E

The audit branch is `architecture/style-context-01f-finalization-audit`.

## Architectural target

The target ownership model is:

```text
application / element
        ↓ declares requirement
materialization boundary
        ↓
OdtDocumentContext
        └── StyleContext
              ├── text requirements
              ├── paragraph requirements
              ├── graphic requirements
              ├── table requirements
              ├── table-cell requirements
              ├── font requirements
              └── related style-resource requirements where appropriate
        ↓
document-aware finalization
        ↓
styles.xml / content.xml / package resources
```

`styles.xml` and `content.xml` remain authoritative for styles already present in the loaded ODF document. `StyleContext` should own pending/generated requirements, not duplicate the complete XML style model.

`StyleMapper` should ultimately be a mapping/value-transformation utility. Mapping methods should not create document state as a side effect.

Elements are producers of requirements, not owners of document-global registries.

## Current save/finalization path

`OdtTemplate::save()` currently combines two different models:

```text
injectImageStyles()
    ↓ reads process-wide image/fill-image registries
StyleWriter::writeAllStyles(stylesDom)
    ↓ reads process-wide text/paragraph/frame/table-cell/table registries
adjustBulletIndentation()
    ↓
OdtPackage::saveAs()
```

`refresh()` also invokes style writing before its existing reset behavior.

This means the final saved document can still depend on mutable state created by unrelated elements or documents earlier in the same PHP process.

## Inventory matrix

| Family / state | Requirement producer today | Current mutable owner | Current materialization | ODF destination | Target owner | Status / risk |
| --- | --- | --- | --- | --- | --- | --- |
| Paragraph styles | `Paragraph`, `RichText`, legacy callers | modern `StyleContext` plus legacy `LegacyStyleRegistry` | structured path immediately through `ensureParagraphStylesExist()`; legacy path through `StyleWriter::writeAllStyles()` | primarily `styles.xml` / `office:styles` | `StyleContext` | partially migrated; legacy ambiguity remains |
| Text styles | `Paragraph` text parts, RichText aggregation, compatibility callers | element-local `textStyleMap` plus process-wide `StyleMapper` text registries | structured path can be written immediately by `ensureTextStylesExist()`; fallback/legacy paths register globally; `StyleWriter` also consumes globals | `styles.xml` / `office:styles` | `StyleContext` | mixed path; element-local requirements already provide a migration seam |
| Fonts | text style mapping and writer paths | `StyleMapper::$registeredFonts`; `StyleWriter::$fontsUsed`; document DOM scan in `writeAllStyles()` | `writeAllStyles()` now scans the current DOM; older writer helpers still use process-wide caches | `styles.xml` / `office:font-face-decls` | document-owned requirements derived from document styles | mixed; current main writer is more document-local than legacy helpers |
| Graphic / frame styles | `DrawTextBox` and frame-related elements | public process-wide `StyleMapper::$frameStyles` | `StyleWriter::writeAllStyles()` | `styles.xml` / graphic styles | `StyleContext` | active global registration occurs during construction, mutation and rendering |
| Image graphic styles | `ImageElement` | process-wide `StyleMapper::$registeredImageStyles` | `OdtTemplate::injectImageStyles()` | `styles.xml` / `office:automatic-styles` | `StyleContext` or document-scoped graphic requirement registry | active global side effects in both `setStyle()` and `toDomNode()` |
| Fill-image definitions | image/frame bitmap-fill paths | process-wide `StyleMapper::$registeredFillImages` | `OdtTemplate::injectImageStyles()` | `styles.xml` `draw:fill-image` plus package image resource | document context; style metadata may live under `StyleContext`, asset ownership under package/document asset lifecycle | crosses STYLE-CONTEXT and future ASSET-CONTEXT boundary; do not collapse both concerns blindly |
| Table styles | table APIs / compatibility callers | public process-wide `StyleMapper::$tableStyles` | `StyleWriter::writeAllStyles()` | `styles.xml` / table styles | `StyleContext` | global save-time input |
| Table-cell styles | `RichTableCell` | element-local definition plus process-wide `StyleMapper::$tableCellStyles`; a second `registeredTableCellStyles` array also exists | cells can emit style DOM directly into the target content DOM; `StyleWriter` separately consumes global table-cell registry | `content.xml` automatic styles and/or `styles.xml` depending path | document-owned requirement with ODF-aware placement | highest duplication risk; existing dual registries and dual materialization paths must be characterized, not normalized casually |
| Table-column styles | `RichTable` column widths | no persistent registry; direct writer call | `StyleWriter::writeColumnStyles($contentDom, ...)` during `toDomNode()` | `content.xml` / `office:automatic-styles` | current document DOM is already the owner | largely document-local; not a migration target merely because it is called StyleWriter |
| List styles | Paragraph list flags and OdtTemplate helpers | mixed constants/direct DOM behavior | direct ensure helpers in `styles.xml` and `content.xml` | both core XML documents according to path | document DOM / document-aware helper | not primarily registry leakage; must be regression-covered but should not be forced into a generic style registry |
| Generated writer caches | `StyleWriter::writeTextStyles()` / `writeFontFaces()` | static `generatedTextStyles`, static `fontsUsed` | specialized/legacy writer helpers | `styles.xml` | no process-wide mutable cache; derive from target document/context | dangerous if reachable across documents; main `writeAllStyles()` already avoids part of this problem |

## Detailed findings

### 1. Paragraph styles are only partially migrated

The modern structured path already follows the intended ownership direction:

```text
Paragraph / RichText
    ↓ getRequiredParagraphStyles()
OdtTemplate::setElement()
    ↓
current OdtDocumentContext::styleContext()
```

`StyleContext` enforces document-local idempotency and rejects same-name conflicting pending definitions.

However, `setElement()` then immediately writes those requirements to the current `styles.xml` through `ensureParagraphStylesExist()`. Therefore `StyleContext` is currently an ownership/conflict boundary, not yet the sole source consumed by save-time finalization.

The legacy static path remains distinct:

```text
StyleMapper::registerParagraphStyle()
    ↓
LegacyStyleRegistry
    ↓
StyleWriter::writeAllStyles()
```

The process-wide leakage characterized in 01A is still intentional current behavior.

### 2. Text styles already have a useful element-local seam

`Paragraph` stores generated inline text requirements in its own `textStyleMap` and exposes them through `getRequiredStyles()`. This is already close to the desired producer model.

Normal structured materialization can consume those requirements directly into the current document. However, `Paragraph::registerStyles()` still pushes them to `StyleMapper`, and the `toDomNode()` fallback can call `StyleMapper::registerTextStyle()` if a styled part lacks a precomputed style name.

This means text migration should prefer the existing requirement API and characterize whether the fallback is reachable before removing it.

### 3. Font mapping is not pure

`StyleMapper::mapTextStyleOptions()` maps text options but also mutates the process-wide registered-font set when `font-family` or the monospace shortcut is encountered.

That side effect violates the target role of `StyleMapper` as a pure mapping helper.

At the same time, the main `StyleWriter::writeAllStyles()` font phase has already evolved in a better direction: it scans font references from the current target DOM and writes missing `font-face` declarations for that document. This is a strong candidate for preservation or extraction into document-aware finalization.

The specialized `writeTextStyles()` / `writeFontFaces()` path still relies on static `generatedTextStyles` and `fontsUsed`, so its reachability and compatibility surface must be characterized before removal or migration.

### 4. DrawTextBox is strongly process-global today

`DrawTextBox` registers its frame style in the constructor, on every relevant fluent mutation, and again during `toDomNode()`. It writes directly to public static `StyleMapper::$frameStyles`.

This is a clear violation of the desired element-producer model: constructing an element that has not yet been attached to any document mutates process-wide document-generation state.

The class already exposes `getStyleDefinitions()` and `toStyleDomNode()`, so there are existing seams for a document-aware migration. FRAME-LAYOUT-01 should later build on the cleaned ownership model rather than invent another registry.

### 5. ImageElement has both style and asset concerns

`ImageElement::setStyle()` maps options, generates a style name, and immediately registers the image style globally. `toDomNode()` registers the style globally again.

The element separately exposes image assets through `getImageAssets()`. That distinction is useful:

- graphic style requirement belongs to document style ownership;
- physical picture/resource lifecycle belongs to the document/package asset boundary.

Fill-image definitions touch both. STYLE-CONTEXT should establish ownership of the style/resource requirement without prematurely redesigning the full ASSET-CONTEXT lifecycle.

### 6. RichTableCell has duplicated materialization paths

`RichTableCell::setStyle()` and `registerStylesAndRefresh()` register mapped table-cell styles globally. The cell also exposes `getStyleDefinitions()` and can directly create a `style:style` DOM node with `toStyleDomNode()`.

`RichTable::toDomNode()` collects these cell style nodes and appends them to the target document's `office:automatic-styles`. Separately, `StyleWriter::writeAllStyles()` consumes the process-wide table-cell registry and writes table-cell styles into `styles.xml`.

This is not merely an ownership issue; it is an ODF placement/duplication issue. The migration must first characterize which path is active for which APIs and preserve actual LibreOffice rendering before choosing one canonical placement.

The existing `StyleMapper` also contains both `registeredTableCellStyles` and `tableCellStyles`; current active register/get behavior must be verified rather than assuming both are meaningful.

### 7. RichTable column styles are already document-local

`RichTable::toDomNode()` calls `StyleWriter::writeColumnStyles()` with the target DOM. The helper writes automatic table-column styles directly into that DOM and does not use a process-wide registry.

This demonstrates an important rule for the migration: not every method in `StyleWriter` is architecturally wrong. The problem is process-wide mutable ownership, not the existence of ODF-writing helpers.

### 8. List styles should remain ODF-aware

List helpers currently ensure required structures directly in `styles.xml` and `content.xml`. Their placement is ODF-specific and they are not primarily driven by the global registries being removed.

They should be covered by regression tests during finalization work, but STYLE-CONTEXT must not force them into one generic registry simply for uniformity.

## Active, legacy and compatibility paths

The migration must label paths explicitly rather than treating every method equally.

### Clearly active / important

- `OdtTemplate::setElement()` requirement collection;
- `StyleContext` paragraph ownership;
- `ensureTextStylesExist()` / `ensureParagraphStylesExist()` current DOM materialization;
- `StyleWriter::writeAllStyles()` from save/finalization;
- `OdtTemplate::injectImageStyles()`;
- `Paragraph` local style requirement maps;
- `ImageElement` global registration;
- `DrawTextBox` global frame registration;
- `RichTableCell` global registration plus direct automatic-style generation;
- `RichTable` direct table-column generation.

### Explicit compatibility / legacy

- `LegacyStyleRegistry` paragraph storage;
- static `StyleMapper` registration entry points;
- `Paragraph::registerStyles()` and other `HasStyles::registerStyles()` methods where normal structured materialization already has requirement APIs;
- specialized `StyleWriter::writeTextStyles()` / `writeFontFaces()` unless reachability proves otherwise.

### Suspicious and requiring characterization

- duplicated table-cell registries in `StyleMapper`;
- context-free `OdtTemplate::registerStyles()` compatibility code identified in earlier audits;
- writer static generated-style/font caches;
- fallback registration inside `Paragraph::toDomNode()`;
- direct public static `$frameStyles` and `$tableStyles` access.

Suspicious behavior must not be fixed opportunistically during a family migration. Characterize first.

## Target responsibilities

### OdtDocumentContext

Owns the lifetime of document-scoped mutable collaborators. It remains the semantic document boundary.

### StyleContext

Owns pending/generated style requirements belonging to that document. It may use separate internal registries by family; a single undifferentiated array is not required or desirable.

It should enforce family-appropriate duplicate/conflict semantics without pretending all ODF style families have identical rules.

### StyleMapper

Target role: mapping and deterministic naming/value transformation.

It should not be the authoritative owner of mutable document state. Mapping operations should become free of process-wide registration side effects.

### Elements

Own their own configuration and expose requirements/assets. Constructing or mutating an unattached element should not change another document's future output.

### Finalization

Finalization is a document operation. A finalizer may use ODF-specific writer helpers, but all pending requirements must be derived from the current document/context, not unrelated process-wide registries.

The finalizer must preserve authored styles already present in the loaded DOM and merge generated requirements idempotently into the correct ODF location.

## Legacy API consequence

A context-free call such as:

```php
StyleMapper::registerParagraphStyle('Example', $definition);
```

cannot be made truly document-scoped because no document identity is present.

The project must not introduce a process-global current-document pointer, constructor resets, or last-created-document semantics.

The final migration will therefore require an explicit legacy API strategy. The semantically clean direction is a document-aware registration facade, conceptually for example:

```php
$template->registerParagraphStyle('Example', $definition);
```

The exact public API is not approved by this audit. It requires a dedicated compatibility/change contract. Existing static methods may remain deprecated/compatibility surfaces, but they cannot remain authoritative automatic inputs to unrelated document saves if complete isolation is claimed.

## Proposed implementation sequence

The complete semantic closeout should remain incremental.

### 01F-B — Paragraph finalization and legacy paragraph contract

- make current-document paragraph requirements the authoritative finalization input;
- characterize immediate pre-save DOM observability before changing it;
- define the explicit compatibility fate of context-free legacy paragraph registration;
- invert/replace the 01A leakage characterization only under an approved contract;
- add save-order, interleaving, repeated-save and load-reset regressions.

### 01F-C — Text styles and font requirements

- route local text requirements into document ownership;
- characterize/remove global text fallback dependencies;
- make `mapTextStyleOptions()` side-effect free;
- preserve current document-DOM font discovery where appropriate;
- characterize specialized writer caches before removal.

### 01F-D — Graphic, frame, image and fill-image requirements

- stop unattached `DrawTextBox` / `ImageElement` instances from mutating process-wide state;
- register graphic/image requirements at the document materialization boundary;
- keep physical image asset lifecycle distinct from style ownership;
- preserve rendering and prepare a clean foundation for FRAME-LAYOUT-01.

### 01F-E — Table and table-cell requirements

- characterize global versus direct automatic-style paths;
- determine correct ODF placement from current behavior and LibreOffice-authored structures;
- migrate ownership without mixing in TABLE-LAYOUT feature work;
- leave column geometry semantics unchanged.

### 01F-F — Legacy facade and obsolete global-state removal

- audit all remaining `StyleMapper` registration APIs and direct public static registry access;
- define/document deprecation or document-aware replacements where needed;
- remove process-wide mutable registries from active finalization;
- remove obsolete writer caches only after reachability/compatibility proof.

### 01F-G — Full multi-document and visual closeout

- prove isolation across every migrated family with two or more simultaneous documents;
- test interleaved save order and repeated saves;
- run lifecycle/reset tests;
- run all public samples;
- run rendering-sensitive visual regression;
- perform final architecture review proving that active document output no longer depends on process-wide mutable style state.

The lettering is planning terminology. Each slice should receive its own characterization/change contract before implementation.

## Cross-document end-state invariant

After STYLE-CONTEXT-01 is fully closed:

> Operations performed on document A must not change style output of document B unless application code explicitly transfers data, an element, or a style requirement from A to B.

This must hold for interleaved usage:

```text
construct A
construct B
edit A
edit B
save A
edit A
save B
save A again
```

It must also hold when styled elements are constructed before attachment to either document.

## Compatibility invariants during migration

Unless a slice explicitly approves a behavior change:

- public `OdtTemplate` APIs remain stable;
- protected facade behavior relevant to subclasses remains stable;
- loaded LibreOffice-authored styles remain authoritative;
- normal single-document ODF output and rendering remain unchanged;
- repeated `render()` / `save()` behavior remains compatible;
- `load()` remains a reset boundary for document-scoped pending requirements;
- `refresh()` is not redesigned incidentally;
- `content.xml` versus `styles.xml` placement is not normalized without ODF evidence;
- table/layout/image/frame feature semantics are not redesigned as part of ownership migration.

## Test and visual-regression strategy

Every family migration needs focused characterization plus the existing compatibility suite.

The final closeout must include at least:

- `StyleContextTest`;
- `StyleContextCharacterizationTest`, deliberately evolved rather than silently deleted;
- `StyleContextElementIntegrationTest`;
- `StyleMapperCompatibilityTest` while legacy APIs remain;
- `StylePipelineP2BTest`;
- relevant paragraph/text/image/frame/table/list integration tests;
- package/lifecycle/finalization tests;
- `PublicSampleSmokeTest`;
- full `composer test`;
- PHP lint for `src/` and `tests/`;
- `composer validate`;
- `git diff --check`;
- documentation build when docs change.

`PublicSampleSmokeTest` currently executes all 25 public samples in an isolated temporary repository copy, validates their generated ODT ZIP structure and core XML, and verifies that the repository's `samples/output/` files are not changed.

Rendering-sensitive validation remains:

```text
automated tests
    ↓
ODT / ZIP / XML validation
    ↓
public Sample Explorer
    ↓
LibreOffice headless
    ↓
PDF
    ↓
Poppler PNG pages
    ↓
visual review
```

The repository documents this workflow but the audit did not find a versioned implementation of the local PDF/PNG conversion helper. Preflight reports must record the actual local command/tool used rather than inventing one.

For ownership-only slices, correctly generated single-document samples are expected to remain visually unchanged. Any visual difference must be investigated as a possible semantic change, not accepted as incidental refactoring noise.

## Non-goals of STYLE-CONTEXT semantic closeout

The migration does not by itself introduce:

- DOCUMENT-DEFAULTS-01;
- STYLE-API-02 as a broad public styling redesign;
- FRAME-LAYOUT-01 positioning semantics;
- TABLE-LAYOUT width/geometry features;
- generic named-object replacement;
- a rewritten asset manager;
- new list feature semantics;
- template-format-preservation features;
- changes to Sample 25 authoring/layout limitations.

Those later strands should consume the cleaned document-scoped ownership model rather than being pulled into this refactor.

## Architectural consequence for later work

Completing STYLE-CONTEXT semantically reduces architectural burden in later milestones:

- `DOCUMENT-DEFAULTS-01` gains a natural document owner and precedence foundation;
- `FRAME-LAYOUT-01` can define layout semantics without inheriting global graphic-style state;
- table layout work can focus on geometry instead of ownership leakage;
- image/frame content replacement can reuse document-owned style/resource requirements;
- long-running workers and multi-document processes no longer require timing-dependent global cleanup.

This is why the broader migration is justified even though it requires more slices than the original narrow 01F proposal.

## Audit conclusion

The repository contains enough existing seams to migrate incrementally without a rewrite:

- `OdtDocumentContext` already owns `StyleContext`;
- `Paragraph` and composite elements already expose local style requirements;
- the main writer already derives font faces partly from the target document;
- several elements can already expose style definitions or direct style DOM nodes;
- table-column generation demonstrates a document-local writer path.

The main architectural debt is not lack of ODF-writing capability. It is mixed ownership: element-local requirements, document-local requirements, direct DOM writes, legacy registries and process-wide writer caches coexist.

The approved direction is therefore:

> Finish STYLE-CONTEXT-01 as a sequence of characterized family migrations until active document generation no longer depends on process-wide mutable style state.

Recommended next action: define the focused change contract for **STYLE-CONTEXT-01F-B — Paragraph finalization and legacy paragraph semantics**. Do not begin text, frame/image or table migration inside that slice.
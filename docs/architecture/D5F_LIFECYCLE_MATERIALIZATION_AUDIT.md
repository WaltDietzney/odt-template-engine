# D5F — Lifecycle / Materialization Audit

Status: **AUDIT COMPLETE / CHARACTERIZATION REQUIRED BEFORE CHANGE CONTRACT**

Baseline: `develop` after SR-07H / PR #56 (`5ffb6e3c74c35aed7507654b99b53860fb3a3a70`)

## 1. Purpose

D5F was originally planned as the lifecycle-integration slice following D5C–D5E. Its purpose was to coordinate pre-materialization discovery, native subtree materialization, post-materialization discovery, and document finalization for structured insertion.

The original D5 contract deliberately postponed D5F because the active structured-insertion path still mixed semantic and legacy requirement models. SR-06 and SR-07 have since migrated the relevant graphic and table style families into the semantic `StyleRequirement` model. D5F can therefore be reassessed against the current `develop` architecture rather than against the historical mixed-family state.

This document records that reassessment. It is an audit, not a Change Contract. It does not approve refactoring, remove compatibility paths, introduce a lifecycle framework, or change public behavior.

## 2. Inherited architecture decisions

The following decisions remain authoritative:

1. `OdtElement` or an explicitly element-associated collaborator owns native ODF semantics.
2. `ownedElements()` is the authoritative semantic ownership projection.
3. `OdtTemplate` is an orchestration facade, not a concrete-element renderer.
4. `OdtDocumentContext` is the document-local mutable-state boundary and must not become a God context.
5. `StyleContext` owns document-local style requirement state.
6. Physical image resources remain package concerns and are prepared through `OdtPackage`.
7. Structured style/dependency discovery and physical resource discovery are separate projections over the same ownership tree.
8. Compatibility paths must remain explicit and must not silently define target architecture.
9. Refactoring and behavior change should remain separate wherever practical.

The original D5 lifecycle model allowed a two-phase semantic lifecycle where required:

```text
pre-materialization discovery
    -> prepare known resources / requirements
native subtree materialization
    -> element-local final state becomes available
post-materialization discovery
    -> adopt final requirements / newly exposed resources
document finalization
```

This audit asks whether the post-materialization semantic phase is still required after SR-06 and SR-07.

## 3. Current active `setElement()` orchestration

The current `OdtTemplate::setElement()` path is transitional and performs several distinct phases:

```text
semantic pre-phase
    collectSemantic()
        -> StyleContext::registerRequirement()
        -> font dependency discovery
        -> fill-image dependency discovery
        -> fill-image materialization
        -> semantic style materialization

legacy pre-phase
    collect()
        -> paragraph/text compatibility registration
        -> HasStyles compatibility path

resource phase
    StructuredResourceCollector
        -> OdtPackage resource preparation

native materialization
    StructuredElementMaterializer::insert()
        -> element->toDomNode()
        -> placeholder replacement

legacy post-phase
    collect() again
        -> paragraph/text registration again
        -> frame/image/fill-image compatibility state
```

The important architectural observation is that the normal `setElement()` path already has one complete semantic requirement channel, but still carries a second historical compatibility projection before and after native materialization.

## 4. Requirement and resource projections

### 4.1 Semantic style requirements

`StyleRequirementCollector::collectSemantic()` traverses `ownedElements()` and yields each element's `getOwnStyleRequirements()` values individually. Duplicate identities remain visible until `StyleContext` applies document-local conflict and idempotency rules.

The semantic materializer currently supports the required structured style families:

- `paragraph`;
- `text`;
- `graphic`;
- `table`;
- `table-column`;
- `table-row`;
- `table-cell`.

This means lifecycle integration no longer needs to wait for missing graphic or table family semantics.

### 4.2 Fill-image dependencies

`FillImageRequirementCollector` traverses the same ownership tree using `getOwnFillImageDependencies()`.

The corresponding materializer writes document-local `draw:fill-image` declarations independently of native subtree rendering. Physical bitmap copying remains a package concern.

### 4.3 Physical resources

`StructuredResourceCollector` traverses `ownedElements()` and collects `getOwnImageAssets()` before subtree materialization.

The current ownership/resource model therefore already supports:

```text
owned element tree
    ├── semantic styles/declarations -> document context
    └── physical resources           -> OdtPackage
```

No new ownership abstraction is required by D5F.

## 5. Legacy collector remains transitional

`StyleRequirementCollector` still exposes two projections:

```php
collectSemantic()
collect()
```

The historical `collect()` path yields engine-role families:

- `paragraph`;
- `text`;
- `frame`;
- `image`;
- `fill-image`.

Its comment still describes this projection as necessary until graphic families receive semantic producer contracts. That rationale is now stale after SR-06.

This does not prove the legacy projection can immediately be removed. It does prove that D5F must distinguish semantic lifecycle needs from compatibility-only state before any lifecycle abstraction is frozen.

## 6. Producer-by-producer lifecycle audit

### 6.1 Paragraph

Paragraph style names, paragraph options, inline text style maps, tab-stop definitions, and related semantic style requirements are established during construction/mutation before `toDomNode()`.

`getOwnStyleRequirements()` can therefore emit paragraph/text definitions or references before native materialization.

**Audit classification:**

```text
pre semantic state final:       yes
post semantic discovery needed: no evidence
materialization-only mutation:  no relevant semantic state
```

### 6.2 RichText

`RichText` is a semantic container. It exposes its children through `ownedElements()` and `toDomNode()` only creates a document fragment and delegates native rendering to the contained elements.

**Audit classification:**

```text
pre semantic state final:       yes, through owned children
post semantic discovery needed: no
materialization-only mutation:  none relevant
```

### 6.3 ListElement

`ListElement` exposes list items through `ownedElements()`. Native materialization creates `text:list` / `text:list-item` structure and delegates paragraph or nested-list rendering.

No requirement-producing state is established only after rendering.

**Audit classification:**

```text
pre semantic state final:       yes
post semantic discovery needed: no
```

### 6.4 RichTable / RichTableCell

After SR-07, the table path can emit semantic `table`, `table-column`, `table-row`, and `table-cell` requirements before DOM construction. Nested cell content participates in the same ownership traversal.

`RichTable::toDomNode()` still contains direct compatibility fallbacks such as `StyleWriter::writeColumnStyles()` and cell style DOM handling. These are not evidence that semantic table requirements are post-materialization-dependent; they are bounded legacy/direct-call compatibility behavior.

**Audit classification:**

```text
pre semantic state final:       yes
post semantic discovery needed: no evidence
legacy DOM fallbacks remain:    yes
likely D5G concern:             yes
```

### 6.5 DrawTextBox

`DrawTextBox::getOwnStyleRequirements()` derives semantic `graphic` properties from already-known frame options before `toDomNode()`.

`toDomNode()` recomputes the rendered style name and frame structure, but does not discover semantic properties from the rendered DOM. `requiresLegacyGraphicCarrier()` intentionally preserves a compatibility carrier for properties not represented by the migrated semantic subset.

**Audit classification:**

```text
pre semantic state final:       yes for migrated graphic subset
post semantic discovery needed: no evidence
legacy carrier remains:         yes
likely D5G concern:             yes
```

### 6.6 CircularImageElement

Circular images were an original D5 example for possible post-materialization requirements. The current implementation no longer supports that assumption.

Before `toDomNode()`, the element can already provide:

- its semantic `graphic` style through `getOwnStyleRequirements()`;
- its fill-image declaration through `getOwnFillImageDependencies()`;
- its physical image resource through the normal image asset path.

`toDomNode()` subsequently assigns compatibility fields such as the resolved fill-image and generated graphic style names. The code explicitly describes those assignments as preservation of legacy compatibility state.

**Audit classification:**

```text
pre style requirement final:        yes
pre fill-image dependency final:    yes
pre physical resource final:        yes
post semantic discovery needed:     no
post materialization mutation:      yes
reason for post mutation:           compatibility state
```

This is the strongest evidence that the historical generic post-discovery requirement may no longer describe the target architecture.

### 6.7 ImageElement

`ImageElement` is the only clear unresolved lifecycle case.

The element's inputs are already complete before materialization, but `toDomNode()` performs deterministic rendering decisions for alignment/wrap/position and then writes some of the rendered values back into `$imageOptions`, including where applicable:

- `style:wrap`;
- `style:horizontal-pos`;
- `style:horizontal-rel`;
- `style:vertical-pos`;
- `style:vertical-rel`.

These values are not discovered from an external renderer or from the completed document. They are derived from already-known input state while the DOM node is being constructed.

The present implementation therefore has post-materialization observable mutation, but this audit does not establish that the semantics are intrinsically post-materialization-dependent.

**Audit classification:**

```text
pre input state complete:            yes
derived graphic state calculated:   during toDomNode()
genuinely post-materialization:      not proven
post mutation observable:            yes
```

This case requires focused characterization before lifecycle code is changed.

## 7. StructuredElementMaterializer boundary

`StructuredElementMaterializer` currently has a narrow responsibility:

- normalize placeholder structure;
- call `element->toDomNode()`;
- replace the structured placeholder in content/styles DOMs.

It deliberately does not own package state, style registries, or template-language state.

This audit finds no evidence that D5F should turn it into a broad lifecycle coordinator or renderer registry. Its current boundary remains appropriate.

## 8. OdtDocumentContext boundary

`OdtDocumentContext` owns:

- `content.xml`, `styles.xml`, and `meta.xml` DOMs;
- `StyleContext`;
- font-face requirement registry;
- fill-image requirement registry.

`replaceCoreDocuments()` resets the document-local pending requirement state on package reload.

This remains a suitable document-local state boundary. D5F should not introduce a generic materialization context merely to regroup orchestration methods.

## 9. Audit matrix

| Concern | Pre-materialization final? | Mutated by `toDomNode()`? | Semantic post-pass required? | Compatibility-only residue? |
| --- | --- | --- | --- | --- |
| Paragraph/Text | Yes | No relevant state | No evidence | Legacy array APIs remain |
| RichText | Yes through children | No relevant state | No | Compatibility getters remain |
| ListElement | Yes through children | No relevant state | No | Compatibility getters remain |
| RichTable/Cell | Yes after SR-07 | Native DOM fallbacks only | No evidence | Yes |
| DrawTextBox | Yes for migrated semantics | Render mechanics/style-name recomputation | No evidence | Yes |
| CircularImage | Yes | Yes, compatibility fields | No | Yes, explicitly |
| ImageElement | Inputs yes; derived state unresolved | Yes | **Not proven** | Possibly mixed |
| Fill-image dependency | Yes | No | No | Legacy fill-image arrays remain |
| Physical image resource | Yes | No relevant state | No | n/a |

## 10. Core audit conclusion

The original D5 contract allowed a generic two-phase semantic lifecycle because requirements could plausibly become final only during native materialization.

After SR-06 and SR-07, the current repository contains **no proven producer whose semantic requirement is intrinsically discoverable only after native DOM materialization**.

The remaining cases instead fall into three categories:

```text
A. semantic requirements already final before materialization;
B. deterministic derived state currently calculated inside toDomNode();
C. state written during toDomNode() only to preserve legacy compatibility.
```

`ImageElement` is currently the only unresolved B/C boundary requiring focused characterization.

Therefore D5F should not begin by introducing a generic pre/post materialization framework.

## 11. Revised D5F problem statement

Subject to characterization, the likely D5F goal is:

> Integrate the normal `setElement()` lifecycle around one semantically authoritative pre-materialization requirement/resource path, while preserving only those post-materialization behaviors that characterization proves are functionally required.

A likely target flow is:

```text
setElement(root)
    |
    +-- collect semantic style requirements
    +-- collect fill-image dependencies
    +-- collect physical resources
    |
    +-- register document-local requirements
    +-- prepare package resources
    +-- materialize declarations/styles
    |
    +-- StructuredElementMaterializer::insert()
```

A generic semantic post-pass should be introduced only if characterization proves a genuine producer requirement for it.

## 12. D5F versus D5G boundary

D5F should remain focused on the active normal `setElement()` lifecycle:

- semantic requirement/resource ordering;
- deterministic producer state required before insertion;
- avoiding unnecessary duplicate collection;
- stable materialization ordering;
- repeated insertion/save behavior relevant to that active path.

D5G should remain the compatibility closeout phase and is the appropriate place to review or retire, where evidence allows:

- legacy `StyleRequirementCollector::collect()` projection;
- old `StyleMapper` / `StyleWriter` registration/finalization bridges;
- protected compatibility hooks;
- legacy structured-value `assign()` / `render()` behavior;
- save/finalization compatibility state;
- legacy graphic carriers and old `getOwnFrameStyleRequirements()` / related array APIs.

D5F must not silently absorb full D5G compatibility cleanup.

## 13. Required characterization before a D5F Change Contract

The audit is complete, but implementation is not yet approved.

The next evidence gate should characterize only the unresolved lifecycle questions:

1. **ImageElement state transition**
   - capture relevant style/requirement-visible state before and after `toDomNode()`;
   - prove which values change;
   - prove whether the post-state is derivable without native materialization;
   - verify rendered DOM/style identity remains unchanged if derivation is moved or exposed earlier.

2. **Legacy collector before/after materialization**
   - compare `collect()` results for representative Paragraph, ImageElement, CircularImageElement, DrawTextBox, and table subtrees before and after native rendering;
   - identify which changes are semantically required versus compatibility-only.

3. **Active `setElement()` ordering**
   - characterize ordering dependencies among semantic registration, font/fill-image materialization, resource copying, native insertion, and compatibility collection.

4. **Lifecycle stability**
   - preserve repeated `setElement()` / `save()` / `refresh()` behavior;
   - preserve document-local conflict handling and style-idempotency;
   - preserve package resource idempotency.

Only after these focused tests should D5F receive a Change Contract.

## 14. Explicit non-goals of the next D5F step

The characterization step must not:

- introduce a new lifecycle service or context;
- refactor `StructuredElementMaterializer` into a renderer framework;
- remove legacy collector methods;
- remove `StyleMapper` or `StyleWriter` compatibility behavior;
- migrate the legacy `assign()` / `render()` path;
- redesign images, frames, anchors, wrapping, tables, lists, or layout APIs;
- change visible ODF output merely to simplify orchestration;
- merge D5F and D5G.

## 15. Audit status

**D5F-A Lifecycle / Materialization Audit: PASS.**

The current architecture is sufficiently coherent to proceed to a small characterization gate. The key result is that a generic semantic post-materialization discovery phase is no longer justified by repository evidence. `ImageElement` remains the only unresolved producer whose deterministic derived state currently changes during `toDomNode()` and must therefore be characterized before the D5F Change Contract is written.

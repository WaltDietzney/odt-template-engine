# D5G — Compatibility Surface Audit

Status: **AUDIT — NO IMPLEMENTATION AUTHORIZED**

Base: `develop` after D5F merge (`7ca1d435c196db620be76e9099db79db16b067db`).

## 1. Purpose

D5G closes the remaining compatibility transition around the document-local semantic style/dependency architecture. This audit deliberately does not remove legacy APIs or change runtime behavior.

The central question is not whether code is old. It is whether a compatibility surface is:

1. an externally observable public contract;
2. a protected polymorphic extension surface;
3. a still-required compatibility bridge for a legacy lifecycle;
4. or internal transition infrastructure that can eventually be narrowed without changing behavior.

D5F established the authoritative lifecycle for `setElement()`:

```text
constructed OdtElement subtree
    -> semantic requirements / fonts / typed fill-image dependencies / resources
    -> document/package preparation
    -> semantic materialization
    -> StructuredElementMaterializer::insert()
       -> element->toDomNode()
    -> bounded legacy frame/image/fill-image adoption
```

No current semantic producer requires generic post-materialization discovery. D5G therefore begins from the invariant that compatibility behavior must not become a competing semantic lifecycle.

## 2. Highest-risk finding: two structured-element lifecycles remain

The normal structured API, `OdtTemplate::setElement()`, now uses the D5F lifecycle.

The legacy `assign()` / `render()` path does not. `render()` eventually calls `setValuesInDom()` for both `content.xml` and `styles.xml`. When an assigned value is an `OdtElement`, `setValuesInDom()`:

```text
marks legacyStructuredValuesMaterialized
    -> calls element->toDomNode(dom)
    -> registers legacy graphic requirements
    -> replaces the placeholder
```

This path does not perform the D5F semantic pre-materialization preparation used by `setElement()`.

This is the primary D5G compatibility boundary. It must be characterized before any attempt to unify, deprecate, or remove it.

### Consequences

- the same `OdtElement` instance may be rendered against both `content.xml` and `styles.xml`;
- mutable rendering-local state can therefore be touched more than once;
- resource/style preparation differs from `setElement()`;
- save/finalization behavior is later switched by a document-wide legacy flag;
- mixed use of `setElement()` and `assign(OdtElement)` can activate both lifecycle models in one document.

D5G-B should treat these as characterization targets, not assumed defects.

## 3. Document-wide legacy lifecycle switch

`OdtTemplate` contains:

```php
private bool $legacyStructuredValuesMaterialized = false;
private array $legacyFrameStylesMaterialized = [];
```

`legacyStructuredValuesMaterialized` is set when the legacy `assign()` / `render()` path materializes an `OdtElement`. The flag is reset by `load()`.

The flag is not merely bookkeeping. It changes save/finalization policy for the whole document. In `save()` it affects:

- whether protected legacy image-style injection is activated;
- whether legacy frame styles are written;
- which referenced frame styles are still pending;
- whether semantic table-cell exclusions are passed to `StyleWriter`;
- whether table/table-cell reference filters are passed to `StyleWriter`.

`refresh()` has a related but not identical finalization call and then persists the core XML before calling `load()`, which resets the flag and materialized-frame set.

### Audit classification

| Surface | Visibility | Current role | Classification | D5G action |
| --- | --- | --- | --- | --- |
| `legacyStructuredValuesMaterialized` | private | coarse document-wide finalization mode switch | internal transition infrastructure with observable effects | characterize before narrowing |
| `legacyFrameStylesMaterialized` | private | prevents repeated legacy frame emission | internal compatibility state | characterize repeated save/render |
| `load()` reset of both | public lifecycle effect | resets compatibility mode with package reload | observable lifecycle behavior | preserve until characterized |
| `refresh()` interaction | public lifecycle effect | persist/finalize/reload boundary | observable lifecycle behavior | characterize explicitly |

A future implementation should avoid replacing this with a speculative `CompatibilityContext` or lifecycle framework unless evidence requires one.

## 4. Legacy graphic registration path

`registerLegacyGraphicRequirements(OdtElement $element)` remains a private legacy bridge used by `setValuesInDom()` after `toDomNode()`.

It writes to process-wide `StyleMapper` registries:

- frame styles;
- image styles;
- fill-image declarations;

and copies fill-image resources to the package.

This path uses the historical subtree getters:

- `getFrameStyleRequirements()`;
- `getImageStyleRequirements()`;
- `getFillImageRequirements()`.

It is therefore distinct from the D5F semantic collector/dependency/resource pipeline.

### Important limitation

The existence of global registries does not mean unrelated global state is blindly emitted. Earlier style-refactor work already narrowed adoption to names referenced by the current document. That isolation must be preserved during D5G.

## 5. Static StyleMapper registries

`StyleMapper` still owns process-wide mutable compatibility registries for several families, including text, paragraph, table-cell, table, frame, image, fill-image, and fonts. A dedicated `LegacyStyleRegistry` also preserves global first-write-wins paragraph behavior for legacy callers.

These registries must be split conceptually into two questions:

1. Is the **public/static registration API** externally compatible behavior?
2. Is the **active document lifecycle** still required to use that registry internally?

D5G should not answer both questions by deleting static state wholesale.

### Audit classification

| Surface | Visibility | Semantic authority? | Likely disposition |
| --- | --- | --- | --- |
| StyleMapper mapping helpers | public static | mapping utility, not document ownership | keep unless separate API work |
| StyleMapper registration/getter APIs | public static | no, compatibility facade | preserve or explicitly deprecate; do not silently break |
| `StyleMapper::$frameStyles` | public static property | no | high compatibility risk; characterize external use assumptions |
| LegacyStyleRegistry paragraph store | internal class, process-wide | no | retain until legacy paragraph API policy is explicit |
| active `setElement()` semantic path | document-local | yes | authoritative |

## 6. StyleWriter as compatibility router

`StyleWriter::writeAllStyles()` is still public static and supports both legacy/static and document-scoped finalization through parameters such as:

```text
includeLegacyParagraphStyles
includeLegacyTextStyles
includeLegacyFrameStyles
legacyFrameStyleNames
excludedTableCellStyleNames
allowedTableStyleNames
allowedTableCellStyleNames
```

The current `OdtTemplate::save()` deliberately calls this differently depending on whether legacy structured values were materialized.

This means the method is no longer merely a dumb writer; it is also a compatibility routing surface.

D5G must determine which of these controls are:

- required external API semantics;
- only used internally by `OdtTemplate`;
- temporary migration switches;
- or necessary for repeated-save/document-reference compatibility.

No signature change is authorized by this audit.

## 7. Public OdtElement compatibility getters

`OdtElement` currently exposes both the semantic ownership model and historical array-based style/resource APIs.

### Semantic/document-local model

- `ownedElements()`;
- `getOwnStyleRequirements()`;
- `getOwnFillImageDependencies()`;
- `getOwnImageAssets()`.

Traversal is owned by collectors.

### Historical compatibility model

- `getRequiredStyles()`;
- `getOwnRequiredStyles()`;
- `getOwnRequiredParagraphStyles()`;
- `getOwnFrameStyleRequirements()`;
- `getOwnImageStyleRequirements()`;
- `getOwnFillImageRequirements()`;
- `getFrameStyleRequirements()`;
- `getImageStyleRequirements()`;
- `getFillImageRequirements()`;
- `getImageAssets()`;
- `getStyleDefinitions()`.

The subtree graphic getters perform traversal themselves through embedded elements, while the semantic architecture expects elements to declare only their own requirements and collectors to traverse ownership.

### Compatibility risk

These methods are public and can be overridden by external subclasses. Removing them or changing traversal semantics would be a source- and behavior-compatibility decision, not routine cleanup.

D5G should first determine:

- which methods still have internal callers on `develop`;
- which are used only by the legacy `assign()` / `render()` path;
- which are sampled/documented publicly;
- which can become thin compatibility facades over semantic state without changing subclass polymorphism;
- which must remain indefinitely.

## 8. Protected OdtTemplate extension surfaces

Earlier architecture work intentionally preserved protected dispatch. Existing characterization proves that public `render()` still dispatches through inherited protected `fixBrokenVariables()` and `setValuesInDom()` overrides; `setElement()` replacement remains observable through a protected `replacePlaceholderWithDom()` override; and `save()` still reaches the protected `adjustBulletIndentation()` override.

This means protected methods are a real compatibility surface, not merely internal implementation details.

Relevant D5G surfaces include at least:

- `setValuesInDom()`;
- `replacePlaceholderWithDom()`;
- `hasPlaceholder()`;
- `injectImageStyles()`;
- `replaceImageInNamedDom()`;
- template-processing facade methods used by public lifecycle entry points;
- save/finalization hooks such as `adjustBulletIndentation()`.

`injectImageStyles()` is particularly illustrative: the active semantic save path is document-owned, but the protected method remains as a compatibility hook and still performs legacy behavior when the legacy structured path was used.

Preferred rule for D5G:

> Remove internal dependence where safe, but retain a thin protected facade when existing polymorphic behavior is part of the compatibility contract.

## 9. `assign()` / `render()` compatibility surface

`assign()` remains public and stores arbitrary values. `render()` processes both `content.xml` and `styles.xml` and accepts `OdtElement` values through `setValuesInDom()`.

The structured-value behavior of `assign()` / `render()` is therefore observable legacy API even if `setElement()` is now the architecturally preferred structured insertion path.

The current implementation needs characterization for:

1. Paragraph/RichText/List structured values;
2. ImageElement;
3. CircularImageElement;
4. DrawTextBox;
5. RichTable;
6. nested structured elements;
7. placeholders present only in content.xml;
8. placeholders present only in styles.xml;
9. the same placeholder in both parts;
10. repeated `render()`;
11. `render()` followed by repeated `save()`;
12. `refresh()` after legacy structured rendering;
13. `load()` after legacy structured rendering;
14. mixed `setElement()` plus `assign(OdtElement)` in one document;
15. two independent templates in one process to verify static-registry isolation by current-document references.

D5G-B should be characterization-only unless a test exposes an immediately dangerous regression.

## 10. Save/finalization compatibility

The current save path consists of several generations of architecture:

```text
injectImageStyles() protected compatibility hook
    -> legacy-only image/fill-image injection when applicable

injectDocumentGraphicStyles()
    -> document-owned frame/image/fill-image compatibility state

FontFaceRequirementMaterializer
    -> document-local semantic font dependencies

StyleWriter::writeAllStyles(...)
    -> configured mixture of legacy/static and document-reference filtered families

adjustBulletIndentation()
    -> protected lifecycle hook

OdtPackage::saveAs()
```

D5G should not collapse these layers before distinguishing semantic authority from retained compatibility.

### Key question

Can the legacy structured path eventually prepare/adopt its requirements into the existing document-local structures while preserving all public/protected behavior, thereby reducing the document-wide mode switch?

This is a design hypothesis only. It requires D5G-B evidence and a Change Contract before implementation.

## 11. Refresh/load semantics

`refresh()` finalizes the current DOM, persists core documents, and then calls `load()`. `load()` resets package state from the template preparation path and resets legacy lifecycle flags.

This is potentially subtle after legacy structured rendering. D5G must characterize what survives and what intentionally resets across:

```text
assign structured -> render -> refresh
setElement -> refresh
mixed path -> refresh
repeated refresh
```

No semantic correction should be made during the audit.

## 12. Compatibility surface matrix

| Surface | Visibility | Current internal caller | External caller possible? | Semantic authority | D5G classification |
| --- | --- | --- | --- | --- | --- |
| `setElement()` | public | application | yes | yes, facade to authoritative lifecycle | keep |
| `assign()` / structured `render()` | public | application | yes | no, legacy structured lifecycle | characterize/preserve initially |
| `setValuesInDom()` | protected | `render()` | yes via subclass override | no | protected facade; characterize |
| `replacePlaceholderWithDom()` | protected | both structured paths | yes | no | preserve polymorphism |
| `injectImageStyles()` | protected | `save()` | yes | no | compatibility facade |
| `registerLegacyGraphicRequirements()` | private | legacy structured render | no | no | transition candidate |
| `injectLegacyImageStyles()` | protected deprecated | compatibility hook | yes | no | high compatibility caution |
| `legacyStructuredValuesMaterialized` | private | render/save/refresh logic | no | no | narrowing candidate after tests |
| `legacyFrameStylesMaterialized` | private | repeated save | no | no | narrowing candidate after tests |
| `StyleRequirementCollector::collectSemantic()` | public service | `setElement()` lifecycle | library caller possible | yes for semantic styles | keep |
| `StyleRequirementCollector::collect()` | public service | legacy compatibility | library caller possible | no | D5G review; do not remove yet |
| public OdtElement legacy getters | public | collector / legacy render | yes, subclass override | no | preserve until policy decision |
| StyleMapper static registries | public/static or exposed via public statics | legacy writers/callers | yes | no | compatibility facade |
| StyleWriter public static API | public | `OdtTemplate` and callers | yes | mixed | characterize signature/usage |
| OdtDocumentContext / StyleContext | document-local | semantic lifecycle | library caller possible | yes | authoritative |
| OdtPackage resources | document package | semantic/resource lifecycle | internal/facade | yes for physical package state | authoritative |

## 13. Stale documentation/code comments found

`StyleRequirementCollector::collectSemantic()` still states that legacy `collect()` remains until graphic families receive semantic producer contracts. That statement is stale after SR-06/SR-07. Graphic semantic producers already exist for the migrated structured families; the remaining `collect()` path is a compatibility projection.

This is documentation debt, not authorization to remove `collect()`.

A later bounded D5G documentation cleanup may correct the comment.

## 14. D5G-A conclusions

### Proven

1. D5F's `setElement()` lifecycle is now semantically authoritative.
2. A separate structured `assign()` / `render()` lifecycle remains active.
3. That legacy path materializes first and adopts legacy graphic requirements afterward.
4. A single private boolean currently changes multiple save/finalization policies for the entire document.
5. Static registries remain compatibility facades, but current-document reference filtering already prevents unconditional cross-document adoption.
6. Public OdtElement legacy getters and protected OdtTemplate hooks are compatibility surfaces with external subclass risk.
7. `StyleWriter` still serves both legacy/static and document-scoped finalization modes.
8. Repeated render/save, refresh/load, mixed structured paths, and content.xml/styles.xml behavior are not sufficiently characterized for safe cleanup.

### Not proven

This audit does **not** prove that:

- `legacyStructuredValuesMaterialized` can be removed;
- the legacy structured `assign()` / `render()` behavior can be routed directly through `setElement()`;
- public legacy getters can be deprecated or removed;
- protected hooks can be removed;
- StyleMapper static registries can be deleted;
- StyleWriter compatibility parameters can be collapsed;
- frame/image/fill-image compatibility carriers are redundant;
- refresh/load behavior should change.

## 15. Recommended D5G sequence

```text
D5G-A Compatibility Surface Audit — this document
        ↓
D5G-B Legacy Structured Lifecycle Characterization
        ↓
D5G Change Contract
        ↓
D5G-C Legacy lifecycle compatibility narrowing
        ↓
D5G-D Static/finalization compatibility narrowing
        ↓
D5G-E Regression closeout
        ↓
STYLE-CONTEXT-01 final closeout
```

The exact C/D split remains provisional until D5G-B evidence is available.

## 16. D5G-B characterization contract candidate

D5G-B should add tests before production changes and answer, for each scenario:

```text
entry point
DOM part(s) touched
semantic requirements available?
legacy requirements registered?
physical resources copied?
StyleContext state after render?
StyleMapper state after render?
save output
repeat render behavior
repeat save behavior
refresh/load behavior
cross-document isolation
protected override dispatch
```

At minimum, characterize Paragraph/RichText, ImageElement, CircularImageElement, DrawTextBox, and RichTable through `assign()` / `render()`, plus mixed modern/legacy insertion and independent-document scenarios.

## 17. Governing rule for later D5G implementation

> Compatibility facades may remain public or protected even when their internal implementation is no longer authoritative. D5G should remove duplicate internal state and transition machinery only where characterization proves the observable contract can be preserved.

This keeps backward compatibility separate from internal architecture debt and prevents D5G from becoming a broad rewrite.

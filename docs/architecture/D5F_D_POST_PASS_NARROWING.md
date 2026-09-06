# D5F-D — Redundant Post-Pass Narrowing

Status: **IMPLEMENTATION RECORD — POST-PASS NARROWING**

Base: `architecture/d5f-c-lifecycle-orchestration` at `14ba108`

## 1. Scope

D5F-D narrows the second legacy `StyleRequirementCollector::collect()` pass
in the normal `OdtTemplate::setElement()` lifecycle only where D5F-B proved
the post-materialization operation redundant.

The semantic pre-materialization path, physical resource preparation, native
materialization, legacy getters, static registries, protected hooks, and
legacy structured lifecycle remain unchanged.

## 2. Decision matrix

| Family / producer | Pre-materialization state | Post-materialization state | Identical? | Post-pass side effect | Safe to remove? | Decision |
| --- | --- | --- | --- | --- | --- | --- |
| `paragraph` / Paragraph | Legacy paragraph definitions already collected and registered before insertion | Same definitions | Yes | Re-registers paragraph styles | Yes | Removed from post phase |
| `text` / Paragraph, RichText, ListElement | Legacy text definitions already collected and registered before insertion | Same definitions | Yes | Re-registers text styles | Yes | Removed from post phase |
| `frame` / DrawTextBox | Frame compatibility definition is stable and available through the existing compatibility path | Same frame definition in characterization | Yes for characterized producer | Re-registers frame carrier | Not narrowed in D5F-D | Retained as a family-level compatibility carrier; protected/subclass compatibility remains unresolved for D5G |
| `image` / ImageElement | Mapped options and legacy image identity exist; placement-derived values are not yet synchronized | `style:wrap`, horizontal placement, and applicable vertical values are synchronized into `imageOptions` | No | Adopts post-render image compatibility definition | No | Retained |
| `fill-image` / CircularImageElement | Typed fill-image dependency, semantic graphic requirement, and physical asset are complete; legacy arrays are initially empty | `$fillImageName`, `$circularStyleName`, and `$circularStyleOptions` become observable | No for legacy state | Adopts CircularImage legacy compatibility state | No | Retained |
| Paragraph / RichText / ListElement subtree | Owned semantic and legacy states are complete | Native DOM rendering adds no requirement state | Yes | Only paragraph/text re-registration | Yes | Covered by removed branches |
| DrawTextBox subtree | Graphic semantic and compatibility state is known | Rendering recomputes native structure/style identity | Yes in characterization | Frame compatibility registration remains | Not narrowed | Retained conservatively |
| ImageElement nested in Paragraph/RichText | Resource and semantic projections are complete | Legacy image options synchronize during rendering | No for legacy state | Image compatibility adoption | No | Retained |
| CircularImageElement nested in structured content | Fill-image/graphic/resource projections are complete | Legacy fill-image/graphic getters become populated | No for legacy state | CircularImage compatibility adoption | No | Retained |
| RichTable / RichTableCell | Table, column, row, cell requirements and resources are complete | Collector output remains stable; direct rendering may execute compatibility fallbacks | Yes in characterization | No paragraph/text side effect; graphic family handling remains generic | Not broadened | Existing family compatibility retained |

The matrix distinguishes semantic state from deterministic rendering-local
state, legacy compatibility state, and physical package resources. Equality of
semantic or resource projections does not authorize removal of a legacy state
transition that remains externally observable.

## 3. Exact implementation change

Before D5F-D, `finalizeStructuredCompatibility()` re-registered every legacy
collector family after native materialization:

```text
paragraph -> registerParagraphStyle()
text      -> registerTextStyle()
frame     -> registerLegacyGraphicCompatibilityState()
image     -> registerLegacyGraphicCompatibilityState()
fill-image -> registerLegacyGraphicCompatibilityState()
```

After D5F-D, the post phase acts only on the legacy graphic compatibility
families:

```text
frame / image / fill-image -> registerLegacyGraphicCompatibilityState()
paragraph / text           -> no post action
```

The legacy collector itself was not changed. The pre-materialization
paragraph/text path remains responsible for:

- `semanticOwnedLegacyStyles()`;
- `isSemanticParagraphTextRequirement()`;
- `ensureParagraphStylesExist()`;
- `ensureTextStylesExist()`.

## 4. ImageElement decision

ImageElement remains in the post phase. `toDomNode()` deterministically writes
derived placement values into `$imageOptions`, and
`getOwnImageStyleRequirements()` observes the resulting compatibility
definition. The style identity remains stable, but the definition contents
change after rendering.

D5F-D does not precompute, redesign, or migrate this behavior to semantic
graphic requirements. The post-pass continues to adopt the resulting legacy
image state.

## 5. CircularImageElement decision

CircularImageElement remains in the post phase. Its semantic graphic
requirement, typed FillImageRequirement, and physical image asset are already
complete before rendering. However, `toDomNode()` populates legacy state used
by `getImageStyleRequirements()` and `getFillImageRequirements()`.

That state remains observable and is therefore retained as compatibility
adoption, not semantic discovery.

## 6. Stable graphic compatibility

The characterized DrawTextBox path has stable pre/post legacy output, and its
compatibility definition is available through the existing `HasStyles`
compatibility path. D5F-D nevertheless retains the `frame` branch in the
generic graphic compatibility boundary. This avoids changing family-level
dispatch for external subclasses or uncharacterized frame producers and does
not remove a public/protected compatibility surface.

This conservative boundary is a D5G review candidate, not an attempt to
declare all frame compatibility redundant.

## 7. Preserved behavior

The D5F-B characterization suite remains unchanged and green. In particular,
D5F-D preserves:

- ImageElement post-render options and stable style identity;
- CircularImage legacy post-render state;
- repeated `setElement()` and `save()` behavior;
- independent-document isolation;
- semantic requirements and materialized styles;
- FillImage declarations and physical resources;
- protected StructuredElementMaterializer callbacks;
- legacy `assign()`/`render()` and finalization paths.

No generic semantic post-discovery phase was introduced.

## 8. D5G handoff

The following remain explicitly deferred to D5G:

- removing or narrowing `StyleRequirementCollector::collect()` itself;
- StyleMapper/StyleWriter compatibility policy;
- protected extension-surface decisions;
- legacy graphic carriers and getters;
- ImageElement post-render synchronization;
- CircularImageElement legacy state;
- frame compatibility narrowing for external or uncharacterized producers;
- legacy `assign()`/`render()` and save/finalization behavior.

## 9. Validation evidence

The focused D5F and compatibility suites passed with 50 tests and 413
assertions. Full SR-06/SR-07/resource/table regression and the full Composer
suite were run after the implementation. No production element class or
collector was changed; the only production change is the private orchestration
filter in `OdtTemplate`.

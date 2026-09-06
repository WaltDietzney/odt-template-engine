# D5G-B — Legacy Structured Lifecycle Characterization

Status: **CHARACTERIZATION COMPLETE — NO PRODUCTION CHANGE**

Base: `origin/architecture/d5g-compatibility-surface-audit` at
`d7843562e6f0e983ababcdda20984738a94188a9`

## 1. Scope and non-goals

This slice characterizes the public legacy lifecycle:

```text
assign([placeholder => OdtElement])
    -> render()
    -> save()
```

It records observed API, DOM, package, registry, and lifecycle effects. It
does not remove compatibility paths, introduce a lifecycle abstraction, make
the legacy path delegate to `setElement()`, or define the D5G Change Contract.
Unexpected behavior is evidence for a later decision, not a correction here.

The focused tests are in
`tests/Integration/D5GLegacyStructuredLifecycleCharacterizationTest.php`.

## 2. Observed legacy lifecycle

`render()` processes both core DOMs. For each assigned `OdtElement`,
`setValuesInDom()` sets `legacyStructuredValuesMaterialized` to `true`, calls
`toDomNode($dom)`, registers legacy graphic requirements, and then performs
placeholder replacement. The same assigned object can therefore be rendered
against `content.xml` and `styles.xml`.

In a controlled probe with a placeholder appended to both document parts,
`toDomNode()` was called exactly twice. A missing placeholder still sets the
legacy flag because the flag is set before replacement is attempted.

The legacy path does not execute the D5F semantic pre-materialization
preparation. In particular, it does not automatically register semantic
requirements in the document-local `StyleContext`, and it does not use the
normal `StructuredResourceCollector` preparation phase.

## 3. Producer matrix

| Producer | Before render | After render | After save | Characterized result |
| --- | --- | --- | --- | --- |
| Paragraph | legacy/semantic getters available | paragraph DOM inserted | text/paragraph compatibility finalized | observable content and styles |
| RichText | owned paragraphs available | child subtree inserted | child compatibility finalized | observable content and styles |
| ListElement | owned list items available | list DOM inserted | legacy child styles finalized | observable list content |
| ImageElement | image options and legacy identity available | frame rendered; derived wrap/position state synchronized | legacy image style finalized; resource is not copied by this path | content stable on repeat, styles change on repeat |
| CircularImageElement | semantic graphic/fill/resource projections available, legacy circular arrays empty | custom shape rendered; legacy fill/style state populated | graphic/fill declarations and resource are emitted | semantic state is not adopted into document context automatically |
| DrawTextBox | frame/graphic getters available | frame/text-box DOM inserted | legacy frame style finalized | frame compatibility remains observable |
| RichTable | table/column/row/cell semantic projections exist on element | table structure and legacy cell/column fallbacks rendered | legacy finalization applies | legacy output differs from `setElement()` in ownership/materialization |

The semantic availability shown in the table is an element-level observation.
It does not mean the legacy path registers that state in `OdtDocumentContext`.

## 4. Comparison with `setElement()`

Representative packages were generated through both paths for Paragraph,
ImageElement, CircularImageElement, DrawTextBox, and RichTable.

Observed package comparison:

| Producer | `content.xml` | `styles.xml` | Manifest | Classification |
| --- | --- | --- | --- | --- |
| Paragraph | same | different | same | compatibility finalization difference |
| ImageElement | same | different | different | legacy resource-preparation difference |
| CircularImageElement | same | different | same | legacy/static style difference |
| DrawTextBox | same | different | same | legacy frame finalization difference |
| RichTable | different | different | same | semantic versus legacy table ownership/materialization difference |

These differences are characterized observations, not approved parity targets.
The normal `setElement()` path owns semantic document preparation; the legacy
path retains historical registration and finalization behavior.

## 5. Repeated `render()` and `save()`

For Paragraph, RichText, ListElement, CircularImageElement, DrawTextBox, and
RichTable:

```text
assign()
render()
save(A)
render()
save(B)
```

produced identical `content.xml` in the focused probe. `META-INF/manifest.xml`
was also stable. `styles.xml` changed for every representative producer,
which characterizes repeated legacy style finalization/re-registration rather
than repeated native subtree insertion.

For ImageElement, repeated render/save additionally preserves the synchronized
legacy image options and style identity, while the legacy path does not copy
the image asset into the package. The current tests explicitly preserve that
observed distinction from `setElement()`.

Repeated legacy registration remains registry-idempotent by style-name in the
existing compatibility tests, but the serialized styles document can still
change across repeated lifecycle calls.

## 6. ImageElement findings

The legacy lifecycle preserves the D5F-observed deterministic synchronization.
For alignment and positioning variants, `toDomNode()` writes derived values
such as `style:wrap`, `style:horizontal-pos`, `style:horizontal-rel`, and
applicable vertical values into `imageOptions`. A second render observes the
same already-resolved options and the style identity remains stable.

Legacy image requirements are registered in `StyleMapper` during render/save
compatibility handling. The legacy path does not perform the D5F physical
resource preparation, so an image reference can appear in the document while
the corresponding `Pictures/*` entry is absent. This is observed compatibility
behavior and remains uncorrected here.

## 7. CircularImageElement findings

Before legacy rendering, the element exposes:

- a semantic graphic requirement;
- a typed `FillImageRequirement`;
- a physical image asset;
- empty legacy fill-image state.

After `toDomNode()`/legacy registration, `$fillImageName`,
`$circularStyleName`, and `$circularStyleOptions` become observable through the
legacy getters. The legacy path registers the fill-image and copies its package
resource through its own compatibility bridge. It does not automatically
register the semantic requirements in the current document's `StyleContext`.

This is a lifecycle asymmetry, not evidence that semantic discovery must move
after native materialization.

## 8. DrawTextBox and frame compatibility

DrawTextBox frame and semantic graphic information is available before
rendering. Legacy assign/render registers the frame compatibility style and
save finalization writes the current referenced frame definition. Repeated
render/save preserves the native content while the legacy styles document can
change through compatibility finalization.

The frame registry remains process-global compatibility state. Existing
current-document filtering prevents an unrelated frame registration from
appearing in the later document's saved styles, while the static registry entry
itself remains observable.

## 9. RichTable findings

Legacy RichTable rendering was characterized with table style data, a styled
cell, explicit column widths, row `min-row-height`, and a caller-defined
colspan. The legacy path renders the table structure and preserves the
structural cell span. Its output is not the same ownership path as normal
`setElement()`:

- semantic table/column/row/cell requirements are available on the element;
- legacy rendering does not first register them in `StyleContext`;
- `toDomNode()` and compatibility writers provide the observed legacy output;
- normal `setElement()` remains the authoritative document-local semantic path.

The legacy path therefore must not be inferred to provide the full D5F semantic
table lifecycle merely because its final ODF contains table structure.

## 10. `content.xml` / `styles.xml` dual processing

The controlled dual-placeholder probe showed two separate `toDomNode()` calls,
one per DOM part, with the same element instance passed to both calls. This
means mutable rendering-local state can be touched twice. The replacement
operation is independently attempted in each part; absence of a placeholder
does not prevent materialization or legacy registration for that DOM pass.

The existing public tests also characterize structured replacement in
`styles.xml`, including header/template-style placeholders. This document does
not declare that both parts are equally useful or desirable targets; it records
the current general `render()` behavior.

## 11. `legacyStructuredValuesMaterialized` decision matrix

| Legacy structured use | Flag before | Flag after `render()` | Observable save effect |
| --- | ---: | ---: | --- |
| no structured value | false | false | legacy image injection and legacy frame/table routing remain disabled |
| Paragraph/RichText/List | false | true | legacy finalization mode enabled for the document |
| ImageElement | false | true | legacy image injection and legacy style routing enabled |
| CircularImageElement | false | true | legacy graphic/fill-image finalization enabled |
| DrawTextBox | false | true | referenced legacy frame finalization enabled |
| RichTable | false | true | legacy table/table-cell routing is selected |
| missing placeholder | false | true | same document-wide save switch; replacement may be a no-op |
| `setElement()` only | false | false | document-local semantic finalization path remains selected |
| after `load()` | reset to false | false until next legacy render | original package is reloaded and compatibility mode is reset |
| after `refresh()` | true before call | false after reload | refresh writes core documents using its own finalization call, then reloads |

The evidence shows that the Boolean is a coarse document-wide compatibility
mode switch, not a precise producer-specific state model. It bundles image,
frame, table, and table-cell save decisions. Whether this should be split is a
Change Contract question, not a D5G-B implementation decision.

## 12. Mixed lifecycle

Both mixed orderings were characterized with a semantic paragraph and a legacy
image after adding a second content placeholder. Both subtrees remain
observable in the resulting content. The legacy flag is activated as soon as
the assigned structured value is processed, so subsequent save finalization
uses the document-wide legacy branch. No production change was made to define
precedence between the two lifecycle models.

## 13. Document isolation

Two independent legacy documents were exercised in one process. Static frame
registrations remain observable in `StyleMapper` after document A is rendered.
Document B's saved styles did not contain A's unrelated frame definition.
This distinguishes:

```text
global registry contains entry  !=  entry affects unrelated document output
```

The same current-document adoption principle is covered by the existing image,
fill-image, frame, table, and table-cell compatibility tests. Physical image
resources are package-local; no resource from document A was observed in B's
package through the tested paths.

## 14. `refresh()` / `load()`

`load()` resets `legacyStructuredValuesMaterialized` and the materialized frame
set, but does not clear process-global StyleMapper registries. The focused flag
test also shows that a missing placeholder still activates the flag before
`load()` resets it.

`refresh()` persists core documents using its distinct finalization call and
then invokes the load boundary. For a legacy structured paragraph, the saved
DOM is reloaded from the template state and the inserted value is not present
after the refresh in the tested template. The flag is false after refresh.
Existing characterization tests additionally show that refresh skips the
legacy image/fill-image finalization that `save()` performs.

These are observed lifecycle semantics. No parity or reload behavior is fixed
in D5G-B.

## 15. Protected compatibility and public getters

Existing compatibility tests confirm that protected dispatch remains
observable for `setValuesInDom()`, placeholder replacement, image injection,
and save-related hooks. D5G-B did not alter or bypass those surfaces.

Getter usage inventory:

| Getter family | Current observation |
| --- | --- |
| `getRequiredStyles()` | used by paragraph/RichText/List/Table compatibility code and tests; override-sensitive |
| `getOwnRequiredStyles()` / `getOwnRequiredParagraphStyles()` | consumed by the legacy `StyleRequirementCollector` |
| `getOwnFrameStyleRequirements()` | DrawTextBox producer/tests; compatibility-facing |
| `getOwnImageStyleRequirements()` | ImageElement and lifecycle/compatibility tests; compatibility-facing |
| `getOwnFillImageRequirements()` | CircularImageElement and fill-image collectors/tests |
| `getFrameStyleRequirements()` | legacy `OdtTemplate` registration and compatibility tests |
| `getImageStyleRequirements()` | legacy `OdtTemplate` registration and compatibility tests |
| `getFillImageRequirements()` | legacy `OdtTemplate` registration and compatibility tests |
| `getStyleDefinitions()` | `HasStyles` registration, table/cell traversal, and compatibility tests |
| `getImageAssets()` | package/resource collection, section mutation, importers, and tests |

No public getter was removed or normalized. Several are used by both current
compatibility code and externally overrideable element implementations; they
must remain protected from opportunistic cleanup.

## 16. Evidence for the D5G Change Contract

The evidence establishes these boundaries:

1. `assign()`/`render()` is a distinct public compatibility lifecycle.
2. Its structured values can be rendered against both core DOMs.
3. The same element may be mutated more than once by rendering.
4. Semantic element projections can exist without entering document-local
   semantic context through the legacy path.
5. ImageElement and CircularImageElement retain observable post-render legacy
   state.
6. The legacy Boolean is a coarse save/finalization switch.
7. Static registries remain globally observable, while current-document
   filtering limits tested output leakage.
8. Repeated legacy render changes styles XML even when native content and
   manifest remain stable.

These findings are sufficient evidence for a later D5G Change Contract. They
do not select a remediation, deprecation, registry reset, or lifecycle
unification policy.

## 17. Open questions

- Should the document-wide legacy flag eventually be split into independent
  compatibility capabilities, or remain as a stable historical switch?
- Is the legacy ImageElement resource omission an intentional compatibility
  contract or a deferred bug?
- Should `refresh()` continue to reload the original template after legacy
  structured rendering, or receive a separately approved lifecycle contract?
- Which legacy styles XML differences from repeated render are safe to narrow?
- How should mixed `setElement()` and `assign(OdtElement)` precedence be
  specified without changing either public API?
- Which public legacy getters can eventually become facades while preserving
  external subclass overrides?

## 18. Final characterization status

**D5G-B CHARACTERIZATION COMPLETE.**

No production code was changed. No compatibility behavior was corrected.

# STYLE-CONTEXT-01F-D5A — Composite / Materialization Characterization Audit

## 1. Scope and status

This document records the current composite-element and structured
materialization behavior for D5A. It is an evidence document, not a Change
Contract and not an implementation proposal.

No production behavior was changed in D5A. The characterization tests cover
the currently reachable `setElement()` compositions and intentionally assert
both successful and incomplete behavior.

## 2. Source of truth

The audit was performed against develop commit
`c785569f5d9639f8d706e2eb8913c9bfd47513bb` on branch
`architecture/style-context-01fd5-transitive-requirements`.

The relevant earlier contracts remain authoritative, especially the
document-owned `StyleContext` model and the D4 requirement that graphic style
requirements are adopted by the current document rather than by global
registries. D5A does not select a future composite architecture.

## 3. Existing constraints

The following are inherited constraints, not new D5A decisions:

* requirements of an inserted structured element are intended to include its
  owned descendant subtree;
* physical image files and manifest entries are package resources, not
  `StyleContext` style state;
* `OdtTemplate` must not contain a concrete-element traversal cascade;
* elements retain responsibility for producing native ODF through
  `toDomNode()`;
* no renderer/God-object, ASSET-CONTEXT, FRAME-LAYOUT, STYLE-API-02, or D6
  work is included here.

## 4. Element inventory

| Element | Shape | Actual child storage | Native rendering | Requirement exposure |
|---|---|---|---|---|
| `OdtElement` | base/composite convention | `embeddedElements` | abstract `toDomNode()` | default graphic getters recurse through `getEmbeddedElements()`; assets and style definitions do likewise |
| `Paragraph` | composite | `embeddedElements`, plus text `parts` | renders text parts, then each embedded element; may wrap as list | own text/paragraph styles; inherited graphic recursion; inherited asset recursion |
| `RichText` | composite | private/protected `elements` | appends every element into a `DOMDocumentFragment` | text/paragraph styles are collected by its own direct loops; inherited graphic getters see the unused base child list; special image-asset code handles direct images in paragraphs only |
| `DrawTextBox` | composite | `paragraphs` | creates `draw:frame`/`draw:text-box` and directly renders each stored child | own frame requirement; no delegation for stored child requirements/assets |
| `ListElement` | composite | `items` containing `Paragraph` or nested `ListElement` | creates `text:list` and `text:list-item`, recursively rendering items | text styles and image assets recurse through items; paragraph styles only direct paragraph items; no graphic requirement recursion |
| `RichTable` | composite | `rows`, each with cell objects | creates table, rows, cells, and directly renders paragraph/RichText cell content | text styles use direct row/cell inspection; cell/table styles use their existing materialization; no graphic or asset recursion |
| `RichTableCell` | composite wrapper | single `content` value | renders contained `Paragraph`/`RichText` into a cell | exposes its own table-cell style only; does not delegate child requirements/assets |
| `ImageElement` | leaf | none | creates `draw:frame` and `draw:image` | own image graphic requirement and own image asset |
| `CircularImageElement` | specialized image leaf | none | creates native `draw:custom-shape` ellipse | own image graphic and fill-image requirements after materialization; inherited image asset |

`Paragraph`, `RichText`, `DrawTextBox`, `ListElement`, and `RichTable` all
render children directly, but they do not share one storage model. The name
`getEmbeddedElements()` therefore does not currently mean “all descendants”
for every composite class.

## 5. Child ownership and rendering facts

### Paragraph

`Paragraph::addElement()` stores the child in `embeddedElements`, and
`Paragraph::toDomNode()` renders that same collection after its text parts.
This is the one central path where the D4 graphic getters on `OdtElement`
already provide useful nested propagation. A `Paragraph -> ImageElement`
therefore exposes the image requirement and asset through the base traversal.

### RichText

`RichText::addParagraph()`, `addTable()`, `addImage()`, and `addElement()` all
store values in `$elements`. `toDomNode()` renders `$elements`, but
`getEmbeddedElements()` is not overridden. Its requirement methods are
specialized direct loops rather than one common recursive traversal. Direct
image insertion is renderable, but current image asset collection only
recognizes images embedded in a `Paragraph` held by the rich text.

### DrawTextBox

`DrawTextBox::addElement()` stores children in `$paragraphs`, and
`toDomNode()` renders exactly that array inside `draw:text-box`. The base
`embeddedElements` list remains empty. The textbox exposes its own frame style
but not requirements from its stored children.

### Lists and tables

`ListElement` and `RichTable` both have their own domain-specific storage and
native ODF rules. `ListElement` recursively renders nested lists. `RichTable`
uses `RichTableCell::getContent()` and directly invokes content rendering.
`RichTableCell` does not present its content through the base embedded-child
convention.

## 6. Characterization matrix

The following results are from
`StyleContextCompositeMaterializationCharacterizationTest`. “Content” means
the nested image/frame node is present in `content.xml`; “graphic style” means
the referenced generated style is present exactly once in `styles.xml`.

| Composition | Content node | Graphic requirement | Physical image | Manifest |
|---|---:|---:|---:|---:|
| `Paragraph -> ImageElement` | yes | yes | yes | yes |
| `Paragraph -> DrawTextBox` | yes | yes (frame) | n/a | n/a |
| `RichText -> Paragraph -> ImageElement` | yes | no | yes | yes |
| `RichText -> direct ImageElement` | yes | no | no | no |
| `RichText -> Paragraph -> DrawTextBox` | yes | no (frame) | n/a | n/a |
| `DrawTextBox -> styled Paragraph` | yes | yes (own frame) | n/a | n/a |
| `DrawTextBox -> ImageElement` | yes | yes (own frame), no (image) | no | no |
| `DrawTextBox -> RichText -> ImageElement` | yes | yes (own frame), no (image) | no | no |
| `ListElement -> Paragraph -> ImageElement` | yes | no | yes | yes |
| nested `ListElement -> ListElement -> Paragraph -> ImageElement` | yes | no | yes | yes |
| `RichTable -> RichTableCell -> Paragraph -> ImageElement` | yes | no | no | no |
| `RichTable -> RichTableCell -> RichText -> ImageElement` | yes | no | no | no |

The matrix separates node materialization from requirement/resource
propagation. A nested node can be present and valid while its style or package
resource is absent.

An additional characterization shows that a styled paragraph inside a
`DrawTextBox` emits a `text:style-name` reference, but the corresponding text
style is not collected by the textbox. This is distinct from the frame-style
result.

## 7. Requirement propagation details

The current `OdtElement::getFrameStyleRequirements()`,
`getImageStyleRequirements()`, and `getFillImageRequirements()` recurse only
through `getEmbeddedElements()`. They combine maps with `array_merge()`.

Consequently:

* the Paragraph child model is visible to the default traversal;
* RichText, list, table, cell, and textbox-specific storage is not visible to
  that traversal unless the concrete class supplies a corresponding method;
* existing concrete methods may expose only their own requirement;
* text and paragraph requirement methods are independently implemented by
  several composites and are generally direct-only or type-filtered;
* no single transitive closure exists for all requirement families.

## 8. Physical resource propagation

The current resource behavior is not equivalent to graphic style behavior.

* `ImageElement::getImageAssets()` exposes its own image path.
* The base `OdtElement::getImageAssets()` recursively visits only
  `getEmbeddedElements()`.
* `Paragraph` therefore exposes assets for its embedded images.
* `ListElement` explicitly walks its items and recursively exposes their
  assets.
* `RichText` has a specialized implementation that finds direct
  `ImageElement` children of paragraphs, but does not cover every rendered
  descendant shape.
* `DrawTextBox` uses `$paragraphs` for rendering and does not expose those
  assets through its inherited base traversal.
* `RichTable` and `RichTableCell` do not expose rendered image assets through a
  general resource traversal.

This explains why some compositions contain a `Pictures/...` reference while
the archive and manifest do not contain the corresponding file. D5A records
this; it does not implement nested resource propagation.

## 9. `toDomNode()` responsibility audit

### Element-specific ODF semantics

These responsibilities are localized in their concrete elements and should
remain semantically close to them:

* `Paragraph`: `text:p`, spans, breaks, tabs, list wrapping, and embedded
  insertion order;
* `RichText`: fragment creation and sibling paragraph/list/table placement;
* `DrawTextBox`: `draw:frame`, text-box structure, frame attributes, and
  textbox child placement;
* `ListElement`: `text:list`, list items, nesting, and list style reference;
* `RichTable`: table/row/cell ODF structure, columns, headers, and cell
  content placement;
* `RichTableCell`: table-cell attributes and content insertion;
* `ImageElement`: frame/image structure, anchor/size/wrap attributes, and
  image href;
* `CircularImageElement`: custom-shape/ellipse geometry and bitmap-fill style
  references.

### Repeated or generic mechanics

The following mechanics recur and may be candidates for later extraction or a
shared composition protocol, without deciding that design here:

* iterating stored children;
* rendering a child into the target DOM;
* handling `DOMDocumentFragment` versus a single node;
* cloning fragment children before appending them to another parent;
* collecting requirements/assets over the same logical owned subtree;
* ensuring that a requirement is adopted into the current document before
  final serialization.

`StructuredElementMaterializer` currently owns placeholder normalization and
replacement mechanics, not element-child traversal. `OdtTemplate` owns the
document boundary and current style finalization, not knowledge of every
composite’s private child storage.

## 10. Materialization side effects and state mutation

The audit found these observable behaviors:

* `ImageElement::toDomNode()` resolves alignment/wrap/position values and
  writes the resolved attributes back into `$imageOptions`.
* `CircularImageElement::toDomNode()` assigns its fill-image name and final
  circular style name/options while creating the custom shape.
* `DrawTextBox::toDomNode()` recomputes its deterministic frame style name.
* `Paragraph::toDomNode()` still contains a fallback path that can register a
  text style through the legacy `StyleMapper` when a text part has style data
  but no precomputed style name.
* `RichTable::toDomNode()` can materialize cell and column style nodes directly
  into the target DOM.
* `StructuredElementMaterializer::insert()` may call `toDomNode()` for the
  content DOM and again for a matching styles-DOM placeholder.

No D5A change attempts to make materialization pure or to remove these
side-effects.

## 11. Conflict-loss characterization

Two test-local `OdtElement` children expose the same frame requirement name
with different definitions. The base collector returns only the second
definition:

```text
child A: same-name => definition A
child B: same-name => definition B
collector result: same-name => definition B
```

This is silent loss before `StyleContext` can apply its document-local
same-name conflict rule. The test deliberately locks down the current
behavior. It is a design risk for any future transitive collector and is not
fixed in D5A.

## 12. Active versus compatibility paths

The normal structured path is `OdtTemplate::setElement()` followed by
`StructuredElementMaterializer::insert()`. The facade gathers top-level
text/paragraph requirements, copies whatever assets the root exposes, invokes
the element’s native `toDomNode()`, and adopts top-level graphic requirements.

The legacy structured path reaches `setValuesInDom()` through the existing
assign/render lifecycle. It materializes values directly and uses the explicit
legacy graphic registration bridge. D5A does not migrate that path.

Direct element rendering remains element-owned. D5A adds no renderer and does
not alter compatibility registries.

## 13. Historical inconsistencies and code smells

The current implementation contains several independent child models and
several partial collectors:

* common base storage versus private composite storage;
* direct-only type checks in RichText/ListElement/RichTable collectors;
* inherited graphic traversal that is transitive only where base storage is
  used;
* image assets and style requirements following different traversal rules;
* direct DOM style insertion in some table paths;
* `array_merge()` collision loss in the base graphic collector;
* legacy fallback registration in paragraph materialization.

These are observations, not D5A repair targets.

## 14. Facts constraining future design

Future work must account for all of the following:

1. A composite’s rendered children are not reliably discoverable through
   `getEmbeddedElements()` today.
2. Requirement propagation and physical-resource propagation need separate
   semantics, even when they traverse related content.
3. A child can render successfully while its style/resource is missing.
4. Native ODF parent/child mechanics differ between paragraphs, text boxes,
   lists, fragments, and tables.
5. Requirement collection must detect same-name conflicts before map merging
   silently discards one definition.
6. Image and circular-image requirements can become final only during native
   materialization.
7. Existing protected/public compatibility paths must remain distinguishable
   from the active document-owned path.

## 15. Open architecture questions

The next design work still needs to decide:

* whether one authoritative owned-child model can represent all current
  composites without obscuring their native ODF semantics;
* how requirement collectors can preserve duplicate definitions long enough
  to apply explicit document-local conflict rules;
* how nested physical assets can be propagated without making `StyleContext`
  an asset owner;
* how tables and cells can expose content requirements without reflection or
  class-specific facade traversal;
* how the legacy structured-value path can share requirement adoption without
  silently changing its public lifecycle;
* whether repeated DOM-fragment mechanics should be shared while keeping
  element-specific ODF semantics localized.

## 16. Neutral A/B/C architecture comparison

### A — Per-element `toDomNode()` with unified ownership

Keep native DOM construction in each element and establish a common owned-child
or composition protocol. Requirement/resource traversal would use that same
logical ownership model.

### B — Element-specific collaborators/renderers

Move DOM construction into collaborators associated with each element while
keeping ODF semantics close to the element type. This could separate
construction from state but adds collaborator lifecycle and dispatch choices.

### C — Element semantics plus shared materialization mechanics

Keep ODF-specific construction on elements while extracting repeated mechanics
such as child-node insertion, fragment handling, or requirement adoption into a
small shared mechanism.

These descriptions are alternatives for later evaluation. D5A selects none of
them.

## 17. Explicit D5A conclusion

D5A records the current model and its losses. It does not select A, B, or C;
it does not introduce `ownedChildren()`, a requirement bag, renderer services,
contexts, or new production APIs. No production behavior was changed by this
audit.

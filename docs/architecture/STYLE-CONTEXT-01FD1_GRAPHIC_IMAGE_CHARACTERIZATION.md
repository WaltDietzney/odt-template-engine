# STYLE-CONTEXT-01F-D1 — Graphic/Image Characterization

## 1. Baseline and scope

This evidence slice was run on branch
`architecture/style-context-01fd-graphic-image`, based at
`44a476f988438a0fc87da96208a8dea7245990f6`.

It characterizes the current graphic/frame-style, image-style, fill-image,
asset, and nested structured-element behavior before the 01F-D ownership
decision. No production source, template, or generated sample output was
changed.

The tests intentionally preserve undesirable compatibility behavior. They do
not define the desired document-scoped end state.

## 2. Active producer and finalization paths

The relevant current paths are:

* `DrawTextBox` registers a mapped frame style in the static
  `StyleMapper::$frameStyles` during construction, fluent mutations,
  `registerStyles()`, and `toDomNode()`.
* `ImageElement` registers a mapped image graphic style in the static image
  registry during construction/`setStyle()` and again in `toDomNode()`.
* `CircularImageElement::toDomNode()` registers both a fill-image and a
  graphic style. It is the only runtime producer found for
  `StyleMapper::registerFillImage()`; repository sample call-site search found
  no current sample that instantiates `CircularImageElement`.
* `OdtTemplate::setElement()` prepares top-level image assets, invokes the
  element's DOM materialization, and does not recursively collect assets from
  arbitrary nested children.
* `OdtTemplate::save()` calls `injectImageStyles()` and then the compatibility
  `StyleWriter::writeAllStyles()` path. `refresh()` calls the writer path but
  does not call `injectImageStyles()`.
* `StyleWriter` consumes static frame/table and other compatibility registries;
  image styles are injected by `OdtTemplate` before the writer is run.

`DrawTextBox::getStyleDefinitions()` and `toStyleDomNode()` are a second frame
style materialization path. The textbox also calls each child element's
`toDomNode()` directly.

## 3. Characterization matrix and observations

The focused test class is
`StyleContextGraphicImageCharacterizationTest`.

### Unattached frame style

Constructing a styled, unattached `DrawTextBox` before creating an unrelated
template causes its frame style to appear in that template's saved
`styles.xml`. The test identifies the style by a distinctive background color.
This is legacy process-global leakage.

### Unattached image style

Constructing a styled, unattached `ImageElement` before saving an unrelated
template causes its image graphic style to appear in the unrelated
`styles.xml`. The mapped style contains the expected wrapping property. This
is also process-global leakage.

### Interleaved image styles

Constructing image elements for two documents and then saving both makes both
image styles visible in both saved documents. Save order therefore does not
provide ownership isolation for this current registry.

### Same-name image registration

`StyleMapper::registerImageStyle()` assigns directly by name. Registering the
same name with different definitions exposes the latest definition. This is
overwrite semantics, not `StyleContext` conflict semantics.

### Repeated save

Repeated save remains parseable and retains the required image style, but the
current output can accumulate a second serialized occurrence of the image
style. Frame style materialization through the textbox path also exposes two
occurrences of the generated style name in the saved styles document. No
deduplication change is made here.

### Save versus refresh

An image style registered globally before `refresh()` is not injected into the
current styles DOM by the observed refresh path. A subsequent `save()` does
inject it. This confirms the existing `injectImageStyles()` asymmetry between
the two lifecycle operations.

### Load lifecycle

Calling `load()` resets the document's loaded/core DOM state but does not clear
the process-global image registry. A later save can therefore reuse and emit
the previously registered image style.

### Repeated image DOM materialization

For an image using `align => right`, the first `toDomNode()` mutates
`getImageOptions()` to include the resolved `style:wrap`,
`style:horizontal-pos`, and `style:horizontal-rel` values. A second call is
stable and exposes the same resolved options. This mutation is retained as
current behavior.

### Nested styled paragraph in a textbox

The nested paragraph is rendered into `content.xml` and references its text
style. However, the top-level textbox does not expose the child's text-style
requirements through its own requirement collection. With the document-aware
text writer path, the nested text style is not materialized in the saved
`styles.xml`; the nested `toDomNode()` has nevertheless created the reference
through the legacy side effect. This is a requirement-propagation asymmetry.

### Nested image in a textbox

The nested image frame and `draw:image` node are materialized in
`content.xml`, and its graphic style is available through the global image
injection path. The top-level `setElement()` asset preparation does not recurse
into the textbox child, so the image is absent from `Pictures/` and from the
manifest. This is the current asset ownership gap, not a test fixture failure.

### Legacy structured-value path

Existing structured-value tests exercise `assign()`/`render()` and the
protected `setValuesInDom()` route. That route calls `OdtElement::toDomNode()`
directly rather than using the explicit top-level requirement collection used
by `setElement()`. Consequently its success depends on the element's own
legacy DOM/style side effects. No production change was made to reconcile the
two routes.

## 4. Distinct kinds of graphic/image state

These concepts must not be conflated in the later ownership design:

* **Frame graphic styles** are `DrawTextBox` style definitions, normally
  emitted as `style:family="graphic"` declarations.
* **Image graphic styles** are `ImageElement`/circular-image style definitions
  applied to image frames or custom shapes.
* **Fill-image declarations** are `draw:fill-image` entries referencing a
  bitmap for a graphic fill. The active producer found is
  `CircularImageElement`; no generic fill-image producer was invented.
* **Physical image/package assets** are files under `Pictures/` plus manifest
  entries. They are separately exposed by `getImageAssets()` and are not
  implied merely by a style registration.

The current `StyleMapper::registerFillImage()` registry is process-global.
`OdtTemplate::injectImageStyles()` emits registered fill-image declarations,
but asset copying is a separate operation. This separation is especially
visible for nested images, where style materialization occurs but top-level
asset preparation does not.

## 5. `setElement()` versus legacy materialization

The public `setElement()` path is document-aware for the requirement families
that its top-level element exposes. It prepares top-level image assets and then
materializes the native subtree. It does not recursively infer arbitrary child
requirements or assets from a `DrawTextBox`.

The legacy structured-value path eventually invokes `toDomNode()` directly.
Elements remain responsible for describing native ODF/XML, but this means
their direct global registrations can influence later document finalization.
The two paths therefore have different observability and ownership boundaries.

## 6. Duplicate, conflict, and lifecycle behavior

Image-style registration is overwrite-by-name. Frame-style registration uses
the static frame map and is repeated by several textbox lifecycle points.
These behaviors differ from document-scoped `StyleContext` paragraph/text
requirements, where equivalent definitions are idempotent and conflicting
pending definitions fail.

The following current behavior is surprising or undesirable but deliberately
unchanged in D1:

* unattached graphic/image elements affect unrelated saved documents;
* interleaved documents share global image styles;
* `refresh()` and `save()` have different image-style injection behavior;
* `load()` does not clear global graphic/image registrations;
* nested textbox requirements/assets are not recursively surfaced;
* repeated save can duplicate serialized graphic-style declarations;
* image DOM materialization mutates image options.

These are characterization results, not recommendations to preserve them in
the final architecture.

## 7. Implications for the later 01F-D contract

The later contract must decide independently how to own:

1. frame styles and image graphic styles;
2. fill-image declarations;
3. physical image assets and manifest entries;
4. nested requirement and asset collection;
5. save/refresh/load finalization consistency;
6. same-name duplicate and conflict behavior;
7. repeated-save deduplication;
8. whether resolved image options remain mutable element state.

It should not assume that all graphic-related state belongs in one generic
registry. Frame style definitions, image graphic styles, fill-image
declarations, and package assets have different producers and lifecycle
effects.

## 8. Scope exclusions

No `StyleContext`, `StyleMapper`, `StyleWriter`, `OdtTemplate`, element, asset,
anchor, wrap, layout, or public API behavior was changed. This slice does not
implement ASSET-CONTEXT, FRAME-LAYOUT-01, or any 01F-D2 decision.

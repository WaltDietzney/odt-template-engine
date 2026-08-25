# ARCH-05A Structured Element & ODF Object Model Audit

**Status:** Audit / research complete
**Milestone:** ARCH-05 — Structured elements / structured insertion
**Repository basis:** `develop`
**Audited HEAD:** `95681ef62b2b9a5d48a825a4406e20000c3f51b5`
**Production changes:** none

## 1. Scope

This audit examines the existing structured-element classes, structured
placeholder insertion, named ODF objects, and the dependencies that make
materialization more than a DOM replacement operation. It does not define a
public API and does not implement extraction, named-object replacement, or
cloning.

The existing exploratory notes remain the source of open hypotheses:
[ARCH-05 structured-element design notes](ARCH-05_STRUCTURED_ELEMENTS_DESIGN_NOTES.md).

## 2. OdtElement is a constructed-content contract

`OdtElement` requires implementations to provide:

```php
toDomNode(DOMDocument $dom): DOMNode
```

Optional hooks expose styles, image assets, placeholder information, and
style definitions. The class also stores embedded elements.

The verified semantic is:

> `OdtElement` is a programmatic ODF-subtree serializer, not a handle to an
> existing object in a LibreOffice-authored template.

That distinction must remain explicit. A constructed `RichTable` and an
existing named table may have related XML, but they have different ownership
and lifecycle semantics.

## 3. Existing constructed element model

### RichText

`RichText` is a heterogeneous engine-side block container. It serializes its
children into a `DOMDocumentFragment`, so it is not itself a native ODF
element. It may contain paragraphs, tables, images, lists, and other
`OdtElement` instances.

### Paragraph

`Paragraph` serializes to `text:p`. It owns inline parts and can emit spans,
line breaks, tabs, links, and embedded elements. List-configured paragraphs
can be represented through a surrounding `text:list` structure. It remains a
constructed paragraph, not an established named-template target.

### ListElement

`ListElement` serializes to `text:list` with `text:list-item` children and can
contain paragraphs or nested list elements. No reliable LibreOffice-authored
user-facing naming mechanism for lists was established by this audit.

### RichTable and RichTableCell

`RichTable` serializes real ODF tables, columns, rows, cells, spans and
optional header rows. It exposes `setTableName()`, which produces
`table:name`. Table-cell and column styles may also be written into automatic
styles during serialization. A table therefore has native type-specific
identity, style dependencies, and layout semantics.

### ImageElement and DrawTextBox

`ImageElement` constructs a `draw:frame` containing `draw:image`; the frame
owns anchoring, dimensions, positioning and graphic-style attributes, while
the image references a package resource.

`DrawTextBox` constructs a named `draw:frame` containing `draw:text-box` and
its flow content. The frame owns placement and layout; the text box owns the
editable content.

## 4. Current structured insertion lifecycle

`AbstractOdtTemplate::setElement()` currently performs all of the following:

1. collects text, paragraph and table-cell style requirements;
2. registers style definitions and installs style nodes;
3. copies or replaces image assets through the existing image path;
4. normalizes broken placeholders;
5. materializes the element into `content.xml`;
6. replaces matching occurrences in `styles.xml` as well.

`setValuesInDom()` also distinguishes `OdtElement` values from scalar values
and routes structured values through `replacePlaceholderWithDom()`.

Consequently, structured materialization is a document-context operation
with XML, style, and package-resource side effects. It is not equivalent to
`parent->replaceChild()`.

## 5. Placeholder insertion semantics

`replacePlaceholderWithDom()` has two observable paths:

* inline-compatible replacements such as spans, spaces and line breaks are
  inserted around text-node parts;
* block replacements climb to the containing `text:p` and replace that
  paragraph, with special handling for paragraphs inside `draw:text-box`.

Thus a block placeholder embedded in prose is currently a paragraph-level
insertion site, not a general inline insertion point. The useful vocabulary
for future work is therefore:

* **inline placeholder insertion**;
* **block placeholder insertion**.

The dedicated `renderTextBoxes()` pass and text-box handling in structured
replacement make text boxes a compatibility seam rather than a simple
special case that can be removed during extraction.

## 6. Native ODF identity findings

The audit does not support one universal `name` attribute or XPath.

| ODF object family | Observed identity mechanism | Finding |
|---|---|---|
| drawing/frame/image container | `draw:name` | The named object is the frame, not the nested image resource. |
| text box | usually its owning `draw:frame` | The frame is the layout/identity container; the text box is its payload. |
| table | `table:name` | Tables have a separate native identity mechanism. |
| paragraph, span, ordinary list, RichText | none established | Do not assume a comparable named-object workflow. |

The existing `replaceImageByName()` is concrete evidence for the frame model:
it searches `//draw:frame[@draw:name='...']` and changes the nested
`draw:image`. It also changes `svg:width` and `svg:height`, so its current
meaning is image replacement plus optional frame-dimension overwrite, not
pure content replacement. Existing options are compatibility-sensitive.

## 7. Two image semantics already coexist

`setImage()` constructs a new frame at a textual placeholder and replaces the
containing paragraph. `replaceImageByName()` locates an existing named frame,
copies a new package resource, and updates its nested image reference.

These are different operations and should remain conceptually distinct even
if future infrastructure shares lower-level resource synchronization.

## 8. Content/container distinction

The strongest common ODF pattern found is:

```text
template object
├── identity
├── layout / anchoring
├── style
└── dynamic content or package resource
```

For a named image frame, a useful future operation may preserve the frame and
replace only the image payload. For a named text-box frame, it may preserve
the frame and replace the text-box children. Whole-object replacement must
remain a separate, more destructive operation.

This is a design direction, not an implemented API.

## 9. Five operations that must not be conflated

Future ARCH-05 work should distinguish:

1. textual placeholder replacement;
2. structured placeholder insertion of constructed `OdtElement` content;
3. named-object content replacement while retaining the container;
4. whole-object replacement;
5. cloning of a template-authored object or block.

Cloning is not merely current foreach behavior. It introduces identity,
resource, style, anchor, nested-object and duplicate-name questions even when
`cloneNode(true)` is the underlying DOM primitive.

## 10. Styles and package resources

Structured elements are coupled to `StyleMapper`, `StyleWriter`,
`styles.xml`, and `office:automatic-styles`. `RichTable` can inject cell
style nodes while serializing; image and frame elements require graphic
styles; paragraphs and text require text/paragraph styles.

Images add package dependencies:

```text
XML xlink:href
    -> Pictures/<file>
    -> META-INF/manifest.xml entry
```

Any future insertion, replacement, or cloning operation must therefore
classify whether it is XML-only, style-backed, resource-backed, or both. ARCH-05
must not solve this by introducing additional global mutable style state;
`STYLE-CONTEXT-01` remains the relevant architectural dependency.

## 11. Compatibility-sensitive surface

The following existing behavior is publicly documented or exercised by the
repository and must be characterized before change:

* `OdtElement::toDomNode()` and its style/resource hooks;
* `setElement()` and `setValues()` with structured values;
* `setImage()` and `replaceImageByName()`;
* `RichText`, `Paragraph`, `RichTable`, `RichTableCell`, `ListElement`,
  `ImageElement` and `DrawTextBox` serialization;
* structured replacement in both `content.xml` and `styles.xml`;
* style registration and manifest/resource synchronization;
* text-box processing and render/save lifecycle;
* protected facade seams inherited from `OdtTemplate` and
  `AbstractOdtTemplate`.

The existing integration and API-contract tests cover construction and
insertion of rich text, lists, tables, images, HTML-derived content and text
boxes. They do not establish a universal named-object API.

## 12. Architectural model supported by the evidence

The evidence supports two distinct families:

```text
constructed structured content       existing template objects
OdtElement / RichText                typed targets with native identity
serialization                       resolution and preservation rules
```

A future target model should be type-aware and capability-oriented rather
than based on one universal `replaceElementByName()` operation. Possible
capabilities include content replacement, whole-object replacement, cloning,
flow-content containment, style dependency and package-resource dependency.

The default preservation principle should be:

> Preserve the template-owned container unless whole-object replacement is
> explicitly requested.

This is especially compelling for image and text-box frames, where LibreOffice
should remain responsible for visual layout and the engine should supply
dynamic content.

## 13. CV benchmark

The professional-CV use case validates the direction without making the core
library CV-specific:

* a named image frame is a strong content-replacement candidate;
* a named text-box frame is a strong content-replacement candidate for
  `RichText`/paragraph content;
* a constructed `RichTable` remains a valid structured-placeholder strategy;
* named-table manipulation and repeated template-object cloning require
  separate identity, style, and resource rules;
* decorative drawing objects and page regions require additional ODF
  characterization.

## 14. Recommended next step: ARCH-05B

Proceed to **ARCH-05B — Element Identity & Replacement Semantics** without
production implementation or commitment to public method names.

ARCH-05B should define, conceptually:

* constructed structured content;
* typed template targets;
* identity by ODF family;
* container versus payload;
* content replacement versus whole-object replacement;
* cloning and identity/resource rules;
* style and package-resource dependencies;
* document-region lookup;
* ownership of template-authored versus application-supplied state.

Only after those semantics are precise should ARCH-05C define a change
contract and implementation slices.

## 15. Explicit non-goals

This audit does not:

* add or rename public APIs;
* implement named target resolution;
* implement whole-object replacement or cloning;
* redesign `OdtElement` or `AbstractOdtTemplate`;
* change styles, package ownership, image behavior, or structured insertion;
* implement `STYLE-CONTEXT-01`, document defaults, ARCH-05, or ARCH-05B.

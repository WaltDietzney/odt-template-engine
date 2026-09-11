# FRAME-LAYOUT-01A0.3 — Cross-Part Image Compatibility Investigation

Status: RESEARCH / CHARACTERIZATION REQUIRED

Milestone: `FRAME-LAYOUT-01`

Related backlog item:

- `GRAPHIC-PART-COMPAT-01`

Prior evidence:

- `PAGE_FLOW_01D_PAGE_OWNED_CONTENT_EVIDENCE.md`

## 1. Why this belongs in the FRAME-LAYOUT evidence set

PAGE-FLOW-01 established a bounded but important discrepancy:

```text
ImageElement via setElement() / content.xml body      -> Writer-visible
ImageElement via setElement() / styles.xml header     -> not Writer-visible
setImage() / styles.xml header                         -> Writer-visible
```

The resource package state was valid in the failing structured-header case:

- `draw:image` existed;
- the bitmap existed in `Pictures/`;
- the manifest entry existed;
- save/reopen retained the structured node.

Therefore the failure is not simply "image file missing".

Because FRAME-LAYOUT-01 is now separating drawing-object structure, frame geometry, graphic style properties, and document-part behavior, this discrepancy must be investigated alongside the native frame model.

It must still remain bounded: if the root cause is document-part materialization rather than frame semantics, it stays a separate compatibility issue.

## 2. Known falsified hypothesis

PAGE-FLOW-01 already tested whether the generated graphic parent style alone caused the problem.

Changing:

```xml
style:parent-style-name="Standard"
```

to:

```xml
style:parent-style-name="Graphics"
```

did not make the structured header image visible.

Do not repeat that hypothesis as if it were unresolved.

## 3. Critical architectural difference between the two paths

### 3.1 setImage()

`OdtTemplate::setImage()` is a direct template utility path.

It creates a frame directly in the target DOM and does not depend on a generated `draw:style-name` for basic geometry.

The generated object contains approximately:

```text
text:p
└── draw:frame
    ├── draw:name
    ├── text:anchor-type
    ├── svg:width
    ├── svg:height
    ├── draw:z-index
    └── draw:image
        └── xlink:href
```

For the PAGE-FLOW header case it was used as:

```php
$template->setImage('header_logo', $imagePath, [
    'width' => '1cm',
    'anchor' => 'as-char',
]);
```

Writer displayed this image.

### 3.2 setElement(..., ImageElement)

`ImageElement` is a structured element path.

It creates:

```text
draw:frame
├── draw:style-name
├── text:anchor-type
├── svg:width
├── svg:height
└── draw:image
    └── xlink:href
```

and participates in the legacy image-style compatibility pipeline.

Current `ImageElement::toDomNode()` also writes layout properties such as:

```text
style:wrap
style:horizontal-pos
style:horizontal-rel
style:vertical-pos
style:vertical-rel
```

directly on the frame when present.

A0.2 has now shown that this is not the native ODF ownership model for those properties.

The PAGE-FLOW header test used only width + `as-char`, so invalid position properties are not sufficient by themselves to explain the failure. The style/materialization context remains relevant.

## 4. Investigation questions

FRAME-LAYOUT-01 must compare the successful and failing paths for the same image and same header placeholder.

At minimum inspect:

1. exact `draw:frame` subtree shape;
2. paragraph/insertion context;
3. `draw:style-name` presence/absence;
4. style definition location and scope;
5. whether the style definition is reachable from page-owned content in `styles.xml`;
6. whether Writer resolves automatic/common graphic styles identically inside master-page-owned header content;
7. anchor handling for `as-char` in header content;
8. frame parent node after structured replacement;
9. namespace correctness;
10. image xlink attributes;
11. save/reopen stability;
12. body/header comparison using the same `ImageElement`;
13. whether removing only `draw:style-name` changes Writer visibility;
14. whether replacing the generated graphic style with a Writer-authored style changes visibility;
15. whether moving the same generated frame subtree from header to body changes visibility.

## 5. Important new hypothesis from A0.2

A0.2 established that Writer/ODF separates:

```text
draw:frame object geometry
from
style:graphic-properties layout/appearance
```

The structured ImageElement path still carries historical mixed image-style state.

Therefore one focused hypothesis is now justified:

> The header discrepancy may be caused by the structured ImageElement's generated graphic-style/reference path or insertion context, not by image resources and not by the fundamental legality of a frame/image inside header content.

This remains a hypothesis until Writer-visible controlled experiments confirm it.

## 6. Required controlled cases

Use one Writer-authored header fixture and produce at least these variants:

```text
H1  setImage(), as-char                       known visible baseline
H2  ImageElement, as-char                     known invisible baseline
H3  ImageElement frame with style-name removed
H4  ImageElement frame referencing Writer-authored graphic style
H5  setImage structural frame + generated ImageElement style-name
H6  identical ImageElement subtree in body
H7  identical ImageElement subtree in header
```

Each case should record:

- resulting XML;
- style definition location;
- package/resource state;
- Writer-visible yes/no.

This matrix should identify whether the visibility boundary follows:

```text
document part
insertion context
style reference
style definition scope/location
frame subtree
anchor
or some combination
```

## 7. Scope rule

Do not fix `GRAPHIC-PART-COMPAT-01` opportunistically during A0 research.

If the root cause is a shared frame semantic issue, FRAME-LAYOUT-01 may absorb the necessary bounded correction into its Change Contract.

If the root cause is structured cross-part materialization, retain a separate compatibility slice coordinated with FRAME-LAYOUT-01.

## 8. Expected architectural value

This investigation is valuable beyond headers.

A shared drawing model must eventually answer whether a generated drawing object can be materialized consistently in:

```text
content.xml body
styles.xml master-page header/footer
nested structured containers
future named-template-object targets
```

The engine should not have one frame geometry model for body content and another accidental one for page-owned content.

The PAGE-FLOW discrepancy is therefore an excellent stress test for the durability of the FRAME-LAYOUT design.


## 9. First H1-H7 manual run — provisional result

The first generated matrix produced this LibreOffice visibility result:

```text
H1  setImage(), as-char                       visible
H2  ImageElement, as-char                     not visible
H3  ImageElement without draw:style-name      not visible
H4  ImageElement with Graphics style          not visible
H5  setImage structure + ImageElement style   visible
H6  ImageElement in body                      not visible
H7  ImageElement in header                    not visible
```

However, every generated research document, including the synthetic baseline, opened with a LibreOffice repair/error prompt.

Therefore this run is **diagnostically useful but not final interoperability evidence**. The matrix must be repeated on a clean Writer-authored base document before a production change is authorized.

### 9.1 Strong structural signal

Despite the invalid synthetic baseline, the visibility split is highly informative:

```text
visible:
    H1
    H5

not visible:
    H2
    H3
    H4
    H6
    H7
```

H1 and H5 share one structural property that the other cases do not:

```text
text:p
└── draw:frame
    └── draw:image
```

The direct `ImageElement` cases are materialized by `StructuredElementMaterializer` as a bare replacement for the placeholder paragraph:

```text
draw:frame
└── draw:image
```

For `text:anchor-type="as-char"`, this is a particularly strong suspect because an as-character frame is inline content and naturally belongs inside a text paragraph.

### 9.2 Style-name hypotheses weakened

H3 remained invisible after removing `draw:style-name`.

H4 remained invisible when referencing the Writer-authored `Graphics` style.

H5 remained visible while attaching the generated ImageElement style to the successful `setImage()` structural form.

Therefore the first matrix substantially weakens these hypotheses:

- generated `draw:style-name` alone causes invisibility;
- the `Graphics` parent/reference alone fixes visibility;
- the generated ImageElement style definition is intrinsically invalid for Writer.

The leading hypothesis becomes **insertion/container structure**, not graphic style identity.

### 9.3 Header-only hypothesis weakened

H6 was also invisible in body content.

On this synthetic fixture, the failure therefore follows the direct `setElement(..., ImageElement)` insertion shape rather than the header document part.

This conflicts with earlier PAGE-FLOW evidence that an existing body sample was Writer-visible. That contradiction must be resolved with a clean Writer-authored fixture and a freshly generated body sample before revising the historical conclusion.

### 9.4 Current leading hypothesis

`StructuredElementMaterializer::replacePlaceholder()` currently considers only these generated node types inline-compatible:

```text
text:span
text:s
text:line-break
```

A structured `ImageElement` returns `draw:frame`, so the materializer replaces the containing `text:p` entirely.

By contrast, `setImage()` explicitly creates a replacement paragraph and inserts the frame inside it.

For `as-char` images, the direct structured path may therefore destroy the required inline paragraph context.

This is a characterization hypothesis, not yet an approved fix.

## 10. Required clean rerun

Because the first synthetic baseline itself requires LibreOffice repair, do not use it for final Writer conclusions.

Repeat H1-H7 using a **Writer-authored ODT base** that already contains:

```text
header: {{header_logo}}
body:   {{body_logo}}
```

and otherwise retains Writer-generated package/XML structure.

The clean rerun should preserve the same mutation matrix while changing only the insertion method/style-reference variable under investigation.

If the same H1/H5 versus H2/H3/H4/H6/H7 split reproduces without a Writer repair prompt, the paragraph/insertion-context hypothesis becomes strong enough for a dedicated Change-Contract candidate.

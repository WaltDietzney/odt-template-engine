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


## 11. Confirmed A0 finding — frame container semantics are anchor-sensitive

The clean H1-H7 rerun used a Writer-authored ODT base document and no longer triggered a LibreOffice repair/error prompt. The original visibility split reproduced unchanged.

The decisive cases were inspected directly:

```text
H1  setImage(), header, as-char
    visible
    style:header
    └── text:p
        └── draw:frame
            └── draw:image

H2  ImageElement, header, as-char
    not visible
    style:header
    └── draw:frame
        └── draw:image

H5  setImage structure + ImageElement style, header
    visible
    style:header
    └── text:p
        └── draw:frame
            └── draw:image

H6  ImageElement, body, as-char
    not visible
    office:text
    └── draw:frame
        └── draw:image
```

This clean reproduction substantially closes the earlier uncertainty caused by the synthetic fixture.

### 11.1 ODF structure explains the header result

ODF permits image content through `draw:image` inside `draw:frame`. Header content is text/document content and a frame used as an inline character belongs in the paragraph/text flow.

The working header form is therefore:

```xml
<style:header>
    <text:p>
        <draw:frame text:anchor-type="as-char">
            <draw:image .../>
        </draw:frame>
    </text:p>
</style:header>
```

The failing structured-element path instead produces:

```xml
<style:header>
    <draw:frame text:anchor-type="as-char">
        <draw:image .../>
    </draw:frame>
</style:header>
```

The paragraph emitted by the legacy `setImage()` path is therefore not merely an arbitrary historical workaround. For an `as-char` frame it preserves the required text-flow/container semantics.

### 11.2 Style hypotheses are now strongly rejected

H3, H4 and H5 isolate graphic style identity from container structure.

- removing `draw:style-name` does not make the direct ImageElement visible;
- referencing Writer's `Graphics` style does not make it visible;
- using the ImageElement-generated style on the paragraph-wrapped `setImage()` structure remains visible.

Therefore graphic style identity is not the primary cause of this defect.

### 11.3 The defect is not header-specific

H6 reproduces the same invisibility in `content.xml`.

The common failing property is not the document part. It is the combination:

```text
ImageElement
+ text:anchor-type="as-char"
+ StructuredElementMaterializer block replacement
+ loss of surrounding text:p
```

This supersedes the provisional hypothesis that the incompatibility was primarily caused by cross-part/header style materialization.

### 11.4 Concrete architecture cause

`StructuredElementMaterializer::replacePlaceholder()` currently recognizes only a small fixed set of generated node types as inline-compatible:

```text
text:span
text:s
text:line-break
```

`ImageElement` materializes to `draw:frame`. The materializer therefore follows its block replacement path and replaces the containing `text:p`.

That decision is too coarse for draw frames because the correct container behavior depends on frame anchoring semantics.

A `draw:frame` is not intrinsically "block" or "inline" for replacement purposes.

For at least:

```text
text:anchor-type="as-char"
```

the frame must remain in the paragraph/text flow.

### 11.5 Architectural consequence for FRAME-LAYOUT-01

FRAME-LAYOUT must not model frame insertion semantics solely from the generated XML element name.

The insertion/materialization boundary must be able to distinguish at least:

```text
as-character frame
    -> inline/text-flow insertion semantics

floating / paragraph / character / page anchored frame
    -> separate positioning/container semantics to be characterized
```

No API or production implementation for the second category is decided by this finding.

### 11.6 Master-page drawing is a separate semantic path

ODF also supports drawing objects associated with master-page/page layout structures. Such page-level/master-page drawing semantics must not be conflated with an image placed in the text flow of a header.

FRAME-LAYOUT must therefore keep separate concepts for:

1. a frame contained in header/footer text flow;
2. a floating frame anchored relative to text/paragraph/page;
3. a drawing object belonging to page/master-page layout.

This distinction is especially relevant for future Draw elements.

### 11.7 Status

**A0 evidence: confirmed.**

Confirmed facts:

- images can be used in Writer header content;
- the clean Writer-authored fixture reproduces the H1-H7 split;
- paragraph-wrapped `as-char` frames are Writer-visible in the tested header path;
- direct structured `as-char` frames are not Writer-visible in either tested header or body path;
- the generated ImageElement graphic style is not the primary cause;
- the current StructuredElementMaterializer removes the paragraph because it classifies `draw:frame` by node name rather than anchor semantics.

This finding is characterization evidence. It authorizes a dedicated semantic design/change-contract discussion, **not an immediate production fix**.

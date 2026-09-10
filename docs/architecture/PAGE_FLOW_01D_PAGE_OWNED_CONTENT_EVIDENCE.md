# PAGE-FLOW-01D — Page-Owned Header/Footer Content Evidence

## Status

**Empirical research complete enough for PAGE-FLOW-01 architecture synthesis.**

This document records the PAGE-FLOW-01D evidence for header/footer ownership, template processing in page-owned content, native field preservation, structured insertion, image handling, and Writer rendering.

The findings are characterization evidence. They do not by themselves define a new public API.

The governing PAGE-FLOW principle remains:

> **The engine describes or preserves native ODF flow semantics; LibreOffice/Writer computes actual pagination.**

## 1. Research question

PAGE-FLOW-01D asks whether master-page-owned header/footer content behaves as a real structured ODF content domain for the engine, rather than as opaque style metadata.

The practical questions were:

1. where Writer stores header/footer content;
2. which native structures can appear there;
3. whether scalar template processing reaches that content;
4. whether native Writer fields survive processing;
5. whether structured `OdtElement` insertion reaches page-owned content;
6. whether image resources can be used from page-owned content;
7. whether First Page → Standard master-page behavior survives engine processing and renders correctly in Writer;
8. whether any remaining defect is page-flow-specific or belongs to another subsystem.

## 2. Normative and Writer-authored ownership model

ODF and Writer evidence establish two distinct ownership layers.

### 2.1 Header/footer content is master-page-owned

Writer serializes header/footer content directly beneath `style:master-page`, for example:

```xml
<style:master-page
    style:name="Standard"
    style:page-layout-name="Mpm1">
    <style:header>
        <text:p text:style-name="Header">...</text:p>
    </style:header>
    <style:footer>
        <text:p text:style-name="Footer">...</text:p>
    </style:footer>
</style:master-page>
```

The page/master style therefore owns page-specific header/footer content.

### 2.2 Header/footer geometry is page-layout-owned

Header/footer geometry is represented through the referenced `style:page-layout`, including `style:header-style`, `style:footer-style`, and `style:header-footer-properties`.

The resulting model is:

```text
Master Page / Page Style
├── identity
├── successor relationship
├── header/footer content
└── page-layout reference
        ↓
Page Layout
└── page/header/footer geometry
```

This distinction is important because page-owned content processing and page-layout mutation are related but separate responsibilities.

## 3. Writer-authored research fixture

The Writer fixture `page-flow-01d-page-owned-content.odt` used two page styles/master pages:

```text
First Page
    ↓ style:next-style-name
Standard
```

Both master pages referenced the same page layout `Mpm1`. This deliberately separated page identity/content from page geometry.

The fixture contained the following cases.

### D1 — scalar header/footer content

First Page:

```text
FIRST HEADER — {{person_name}}
FIRST FOOTER — {{document_title}}
```

Standard:

```text
STANDARD HEADER — {{person_name}}
Page [native page-number field] — {{document_title}}
```

### D2 — native structured content in the header

The Standard header contained a Writer-authored two-column table with scalar expressions:

```text
Name: {{person_name}} | Role: {{role}}
```

The purpose was not to research table geometry. It was to prove that page-owned content can contain ordinary native structured ODF content and that scalar processing can reach nested text inside it.

### D3 — generic structured placeholder

The Standard header contained:

```text
{{header_block}}
```

This was later replaced through the modern `setElement()` path with a `Paragraph`.

### D4 — image placeholder

The Standard header contained:

```text
{{header_logo}}
```

Image behavior was characterized through both the established public `setImage()` path and the generic `setElement(..., ImageElement)` path.

## 4. Engine characterization test

The permanent characterization test is:

```text
tests/Integration/PageFlow01DPageOwnedContentCharacterizationTest.php
```

The final focused run was:

```text
OK (2 tests, 60 assertions)
```

PHP lint was clean and `git diff --check` was clean.

The test characterizes two groups of behavior.

## 5. Scalar processing reaches master-page-owned content

The engine successfully replaced assigned scalar values in `styles.xml` page-owned content:

```text
{{person_name}}     → Walter Beispiel
{{role}}            → Projektleiter
{{document_title}}  → Lebenslauf
```

This worked in:

- First Page header;
- First Page footer;
- Standard header;
- nested text inside the Writer-authored Standard-header table;
- Standard footer.

The test deliberately leaves unrelated structured placeholders such as `{{header_block}}` and `{{header_logo}}` unresolved in the scalar-only case. The important distinction is:

> Scalar rendering resolves scalar expressions assigned to that path; it does not imply that all syntactically similar structured placeholders must disappear.

This corrected an initially over-broad test assertion that required every `{{...}}` token in header/footer content to disappear after scalar rendering.

## 6. Native Writer fields survive scalar replacement

The Standard footer contained a native page-number field:

```xml
<text:page-number text:select-page="current">...</text:page-number>
```

The characterization test confirms that scalar replacement preserves the `text:page-number` element.

Manual Writer verification then showed the field rendering as the actual current page number, for example:

```text
Page 2 — Lebenslauf
```

This is important evidence that template processing does not flatten the surrounding page-owned content into plain text.

## 7. Generic structured insertion reaches page-owned content

The header placeholder `{{header_block}}` was replaced through the modern structured path:

```php
$template->setElement(
    'header_block',
    (new Paragraph())->addText('STRUCTURED HEADER BLOCK')
);
```

The resulting page-owned content contained the structured paragraph, and manual LibreOffice verification showed the text correctly in the Standard header.

This proves that `setElement()` is not limited to body content in `content.xml`; the current materialization path can also reach a matching placeholder in master-page-owned content in `styles.xml`.

## 8. Image resources in page-owned content

Two image paths were examined and must be kept distinct.

### 8.1 Established `setImage()` path

The public image API was used against `{{header_logo}}`:

```php
$template->setImage(
    'header_logo',
    $imagePath,
    [
        'width' => '1cm',
        'anchor' => 'as-char',
    ]
);
```

Manual LibreOffice verification showed the image correctly in the Standard header.

This establishes the PAGE-FLOW-01D result that page-owned header content can successfully reference and render package image resources through the established image API.

### 8.2 Generic `setElement(..., ImageElement)` path

The same logical placeholder was also replaced with:

```php
$template->setElement(
    'header_logo',
    new ImageElement($imagePath, [
        'width' => '1cm',
        'anchor' => 'as-char',
    ])
);
```

Automated characterization confirmed:

- a `draw:image` reference existed in the Standard header;
- the image resource existed under `Pictures/`;
- the manifest contained the image resource;
- save/reopen retained the structured image node.

However, manual LibreOffice verification did **not** display the image in the header for this path.

This is a real interoperability/rendering difference, but current evidence does not justify treating it as the central PAGE-FLOW-01D result.

## 9. Why the `ImageElement` discrepancy is not currently a PAGE-FLOW blocker

Further comparison narrowed the discrepancy.

### 9.1 Wrong graphic parent-style hypothesis was falsified

The generated `ImageElement` graphic style used:

```xml
style:family="graphic"
style:parent-style-name="Standard"
```

A controlled manual experiment changed only the parent reference to:

```xml
style:parent-style-name="Graphics"
```

LibreOffice still did not display the image.

Therefore the parent-style reference alone is not the cause.

### 9.2 Generic `ImageElement` is not globally broken

The existing Sample 06 output was manually rechecked. `ImageElement` renders successfully in normal body content.

That sample also shows that a directly materialized `draw:frame` and the same broadly similar generated graphic-style family are sufficient for Writer rendering in `content.xml`.

Therefore the current evidence is:

```text
ImageElement in normal body content      ✓ Writer-visible
ImageElement in page-owned header        ✗ not Writer-visible
setImage() in page-owned header           ✓ Writer-visible
```

The remaining discrepancy is therefore narrower than a general image implementation defect and narrower than a simple parent-style-name defect.

### 9.3 Scope decision for PAGE-FLOW-01D

PAGE-FLOW-01D records this as a bounded follow-up finding:

> The generic `ImageElement` path has a Writer-rendering discrepancy when directly materialized into master-page-owned header content, although its resource/package structure survives correctly. The established `setImage()` path renders successfully in the same page-owned content.

The discrepancy should be investigated in the appropriate graphic/structured-materialization context rather than expanding PAGE-FLOW-01D into a broad image implementation refactor.

No production fix is made as part of this research step.

## 10. Manual LibreOffice regression

The processed research document was opened in LibreOffice Writer.

The following behavior was visually confirmed:

### Page 1

- `FIRST HEADER — Walter Beispiel`;
- `FIRST FOOTER — Lebenslauf`;
- normal body flow.

### Page 2 and following Standard pages

- `STANDARD HEADER — Walter Beispiel`;
- Writer-authored two-column header table retained;
- `Name: Walter Beispiel` rendered;
- `Role: Projektleiter` rendered;
- `STRUCTURED HEADER BLOCK` rendered;
- established `setImage()` image path rendered visibly;
- Standard footer rendered as `Page 2 — Lebenslauf` on page 2.

This confirms the page transition and page-owned content behavior in the actual Writer layout engine, not only at XML level.

## 11. Architecture conclusions

PAGE-FLOW-01D establishes the following.

1. Header/footer **content** belongs to `style:master-page`.
2. Header/footer **geometry** belongs to the referenced page layout.
3. Page-owned header/footer content is structured ODF content, not opaque metadata.
4. Current scalar template processing already spans `styles.xml` and reaches master-page-owned content.
5. Scalar processing can reach nested native content such as table-cell paragraphs in a header.
6. Native Writer fields can coexist with template expressions and survive scalar replacement.
7. The modern generic `setElement()` path can materialize ordinary structured content into page-owned content.
8. The established `setImage()` path can insert a Writer-visible image into page-owned header content.
9. Package resources referenced from page-owned content remain ordinary ODT resources and survive save/reopen.
10. First Page → Standard master-page succession remains Writer-owned and survives engine processing.
11. The engine does not need a separate header/footer template language or a separate scalar-processing subsystem for 1.0.
12. Page-owned target/addressing APIs are a separate question from whether existing processing can already reach page-owned content.
13. The `ImageElement` header discrepancy is real but bounded and should not silently redefine PAGE-FLOW scope.

The concise architectural statement is:

> **Master-page-owned header/footer content is a normal structured ODF content domain for the engine. Existing template processing can operate there while preserving native master-page and Writer-field semantics. Writer remains responsible for page selection and rendering.**

## 12. 1.0 scope consequence

PAGE-FLOW-01 does not need to introduce a broad API such as:

```text
masterPage(...)->header()->...
setFirstPageHeader(...)
setLeftPageFooter(...)
```

merely to make page-owned content processable.

Such APIs may later be justified by explicit addressability/authoring requirements, but PAGE-FLOW-01D evidence does not require them for the native-first 1.0 flow model.

Similarly, Writer supports multiple first/left/right header/footer variants. PAGE-FLOW-01 must understand their ownership well enough not to design the wrong model, but exhaustive convenience APIs for all variants are outside this milestone.

## 13. Remaining bounded follow-up

One follow-up is intentionally retained outside the PAGE-FLOW-01 critical path:

> Characterize why `setElement(..., ImageElement)` renders in body content but not when directly materialized into master-page-owned header content, while `setImage()` works in the same header.

This should be handled as a focused graphic/structured-materialization compatibility investigation unless later evidence proves that page-owned target semantics are the actual cause.

The finding must not be lost, but it does not block PAGE-FLOW-01 architecture synthesis.

## 14. PAGE-FLOW-01D completion statement

PAGE-FLOW-01D is empirically complete enough for the PAGE-FLOW-01 architecture decision.

The core questions about ownership, processing boundaries, native-field preservation, structured insertion, resource handling, and Writer page-style rendering have sufficient evidence. The remaining `ImageElement` discrepancy is explicitly documented and bounded rather than silently repaired inside the page-flow milestone.

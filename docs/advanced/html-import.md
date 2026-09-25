# HTML Import

`HtmlImporter` converts an HTML fragment into the engine's native `RichText`, `Paragraph`, `ListElement`, `RichTable`, and `ImageElement` structures.

Use it when application content already exists as controlled HTML and should become editable ODT content. It is not a browser rendering engine and should not be treated as a general HTML/CSS-to-ODT converter.

## Basic import

```php
use OdtTemplateEngine\Import\HtmlImporter;

$html = <<<'HTML'
<h2>Project summary</h2>
<p>This paragraph contains <strong>bold</strong> and <em>italic</em> text.</p>
<ul>
    <li>Native paragraph content</li>
    <li>Native ODT list structure</li>
</ul>
HTML;

$content = HtmlImporter::fromHtml($html);
$template->setElement('content', $content);
```

The importer parses the fragment with PHP's DOM extension and builds engine elements rather than embedding the original HTML into the ODT package.

## Characterized structure

The current importer handles common document-oriented HTML, including:

- paragraphs and block containers such as `p`, `div`, `section`, `article`, `header`, `footer`, and `main`;
- headings `h1` through `h6`;
- line breaks;
- inline emphasis such as `strong`, `b`, `em`, `i`, and `u`;
- additional text semantics such as `mark`, `del`, `sub`, `sup`, `code`, `tt`, `kbd`, `samp`, and `pre`;
- `span` elements with supported inline CSS;
- hyperlinks;
- ordered and unordered lists; nested-list input is recognized but its
  structural extraction is currently partial (see the limitation below);
- blockquotes;
- images;
- HTML tables with `td`/`th`, `colspan`, `rowspan`, and supported cell styling.

These are the element families that the current implementation can translate.
That is not a guarantee of browser-equivalent semantics: several input
elements are reduced to simpler ODT structures.

| Input family | Current conversion | Boundary |
| --- | --- | --- |
| `h1`–`h6` | `Paragraph` using the corresponding `Heading N` style reference | Heading text is imported as one run; child markup is not recursively styled. |
| `p`, `div`, `article`, `section`, `header`, `footer`, `main` | Paragraph content; block inline CSS is split into paragraph/text options | These are not preserved as semantic HTML containers. |
| `blockquote` | Plain text in a paragraph referencing `Quote` | Nested inline markup is flattened. |
| `strong`/`b`, `em`/`i`, `u`, `mark`, `del`, `sub`, `sup`, `code`, `tt`, `kbd`, `samp`, `pre` | Styled text runs in paragraph content | A single semantic wrapper is supported; nested style composition and browser whitespace/layout are limited. `pre` remains paragraph text, not a browser layout box. |
| `span` | Text runs with supported inline CSS | Only properties understood by `StyleMapper` apply. |
| `a` | Native ODT hyperlink | Label is flattened from the element text. |
| `br` | Native `text:line-break` when inside an active paragraph | A break outside an active paragraph has no output. |
| `ul`, `ol`, `li` | Native `ListElement` / ODF lists | Flat lists import reliably. Nested-list extraction and adjacent list siblings are currently partial and can detach/reorder content; L08 does not claim them. |
| `table`, `tr`, `th`, `td` | Native `RichTable` and `RichTableCell` | `th` is treated like a cell, not as a repeating ODF header-row group. `<thead>`/`<tbody>` wrappers are not retained. Explicit inline cell styles are needed for visual header treatment. |
| `colspan`, `rowspan` | Span attributes on the imported `RichTableCell` | Physical layout/covered-cell normalization should be validated in LibreOffice for the target table. |
| `img` | Native `ImageElement` in a paragraph | Missing/invalid image sources are skipped; the importer does not synthesize a fallback. |

Unknown elements are generally traversed so supported child content may still
be imported; this is not a promise that unknown element semantics are retained.

## Inline styles

The importer uses `StyleMapper::parseInlineStyle()` and the normal ODT style pipeline for supported inline CSS-like properties. It does not implement selectors, cascading stylesheets, inheritance, or layout computation.

For example:

```php
$html = <<<'HTML'
<p style="margin-bottom: 0.2cm; text-align: justify;">
    Normal text
    <span style="color: #a40000; font-weight: bold;">highlighted text</span>
</p>
HTML;
```

For text runs, the current mapper recognizes properties including color,
background color, font weight/style, decoration, size, and family. Paragraph
and block mapping recognizes a bounded set including margins, padding,
alignment, line height, and borders. Table cells route background, border,
and padding to cell style, and supported text/paragraph options to their
content. Unsupported CSS declarations are ignored. This is not a general CSS
renderer.

## HTML tables

HTML tables are converted into native `RichTable` / `RichTableCell` structures:

```php
$html = <<<'HTML'
<table>
    <tr>
        <th style="background: #eeeeee; padding: 0.15cm;">Product</th>
        <th style="background: #eeeeee; padding: 0.15cm;">Price</th>
    </tr>
    <tr>
        <td>Tea</td>
        <td style="text-align: right;">3.50</td>
    </tr>
</table>
HTML;
```

The importer creates a native table and separates supported cell decoration
from paragraph/text styling before creating ODT cells. It imports rows found
under the table, but does not preserve `<thead>` as `table:table-header-rows`;
header appearance should be specified in the header cells' inline styles.
Supported `colspan`/`rowspan` values are written as native cell span
attributes. L08 includes an HTML table as part of its report; L07 teaches
explicit `RichTable` construction directly.

For tables requiring exact geometry, use the same caution as with programmatically created `RichTable` objects: HTML width rules do not imply browser-identical physical widths in LibreOffice.

## Images and security

Three source classes are supported by `HtmlImageResolver`:

- readable local filesystem image paths;
- valid `data:image/...;base64,...` resources, written to a temporary asset;
- HTTP/HTTPS images only when explicitly enabled.

Local and data images work without a network request. Invalid or missing
sources resolve to no image and are skipped by the importer.

```php
$content = HtmlImporter::fromHtml($html, [
    'allow_remote_images' => true,
]);
```

Remote images are disabled by default and require the explicit
`allow_remote_images` option shown above. When enabled, the current resolver
uses a 5-second stream timeout, does not follow redirects, reads at most
5,000,001 bytes to enforce a 5,000,000-byte maximum, and accepts the response
only when PHP can recognize image data. A remote failure is ignored as a
missing image; it does not mutate the document. Enabling this option creates
a network boundary, so use it only for trusted input and destinations.

The import pipeline uses `HtmlImageResolver` and `TemporaryAssetRegistry`.
Importer-created files are tracked and removed at process shutdown; the
structured-element pipeline embeds the resource when the RichText is inserted
and the document is saved. The canonical L08 sample uses a local image and a
deterministic data image and makes no live remote request. Its PHP source shows
the explicit opt-in as a comment only.

## Recommended use

HTML import works best as an adapter at the application boundary:

```text
controlled HTML
      ↓
HtmlImporter
      ↓
RichText / Paragraph / List / Table / Image
      ↓
ODT element pipeline
      ↓
editable ODT document
```

If your application already owns structured data, building `RichText` directly is usually more predictable than first converting the data to HTML and then importing it again.

## Current limitations

The importer intentionally supports a practical subset rather than full
HTML/CSS rendering. In particular, do not expect browser-equivalent behavior
for complex CSS layout, floats, advanced selectors, external stylesheets,
scripts, or arbitrary web markup. Browser-style `float`, `display`, and absolute-position layout are outside the importer contract. Use [C03 — Frame Layout](../../samples/sample_C03_frame_layout.php) for the current semantic frame-layout API.

Styled tables are useful document structures, but exact visual behavior
should be verified with representative LibreOffice output. The current
importer does not assign semantic repeating-header behavior to HTML `<thead>`
rows, nor does it promise browser-like mixed list styles. The current
recursive list importer can detach/re-attach a prior sibling while attempting
to extract nested lists; adjacent and nested list combinations are therefore
partial. L08 therefore uses separated flat list types and does not claim nested-list fidelity.

## Related samples

- [L08 — HTML Import](../../samples/sample_L08_html_import.php) is the canonical broad capability example, including native editable table import.

See [RichText & Paragraphs](../rich-documents/richtext-and-paragraphs.md) to understand the native element model produced by the importer.

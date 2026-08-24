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

## Supported structure

The current importer handles common document-oriented HTML, including:

- paragraphs and block containers such as `p`, `div`, `section`, `article`, `header`, `footer`, and `main`;
- headings `h1` through `h6`;
- line breaks;
- inline emphasis such as `strong`, `b`, `em`, `i`, and `u`;
- additional text semantics such as `mark`, `del`, `sub`, `sup`, `code`, `tt`, `kbd`, `samp`, and `pre`;
- `span` elements with supported inline CSS;
- hyperlinks;
- ordered and unordered lists, including nested structures;
- blockquotes;
- images;
- HTML tables with `td`/`th`, `colspan`, `rowspan`, and supported cell styling.

Unknown elements are generally traversed so that supported child content can still be imported.

## Inline styles

The importer uses `StyleMapper::parseInlineStyle()` and the normal ODT style pipeline for supported CSS-like properties.

For example:

```php
$html = <<<'HTML'
<p style="margin-bottom: 0.2cm; text-align: justify;">
    Normal text
    <span style="color: #a40000; font-weight: bold;">highlighted text</span>
</p>
HTML;
```

Only the subset understood by the engine can be translated meaningfully. Browser layout concepts, cascading stylesheets, selectors, JavaScript, and arbitrary web layout behavior are outside the scope of the importer.

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

The importer separates supported cell decoration from paragraph/text styling before creating the ODT table cells.

For tables requiring exact geometry, use the same caution as with programmatically created `RichTable` objects: HTML width rules do not imply browser-identical physical widths in LibreOffice.

## Images and security

Local images can be imported through `<img>` elements. Remote HTTP/HTTPS images are disabled by default.

```php
$content = HtmlImporter::fromHtml($html, [
    'allow_remote_images' => true,
]);
```

Enable remote images only for input and network destinations you trust. Remote image resolution creates a network boundary that does not exist when importing local application assets.

The import pipeline uses `HtmlImageResolver` and temporary-asset tracking so resolved temporary images can participate in normal ODT image embedding and cleanup.

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

The importer intentionally supports a practical subset rather than full HTML/CSS rendering. In particular, do not expect browser-equivalent behavior for complex CSS layout, floats, advanced selectors, external stylesheets, scripts, or arbitrary web markup.

Nested lists and complex table styling are supported at a useful document level, but should be verified with representative LibreOffice output when exact visual behavior matters.

## Related samples

- Sample 08 — HTML to editable ODT content
- Sample 19 — HTML table import
- `sample_html_images.php` — HTML image import behavior

See [RichText & Paragraphs](../rich-documents/richtext-and-paragraphs.md) to understand the native element model produced by the importer.

# Metadata

ODT document metadata is stored in `meta.xml`. `OdtTemplate` exposes `setMeta()` and `getMeta()` so applications can update common document properties without editing the package XML directly.

## Set metadata

```php
$template->setMeta([
    'title' => 'Q2 Financial Report',
    'author' => 'Anna Example',
    'subject' => 'Quarterly Financial Analysis',
    'description' => 'Generated with ODT Template Engine.',
    'keywords' => 'finance,report,2026',
    'language' => 'en',
    'generator' => 'ODT Template Engine',
    'date' => date('c'),
]);
```

Supported keys currently include:

| PHP key | ODF metadata element |
| --- | --- |
| `title` | `dc:title` |
| `subject` | `dc:subject` |
| `description` | `dc:description` |
| `coverage` | `dc:coverage` |
| `keywords` | `meta:keyword` |
| `initial_author` | `meta:initial-creator` |
| `author` | `dc:creator` |
| `language` | `dc:language` |
| `creation_date` | `meta:creation-date` |
| `date` | `dc:date` |
| `editing_cycles` | `meta:editing-cycles` |
| `editing_duration` | `meta:editing-duration` |
| `generator` | `meta:generator` |

Unknown keys are currently ignored.

## Read metadata

```php
$metadata = $template->getMeta();

$title = $metadata['title'] ?? null;
$author = $metadata['author'] ?? null;
```

`getMeta()` returns the supported fields that are present in the document.

## Metadata is separate from visible content

Setting metadata does not insert visible text into `content.xml`.

```text
meta.xml
└── document properties

content.xml
└── visible document body
```

If metadata should also appear visibly in the document, read it and build normal ODT content from it:

```php
$metadata = $template->getMeta();

$paragraph = new Paragraph();
$paragraph
    ->addText('Title: ', ['bold' => true])
    ->addText($metadata['title'] ?? '');

$template->setElement('metadata_summary', $paragraph);
```

## Save and reload

Metadata changes are serialized when the ODT is saved. Sample 04 demonstrates a complete round trip: set metadata, save the document, reopen it with a fresh `OdtTemplate`, call `getMeta()`, and render selected metadata as visible styled content.

That pattern is useful when testing that metadata survives the package write/read cycle rather than only inspecting the in-memory DOM.

## Date and duration values

ODF metadata uses structured textual values. For dates, ISO 8601 values are a good default:

```php
'date' => date('c')
```

Editing duration values are typically ISO 8601 durations, for example:

```php
'editing_duration' => 'PT20M'
```

The engine currently writes these values; it does not provide a high-level date/duration value object or semantic validator for every metadata field.

## Custom metadata fields

Arbitrary user-defined metadata fields are not part of the current `setMeta()` / `getMeta()` API. The public API currently covers the known standard mappings listed above.

If custom metadata becomes a project requirement, it should be added deliberately to the metadata API rather than relying on undocumented XML manipulation from application code.

## Related sample

- Sample 04 — setting, saving, reloading, and displaying metadata

For the package location of `meta.xml`, see [ODT Internals](odt-internals.md).

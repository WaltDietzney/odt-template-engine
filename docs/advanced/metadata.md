# Metadata

ODT document metadata is stored in `meta.xml`. `OdtTemplate::setMeta()` and
`OdtTemplate::getMeta()` provide the bounded public API for the supported
document properties; they are not an arbitrary custom-metadata interface.

## Canonical keys and creator aliases

Use `creator` for `dc:creator` and `initial_creator` for
`meta:initial-creator`. These distinguish the current/last-modified creator
from the original creator. The imperative input aliases `author` and
`initial_author` remain accepted for compatibility.

`getMeta()` returns canonical creator keys and, when present, also returns the
established `author` / `initial_author` aliases with the same values.

```php
$template->setMeta([
    'title' => 'Q2 Financial Report',
    'creator' => 'Anna Example',
    'initial_creator' => 'ODT Template Engine Sample',
    'subject' => 'Quarterly Financial Analysis',
    'description' => 'Generated with ODT Template Engine.',
    'language' => 'en',
    'generator' => 'ODT Template Engine',
    'date' => '2026-09-18T10:30:00Z',
]);
```

## Keywords

ODF stores keywords as repeatable `meta:keyword` elements. Use a list to set
multiple values:

```php
$template->setMeta([
    'keywords' => ['finance', 'report', '2026'],
]);
```

This replaces the complete existing keyword collection. `getMeta()` returns
all present keywords as a list. An empty list removes all existing keyword
elements.

For imperative compatibility, a string is also accepted and represents
exactly one keyword. It is not split on commas, semicolons, whitespace, or
other delimiters:

```php
$template->setMeta(['keywords' => 'finance,report,2026']);
// One keyword: "finance,report,2026"
```

## Supported fields and Phase-E value semantics

| Canonical PHP key | ODF element | Phase-E concrete value |
| --- | --- | --- |
| `title` | `dc:title` | string |
| `subject` | `dc:subject` | string |
| `description` | `dc:description` | string |
| `keywords` | repeated `meta:keyword` | list of strings |
| `initial_creator` | `meta:initial-creator` | string |
| `creator` | `dc:creator` | string |
| `language` | `dc:language` | language tag |
| `creation_date` | `meta:creation-date` | XML Schema `dateTime` |
| `date` | `dc:date` | XML Schema `dateTime` |
| `editing_cycles` | `meta:editing-cycles` | non-negative integer |
| `editing_duration` | `meta:editing-duration` | XML Schema `duration` |
| `generator` | `meta:generator` | string |
| `coverage` | `dc:coverage` | string; LibreOffice/Dublin Core extended metadata |

Phase-E preflight applies these target-specific types. In particular, a value
is not valid merely because PHP can cast it to a string: booleans and floats
are not generic metadata payloads. The existing imperative `setMeta()` facade
continues its legacy string-writing behavior for singular fields; the stricter
type checks apply to Phase-E preflight.

ODF 1.2 assigns the language, non-negative-integer, and duration datatypes to
the corresponding metadata elements; see the [OASIS OpenDocument 1.2
specification](https://docs.oasis-open.org/office/v1.2/OpenDocument-v1.2.html).

Date values use XML Schema `dateTime` lexical form, for example
`2026-09-18T10:30:00Z` or `2026-09-18T10:30:00+02:00`. Language values use the
ODF language-tag form, for example `en` or `en-US`. `editing_cycles` accepts
non-negative integer values. `editing_duration` uses XML Schema duration
syntax, for example `PT20M` or `P1DT2H`.

`coverage` is supported as LibreOffice / Dublin Core-compatible extended
metadata. This API does not expose arbitrary Dublin Core fields or user-defined
metadata. Support for additional fields requires a separate decision.

Unknown keys passed to the imperative `setMeta()` remain ignored for
compatibility.

## Read, save, and reload

```php
$template->save($outputPath);
$reopened = new OdtTemplate($outputPath);
$metadata = $reopened->getMeta();

$creator = $metadata['creator'] ?? null;
$keywords = $metadata['keywords'] ?? [];
```

Metadata is separate from visible content. Setting it does not insert text into
`content.xml`. S03 demonstrates setting metadata, saving, reopening, and
displaying selected values in document content.

## Related sample

- [S03 — Structured Professional Report](../../samples/sample_S03_structured_professional_report.php) — setting, saving, reloading, and displaying metadata

For the package location of `meta.xml`, see [ODT Internals](odt-internals.md).

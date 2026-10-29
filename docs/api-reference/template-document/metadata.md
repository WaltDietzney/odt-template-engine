# Metadata

`OdtTemplate` exposes a bounded metadata API for common ODT document properties stored in `meta.xml`.

Metadata is document information, not visible template content. It is independent of classic `assign()/render()` processing.

## `setMeta()`

**Recommended · Template & Document**

Updates supported metadata fields in the current working document.

```php
public function setMeta(array $meta): void
```

### Supported keys

| Key | Input | Behavior |
| --- | --- | --- |
| `title` | any value cast to string | Document title |
| `subject` | any value cast to string | Subject |
| `description` | any value cast to string | Description |
| `coverage` | any value cast to string | Coverage |
| `keywords` | `string` or `list<string>` | Complete keyword collection |
| `initial_creator` | any value cast to string | Initial creator |
| `creator` | any value cast to string | Current creator |
| `language` | any value cast to string | Document language value |
| `creation_date` | any value cast to string | Creation date value |
| `date` | any value cast to string | Document date value |
| `editing_cycles` | any value cast to string | Editing cycle value |
| `editing_duration` | any value cast to string | Editing duration value |
| `generator` | any value cast to string | Generator |

Compatibility input aliases:

- `author` -> `creator`
- `initial_author` -> `initial_creator`

Unknown keys are silently ignored for compatibility. The special `custom` key accepts a map of ODF user-defined metadata field names to string values.

### Keywords

A string is one keyword; it is not delimiter-split.

```php
$template->setMeta([
    'keywords' => ['invoice', 'customer', '2026'],
]);
```

A keyword array must be a PHP list and every item must be a string. Invalid keyword input throws `InvalidArgumentException`. An empty list removes all existing keyword elements.

### Lifecycle and side effects

`setMeta()` mutates the current document-local metadata state immediately. It does not require `render()`. Persist it with `save()`.

```php
$template->setMeta([
    'title' => 'Q2 Financial Report',
    'creator' => 'Anna Example',
    'subject' => 'Quarterly Financial Analysis',
]);

$template->save('report.odt');
```

The imperative metadata API intentionally keeps historical string-casting behavior for non-keyword fields. The stricter typed metadata validation used by mapped Phase-E automation is a separate contract.

## `getMeta()`

**Recommended · Template & Document**

Returns supported metadata currently present in the working document.

```php
public function getMeta(): array
```

Absent supported fields are omitted. `keywords`, when present, is returned as a list in document order.

When `creator` or `initial_creator` is present, the returned array also exposes the compatibility aliases `author` and `initial_author` with the same values.

```php
$metadata = $template->getMeta();

$title = $metadata['title'] ?? null;
$keywords = $metadata['keywords'] ?? [];
```

## Custom user-defined metadata

`setMeta(['custom' => ['bewerbungstools.profile' => $yaml]])` writes a native
`meta:user-defined` entry with `meta:value-type="string"` in `meta.xml`.
`getMeta()['custom']['bewerbungstools.profile']` reads it back. Values are stored
verbatim as strings, so callers may use YAML or other text formats. Names must
be nonempty strings; values must be strings. Invalid input throws
`InvalidArgumentException` without partially updating custom fields.

Existing custom fields with other names are retained. Updating the same name
replaces its value; duplicate entries of that name are consolidated. This is
not a cryptographic integrity or purchase-proof mechanism. Writer may change
custom metadata on editing; callers must handle stale data and conflicts.

## Limitations

This API does not expose arbitrary `meta.xml` element authoring. Standard fields and string-valued user-defined metadata are supported.

## See also

- [Template & Document Lifecycle](lifecycle.md)
- Mapping & Automation reference for stricter mapped metadata payload validation

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

Unknown keys are silently ignored for compatibility.

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

## Limitations

This API does not expose arbitrary custom `meta.xml` element authoring. It is limited to the supported mappings above.

## See also

- [Template & Document Lifecycle](lifecycle.md)
- Mapping & Automation reference for stricter mapped metadata payload validation

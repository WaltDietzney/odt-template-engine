# Template & Document Lifecycle

The `OdtTemplate` facade owns one working ODT document created from an original template source.

For classic template processing, the normal lifecycle is:

```text
construct
   -> assign / assignRepeating
   -> render
   -> optional immediate/structured mutations
   -> save
```

`save()` does **not** call `render()`. Structured and Writer-native mutations may use their own lifecycle and still require an explicit `save()`.

## `__construct()`

**Recommended · Template & Document**

Creates an `OdtTemplate` instance, loads the supplied ODT package immediately, prepares the working document, and registers shutdown cleanup.

```php
public function __construct(string $templatePath)
```

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `$templatePath` | `string` | Path to the source `.odt` template. |

### Lifecycle and side effects

Construction establishes the original template source and a separate working package state. Normal callers do not need to call `load()` immediately after construction.

```php
use OdtTemplateEngine\OdtTemplate;

$template = new OdtTemplate('templates/invoice.odt');
```

## `render()`

**Recommended · Simple Template Processing**

Applies values and repeating data previously staged through `assign()` and `assignRepeating()` to the current working document. It also executes the classic template-language filters, list placeholders, conditions, and foreach processing.

```php
public function render(): void
```

### When to use

Use `render()` for the classic visible template language.

```php
$template->assign([
    'customer' => 'Example GmbH',
]);

$template->assignRepeating('items', [
    ['name' => 'Consulting', 'price' => '900.00'],
]);

$template->render();
$template->save('invoice.odt');
```

### Lifecycle and side effects

`render()` mutates the current working DOM. It does not save the ODT package.

It is **not** a "rerender from original source" operation. Once a placeholder has been consumed in the working document, a later `render()` does not implicitly reconstruct it. Use `load()` when an explicit reset to the original template is required.

Assigning an `OdtElement` through `assign()` and then calling `render()` remains a compatibility path. For new structured content use `setElement()`.

## `save()`

**Recommended · Template & Document**

Finalizes the current working document and writes it as an ODT package.

```php
public function save(string $outputPath): void
```

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `$outputPath` | `string` | Absolute or relative path for the output ODT file. |

### Lifecycle and side effects

`save()` finalizes document-owned graphic/font requirements and package state before writing the file.

It does **not** call `render()`. For classic processing the caller must render first.

```php
$template->assign(['name' => 'Anna']);
$template->render();
$template->save('output.odt');
```

Structured operations such as `setElement()` mutate their document structure directly and can be saved without a classic render when no staged classic values need processing.

## `load()`

**Advanced · Lifecycle**

Resets the current working document from the instance's original template source and prepares it again.

```php
public function load(): void
```

### When to use

Use `load()` only when you deliberately want to discard working-document mutations and return to the original source of this `OdtTemplate` instance.

### Lifecycle and side effects

`load()` resets working package state, the legacy structured-render lifecycle state, and the successful common Phase-E automation guard.

It is not required after construction.

```php
$template->assign(['name' => 'First']);
$template->render();

$template->load(); // working document returns to the original template
```

## `cleanup()`

**Advanced · Lifecycle**

Releases the temporary package workspace used by this template instance.

```php
public function cleanup(): void
```

A shutdown cleanup callback is registered by the constructor, so normal short-lived application code does not need to call `cleanup()` merely to obtain cleanup at process shutdown.

Do not rely on further authoring calls after explicit cleanup; object reuse after cleanup is not part of the characterized public contract.

## `refresh()`

**Compatibility · Lifecycle**

Performs historical pre-reset finalization/persistence work and then resets the working document to the original template source.

```php
public function refresh()
```

Despite its name, `refresh()` does **not** mean "reload while preserving current rendered values".

```php
$template->assign(['name' => 'Rendered']);
$template->render();

$template->refresh();
// The working document is back at the original template state.
```

For new code, use the normal lifecycle directly. If an explicit reset is required, `load()` expresses that intent more clearly.

## Structured insertion in the lifecycle

`setElement()` is the Recommended entry point when PHP owns a structured dynamic region:

```php
public function setElement(string $placeholder, OdtElement $element): void
```

It materializes the structured element immediately rather than staging it for `render()`.

```php
use OdtTemplateEngine\Elements\RichText;

$content = new RichText();
$content->addParagraph('Generated structured content');

$template->setElement('content', $content);
$template->save('output.odt');
```

The full `OdtElement`, `RichText`, `Paragraph`, list, table, image, and frame contracts belong to the Structured Content reference.

## See also

- [Values & Repeating Data](values-and-repeating.md)
- [Metadata](metadata.md)
- [Images](images.md)
- [Styles & Document Defaults](styles-and-defaults.md)

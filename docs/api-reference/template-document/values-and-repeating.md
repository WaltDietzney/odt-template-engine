# Values & Repeating Data

Classic template processing is a staged lifecycle:

```text
assign values
    + assign repeating rows
    -> render
    -> save
```

The assignment methods do not render by themselves.

## `assign()`

**Recommended · Simple Template Processing**

Stages scalar values for later classic template rendering.

```php
public function assign(array $values): void
```

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `$values` | `array<string, mixed>` | Placeholder values merged into the current staged value set. |

A later assignment with the same key replaces the staged value for that key.

```php
$template->assign([
    'name' => 'Anna Example',
    'city' => 'Berlin',
]);

$template->render();
$template->save('letter.odt');
```

### Lifecycle and side effects

`assign()` only stages values. It does not mutate visible template placeholders until `render()` is called.

For new structured `OdtElement` content, use `setElement()`. Passing an `OdtElement` through `assign()` remains compatibility behavior and must not be assumed to have the same style/resource lifecycle as `setElement()`.

## `assignRepeating()`

**Recommended · Simple Template Processing**

Stages rows for a classic `{{#foreach:key}} ... {{#endforeach}}` block.

```php
public function assignRepeating(string $key, array $rows): void
```

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `$key` | `string` | Foreach block name. |
| `$rows` | `array<int, array<string, mixed>>` | Row-local placeholder data. |

```php
$template->assignRepeating('items', [
    ['name' => 'Coffee', 'price' => '4.99'],
    ['name' => 'Tea', 'price' => '3.49'],
]);

$template->render();
```

### Lifecycle and side effects

The rows are staged until `render()`. Calling `assignRepeating()` again with the same key replaces the staged rows for that key.

Classic foreach row substitution is the established classic-template mechanism; do not infer Writer-native table population semantics from it.

## `setValues()`

**Compatibility**

```php
public function setValues(array $values): void
```

`setValues()` currently has the same staging behavior as `assign()`: it merges values into the staged value set and does not render.

Prefer `assign()` for new classic-template code.

## `setRepeating()`

**Deprecated · Compatibility**

```php
public function setRepeating(string $key, array $rows): void
```

This method currently stages rows like `assignRepeating()`.

Prefer:

```php
$template->assignRepeating($key, $rows);
$template->render();
```

## `setRepeatingData()`

**Compatibility · Historical direct mutation**

```php
public function setRepeatingData(array $data): void
```

Unlike `assignRepeating()`, this method does **not** stage rows for `render()`. It immediately repairs classic markers and applies its older foreach mutation algorithm to the current content and styles DOMs.

```php
$template->setRepeatingData([
    'items' => [
        ['name' => 'Coffee'],
        ['name' => 'Tea'],
    ],
]);
```

This historical surface has no current focused behavioral characterization comparable to the Recommended staged workflow. It also does not provide a deterministic malformed-block exception when its expected end marker cannot be found.

Prefer `assignRepeating() -> render()` for new code.

## Classic lifecycle example

```php
use OdtTemplateEngine\OdtTemplate;

$template = new OdtTemplate('templates/order.odt');

$template->assign([
    'customer' => 'Example GmbH',
]);

$template->assignRepeating('items', [
    ['name' => 'Item A', 'price' => '10.00'],
    ['name' => 'Item B', 'price' => '15.00'],
]);

$template->render();
$template->save('order.odt');
```

## See also

- [Template & Document Lifecycle](lifecycle.md)
- Template Language guides for variables, filters, conditions, and loops

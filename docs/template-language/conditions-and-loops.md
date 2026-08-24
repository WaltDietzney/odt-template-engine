# Conditions & Loops

Template control structures let an ODT template show, hide, or repeat document sections without moving normal application logic into the document.

Keep these structures simple. Complex business decisions are usually easier to calculate in PHP before values are assigned to the template.

## Conditional blocks

Use `if` to render a block when a value is truthy:

```text
{{#if:is_admin}}
Administrator access
{{#endif}}
```

Assign the controlling value in PHP:

```php
$template->assign([
    'is_admin' => true,
]);
```

## `else`

Add an alternative branch with `else`:

```text
{{#if:is_admin}}
Administrator access
{{#else}}
Standard user access
{{#endif}}
```

## `elseif`

The engine also supports multiple branches:

```text
{{#if:price > 100}}
Premium order
{{#elseif:price >= 50}}
Standard order
{{#else}}
Small order
{{#endif}}
```

Supported comparison operators are:

```text
==  !=  >  <  >=  <=
```

Comparisons can use numeric values or strings. String literals may be quoted with single or double quotes.

Example:

```text
{{#if:status == "approved"}}
Approved
{{#else}}
Pending
{{#endif}}
```

## `ifnot`

Use `ifnot` when a block should be rendered for a falsy or undefined value:

```text
{{#ifnot:archived}}
This document is active.
{{#endif}}
```

## Repeating blocks

Use `foreach` when a document section should be repeated for a list of rows.

Template:

```text
{{#foreach:items}}
{{name}} — {{price}}
{{#endforeach}}
```

PHP:

```php
$template->assignRepeating('items', [
    ['name' => 'Tea', 'price' => '3.50'],
    ['name' => 'Coffee', 'price' => '4.20'],
]);
```

Each associative array represents one repetition. Placeholders inside the block are resolved from that row.

## Rendering order

Prepare all values and repeating data before calling `render()`:

```php
$template->assign([
    'customer_name' => 'Jane Smith',
    'is_vip' => true,
]);

$template->assignRepeating('items', [
    ['name' => 'Tea', 'price' => '3.50'],
    ['name' => 'Coffee', 'price' => '4.20'],
]);

$template->render();
$template->save(__DIR__ . '/output/order.odt');
```

During `render()`, the engine applies assigned values, repeating blocks, and conditional template logic to the ODT XML.

## Template-authoring guidance

Control markers are easiest to maintain when they are placed in clear paragraphs:

```text
{{#if:show_section}}
Section content
{{#endif}}
```

For loops, keep the start and end markers around the document nodes that should be repeated:

```text
{{#foreach:items}}
Item: {{name}}
Price: {{price}}
{{#endforeach}}
```

Avoid deeply nested template logic. If a condition becomes difficult to understand in the ODT document, calculate a simpler flag or value in PHP and assign that instead.

## Repeating template content vs. generated PHP content

Use a template loop when the repeated structure already exists naturally in the LibreOffice template.

Use programmatic elements when PHP needs to build a variable or structurally complex section. For example, a dynamically assembled `RichText`, `ListElement`, or `RichTable` may be clearer than forcing a large amount of structural logic into `foreach` blocks.

Both approaches can coexist in the same document.

## Compatibility APIs

New code should use:

```php
$template->assignRepeating('items', $rows);
$template->render();
```

Older repeating-data methods remain for compatibility but are not the recommended starting point for new applications.

## Related samples

- Sample 01 — variables and a simple repeating block
- Sample 03 — `if`, `elseif`, `else`, and `ifnot`
- Sample 10 — a larger document combining repeating data, conditions, rich content, images, and metadata

For the overall division of responsibility between the LibreOffice template and PHP, see [How the Engine Works](../concepts/how-it-works.md).

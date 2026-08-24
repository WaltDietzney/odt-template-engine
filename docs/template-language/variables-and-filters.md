# Variables & Filters

Template variables are the simplest way to place application data into an ODT document.

## Simple variables

Place a named placeholder in the LibreOffice template:

```text
Customer: {{customer_name}}
Email: {{email}}
```

Assign the values from PHP:

```php
$template->assign([
    'customer_name' => 'Jane Smith',
    'email' => 'JANE.SMITH@EXAMPLE.COM',
]);
```

Then render and save the document:

```php
$template->render();
$template->save(__DIR__ . '/output/result.odt');
```

Multiple calls to `assign()` are allowed. Later values with the same key replace earlier assignments in the internal value set.

## Filters

A filter transforms a value when the placeholder is rendered.

The general syntax is:

```text
{{filter:name}}
```

Some filters accept an option:

```text
{{filter:name|option}}
```

### Text filters

```text
{{upper:name}}
{{lower:email}}
{{trim:description}}
```

- `upper` converts text to uppercase.
- `lower` converts text to lowercase.
- `trim` removes surrounding whitespace.

### Date formatting

```text
{{date:created_at|d.m.Y}}
```

The option is passed as a PHP date format. If no format is supplied, the engine uses `d.m.Y`.

Example:

```php
$template->assign([
    'created_at' => '2026-08-24',
]);
```

With `{{date:created_at|d.m.Y}}`, the document receives `24.08.2026`.

### Number formatting

```text
{{number:total|2}}
```

The option specifies the number of decimal places. The current formatter uses a comma as decimal separator and a dot as thousands separator.

### Currency formatting

```text
{{currency:total}}
```

The current currency filter formats the value with two decimal places and appends ` €`.

### Checkbox formatting

```text
{{checkbox:approved}}
```

Truthy values render as `☑`; falsy values render as `☐`.

## Line breaks with `nl2br`

Use `nl2br` when a value contains newline characters that should become native ODT line breaks:

```text
{{nl2br:comment}}
```

```php
$template->assign([
    'comment' => "First line\nSecond line",
]);
```

The engine inserts ODT `<text:line-break/>` elements rather than writing HTML `<br>` tags.

## Keep placeholders easy to process

Office editors may split visually continuous text into multiple XML spans. The engine contains normalization logic for fragmented placeholders, but templates are more predictable when a placeholder remains one logical piece of text.

Prefer:

```text
{{customer_name}}
```

and apply formatting to the whole placeholder or its paragraph rather than styling individual characters inside the marker.

## Recommended assignment API

Use `assign()` for normal values:

```php
$template->assign([
    'name' => 'Jane Smith',
]);
```

Older direct assignment methods remain in the library for compatibility, but new application code and public samples should use the `assign()` → `render()` workflow.

## Complete example

```php
<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use OdtTemplateEngine\OdtTemplate;

$template = new OdtTemplate(__DIR__ . '/templates/customer.odt');

$template->assign([
    'name' => 'Jane Smith',
    'email' => 'JANE.SMITH@EXAMPLE.COM',
    'created_at' => '2026-08-24',
    'total' => '1345.5',
    'approved' => true,
]);

$template->render();
$template->save(__DIR__ . '/output/customer.odt');
```

A corresponding template could contain:

```text
Customer: {{upper:name}}
Email: {{lower:email}}
Created: {{date:created_at|d.m.Y}}
Total: {{currency:total}}
Approved: {{checkbox:approved}}
```

## Related samples

- Sample 01 — simple variables and repeating data
- Sample 02 — filters, formatting, and conditional values
- Sample 10 — larger business-document example combining template syntax and generated content

Continue with [Conditions & Loops](conditions-and-loops.md) for conditional sections and repeating blocks.

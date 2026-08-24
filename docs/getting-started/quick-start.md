# Quick Start

This guide creates a small editable ODT document using the recommended template workflow.

## 1. Create the template in LibreOffice

Create `templates/example.odt` in LibreOffice Writer and add placeholders such as:

```text
Customer: {{customer_name}}
Total: {{total}}
```

Save the file as an OpenDocument Text (`.odt`) document.

Keep each placeholder intact. Apply formatting to the complete placeholder or its surrounding paragraph rather than formatting individual characters inside `{{...}}` differently.

## 2. Fill the template from PHP

```php
<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use OdtTemplateEngine\OdtTemplate;

$template = new OdtTemplate(__DIR__ . '/templates/example.odt');

$template->assign([
    'customer_name' => 'Jane Smith',
    'total' => '129.90',
]);

$template->render();
$template->save(__DIR__ . '/output/example-result.odt');
```

`OdtTemplate` loads the template during construction, so a separate `load()` call is not required for the normal workflow.

The generated file remains a normal editable `.odt` document.

## 3. Add repeating data

A template may contain a repeating block:

```text
{{#foreach:items}}
{{name}} — {{price}}
{{#endforeach}}
```

Provide the rows with `assignRepeating()`:

```php
$template->assignRepeating('items', [
    ['name' => 'Tea', 'price' => '3.50'],
    ['name' => 'Coffee', 'price' => '4.20'],
]);
```

Call `render()` after all normal and repeating values have been assigned, then save the result.

## 4. Choose the right content model

Use template syntax when the document already contains the structure and PHP only supplies values or controls which sections are visible.

Use PHP document elements such as `RichText`, `Paragraph`, `ListElement`, `ImageElement`, and `RichTable` when PHP needs to construct the document structure itself.

Both approaches can be combined in the same ODT template.

## Next steps

- Read [Creating Templates](creating-templates.md) before building more complex template layouts.
- Continue with [Variables & Filters](../template-language/variables-and-filters.md).
- Continue with [Conditions & Loops](../template-language/conditions-and-loops.md).
- Read [How the Engine Works](../concepts/how-it-works.md) for the complete processing model.
- Explore the executable examples in the [public Sample Explorer](https://odt.walter-dietz.de/).

# Quick Start

This example fills simple variables, renders the template, and saves a new editable ODT document.

## 1. Create the template

Create `templates/example.odt` in LibreOffice Writer and add placeholders such as:

```text
Customer: {{customer_name}}
Total: {{total}}
```

Save the file as an OpenDocument Text (`.odt`) document.

## 2. Fill the template from PHP

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use OdtTemplateEngine\OdtTemplate;

$template = new OdtTemplate('templates/example.odt');
$template->load();

$template->assign([
    'customer_name' => 'Jane Smith',
    'total' => '129.90',
]);

$template->render();
$template->save('output/example-result.odt');
```

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

Call `render()` after all assignments have been prepared, then save the result.

## Next steps

- Read [Creating Templates](creating-templates.md) before building more complex template layouts.
- Read [How the Engine Works](../concepts/how-it-works.md) to understand the difference between template syntax and programmatically generated elements.
- Explore the existing samples in the repository and the [public Sample Explorer](https://odt.walter-dietz.de/).

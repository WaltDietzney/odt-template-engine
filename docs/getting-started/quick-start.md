# Quick Start

This is the shortest supported path from a LibreOffice-authored template to an
editable generated ODT. It uses only the **Recommended** 1.0 API.

You do not need to understand ODF XML or the engine's internal document model
for this workflow.

## 1. Create the template in LibreOffice

Create a directory named `templates`, then create
`templates/example.odt` in LibreOffice Writer.

Enter:

```text
Customer: {{customer_name}}
Total: {{total}}
```

Save the file as **OpenDocument Text (.odt)**.

Keep each placeholder intact. Apply formatting to the complete placeholder or
its surrounding paragraph rather than formatting individual characters inside
`{{...}}` differently.

## 2. Create an output directory

The engine writes to the path you give to `save()`. For this example, create:

```text
output/
```

Your small project can now look like this:

```text
your-project/
├── vendor/
├── templates/
│   └── example.odt
├── output/
└── generate.php
```

## 3. Fill and generate the document

Put this in `generate.php`:

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

Run it:

```bash
php generate.php
```

Open `output/example-result.odt` in LibreOffice. The placeholders have been
replaced and the result remains a normal editable ODT document.

The lifecycle used here is intentionally explicit:

```text
LibreOffice template
        ↓
new OdtTemplate(...)
        ↓
assign(...)
        ↓
render()
        ↓
save(...)
        ↓
editable .odt
```

`OdtTemplate` loads the source template during construction, so the normal
workflow does not need a separate `load()` call. `assign()` stages values,
`render()` applies the visible template language, and `save()` writes the
current document.

**`save()` does not call `render()` for you.** When you use visible
`{{...}}` template expressions, call `render()` before `save()`.

## 4. Add repeating data

Once the first document works, a template may also contain a lightweight
repeating block:

```text
{{#foreach:items}}
{{name}} — {{price}}
{{#endforeach}}
```

Provide its rows with the Recommended `assignRepeating()` API:

```php
$template->assignRepeating('items', [
    ['name' => 'Tea', 'price' => '3.50'],
    ['name' => 'Coffee', 'price' => '4.20'],
]);
```

Stage normal and repeating values first, then call `render()` once and save
the result.

## 5. Choose a working model only when you need more

The first example uses **Simple Template Processing**: Writer owns the document
structure and PHP supplies values.

As documents become more dynamic, the engine provides two complementary
choices:

- **Structured ODT Construction** — use `RichText`, `Paragraph`,
  `ListElement`, `RichTable`, `ImageElement`, frames, or text boxes when
  PHP genuinely owns a generated subtree.
- **Writer-native Document Model** — use named Sections, Bookmarks, tables,
  frames, and supported Writer fields when LibreOffice should continue to own
  that native structure and PHP needs a stable semantic handle.

These are not mutually exclusive modes. Decide per piece of structure:
**who owns it — Writer or PHP?**

## Next steps

- Learn [how to create robust templates](creating-templates.md).
- Continue with [Variables & Filters](../template-language/variables-and-filters.md)
  and [Conditions & Loops](../template-language/conditions-and-loops.md).
- Read [RichText & Paragraphs](../rich-documents/richtext-and-paragraphs.md)
  when PHP needs to own generated structure.
- Read [Addressable Native ODT Structures](../rich-documents/addressable-document.md)
  when Writer should own named native structure.
- Use [How the Engine Works](../concepts/how-it-works.md) for the complete
  ownership model.
- Explore the canonical L/C/B/S examples in the
  [Sample Guide](../examples/sample-guide.md) or the
  [public Sample Explorer](https://odt.walter-dietz.de/).
- Use the [API Reference](../api-reference/index.md) when you need exact
  signatures, options, lifecycle rules, or compatibility information.

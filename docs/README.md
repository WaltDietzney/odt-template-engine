---
<pre>
       )        (
    ( /(    (   )\ )     )
    )\())  ))\ (()/(  ( /(   ✨
   ((_)\  /((_) )(_)) )(_))  WaltDietzney
    _((_)_))  ((_)_  ((_)_    ODT Template Engine
   | || (_)_ _| | |   | | |   Document spells for .odt files 🧙‍♂️📄
   | __ / _` | | |__ | | |__  PHP-powered, template-magic.
   |_||_\__,_|_|____||_|____|
</pre>

# 🧩 ODT Template Engine for PHP

A lightweight PHP library for generating and manipulating OpenDocument Text (`.odt`) files from templates.

[![License](https://img.shields.io/github/license/WaltDietzney/odt-template-engine?color=blue)](../LICENSE)
[![PHP Version](https://img.shields.io/badge/PHP-%5E8.2-blue.svg)](https://www.php.net/)
[![ODF Friendly](https://img.shields.io/badge/OpenDocument-ODT-success.svg)](https://en.wikipedia.org/wiki/OpenDocument)

## ✨ Features

- Replace variables such as `{{name}}`, including filters
- Loops with `{{#foreach:items}} ... {{#endforeach}}`
- Conditional logic with `if`, `elseif`, `else`, and `ifnot`
- Filters such as `upper`, `lower`, `date`, `number`, `currency`, and `nl2br`
- Dynamic image insertion and replacement
- RichText and Paragraph elements
- Numbered and bullet lists
- Styled tables and table cells
- HTML-to-ODT import support
- Automatic span normalization for LibreOffice-generated XML
- Header, footer, styles, and metadata support

## 📦 Installation

```bash
composer require waltdietzney/odt-template-engine
```

Requirements:

- PHP 8.2 or newer
- DOM extension (`ext-dom`)
- ZIP extension (`ext-zip`)

## 📂 Project Structure

```text
src/
├── AbstractOdtTemplate.php
├── OdtTemplate.php
├── Contracts/
├── Elements/
├── Import/
└── Utils/

tests/                      Unit and integration tests
samples/                    Example scripts and ODT templates
demo/sample-explorer/       Optional local sample browser
docs/                       Project documentation
docker/                     Development container setup
```

The Composer library API lives under `src/`. Demo applications are kept separately under `demo/` and are not part of the core package API.

## 🚀 Quick Example

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use OdtTemplateEngine\OdtTemplate;

$template = new OdtTemplate('templates/example.odt');
$template->load();

$template->assign([
    'customer_name' => 'Jane Smith',
    'total' => '129.90',
    'is_vip' => true,
]);

$template->assignRepeating('items', [
    ['name' => 'Tea', 'price' => '3.50'],
    ['name' => 'Coffee', 'price' => '4.20'],
]);

$template->render();
$template->save('output/invoice_result.odt');
```

## 🖼 Image Handling

```php
use OdtTemplateEngine\Elements\ImageElement;

$image = new ImageElement('path/to/photo.jpg');
$image->setStyle([
    'width' => '5cm',
    'height' => '4cm',
    'align' => 'right',
    'anchor' => 'paragraph',
]);

$template->setElement('logo', $image);

$template->setImage('photo', 'path/to/photo.jpg', [
    'width' => '5cm',
]);

$template->replaceImageByName('logo', 'assets/logo.png', [
    'width' => '5cm',
]);
```

## 🖊 RichText and Paragraphs

```php
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\Elements\RichText;

$paragraph = new Paragraph();
$paragraph
    ->addText('Hello ', ['bold' => true])
    ->addText('world!', ['italic' => true]);

$richText = new RichText();
$richText->addParagraph($paragraph);

$template->setElement('intro', $richText);
```

## 📋 Styled Tables

```php
use OdtTemplateEngine\Elements\RichTable;
use OdtTemplateEngine\Elements\RichTableCell;

$table = new RichTable();
$table->addRow([
    new RichTableCell('Task', [
        'background' => '#ddeeff',
        'text-align' => 'center',
        'border' => '0.05pt solid #000',
    ]),
    new RichTableCell('Status', [
        'background' => '#ddeeff',
        'text-align' => 'center',
        'border' => '0.05pt solid #000',
    ]),
]);

$template->setElement('tableblock', $table);
```

## 🌍 HTML Import

```php
use OdtTemplateEngine\Import\HtmlImporter;

$html = '<h1>Imported Title</h1><p>This comes from HTML.</p>';
$element = HtmlImporter::fromHtml($html);

$template->setElement('html_block', $element);
```

## 🔠 Filters

| Filter | Syntax | Example output |
|---|---|---|
| `upper` | `{{upper:name}}` | `ANNA` |
| `lower` | `{{lower:email}}` | `anna@example.com` |
| `nl2br` | `{{nl2br:note}}` | ODT line breaks |
| `date` | `{{date:birth\|d.m.Y}}` | `01.01.1990` |
| `number` | `{{number:price\|2}}` | `4.20` |
| `currency` | `{{currency:price}}` | `4.20 €` |

## 🤖 Conditional Logic

```text
{{#if:is_vip}}
  VIP Customer
{{#elseif:total>100}}
  Premium Customer
{{#else}}
  Regular Customer
{{#endif}}
```

Negation is available through `{{#ifnot:is_blocked}}`.

## 🧼 Template Design Recommendations

For reliable processing:

- use tables instead of manual tabs for structured dynamic layouts;
- keep placeholders such as `{{name}}` intact;
- apply formatting around complete placeholders rather than inside them;
- avoid unnecessary nested spans and manual line breaks inside control structures;
- test templates with realistic sample data after significant layout changes.

LibreOffice and other editors can split text into multiple `<text:span>` nodes in `content.xml`. The engine includes normalization logic to make placeholder processing more robust, but simple template structures remain easier to maintain.

## 🧪 Tests

Install development dependencies and run:

```bash
composer install
composer test
```

The test suite currently contains two layers:

- focused unit/regression tests for ODT elements such as `Paragraph`;
- an ODT package integration test that loads a real sample template, renders data, saves the result, reopens the generated `.odt` as ZIP, checks required package entries, validates `content.xml`, `styles.xml`, and `meta.xml` as well-formed XML, and verifies rendered content.

GitHub Actions runs the test suite on PHP 8.2, 8.3, and 8.4.

## 🧪 Samples and Demo

Example scripts and templates are located under `samples/`.

The optional browser-based Sample Explorer is located under:

```text
demo/sample-explorer/
```

It is intended for local development and controlled test environments. See [`demo/README.md`](../demo/README.md) for details.

## 🔐 Security

Please report vulnerabilities according to [`SECURITY.md`](../SECURITY.md). Do not publish suspected vulnerabilities in a public issue before they have been reviewed.

## 🛠 Roadmap

- [ ] Expand automated integration coverage across representative ODT features
- [ ] Nested logic and loops
- [ ] Style presets and style exporting
- [ ] Additional RichText capabilities
- [ ] Improved table style mapping
- [ ] LibreOffice and Collabora integration testing

## 🧑‍💻 Author

Created by **Walter Dietz** — GitHub: `@WaltDietzney`

## 📜 License

MIT. See [`LICENSE`](../LICENSE).

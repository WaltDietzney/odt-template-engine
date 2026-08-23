# ODT Template Engine

**Generate real, editable OpenDocument Text (`.odt`) files from PHP.**

ODT Template Engine is an open-source PHP library for turning existing ODT templates into structured documents with variables, loops, conditions, images, rich text, lists, tables, styles, HTML imports and metadata.

[![CI](https://github.com/WaltDietzney/odt-template-engine/actions/workflows/ci.yml/badge.svg)](https://github.com/WaltDietzney/odt-template-engine/actions/workflows/ci.yml)
[![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-777BB4.svg)](https://www.php.net/)
[![License](https://img.shields.io/github/license/WaltDietzney/odt-template-engine)](LICENSE)
[![OpenDocument](https://img.shields.io/badge/OpenDocument-ODT-2ea44f.svg)](https://www.oasis-open.org/standards/)

> Use familiar LibreOffice/ODT documents as templates and keep the generated result editable instead of limiting document generation to PDF output.

**Official project site:** [odt.walter-dietz.de](https://odt.walter-dietz.de/) — explore the engine through the interactive Sample Explorer, inspect examples and generate real ODT documents online.

## Why ODT Template Engine?

Many document-generation workflows start with HTML and end with PDF. That is useful when the final document is meant to be fixed, but it is less convenient when users need to continue editing the result in an office application.

ODT Template Engine works directly with OpenDocument Text packages. Templates can be designed in LibreOffice, populated from PHP and saved again as real `.odt` files.

This makes the engine useful for documents such as:

- CVs and application documents;
- reports and structured business documents;
- letters and document templates;
- tables and data-driven documents;
- documents that must remain editable after generation.

## Features

- **Variables and filters** — replace placeholders such as `{{name}}` and transform values with filters.
- **Loops** — repeat template sections with `{{#foreach:items}} ... {{#endforeach}}`.
- **Conditional content** — use `if`, `elseif`, `else` and `ifnot` blocks.
- **Images** — insert new images or replace existing images in ODT packages.
- **Rich content** — build styled text and paragraphs programmatically.
- **Lists** — generate numbered and bulleted lists, including nested structures.
- **Tables** — create native ODT tables and styled table cells.
- **HTML import** — convert supported HTML fragments into native ODT content.
- **Styles** — map text, paragraph and table-cell styling to ODF markup.
- **Metadata** — write document title, author, description, dates and other metadata.
- **ODT-aware processing** — normalize editor-generated spans and manipulate the XML inside real ODT packages.

## Requirements

- PHP 8.2 or newer
- DOM extension (`ext-dom`)
- ZIP extension (`ext-zip`)

The automated test suite currently runs on PHP 8.2, 8.3 and 8.4.

## Installation

Install the package with Composer:

```bash
composer require waltdietzney/odt-template-engine
```

For development or to explore the repository locally:

```bash
git clone https://github.com/WaltDietzney/odt-template-engine.git
cd odt-template-engine
composer install
composer test
```

## Quick start

Create an ODT document in LibreOffice and place placeholders such as these in the document:

```text
Hello {{customer_name}}

{{#foreach:items}}
{{name}} — {{price}}
{{#endforeach}}
```

Then populate the template from PHP:

```php
<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use OdtTemplateEngine\OdtTemplate;

$template = new OdtTemplate('templates/example.odt');
$template->load();

$template->assign([
    'customer_name' => 'Jane Smith',
]);

$template->assignRepeating('items', [
    ['name' => 'Tea', 'price' => '3.50'],
    ['name' => 'Coffee', 'price' => '4.20'],
]);

$template->render();
$template->save('output/example-result.odt');
```

The result is a normal ODT document that can be opened and edited in LibreOffice and other compatible OpenDocument applications.

## Rich document elements

The template language is only one part of the engine. More complex document content can be constructed with ODT-aware elements.

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

The repository also contains elements and helpers for tables, table cells, images, lists, styles and HTML imports.

## Interactive samples

Try the **[live Sample Explorer](https://odt.walter-dietz.de/)** to browse examples, inspect their PHP source and generate downloadable ODT documents directly in the browser.

The same Sample Explorer is included in the repository under [`demo/sample-explorer/`](demo/sample-explorer/), while the growing collection of real ODT templates and executable examples lives under [`samples/`](samples/).

The explorer lets you:

- browse examples by feature area;
- search the sample collection;
- inspect template variables;
- inspect the PHP source;
- generate and download the resulting ODT document.

Run it locally with PHP's development server:

```bash
php -S localhost:8085 -t demo/sample-explorer
```

Then open `http://localhost:8085` in your browser.

> Before exposing your own Sample Explorer deployment publicly, review [`demo/README.md`](demo/README.md) and apply appropriate server and deployment controls.

## Used in real projects

ODT Template Engine is developed alongside real document-generation use cases rather than as an isolated format experiment.

### Bewerbungstools.de

[Bewerbungstools.de](https://www.bewerbungstools.de/) uses ODT-based document workflows for application tooling and document generation.

### CV Generator

The [CV Generator](https://www.bewerbungstools.de/lebenslauf-erstellen) uses the engine as a document-rendering layer for editable CVs with structured sections, layouts, rich text, images and styles. It serves as an important real-world consumer for the engine's richer document features.

## Tests and quality

Run the complete test suite with:

```bash
composer test
```

The suite combines focused regression tests with integration tests that generate fresh ODT files and inspect the resulting package. Integration coverage includes checks of ODT ZIP contents and XML such as `content.xml`, `styles.xml`, `meta.xml` and the package manifest.

GitHub Actions runs the suite against PHP 8.2, 8.3 and 8.4.

The project also uses generated sample documents for practical LibreOffice-oriented testing. Automated package tests are intended to complement, not completely replace, real office-suite compatibility checks.

## Documentation

More detailed feature documentation and examples are available in [`docs/README.md`](docs/README.md).

Useful repository areas:

```text
src/                     Core library
src/Elements/            Rich ODT document elements
src/Import/              Import helpers such as HTML import
src/Utils/               Style and XML utilities
tests/                   Unit and integration tests
samples/                 Example scripts, templates and assets
demo/sample-explorer/    Interactive local showcase
docs/                    Extended documentation
```

## Project status

The engine is actively maintained and already supports substantial real-world ODT generation. The public API and internal architecture are still evolving, so applications should pin an appropriate package version when stable API behavior is important.

Current development priorities include:

- broader integration coverage for representative document features;
- richer text and style capabilities;
- improved table and style mapping;
- continued LibreOffice compatibility testing;
- practical requirements discovered through real document-generation projects.

## Security

Please report suspected vulnerabilities according to [`SECURITY.md`](SECURITY.md). Do not publish security-sensitive reports as public issues before they have been reviewed.

## Contributing

Issues, reproducible bug reports and focused pull requests are welcome. Before submitting code, please install development dependencies and run:

```bash
composer test
```

A dedicated contribution guide will be added as the public release process is formalized.

## Support the project

ODT Template Engine is free and open source. If the project is useful to you, starring the repository helps other developers discover it.

You can also support continued development via [PayPal](https://www.paypal.com/donate/?hosted_button_id=RVFJUELPFMXQW) or visit the [official project site](https://odt.walter-dietz.de/) for PayPal and Bitcoin Lightning support.

## Author

Created and maintained by **Walter Dietz** (`@WaltDietzney`).

## License

ODT Template Engine is released under the [MIT License](LICENSE).

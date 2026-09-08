# ODT Template Engine

**Generate real, editable OpenDocument Text (`.odt`) files from PHP.**

ODT Template Engine is an open-source PHP library for turning existing ODT templates into structured documents with variables, loops, conditions, images, rich text, lists, tables, styles, HTML imports, metadata, and addressable native ODT structures.

[![CI](https://github.com/WaltDietzney/odt-template-engine/actions/workflows/ci.yml/badge.svg)](https://github.com/WaltDietzney/odt-template-engine/actions/workflows/ci.yml)
[![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-777BB4.svg)](https://www.php.net/)
[![License](https://img.shields.io/github/license/WaltDietzney/odt-template-engine)](LICENSE)
[![OpenDocument](https://img.shields.io/badge/OpenDocument-ODT-2ea44f.svg)](https://www.oasis-open.org/standards/)

> Use familiar LibreOffice/ODT documents as templates and keep the generated result editable instead of limiting document generation to PDF output.

### 🚀 Try it online

**[Open the live Sample Explorer](https://odt.walter-dietz.de/)** — inspect real PHP sample code, explore template variables and generate downloadable `.odt` documents directly in your browser. No installation required.

## Why ODT Template Engine?

Many document-generation workflows start with HTML and end with PDF. That is useful when the final document is meant to be fixed, but it is less convenient when users need to continue editing the result in an office application.

ODT Template Engine works directly with OpenDocument Text packages. Templates can be designed in LibreOffice, populated from PHP, structurally addressed by native ODT identities, and saved again as real `.odt` files.

This makes the engine useful for documents such as:

- CVs and application documents;
- reports and structured business documents;
- letters and document templates;
- tables and data-driven documents;
- documents whose repeatable structures should remain authored in LibreOffice;
- documents that must remain editable after generation.

## Three complementary ways to work with a document

The engine is not limited to one templating model.

### 1. Template language

Use visible expressions for scalar values, filters, conditions, and lightweight loops:

```text
Hello {{customer_name}}

{{#foreach:items}}
{{name}} — {{price}}
{{#endforeach}}
```

### 2. Programmatic ODT elements

When PHP owns a dynamic region's internal structure, build native ODT elements such as `RichText`, `Paragraph`, `ListElement`, `RichTable`, and `ImageElement`, then insert them into a template placeholder.

### 3. Addressable native ODT structures

When LibreOffice should own the structure, address native named document objects directly:

```php
$inspection = $template->inspect();
$template->bookmark('FullName')->replaceText('Jane Smith');
$experience = $template->section('ExperienceEntry');
```

Named sections can be cloned or instantiated from data, including nested repeatable collections. Named tables and frames can be resolved through typed targets for the operations currently supported by those target types.

These models can coexist in one document. The important design choice is **who owns the structure: the template or PHP?**

## Features

- **Variables and filters** — replace placeholders such as `{{name}}` and transform values with filters.
- **Loops** — repeat template sections with `{{#foreach:items}} ... {{#endforeach}}`.
- **Conditional content** — use `if`, `elseif`, `else` and `ifnot` blocks.
- **Images** — insert new images or replace existing images in ODT packages.
- **Rich content** — build styled text and paragraphs programmatically.
- **Lists** — generate numbered and bulleted lists, including nested structures.
- **Tables** — create native ODT tables, styled cells, relative column widths, and supported row geometry.
- **HTML import** — convert supported HTML fragments into native ODT content.
- **Styles** — use friendly element styling and document-local named paragraph styles.
- **Metadata** — write document title, author, description, dates and other metadata.
- **Document inspection** — inspect native named sections, bookmarks, tables, and frames.
- **Typed native targets** — resolve native ODT objects by semantic name instead of application XPath.
- **Named sections** — replace section content, clone native structure, and instantiate repeatable section collections.
- **Nested collections** — expand owner-scoped nested section prototypes without manually constructing generated native names.
- **ODT-aware processing** — preserve and manipulate native ODF structures inside real ODT packages.

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

$template = new OdtTemplate(__DIR__ . '/templates/example.odt');

$template->assign([
    'customer_name' => 'Jane Smith',
]);

$template->assignRepeating('items', [
    ['name' => 'Tea', 'price' => '3.50'],
    ['name' => 'Coffee', 'price' => '4.20'],
]);

$template->render();
$template->save(__DIR__ . '/output/example-result.odt');
```

`OdtTemplate` loads the source document during construction. After all values and repeating data have been assigned, `render()` applies the template logic and `save()` writes the resulting ODT package.

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

For reusable named paragraph styles in the current document, use the document-style facade:

```php
$template->styles()->defineParagraph('ReportHeading', [
    'margin-top' => '0.3cm',
    'margin-bottom' => '0.1cm',
]);

$heading = new Paragraph('ReportHeading');
```

The repository also contains elements and helpers for tables, table cells, images, lists and HTML imports.

## Native structured templates

LibreOffice-authored structure can also remain the source of truth for repeatable blocks.

```php
$experiences = $template
    ->section('ExperienceEntry')
    ->instantiateMany([
        ['note' => 'Current role', 'position' => 'Senior Project Manager'],
        ['note' => 'Previous role', 'position' => 'Project Coordinator'],
    ]);
```

Each returned `SectionTarget` represents the generated native section and can address nested section prototypes relative to its own subtree. This allows application data to drive collections without rebuilding the visual block in PHP.

Use `inspect()` when you need an immutable snapshot of the native named sections, bookmarks, tables, frames, and diagnostics present in the current document.

## Interactive samples

Want to see what the engine actually produces before installing it? **[Try the live Sample Explorer](https://odt.walter-dietz.de/)**. You can open the PHP source behind each example, inspect the template variables and generate the real editable ODT output yourself.

The same Sample Explorer is included in the repository under [`demo/sample-explorer/`](demo/sample-explorer/), while the growing collection of real ODT templates and executable examples lives under [`samples/`](samples/).

The later samples include two complementary CV architecture showcases:

- **Sample 21** — PHP-generated `RichText`/element regions inside a LibreOffice-designed shell;
- **Sample 25** — LibreOffice-authored named section collections instantiated from application data.

Run the explorer locally with PHP's development server:

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

The developer documentation is published at [odt.walter-dietz.de/docs/](https://odt.walter-dietz.de/docs/). Its versioned Markdown source lives in [`docs/`](docs/) and is built with Zensical.

Start with the [Quick Start](https://odt.walter-dietz.de/docs/getting-started/quick-start/) and then continue with the template-language, rich-document, addressable-structure and styling guides.

Useful repository areas:

```text
src/                     Core library
src/Document/            Document context, inspection, typed targets and document services
src/Elements/            Rich ODT document elements
src/Import/              Import helpers such as HTML import
src/Style/               Document-local style authoring and semantic style services
src/Utils/               Mapping, serialization and XML utilities
tests/                   Unit and integration tests
samples/                 Example scripts, templates and assets
demo/sample-explorer/    Interactive local showcase
docs/                    Developer documentation source
```

## Project status

The engine is actively maintained and already supports substantial real-world ODT generation. Its architecture now combines document-local package/context services, structured ODT elements, semantic style requirements, and typed access to native named ODT structures.

Current development priorities include:

- broader integration coverage for representative document features;
- layout work for frames, tables, lists and page flow;
- template-authoring and format-preservation improvements;
- continued LibreOffice compatibility testing;
- further structured-document capabilities driven by real document-generation requirements.

## Security

Please report suspected vulnerabilities according to [`SECURITY.md`](SECURITY.md). Do not publish security-sensitive reports as public issues before they have been reviewed.

## Contributing

Issues, reproducible bug reports and focused pull requests are welcome. See [`CONTRIBUTING.md`](CONTRIBUTING.md) for development and contribution guidance.

Before submitting code, please install development dependencies and run:

```bash
composer test
```

## Support the project

ODT Template Engine is free and open source. If the project is useful to you, starring the repository helps other developers discover it.

You can also support continued development via [PayPal](https://www.paypal.com/donate/?hosted_button_id=RVFJUELPFMXQW) or visit the [official project site](https://odt.walter-dietz.de/) for PayPal and Bitcoin Lightning support.

## Author

Created and maintained by **Walter Dietz** (`@WaltDietzney`).

## License

ODT Template Engine is released under the [MIT License](LICENSE).

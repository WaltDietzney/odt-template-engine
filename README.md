# ODT Template Engine

**Generate real, editable OpenDocument Text (`.odt`) files from PHP.**

ODT Template Engine is an open-source PHP library for working with LibreOffice-authored ODT templates, PHP-generated native ODT content, and named Writer structures while keeping the result editable as a real ODT document.

[![CI](https://github.com/WaltDietzney/odt-template-engine/actions/workflows/ci.yml/badge.svg)](https://github.com/WaltDietzney/odt-template-engine/actions/workflows/ci.yml)
[![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-777BB4.svg)](https://www.php.net/)
[![License](https://img.shields.io/github/license/WaltDietzney/odt-template-engine)](LICENSE)
[![OpenDocument](https://img.shields.io/badge/OpenDocument-ODT-2ea44f.svg)](https://www.oasis-open.org/standards/)

> Use familiar LibreOffice/ODT documents as templates and keep the generated result editable instead of limiting document generation to PDF output.

### 🚀 Try it online

**[Open the live Sample Explorer](https://odt.walter-dietz.de/#samples)** — inspect real PHP sample code, explore the semantic template contract and generate downloadable `.odt` documents directly in your browser. No installation required.

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

### 1. Simple Template Processing

Use visible expressions for scalar values, filters, conditions, and lightweight loops:

```text
Hello {{customer_name}}

{{#foreach:items}}
{{name}} — {{price}}
{{#endforeach}}
```

### 2. Structured ODT Construction

When PHP owns a dynamic region's internal structure, build native ODT elements such as `RichText`, `Paragraph`, `ListElement`, `RichTable`, and `ImageElement`, then insert them into a template placeholder.

### 3. Writer-native Document Model

When LibreOffice should own the structure, address native named document objects directly:

```php
$inspection = $template->inspect();
$template->bookmark('FullName')->replaceText('Jane Smith');
$experience = $template->section('ExperienceEntry');
```

Named Sections and Bookmarks expose bounded native operations; Writer-owned tables support bounded row population; named frames expose typed identity/inspection and participate in the separately validated mapped image-replacement workflow. Writer User Fields provide native document-global string bindings.

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
- **Document inspection** — inspect native named sections, bookmarks, tables, frames, template dependencies, and supported Writer User Fields.
- **Typed native targets** — resolve native ODT objects by semantic name instead of application XPath.
- **Named sections** — replace section content, clone native structure, and instantiate repeatable section collections.
- **Nested collections** — expand owner-scoped nested section prototypes without manually constructing generated native names.
- **Native Writer User Fields** — inspect and explicitly bind document-global string User Fields while preserving native Writer reevaluation semantics.
- **ODT-aware processing** — preserve and manipulate native ODF structures inside real ODT packages.

## Requirements

- PHP 8.2 or newer
- DOM extension (`ext-dom`)
- ZIP extension (`ext-zip`)

The automated test suite currently runs on PHP 8.2, 8.3 and 8.4.

LibreOffice is used to author normal ODT templates and is recommended for visual verification of generated documents. It is not a PHP runtime dependency of the engine.

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

`OdtTemplate` loads the source document during construction. After all values and repeating data have been assigned, `render()` applies the template logic and `save()` writes the resulting ODT package. `save()` does **not** implicitly call `render()`.

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



## Native Writer User Fields

For LibreOffice-authored document-global string values, use Writer User Fields:

```php
$template = new OdtTemplate(__DIR__ . '/templates/example.odt');

$template->setUserField('customer', 'Jane Smith');
$template->save(__DIR__ . '/output/example-result.odt');
```

The engine updates the authoritative User Field declarations in the working ODT. Cached field display text is left to Writer reevaluation.

This is intentionally separate from classic `{{customer}}` placeholder assignment. The 1.0 Writer User Field API is bounded to string User Fields; broader native field types are outside this contract.

## Interactive samples

Want to see what the engine actually produces before installing it? **[Try the live Sample Explorer](https://odt.walter-dietz.de/)**. You can open the PHP source behind each example, inspect the semantic template contract and generate the real editable ODT output yourself.

The same Sample Explorer is included in the repository under [`demo/sample-explorer/`](demo/sample-explorer/), while the growing collection of real ODT templates and executable examples lives under [`samples/`](samples/).

The canonical sample path culminates in professional ownership-focused examples:

- **S01b Professional CV · Structured Template** — LibreOffice owns stable page design, Frames, styles and repeatable native Sections while PHP supplies application data and bounded generated regions;
- **S03 Structured Professional Report** — a Writer-authored report demonstrates native inspection, mapping, preflight and bounded automation.

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

Start with the [Quick Start](https://odt.walter-dietz.de/docs/getting-started/quick-start/), then choose the guide for **Simple Template Processing**, **Structured ODT Construction**, or the **Writer-native Document Model**. For application-shaped data and inspected template contracts, [Mapping, Preflight & Automation](https://odt.walter-dietz.de/docs/advanced/mapping-automation/) is an optional integration workflow rather than a fourth authoring model.

For exact signatures, lifecycle rules, options, limitations, and API classifications, use the [Practical API Reference](https://odt.walter-dietz.de/docs/api-reference/). The [Sample Guide](https://odt.walter-dietz.de/docs/examples/sample-guide/) explains the canonical L/C/B/S learning path.

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

For the current voluntary support options, including PayPal and Bitcoin Lightning, visit the [project support page](https://odt.walter-dietz.de/#support).

## Author

Created and maintained by **Walter Dietz** (`@WaltDietzney`).

## License

ODT Template Engine is released under the [MIT License](LICENSE).

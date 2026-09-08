# ODT Template Engine for PHP

The ODT Template Engine generates fully editable OpenDocument Text (`.odt`) files from PHP applications.

It combines LibreOffice-authored templates with PHP data, programmatically constructed ODT elements, and an addressable model for native named document structures.

## Start here

- [Install the package](getting-started/installation.md)
- [Generate your first document](getting-started/quick-start.md)
- [Learn how to prepare ODT templates](getting-started/creating-templates.md)
- [Understand the engine's processing model](concepts/how-it-works.md)
- [Work with addressable native ODT structures](rich-documents/addressable-document.md)

## Three complementary ways to make a document dynamic

### 1. Template expressions

For simple values and lightweight template logic, place expressions directly inside the ODT template:

```text
{{customer_name}}

{{#foreach:items}}
{{name}} – {{price}}
{{#endforeach}}
```

This keeps the surrounding structure in LibreOffice while PHP supplies values, conditions, and repeating data.

### 2. Programmatically generated ODT elements

When PHP should own a dynamic content subtree, build native ODT elements and insert them into a placeholder:

```php
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\Elements\RichText;

$paragraph = new Paragraph();
$paragraph->addText('Generated from PHP', [
    'bold' => true,
]);

$richText = new RichText();
$richText->addParagraph($paragraph);

$template->setElement('content', $richText);
```

RichText, paragraphs, lists, tables, images, frames, and text boxes can be composed as native ODT structures.

### 3. Addressable native ODT structures

When LibreOffice should remain the structural designer but PHP needs stable semantic handles, address named structures already present in the document:

```php
$inspection = $template->inspect();
$bookmark = $template->bookmark('ApplicantName');
$experience = $template->section('ExperienceEntry');
$table = $template->table('SkillsTable');
$frame = $template->frame('ProfilePhoto');
```

The current addressable model covers sections, bookmarks, named tables, and named drawing frames. Supported operations are intentionally type-specific: named sections provide cloning and data-bound instantiation, bookmarks support bounded text replacement, while table and frame targets currently expose read-only descriptors.

See [Addressable Native ODT Structures](rich-documents/addressable-document.md) and [Named Sections](rich-documents/named-sections.md).

## LibreOffice remains the visual template designer

The three models are complementary rather than competing. A professional document can keep page layout, stable tables, frames, styles, and repeatable prototypes in LibreOffice while PHP supplies values, generates genuinely dynamic subtrees, and instantiates named native structures.

A useful rule of thumb is:

> **Keep durable office-document design in LibreOffice. Move only genuinely dynamic structure and data into PHP.**

## Real-world examples

The repository contains two complementary CV-shaped architecture examples:

- **Sample 21** builds large sidebar and main-content regions programmatically with `RichText`, paragraphs, lists, images, and reusable paragraph styles, then inserts those regions into a LibreOffice-designed layout.
- **Sample 25** keeps repeatable entry structures in the LibreOffice template as named native sections and expands them through section instantiation and nested collections.

The [Editable CV Showcase](examples/cv-showcase.md), [Building Complex Documents](examples/building-complex-documents.md), and [Sample Guide](examples/sample-guide.md) explain when each approach is useful.

The samples can also be executed through the public Sample Explorer.

## Project links

- [GitHub repository](https://github.com/WaltDietzney/odt-template-engine)
- [Packagist package](https://packagist.org/packages/waltdietzney/odt-template-engine)
- [Public project page](https://odt.walter-dietz.de/)
- [Live Sample Explorer](https://odt.walter-dietz.de/#samples)

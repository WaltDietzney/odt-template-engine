# ODT Template Engine for PHP

The ODT Template Engine generates fully editable OpenDocument Text (`.odt`) files from PHP applications.

It combines LibreOffice/OpenOffice templates with PHP data and programmatically constructed document elements such as rich text, paragraphs, lists, images, and tables.

## Start here

- [Install the package](getting-started/installation.md)
- [Generate your first document](getting-started/quick-start.md)
- [Learn how to prepare ODT templates](getting-started/creating-templates.md)
- [Understand the engine's processing model](concepts/how-it-works.md)

## Two ways to add dynamic content

For simple values and template logic, place expressions directly inside the ODT template:

```text
{{customer_name}}

{{#foreach:items}}
{{name}} – {{price}}
{{#endforeach}}
```

For structured content, build ODT elements in PHP and inject them into a placeholder:

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

## Real-world example

The [Editable CV Showcase](examples/cv-showcase.md) demonstrates a two-column CV with a sidebar, rich text, native lists, images, styles, dynamic career entries, education, qualifications, skills, languages, and programmatic page margins.

The complete sample can also be executed through the public Sample Explorer.

## Project links

- [GitHub repository](https://github.com/WaltDietzney/odt-template-engine)
- [Packagist package](https://packagist.org/packages/waltdietzney/odt-template-engine)
- [Public project page](https://odt.walter-dietz.de/)
- [Live Sample Explorer](https://odt.walter-dietz.de/#samples)

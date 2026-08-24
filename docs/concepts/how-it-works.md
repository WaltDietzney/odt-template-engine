# How the Engine Works

The ODT Template Engine combines a real office document with application data. It is template-driven, but it is not limited to replacing text placeholders.

A useful rule of thumb is:

> **Use LibreOffice for document design. Use PHP for dynamic content.**

The engine supports three complementary levels of document generation.

## Level 1: Template syntax

Create the stable document structure in LibreOffice Writer or another ODT-compatible editor and mark dynamic positions with placeholders:

```text
Customer: {{customer_name}}
```

Assign the value from PHP:

```php
$template->assign([
    'customer_name' => 'Jane Smith',
]);
```

Template syntax also supports filters, conditions, and repeating blocks. This is the simplest approach when the ODT template already owns the document structure.

See [Variables & Filters](../template-language/variables-and-filters.md) and [Conditions & Loops](../template-language/conditions-and-loops.md).

## Level 2: Structured PHP content

When the structure itself depends on application data, build native ODT content with PHP elements such as `RichText`, `Paragraph`, `ListElement`, `ImageElement`, and `RichTable`.

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

The template contains a placeholder such as `{{content}}`, but PHP supplies a document structure rather than plain text.

This is useful for dynamic lists, tables, styled paragraphs, images, and larger generated sections.

## Level 3: Advanced document control

Advanced workflows can also control document-level concerns such as:

- reusable styles;
- HTML import;
- document metadata;
- page layout;
- image and frame behavior.

These features still operate on a real ODT package rather than converting the document to another format.

## The ODT package

An `.odt` file is a ZIP package containing XML and related assets. Important package members include:

- `content.xml` — document body content and some automatic styles;
- `styles.xml` — document styles, page styles, and related definitions;
- `meta.xml` — document metadata;
- `META-INF/manifest.xml` — package file declarations;
- `Pictures/` — embedded image assets when present.

The engine extracts the template into a temporary working directory, loads the relevant XML documents, modifies them, and writes a new ODT package when `save()` is called.

You normally do not need to edit this XML manually. Understanding the package structure becomes useful when diagnosing advanced styling, layout, or interoperability behavior.

## The normal processing lifecycle

A typical document follows this sequence:

```text
LibreOffice ODT template
        ↓
new OdtTemplate(...)
        ↓
assign values / repeating data
        ↓
add generated ODT elements
        ↓
render()
        ↓
save(...)
        ↓
editable .odt document
```

`OdtTemplate` loads the source ODT during construction. `render()` applies assigned values, repeating blocks, and conditional template logic. `save()` writes styles and XML changes and packages the result as an ODT file.

## Template layout and PHP content work together

The most useful documents often combine the three levels instead of choosing only one.

```text
LibreOffice template
├── page and stable layout
├── static text
├── {{simple_value}}
└── {{generated_section}}

PHP
├── assigns simple values
├── controls conditions and loops
└── builds generated ODT elements

                    ↓
            ODT Template Engine
                    ↓
          fully editable .odt file
```

The [Editable CV Showcase](../examples/cv-showcase.md) demonstrates this architecture with a two-column LibreOffice template, programmatically generated sidebar and main content, native lists, an image, reusable styles, and programmatic page margins.

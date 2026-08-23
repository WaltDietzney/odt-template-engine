# How the Engine Works

The ODT Template Engine is template-driven, but it is not limited to replacing text placeholders. It supports two complementary ways to create dynamic document content.

## 1. Start with a real ODT template

Create the document layout in LibreOffice Writer or another ODT-compatible editor. The template remains a normal `.odt` file, so page structure, tables, static text, and other design decisions can be prepared visually.

Inside the template, mark dynamic positions with placeholders such as:

```text
{{customer_name}}
{{content}}
```

An ODT file is a ZIP package containing XML files such as `content.xml`, `styles.xml`, and `meta.xml`. The engine loads that package and modifies the relevant XML while preserving the document as an editable ODT file.

## 2. Use template syntax for data and control flow

Simple values are assigned from PHP:

```php
$template->assign([
    'customer_name' => 'Jane Smith',
]);
```

The template can also contain filters, conditions, and repeating blocks. For example:

```text
{{#if:is_vip}}
VIP Customer
{{#endif}}

{{#foreach:items}}
{{name}} — {{price}}
{{#endforeach}}
```

Repeating data is provided with `assignRepeating()`.

## 3. Build complex content with PHP elements

For content that is easier to express programmatically, the engine provides document elements such as `RichText`, `Paragraph`, `ListElement`, `ImageElement`, and `RichTable`.

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

The placeholder `{{content}}` is then replaced by the generated ODT structure rather than by plain text.

## 4. Combine template layout and generated content

The two approaches are designed to work together:

```text
LibreOffice template
        +
PHP data and ODT elements
        ↓
ODT Template Engine
        ↓
Fully editable .odt document
```

This separation is especially useful for complex documents. The template can define stable layout structures while PHP generates the parts that depend on application data.

The [Editable CV Showcase](../examples/cv-showcase.md) demonstrates this approach with a two-column template, programmatically generated sidebar and main content, native lists, an image, text styles, and programmatic page margins.

# How the Engine Works

The ODT Template Engine combines a real office document with application data. It is template-driven, but it is not limited to replacing text placeholders or generating content from PHP.

A useful rule of thumb is:

> **Use LibreOffice for durable document design. Use PHP for dynamic data, generated content, and bounded operations on semantic document structures.**

The engine supports three complementary document-authoring models.

## Level 1: Template expressions

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

Template syntax also supports filters, conditions, and repeating blocks. This is the simplest approach when the ODT template already owns the structure and PHP mainly supplies values and lightweight logic.

See [Variables & Filters](../template-language/variables-and-filters.md) and [Conditions & Loops](../template-language/conditions-and-loops.md).

## Level 2: Programmatically generated ODT content

When PHP genuinely owns a dynamic content subtree, build native ODT content with elements such as `RichText`, `Paragraph`, `ListElement`, `ImageElement`, and `RichTable`.

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

The template contains a placeholder such as `{{content}}`, but PHP supplies a native document structure rather than plain text.

This is useful for dynamic lists, tables, styled paragraphs, images, and larger generated regions.

## Level 3: Addressable native ODT structures

A LibreOffice template can also contain named native structures that PHP addresses directly:

```php
$inspection = $template->inspect();
$bookmark = $template->bookmark('ApplicantName');
$section = $template->section('ExperienceEntry');
$table = $template->table('SkillsTable');
$frame = $template->frame('ProfilePhoto');
```

This model keeps the native ODT structure authored in LibreOffice while giving application code stable semantic handles.

The current public target types deliberately have different capabilities:

- bookmarks support bounded text replacement;
- named sections support inspection, cloning, data-bound instantiation, finalized collections, and nested owner scopes;
- named tables and drawing frames currently expose typed resolution and read-only descriptors.

This is not a generic DOM API. The engine exposes bounded operations where semantics have been defined and tested.

See [Addressable Native ODT Structures](../rich-documents/addressable-document.md) and [Named Sections](../rich-documents/named-sections.md).

## Document-level capabilities

The three authoring models work alongside document-level features such as:

- reusable document-local paragraph styles;
- HTML import;
- document metadata;
- page layout;
- image and frame behavior.

These features still operate on a real ODT package rather than converting the document to another format.

## The ODT package and document context

An `.odt` file is a ZIP package containing XML and related assets. Important package members include:

- `content.xml` — document body content and some automatic styles;
- `styles.xml` — document styles, page styles, and related definitions;
- `meta.xml` — document metadata;
- `META-INF/manifest.xml` — package file declarations;
- `Pictures/` — embedded image assets when present.

Internally, `OdtPackage` owns the physical package and its resources. The current logical document is represented through `OdtDocumentContext`, which owns the active content/styles DOMs and document-local semantic dependencies such as style requirements.

Application code normally works through `OdtTemplate`; it should not manipulate those internal DOMs or services directly.

## The normal processing lifecycle

A typical document can combine all three models:

```text
LibreOffice ODT template
        ↓
new OdtTemplate(...)
        ↓
assign template values
        +
insert generated OdtElement content
        +
inspect/address native named structures
        ↓
render()
        ↓
save(...)
        ↓
editable .odt document
```

`OdtTemplate` loads and prepares the source document during construction. Template-language processing is delegated to `TemplateProcessor`. Structured elements contribute semantic requirements and physical resources before their native ODF subtree is materialized. Typed target resolvers address supported native structures in the current document state. `save()` finalizes the modified package as an ODT file.

## Semantic dependencies are document-local

Generated ODT structures may require paragraph, text, table-family, graphic, font-face, or fill-image definitions. These dependencies are collected from structured elements and registered against the current `OdtDocumentContext`.

`StyleContext` is the document-local semantic authority for style requirements. `StyleMapper` is a stateless option-mapping and identity helper, while `StyleWriter` is a narrow serialization helper. Neither is a process-global style owner.

Normal application code does not need to orchestrate these internals. Use element options for one-off formatting and `$template->styles()->defineParagraph()` for a reusable generated paragraph style in the current document.

See [Style Model](../styling/style-model.md) for the public style model.

## Template layout and PHP content work together

The strongest documents often combine the models instead of choosing only one:

```text
LibreOffice template
├── page and stable layout
├── static text and authored styles
├── {{simple_value}}
├── {{generated_region}}
└── named native structures
    ├── ApplicantName bookmark
    ├── ExperienceEntry section
    ├── SkillsTable
    └── ProfilePhoto frame

PHP
├── assigns simple values
├── controls template conditions and loops
├── builds generated ODT elements
└── addresses supported native targets

                    ↓
            ODT Template Engine
                    ↓
          fully editable .odt file
```

Sample 21 demonstrates the programmatically generated-region model. Sample 25 demonstrates the native structured-template model with named sections and data-bound collections. Both are valid architecture patterns; the right choice depends on whether PHP or the LibreOffice template should own the dynamic structure.

See [Building Complex Documents](../examples/building-complex-documents.md), [Editable CV Showcase](../examples/cv-showcase.md), and the [Sample Guide](../examples/sample-guide.md).

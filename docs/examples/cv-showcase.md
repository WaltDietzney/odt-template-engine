# Editable CV Showcase

Sample 21 demonstrates how the ODT Template Engine can be used for a realistic document rather than only isolated feature examples.

The result is a fully editable two-column CV generated from an ODT template and structured PHP content.

## What the sample demonstrates

The sample combines:

- a two-column LibreOffice template;
- a dark sidebar and main content column;
- `PageLayoutOdtTemplate` for programmatic page margins;
- `RichText` and `Paragraph` for structured text;
- native ODT bullet lists through `ListElement`;
- an embedded image through `ImageElement`;
- text and paragraph styles;
- dynamic professional experience, education, qualifications, skills, and languages.

## Template and PHP have different responsibilities

The ODT template defines the stable column structure and contains two placeholders:

```text
{{cv_sidebar}}
{{cv_content}}
```

PHP builds the content that belongs in those areas.

```text
LibreOffice template
├── sidebar column
│   └── {{cv_sidebar}}
└── main column
    └── {{cv_content}}

PHP
├── builds sidebar content
├── builds main CV content
└── adjusts page margins
```

This keeps the document editable in an office application while allowing application data to control the generated content.

## Programmatic page margins

Sample 21 uses the page-layout-aware template class:

```php
use OdtTemplateEngine\PageLayoutOdtTemplate;

$template = new PageLayoutOdtTemplate(
    'samples/templates/template_21_cvProfile.odt'
);

$template->setPageMargins(
    '0cm',
    '0.8cm',
    '0cm',
    '0cm'
);
```

`PageLayoutOdtTemplate` extends the regular template processor and changes the page layout referenced by the document's master page in `styles.xml`.

## Building the sidebar

The sidebar is assembled as a `RichText` block. Individual `Paragraph` objects provide paragraph and text styling, while an `ImageElement` inserts the profile image.

```php
use OdtTemplateEngine\Elements\ImageElement;
use OdtTemplateEngine\Elements\RichText;

$sidebar = new RichText();

$sidebar->addImage(new ImageElement($cv['personal']['photo'], [
    'width' => '3.4cm',
    'height' => '3.4cm',
    'anchor' => 'as-char',
    'align' => 'left',
]));
```

The complete sample also uses native `ListElement` objects for sidebar lists.

## Building dynamic experience entries

Professional experience is generated from application data. Each station creates several paragraphs followed by a native bullet list for its tasks.

The same approach is used for education and additional qualifications, which makes the example representative of data-driven business documents.

## Injecting the finished content

After both content blocks have been built, they are assigned to the template placeholders:

```php
$template->setElement('cv_sidebar', $sidebar);
$template->setElement('cv_content', $content);
$template->save('samples/output/output_21_cvProfile.odt');
```

The generated file remains an editable ODT document.

## Architecture behind the showcase

The showcase is intentionally short. For the architectural lessons behind it — section builders, semantic styles, template/application boundaries, and how to scale from a sample to a production renderer — continue with [Building Complex Documents](building-complex-documents.md).

## Run the complete example

The documentation intentionally shows only the architectural parts of the example. The executable source remains the single source of truth:

- [View `sample_21_cvProfile.php` on GitHub](https://github.com/WaltDietzney/odt-template-engine/blob/master/samples/sample_21_cvProfile.php)
- [Open the public Sample Explorer](https://odt.walter-dietz.de/)

For the underlying processing model, see [How the Engine Works](../concepts/how-it-works.md). For the individual numbered examples, see the [Sample Guide](sample-guide.md).

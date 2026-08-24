# Building Complex Documents

Complex ODT generation becomes manageable when the application does **not** try to generate every aspect of the document from PHP.

Sample 21 demonstrates the architecture the engine is designed to support:

```text
application data
      ↓
small PHP rendering functions
      ↓
RichText / Paragraph / List / Image elements
      ↓
large template placeholders
      ↓
LibreOffice-designed document structure
      ↓
editable ODT output
```

The sample is a CV, but the same architecture applies to reports, dossiers, offers, profiles, certificates, and other structured office documents.

## 1. Let the template own durable layout

Sample 21 starts from a LibreOffice-designed template containing the two-column CV structure. PHP does not rebuild the entire page from low-level XML.

Conceptually, the template contains large insertion regions such as:

```text
┌──────────────────────┬───────────────────────────────┐
│                      │                               │
│   {{cv_sidebar}}     │       {{cv_content}}          │
│                      │                               │
└──────────────────────┴───────────────────────────────┘
```

The table/column structure, page design, and stable visual composition remain editable in LibreOffice.

This is the template-first principle at application scale.

## 2. Keep application data independent

The sample stores its example CV data in a normal PHP array:

```php
$cv = [
    'personal' => [
        'name' => 'Max Mustermann',
        'email' => 'max.mustermann@example.de',
    ],
    'profile' => 'Experienced developer ...',
    'skills' => [
        ['name' => 'PHP', 'level' => 5],
        ['name' => 'SQL', 'level' => 4],
    ],
    'experience' => [
        // ...
    ],
];
```

In a real application this data can come from forms, a database, an API, or a domain model. The important point is that the data does not contain ODF XML.

## 3. Render sections, not the whole document

Build focused rendering functions that return engine elements.

For example:

```php
function cvParagraph(
    string $text,
    array $textStyle = [],
    ?string $paragraphStyle = null
): Paragraph {
    $paragraph = new Paragraph($paragraphStyle);
    $paragraph->addText($text, array_merge([
        'font-family' => 'Arial',
    ], $textStyle));

    return $paragraph;
}
```

Then create higher-level helpers for repeated visual roles:

```php
function addMainHeading(
    RichText $rich,
    string $title,
    bool $first = false
): void {
    $rich->addParagraph(cvParagraph(
        $title,
        [
            'bold' => true,
            'font-size' => '13pt',
            'color' => '#111111',
        ],
        $first ? 'CVMainHeadingFirst' : 'CVMainHeading'
    ));
}
```

This is much easier to maintain than one giant renderer method containing every paragraph and style rule.

## 4. Use semantic style names for repeated roles

Sample 21 registers names such as:

```text
CVSidebarName
CVSidebarHeading
CVMainHeading
CVProfile
CVEntryDate
CVEntryTitle
CVEntryCompany
```

That is preferable to thinking only in terms of visual attributes such as "13pt bold with bottom border".

The semantic name describes why the style exists.

```php
StyleMapper::registerParagraphStyle('CVMainHeading', [
    'margin-top' => '0.45cm',
    'margin-bottom' => '0.10cm',
    'padding-bottom' => '0.03cm',
    'line-height' => '100%',
    'border-bottom' => '1.5pt solid #12324a',
]);
```

Remember the current advanced-API caveat: explicit `StyleMapper` registries are static process state. The project roadmap tracks a future document-scoped style context.

## 5. Compose RichText from native elements

A sidebar can contain text, an image, and native lists in one generated region:

```php
$sidebar = new RichText();

$sidebar->addParagraph(cvParagraph(
    $cv['personal']['name'],
    [
        'bold' => true,
        'font-size' => '16pt',
        'color' => '#ffffff',
    ],
    'CVSidebarName'
));

$sidebar->addImage(new ImageElement(
    $cv['personal']['photo'],
    [
        'width' => '3.4cm',
        'height' => '3.4cm',
        'anchor' => 'as-char',
    ]
));
```

Lists are represented as `ListElement`, not as bullet characters embedded in strings. Experience entries are represented as paragraphs with meaningful paragraph roles.

The result remains native, editable ODT content.

## 6. Build separate large regions

The CV creates a sidebar block and a main-content block separately:

```php
$sidebar = new RichText();
$content = new RichText();
```

Each block can have its own rendering helpers and styling conventions. Finally, they are assigned to the large template placeholders:

```php
$template->setElement('cv_sidebar', $sidebar);
$template->setElement('cv_content', $content);
```

This is a useful scale boundary. Avoid creating hundreds of tiny template placeholders when one coherent generated region is easier to own in PHP.

Conversely, avoid replacing the entire document with one generated region when stable layout can remain in LibreOffice.

## 7. Use page-layout code only where it adds value

Sample 21 uses `PageLayoutOdtTemplate` to adjust margins:

```php
$template = new PageLayoutOdtTemplate(
    __DIR__ . '/templates/template_21_cvProfile.odt'
);

$template->setPageMargins('0cm', '0.8cm', '0cm', '0cm');
```

It does **not** recreate the full two-column design programmatically. This is the intended balance between template-owned layout and application-controlled variation.

## 8. Separate data, rendering, and template responsibilities

For production applications, a useful architecture is:

```text
Domain / application data
        │
        ▼
Document renderer
        │
        ├── buildSidebar()
        ├── buildProfile()
        ├── buildExperience()
        ├── buildEducation()
        └── buildSkills()
        │
        ▼
ODT Template Engine elements
        │
        ▼
LibreOffice template
        │
        ▼
Generated .odt
```

The sample keeps everything in one script so it remains runnable and easy to inspect. A real application should normally move the section builders into renderer classes or focused private methods.

For example:

```php
final class CvOdtRenderer
{
    private function buildSidebar(CvData $cv): RichText
    {
        // ...
    }

    private function buildContent(CvData $cv): RichText
    {
        // ...
    }
}
```

The engine does not require this class structure; it is an application architecture that scales better as document complexity grows.

## 9. Prefer native ODT semantics

When building complex documents:

- use paragraph margins for spacing;
- use named paragraph roles for repeated layout semantics;
- use `ListElement` for lists;
- use `RichTable` for genuinely tabular generated content;
- use image elements or template image replacement according to layout ownership;
- keep HTML import at integration boundaries rather than making HTML the internal document model.

These choices make the output easier to edit and reduce surprises in LibreOffice.

## 10. Know when to stop generating layout in PHP

Some layout requirements are better expressed directly in LibreOffice.

If you find yourself programmatically reproducing fixed headers, fixed multi-column page composition, elaborate master-page behavior, or exact static frame positions, consider moving that responsibility back into the `.odt` template.

The engine is strongest when PHP controls **dynamic structure and data** while LibreOffice controls **stable office-document design**.

## Verification for complex documents

A real-world document should be tested at several levels:

```text
renderer/unit behavior
        +
ODT package/XML correctness
        +
representative generated sample
        +
LibreOffice visual inspection
```

Sample 21 is especially useful as a regression document because it exercises many subsystems at once. It should complement focused tests, not replace them.

## Continue exploring

- [Sample Guide](sample-guide.md) — choose smaller focused examples
- [RichText & Paragraphs](../rich-documents/richtext-and-paragraphs.md) — generated section building blocks
- [Style Model](../styling/style-model.md) — style responsibilities
- [Page Layout](../advanced/page-layout.md) — controlled page geometry
- [ODT Internals](../advanced/odt-internals.md) — package-level debugging

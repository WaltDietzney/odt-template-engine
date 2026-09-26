# Building Complex Documents

Complex ODT generation becomes manageable when the application does **not** try to generate every aspect of the document from PHP.

The engine supports more than one useful ownership boundary between LibreOffice and application code. This chapter explains the **programmatically generated region** pattern that S01b uses for its bounded sidebar regions, while S01b keeps its main repeatable structures Writer-owned:

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

C04 demonstrates a complementary pattern in which repeatable native sections remain authored in LibreOffice and PHP addresses and instantiates those semantic template objects. See [Named Sections](../rich-documents/named-sections.md) when that ownership model fits better.

The samples use CVs, but both patterns apply to reports, dossiers, offers, profiles, certificates, and other structured office documents.

## 1. Let the template own durable layout

S01b starts from a LibreOffice-designed template containing the two-column CV structure, Writer Frames, native repeatable Sections, and named image geometry. PHP does not rebuild the entire page from low-level XML.

Conceptually, Writer owns the stable page and repeatable main-column structures while PHP owns only bounded generated regions such as the sidebar Frames.

This is one form of the template-first principle at application scale.

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

Then create higher-level helpers for repeated visual roles. This is much easier to maintain than one giant renderer method containing every paragraph and style rule.

## 4. Use semantic style names for repeated roles

S01b defines reusable names such as:

```text
CVSidebarName
CVSidebarHeading
CVMainHeading
CVProfile
CVEntryDate
CVEntryTitle
CVEntryCompany
```

The semantic name describes why the style exists.

```php
$template->styles()->defineParagraph('CVMainHeading', [
    'margin-top' => '0.45cm',
    'margin-bottom' => '0.10cm',
    'padding-bottom' => '0.03cm',
    'line-height' => '100%',
    'border-bottom' => '1.5pt solid #12324a',
]);
```

The definition belongs to the current logical document. Content can reference the named style later with `new Paragraph('CVMainHeading')`; that constructor call is a style reference and does not define the style.

## 5. Compose RichText from native elements

A generated region can contain text, images, and native lists:

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

Lists are represented as `ListElement`, not as bullet characters embedded in strings. The result remains native, editable ODT content.

## 6. Build separate large regions

The CV creates a sidebar block and a main-content block separately:

```php
$sidebar = new RichText();
$content = new RichText();
```

In S01b the bounded sidebar regions are assigned to template-authored Writer Frame insertion points:

```php
$template->setElement('CVSidebarPage1', $sidebarPage1);
$template->setElement('CVSidebarPage2', $sidebarPage2);
```

This is a useful scale boundary when PHP owns the region. Avoid hundreds of tiny placeholders when one coherent generated region is easier to own in PHP.

Conversely, do not replace a complete LibreOffice-authored structure merely because PHP can rebuild it. If a repeatable block should remain visually authored in the template, consider a named native section instead.

## 7. Keep stable page geometry in Writer

The current canonical S01b template owns its page geometry and stable two-column
composition in LibreOffice. The sample uses the normal `OdtTemplate` facade;
it does not require `PageLayoutOdtTemplate` or programmatic margin changes.

Use the advanced page-layout API only when an application genuinely needs to
change bounded page geometry. Do not move stable template design into PHP merely
because the API can express it.

## 8. Separate data, rendering, and template responsibilities

For production applications using generated regions, a useful architecture is:

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

The engine does not require a particular renderer class structure; focused renderer methods simply scale better as generated regions become complex.

## 9. Prefer native ODT semantics

When building complex documents:

- use paragraph margins for spacing;
- use named paragraph roles for repeated layout semantics;
- use `ListElement` for lists;
- use `RichTable` for genuinely tabular generated content;
- use image elements or template image replacement according to layout ownership;
- use named native sections when the template should own a repeatable structure;
- keep HTML import at integration boundaries rather than making HTML the internal document model.

These choices make the output easier to edit and reduce surprises in LibreOffice.

## 10. Choose the ownership boundary deliberately

A useful question is not only “Can PHP generate this?” but **“Who should own this structure?”**

Use a bounded generated region, as in S01b's sidebar, when application code genuinely controls its internal structure and composition.

Use native named sections, as in C04, when LibreOffice should remain the visual authoring environment for a repeatable semantic block and PHP should mainly bind and repeat it.

Use simple placeholders when only scalar values or lightweight template logic are dynamic.

This gives three complementary levels rather than one universal rendering strategy.

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

Use the canonical samples for regression and learning: S01b demonstrates a mixed professional template, while C04 isolates declarative Writer-owned collections. They should complement focused tests, not replace them.

## Continue exploring

- [Sample Guide](sample-guide.md) — choose the smallest canonical L/C/B/S example that matches the ownership problem
- [RichText & Paragraphs](../rich-documents/richtext-and-paragraphs.md) — generated region building blocks
- [Addressable ODT Structures](../rich-documents/addressable-document.md) — typed access to native document objects
- [Named Sections](../rich-documents/named-sections.md) — native repeatable template structures
- [Style Model](../styling/style-model.md) — style responsibilities
- [Page Layout](../advanced/page-layout.md) — controlled page geometry
- [ODT Internals](../advanced/odt-internals.md) — package-level debugging

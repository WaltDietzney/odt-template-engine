# Editable CV Showcase

The repository contains two complementary CV architecture showcases.

**Sample 21** demonstrates a realistic document whose large dynamic regions are constructed from structured PHP elements and inserted into a LibreOffice-designed shell.

**Sample 25** demonstrates a LibreOffice-authored CV whose repeatable native sections are addressed and instantiated directly from PHP.

Both produce editable ODT output. They differ mainly in **who owns the dynamic structure**.

## Sample 21 — PHP-generated document regions

Sample 21 combines:

- a two-column LibreOffice template;
- a dark sidebar and main content column;
- `PageLayoutOdtTemplate` for programmatic page margins;
- `RichText` and `Paragraph` for structured text;
- native ODT bullet lists through `ListElement`;
- an embedded image through `ImageElement`;
- text and paragraph styles;
- dynamic professional experience, education, qualifications, skills, and languages.

The ODT template defines the stable column structure and contains two large placeholders:

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

After both content blocks have been built, they are assigned to the template placeholders:

```php
$template->setElement('cv_sidebar', $sidebar);
$template->setElement('cv_content', $content);
```

Use this model when PHP genuinely owns the internal structure of a dynamic region.

## Sample 25 — LibreOffice-authored native sections

Sample 25 keeps more structure in the ODT template itself. Scalar values still use ordinary assignment, while repeatable CV entries are native named sections authored in LibreOffice.

```php
$template->assign([
    'firstname' => 'Max',
    'lastname' => 'Mustermann',
    'profession' => 'Senior Projektmanager',
]);

$experienceInstances = $template
    ->section('ExperienceEntry')
    ->instantiateMany($experienceRows);
```

Each generated experience instance then owns its own nested `ActivityEntry` prototype, which can be expanded independently.

```php
foreach ($experienceInstances as $index => $experience) {
    $experience
        ->section('ActivityEntry')
        ->instantiateMany($activities[$index]);
}
```

This model is useful when repeatable blocks should remain visually editable in LibreOffice and PHP should address semantic template objects rather than reconstruct their native structure.

## Choosing between the two

A useful rule is:

| Need | Prefer |
| --- | --- |
| PHP owns a dynamic region's internal composition | Sample 21 / `RichText` + `setElement()` |
| LibreOffice owns a repeatable semantic block | Sample 25 / named sections + `instantiateMany()` |
| only scalar values or lightweight logic change | normal template expressions |

The models can also coexist in one document. A named section may contain ordinary placeholders, and PHP-generated elements can still be used where application-owned structure is appropriate.

## Why both matter

Sample 21 remains an important benchmark for the structured-element layer: paragraphs, lists, images, styles, and large generated regions.

Sample 25 is the benchmark for the newer addressable native-document layer: semantic section identities, nested ownership, cloning/instantiation, collection finalization, and preservation of LibreOffice-authored structure.

Together they show the direction of the engine more accurately than either sample alone:

```text
LibreOffice visual authoring
        +
lightweight template language
        +
programmatic ODT elements
        +
addressable native ODT structures
        ↓
editable ODT output
```

## Continue exploring

For Sample 21, read [Building Complex Documents](building-complex-documents.md).

For Sample 25, read [Named Sections](../rich-documents/named-sections.md) and the [Practical ODT template authoring guide](../getting-started/template-authoring-guide.md).

For the complete numbered example map, see the [Sample Guide](sample-guide.md).

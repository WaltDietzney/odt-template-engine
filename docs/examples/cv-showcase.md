# Editable CV Showcase

The repository uses the professional S01b CV and the focused C04 capability sample to demonstrate complementary ownership choices.

**S01b** is the canonical professional CV showcase. It deliberately combines Writer-owned page design and repeatable native Sections with bounded PHP-owned RichText regions.

**C04** is the canonical capability sample for declarative Writer-authored nested Section collections with template-shaped data.

Both produce editable ODT output. The important comparison is **who owns each dynamic structure**.

These CV examples are also architectural teaching material. S01b is the
reference for a professionally styled PHP-owned region: it uses document-local
paragraph styles, deliberate spacing and line-height, native lists, images,
and skill ratings. C04 is the reference for keeping repeatable visual
structure in LibreOffice and preserving it while native Sections are
instantiated. A professional showcase should reuse those proven patterns when
their ownership boundary applies instead of reducing them to minimally styled
content.

The canonical professional showcase is [S01b — Professional CV · Structured
Template](../../samples/sample_S01b_cv_structured.php). It combines the two
boundaries deliberately: the prepared template owns the page design, native
Sections, and image-frame placement, while PHP expands the Section collections
and supplies bounded RichText sidebar regions.

## S01b — Professional mixed-ownership template

S01b combines:

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

## C04 — Declarative Writer-owned collections

C04 keeps more structure in the ODT template itself. Scalar values still use ordinary assignment, while repeatable entries are native named sections authored in LibreOffice.

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
| PHP owns a dynamic region's internal composition | S01b / `RichText` + `setElement()` |
| LibreOffice owns a repeatable semantic block | C04 / named sections + `instantiateMany()` |
| only scalar values or lightweight logic change | normal template expressions |

S01b is the structured-template variant of this comparison. It deliberately
composes multiple mechanisms: C04-style native Sections for repeatable
main-column structures, bounded bookmarks for the Writer-authored
profile/extract text, named-image replacement for template-owned image
geometry, and S01b-style RichText inserted into template-authored Writer Frames.
The sidebar paragraph styles and tab-stop geometry are owned by the template. This mixed approach is intentional: the correct
ownership mechanism is chosen per document region rather than imposed on the
whole file.

S01b is also expected to be readable as an architectural example by coding
agents. Its implementation should make these ownership decisions obvious and
should demonstrate the strongest appropriate existing engine capabilities,
not merely generate a structurally valid ODT.

S01b's main CV is not rebuilt as one large RichText block. Its `Experience`, `Education`, and
`AdditionalQualifications` areas remain authored native Sections, while the
sidebar is an intentional PHP-owned dynamic region. Empty collection areas are
removed through the existing public `instantiateMany([])` behavior, and Writer
continues to determine physical pagination.

A practical lesson from S01b is that visually similar LibreOffice containers
are not interchangeable. The original text-box approach did not provide the
ordinary named-paragraph-style behavior required by the generated sidebar.
Writer Frames did. The final template therefore owns the first-page and
continuation Frame geometry plus `S01bSidebar*` paragraph styles. PHP builds
the sidebar structure and references those styles without duplicating their
typography as direct formatting. Skill ratings use `Paragraph::addTab()` and
a 5.2 cm tab stop defined by the template style rather than spaces.

A normal table was also rejected for this sidebar design: tables participate
in document flow and do not provide the page-positioned geometry needed beside
the independently indented main Section. A header table remains constrained by
header flow. This is a design-specific ownership lesson, not a general rule
against tables.

The models can also coexist in one document. A named section may contain ordinary placeholders, and PHP-generated elements can still be used where application-owned structure is appropriate.

## Why both matter

S01b is an important professional benchmark for combining Writer-owned structure with bounded structured PHP content: paragraphs, lists, images, styles, native Sections, bookmarks, and template-owned frame geometry.

C04 is the focused benchmark for declarative Writer-native collections: semantic Section identities, nested ownership, collection execution, and preservation of LibreOffice-authored structure.

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

For S01b, read [Building Complex Documents](building-complex-documents.md).

For C04, read [Named Sections](../rich-documents/named-sections.md) and the [Practical ODT template authoring guide](../getting-started/template-authoring-guide.md).

For the complete canonical example map, see the [Sample Guide](sample-guide.md).

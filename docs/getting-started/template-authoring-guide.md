# Practical ODT template authoring guide

This guide explains how to design LibreOffice-authored templates that remain
predictable when processed by the ODT Template Engine. It is intended for PHP
developers, LibreOffice template authors, and AI coding agents.

The central rule is simple:

> LibreOffice is the visual template designer. PHP supplies data and performs
> bounded document operations on the native ODT structure.

Prefer visual layout in LibreOffice, semantic structure in ODT, and data
binding/manipulation in PHP. Do not recreate LibreOffice's layout system in
application code unless the content is genuinely application-generated.

## Fixed, variable, and repeatable content

Classify each part of a template before authoring it:

| Category | Examples | Authoring approach |
| --- | --- | --- |
| Fixed | headings, labels, decorative lines, icons | author directly in LibreOffice |
| Variable | name, profession, phone, e-mail, address | use a scalar expression |
| Repeatable/structural | jobs, activities, education, projects | use a named native section |

This distinction prevents two common mistakes: putting dynamic structure into a
fixed frame, and trying to make PHP infer repeatable ownership from visual
proximity.

## Scalar variables

Use the existing template syntax and assign values with the public `assign()`
API:

```text
{{firstname}} {{lastname}}
{{profession}}
{{phone}}
```

```php
$template->assign([
    'firstname' => 'Max',
    'lastname' => 'Mustermann',
    'profession' => 'Senior Projektmanager',
    'phone' => '+49 151 12345678',
]);

$template->render();
```

Filters such as `{{upper:name}}`, `{{date:start|d.m.Y}}`, and
`{{number:amount|2}}` remain TemplateProcessor concerns. Keep presentation in
LibreOffice styles rather than encoding presentation rules in placeholder
names.

### Placeholder integrity and whitespace

Keep an expression logically intact. LibreOffice may represent one expression
as several `text:span` fragments; the engine can recognize and preserve bounded
fragmentation, but deliberately styling individual characters makes authoring
ambiguous.

Whitespace and punctuation are literal content. A span boundary does not imply
a space:

```text
{{firstname}}{{lastname}}
{{firstname}} {{lastname}}
{{firstname}} · {{lastname}}
```

These three forms intentionally render differently. An ODF `text:s` is also
content. The engine preserves authored separators and does not invent spaces for
readability. Use one consistent style for a complete expression wherever
possible.

### Style variables in LibreOffice

Select the complete placeholder, its surrounding text, or its paragraph and
apply the desired LibreOffice style. For example, use a name style around:

```text
{{firstname}} {{lastname}}
```

Do not expect the placeholder syntax to control font size, color, or weight.
The engine preserves supported native spans and styles during replacement; it
does not provide a general effective-style inference system.

## Design for realistic lengths

A short placeholder is not a useful width test. Check templates with realistic
boundary values before calling them production-ready:

- long German and international names;
- long job titles and company names;
- international phone numbers;
- long e-mail addresses;
- postal addresses;
- long activity descriptions;
- six or more employment entries;
- five or more activities per employment;
- zero activities and zero employment entries.

Sample 25 demonstrated that a phone value can fit during authoring while
`+49 151 12345678` exceeds a narrow text box. The engine does not automatically
resize arbitrary frames. Give bounded fields enough width, allow wrapping when
appropriate, or use normal text flow for content whose length is not bounded.

Fixed-height sidebars have the same limitation: growing skills, languages, or
qualifications can overflow them. A visually attractive fixed area should own
bounded content; unbounded content belongs in normal flow.

## Normal text flow versus frames

Use normal paragraphs, lists, and tables for content that grows vertically:

- experience history and activities;
- education and projects;
- descriptions and other variable-length text;
- repeatable collections.

Use frames or custom shapes for bounded visual components:

- portraits;
- decorative headers and lines;
- icons;
- bounded contact fields;
- visual accents.

Normal flow naturally pushes following content when a collection grows. Fixed
positioning does not. Frame behavior depends on anchor, dimensions, position,
wrapping, and surrounding flow; exact frame-layout semantics remain a separate
FRAME-LAYOUT work area. Do not assume changing an image anchor will make
unrelated absolute-positioned shapes move.

### Word-converted templates

DOCX templates converted to ODT may contain `draw:custom-shape`, absolute
positions, and imported drawing structures. Such templates can remain visually
useful, but they are often poor containers for dynamic flowing content. Inspect
the converted ODT and move repeatable or unbounded content into native text
flow where possible. See the [Word-compatible template findings](../product/FRAME-LAYOUT-01_WORD_COMPATIBLE_TEMPLATE_AUTHORING.md).

## Native named sections

Use a LibreOffice named `text:section` for a repeatable semantic unit. Choose
stable domain names such as `ExperienceEntry`, `ActivityEntry`, and
`EducationEntry`, not arbitrary names such as `Section1`.

For example:

```text
ExperienceEntry
├── {{note}}
├── {{position}}
└── ActivityEntry
    └── {{activity}}
```

The section supplies native ODT structure, addressability, cloning, identity
rewriting, data binding, and collection lifecycle. The native section is the
template object; the caller should not need to know generated suffixes.

### Ownership is structural

Everything that should repeat must be inside the repeatable section. The engine
must not infer ownership from nearby bullets, frames, or visual alignment.

Bad for fully dynamic activities:

```text
ExperienceEntry
├── ActivityEntry
├── static activity bullet
└── static activity bullet
```

Good:

```text
ExperienceEntry
└── ActivityEntry
    └── one complete repeatable list item
```

Static siblings remain static. This rule was demonstrated by Sample 25: the
collection engine cannot safely guess that visually related sibling content is
part of a collection.

### Nested collections

Nested sections are resolved relative to their containing instance. Expand
them explicitly:

```php
$experiences = $template
    ->section('ExperienceEntry')
    ->instantiateMany($jobs);

foreach ($experiences as $index => $experience) {
    $experience
        ->section('ActivityEntry')
        ->instantiateMany($activities[$index]);
}
```

Each generated experience owns an independent local `ActivityEntry` prototype
and clone family. Do not use generated names such as `ActivityEntry_1_2` in
caller data. Declarative recursive mapping is not part of the current API.

### Zero-item behavior and headings

`instantiateMany([])` removes the selected prototype and creates no instances.
Put only content inside a section that should disappear when its collection is
empty.

If a heading must remain with zero entries, keep it outside the repeatable
section:

```text
BERUFSERFAHRUNG          fixed content
[ExperienceEntry]        zero items removes only this section
```

The same rule applies to an empty activity collection: the complete repeatable
list item should be inside `ActivityEntry`, so the placeholder bullet is
removed while the outer experience remains.

## Bookmarks

Bookmarks are native named ranges or positions. They are useful for targeted
operations and inspection, but they are not a substitute for a repeatable
section.

Use unique, semantic names and keep paired start/end markers well formed.
Cloning rewrites bookmark identities deterministically; expression binding does
not guess that a data key should mutate a bookmark. Explicit bookmark
replacement and template-expression replacement are separate operations.

LibreOffice can place bookmark markers between fragments of one expression.
The engine preserves marker topology where it cannot prove that moving markers
would preserve the intended range. Avoid overlapping bookmark and placeholder
boundaries unless that topology is deliberate and tested.

## Tables and lists

Use native LibreOffice tables for stable tabular layout, aligned fields, and
bounded columns. The engine also supports programmatically generated
`RichTable` structures when the table itself is application data. Do not claim
row-cloning or named-table collection APIs that are not present in the current
public model.

Use native ODT lists for activities and other repeatable bullets. Put the full
repeatable list item inside its named section. Avoid simulating list layout with
spaces or manually typed bullet characters when the list should grow.

See [Lists](../rich-documents/lists.md) and
[Tables](../rich-documents/tables.md) for element-level guidance.

## Images and semantic elements

Choose who owns the image position:

- if LibreOffice already contains the intended position, use template-level
  image replacement;
- if PHP creates the surrounding rich content, use `ImageElement` or the
  existing semantic image APIs.

`ImageElement` supports ODT-oriented size, anchor, wrapping, and alignment
options. When only one dimension is supplied, the current implementation can
derive the other from the source aspect ratio. Start with simple dimensions
and `as-char` or paragraph-oriented placement for portable flowing content.

When generating structure programmatically, prefer an engine-provided semantic
element such as `CircularImageElement` over manually reproducing arbitrary
OOXML-derived custom-shape XML. This makes intent clearer and testing easier,
while not promising Word round-trip fidelity.

Image geometry still interacts with frame layout, anchors, and wrapping. Test
the result in LibreOffice when exact placement matters; an `as-char` image
participates in text flow more naturally but does not repair an otherwise fixed
layout.

## Structure safety

Valid ODF is not automatically an ideal engine template. The engine
distinguishes structures that are:

- valid and safe as authored;
- logically recognizable but only safely repairable in bounded cases;
- unsafe or ambiguous to normalize automatically.

Do not flatten all spans or hand-edit XML to make a placeholder look simple.
Same-style fragmentation can be projected and safely canonicalized in bounded
cases. Different-style fragmentation, hard text-flow boundaries, malformed
expressions, and ambiguous bookmark topology require caution. The structure-
inspection and preservation work documents these rules in detail:

- [Template structure semantics](../product/TEMPLATE-STRUCTURE-01A_VALIDATION_NORMALIZATION_SEMANTICS.md)
- [Non-mutating projection](../product/TEMPLATE-STRUCTURE-01B_NON_MUTATING_VALIDATION_PROJECTION.md)
- [Canonical normalization](../product/TEMPLATE-STRUCTURE-01C_CANONICAL_TEMPLATE_NORMALIZATION.md)
- [Structure-preserving replacement](../product/TEMPLATE-STRUCTURE-01D_STRUCTURE_PRESERVING_EXPRESSION_REPLACEMENT.md)
- [Adjacent-expression whitespace](../product/TEMPLATE-STRUCTURE-01E_ADJACENT_EXPRESSION_WHITESPACE_PRESERVATION.md)

## Stress-test before production

Treat template quality as a progression:

1. **Layout prototype** — looks correct with placeholders.
2. **Data-tested template** — tested with realistic lengths, zero items, and
   several collection sizes.
3. **Production template** — tested through save/reopen and LibreOffice
   rendering, with the relevant export or round-trip requirements understood.

The executable samples are useful reference implementations. Sample 25 is the
complete CV benchmark combining scalar values, finalized `ExperienceEntry`
collections, nested `ActivityEntry` collections, bookmarks, lists, frames, and
preserved native structure.

## Guidance for AI coding agents

Before changing a template or its rendering code:

1. inspect the actual ODT structure and logical expressions;
2. identify named sections, bookmarks, frames, tables, lists, and styles;
3. determine structural ownership rather than inferring it from visual
   proximity;
4. classify content as fixed, variable, or repeatable;
5. preserve native nodes, styles, marker order, and authored whitespace;
6. use public engine APIs instead of direct XML mutation where possible;
7. test realistic maximum-length and zero-item data;
8. validate the ODT package/XML and inspect the result in LibreOffice.

Do not introduce convenience APIs, declarative mappings, or layout redesigns
just to make one template easier to author. Record a bounded limitation when
the existing native structure is ambiguous.

## Final checklist

- [ ] Placeholder expressions are logically intact.
- [ ] Whitespace and punctuation are intentional.
- [ ] Repeatable content is fully inside named sections.
- [ ] Headings are intentionally inside or outside collections.
- [ ] Zero-item behavior has been tested.
- [ ] Long names, contacts, titles, and descriptions have been tested.
- [ ] Fixed-width frames have enough room for realistic values.
- [ ] Unbounded vertical content uses normal text flow.
- [ ] Images have intentional size, anchor, and wrapping behavior.
- [ ] No unresolved expressions remain after the showcase render.
- [ ] The ODT reopens successfully in LibreOffice.
- [ ] The final visual output has been reviewed in the target office suite.

## Related documentation

- [Creating Templates](creating-templates.md)
- [Variables & Filters](../template-language/variables-and-filters.md)
- [Conditions & Loops](../template-language/conditions-and-loops.md)
- [Editable CV Showcase](../examples/cv-showcase.md)
- [SECTION-03 final review](../product/SECTION-03_FINAL_REVIEW.md)
- [Images](../rich-documents/images.md)

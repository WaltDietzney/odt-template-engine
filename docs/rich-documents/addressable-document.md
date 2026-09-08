# Addressable Native ODT Structures

The ODT Template Engine can work with native named structures that already exist in a LibreOffice-authored document. This complements placeholder processing and programmatically generated `OdtElement` content.

The current public model covers four native target types:

- named sections;
- bookmarks and bookmark ranges;
- named tables;
- named drawing frames.

This makes it possible to use LibreOffice not only as a visual template editor, but also as the authoring environment for semantic document structures that PHP can inspect and address later.

## Inspect the current document

Use `inspect()` when you want a read-only snapshot of the named native structures in the current document state:

```php
$inspection = $template->inspect();

foreach ($inspection->sections() as $section) {
    echo $section->name() . PHP_EOL;
}

foreach ($inspection->bookmarks() as $bookmark) {
    echo $bookmark->name() . PHP_EOL;
}
```

`DocumentInspection` exposes:

```php
$inspection->sections();
$inspection->bookmarks();
$inspection->tables();
$inspection->frames();
$inspection->diagnostics();
```

You can also look up the first descriptor with a given name:

```php
$section = $inspection->section('ExperienceEntry');
$table = $inspection->table('SkillsTable');
```

Inspection is read-only. It returns descriptors and diagnostics rather than exposing internal DOM nodes.

## Resolve a live target

When application code intends to work with a specific native structure, resolve a typed target from `OdtTemplate`:

```php
$section = $template->section('ExperienceEntry');
$bookmark = $template->bookmark('ApplicantName');
$table = $template->table('SkillsTable');
$frame = $template->frame('ProfilePhoto');
```

These calls are deliberately different from inspection. Inspection describes the document; target resolution gives application code a typed handle for the supported operations of that structure.

Resolution is strict. Do not use these APIs as nullable probes for structures that may or may not exist. Missing or ambiguous targets fail explicitly through the target-resolution exceptions.

## Bookmarks

A bookmark or bookmark range is useful when LibreOffice should own the surrounding paragraph and formatting while PHP replaces only a bounded text region.

```php
$template
    ->bookmark('ApplicantName')
    ->replaceText('Max Mustermann');
```

`replaceText()` preserves the bookmark markers and changes the bounded textual content. Bookmark replacement is an explicit native-target operation; it is not the same mechanism as replacing a `{{placeholder}}` expression.

Use bookmarks when the template author wants a stable named text location without turning the whole surrounding structure into generated PHP content.

## Named sections

Named sections are the richest addressable structure currently exposed by the public API. A section can be resolved, cloned, instantiated with data, expanded as a collection, and used as an owner scope for nested sections.

```php
$entries = $template
    ->section('ExperienceEntry')
    ->instantiateMany($experienceData);
```

See [Named Sections](named-sections.md) for the complete lifecycle, binding, nesting, cloning, and collection semantics.

## Named tables and frames

Named tables and drawing frames can currently be resolved as typed targets:

```php
$table = $template->table('SkillsTable');
$frame = $template->frame('ProfilePhoto');

$tableDescriptor = $table->descriptor();
$frameDescriptor = $frame->descriptor();
```

The current `TableTarget` and `FrameTarget` APIs are intentionally read-only beyond target resolution and descriptor access. Do not infer mutation methods merely because sections and bookmarks already expose bounded mutation operations.

This distinction is important: the addressable document model can grow target by target without pretending that every native ODT structure supports the same operations.

## Three complementary authoring models

The engine now supports three complementary ways to make a document dynamic:

```text
1. Template expressions
   {{name}}, filters, conditions, foreach

2. Generated ODT elements
   RichText, Paragraph, RichTable, ListElement, ImageElement

3. Addressable native ODT structures
   inspect(), bookmark(), section(), table(), frame()
```

Choose according to who should own the structure.

- Use template expressions when LibreOffice owns the structure and PHP supplies simple values or lightweight logic.
- Use generated elements when PHP genuinely owns a dynamic content subtree.
- Use addressable native structures when LibreOffice should remain the visual/structural author but PHP needs stable semantic handles for inspection or bounded operations.

These models can be combined in one document.

## Authoring guidance

Stable semantic names are part of the template contract. Prefer names such as `ExperienceEntry`, `ApplicantName`, `SkillsTable`, or `ProfilePhoto` over editor-generated names such as `Section1` or `Frame3` when application code will address them.

For practical LibreOffice authoring rules, nested section ownership, dynamic lengths, and structure-preservation guidance, see the [Template Authoring Guide](../getting-started/template-authoring-guide.md).

## Related samples

- Sample 22 — bookmark text replacement;
- Sample 23 — named section content replacement;
- Sample 24 — image replacement inside a named section;
- Sample 25 — complete CV showcase using section instantiation and nested collections.

Continue with [Named Sections](named-sections.md) for the most capable current target API and the [Sample Guide](../examples/sample-guide.md) for the executable examples.
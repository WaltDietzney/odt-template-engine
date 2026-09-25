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

`TableTarget` and `FrameTarget` deliberately have different 1.0 operation contracts. A Writer-authored table can be populated through `TableTarget::populate()` while preserving the authored table as the structural source model. `FrameTarget` remains identity/inspection-only and does **not** expose an imperative `replaceImage()` method.

A bounded named-frame image replacement does exist in the optional Mapping & Automation workflow, where an inspected native-object action is validated by Concrete Preflight before `automate()` mutates the Working Document. Do not infer that operation onto `FrameTarget` itself.

This distinction is important: Writer-native objects expose only the operations characterized for that object type rather than one generic mutation interface.

## Three complementary authoring models

The engine now supports three complementary ways to make a document dynamic:

```text
1. Simple Template Processing
   {{name}}, filters, conditions, foreach

2. Structured ODT Construction
   RichText, Paragraph, RichTable, ListElement, ImageElement

3. Writer-native Document Model
   inspect(), bookmark(), section(), table(), frame(), Writer User Fields
```

Choose according to who should own the structure.

- Use **Simple Template Processing** when LibreOffice owns the structure and PHP supplies simple values or lightweight logic through visible expressions.
- Use **Structured ODT Construction** when PHP genuinely owns a dynamic content subtree.
- Use the **Writer-native Document Model** when LibreOffice should remain the visual/structural author but PHP needs stable semantic handles for inspection or bounded operations.

These models can be combined in one document.

## Authoring guidance

Stable semantic names are part of the template contract. Prefer names such as `ExperienceEntry`, `ApplicantName`, `SkillsTable`, or `ProfilePhoto` over editor-generated names such as `Section1` or `Frame3` when application code will address them.

For practical LibreOffice authoring rules, nested section ownership, dynamic lengths, and structure-preservation guidance, see the [Template Authoring Guide](../getting-started/template-authoring-guide.md).

## Related samples

- [L09 Native Objects](../../samples/sample_L09_native_objects.php) — bookmark text replacement, Section content replacement, and descriptor-only named table/frame access;
- [L10 Writer User Fields](../../samples/sample_L10_writer_user_fields.php) — document-global string User Field binding;
- [L12 Writer Table Population](../../samples/sample_L12_writer_table_population.php) — bounded population of a Writer-authored named table;
- [C04 Declarative Structured Collections](../../samples/sample_C04_declarative_structured_collections.php) — declarative nested Writer-owned collections.

Continue with [Named Sections](named-sections.md) for the most capable current target API and the [Sample Guide](../examples/sample-guide.md) for the executable examples.

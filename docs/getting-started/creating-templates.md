# Creating Templates

ODT templates are normal OpenDocument Text files. Create them in LibreOffice Writer or another ODT-compatible editor, place dynamic markers where content should be inserted, and let the template own the stable visual structure of the document.

A useful design rule is:

> **Use LibreOffice for durable document design and native template structure; use PHP for data and genuinely application-owned structure.**

## Keep placeholders intact

Use complete placeholders such as:

```text
{{customer_name}}
```

Avoid applying different formatting to individual characters inside a placeholder. Office editors can represent visually continuous text as several XML spans, which makes unnecessarily fragmented placeholders harder to process reliably.

Apply formatting to the complete placeholder, the surrounding paragraph, or the table cell instead.

## Use clear template markers

Keep template expressions easy to identify and maintain:

```text
Customer: {{customer_name}}
```

```text
{{#if:is_vip}}
VIP Customer
{{#endif}}
```

```text
{{#foreach:items}}
{{name}} — {{price}}
{{#endforeach}}
```

For control structures, keep the opening and closing markers in clear paragraphs and avoid unnecessary nested formatting around them.

## Prefer native structures for stable document design

For columns and aligned dynamic data, LibreOffice tables are generally more robust than manually arranging content with spaces or repeated tab characters.

This is particularly useful for:

- invoices and price rows;
- address blocks;
- multi-column document areas;
- stable regions that will later receive generated content;
- repeatable semantic blocks that should remain visually authored in LibreOffice.

The engine also supports programmatically generated `RichTable` structures when PHP genuinely owns the table structure.

For repeatable semantic document blocks such as experience entries, activities, education entries, or projects, a named LibreOffice section can act as a native template prototype. PHP can then address and instantiate that structure without reconstructing it from scratch.

## Decide who owns each dynamic structure

The current engine supports three complementary choices.

**Template expressions** are appropriate when LibreOffice owns the surrounding structure and PHP supplies scalar values or lightweight logic.

**Programmatic ODT elements** are appropriate when PHP owns the internal composition of a dynamic subtree, for example a generated `RichText`, `ListElement`, `RichTable`, or `ImageElement` region.

**Addressable native ODT structures** are appropriate when LibreOffice should own a named section, bookmark, table, or frame and PHP needs a stable semantic handle. Named sections can be cloned or instantiated as repeatable native structures.

The important question is therefore not simply whether content is dynamic, but **who should own its structure**.

A placeholder such as `{{content}}` can act as an insertion point for a complete generated ODT element. A named section such as `ExperienceEntry` can instead keep the repeatable native structure in LibreOffice while PHP binds and expands it.

See [Addressable Native ODT Structures](../rich-documents/addressable-document.md) and [Named Sections](../rich-documents/named-sections.md) for the structured-template model.

## Keep control structures simple

Conditions and loops are deliberately lightweight. Prefer several understandable blocks over deeply nested template logic.

For example:

```text
{{#if:is_vip}}
Priority support enabled
{{#else}}
Standard support
{{#endif}}
```

For more complex application decisions, calculate the required data in PHP first and keep the ODT template focused on document presentation and native structure.

## Test templates with realistic data

Office editors may rewrite XML structure when a file is saved. After significant template or layout changes:

1. generate the document with representative data;
2. open the result in LibreOffice or another target editor;
3. check layout, page breaks, lists, images, tables, and repeated native sections;
4. verify that the result remains editable.

The repository's `samples/` directory is both executable documentation and a collection of tested template patterns. The public Sample Explorer can generate the same representative documents interactively.

## Template anatomy

Internally, an `.odt` document is a ZIP package. Important members include:

- `content.xml` for document content and native structures such as sections, bookmarks, tables, and frames;
- `styles.xml` for document, text, paragraph, and page styles;
- `meta.xml` for metadata;
- `META-INF/manifest.xml` for package declarations;
- `Pictures/` for embedded images when present.

You normally do not need to edit these files manually. Knowing where information lives is useful when diagnosing advanced layout or interoperability issues.

## Next steps

- [Template Authoring Guide](template-authoring-guide.md)
- [Variables & Filters](../template-language/variables-and-filters.md)
- [Conditions & Loops](../template-language/conditions-and-loops.md)
- [Addressable Native ODT Structures](../rich-documents/addressable-document.md)
- [Named Sections](../rich-documents/named-sections.md)
- [How the Engine Works](../concepts/how-it-works.md)

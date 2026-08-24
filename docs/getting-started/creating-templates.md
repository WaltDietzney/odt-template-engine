# Creating Templates

ODT templates are normal OpenDocument Text files. Create them in LibreOffice Writer or another ODT-compatible editor, place dynamic markers where content should be inserted, and let the template own the stable visual structure of the document.

A useful design rule is:

> **Use LibreOffice for stable document design and PHP for dynamic content.**

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

## Prefer tables for stable structured layouts

For columns and aligned dynamic data, LibreOffice tables are generally more robust than manually arranging content with spaces or repeated tab characters.

This is particularly useful for:

- invoices and price rows;
- address blocks;
- repeated records;
- multi-column document areas;
- stable regions that will later receive generated content.

The engine also supports programmatically generated `RichTable` structures when the table itself is dynamic.

## Decide what belongs to the template

The ODT template is a good place for structures that are stable across generated documents:

- page design;
- static headings and explanatory text;
- recurring table or column structures;
- company letterhead or branding;
- placeholders that mark dynamic regions.

PHP is a better place for structures that depend strongly on application data:

- a variable number of paragraphs;
- dynamic lists;
- generated tables;
- optional complex sections;
- dynamically selected images;
- application-driven rich text.

A placeholder such as `{{content}}` can therefore act as an insertion point for a complete `RichText`, `ListElement`, `RichTable`, or other ODT element rather than only a string.

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

For more complex application decisions, calculate the required data in PHP first and keep the ODT template focused on document presentation.

## Test templates with realistic data

Office editors may rewrite XML structure when a file is saved. After significant template or layout changes:

1. generate the document with representative data;
2. open the result in LibreOffice or another target editor;
3. check layout, page breaks, lists, images, and tables;
4. verify that the result remains editable.

The repository's `samples/` directory is both executable documentation and a collection of tested template patterns. The public Sample Explorer can generate the same representative documents interactively.

## Template anatomy

Internally, an `.odt` document is a ZIP package. Important members include:

- `content.xml` for document content;
- `styles.xml` for document, text, paragraph, and page styles;
- `meta.xml` for metadata;
- `META-INF/manifest.xml` for package declarations;
- `Pictures/` for embedded images when present.

You normally do not need to edit these files manually. Knowing where information lives is useful when diagnosing advanced layout or interoperability issues.

## Next steps

- [Variables & Filters](../template-language/variables-and-filters.md)
- [Conditions & Loops](../template-language/conditions-and-loops.md)
- [How the Engine Works](../concepts/how-it-works.md)

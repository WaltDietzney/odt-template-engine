# Creating Templates

ODT templates are normal OpenDocument Text files. Create them in LibreOffice Writer or another ODT-compatible editor, place dynamic markers where content should be inserted, and keep the surrounding document structure as simple and predictable as possible.

## Keep placeholders intact

Use complete placeholders such as:

```text
{{customer_name}}
```

Avoid applying different formatting to individual characters inside a placeholder. Office editors can represent visually continuous text as several XML spans, which makes unnecessarily fragmented placeholders harder to process reliably.

Apply formatting around the complete placeholder or to the surrounding paragraph or table cell instead.

## Prefer tables for structured layouts

For columns and aligned dynamic data, tables are generally more robust than manual tab characters.

This is particularly useful for:

- invoices and price rows;
- address blocks;
- repeated records;
- multi-column document areas.

The engine also supports programmatically generated `RichTable` structures when the table itself is dynamic.

## Keep control structures simple

Place conditions and loops in clear template structures:

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

Avoid inserting unnecessary manual line breaks or heavily nested formatting inside control markers.

## Use PHP elements for complex generated content

A placeholder does not have to represent plain text. Complex content can be constructed with classes such as `RichText`, `Paragraph`, `ListElement`, `ImageElement`, and `RichTable`, then injected with `setElement()`.

This lets the ODT template own the stable layout while PHP owns dynamic structures.

## Test templates with realistic data

Office editors may rewrite the XML structure when a file is saved. After significant layout changes, generate a document with realistic sample data and open the result in LibreOffice or another target editor.

The repository's `samples/` directory is useful both as executable documentation and as a source of tested template patterns.

## Template anatomy

Internally, an `.odt` document is a ZIP package. The engine works with package files including:

- `content.xml` for document content;
- `styles.xml` for document and page styles;
- `meta.xml` for metadata.

You normally do not need to edit these files manually. Knowing where the information lives is useful when diagnosing advanced layout or interoperability issues.

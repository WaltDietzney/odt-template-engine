# Writer-native Objects

Writer-native APIs address structure that was authored in LibreOffice Writer and remains owned by the template.

Use this model when Writer should control the document structure and layout while PHP discovers, reads, populates, or mutates specifically supported named objects.

This differs from [Structured Content](../structured-content/index.md), where PHP constructs the paragraphs, lists, tables, images, frames, or other ODT elements it inserts.

## Ownership rule

A practical question usually decides between the two APIs:

> Who owns this structure — Writer or PHP?

For example:

- a table designed in Writer and populated with data uses a Writer-native `TableTarget`;
- a table constructed entirely by PHP uses `RichTable`;
- a named Writer Section can be addressed through `SectionTarget`;
- a PHP-built dynamic region is inserted through `setElement()`.

Both ownership models can be combined in one document.

## Reference

- [Inspection](inspection.md) — current Working Document inspection, semantic source-template inspection, and advanced template-structure diagnostics.
- [Bookmarks](bookmarks.md) — strict named bookmark targeting and bounded inline text replacement.
- [Sections](sections.md) — Writer-owned structured containers, replacement, cloning, and bounded instantiation.
- Tables — follows in F2.4.3.
- Frames — follows in F2.4.4.
- User Fields — follows in F2.4.4.

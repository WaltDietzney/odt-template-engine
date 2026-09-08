# ODT Internals

An `.odt` file is a ZIP package containing XML documents, styles, metadata, images, and a manifest. Understanding the main package parts is extremely useful when debugging advanced template behavior.

You do not need to edit these files manually for normal engine usage. This chapter explains what the engine is manipulating behind the public API.

## Package structure

A typical ODT package contains entries similar to:

```text
document.odt
├── mimetype
├── content.xml
├── styles.xml
├── meta.xml
├── settings.xml
├── Pictures/
│   └── image.png
└── META-INF/
    └── manifest.xml
```

The exact package may contain additional files created by LibreOffice or other ODF producers.

## content.xml

`content.xml` contains the main document body and many document-local structures.

Typical elements include:

```xml
<text:p>...</text:p>
<text:span>...</text:span>
<text:section>...</text:section>
<text:bookmark-start>...</text:bookmark-start>
<text:list>...</text:list>
<table:table>...</table:table>
<draw:frame>...</draw:frame>
```

Normal body placeholders such as `{{name}}` are therefore usually found here. Native named sections, bookmarks, tables, and drawing frames are also structures that the engine can inspect and address through public typed APIs.

Generated `Paragraph`, `ListElement`, `RichTable`, and image/frame structures are serialized into this document when inserted as structured content.

## styles.xml

`styles.xml` contains shared document styles, automatic styles, page-layout definitions, master pages, and content used by headers or footers.

A placeholder can occur outside the normal body, so the engine processes both `content.xml` and `styles.xml` for relevant template-language operations. Generated style requirements and page-layout changes must also be materialized into the ODF structures appropriate to their family and document part.

`PageLayoutOdtTemplate`, for example, resolves a `style:master-page`, follows its `style:page-layout-name`, and changes `style:page-layout-properties`.

## meta.xml

`meta.xml` contains document metadata such as title, author, language, dates, generator information, and editing metadata.

The public `setMeta()` and `getMeta()` methods provide the supported abstraction for these values.

See [Metadata](metadata.md) for the current field mapping.

## Pictures/ and the manifest

Images embedded by the engine are copied into the ODT package's `Pictures/` directory. The XML then references the package asset, conceptually:

```xml
<draw:image xlink:href="Pictures/photo.png" />
```

Adding the file alone is not sufficient. `META-INF/manifest.xml` declares package entries and their media types, so the resource path also updates the manifest.

## The mimetype entry

ODF packages have a special `mimetype` entry. When saving, the engine creates the ZIP package with `mimetype` first and stores it without compression. The remaining working-directory files are then added to the archive.

This is one reason the engine does not treat an ODT document as an arbitrary ZIP file containing XML.

## The current engine architecture

The package is the physical document, but most higher-level behavior is document-local and semantic.

At a high level:

```text
ODT template
    ↓
OdtPackage
    │  owns extracted package files and physical resources
    ↓
OdtDocumentContext
    │  owns the current content/styles DOMs and document-local services
    ├── template processing
    ├── style context and semantic requirements
    ├── structured-element materialization
    ├── document inspection / typed target resolution
    └── bounded native document mutations
    ↓
serialize XML + resources + manifest
    ↓
rebuild ODT package
```

`OdtPackage` represents physical package concerns. `OdtDocumentContext` represents the current logical document state and provides the boundary for document-local semantic dependencies. Public APIs such as template assignment, structured insertion, styles, inspection, and typed targets operate through that document rather than through process-global document state.

## Structured elements and semantic requirements

Programmatic elements such as `Paragraph`, `RichText`, `ListElement`, `RichTable`, and `ImageElement` describe native ODT content. Their required styles and resources are collected before the element is materialized into the current document.

The style path is conceptually:

```text
authoring options / structured element
        ↓
StyleRequirement
        ↓
document-local StyleContext
        ↓
semantic materializer
        ↓
ODF style definition/reference
```

`StyleMapper` is a stateless mapping and identity utility. `StyleWriter` is a narrow serialization helper. Neither is the owner of the document's style membership. See [Style Model](../styling/style-model.md).

## Addressable native structures

The engine can also work with structure that already exists in the LibreOffice-authored document.

```php
$inspection = $template->inspect();
$bookmark = $template->bookmark('FullName');
$section = $template->section('ExperienceEntry');
$table = $template->table('SkillsTable');
$frame = $template->frame('ProfilePhoto');
```

Inspection produces descriptors of native named structures. Typed targets resolve semantic names against the current document state and expose only the operations supported for that target type.

This is intentionally different from application code reaching into `DOMDocument` or writing XPath. The DOM remains an implementation detail while the public API addresses document objects by their native identities.

See [Addressable ODT Structures](../rich-documents/addressable-document.md) and [Named Sections](../rich-documents/named-sections.md).

## Why placeholders can break in LibreOffice

LibreOffice may split visually continuous text across several XML nodes or spans. A placeholder that looks like this in the editor:

```text
{{customer_name}}
```

may internally resemble:

```xml
<text:span>{{customer_</text:span>
<text:span>name}}</text:span>
```

The engine contains normalization logic for common placeholder fragmentation. Template authoring still matters: a placeholder should be entered as one logical token and should not intentionally contain mixed formatting.

For structured native replacements, the engine uses bounded operations designed to preserve surrounding authored structure rather than flattening the document into plain text.

## Why style ownership matters

ODF distinguishes text styles, paragraph styles, table-cell styles, table-column styles, table-row styles, graphic styles, page layouts, and other families.

A generated document can be valid XML while still being semantically wrong if a property is attached to the wrong family or document part. Friendly authoring options are therefore mapped into semantic style requirements owned by the current document and materialized according to their ODF role.

This is different from a process-global registry model: the current document context is the authority for generated semantic style requirements.

## Debugging an ODT package

For difficult problems, inspect the package rather than guessing from the LibreOffice screen alone:

```bash
mkdir /tmp/odt-debug
cd /tmp/odt-debug
unzip /path/to/output.odt
```

Then inspect the relevant XML:

```bash
xmllint --format content.xml | less
xmllint --format styles.xml | less
xmllint --format meta.xml | less
```

Useful questions are:

- Did the placeholder disappear from the expected XML file?
- Was the generated element inserted in the correct parent structure?
- Does the referenced style actually exist in the correct family/part?
- Is an image present in `Pictures/` and declared in the manifest?
- Does the master page reference the page layout you expected?
- Does `inspect()` report the native section/bookmark/table/frame you intended to address?
- Did a typed mutation preserve the surrounding native structure and identity?

## Verification strategy

For non-trivial ODT generation, use three levels of confidence:

```text
1. PHP tests
2. package / XML inspection
3. LibreOffice visual verification
```

ODF is rich enough that no single level catches every class of problem.

## Do not build application logic against private XML details

This chapter is for understanding and debugging the engine. Application code should use the public abstraction that matches its task:

- template assignment and expressions for scalar/lightweight logic;
- `RichText`, `Paragraph`, `ListElement`, `RichTable`, `ImageElement`, and `setElement()` for PHP-owned structure;
- `styles()` for document style authoring supported by the public facade;
- `inspect()`, `bookmark()`, `section()`, `table()`, and `frame()` for addressable native structures;
- `setMeta()` / `getMeta()` and page-layout APIs for document-level concerns.

Do not couple application code to internal DOM properties, private XPath expressions, materializers, or package-service internals. Those implementation boundaries may evolve while the public document semantics remain stable.

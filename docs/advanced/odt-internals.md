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
<text:list>...</text:list>
<table:table>...</table:table>
<draw:frame>...</draw:frame>
```

Normal body placeholders such as `{{name}}` are therefore usually found here.

Generated `Paragraph`, `ListElement`, `RichTable`, and image/frame structures are ultimately serialized into this document when they replace a body placeholder.

## styles.xml

`styles.xml` contains shared document styles, automatic styles, page-layout definitions, master pages, and content used by headers or footers.

This matters for two reasons.

First, a placeholder can occur outside the normal body. The engine therefore processes both `content.xml` and `styles.xml` for several template-language operations.

Second, generated styles and page layout changes must be written into the correct ODF style structures rather than represented as arbitrary XML attributes on body text.

`PageLayoutOdtTemplate`, for example, resolves a `style:master-page`, follows its `style:page-layout-name`, and changes `style:page-layout-properties`.

## meta.xml

`meta.xml` contains document metadata such as title, author, language, dates, generator information, and editing metadata.

The public `setMeta()` and `getMeta()` methods provide the supported abstraction for these values.

See [Metadata](metadata.md) for the current field mapping.

## Pictures/

Images embedded by the engine are copied into the ODT package's `Pictures/` directory.

The XML then references the package asset, for example conceptually:

```xml
<draw:image xlink:href="Pictures/photo.png" />
```

Adding the file alone is not sufficient: the package manifest must also know about the asset.

## META-INF/manifest.xml

The ODF manifest declares package entries and their media types.

When the engine embeds new images, the save pipeline updates the manifest so the generated package remains coherent for LibreOffice and other ODF consumers.

## The mimetype entry

ODF packages have a special `mimetype` entry. When saving, the engine creates the ZIP package with `mimetype` first and stores it without compression.

The remaining working-directory files are then added to the archive.

This is one of the reasons the engine does not simply treat an ODT document as an arbitrary ZIP file containing XML.

## The engine's working model

At a high level:

```text
ODT template
    ↓
unpack to temporary directory
    ↓
load content.xml / styles.xml / meta.xml as DOMDocument
    ↓
normalize and modify XML
    ↓
collect/write styles and image assets
    ↓
update manifest
    ↓
serialize XML
    ↓
rebuild ODT ZIP package
```

The constructor creates a unique temporary working directory, `load()` extracts the template and loads the core XML documents, and `save()` serializes the modified package.

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

The engine contains normalization logic to repair this common situation before placeholder processing.

This is also why template authoring rules matter: a placeholder should be entered as a single logical token and should not intentionally contain mixed formatting.

## Why style ownership matters

ODF distinguishes text styles, paragraph styles, table-cell styles, graphic styles, page layouts, and other style families.

A generated document can be valid XML while still being semantically wrong if a property is attached to the wrong style family. The engine's style mapper, splitter, and writer exist to translate higher-level PHP options into those ODF responsibilities.

See [Style Model](../styling/style-model.md) for the application-facing view.

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
- Does the referenced style actually exist?
- Is an image present in `Pictures/` and declared in the manifest?
- Does the master page reference the page layout you expected?

## Verification strategy

For non-trivial ODT generation, use three levels of confidence:

```text
1. PHP tests
2. package / XML inspection
3. LibreOffice visual verification
```

ODF is rich enough that no single level catches every class of problem.

## Do not build application logic against private XML details

This chapter is for understanding and debugging the engine. Application code should continue to use public APIs such as `setValues()`, `setElement()`, `RichText`, `RichTable`, `setMeta()`, and `PageLayoutOdtTemplate` rather than reaching into internal DOM properties.

The XML representation is an implementation concern and may evolve as the engine's style and layout architecture improves.

# ODF / LibreOffice Semantic Reference Matrix

## Status and purpose

This document defines the framework for an empirical reference study of native
ODF and LibreOffice serialization. It is a research reference, not an engine
implementation specification.

The study answers:

> How does LibreOffice serialize specific document semantics into native ODF,
> and what relationships exist between content, style definitions, resources,
> and package metadata?

The ODF specification is the normative source. LibreOffice-generated fixtures
are practical implementation evidence. Current ODT Template Engine output is
not the source of truth for this study. Fixtures must never be modified merely
to make current engine output match them. Observation and future architecture
decisions remain separate.

Reference fixtures describe observed LibreOffice/ODF semantics; they do not
describe what the current engine happens to generate.

This framework is intentionally created before the Phase 1 fixtures. No binary
ODT fixture is included by this change.

## Materialization channels

Every reference case records the channels it affects:

| Channel | Evidence to record |
| --- | --- |
| CONTENT | `content.xml` structure and references |
| STYLE | common, automatic, master styles, families, definitions and references |
| FONT | font-face declarations and font dependencies |
| RESOURCE | embedded resources such as images |
| MANIFEST | `META-INF/manifest.xml` entries |
| PACKAGE | physical ODT ZIP/package structure |
| PAGE/MASTER | page layouts, master pages, headers and footers |
| METADATA | `meta.xml` document metadata |
| SETTINGS | `settings.xml` where relevant |

These are research categories only. They are not production classes or APIs.

## Phase 1 reference cases

The first study phase contains only the following cases.

| ID | Case | Question |
| --- | --- | --- |
| STYLE-01 | Named paragraph style | How does LibreOffice represent and reference a reusable named paragraph style? |
| STYLE-02 | Direct paragraph formatting | How does LibreOffice represent formatting applied directly to one paragraph? |
| STYLE-03 | Named character/text style | How does LibreOffice represent and reference a reusable named character style? |
| STYLE-04 | Direct character formatting | How does LibreOffice represent formatting applied directly to a text fragment? |
| STYLE-05 | Named paragraph plus local override | How is a named paragraph style combined with one direct paragraph override? |
| STYLE-06 | Derived named style | How is style inheritance represented? |
| STYLE-07 | Reused named style | How is one definition referenced by several content nodes? |
| FONT-01 | Non-default font in named style | Which font-face declarations are created and where? |
| TABLE-02 | Formatted table cell | How are table-cell, paragraph, and text responsibilities distributed? |
| FRAME-01 | Basic text box | Which content nodes and graphic/frame styles represent a text box? |
| FRAME-02 | Positioned text box | How are anchoring and horizontal/vertical positioning represented? |
| IMAGE-01 | Embedded image | How are frame, image, graphic style, package resource, href, and manifest connected? |
| PAGE-01 | Page format/layout | How is page layout represented and where is it defined? |
| PAGE-02 | Header/footer through master page | How are master page, page layout, header/footer content, and references related? |

### Standard record for each case

Every fixture and evidence record must use this structure:

```text
Reference ID

Purpose

LibreOffice procedure
    Exact manual GUI/document operation used to create the fixture.

Visible result
    What the document visibly contains.

Affected channels
    CONTENT / STYLE / FONT / RESOURCE / MANIFEST / PACKAGE / etc.

content.xml observations
    Content nodes, references, and automatic styles where applicable.

styles.xml observations
    Common styles, automatic styles, master styles, and font-face declarations.

META-INF/manifest.xml observations

Package resources

Reference topology
    Explicitly document who references what.

Round-trip observation
    What happens after opening and saving the fixture again in LibreOffice.

ODF interpretation
    What the ODF specification says about the observed structure.

Engine implication
    Not decided yet.
```

### Reference topology

Topology is a first-class observation, not merely a check that a style exists.
For each relationship record:

- where the definition is stored;
- its ODF family;
- which node references it;
- which attribute forms the reference;
- whether the referenced definition exists;
- whether the definition depends on another style, resource, font, or layout;
- whether the relationship survives a LibreOffice round trip.

The following are conceptual examples, not measured findings:

```text
text:p
    |
    | text:style-name
    v
style:style
    |
    +-- style:paragraph-properties
    +-- style:text-properties
```

```text
draw:image
    |
    +-- xlink:href ----------> package resource
    |                            |
    |                            v
    |                       manifest:file-entry
    |
draw:frame
    |
    +-- draw:style-name -----> graphic style
```

Exact storage locations and attribute combinations must be taken from the
fixture and normative ODF evidence, not assumed from these diagrams.

## Provenance requirements

Each fixture must record at least:

- reference ID;
- LibreOffice version;
- operating system/platform;
- creation date;
- exact manual creation procedure;
- whether it was reopened and resaved;
- SHA-256 of the original ODT;
- relevant ODF version reported by the document/package, if available.

Fixtures must not be silently regenerated with another LibreOffice version.
Any replacement requires new provenance and an explicit comparison.

## Future extraction workflow

Future tooling may provide a helper such as:

```text
tools/odf-reference/extract-reference.sh <fixture.odt>
```

It may extract `content.xml`, `styles.xml`, `META-INF/manifest.xml`,
`meta.xml`, relevant `settings.xml`, and the package file listing. Future
normalization is allowed only in addition to retaining the raw extracted XML;
normalization must not hide semantically relevant data. The helper is not part
of this framework slice.

## STYLE-01 — Named paragraph style

### Purpose

Record how LibreOffice represents a reusable named paragraph style and how a
content paragraph references that definition.

### LibreOffice procedure

In LibreOffice Writer 24.2.7.2 Community on Ubuntu 24.04 with the de-DE UI,
create a document with two visible paragraphs. Create a paragraph style named
`RefParagraph`, assign it to the first paragraph, and set font size to 14 pt,
bold, font color `#123456`, and paragraph spacing below to 0.50 cm. Leave the
second visible paragraph in the standard paragraph style and apply no direct
formatting after assigning `RefParagraph`.

### Visible result

The document contains the visible paragraphs `Named paragraph style` and
`Standard paragraph`. The package also contains one empty standard paragraph
between them, as serialized by LibreOffice.

### Affected channels

`CONTENT`, `STYLE`, `FONT`, `MANIFEST`, and `PACKAGE` are present in the
fixture. `RESOURCE` is limited to LibreOffice's thumbnail; no image resource
was introduced by this case. No page/master, metadata, or settings behavior
was attributed to the named style.

### content.xml observations

The root is `office:document-content` with `office:version="1.3"`. The two
visible paragraphs are serialized as:

```xml
<text:p text:style-name="RefParagraph">Named paragraph style</text:p>
<text:p text:style-name="Standard">Standard paragraph</text:p>
```

There is no direct paragraph-formatting attribute, child `text:span`, or
automatic-style indirection on either visible paragraph. `RefParagraph` is
referenced once directly through `text:style-name`. The standard paragraph is
referenced directly through `text:style-name="Standard"`.

`office:automatic-styles` in `content.xml` is empty.

### styles.xml observations

The root is `office:document-styles` with `office:version="1.3"`.
`RefParagraph` is defined directly under `office:styles`:

```xml
<style:style style:name="RefParagraph"
             style:family="paragraph"
             style:parent-style-name="Standard"
             style:master-page-name="">
  <style:paragraph-properties
      fo:margin-top="0cm"
      fo:margin-bottom="0.499cm"
      style:contextual-spacing="false"
      style:page-number="auto"/>
  <style:text-properties
      fo:color="#123456"
      loext:opacity="100%"
      fo:font-size="14pt"
      fo:font-weight="bold"/>
</style:style>
```

The requested 0.50 cm spacing is serialized as `fo:margin-bottom="0.499cm"`.
The style has no `style:display-name`, no `style:next-style-name`, and no
explicit font-family property. Its parent is the common `Standard` paragraph
style. The `Standard` definition itself has no explicit paragraph or text
properties in this fixture; defaults provide inherited baseline formatting.

`office:automatic-styles` in `styles.xml` contains the page layout `Mpm1`, not
a style caused by `RefParagraph`. It defines the document's page geometry and
is unrelated to this paragraph-style requirement.

### Font-face observations

Both `content.xml` and `styles.xml` contain the same six LibreOffice font-face
declarations, including `Liberation Serif`, `Liberation Sans`, `FreeSans`, and
CJK/complex-script fallback faces. `RefParagraph` introduces no new font-face
dependency: it changes size, weight, and color but does not select a font
family. The paragraph style therefore inherits the default font family.

### Manifest and package observations

`META-INF/manifest.xml` has `manifest:version="1.3"` and entries for `/`,
`styles.xml`, `content.xml`, `meta.xml`, `settings.xml`, configuration data,
`manifest.rdf`, and `Thumbnails/thumbnail.png`. There are no `Pictures/`
entries and no additional resource introduced by STYLE-01.

The ODT is a ZIP package with 17 entries. `mimetype` is the first entry, has
the exact value `application/vnd.oasis.opendocument.text`, and is stored
uncompressed. The original fixture size is 10,879 bytes.

### Reference topology

The observed topology is:

```text
content.xml
  text:p text:style-name="RefParagraph"
        |
        v
styles.xml / office:styles
  style:style style:name="RefParagraph" style:family="paragraph"
        |
        +-- style:paragraph-properties
        |     +-- fo:margin-bottom="0.499cm"
        |     +-- style:contextual-spacing="false"
        |     +-- style:page-number="auto"
        |
        +-- style:text-properties
              +-- fo:font-size="14pt"
              +-- fo:font-weight="bold"
              +-- fo:color="#123456"
```

The style inherits from `Standard`. There is no automatic-style or font-face
indirection specific to `RefParagraph`, and no physical resource dependency.

### ODF interpretation

Observed `style:family="paragraph"` identifies a paragraph-family style
definition. The content paragraph's `text:style-name` supplies the reference
to that named definition. The `style:parent-style-name="Standard"` expresses
inheritance from the common paragraph style, while the paragraph and text
property children carry the family-specific formatting. These statements are
the normative ODF interpretation of the observed vocabulary; LibreOffice's
choice to serialize 0.50 cm as 0.499 cm is an implementation observation.

### Provenance

| Field | Value |
| --- | --- |
| Reference ID | STYLE-01 |
| LibreOffice | 24.2.7.2 (X86_64), Community, Build 420(Build:2) |
| Platform | Ubuntu 24.04, package `4:24.2.7-0ubuntu0.24.04.6` |
| UI/locale | de-DE |
| Creation date | 2026-09-01 |
| SHA-256 | `1782fa8733db3e88752284e76d72e7fff649ef3fe4aee38976263fcdf6ce53eb` |
| ODF version | `1.3` in content, styles, meta, and settings; manifest version `1.3` |
| Round-trip | Not performed yet. |

### Engine implication

Not decided yet.

## Deferred reference cases

The following are explicitly deferred and are not Phase 1 execution items:

- LIST-01
- LIST-02
- TABLE-01
- TABLE-03
- TABLE-04
- IMAGE-02
- IMAGE-03
- SECTION-01
- PAGE-03
- META-01
- PACKAGE-01

## Research boundary

This study records empirical semantics. It does not select an engine data
model, prescribe a renderer, repair existing output, or turn observations into
an implementation contract. Such decisions require a separate reviewed
architecture step after the relevant fixtures have been inspected.

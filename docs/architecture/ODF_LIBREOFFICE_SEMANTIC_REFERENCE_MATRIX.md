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

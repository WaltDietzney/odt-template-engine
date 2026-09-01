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

The framework predates the fixture sweep. STYLE-01 is the first captured
fixture; this research update adds no binary ODT fixtures.

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

## Phase-1 research overview

Detailed findings and source-by-source reasoning are recorded in
[ODF_LIBREOFFICE_PHASE1_RESEARCH_FINDINGS.md](ODF_LIBREOFFICE_PHASE1_RESEARCH_FINDINGS.md).
The table below is an index, not a replacement for the case records.

| ID | Normative status | Reference evidence | Serialization freedom | Additional LO fixture | Reason |
| --- | --- | --- | --- | --- | --- |
| STYLE-01 | NORMATIVE CLEAR; fixture captured | Local STYLE-01 fixture; OASIS Part 3 §§3.15, 16.30.2 | Names and defaults are free; family/reference semantics are not | NO ADDITIONAL LO FIXTURE REQUIRED | Control case already records the practical serialization. |
| STYLE-02 | NORMATIVE PARTIAL | OASIS Part 3 §16.30.2; LibreOffice Style Inspector | Direct formatting may be serialized through automatic styles or legal local properties | TARGETED LO FIXTURE USEFUL | Exact Writer encoding and parent relation remain practical details. |
| STYLE-03 | NORMATIVE CLEAR | OASIS Part 3 §16.30.1; LibreOffice Writer style help | Names and placement of equivalent definitions are free within ODF rules | TARGETED LO FIXTURE USEFUL | Confirms Writer's character-style reference topology. |
| STYLE-04 | NORMATIVE PARTIAL | OASIS Part 3 §16.30.1; LibreOffice Style Inspector | Automatic-style grouping and span boundaries are implementation choices | TARGETED LO FIXTURE USEFUL | Useful for mixed direct formatting and span merging. |
| STYLE-05 | NORMATIVE PARTIAL | OASIS Part 3 §§3.15, 16.30.2 | Base-style plus local override has several legal representations | TARGETED LO FIXTURE REQUIRED | The parent/automatic-style topology is architecture-relevant. |
| STYLE-06 | NORMATIVE CLEAR | OASIS Part 3 §3.15; `style:parent-style-name` | Names and unrelated defaults are free | NO ADDITIONAL LO FIXTURE REQUIRED | Inheritance mechanism is normatively defined; STYLE-01 supplies a parent example. |
| STYLE-07 | NORMATIVE CLEAR | OASIS Part 3 §§3.15, 16.30 | Reference count and generated names are free | NO ADDITIONAL LO FIXTURE REQUIRED | Reuse is ordinary named-style reference semantics. |
| FONT-01 | NORMATIVE PARTIAL | OASIS Part 3 §16.23; LibreOffice Writer style help | Declaration placement and font fallback are implementation-sensitive | TARGETED LO FIXTURE REQUIRED | The font-face/reference dependency needs one practical check. |
| TABLE-02 | NORMATIVE PARTIAL | OASIS Part 3 §§16.30, 16.38; LibreOffice Writer style help | Equivalent formatting can be split across compatible style families | TARGETED LO FIXTURE REQUIRED | Cell/paragraph/text responsibility is central to current architecture. |
| FRAME-01 | NORMATIVE PARTIAL | OASIS Part 3 §§16.39, 10.4; LibreOffice core QA | Names, defaults, and some placement details are free | TARGETED LO FIXTURE USEFUL | Confirms basic frame/text-box topology. |
| FRAME-02 | NORMATIVE PARTIAL | OASIS Part 3 §§16.39, 19.750–19.755 | Position values depend on anchor and relation choices | TARGETED LO FIXTURE REQUIRED | Writer's anchor/position serialization must be observed. |
| IMAGE-01 | NORMATIVE CLEAR for dependency graph | OASIS Part 2 §§3–4; Part 3 `draw:image`/`xlink:href`; LibreOffice core QA | Graphic-style use and package conventions have some freedom | TARGETED LO FIXTURE REQUIRED | Package/resource/manifest topology is compatibility-critical. |
| PAGE-01 | NORMATIVE CLEAR | OASIS Part 3 §§16.5, 16.9 | Property defaults and generated names are free | TARGETED LO FIXTURE USEFUL | Confirms Writer's page-layout property grouping. |
| PAGE-02 | NORMATIVE CLEAR | OASIS Part 3 §§16.9, 16.5 | Header/footer content placement and defaults vary by editor | TARGETED LO FIXTURE USEFUL | One combined page/master fixture can verify practical topology. |

## Phase-1 captured fixture register

| IDs | Fixture | Observed empirical result | Provenance |
| --- | --- | --- | --- |
| STYLE-02, STYLE-04 | `STYLE-02-04-direct-formatting.odt` | `P1` paragraph and `T1`–`T3` text automatic styles are in `content.xml`; paragraph references P1 and the formatted run references T3. | 12,119 bytes; SHA-256 `12e48079af090d25c42186e81052a8d3d8a356be1e2298986eedbf1c3b0bdb9f` |
| STYLE-05 | `STYLE-05-named-style-direct-override.odt` | Automatic P1 in `content.xml` is parented by common `RefOverrideBase` in `styles.xml` and contains only the red override. | 10,249 bytes; SHA-256 `7ae5f32ddd9ef99f3db5ddcdd9b340d52425b5075bffa97c1e9899bf36b99a02` |
| FONT-01 | `FONT-01-non-default-font.odt` | Common `RefFont` references `Liberation Sans1`; matching font-face declarations occur in both document parts. | 10,408 bytes; SHA-256 `4680810fd77fc32ff2502d142f80c59a16fde883777f45e9eb487016710c3231` |
| TABLE-02 | `TABLE-02-formatted-cell.odt` | Automatic table/table-column/table-row/table-cell styles are in `content.xml`; cell A1 owns background/padding/border and paragraph P1 owns text color/bold. | 10,272 bytes; SHA-256 `dba70c9fe41026bda5a3f41bf2d1f2502c152f99e6d4e4272b0849d6f2f05c9b` |
| FRAME-01, FRAME-02 | `FRAME-01-02-text-box-position.odt` | Paragraph-anchored `draw:frame` contains `draw:text-box`; direct size/coordinates and automatic graphic style `gr1` carry distinct data. | 10,811 bytes; SHA-256 `a12b50088eed794fb74799ae8feb91db5ac0be3e48a900e99e9c3d270560f7b3` |
| IMAGE-01 | `IMAGE-01-embedded-image.odt` | Character-anchored frame references automatic graphic style `fr1`; `draw:image` href resolves to a JPEG package resource with matching manifest entry. | 93,272 bytes; SHA-256 `2e4554d7494f28ebee77bc063dc93816e9a77986d3f9b0b707cff3287dd2bbeb` |
| PAGE-01, PAGE-02 | `PAGE-01-02-layout-master-page.odt` | Automatic page layout `Mpm1` in `styles.xml` is referenced by master page `Standard`, which contains header/footer content. | 11,092 bytes; SHA-256 `e4f5476cbbf7971461998df99a8f7d5eaf7d0eedf64476cfd0c38a5ce9ce9a81` |

All seven fixtures report ODF 1.3 and manifest version 1.3, use the stated
LibreOffice 24.2.7.2 provenance, and have no non-thumbnail resource except
IMAGE-01. Detailed content/style/package observations are in
[the Phase-1 findings](ODF_LIBREOFFICE_PHASE1_RESEARCH_FINDINGS.md).

## Source register

| Source | Version/section | URL | Use |
| --- | --- | --- | --- |
| OASIS OpenDocument Format, Part 1: Introduction | ODF 1.3, normative overview | [OASIS Part 1](https://docs.oasis-open.org/office/OpenDocument/v1.3/os/part1-introduction/OpenDocument-v1.3-os-part1-introduction.html) | Specification status and relationship of the ODF parts. |
| OASIS OpenDocument Format, Part 2: Packages | ODF 1.3, §§3–4 | [OASIS Part 2](https://docs.oasis-open.org/office/OpenDocument/v1.3/OpenDocument-v1.3-part2-packages.html) | ZIP package, manifest, mimetype, and relative-IRI semantics. |
| OASIS OpenDocument Format, Part 3: Schema | ODF 1.3, §§3.15, 10.4, 16.5, 16.9, 16.23, 16.30, 16.38–16.39, 19.750–19.755 | [OASIS Part 3](https://docs.oasis-open.org/office/OpenDocument/v1.3/os/part3-schema/OpenDocument-v1.3-os-part3-schema.html) | Normative elements, attributes, style families, placement, and dependencies. |
| OASIS ODF Technical Committee repository | current repository; ODF 1.2–1.4 resources | [oasis-tcs/odf-tc](https://github.com/oasis-tcs/odf-tc) | Existing OASIS test/reference-document infrastructure; no binary is copied here. |
| LibreOffice Help, Style Inspector | current Writer help | [Style Inspector](https://help.libreoffice.org/latest/en-GB/text/swriter/01/style_inspector.html?DbPAR=WRITER) | Practical distinction between style properties and direct formatting. |
| LibreOffice Help, Styles in Writer | current Writer help | [Styles in Writer](https://help.libreoffice.org/latest/en-GB/text/swriter/01/05130000.html) | Writer's paragraph, character, frame, page, and list style categories. |
| LibreOffice Help, Character Styles | current Writer help | [Character styles](https://help.libreoffice.org/latest/en-GB/text/swriter/01/05130002.html) | Override ordering for paragraph, character, and direct formatting. |
| LibreOffice core QA layout fixture | current `master` | [keep-with-next-fly.fodt](https://github.com/LibreOffice/core/blob/master/sw/qa/extras/layout/data/keep-with-next-fly.fodt) | Implementation evidence for a real frame/image flat-ODF topology. |
| LibreOffice core QA documentation | current `master` | [layout QA README](https://github.com/LibreOffice/core/blob/master/sw/qa/extras/README) | Context for interpreting LibreOffice test documents as implementation evidence. |

The STYLE-01 fixture reports ODF 1.3. The relevant ODF 1.4 style, package, and
master-page concepts are treated as compatible for this sweep; no conclusion
here depends on a version-specific 1.4 change. ODF 1.3 remains the normative
reference for interpreting the captured fixture.

## Minimum additional fixture plan

Only the following targeted fixtures are currently justified by unresolved
practical serialization questions. A fixture may cover multiple matrix cases:

1. STYLE-02 + STYLE-04: direct paragraph and direct character formatting in
   one document, including mixed spans.
2. STYLE-05: a named paragraph style with one direct local override.
3. FONT-01: a named style using a non-default font.
4. TABLE-02: one formatted table cell with distinct cell, paragraph, and text
   formatting.
5. FRAME-01 + FRAME-02: basic and positioned text boxes with different
   anchors/relations.
6. IMAGE-01: one embedded image with its complete package and manifest
   topology.
7. PAGE-01 + PAGE-02: page layout plus a master-page header/footer.

STYLE-03, STYLE-06, and STYLE-07 are sufficiently constrained by the ODF
style model, existing STYLE-01 evidence, and authoritative Writer
documentation that additional fixtures are useful but not presently required.
No fixture is created by this research update.

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

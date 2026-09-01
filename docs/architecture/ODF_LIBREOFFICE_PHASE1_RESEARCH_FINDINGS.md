# ODF / LibreOffice Phase-1 Research Findings

## Status and evidence hierarchy

This is an evidence record, not a Change Contract and not an engine design.
The ODF 1.3 specification is normative. OASIS and LibreOffice test/reference
documents are secondary empirical evidence. A manually created LibreOffice
fixture is used only where concrete Writer serialization remains relevant.
Current ODT Template Engine output is not evidence of correct ODF semantics.

The existing STYLE-01 fixture was created with LibreOffice Community
24.2.7.2 (Build 420) on Ubuntu 24.04, de-DE UI, and reports `office:version`
1.3. Its detailed raw evidence remains in the matrix and fixture directory.
The individual Engine implication for every case remains **Not decided yet**.

## Cross-cutting semantic model

ODF distinguishes common styles, automatic styles, and master styles. Common
styles are reusable `style:style` definitions in `office:styles`; automatic
styles are also `style:style` definitions but are scoped to a document part
and are carried by an `office:automatic-styles` container; master styles are
held by `office:master-styles`, notably `style:master-page`. Automatic styles
are therefore not synonymous with “content.xml styles”: the containing ODF
document part determines where the automatic-style container occurs. A page
layout is an automatic style in `styles.xml`, while a master page is a master
style that references it.

The specification defines family and reference semantics, but generally does
not prescribe generated names, an editor's grouping of equivalent properties,
or every legal split between inherited and local properties. LibreOffice
documentation calls the UI notion “direct formatting”; ODF represents the
result using the legal property/style structures selected by the writer.
Direct formatting overrides the applicable style, but “direct” is not a
separate ODF style family.

For all cases below, “fixture classification” refers to the practical
LibreOffice serialization question, not to uncertainty about the normative
ODF model.

## STYLE-01 — Named paragraph style

**Normative ODF semantics.** A paragraph style is a `style:style` with
`style:family="paragraph"`; a paragraph references it with
`text:style-name`. A common style may use `style:parent-style-name` for
inheritance. Paragraph and text properties can both occur in the definition;
text properties apply to paragraph characters unless a descendant text style
overrides them. See ODF 1.3 Part 3 §§3.15 and 16.30.2.

**Serialization freedom.** Style names, display names, defaults, and
irrelevant attribute emission are free. A legal editor may choose a different
parent or explicit default representation while preserving semantics.

**Existing reference evidence.** The local STYLE-01 fixture is direct
LibreOffice evidence: `text:p text:style-name="RefParagraph"` references a
common paragraph style in `styles.xml`, parent `Standard`, with the requested
paragraph/text properties. The OASIS schema is the normative source.

**Fixture classification.** **NO ADDITIONAL LO FIXTURE REQUIRED**. This is the
captured control case.

## STYLE-02 — Direct paragraph formatting

**Normative ODF semantics.** Paragraph properties are represented by
paragraph-style properties or permitted local/automatic-style properties on
the paragraph representation. The style family remains paragraph; ODF does
not define a separate “direct formatting” family.

**Serialization freedom.** An editor may create an automatic paragraph style,
attach local properties where the schema permits, or fold equivalent values
into another legal style structure. Automatic-style names and the precise
scope of the generated style are not normative.

**Existing reference evidence.** ODF 1.3 Part 3 §16.30.2 defines paragraph
style semantics. LibreOffice Style Inspector documents the UI distinction
between applied style and direct formatting and that direct formatting
overrides style properties.

**Fixture classification.** **TARGETED LO FIXTURE USEFUL**. The normative
meaning is clear, but Writer's automatic-style grouping matters to later
compatibility work.

## STYLE-03 — Named character/text style

**Normative ODF semantics.** A character style is `style:style` with
`style:family="text"`; a text span may reference it through
`text:style-name`. Its text properties apply to the span and participate in
the documented override order.

**Serialization freedom.** The common/automatic placement, generated name,
and span segmentation are not fixed if the resulting style references and
properties are legal.

**Existing reference evidence.** ODF 1.3 Part 3 §16.30.1 and LibreOffice's
Character Styles help define the family and override behavior. The OASIS ODF
TC repository provides a durable place to search for conformance examples,
but no external file is copied into this repository.

**Fixture classification.** **TARGETED LO FIXTURE USEFUL**. It would confirm
Writer's exact span/reference topology, but the architecture-relevant family
semantics are already clear.

## STYLE-04 — Direct character formatting

**Normative ODF semantics.** Character properties apply to a text run or
descendant text span. Direct formatting is an editor operation/result, not a
new ODF family; the serialized properties may be carried by an automatic text
style or another schema-valid local representation.

**Serialization freedom.** Span boundaries, automatic-style grouping,
property omission, and merging of adjacent equivalent runs are editor
choices. Direct formatting overrides a character style.

**Existing reference evidence.** ODF 1.3 Part 3 §16.30.1 plus LibreOffice
Style Inspector and Character Styles help establish the semantics and
precedence.

**Fixture classification.** **TARGETED LO FIXTURE USEFUL**. A small mixed-span
fixture would resolve practical grouping questions.

## STYLE-05 — Named paragraph style plus direct local override

**Normative ODF semantics.** A paragraph can reference a named paragraph
style while a more local paragraph property overrides it. An automatic style
may inherit from a common style with `style:parent-style-name`; the exact
representation depends on the containing part and legal schema placement.

**Serialization freedom.** The override may appear in an automatic style,
through local properties, or through an equivalent legal style structure.
The specification does not require LibreOffice's likely “automatic child
style” choice.

**Existing reference evidence.** ODF 1.3 Part 3 §§3.15 and 16.30.2 define
parent-style and paragraph-property semantics; LibreOffice Style Inspector
documents the precedence, but does not prescribe package serialization.

**Fixture classification.** **TARGETED LO FIXTURE REQUIRED**. The base-plus-
override reference topology is directly relevant to style collection and
cannot be established from STYLE-01 alone.

## STYLE-06 — Named style derived from another named style

**Normative ODF semantics.** `style:parent-style-name` expresses a parent
style dependency for a style definition. The child inherits applicable
properties and can override them; the style family and valid parent relation
remain governed by ODF.

**Serialization freedom.** Names, explicit repetition of defaults, and
unrelated property emission are free. An editor need not emit every inherited
property in the child.

**Existing reference evidence.** ODF 1.3 Part 3 §3.15 and the STYLE-01
fixture's parent `Standard` provide sufficient normative/practical evidence.

**Fixture classification.** **NO ADDITIONAL LO FIXTURE REQUIRED**. A fixture
would be useful for UI provenance, not necessary for the semantic model.

## STYLE-07 — One named style referenced multiple times

**Normative ODF semantics.** Multiple content nodes may use the same
`text:style-name` (or applicable family-specific reference) to reuse one
definition. The definition remains one style object in its owning style
container.

**Serialization freedom.** Generated names, order, and whether an editor
creates a new equivalent style rather than reusing one are not prescribed;
reuse itself is legal and semantically ordinary.

**Existing reference evidence.** ODF 1.3 Part 3 §§3.15 and 16.30 define the
reference model; STYLE-01 directly demonstrates one reference and the same
model scales to multiple references.

**Fixture classification.** **NO ADDITIONAL LO FIXTURE REQUIRED**.

## FONT-01 — Non-default font used by a named style

**Normative ODF semantics.** A font face is declared with `style:font-face`
in `office:font-face-decls`; text properties refer to it using the relevant
font-name attribute (for example `style:font-name`). The style definition
and font-face declaration are separate nodes linked by the font name.

**Serialization freedom.** Font-face placement in the document part,
fallback-related attributes, aliases, and unused declarations are practical
serialization details. This is not font embedding research.

**Existing reference evidence.** ODF 1.3 Part 3 §16.23 defines font-face
declarations and Part 3's text-property sections define font-name use.
LibreOffice Writer style help establishes that font is a text/style property.

**Fixture classification.** **TARGETED LO FIXTURE REQUIRED**. The dependency
graph is normative, but the exact Writer placement and declaration set should
be observed once.

## TABLE-02 — Formatted table cell

**Normative ODF semantics.** A `table:table-cell` can reference a table-cell
style; cell properties cover cell-level concerns such as borders, padding,
background, and vertical alignment. Content inside the cell is independently
structured: paragraphs use paragraph styles and text runs/spans use text
styles. Alignment/margins/line-spacing are paragraph concerns; font,
weight, color, and decoration are text concerns, subject to the exact ODF
property definitions.

**Serialization freedom.** A visual result may sometimes be achieved by a
combination of compatible family properties, but a cell border/background
does not become a text style merely because text is inside the cell. Style
splitting and generated names are free.

**Existing reference evidence.** ODF 1.3 Part 3 §16.38 defines table styles
and §16.30 defines paragraph/text styles. LibreOffice Writer's style-category
documentation distinguishes paragraph, character, and table/frame categories.

**Fixture classification.** **TARGETED LO FIXTURE REQUIRED**. Current engine
architecture needs empirical evidence about the split and the exact table
cell/paragraph/text topology.

## FRAME-01 — Basic text box

**Normative ODF semantics.** A `draw:frame` is a drawing container and may
contain `draw:text-box`; the text box contains text content. The frame can
reference a graphic style, while text inside retains paragraph/text semantics.
Size and graphic properties are governed by the relevant drawing/style
attributes.

**Serialization freedom.** Generated style names, explicit defaults, and
some placement/property grouping are editor choices. A frame's native
container relationship is not optional when a text box is represented this
way.

**Existing reference evidence.** ODF 1.3 Part 3 drawing and graphic-style
sections define the structure. The LibreOffice core `keep-with-next-fly.fodt`
QA document is practical evidence of a real frame/image serialization.

**Fixture classification.** **TARGETED LO FIXTURE USEFUL**.

## FRAME-02 — Positioned text box

**Normative ODF semantics.** Anchor semantics are expressed through the
anchor-type relationship and graphic positioning properties such as
horizontal/vertical position and relation. The allowed interpretation is
relative to the selected anchor/reference context, not just raw coordinates.

**Serialization freedom.** Writer may choose anchor types, relation values,
defaults, and coordinate units/values that are all legal yet render
differently. Equal-looking coordinates without equal anchoring are not
semantically equivalent.

**Existing reference evidence.** ODF 1.3 Part 3 graphic property and drawing
sections define the attributes; LibreOffice core layout QA documents provide
implementation evidence, not a universal Writer contract.

**Fixture classification.** **TARGETED LO FIXTURE REQUIRED** for the
architecture-relevant anchor/position combinations.

## IMAGE-01 — Embedded image

**Normative ODF semantics.** A frame may contain `draw:image`; the image's
`xlink:href` is an IRI identifying the image data. For an ODT package, the
physical target is a package file and `META-INF/manifest.xml` describes the
package entry/media type. A frame graphic style is a separate optional
styling dependency, not the bitmap itself.

**Serialization freedom.** Package path/name, graphic-style use, explicit
defaults, and thumbnail/convention details have permitted variation. The
content reference, resolvable package resource, and manifest coverage are
the architecture-relevant dependency chain.

**Existing reference evidence.** OASIS ODF 1.3 Part 2 §§3–4 defines package
and manifest semantics; Part 3 defines `draw:image`/`xlink:href`. The
LibreOffice core QA FODT is implementation evidence for frame/image content.

**Fixture classification.** **TARGETED LO FIXTURE REQUIRED** because the
complete practical package topology must be checked once.

## PAGE-01 — Page format/layout

**Normative ODF semantics.** `style:page-layout` defines page layout
properties and is an automatic-style construct in the document-styles
part. A master page uses `style:page-layout-name` to identify the layout.

**Serialization freedom.** Names, explicit defaults, and the degree of
property emission are free. The specification does not require Writer's
particular generated layout name.

**Existing reference evidence.** ODF 1.3 Part 3 §§16.5 and 16.9 establish
page-layout/master-page semantics. STYLE-01 already shows an automatic page
layout in `styles.xml`.

**Fixture classification.** **TARGETED LO FIXTURE USEFUL**, mainly to verify
the practical property grouping and defaults.

## PAGE-02 — Header/footer through master page

**Normative ODF semantics.** A `style:master-page` belongs in
`office:master-styles`, references a page layout by
`style:page-layout-name`, and can contain header/footer structures. The page
layout and master-page are related but distinct style constructs.

**Serialization freedom.** Header/footer content placement, generated names,
default suppression, and repeated-content details vary by editor while the
master/layout relationship must remain resolvable.

**Existing reference evidence.** ODF 1.3 Part 3 §§16.5 and 16.9 are the
normative source. The OASIS ODF TC repository is a useful place to search for
reference documents; no matching binary is claimed here.

**Fixture classification.** **TARGETED LO FIXTURE USEFUL**; one combined
page-layout/master-page fixture is sufficient unless later evidence exposes
an unresolved Writer-specific detail.

## Minimal additional fixture plan

The minimum targeted set is seven documents: (1) STYLE-02 + STYLE-04,
(2) STYLE-05, (3) FONT-01, (4) TABLE-02, (5) FRAME-01 + FRAME-02, (6)
IMAGE-01, and (7) PAGE-01 + PAGE-02. STYLE-03, STYLE-06, and STYLE-07 need
no additional fixture at present. This is a plan only; no ODT is created here.

## Source register

The durable source register is maintained in the overview matrix. The
primary references are OASIS ODF 1.3 Parts 1–3, the OASIS ODF TC repository,
and the cited LibreOffice Writer/core QA material.

## Captured fixture evidence

All seven files report `office:version="1.3"` in `content.xml` and
`styles.xml`, manifest version `1.3`, and generator
`LibreOffice/24.2.7.2$Linux_X86_64 LibreOffice_project/420$Build-2`. Each has
17 ZIP entries except IMAGE-01, which has 18. In every case `mimetype` is the
first entry, is stored (method 0), and contains
`application/vnd.oasis.opendocument.text`. Raw files are under the
corresponding `extracted/<ID>/` directory and are not normalized.

The common manual procedure was to create a new Writer document in the
documented de-DE LibreOffice environment, perform the operation named by the
fixture, and save it as the listed ODT. The visible results were the requested
text, formatting, table, frame, image, or page/header/footer content described
below. Every package manifest contains the root document plus
`content.xml`, `styles.xml`, `meta.xml`, `settings.xml`, `manifest.rdf`, and a
thumbnail; only IMAGE-01 adds a document resource. No fixture has been
reopened or resaved by this study.

| Fixture | Affected channels | Manifest/package observation |
| --- | --- | --- |
| STYLE-02-04 | CONTENT, STYLE, FONT, MANIFEST, PACKAGE | No additional resource; thumbnail only. |
| STYLE-05 | CONTENT, STYLE, FONT, MANIFEST, PACKAGE | No additional resource; thumbnail only. |
| FONT-01 | CONTENT, STYLE, FONT, MANIFEST, PACKAGE | No additional resource; thumbnail only. |
| TABLE-02 | CONTENT, STYLE, FONT, MANIFEST, PACKAGE | No additional resource; thumbnail only. |
| FRAME-01-02 | CONTENT, STYLE, FONT, MANIFEST, PACKAGE | No additional resource; thumbnail only. |
| IMAGE-01 | CONTENT, STYLE, FONT, RESOURCE, MANIFEST, PACKAGE | JPEG resource and matching manifest entry. |
| PAGE-01-02 | CONTENT, STYLE, FONT, PAGE/MASTER, MANIFEST, PACKAGE | Header/footer live in master-page; no additional resource. |

The embedded creation timestamps are: STYLE-02-04
`2026-09-01T18:09:16.037631657`, STYLE-05
`2026-09-01T18:12:00.589936800`, FONT-01
`2026-09-01T18:16:30.310320989`, TABLE-02
`2026-09-01T18:18:50.634728821`, FRAME-01-02
`2026-09-01T18:22:52.289319358`, IMAGE-01
`2026-09-01T18:26:12.475492331`, and PAGE-01-02
`2026-09-01T18:29:47.843198730`.

### STYLE-02 + STYLE-04 — direct formatting

The original `STYLE-02-04-direct-formatting.odt` is 12,119 bytes with SHA-256
`12e48079af090d25c42186e81052a8d3d8a356be1e2298986eedbf1c3b0bdb9f`.
The first `text:p` references automatic paragraph style `P1` in
`content.xml` `office:automatic-styles`; it contains `fo:text-align="end"`
and `fo:margin-bottom="0.7cm"`. The directly formatted middle run is a
`text:span text:style-name="T3"`; T3 is an automatic `text` style in
`content.xml` with `fo:color="#cc0000"`, `fo:font-size="16pt"`, and
`fo:font-weight="bold"`. Intermediate automatic text styles T1 and T2 are
also present for partial formatting states. The second paragraph references
`Standard`. No P1/T1/T2/T3 definition is in `styles.xml`.

This **CONFIRMS** the normative model and refines it with concrete Writer
behavior: direct formatting uses content-owned automatic styles, while span
segmentation and intermediate styles are LibreOffice choices. Round-trip:
**Not performed yet**.

### STYLE-05 — named style plus direct override

The original `STYLE-05-named-style-direct-override.odt` is 10,249 bytes with
SHA-256 `7ae5f32ddd9ef99f3db5ddcdd9b340d52425b5075bffa97c1e9899bf36b99a02`.
The paragraph references automatic paragraph style `P1` in content.xml.
P1 has `style:parent-style-name="RefOverrideBase"` and only the local
`fo:color="#cc0000"` text property, plus LibreOffice opacity. Common
`RefOverrideBase` remains in `styles.xml`, family `paragraph`, parent
`Standard`, with `fo:color="#123456"`, `fo:font-size="14pt"`, and
`fo:margin-bottom="0.499cm"`. No text-span indirection is used.

This **CONFIRMS** the prior expectation of an automatic child style and
refines it: LibreOffice emits only the override in that child. Round-trip:
**Not performed yet**.

### FONT-01 — non-default font

The original `FONT-01-non-default-font.odt` is 10,408 bytes with SHA-256
`4680810fd77fc32ff2502d142f80c59a16fde883777f45e9eb487016710c3231`.
`text:p` references common paragraph style `RefFont` in `styles.xml`; its
text properties use `style:font-name="Liberation Sans1"` and
`fo:font-family="'Liberation Sans'"`. The matching `style:font-face` is in
both content.xml and styles.xml `office:font-face-decls`, alongside default
and fallback declarations. The property is the reference and font-face is
the declaration; this is not font embedding. The result **CONFIRMS** the
dependency model and refines it with duplicate per-part declarations.
Round-trip: **Not performed yet**.

### TABLE-02 — formatted cell

The original `TABLE-02-formatted-cell.odt` is 10,272 bytes with SHA-256
`dba70c9fe41026bda5a3f41bf2d1f2502c152f99e6d4e4272b0849d6f2f05c9b`.
Content contains `table:table` style `Tabelle1`, column style `Tabelle1.A`,
row style `Tabelle1.1`, and one cell style `Tabelle1.A1`; these are automatic
styles in content.xml. A1 has `fo:background-color="#ffd546"`,
`fo:padding="0.199cm"`, and `fo:border="1pt solid #000000"`.

The cell contains `text:p text:style-name="P1"`; P1 is an automatic paragraph
style parented by `Table_20_Contents` and carries `fo:color="#cc0000"` and
bold properties. No text span is needed. The requested paragraph centering
was not observed in P1's emitted properties, so this is recorded rather than
corrected. This **REFINES** the prior model. Round-trip: **Not performed yet**.

### FRAME-01 + FRAME-02 — text box and position

The original `FRAME-01-02-text-box-position.odt` is 10,811 bytes with SHA-256
`a12b50088eed794fb74799ae8feb91db5ac0be3e48a900e99e9c3d270560f7b3`.
Content contains `draw:frame` named `Textrahmen 1`,
`text:anchor-type="paragraph"`, `draw:style-name="gr1"`, and
`draw:text-style-name="P1"`. Its size is 6.002cm by 2.001cm and direct
coordinates are x=2cm, y=1cm. It contains a `draw:text-box` with
`Positioned text box`.

Automatic graphic style gr1 in content.xml carries horizontal position
`from-left` relative to `paragraph`, vertical position `from-top` relative to
`paragraph`, and `style:wrap="run-through"`; P1 supplies text-box writing
mode. This separates anchor, size/coordinates, graphic style, and text
content. The single frame is combined evidence for FRAME-01/02. This
**CONFIRMS** and refines the prior model. Round-trip: **Not performed yet**.

### IMAGE-01 — embedded image

The original `IMAGE-01-embedded-image.odt` is 93,272 bytes with SHA-256
`2e4554d7494f28ebee77bc063dc93816e9a77986d3f9b0b707cff3287dd2bbeb`.
Content contains a character-anchored 3cm by 3cm `draw:frame` with
`draw:style-name="fr1"`, containing `draw:image` whose href is
`Pictures/100000000000028000000280B8169D6C.jpg` and media type `image/jpeg`.
fr1 is an automatic graphic style in content.xml, parented by `Graphics`.
The href resolves to the package resource and the manifest has the same path
and media type.

The embedded resource is JPEG, 640x640, 90,207 bytes, SHA-256
`0227b05d69f45b2acdc56ec7dcb966ed8d284b7054a6ac8c8e7ffa6cfa3c3bef`.
No `IMAGE-01-source.png` was supplied, so source-byte comparison is
unavailable. This **CONFIRMS** the dependency graph and refines it with
Writer's generated JPEG package name and content-owned graphic style.
Round-trip: **Not performed yet**.

### PAGE-01 + PAGE-02 — page layout and master page

The original `PAGE-01-02-layout-master-page.odt` is 11,092 bytes with SHA-256
`e4f5476cbbf7971461998df99a8f7d5eaf7d0eedf64476cfd0c38a5ce9ce9a81`.
`styles.xml` contains automatic page layout `Mpm1` with landscape dimensions
29.7cm by 21.001cm, margins top/bottom 2.499cm and left/right 2cm, and
`style:print-orientation="landscape"`. It contains master page `Standard`
with `style:page-layout-name="Mpm1"`.

The master page contains header/footer structures with visible texts
`Reference Header` and `Reference Footer`; their paragraph styles are
`Header` and `Footer`, derived from `Header_20_and_20_Footer`. The body uses
`Standard`, and content.xml has no automatic styles for this case. This
**CONFIRMS** the prior model and demonstrates that an automatic page layout
belongs in styles.xml rather than content.xml. Round-trip: **Not performed yet**.

## Cross-fixture empirical comparison

| Semantic object | ODF family | LO placement | Observed reference | Dependency |
| --- | --- | --- | --- | --- |
| RefParagraph | paragraph | common, styles.xml | text:p/@text:style-name | parent Standard; paragraph/text properties |
| Direct paragraph P1 | paragraph | automatic, content.xml | text:p/@text:style-name | local paragraph properties |
| Direct text T3 | text | automatic, content.xml | text:span/@text:style-name | local text properties |
| RefOverrideBase | paragraph | common, styles.xml | automatic child P1 | P1 parent reference |
| RefFont font face | font declaration | duplicated in content/styles | style:text-properties/@style:font-name | style property to style:font-face |
| Tabelle1.A1 | table-cell | automatic, content.xml | table:table-cell/@table:style-name | cell properties |
| P1 in table | paragraph | automatic, content.xml | text:p/@text:style-name | parent Table_20_Contents |
| gr1 | graphic | automatic, content.xml | draw:frame/@draw:style-name | frame graphic properties |
| IMAGE-01 resource | package file | package + manifest | draw:image/@xlink:href | href to manifest entry |
| Mpm1 | page-layout | automatic, styles.xml | master/@style:page-layout-name | page properties |
| Standard master page | master-page | master styles, styles.xml | master-page context | Mpm1, header, footer |

This is an empirical LibreOffice 24.2 view. ODF 1.3 defines the families,
containers, and references, but not these generated names, property grouping,
or duplicate font-face choices.

## Phase-1 empirical completion assessment

| Question | Status | Reason |
| --- | --- | --- |
| Common style semantics sufficiently evidenced? | SUFFICIENT | STYLE-01, STYLE-05, and FONT-01 show common definitions, parents, and references. |
| Automatic style semantics sufficiently evidenced? | SUFFICIENT | Direct formatting, table/frame styles, and a styles.xml page layout are captured. |
| Named + direct override sufficiently evidenced? | SUFFICIENT | STYLE-05 captures automatic child P1 parented by RefOverrideBase. |
| Font dependencies sufficiently evidenced? | SUFFICIENT | FONT-01 records property, declaration, duplicate containers, and hash. |
| Table-cell/paragraph/text boundaries sufficiently evidenced? | SUFFICIENT | TABLE-02 records separate cell and paragraph styles and text properties. |
| Frame ownership/positioning sufficiently evidenced? | SUFFICIENT | FRAME-01/02 records topology, anchor, size, coordinates, and relations. |
| Image package/resource/manifest dependencies sufficiently evidenced? | SUFFICIENT | IMAGE-01 records href, resource, media type, manifest, and hash. |
| Page-layout/master-page semantics sufficiently evidenced? | SUFFICIENT | PAGE-01/02 records layout, master, reference, header, and footer. |

No additional Phase-1 fixture is currently justified. Round-trip observation
remains a separate future decision. Engine implications remain **Not decided
yet**.

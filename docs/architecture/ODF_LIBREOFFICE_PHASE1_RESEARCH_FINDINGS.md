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

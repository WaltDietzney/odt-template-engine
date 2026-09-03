# Current Style Requirement Behavior Characterization

## Status

This document characterizes the current style-requirement behavior before any
new style architecture is designed or implemented.

It is **not** a Change Contract and does not prescribe production APIs or class
names.

The implementation reference for this characterization is:

- branch: `architecture/style-context-01fd5-transitive-requirements`
- commit: `b038533e9830869d37434be7dc094c7e08923ddf`

The semantic comparison model is documented in:

- `ODF_DOCUMENT_MATERIALIZATION_MODEL.md`
- `ODF_DOCUMENT_MODEL_ENGINE_GAP_ANALYSIS.md`
- `ODF_LIBREOFFICE_PHASE1_RESEARCH_FINDINGS.md`

The purpose here is narrower: identify what the engine actually produces,
collects, stores, and materializes today.

## 1. Summary

The current implementation already has several sound architecture boundaries:

- structured ownership is exposed through `OdtElement::ownedElements()`;
- `StyleRequirementCollector` traverses that ownership tree transitively;
- individual requirement occurrences are preserved until `StyleContext`;
- `StyleContext` is document-local and resets with the document context;
- physical image resources are discovered separately and prepared by
  `OdtPackage`;
- page-layout mutation follows an explicit master-page -> page-layout relation.

The principal style gap is not traversal. It is representation.

Today a collected style requirement is effectively:

```text
family
name
definition[]
```

That tuple does not encode all semantics established by the ODF reference
study. In particular it does not explicitly encode:

- common vs automatic style kind/scope;
- owning document part (`content.xml` vs `styles.xml`);
- parent-style dependency;
- typed property groups;
- definition-vs-reference-only semantics.

The current `definition[]` is also not one uniform representation. Depending on
the producer it may contain convenience API options, mapped ODF attributes, or
a mixture containing non-style structural metadata.

## 2. Current collector protocol

`StyleRequirementCollector` asks every element for five own requirement
families:

```text
paragraph
text
frame
image
fill-image
```

It yields each item as:

```php
[
    'family' => $family,
    'name' => $name,
    'definition' => $definition,
]
```

and then recurses through `ownedElements()`.

This is a good transitive-composition mechanism, but `family` is currently an
engine routing label, not a complete ODF style identity. For example `frame`
and `image` both ultimately materialize as `style:family="graphic"`.

Table and table-cell styles are notably absent from the collector protocol.
They still rely on older registration/materialization paths.

## 3. Paragraph requirements

### 3.1 Producer representation

`Paragraph` stores two different style concepts:

```text
paragraphStyle
paragraphStyleOptions[]

textStyleMap[styleName] = textOptions[]
```

Paragraph requirements are exposed only when both a paragraph style name and
non-empty paragraph options exist:

```text
paragraphStyle + paragraphStyleOptions
    -> paragraph requirement

paragraphStyle only
    -> reference only; no requirement
```

The requirement definition is the original convenience option array, for
example:

```php
[
    'margin-bottom' => '0.10cm',
    'line-height' => '100%',
]
```

It is not yet a typed ODF `style:paragraph-properties` representation.

### 3.2 Materialization behavior

`OdtTemplate::setElement()` currently handles paragraph requirements before
content materialization by calling `ensureParagraphStylesExist()`.

That method always creates a common paragraph style in:

```text
styles.xml
  office:styles
    style:style family="paragraph"
```

with hard-coded:

```text
style:parent-style-name="Standard"
style:class="text"
```

The convenience options are then mapped through
`StyleMapper::mapParagraphStyle()` and written into one
`style:paragraph-properties` element.

Consequences of current behavior:

- every generated paragraph requirement is materialized as a common style;
- automatic-vs-common intent is not represented;
- parent style is not supplied by the requirement;
- all mapped properties are treated as paragraph properties;
- a paragraph requirement cannot currently express the TABLE-02 form where a
  paragraph-family style contains both `style:paragraph-properties` and
  `style:text-properties`.

## 4. Reference-only paragraph styles

A `Paragraph` may be constructed with a style name and no local options:

```php
new Paragraph('CVMainHeading')
```

In that case `toDomNode()` emits:

```text
text:p text:style-name="CVMainHeading"
```

but `getRequiredParagraphStyles()` returns no requirement.

This is semantically meaningful: the element expresses a style reference but
does not claim ownership of a definition.

The current protocol does not make this distinction explicit. It simply emits
no requirement.

Sample 21 demonstrates the compatibility consequence. The sample registers
reusable named paragraph styles through the process-global
`StyleMapper::registerParagraphStyle()` path and later creates paragraphs that
reference those names without carrying definitions themselves.

After the document-local migration disabled legacy paragraph finalization on the
normal `setElement()` path, those external registrations are no longer a
reliable source of document-owned definitions. This explains the observed
Sample-21 class of "reference exists, definition missing" behavior.

The important characterization is therefore:

> A name-only paragraph style is currently a reference-only element state, but
> the engine has no first-class contract describing how that reference is
> resolved to an existing template definition, an explicitly registered common
> style, or an error.

## 5. Text requirements

### 5.1 Producer representation

`Paragraph::addText()` stores the original convenience text options, for
example:

```php
[
    'bold' => true,
    'font-size' => '10pt',
    'color' => '#111111',
    'font-family' => 'Arial',
]
```

A stable hash-based style name is generated from that array and stored in
`textStyleMap`.

The collected text requirement therefore transports **unmapped convenience
options**, not native `style:text-properties` attributes.

### 5.2 Materialization behavior

`ensureTextStylesExist()` always writes those requirements as common text styles
in:

```text
styles.xml
  office:styles
```

with:

```text
style:family="text"
style:parent-style-name="Standard"
```

and maps the convenience definition through
`StyleMapper::mapTextStyleOptions()` into `style:text-properties`.

`Paragraph::toDomNode()` then references the generated style from a
`text:span`.

This is structurally compatible with named character-style semantics, but the
engine does not currently distinguish:

- reusable named common text style;
- generated local/direct formatting that would naturally be an automatic text
  style;
- inherited automatic override.

The hash-based name currently identifies equivalent option arrays, but does not
encode semantic style kind or owning document part.

## 6. StyleMapper representation boundary

`StyleMapper` currently serves several roles at once:

- convenience option mapping;
- stable generated-name creation;
- legacy global registration;
- compatibility escape hatch for native prefixed attributes.

This produces more than one representation layer.

### Convenience representations

Examples:

```text
bold -> fo:font-weight
color -> fo:color
text-align -> fo:text-align
margin-bottom -> fo:margin-bottom
background -> fo:background-color
```

### Native-attribute escape hatch

Several mapping functions accept already-prefixed ODF attributes and preserve
them.

This is useful for compatibility, but means a `definition[]` cannot safely be
assumed to be either entirely semantic/high-level or entirely native ODF.

### Unknown keys

`mapParagraphStyle()` preserves unknown keys unchanged. Likewise
`StyleOptionSplitter` deliberately preserves unknown/native keys on the native
context.

This is a compatibility feature, but it prevents the current flat map from
proving which property group an attribute belongs to.

## 7. StyleOptionSplitter is an important partial semantic boundary

`StyleOptionSplitter` already recognizes three responsibility domains:

```text
cell
paragraph
text
```

For friendly convenience keys this is a strong alignment with the ODF reference
model.

For example, in table-cell context:

```text
background/border/padding -> cell
text-align                  -> paragraph
font/color/weight           -> text
```

`RichTableCell::setStyle()` then pushes paragraph options into its contained
`Paragraph` and text options into the paragraph text content.

This is conceptually important and should not be lost in later refactoring.

However, native prefixed attributes are routed to the current native context as
an advanced compatibility escape hatch. Therefore an input such as
`fo:text-align` in table-cell context can still end up in a cell definition even
though the semantic splitter would route the friendly `text-align` key to the
paragraph domain.

This is current compatibility behavior, not a proposed future rule.

## 8. Table-cell styles remain outside the document-local collector

`RichTableCell` maps cell options to native-looking ODF attributes and immediately
registers the style through the static `StyleMapper::registerTableCellStyle()`
path.

Its own style definition looks like:

```text
styleName -> mapped table-cell attributes
```

and `toDomNode()` references it through `table:style-name`.

But `StyleRequirementCollector` has no `table-cell` requirement family and
`StyleContext` has no table-cell collection.

At save time `StyleWriter::writeAllStyles()` still reads globally registered
cell styles and writes them as common styles into `styles.xml/office:styles`,
with hard-coded parent `Default`.

Therefore table-cell style ownership is currently still a compatibility/global
path rather than part of the document-local style requirement model.

The Phase-1 LibreOffice TABLE-02 fixture does not imply that every API-generated
cell style must be automatic; reusable common table-cell styles are valid use
cases. The gap is that the current engine has no semantic signal allowing it to
choose intentionally.

## 9. Frame requirements

`DrawTextBox` maps its frame options through
`StyleMapper::mapFrameStyleOptions()` and exposes the mapped array as a frame
requirement.

This means frame definitions are already closer to native ODF than Paragraph and
text requirements.

The normal document-owned save path currently materializes frame requirements
as:

```text
styles.xml
  office:styles
    style:style family="graphic"
      parent="Frame"
```

The Phase-1 LibreOffice frame fixture used an automatic graphic style in
`content.xml` for the tested local frame formatting.

This does **not** prove that the current common graphic style is universally
invalid. It demonstrates that the current requirement lacks the information
needed to distinguish reusable/common graphic semantics from local/automatic
frame formatting.

A second current ambiguity exists between structural frame attributes and
style properties. `DrawTextBox::toDomNode()` writes anchor, size, and selected
position attributes on the frame itself, while mapped frame options may also
contain positioning properties for the graphic style.

The current model therefore mixes two questions:

- what belongs to the `draw:frame` node;
- what belongs to `style:graphic-properties`.

The ODF reference model requires those decisions to remain explicit.

## 10. Image requirements are a mixed representation

`ImageElement::setStyle()` maps user options using
`StyleMapper::mapImageStyleOptions()` and then stores a generated `style-name`
inside the resulting `imageOptions` map.

That map can contain values from different semantic layers, including:

```text
svg:width / svg:height
text:anchor-type
style:wrap
style:horizontal-pos / rel
style:vertical-pos / rel
align                (internal convenience metadata)
style-name            (internal identity metadata)
```

`getImageStyleRequirements()` exposes this whole map as the image style
requirement.

`toDomNode()` separately consumes several of these values as direct frame
attributes and emits the physical `draw:image` resource reference.

During style injection only a selected subset is copied into
`style:graphic-properties`.

Therefore the current image requirement is not a pure graphic-style definition.
It is a mixed frame/materialization state object used by multiple consumers.

This is one of the clearest examples of why a richer semantic requirement model
is needed before D5F.

## 11. Graphic requirement placement is hard-coded by engine category

`injectDocumentGraphicStyles()` currently applies these placement rules:

```text
frameStyles
    -> styles.xml / office:styles
    -> family graphic, parent Frame

imageStyles
    -> styles.xml / office:automatic-styles
    -> family graphic, parent Standard

fillImages
    -> styles.xml / office:styles
```

These rules are encoded in the materializer/finalizer rather than carried by the
requirements themselves.

The current categories therefore implicitly combine producer type, style kind,
parent, and placement.

That works for the currently characterized behavior but is not a general ODF
style contract.

## 12. Parent-style dependencies are not first-class

The Phase-1 STYLE-05 fixture established the importance of an explicit parent
relationship:

```text
automatic P1 -> common RefOverrideBase
```

Current generated materializers instead supply parents as implementation
constants:

```text
paragraph -> Standard
text      -> Standard
frame     -> Frame
image     -> Standard
cell      -> Default
```

No current collected requirement can explicitly say:

```text
parent-style-name = RefOverrideBase
```

while preserving a local delta definition.

Therefore STYLE-05 semantics cannot be represented faithfully by the current
flat requirement protocol.

## 13. Property groups are flattened too early

The current paragraph path maps its complete definition into one
`style:paragraph-properties` node.

The current text path maps its definition into one `style:text-properties`
node.

This is adequate for the current convenience APIs because they split paragraph
and inline text concerns before collection.

It is insufficient as a general ODF style model because TABLE-02 demonstrated
that one paragraph-family style may legitimately contain both:

```text
style:paragraph-properties
style:text-properties
```

Therefore ODF family cannot be used as a proxy for a single property group.

## 14. Fonts are derived during finalization, not collected as requirements

`StyleContext` currently has no font dependency collection.

`StyleWriter::writeAllStyles()` scans the current `styles.xml` DOM for
attributes whose names end in `font-name`, then ensures corresponding
`style:font-face` declarations exist in `styles.xml/office:font-face-decls`.

This was an important improvement over process-global font state, but it is
still document-part-specific by accident: it derives fonts only from the
`styles.xml` DOM passed to the writer.

If future automatic text/paragraph styles are correctly materialized into
`content.xml`, their font dependencies cannot be discovered by this scan alone.

The FONT-01 fixture also demonstrated that a style property reference and a
font-face declaration are distinct semantic dependencies, and that LibreOffice
may emit declarations in both document parts.

The current implementation therefore has a valid local compatibility strategy,
but no explicit font dependency channel yet.

## 15. Existing document styles are treated as authoritative data

`StyleContext` intentionally owns only pending requirements; existing
`styles.xml` definitions remain document data.

Materialization helpers generally skip creation when a style with the requested
name already exists.

This is an important compatibility rule and should be preserved unless a future
Change Contract explicitly changes it.

However, because current requirements do not distinguish "reference existing
style" from "define style if absent", this behavior is currently implicit.

## 16. Resource handling is structurally ahead of style handling

The image-resource path already expresses a cleaner semantic split:

```text
ImageElement
    -> content reference + own image asset declaration

StructuredResourceCollector
    -> transitive discovery

OdtPackage
    -> physical copy / conflict handling
    -> manifest synchronization on save
```

This closely matches the document/materialization model and provides a useful
pattern for style dependencies: producer semantics, transitive discovery, and
part/package-owned finalization are separate stages.

## 17. Page layout is already an explicit semantic relation

`PageLayoutManager` resolves a master page, follows its
`style:page-layout-name`, and mutates the referenced
`style:page-layout-properties` in `styles.xml`.

It does not infer page-layout location from a generic style family and does not
route through the flat style requirement protocol.

This area is therefore not a driver for the upcoming style-requirement change.

## 18. Current behavior matrix

| Area | Producer definition form | Document-local collection | Current physical placement | Characterization |
| --- | --- | --- | --- | --- |
| Paragraph | convenience options | yes | common style in `styles.xml/office:styles` | semantically too flat |
| Inline text | convenience options | yes | common style in `styles.xml/office:styles` | common/automatic intent missing |
| Frame | mapped/native-like graphic attrs | yes | common graphic style in `styles.xml/office:styles` | kind/part hard-coded |
| Image graphic style | mixed structural/style/internal map | yes | automatic graphic style in `styles.xml` | representation mixed |
| Fill image | declaration/resource metadata | yes | `styles.xml/office:styles` | separate non-`style:style` concept routed as style family |
| Table cell | mapped native attrs | no | legacy common style in `styles.xml/office:styles` | still global compatibility path |
| Table | mapped/native attrs | no | legacy common style path | still global compatibility path |
| Fonts | derived from style DOM | no explicit channel | `styles.xml/font-face-decls` | part awareness incomplete |
| Page layout | existing ODF relation | dedicated manager | `styles.xml/automatic-styles` | conceptually sound |
| Image resource | physical asset descriptor | yes, separate collector | package `Pictures/` + manifest | conceptually sound |

## 19. What is already worth preserving

The characterization strongly supports preserving the following ideas:

1. `ownedElements()` as the semantic composition tree.
2. Transitive collectors as projections over that tree.
3. Individual conflict occurrences reaching a document-local owner.
4. `StyleContext` lifetime/reset semantics.
5. `StyleOptionSplitter`'s distinction between cell, paragraph, and text
   convenience concerns.
6. Package ownership of physical resources and manifest synchronization.
7. Existing template style definitions remaining authoritative document data.
8. Compatibility facades around legacy global registries until migration is
   complete.

## 20. What must be expressible before D5F

Before materialization lifecycle work continues, a style requirement contract
must be able to answer, without inspecting concrete element classes:

```text
What semantic style/declaration is required?
What ODF family does it have?
What property groups does it contain?
Is it common or automatic?
Which document part owns its definition?
Does it inherit from another style?
Is this a definition request or only a reference?
What font/declaration dependencies follow from it?
```

Not every answer must necessarily be represented by a new public object or API.
The next Change Contract should define the minimum internal representation that
preserves these semantics while retaining backward compatibility.

## 21. Characterization conclusion

The evidence does not support a rewrite of structured materialization.

The current traversal/resource architecture is substantially aligned with the
new ODF model. The bounded problem is the semantic contract transported through
the style channel.

The most important concrete gaps are:

- generated text/paragraph styles have no common-vs-automatic intent;
- physical document-part ownership is inferred by materializer method rather
  than carried semantically;
- parent-style dependencies are hard-coded and cannot express STYLE-05;
- one requirement cannot express multiple typed property groups;
- reference-only named styles are implicit and can lose their definition source;
- table/table-cell styles remain outside the document-local collector;
- image style requirements mix structural, style, and internal metadata;
- font dependencies are derived late from `styles.xml` rather than represented
  explicitly across document parts.

This is sufficient evidence to proceed to a bounded **Style Requirement Change
Contract**. D5F should remain paused until that contract is agreed.
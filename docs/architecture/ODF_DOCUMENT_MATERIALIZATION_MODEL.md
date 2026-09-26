# ODF Document / Materialization Model

## Status

This document is an architecture model derived from the Phase-1 ODF / LibreOffice
semantic study. It is intentionally **not** a Change Contract and does not define
public APIs, production class names, or an implementation plan.

Its purpose is to establish a shared semantic model before D5F or later style and
materialization work continues.

Evidence hierarchy:

1. OASIS ODF specification is normative.
2. LibreOffice reference fixtures provide empirical serialization evidence.
3. Current ODT Template Engine behavior is compatibility evidence, not normative
   ODF truth.

The detailed evidence remains in:

- `ODF_LIBREOFFICE_SEMANTIC_REFERENCE_MATRIX.md`
- `ODF_LIBREOFFICE_PHASE1_RESEARCH_FINDINGS.md`
- `tests/fixtures/libreoffice-reference/`

## 1. Core distinction: semantic object vs. physical serialization

The engine must distinguish an ODF semantic object from the XML or package node
that materializes it.

A style, font declaration, frame, resource, page layout, or master page is not
adequately modeled by its XML location alone. Its meaning is determined by its
ODF role, family, references, dependencies, and owning document/package scope.

Consequently:

> Physical XML location is a materialization result, not the primary semantic
> identity of an ODF concept.

This is especially important because automatic styles legitimately occur in
both `content.xml` and `styles.xml`.

## 2. Document model

The ODT package can be viewed as a set of cooperating semantic channels:

```text
OdtPackage
├── Content Model
├── Style Model
├── Font Model
├── Drawing / Frame Model
├── Resource Model
├── Page / Master Model
├── Manifest Model
├── Metadata Model
└── Settings Model
```

These are not proposed production classes. They are semantic responsibilities.

The channels are related by references and dependencies rather than by a single
flat ownership list.

## 3. Content model

The content model owns the semantic document tree: paragraphs, text runs,
tables, rows, cells, lists, sections, frames, images, text boxes, and similar
structured content.

Content nodes may reference other semantic objects, for example:

- paragraph -> paragraph style
- span -> text style
- table cell -> table-cell style
- frame -> graphic style
- image -> package resource
- body/master context -> page/master structures

The content model should express native ODF structure and relationships without
also becoming the global owner of every dependency it references.

This preserves the existing D5 principle that element-local ODF competence must
remain localized while document/package-wide dependencies are finalized by the
appropriate document/package scope.

## 4. Style model

The style model has at least three independent semantic axes.

### 4.1 Style kind / scope

ODF distinguishes:

- common styles
- automatic styles
- master styles

These are not interchangeable storage labels.

Common styles are reusable semantic definitions. Automatic styles represent
object- or document-part-local formatting. Master styles represent master-page
semantics.

The empirical fixtures confirm that an automatic style cannot be modeled as
"a style in content.xml". For example:

- direct paragraph formatting -> automatic paragraph style in `content.xml`
- table-cell formatting -> automatic table-cell style in `content.xml`
- frame graphic formatting -> automatic graphic style in `content.xml`
- page layout -> automatic page-layout style in `styles.xml`

Therefore placement follows the owning document part and ODF role.

### 4.2 Style family

A style family identifies the type of object to which the style applies, for
example:

- paragraph
- text
- table
- table-column
- table-row
- table-cell
- graphic
- section
- page-layout

Family is not equivalent to property domain.

### 4.3 Property domain

A style definition may contain typed property groups such as:

- `style:paragraph-properties`
- `style:text-properties`
- `style:table-cell-properties`
- `style:graphic-properties`
- `style:page-layout-properties`

The corrected TABLE-02 fixture is the key counterexample to a flat model:

```text
style:style family="paragraph"
├── style:paragraph-properties
│   └── fo:text-align="center"
└── style:text-properties
    ├── fo:color="#cc0000"
    └── fo:font-weight="bold"
```

Thus:

> Style family describes the styled semantic object; property groups describe
> the kinds of properties contributed by that style.

The two concepts must not be collapsed.

## 5. Inheritance and local overrides

Style inheritance is a semantic dependency.

The STYLE-05 fixture demonstrates:

```text
common style RefOverrideBase
        ^
        | style:parent-style-name
        |
automatic paragraph style P1
        ^
        | text:style-name
        |
content paragraph
```

The automatic child contains only the local override while inheriting the
remaining named-style semantics.

Therefore a style requirement cannot always be represented correctly as a flat
`name -> properties` map. At minimum, semantic identity may include:

- kind/scope
- family
- parent dependency
- property groups
- owning document part

No production representation is prescribed here.

## 6. Direct formatting

"Direct formatting" is primarily an editor/UI concept, not an additional ODF
style family.

LibreOffice empirical behavior shows that direct formatting commonly
materializes as automatic styles owned by the relevant document part.

Examples:

- whole-paragraph direct formatting -> automatic paragraph style
- partial text direct formatting -> automatic text style referenced by a
  `text:span`
- named style plus local override -> automatic child style inheriting from the
  common style

The semantic distinction should therefore be modeled as base semantics plus
local override semantics rather than by inventing a special "direct" family.

## 7. Font model

Font-face declarations are dependencies separate from the styles that reference
them.

The FONT-01 fixture establishes the relationship:

```text
style definition
  style:text-properties/@style:font-name
        |
        v
office:font-face-decls/style:font-face
```

LibreOffice may serialize matching font-face declarations in more than one
document part. The declaration is not the same object as the style property
that references it.

Therefore font discovery/finalization is a dependency-materialization concern,
not simply a side effect of serializing a text property.

## 8. Table model

Tables demonstrate why semantic ownership and property ownership must be kept
separate.

The TABLE-02 fixture shows:

```text
table
  -> table style
  -> column style
  -> row style
  -> cell
       -> table-cell style
          -> cell properties
       -> paragraph
          -> paragraph style
             -> paragraph properties
             -> text properties
```

Cell properties include concerns such as background, border, and padding.
Paragraph alignment remains a paragraph property. Font/color/weight remain text
properties even when they are physically carried by the same paragraph-family
style definition.

The engine must not move properties between semantic domains merely because a
visual result appears equivalent.

## 9. Drawing and frame model

A frame is not reducible to a graphic style.

The FRAME-01/02 fixture shows independent concerns:

- structural frame node
- text-box child content
- anchor semantics
- explicit size
- coordinates
- graphic style
- positioning relations

Conceptually:

```text
draw:frame
├── structural content
├── anchor
├── size / coordinates
├── graphic-style reference
└── draw:text-box
    └── text content
```

The graphic style may contribute positioning/wrapping properties, but frame
identity and text-box content remain distinct semantic concerns.

This supports keeping native ODF element semantics near the element while
allowing dependency finalization outside the element itself.

## 10. Resource and manifest model

An embedded image spans multiple semantic channels.

The IMAGE-01 fixture establishes:

```text
content.xml
  draw:image/@xlink:href
        |
        v
package resource
        |
        v
META-INF/manifest.xml file-entry
```

A graphic style may also be referenced by the surrounding frame, but it is a
separate dependency from the physical image resource.

Therefore:

> A physical resource belongs to package ownership, while the content node owns
> the semantic reference to that resource.

The package layer is responsible for ensuring that a referenced resource is
physically present and represented correctly in the manifest.

This remains separate from style ownership.

## 11. Page and master model

Page layout and master page are related but distinct semantic objects.

The PAGE-01/02 fixture establishes:

```text
styles.xml / office:automatic-styles
  style:page-layout Mpm1
        ^
        | style:page-layout-name
        |
styles.xml / office:master-styles
  style:master-page Standard
        ├── header
        └── footer
```

This is the clearest evidence that physical placement cannot be derived from
"automatic style means content.xml".

Page layout is an automatic style, yet its correct owning part is `styles.xml`.
The master page is a master-style construct and owns header/footer structures.

## 12. Materialization model

The architecture should distinguish two phases conceptually, regardless of the
final implementation shape.

### 12.1 Semantic discovery

Traverse the semantic ownership tree and identify what the document requires:

```text
semantic content tree
├── style requirements
├── font dependencies
├── physical resources
├── manifest dependencies
├── page/master dependencies
└── native content structure
```

Discovery must preserve semantic distinctions. It must not flatten style family,
style kind, parent relationship, property domain, resource identity, or owning
part prematurely.

### 12.2 Physical materialization

Finalize each dependency in the document/package part required by ODF semantics:

```text
content model
    -> content.xml

common styles
    -> styles.xml / office:styles

content-owned automatic styles
    -> content.xml / office:automatic-styles

styles-part automatic styles
    -> styles.xml / office:automatic-styles

master styles
    -> styles.xml / office:master-styles

font declarations
    -> appropriate office:font-face-decls container(s)

resources
    -> package files

manifest dependencies
    -> META-INF/manifest.xml
```

The exact implementation services remain undecided.

## 13. Ownership rule

The evidence supports the following architecture rule:

> A structured element owns the native ODF semantics of its content and the
> references it expresses. The document/package context owns the physical
> materialization and finalization of dependencies that have document-part or
> package scope.

This refines, but does not replace, the existing D5 principle of one semantic
ownership tree with multiple materialization channels.

The semantic tree remains authoritative for composition. Dependency channels
are projections of that tree, not competing ownership trees.

## 14. Consequences for `toDomNode()`

The evidence does **not** justify removing `toDomNode()` from structured
objects, nor does it prove that every concrete object must permanently own all
DOM construction itself.

What it does establish is the boundary:

- native ODF content semantics must remain localized;
- global style/font/resource/package finalization must not leak into arbitrary
  element serialization side effects;
- a content node may emit a reference without being the physical owner of the
  referenced definition/resource;
- the orchestrator must not recover semantics by type-switching over concrete
  element classes.

Therefore D5F should be judged against semantic locality and dependency
finalization, not against a predetermined goal of either maximizing or removing
`toDomNode()` usage.

## 15. Consequences for the current style work

Before D5F, the current style requirement path should be evaluated against the
following questions:

1. Can it represent style kind/scope, not only family/name/properties?
2. Can it preserve parent-style dependencies?
3. Can it preserve typed property groups rather than flattening native ODF
   properties into an ambiguous map?
4. Can it determine the owning document part for automatic styles?
5. Can font dependencies be collected/finalized without global side effects?
6. Can package resources and manifest entries remain separate from style state?

A negative answer is evidence for a bounded architectural adjustment, not for a
rewrite.

## 16. Non-goals

This model does not decide:

- public APIs
- future template syntax
- final production class names
- whether all style definitions become first-class objects
- whether `StyleContext` is replaced or extended
- whether `toDomNode()` remains on every concrete element
- round-trip normalization policy
- compatibility changes
- D5F implementation slices

Those require a separate Change Contract after the current engine is mapped
against this semantic model.

## 17. Recommended next architecture step

The next step should be a **model-to-engine gap analysis**, not implementation.

Compare the current code paths for:

- `OdtElement` / `toDomNode()`
- `StyleContext`
- `StyleRequirementCollector`
- `StyleMapper`
- `StyleWriter`
- `StructuredResourceCollector`
- `OdtDocumentContext`
- `OdtPackage`
- current page-layout/master-page handling
- current manifest/resource handling

against the semantic responsibilities defined here.

The result should identify:

- already-correct responsibilities;
- compatibility-only paths;
- semantic flattening or ownership leaks;
- missing document-part awareness;
- places where current behavior must first be characterized before change.

Only after that gap analysis should D5F or a replacement slice be specified.

# ARCH-05 Structured Elements Design Notes

**Status:** Exploratory design notes — no API or implementation decisions  
**Milestone context:** ARCH-05 — Structured insertion  
**Purpose:** Preserve the design direction discovered after ARCH-04 before implementation work begins.

## 1. Context

ARCH-04 separated active template-language processing from the document and structured-element responsibilities. The next milestone reaches a more fundamental boundary: structured ODT content is not only text inserted at placeholders. ODF documents contain paragraphs, images, frames, text boxes, tables, shapes, drawing objects, sections, styles, headers, footers, and other structures with different layout and anchoring semantics.

The immediate goal of ARCH-05 must therefore be analysis before extraction or API design.

These notes capture hypotheses and product goals. They intentionally do not prescribe final class names, method names, XML representations, compatibility rules, or public syntax.

## 2. Product driver

A primary practical architecture test is the generation of professional CVs and similarly layout-sensitive documents.

Such documents may require:

- portraits with controlled positioning and anchoring;
- styled headers and section separators;
- text boxes and frames;
- tables and column-like structures;
- RichText and lists;
- repeatable experience and education blocks;
- conditional sections;
- page-specific layout behavior;
- headers and footers;
- preservation of design authored in LibreOffice.

A useful architecture should make these documents possible without forcing application code to reproduce LibreOffice's complete layout system.

## 3. Core design principle: ODT elements as objects

The long-term model should investigate treating structured ODT constructs as objects with explicit identity and capabilities rather than as unrelated special cases.

Conceptually, the element family may include structures such as:

- paragraphs;
- RichText;
- tables;
- images;
- text boxes;
- frames;
- shapes and lines;
- lists;
- other ODF structures discovered during the audit.

This does **not** imply that all element types are freely interchangeable. Different ODF structures have different ownership, anchoring, layout, content, and style semantics. ARCH-05 must discover these constraints before defining a common abstraction.

## 4. Two fundamental interaction models

### 4.1 Textual placeholders

The existing model places dynamic content at a position in the text flow:

```text
{{name}}
{{profile}}
{{avatar}}
```

This remains valuable for simple document generation and structured insertion at explicit textual locations.

### 4.2 Named template objects

LibreOffice allows at least some document objects, such as images/frames, to carry names. A template author can therefore potentially prepare and style a real document object and use its name as a non-visible integration point.

Conceptual examples:

```text
Avatar
ProfileBox
ExperienceTable
HeaderLine
```

A future API might express operations such as:

```php
$template->replaceElementByName('Avatar', $image);
$template->replaceElementByName('ProfileBox', $textBox);
$template->replaceElementByName('ExperienceTable', $table);
```

`replaceElementByName()` is currently a **design hypothesis**, not an accepted API.

ARCH-05 must determine what an element name identifies in actual ODF XML and which ODF structures can be addressed reliably this way.

## 5. Replacement semantics must be explicit

The discussion identified at least two different operations that must not be conflated.

### 5.1 Replace content while preserving the template object

For an image, the desired operation may often be to replace only the image resource while retaining the existing frame and its layout properties.

Potentially preserved properties include:

- position;
- size;
- anchor;
- text wrapping;
- spacing;
- borders;
- background;
- style references;
- other object-specific layout properties.

A similar content-only operation may be useful for text boxes or other containers.

### 5.2 Replace the structured element

Other use cases may require replacing an entire template element with another structured element.

For example, a template text box might be replaced by a dynamically constructed text box, or a template table by another table.

The rules for retaining layout information from the original object are not yet defined.

ARCH-05 must investigate whether these concepts require separate APIs, capabilities, or replacement strategies.

## 6. Type compatibility and capabilities

A generic element model must not imply arbitrary replacement.

Examples that appear naturally meaningful include:

- image → image;
- text box → text box;
- table → table;
- structured container content → compatible structured content.

Other combinations may be technically possible at the DOM level but semantically invalid or surprising.

ARCH-05 should therefore investigate capability-based semantics rather than assume universal interchangeability.

Relevant questions include:

- Can the element contain text?
- Can it contain arbitrary ODF children?
- Can its content be replaced independently of its container?
- Is it anchored in text, to a paragraph, to a page, or through another mechanism?
- Can it be cloned safely?
- Which layout attributes belong to the container and which to its content?
- Which styles belong to the object and which to descendants?

## 7. Named object cloning

A further design direction is cloning a fully styled template object or block.

Conceptually:

```php
$template->cloneElementByName('ExperienceRow', $rows);
```

This could allow a template author to create one visually complete CV experience block in LibreOffice and let the engine duplicate its ODF structure for multiple data rows.

The current foreach implementation already demonstrates that deep DOM cloning can preserve styled descendants in some circumstances. However, a future named-object cloning model must not simply be assumed equivalent to current Smarty-style foreach processing.

Open questions include:

- object identity after cloning;
- duplicate names;
- nested object references;
- styles and automatic styles;
- images and package resources;
- row-local data and conditions;
- anchored objects;
- tables and merged cells;
- references outside `content.xml`.

## 8. Simple and complex template processing

The project should support different levels of complexity without forcing every user into the most advanced model.

### 8.1 Simple template processing

Simple templates should remain approachable:

```text
{{name}}
{{upper:name}}
{{date:birthdate|d.m.Y}}
```

Conditions and repeating structures remain useful where they are understandable and maintainable.

### 8.2 Complex template processing

Complex layout should increasingly use the strengths of the ODT template itself:

- named objects;
- named or identifiable regions;
- LibreOffice styles;
- structured elements;
- object replacement;
- content replacement;
- object/block cloning where appropriate.

The Smarty-inspired language should not be expanded into a complete replacement for LibreOffice's document and layout model.

## 9. Styles as first-class template functionality

A particularly promising direction is allowing placeholders to reference styles already present in the template or deliberately registered by the engine.

Rather than encoding individual visual properties such as boldness directly into template syntax, a placeholder could conceptually reference a named text or paragraph style.

Illustrative ideas only:

```text
{{name:text-style|CvName}}
{{profile:paragraph-style|CvProfile}}
```

The exact syntax is undecided.

The important semantic distinction is between:

1. **value transformations**, such as uppercase, date, number, or currency formatting; and
2. **presentation/style selection**, such as applying a named text style or paragraph style.

These should not be accidentally collapsed into one concept merely because both may appear in placeholder syntax.

Using named ODT styles would allow complex formatting to remain in LibreOffice, where it can be visually authored and changed without rewriting PHP application code.

This direction is closely related to `TEMPLATE-FORMAT-PRESERVATION-01`, `TEMPLATE-AUTHORING-UX-01`, `STYLE-API-02`, and `STYLE-CONTEXT-01`.

## 10. LibreOffice as the visual template designer

A central product principle is to cooperate with LibreOffice rather than recreate its layout system in PHP.

Where practical:

- LibreOffice should define visual design;
- ODT styles should define reusable formatting;
- named objects should define structured integration points;
- the engine should provide dynamic data and dynamic structure;
- PHP should not need to reproduce every layout property already expressible in the template.

This is especially important for layout-sensitive documents such as CVs.

The future template-authoring UX should investigate which native LibreOffice/ODF concepts can serve as reliable integration mechanisms, including object names, sections, frames, styles, master pages, and other structures discovered during research.

## 11. Programmatic document/template construction

A longer-term possibility is creating complete ODT documents or templates programmatically from the same structured element model.

Conceptually:

```php
$document = new OdtDocument();
$document->addParagraph(...);
$document->addTable(...);
$document->addTextBox(...);
$document->addImage(...);
```

This is **not an ARCH-05 requirement**.

However, ARCH-05 should avoid an architecture that unnecessarily prevents structured elements from eventually being used both for insertion into existing templates and for programmatic document construction.

## 12. Page regions and document-level structures

Different first-page headers, later-page headers, footers, master pages, and related page-layout behavior are relevant to professional document generation but should not automatically be pulled into ARCH-05 implementation scope.

They should instead act as architectural constraints: the structured-element model should not assume that all useful content exists only in the main text flow of `content.xml`.

## 13. Relationship to existing future-development topics

### TEMPLATE-FORMAT-PRESERVATION-01

Structured replacement and cloning must account for preservation of existing ODT formatting and DOM structure. Named-object manipulation may provide opportunities to preserve template-authored formatting more reliably than text reconstruction.

### TEMPLATE-AUTHORING-UX-01

Named objects, named styles, and potentially named regions may provide a cleaner authoring experience than increasingly complex visible Smarty markers.

### STYLE-API-02 / STYLE-CONTEXT-01

Style references and programmatically created elements depend on a coherent style model. ARCH-05 must not silently create new global style coupling while designing structured elements.

## 14. ARCH-05 research questions

ARCH-05 should begin with an audit rather than production extraction.

The audit should answer at least:

1. What structured element classes and insertion paths exist today?
2. What is the current responsibility of `OdtElement` and each important subclass?
3. What exactly does `setElement()` replace in the DOM?
4. How does `replacePlaceholderWithDom()` interact with paragraphs, spans, tables, and other parents?
5. How does the existing image-by-name replacement work and what XML object is actually identified by the LibreOffice name?
6. Which ODF structures can be reliably named or otherwise identified?
7. What constitutes element identity for frames, images, text boxes, tables, shapes, sections, and similar structures?
8. Which properties belong to an object's layout container and which belong to its dynamic content?
9. Which element types can support content-only replacement?
10. Which element types can support whole-element replacement?
11. Which replacement combinations are semantically valid?
12. How should style references and automatic styles behave during insertion, replacement, and cloning?
13. Which resources outside the current DOM must be synchronized when an element is inserted or cloned?
14. How do named-object operations interact with package resources and manifest entries?
15. Which existing protected/public APIs are compatibility-sensitive?
16. What responsibilities should remain in `AbstractOdtTemplate` and what should move to a structured-element service?
17. Can the resulting model later support additional document regions and programmatic document construction without coupling ARCH-05 to those features now?

## 15. Proposed discovery sequence

The following sequence is a working proposal, not yet a roadmap commitment.

### ARCH-05A — Structured Element & ODF Object Model Audit

Characterize existing classes, insertion paths, LibreOffice-generated XML, named objects, anchoring, style ownership, package dependencies, and compatibility seams.

### ARCH-05B — Element Identity & Replacement Semantics

Use the audit evidence to define what an element is, what can be named, what can be replaced, what can retain layout, and what can be cloned.

### ARCH-05C — Change Contract

Only after the semantics are understood, define the extraction boundary, compatibility rules, API direction, test matrix, and implementation slices.

### ARCH-05D+ — Implementation

Implement only the behaviors justified by the preceding audit and contract.

## 16. Non-goals of these notes

These notes do not decide:

- the final public API;
- whether `replaceElementByName()` will exist under that name;
- whether every ODT structure will inherit from one concrete base class;
- whether arbitrary element types can replace one another;
- the final placeholder/style syntax;
- whether row-local RichText or structured foreach values will be supported;
- whether named objects replace Smarty control structures;
- whether complete programmatic template construction will be implemented;
- how first-page headers or master-page regions will be exposed;
- the implementation order after the research phase.

## 17. Working design principles

Until ARCH-05 research proves otherwise, the following principles should guide discussion:

1. **Observe ODF before designing the abstraction.**
2. **Preserve compatibility before simplifying legacy paths.**
3. **Keep simple templates simple.**
4. **Do not turn the template language into a replacement layout engine.**
5. **Use LibreOffice and ODT styles as design assets wherever practical.**
6. **Treat named template objects as a promising structured integration mechanism.**
7. **Distinguish content replacement from whole-element replacement.**
8. **Do not assume universal element interchangeability.**
9. **Preserve template-authored layout and formatting where possible.**
10. **Use professional CV generation as a practical architecture test without making the core library CV-specific.**
11. **Keep future document construction possible without making it an ARCH-05 deliverable.**
12. **Define semantics before implementation.**

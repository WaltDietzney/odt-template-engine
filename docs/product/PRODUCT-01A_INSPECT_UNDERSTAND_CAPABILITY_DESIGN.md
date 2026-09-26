# PRODUCT-01A — Inspect & Understand Capability Design

## 1. Purpose

PRODUCT-01A refines the highest-value product finding from PRODUCT-01: the engine should become capable of understanding an existing ODT document as well as modifying it.

The scope is deliberately semantic rather than API-driven. This document does not freeze public method names. It defines the kinds of information and operations that a developer or coding agent should be able to obtain from a document and the addressing concepts required for later read-modify-write workflows.

The target interaction model is:

```text
inspect
  ↓
understand / address
  ↓
read
  ↓
set / replace / clone / remove
  ↓
validate
```

Inspection without mutation would leave the product incomplete; mutation without inspection forces developers and agents to guess the document structure.

## 2. Current repository foundation

The current repository already contains useful foundations:

- `extractTemplateVariables()` for template-variable discovery;
- `MetadataManager` with metadata read/write behavior;
- `PageLayoutManager` with page-layout mutation;
- `TemplateTargetResolver` with strict typed resolution for named frames and tables;
- `TemplateTarget` as a lightweight typed resolved target;
- `StructuredElementMaterializer` for inserting and replacing constructed ODT content;
- existing named image-frame replacement;
- `OdtPackage` and `OdtDocumentContext` as authoritative package/document state owners.

`TemplateTargetResolver` currently resolves only:

- `draw:frame[@draw:name]`;
- `table:table[@table:name]`.

No repository support was found for sections, bookmarks, named ranges, or a general document-inspection model.

## 3. Product principle: read and write should converge on the same semantics

A rounded API should not be designed as unrelated getter and setter collections.

For simple document properties, paired read/write operations are natural:

```text
get margins     ↔ set margins
get orientation ↔ set orientation
get metadata    ↔ set metadata
get style props ↔ set style props
```

For structural objects, a more useful model is:

```text
inspect/find target
       ↓
read target state/content
       ↓
update properties
replace content
replace object
clone object
remove object
```

This suggests that future inspection descriptors and future mutation targets should share a stable semantic identity model.

## 4. Inspection domains

The inspector should eventually be able to describe at least the following domains.

### 4.1 Template language

- variables/placeholders;
- filters used;
- foreach regions;
- condition regions;
- unresolved expressions after rendering;
- malformed or unsupported template syntax.

### 4.2 Document structure

- paragraphs and headings where meaningful;
- lists;
- tables;
- table rows/cells;
- frames;
- images;
- text boxes;
- sections;
- bookmarks or named ranges if supported reliably by LibreOffice/ODF;
- headers/footers when page-structure work exists;
- master pages/page styles.

### 4.3 Styling

- style inventory by family;
- named and automatic styles;
- style parent/inheritance relationships;
- properties declared on a style;
- properties inherited through style hierarchy;
- effective/resolved properties where this can be determined reliably;
- styles used by a selected element.

A key distinction must be preserved:

```text
raw/declared style
        ≠
inherited style
        ≠
effective appearance
```

### 4.4 Page/layout

- master-page names;
- referenced page-layout names;
- margins;
- page width/height;
- orientation;
- headers/footers when represented;
- section/page-style transitions when later supported.

### 4.5 Package/assets

- embedded images/resources;
- media type;
- package path;
- manifest registration;
- broken/missing resource references;
- document metadata.

## 5. Inspection result shape

Coding agents benefit from deterministic structured results rather than human-oriented text dumps.

The future product should therefore prefer descriptors/value objects such as conceptually:

```text
DocumentInspection
├── templateExpressions
├── pageLayouts
├── styles
├── namedObjects
├── tables
├── frames
├── images
├── sections
├── bookmarks
├── assets
├── metadata
└── diagnostics
```

This does not imply one large PHP object or the exact public class names shown above. The principle is that inspection output should be typed, enumerable, serializable where useful, and stable enough for programmatic consumption.

A coding agent should be able to turn inspection results into JSON or another machine-readable representation without parsing debug strings.

## 6. Getter capabilities

Representative read capabilities include:

```text
get variables
get metadata
get page layout
get margins
get page size
get orientation
get styles
get style by name
get declared style properties
get effective style properties
get named objects
get frames
get images
get text boxes
get tables
get sections
get bookmarks/ranges
get assets
get element/object properties
```

These are semantic requirements, not approved method signatures.

The API should avoid exposing arbitrary XPath as the normal public inspection mechanism.

## 7. Setter and mutation capabilities

Inspection should directly support a later read-modify-write model.

### 7.1 Property mutation

Representative capabilities:

```text
set margins
set page size/orientation
set metadata
set style properties
set frame/image geometry
set table/column geometry
set element/object properties
```

Where setters exist, a corresponding read path should normally make the current value discoverable.

### 7.2 Content mutation

Representative capabilities:

```text
replace target content while preserving container/layout
replace complete target object
insert content before/after target
clear target content
```

### 7.3 Structural mutation

Representative capabilities:

```text
clone target
instantiate template target with local values
remove target
move target where safely representable
```

The exact operation set must depend on target capability. A frame, table, bookmark range, section, and paragraph are not interchangeable simply because each can be named or addressed.

## 8. The block problem

PHPWord provides marker-based template blocks such as paired block tags and operations comparable to clone/replace/delete block and clone row.

The capability is highly valuable; its marker syntax should not automatically be copied.

The ODT Template Engine should first investigate whether native ODF/LibreOffice structures can provide a more elegant authoring model.

The desired capability is:

```text
visually author a multi-element region in LibreOffice
        ↓
assign a durable semantic name to that region
        ↓
inspect it from PHP
        ↓
clone / replace / clear / remove / instantiate it
```

The important question is not whether the public API calls it a "block", but how that region receives a stable identity.

## 9. Native identity candidates for template blocks

### 9.1 Named ODF sections

ODF sections are a promising structured candidate because they are document-level containers and support a name in native ODF/LibreOffice authoring.

Potential advantages:

- native structured container;
- user-visible naming in LibreOffice;
- potentially contains multiple paragraphs and other block-level content;
- natural target for whole-region operations;
- avoids visible template marker text.

Questions that require fixture-based verification before adopting sections as template blocks:

- Which child structures can LibreOffice place inside a section without rewriting them unexpectedly?
- Can tables, lists, frames and nested sections survive round trips reliably?
- Does adding a section introduce layout/style semantics that make it unsuitable as a neutral template marker?
- How does LibreOffice serialize empty sections?
- What happens when a section is cloned?
- Are section names unique in practice and how are duplicates handled?
- Can a section be safely removed while preserving surrounding paragraphs and page layout?

### 9.2 Bookmarks / named ranges

Bookmarks are a second promising identity mechanism, especially if LibreOffice serializes a selected range through start/end markers without introducing layout behavior.

Potential advantages:

- lightweight semantic marker;
- may mark existing content without wrapping it in a layout-bearing container;
- visible/manageable through LibreOffice authoring tools;
- potentially suitable for arbitrary paragraph or multi-node ranges.

Questions requiring verification:

- Does LibreOffice reliably serialize range bookmarks with start/end boundaries for multi-paragraph selections?
- Can bookmarks span tables, lists, frames or other structural elements?
- What happens to bookmark boundaries when the region is cloned or replaced?
- Are nested/overlapping bookmarks legal and stable?
- Does saving through LibreOffice preserve exact bookmark names and boundaries?

### 9.3 Existing native named objects

Already established in ARCH-05:

- frames use `draw:name`;
- tables use `table:name`.

These should remain type-specific native identities rather than being flattened into one global "name" namespace prematurely.

### 9.4 XML IDs

Technical IDs such as `xml:id` should remain distinct from author-facing template identity unless future evidence shows a compelling use case. ARCH-05 already intentionally separated technical identifiers from native template names.

## 10. Working hypothesis for elegant template-object authoring

A promising long-term model is:

```text
Native named objects
├── frame name        → image/text-box/frame target
├── table name        → table target
├── section name      → structured block target?  [to verify]
└── bookmark/range    → lightweight range target? [to verify]
```

This could allow LibreOffice authors to create reusable template structures without adding visible `${BLOCK_x}` markers to document text.

No decision between section and bookmark/range is made by PRODUCT-01A. The next evidence step should test both against real LibreOffice-generated ODT fixtures.

## 11. Clone semantics remain plural

The product should continue to distinguish at least three concepts identified during ARCH-05:

### Exact Clone

Duplicate the original native object/subtree as faithfully as possible.

### Template Clone / Template Instance

Duplicate a visually authored structure and evaluate local placeholders/data for the new instance.

### Structural Clone

Recreate equivalent semantic structure through the engine's object model, not necessarily byte-/subtree-identical ODF.

For application developers, Template Clone/Instance likely has the highest value. For low-level editing, Exact Clone can also be important.

A single ambiguous `clone()` operation should not silently mix these semantics.

## 12. Clone row

`cloneRow` deserves explicit investigation because table rows are one of the most common repeating structures in document templates.

Possible approaches include:

- textual foreach, which already exists and repeats template-language regions;
- native row cloning inside a named table;
- row selection through a marker/bookmark/semantic template-row identity;
- template-instance semantics for a row containing placeholders.

PRODUCT-01A does not assume which model should win. The audit should compare authoring ergonomics, ODF stability, nested content, merged cells, style preservation and predictable placeholder scoping.

## 13. Read-modify-write examples

The future engine should support workflows conceptually like:

```text
inspect page layout
→ read current margins
→ modify left/right margins
→ save
```

```text
inspect named styles
→ read style "CVHeading"
→ change its font size
→ save
```

```text
inspect named section "experience_entry"
→ clone as template instance for each experience
→ bind local values
→ remove original template instance if appropriate
→ save
```

```text
inspect table "InvoiceItems"
→ locate template row
→ clone row for each line item
→ validate row fields
→ save
```

The examples describe product intent only.

## 14. AI-agent interaction model

A coding agent should eventually be able to ask the library:

```text
What is in this ODT?
Which objects can I address?
What can I do to this target type?
What is its current state?
Which operation would preserve its layout?
Did my mutation leave unresolved placeholders or broken references?
```

This suggests a future capability-discovery model may be more useful than forcing the agent to infer valid operations from class names alone.

However, PRODUCT-01A does not propose a generalized capability framework yet. ARCH-05 deliberately deferred such a framework. Real operation families should be implemented first and only generalized once evidence warrants it.

## 15. Avoiding a getter/setter explosion

A product API with dozens of unrelated methods such as `getFoo`, `setFoo`, `getBar`, `setBar`, `cloneBaz`, and `deleteQux` can become difficult for both humans and AI agents.

The design should seek regularity:

```text
Document properties
    inspect/read/update

Named targets
    resolve/inspect
    read properties/content
    update/replace
    clone/instantiate
    remove
```

Concrete convenience methods remain appropriate where they express common intent, but they should sit on coherent underlying semantics.

## 16. Evidence work required before implementation

PRODUCT-01A recommends a dedicated fixture/audit round before any public inspection or block API is designed.

Create LibreOffice-authored fixtures covering:

1. one named section containing multiple paragraphs;
2. named section containing a table;
3. named section containing lists and a frame/text box;
4. nested sections if LibreOffice permits them;
5. bookmark on a single paragraph;
6. bookmark/range spanning multiple paragraphs;
7. bookmark/range around or across a table/list if supported;
8. existing named frame and table controls;
9. duplicate names where LibreOffice permits them;
10. save/reopen/save round trip through LibreOffice.

For each fixture inspect:

- `content.xml` representation;
- `styles.xml` implications;
- identity/name stability;
- nesting;
- allowed child content;
- behavior after clone/delete/replace experiments;
- LibreOffice visual rendering;
- whether the identity is visible and practical for a template author.

## 17. Initial product conclusion

The strongest direction is not "add getters" in isolation.

It is:

> Give the engine a semantic view of the document so the same concepts can be inspected, addressed and then changed predictably.

The desired long-term product loop is:

```text
inspect ODT
    ↓
understand document and named targets
    ↓
read current state
    ↓
set / replace / clone / instantiate / remove
    ↓
validate
    ↓
save editable native ODT
```

Native sections and bookmarks/ranges should be investigated before introducing visible custom block markers. If one of these native mechanisms is stable and author-friendly, it would fit the project's LibreOffice-as-template-designer principle substantially better than a PHPWord-style block syntax.

## 18. Recommended next audit slice

The next step should be evidence rather than API design:

> **PRODUCT-01B — Native ODF Addressability & Block Fixture Audit**

Its purpose is to determine how LibreOffice actually serializes and round-trips named sections, bookmarks/ranges, frames, tables and candidate template blocks, and which of these can safely support later inspect/get/set/replace/clone/remove operations.

No production feature should be implemented until that evidence exists.

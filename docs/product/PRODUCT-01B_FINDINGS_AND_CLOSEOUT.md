# PRODUCT-01B — Native ODF Addressability & Block Fixture Audit — Findings and Closeout

## 1. Status

PRODUCT-01B is complete as an evidence-first product audit.

The audit did not implement public APIs. It established which native ODF structures are promising addressing primitives for future read-modify-write workflows and which semantics must remain type-specific.

The strongest outcome is **Outcome C** from the original decision gate:

> **Named sections and bookmarks/ranges are both useful, but for different roles.**

They should not be collapsed into one artificial universal `Block` abstraction.

## 2. Evidence base

LibreOffice-authored fixtures were created manually to reflect normal template-author behavior rather than synthetic XML generation.

The fixture series covered:

- named section with multiple paragraphs;
- named section with heading and normal paragraphs;
- named section containing a native list;
- named section containing a native table;
- named section containing a text-box/frame;
- named section containing an image/frame and package asset;
- bookmark inside one paragraph;
- bookmark range spanning multiple paragraphs;
- bookmark range spanning a native list;
- bookmark range spanning a native table region;
- combined named section containing heading, paragraph, named table and named image frame;
- manual LibreOffice copy/paste of that combined section to observe clone/identity behavior.

The fixtures showed stable native ODF structures rather than custom marker syntax.

## 3. Named sections — findings

LibreOffice serializes a named section as a real structural container:

```xml
<text:section text:name="example_section">
    ... native block content ...
</text:section>
```

The audited sections successfully contained:

- paragraphs;
- headings;
- native `text:list` structures;
- native `table:table` structures;
- `draw:frame` text boxes;
- `draw:frame` image payloads;
- embedded package image references.

This makes a section substantially more than a textual marker. It is a native, author-visible, named structural object with clear subtree boundaries.

### Product interpretation

A named section is the strongest current candidate for a future **template object / structured template region**.

Potential semantic operations include, without freezing public method names:

```text
inspect section
read section contents
replace section content while preserving the section container
replace complete section object
remove section
clone section
instantiate section as a template instance
```

Sections are especially attractive for reusable visually authored structures such as:

- CV experience entries;
- education entries;
- invoice blocks;
- address blocks;
- report sections;
- reusable layout components.

## 4. Bookmarks/ranges — findings

LibreOffice serializes a bookmark range with native start/end markers:

```xml
<text:bookmark-start text:name="example_range"/>
...
<text:bookmark-end text:name="example_range"/>
```

The fixtures confirmed that bookmark ranges can span:

- text within one paragraph;
- multiple paragraphs;
- native list structures;
- regions that include a native table.

The bookmark is not a container. Its boundaries may be placed inside existing structural elements.

For example, a range may begin inside a paragraph or list-item paragraph and end inside another paragraph/list item while spanning block-level structures between them.

### Critical semantic consequence

A future range operation must not assume that the content between bookmark start/end can safely be treated as one detachable DOM subtree.

Conceptual calls such as:

```php
$template->bookmark('Profile')->replaceContent($richText);
```

are therefore **not automatically safe** merely because the range has a name.

The implementation must understand boundary topology:

- start marker may be inside `text:p`;
- end marker may be inside `text:p` or `text:list-item` content;
- block elements may exist between the two markers;
- replacing arbitrary structured content may require splitting/rebuilding surrounding containers.

This makes bookmark ranges excellent addressing primitives but not generic structural containers.

### Product interpretation

Bookmarks/ranges are the strongest current candidate for a future **named selection / named range** abstraction.

Likely high-value operations include:

```text
inspect range
read text
replace text where structurally safe
read selected content
validate range topology
remove bookmark marker
possibly remove selected content with topology-aware semantics
```

A simple text replacement use case is particularly attractive:

```php
$template->bookmark('FirstName')->replaceText('Walter');
```

The exact API remains undecided, but the addressing model is compelling because no visible template placeholder is required.

## 5. Sections and bookmarks are complementary

The evidence supports the following semantic distinction:

```text
Section
    = named structural object / container

Bookmark
    = named selection / range over existing structure
```

This is stronger than introducing one generic `Block` concept.

A future document model may therefore expose typed addressability such as conceptually:

```text
Document
├── Sections
├── Bookmarks / Named Ranges
├── Tables
├── Frames
├── Images
├── Paragraphs
└── Styles
```

The exact public API is deferred to PRODUCT-01C.

## 6. Existing native object identity remains type-specific

The audit reinforces the ARCH-05 decision that author-facing identity is type-specific.

Observed native identities include:

```text
section      → text:name
bookmark     → text:name on start/end markers
frame        → draw:name
table        → table:name
```

Alternative text/title on a text box was observed as `svg:title`; it is not equivalent to `draw:name` and must not be treated as the same identity field by a future inspector.

Technical identifiers such as `xml:id` remain separate from author-facing template identity.

## 7. Clone / identity experiment

A combined section fixture contained:

- heading;
- paragraph;
- named table;
- named image frame;
- shared embedded image resource.

The section content was manually copied in LibreOffice.

The result was important:

### 7.1 Writer copy/paste does not equal template-instance cloning

LibreOffice copied the content of the section, but did not create a second equivalent named `text:section` container.

This means ordinary Writer copy/paste must not define the engine's future template-instance semantics.

### 7.2 Nested native object names were rewritten

Observed behavior included:

- original table retained its explicit name;
- copied table received a new LibreOffice-generated table name;
- original frame retained its explicit name;
- copied frame received a derived copy name.

The exact generated naming convention is LibreOffice behavior and should not become the engine's API contract.

### 7.3 Embedded image resource was shared

The copied image frame referenced the same package image resource rather than forcing a second binary asset copy.

This shows that clone/instance semantics must distinguish:

- object identity;
- technical identifiers;
- shared immutable resources;
- duplicated mutable structure.

## 8. Section clone / template-instance direction

The audit supports implementing section cloning later as an explicit engine operation rather than imitating Writer copy/paste.

A section is structurally suitable for cloning because it is a real native container.

A future engine can reasonably define deterministic derived names for cloned/instantiated sections and nested named objects.

Conceptually:

```text
experience_entry
    ↓ clone / instantiate
experience_entry_1
experience_entry_2
experience_entry_3
```

The suffix/prefix convention is not decided here.

The important product decision is:

> **The engine should own deterministic clone identity semantics.**

That includes deciding how to handle:

- section names;
- nested table names;
- nested frame names;
- bookmark names inside cloned sections;
- technical IDs;
- style references;
- image/resource references;
- local template placeholders.

## 9. Template authoring implication

The combination of sections and bookmarks enables a powerful authoring model for existing professional documents.

A normal ODT — including one converted from DOCX — can potentially be turned into a template without rebuilding its layout in PHP.

Example:

```text
existing professional CV
        ↓
mark repeatable experience block as named section
        ↓
mark individual scalar fields with bookmarks/ranges
        ↓
inspect document
        ↓
replace named range text
        ↓
instantiate named section for repeated entries
        ↓
save native editable ODT
```

This creates a second authoring style alongside visible `{{variable}}` syntax.

### Visible template-language style

```text
{{first_name}}
{{company}}
```

### Native-addressing style

```text
Bookmark "FirstName"
Section "ExperienceEntry"
Frame "ProfilePhoto"
Table "Skills"
```

Both styles may coexist. Neither should automatically replace the other.

## 10. Document-to-template implication

The audit materially strengthens a previously identified long-term idea:

> Existing documents can potentially be inspected and converted into reusable templates while preserving their visual structure.

A future coding agent could inspect a converted professional document, identify addressable structures, propose bookmark/section names, replace selected content with data bindings where useful, and generate code against the real document rather than reconstructing its layout.

This is strategically relevant to professional CV generation and other document-template products.

## 11. Decision matrix

| Candidate | Authoring UX | Identity | Structural capability | Mutation characteristics | Clone potential | Recommended role |
|---|---|---|---|---|---|---|
| Named section | Good; directly authorable in LibreOffice | native `text:name` | real container; paragraphs, lists, tables, frames, images | subtree/container semantics are clear | Very high | Structured template object |
| Bookmark/range | Good; mark existing content and name it | paired native `text:name` markers | can span heterogeneous existing structures | topology-sensitive because boundaries can sit inside elements | Low/medium as structure; high as address | Named range / selection |
| Named table | Established | native `table:name` | table subtree | table-specific operations | High for rows/table structures | Typed table target |
| Named frame | Established | native `draw:name` | positioned frame/payload | frame/image/text-box specific | Medium/high depending on payload | Typed frame target |

## 12. Rejected direction: one universal Block abstraction

PRODUCT-01B rejects the idea that every named region should be forced into one generic `Block` type.

The native ODF structures have materially different semantics:

```text
Section  → container
Bookmark → range
Table    → tabular object
Frame    → positioned drawing object
```

Regular API design is still desirable, but regularity must not erase native semantics.

## 13. Row-clone status

A dedicated row-clone implementation was not executed in PRODUCT-01B.

The audit nevertheless confirms that row cloning remains a high-value feature and should later be treated as table-specific template-instance behavior rather than being forced through section/bookmark semantics.

Important future questions remain:

- how a template row is identified;
- placeholder/local-value scope;
- merged cells;
- nested lists/images;
- style preservation;
- whether row-local bookmarks are useful;
- deterministic behavior within a named table.

This remains future product/design work, not a blocker for closing PRODUCT-01B.

## 14. Validation/inspection implications

A future inspector should distinguish at least:

```text
SectionDescriptor
RangeDescriptor
TableDescriptor
FrameDescriptor
```

without assuming these exact class names.

For bookmark/range descriptors, inspection should expose boundary/topology information sufficient to answer whether an operation is safe.

For example, diagnostics should be able to distinguish:

```text
range contained inside one paragraph
range spanning sibling paragraphs
range crossing a list
range crossing a table
range boundaries embedded in incompatible containers
```

This is especially important for coding agents, which should not have to infer mutation safety from raw XPath/XML.

## 15. API implication for PRODUCT-01C

PRODUCT-01B intentionally does not freeze method names, but the evidence favors target-oriented APIs over an expanding facade of special-purpose methods.

Conceptually, this is more regular:

```php
$template->bookmark('FirstName')->replaceText('Walter');
```

than a growing collection such as:

```php
$template->replaceBookmarkText(...);
$template->replaceBookmarkContent(...);
$template->deleteBookmark(...);
```

Likewise, section/table/frame operations should be discoverable through typed targets where that produces clearer semantics.

This is a design direction only. PRODUCT-01C must evaluate it against compatibility, ergonomics, AI discoverability, error behavior, and service ownership before public APIs are approved.

## 16. HTML product track remains separate

PRODUCT-01B does not expand the HTML importer.

A professional HTML-to-ODT converter remains a separate product opportunity and should receive its own development/product audit after PRODUCT-01:

> **HTML-ODT-01 — Professional HTML-to-ODT Conversion Product Audit**

The core engine should remain focused on native ODF semantics; a commercial converter may build on the engine without turning the engine itself into a browser layout implementation.

## 17. Final PRODUCT-01B conclusion

The fixture evidence is strong enough to close the native addressability audit.

### Accepted product findings

1. Named ODF sections are viable structured template-object candidates.
2. Bookmarks are viable native named-range/addressing candidates.
3. Sections and bookmarks serve different semantics and should both remain available concepts.
4. Named tables and frames remain typed native targets.
5. A future engine should support read-modify-write through typed addressable targets.
6. Bookmark mutation must be topology-aware; arbitrary structured `replaceContent()` is not automatically safe.
7. Section cloning/template instantiation is promising and should use engine-owned deterministic renaming/identity rules.
8. Ordinary LibreOffice copy/paste is evidence, not the desired clone contract.
9. Shared image resources can remain shared where semantically correct; cloning does not inherently require binary duplication.
10. Native addressability enables a powerful existing-document-to-template workflow.

### Decision gate

**Outcome C — Both are useful for different roles.**

No custom `${BLOCK_x}`-style marker syntax is justified at this stage.

## 18. Next step

The next product-design slice should be:

> **PRODUCT-01C — Addressable Document Model Design**

Its task is to define the semantic developer/AI model that connects:

```text
inspect
    ↓
resolve typed target
    ↓
read capabilities/state
    ↓
perform type-safe mutation
    ↓
validate
```

PRODUCT-01C should design the relationship between sections, named ranges/bookmarks, tables, frames, properties, diagnostics, and future clone/template-instance operations without yet implementing the complete feature set.

PRODUCT-01B is complete.

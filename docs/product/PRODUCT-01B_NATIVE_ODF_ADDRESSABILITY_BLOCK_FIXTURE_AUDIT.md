# PRODUCT-01B — Native ODF Addressability & Block Fixture Audit

## 1. Purpose

PRODUCT-01B is an evidence-first investigation of how a LibreOffice-authored ODT can expose durable, author-friendly identities for document structures that developers and coding agents later need to inspect and mutate.

The central product requirement is not a particular API name such as `cloneBlock()`. It is the ability to author a region visually in LibreOffice, give that region a stable semantic identity, and then perform predictable operations on it from PHP.

Target workflow:

```text
LibreOffice-authored structure
        ↓
native semantic identity
        ↓
inspect / resolve
        ↓
read / set / replace / clone / instantiate / remove
        ↓
validate
        ↓
save editable native ODT
```

PRODUCT-01B does not implement these operations and does not freeze public APIs. It determines which native ODF structures are suitable addressing primitives.

## 2. Questions to answer

The audit must answer, with real LibreOffice-generated fixtures:

1. Can a named ODF section serve as a neutral, reusable template block?
2. Can a bookmark or bookmark range serve as a lighter-weight block/range identity?
3. Which structures can sections and bookmark ranges safely contain or span?
4. Which identity mechanisms survive LibreOffice save/reopen/save round trips?
5. Which mechanisms are practical for a template author to create and name in LibreOffice?
6. What happens to names and boundaries when structures are copied, cloned, replaced, emptied or removed?
7. How should native frame names and table names relate to section/bookmark identity without prematurely creating one global name namespace?
8. What is the most natural native foundation for future equivalents of block clone/replace/delete and row clone/instance operations?

## 3. Existing repository baseline

The current engine already has proven native identity for:

- `draw:frame[@draw:name]`;
- `table:table[@table:name]`.

`TemplateTargetResolver` and `TemplateTarget` provide the current typed resolution foundation.

ARCH-05 deliberately established that native names are type-specific and that technical identifiers such as `xml:id` are not automatically template identity.

PRODUCT-01B must preserve those decisions unless fixture evidence demonstrates a real conflict.

## 4. Candidate A — named ODF sections

Investigate LibreOffice-authored named sections as structured block candidates.

Required fixture cases:

- section containing one paragraph;
- section containing multiple paragraphs;
- section containing headings and paragraphs;
- section containing a list;
- section containing a table;
- section containing a frame/text box;
- section containing an image/frame;
- section containing mixed structures;
- nested sections if LibreOffice supports authoring them;
- empty section if LibreOffice permits it;
- two sibling sections with distinct names;
- duplicate-name attempt and LibreOffice's resulting behavior.

For each case record:

- exact `content.xml` representation;
- relevant attributes, especially identity/name attributes;
- whether additional styles or layout semantics are introduced;
- allowed children;
- nesting behavior;
- name uniqueness behavior;
- authoring ergonomics in LibreOffice;
- visual neutrality or layout side effects.

## 5. Candidate B — bookmarks and bookmark ranges

Investigate both point bookmarks and range bookmarks where LibreOffice distinguishes them.

Required fixture cases:

- bookmark on/in a single paragraph;
- range around text inside one paragraph;
- range spanning multiple paragraphs;
- range spanning heading + paragraph;
- range involving a list;
- range involving a table if LibreOffice permits it;
- range involving a frame/text box if LibreOffice permits it;
- nested bookmarks/ranges;
- overlapping bookmark attempts;
- duplicate-name attempts.

For each case record:

- exact `content.xml` representation;
- start/end marker semantics;
- boundary placement relative to paragraph/table/list elements;
- name stability;
- nesting/overlap rules;
- LibreOffice authoring ergonomics;
- whether the bookmark changes layout or remains semantically neutral.

## 6. Existing native named-object controls

Create or reuse control fixtures for:

- named table;
- named image frame;
- named text-box frame;
- frame containing structured content where available.

Confirm their current ODF representation and compare their identity characteristics with sections/bookmarks.

The audit must not force these target types into one implementation abstraction merely because each has a name.

## 7. Round-trip matrix

Every viable candidate must be tested through at least:

```text
create in LibreOffice
→ save ODT
→ inspect XML
→ reopen in LibreOffice
→ save again
→ inspect XML again
```

Record whether:

- identity/name is preserved;
- structure boundaries are preserved;
- child content remains stable;
- LibreOffice rewrites the structure materially;
- visual output remains equivalent.

Where practical, repeat after a benign edit inside the target to test whether identity survives normal authoring.

## 8. Mutation experiments

PRODUCT-01B is not an implementation slice, but fixture-level XML experiments should determine whether future mutations are structurally safe.

For each viable target type, investigate conceptually and, where safe, with temporary DOM copies:

### Read

- resolve by native identity;
- enumerate target properties;
- inspect child/content boundaries.

### Replace content

- preserve target/container identity;
- replace only contained content;
- verify surrounding layout remains intact.

### Replace object

- replace the complete target/subtree;
- identify consequences for names, styles, references and assets.

### Remove

- remove the target or target range;
- determine whether surrounding paragraphs/structures remain valid;
- identify whether removing a section differs semantically from removing its content.

### Exact clone

- duplicate the native subtree/range as faithfully as possible;
- determine which identities must be regenerated or renamed;
- detect technical IDs/references that cannot safely be duplicated unchanged.

### Template instance

- duplicate a visually authored structure;
- determine how local placeholders could be evaluated independently per instance;
- preserve styles/layout while avoiding identity collisions.

No public API is to be implemented in this audit.

## 9. Block capability criteria

A candidate is suitable as the native foundation for a future template block only if it scores well on all of the following:

| Criterion | Question |
|---|---|
| Native | Is it a normal ODF/LibreOffice structure rather than a custom textual hack? |
| Authorable | Can a normal template author create/name it in LibreOffice? |
| Stable | Does identity survive normal round trips? |
| Structural | Can it represent the required multi-element content? |
| Neutral | Does using it avoid unwanted visual/layout semantics? |
| Addressable | Can it be resolved deterministically? |
| Mutable | Can content/object removal and replacement be defined safely? |
| Cloneable | Can useful clone/instance semantics be defined without corrupting identity/references? |
| Inspectable | Can a developer/agent understand its type, name, contents and capabilities? |

The audit may conclude that no single candidate satisfies every block use case.

## 10. Do not force one universal block type

A likely result is a typed target model rather than one universal `Block` abstraction.

For example:

```text
Named section       → structured container operations
Bookmark range      → range operations
Named table         → table + row operations
Named frame         → frame/image/text-box operations
```

This is acceptable and may be preferable.

The product requirement is regular semantics, not artificial type uniformity.

## 11. Table-row / cloneRow investigation

`cloneRow`-equivalent behavior is explicitly in scope for analysis because it is a high-value template-engine feature.

Create a LibreOffice table fixture containing:

- a named table;
- header row;
- one visually formatted template/data row;
- placeholders in multiple cells;
- mixed text styles;
- optionally a nested list or image where supported;
- merged-cell variant as a separate case.

Investigate candidate row identity approaches:

1. implicit row selected by placeholder/key;
2. bookmark/range identity if LibreOffice permits useful row boundaries;
3. explicit template convention inside a named table;
4. native row subtree cloning without a separate row name.

Compare with existing textual `foreach` semantics.

The desired product capability is conceptually:

```text
find table "InvoiceItems"
→ identify template row
→ instantiate row once per item
→ evaluate row-local values
→ preserve row/cell styles
→ preserve table structure
```

Do not decide the public method name in PRODUCT-01B.

## 12. Identity collision audit

Cloning native structures can duplicate identifiers that were intended to be unique.

Inspect fixtures for at least:

- author-facing names;
- `xml:id` values;
- frame names;
- table names;
- bookmark names;
- section names;
- image/resource references;
- style references;
- any generated LibreOffice identifiers discovered in the fixtures.

Classify each identifier as:

- safe to preserve;
- must be regenerated;
- must be renamed deterministically;
- shared reference that should remain unchanged;
- unresolved / requires later design.

This is necessary before Exact Clone or Template Instance can be considered safe.

## 13. Inspection implications

For every viable target, define the minimum information a future inspector would need to expose, for example:

```text
type
native name
technical identifiers where relevant
location/document part
style references
child/content summary
capabilities supported by that target type
validation warnings
```

Do not build a generalized capability framework yet. Record concrete capabilities by target type first.

## 14. AI-agent implications

The chosen addressing model should allow a coding agent to discover rather than guess document structure.

A future machine-readable inspection should make questions such as these answerable:

```text
Which named structures exist?
Which one is a table, section, bookmark range or frame?
Which structures can be cloned?
Which can have content replaced while preserving layout?
Which contain unresolved placeholders?
Will cloning this target require renaming identities?
```

A marker convention that exists only in prose documentation is weaker for agents than a native structure that can be enumerated deterministically.

## 15. Visual validation

Any fixture mutation experiment that changes document structure must be validated with the established workflow where practical:

```text
ODT / ZIP / XML validation
→ LibreOffice
→ PDF
→ PNG
→ visual review
```

Visual equivalence is especially important when testing whether sections/bookmarks are neutral authoring markers and whether clone/remove experiments disturb surrounding layout.

Fixture outputs and rendered artifacts should remain test/audit artifacts unless a later decision explicitly promotes selected fixtures into repository tests.

## 16. Required audit output

PRODUCT-01B should produce a decision matrix containing at least:

| Candidate | Authoring UX | Identity stability | Structural range | Layout neutrality | Replace | Remove | Clone potential | Recommended role |
|---|---|---|---|---|---|---|---|---|
| Named section | evidence | evidence | evidence | evidence | evidence | evidence | evidence | decision |
| Bookmark/range | evidence | evidence | evidence | evidence | evidence | evidence | evidence | decision |
| Named table | established/evidence | evidence | table | evidence | evidence | evidence | evidence | typed target |
| Named frame | established/evidence | evidence | frame | evidence | evidence | evidence | evidence | typed target |

Also document:

- actual XML snippets or concise structural descriptions;
- LibreOffice authoring steps;
- round-trip findings;
- identity collision findings;
- row-clone findings;
- rejected approaches and why;
- open questions that genuinely require implementation experiments.

## 17. Decision gate

PRODUCT-01B ends with one of these outcomes:

### Outcome A — Sections are suitable template blocks

Proceed later with section inspection/resolution and bounded block operations.

### Outcome B — Bookmark ranges are suitable template blocks

Proceed later with range inspection/resolution and bounded range operations.

### Outcome C — Both are useful for different roles

Keep typed semantics and do not collapse them into one fake universal block.

### Outcome D — Neither is reliable enough

Only then design a custom marker convention comparable in capability to PHPWord block markers.

Custom visible block syntax is therefore the fallback, not the starting assumption.

## 18. Scope exclusions

PRODUCT-01B does not:

- implement parser/getter APIs;
- implement block APIs;
- implement clone/replace/delete public methods;
- implement row cloning;
- redesign `TemplateTargetResolver`;
- implement Style Context or Asset Context;
- implement Document Defaults;
- expand the HTML importer;
- define the commercial HTML-to-ODT product;
- alter current template-language syntax.

## 19. Separate HTML-to-ODT product track

The existing PRODUCT-01 capability matrix originally classified broad HTML/CSS rendering as low strategic fit for the core engine. That statement must not be interpreted as saying that HTML-to-ODT conversion has low product value.

Two distinct ideas must remain separated:

### Core-engine boundary

The ODT Template Engine should not become a browser engine or attempt complete HTML/CSS compatibility as an incidental core feature.

### Separate commercial product opportunity

A professional converter with the product promise:

```text
HTML/CSS
   ↓
semantic conversion
   ↓
native ODF structures/styles/assets
   ↓
high-quality editable ODT
```

may have substantial standalone value and should receive its own development/product audit after PRODUCT-01.

The current `HtmlImporter` is evidence and a useful existing capability, but it is not automatically the architecture or scope of that future commercial product.

This future track should be investigated separately, provisionally as:

> **HTML-ODT-01 — Professional HTML-to-ODT Conversion Product Audit**

Questions such as supported HTML/CSS scope, fidelity tiers, licensing, commercial packaging, API/CLI/service delivery and differentiation belong there, not in PRODUCT-01B.

## 20. Recommended execution

PRODUCT-01B should now be executed as a fixture-based research slice.

The preferred workflow is:

1. create the LibreOffice fixtures manually so they reflect real authoring behavior;
2. archive the original ODT fixtures;
3. inspect `content.xml` and relevant styles/package structures;
4. perform round-trip tests;
5. perform temporary mutation/clone experiments;
6. validate ZIP/XML;
7. visually render representative mutations;
8. write the evidence matrix;
9. only then propose the addressing model for later implementation.

Semantics before implementation remains the governing rule.
# SECTION-02A — Section Content Mutation Semantics & Characterization

## A. Status and purpose

SECTION-02A defines the semantic contract required before `SectionTarget` receives structural mutation operations.

This is a design/characterization slice. It does **not** add `replaceContent()`, `replace()`, `remove()`, `clone()`, or `instantiate()` and does not mutate production ODT output.

The governing distinction is:

```text
SectionTarget::text()
    read-only plain-text projection

Section structural content
    native child ODF nodes inside text:section
```

`SectionTarget::text()` must never be used as the source or serialization format for structural replacement.

The first mutation contract must build on the native section boundary:

```xml
<text:section text:name="ExperienceEntry">
    ... native section children ...
</text:section>
```

A named section is a real ODF container. Mutation may therefore operate on its contained child structure without pretending that a section is a bookmark range or a plain-text block.

## B. Evidence carried forward

This contract builds on:

- PRODUCT-01B native section fixtures and closeout;
- PRODUCT-01C addressable document model;
- ADDRESSABLE-01 inspection/descriptors;
- ADDRESSABLE-02 typed targets;
- SECTION-01 section read operations;
- `StructuredElementMaterializer` and existing `OdtElement`/`RichText` insertion behavior;
- current package/document ownership in `OdtPackage` and `OdtDocumentContext`.

Established facts:

1. `text:section` is a native structural container.
2. Sections may contain paragraphs, headings, lists, tables, frames/text boxes, images, and nested sections.
3. Named tables, frames, bookmarks, and sections may exist inside a section.
4. `SectionTarget` is identity-backed and resolves against the current `OdtDocumentContext`.
5. `SectionTarget::text()` is intentionally lossy and read-only.
6. Existing structured insertion already materializes supported `OdtElement` structures without exposing raw DOM publicly.
7. Style and asset ownership must not be redesigned prematurely in this slice.

## C. Core operation semantics

The future operation family must keep at least these meanings separate:

```text
replaceContent(newContent)
    preserve the existing text:section container and its identity;
    replace only its contained child content.

replace(newObject)
    replace the complete native section object/container itself.

remove()
    remove the section object and its contained content.

clone()
    duplicate section structure according to a structural clone contract.

instantiate(data)
    duplicate a section as a template instance, rewrite identities where needed,
    then evaluate local data/template semantics.
```

SECTION-02A authorizes **none** of these implementations yet. It defines the first `replaceContent()` contract only.

`replaceContent()` is the preferred first mutation because a section provides an unambiguous container boundary and the operation can preserve the author-facing section identity.

## D. First `replaceContent()` contract

Conceptually:

```php
$template->section('Profile')->replaceContent($content);
```

should mean:

1. strictly resolve exactly one valid named section;
2. materialize one explicitly supported replacement content value;
3. validate that the materialized nodes are legal children of the section;
4. prepare any required style/resource dependencies using existing bounded infrastructure;
5. perform an atomic child replacement;
6. preserve the existing `text:section` element and `text:name`;
7. leave surrounding document siblings untouched;
8. keep the section resolvable after mutation;
9. allow repeated replacement.

The operation must not mean:

- replace section identity;
- parse arbitrary XML supplied by the caller;
- interpret HTML;
- use `SectionTarget::text()` as a reversible representation;
- flatten old section content into plain text;
- automatically clone or preserve nested named objects from the old content;
- perform generic DOM surgery outside the section boundary.

## E. Replacement content types

The first implementation should reuse the engine's existing structured-content model rather than inventing a second one.

Candidate accepted inputs must be characterized against current code, especially:

- `Paragraph`;
- `RichText`;
- `RichTable`;
- `ListElement`;
- `ImageElement` where package/resource semantics are already safely reusable;
- bounded collections/sequences of supported block-capable `OdtElement` objects if current materialization semantics already support them.

Do **not** assume every existing `OdtElement` is legal as a direct `text:section` child. Inline-only content may need wrapping in a paragraph and that wrapping policy must not be invented casually.

The first implementation should prefer a small proven green zone rather than accepting arbitrary objects.

### Explicitly deferred inputs

Do not accept in the first slice unless separately characterized:

- raw XML strings;
- `DOMNode`/`DOMElement` supplied publicly;
- HTML;
- arbitrary arrays with implicit conversion;
- complete `text:section` objects passed to `replaceContent()`;
- clone/template-instance descriptors;
- foreign-package resource trees.

## F. Container preservation

For `replaceContent()`, the existing section node survives.

Before:

```xml
<text:section text:name="Profile" text:style-name="Sect1">
    <text:p>Old content</text:p>
</text:section>
```

After conceptual replacement:

```xml
<text:section text:name="Profile" text:style-name="Sect1">
    <text:p>New content</text:p>
</text:section>
```

The operation must preserve all section-level attributes unless the API explicitly authorizes changing them later.

At minimum preserve:

- `text:name`;
- section style/reference attributes;
- protection/visibility/link-related section attributes if present;
- unknown section-level attributes not owned by the replacement operation.

Do not recreate the section merely because replacing children is easier.

## G. Old nested identities

A crucial semantic rule:

> Named objects contained in the **old** section content are part of the removed content. `replaceContent()` does not preserve them automatically.

Example:

```text
Section Profile
├── Bookmark Name
├── Table Skills
└── Frame Photo
```

If the entire section content is replaced, those nested objects disappear unless equivalent objects are explicitly present in the new content.

This is not an identity-rewrite operation and not a merge operation.

After successful replacement:

- old nested target handles re-resolve by identity;
- if their identities no longer exist, existing strict resolution should fail;
- the section target itself remains valid because its container identity survives.

This behavior must be characterized in tests.

## H. New nested identities and collision policy

Replacement content may introduce named sections/tables/frames/bookmarks.

The first implementation must not silently create ambiguous same-type identities elsewhere in the document.

Before committing the DOM mutation, validate newly introduced author-facing names against the current document excluding identities that are being removed as part of the old section content.

Type-specific namespaces remain authoritative:

```text
section  → text:name
bookmark → text:name
table    → table:name
frame    → draw:name
```

A table and section may share the same spelling. Two resulting same-type named targets must not become silently ambiguous.

If collision handling is too broad for the first implementation, the green zone may initially reject replacement content containing named nested objects. Do not invent automatic renaming here; deterministic identity rewriting belongs to clone/instantiate work.

## I. Bookmarks in replacement content

Bookmarks are ranges, not containers. Therefore importing/materializing arbitrary bookmark ranges as part of new section content requires explicit structural correctness.

The first `replaceContent()` implementation should not synthesize bookmark start/end pairs from a high-level shortcut unless existing materialization code already defines such semantics.

Existing valid bookmark structures embedded in supported native/materialized content may be accepted only if they can be proven well-formed and collision-safe.

Do not couple section replacement to `BookmarkMutationService`.

## J. Nested sections

A section may contain another named section.

For replacement content that introduces nested sections:

- the outer target remains the same section;
- nested sections remain independently addressable;
- duplicate section names elsewhere must be detected before mutation;
- no automatic renaming occurs in SECTION-02.

Nested-section cloning/instance semantics remain deferred.

## K. Tables, lists, text boxes, images and frames

### Paragraphs and headings

These are strong first green-zone candidates because they map directly to normal section child content.

### Lists

Native list structures are valid section children and may be accepted if existing materialization produces valid ODF list structures and required styles deterministically.

### Tables

Native tables are valid section children. `RichTable` is therefore a strong candidate if current structured materialization can insert it safely without a placeholder-specific assumption.

### Frames / text boxes / images

These require more caution because package assets, manifest entries, frame styles, and image-resource copying may be involved.

The first implementation should reuse existing resource/style preparation only if it is already bounded and document-scoped through current package/context ownership.

Do not introduce `AssetContext` or `StyleContext` in SECTION-02.

If image/frame preparation cannot be reused cleanly outside `setElement()`, defer those replacement content types rather than duplicating resource logic.

## L. Relationship to `StructuredElementMaterializer`

Existing `StructuredElementMaterializer` is the first component to evaluate for reuse.

However, placeholder replacement and section-child replacement are not identical operations.

The implementation must separate:

```text
materialize supported OdtElement into native nodes/resources
```

from:

```text
choose where/how those nodes replace existing document structure
```

A useful architecture would allow section mutation to reuse materialization while owning its own section-boundary replacement semantics.

Do not make `StructuredElementMaterializer` responsible for resolving named sections or deciding clone/identity policy.

If its current API is too coupled to placeholder replacement, characterize the coupling before extracting a reusable bounded materialization path.

## M. Relationship to `setElement()`

`setElement()` remains the existing placeholder-oriented constructed-content API.

Future section replacement is a different authoring model:

```text
setElement('placeholder', $element)
    replace a template placeholder with constructed content

section('Profile')->replaceContent($element)
    replace contents of a native named structural container
```

They may share lower-level materialization services, but neither API replaces the other.

No existing `setElement()` semantics should change as a side effect of SECTION-02.

## N. Text replacement versus structural replacement

Do not overload section `replaceContent()` to accept a string and infer a structure without an explicit contract.

Possible future convenience methods may include:

```text
replaceText(...)
replaceContent(...)
```

but SECTION-02A does not approve `replaceText()` on sections.

A string is not equivalent to a section's native child structure. If the first implementation accepts strings at all, it must explicitly define whether they become one paragraph and how whitespace is represented. The safer initial choice is to require structured content.

## O. Atomic mutation requirement

Section replacement must be atomic at the semantic level.

All of the following should be completed before deleting old children where practical:

1. strict target resolution;
2. replacement input validation;
3. materialization to detached/importable nodes;
4. style/resource preparation validation;
5. nested identity collision validation;
6. child legality validation.

If any validation fails, the original section content and package state must remain unchanged.

Resource preparation complicates atomicity. If current image/style preparation mutates package state eagerly, image/frame content may need to remain deferred until a transactional/rollback-safe path is characterized.

Do not claim atomic behavior for resource-bearing replacement unless tests prove it.

## P. Failure model

Existing ADDRESSABLE exceptions remain responsible for:

- target not found;
- ambiguous target;
- malformed identity where relevant.

A valid section may reject content replacement for a different reason. SECTION-02 implementation should evaluate a small typed exception such as conceptually:

```text
SectionMutationException
```

with structured reason values for cases such as:

- unsupported content type;
- invalid section child structure;
- nested identity collision;
- materialization failure;
- unsupported resource-bearing content;
- atomic preparation failure.

Exact class/code names are deferred to implementation evidence.

## Q. Lifecycle semantics

`SectionTarget` is identity-backed.

Therefore:

```php
$section = $template->section('Profile');
$section->replaceContent(...);
$section->descriptor();
```

should report current content while retaining the same outer section identity.

Repeated replacement should be supported when each input is valid.

After `load()`/`refresh()`, the handle resolves the current document's same-named section, consistent with ADDRESSABLE-02.

If the section no longer exists, strict resolution fails before mutation.

## R. Render/template-language sequencing

Section replacement is direct native document mutation. It must not implicitly invoke `TemplateProcessor`.

Replacement content may itself contain visible template syntax such as `{{name}}` if the structured content API permits literal text containing that syntax.

Then sequencing is explicit:

```text
replaceContent(structure containing {{name}})
    ↓
render()
    ↓
TemplateProcessor processes remaining visible syntax
```

SECTION-02 must characterize this sequencing but must not merge the section mutation service with `TemplateProcessor`.

## S. Characterization matrix for SECTION-02B

Before production mutation code, characterize at least:

1. replace one-paragraph section with one `Paragraph`;
2. replace multiple paragraphs with `RichText`/supported block collection;
3. replace heading + paragraph;
4. replace with native list content;
5. replace with `RichTable`;
6. replace with mixed paragraph/list/table content if supported by existing model;
7. empty replacement semantics;
8. section with section-level style/protection attributes — attributes preserved;
9. replacement removes old nested bookmark/table/frame identities;
10. replacement introduces nested named targets without collision;
11. replacement introduces duplicate same-type identity — reject atomically;
12. nested section replacement content;
13. replacement content containing `{{variable}}`, then render();
14. repeated replacement;
15. replace → inspect/text()/nestedNamedObjects();
16. replace → save → reopen;
17. target obtained before load()/refresh();
18. unsupported content type rejection;
19. direct inline-only element characterization;
20. resource-bearing image/frame content characterization, but defer implementation if atomic package semantics are not proven.

## T. Empty replacement semantics

The implementation must explicitly decide whether empty replacement is legal.

If legal:

```xml
<text:section text:name="Profile"/>
```

or an equivalent empty native container should remain resolvable.

Do not automatically insert an empty paragraph merely because Writer commonly presents one visually unless ODF/LibreOffice evidence requires it for a valid/stable section.

This requires characterization through save/reopen/LibreOffice.

## U. Visual and package validation

Once mutation exists, successful section replacements require:

- ZIP/package validation;
- XML well-formedness;
- save/reopen stability;
- section identity preservation;
- `inspect()`/`text()` consistency;
- focused LibreOffice visual regression for representative structural cases.

At minimum visual cases should eventually cover:

- paragraph replacement;
- styled paragraph/heading replacement;
- list replacement;
- table replacement;
- mixed content if implemented.

Visual validation is especially important because XML validity does not prove layout preservation outside the section.

## V. Public API direction

If SECTION-02B proves a bounded safe green zone, the preferred target-oriented API remains conceptually:

```php
$template->section('Profile')->replaceContent($content);
```

This is preferable to adding facade methods such as:

```php
$template->replaceSectionContent('Profile', $content);
```

because ADDRESSABLE-02 already established typed target handles.

The exact accepted `$content` types must be derived from characterization and documented precisely.

No `replace()` or `remove()` should be added in the same implementation slice unless separately contracted.

## W. Architecture boundary

Preferred shape:

```text
SectionTarget
    typed public operation + identity
        ↓
small SectionMutationService
    validates/materializes/replaces bounded section content
        ↓
existing structured materialization/resource services where safe
        ↓
OdtDocumentContext / OdtPackage
    authoritative DOM and package/resource ownership
```

`SectionTarget` must not become a large XML algorithm class.

`SectionReader` remains read-only.
`DocumentInspector` remains read-only.
`TemplateProcessor` remains template-language owner.
`StructuredElementMaterializer` remains structured-content materialization infrastructure rather than section identity/resolution owner.

## X. Explicit non-goals

SECTION-02A/02B must not implement or redesign:

- whole-section `replace()`;
- section `remove()`;
- clone;
- instantiate;
- automatic nested identity renaming;
- row cloning;
- bookmark range editing;
- arbitrary XML insertion;
- HTML import expansion;
- Style Context;
- Asset Context;
- Document Defaults;
- TEMPLATE-FORMAT-PRESERVATION-01;
- public DOM/XPath APIs.

## Y. Recommended implementation gate

Proceed to implementation only after characterization proves a small green zone with these properties:

1. outer section node and attributes survive unchanged;
2. new child structure is valid native ODF;
3. old child structure is completely removed;
4. section remains addressable;
5. `text()` and descriptor data reflect the new structure;
6. repeated replacement is deterministic;
7. unsupported input fails before destructive mutation;
8. nested identity behavior is explicit;
9. package/resource side effects are either proven atomic or excluded from the first implementation;
10. save/reopen retains the result.

The likely first green zone is paragraphs/headings/lists/tables and other resource-free structured content already supported by the current materializer. Image/frame support should be added only if existing package-resource preparation can be reused without weakening atomicity.

## Z. Recommendation

The next slice should be:

> **SECTION-02B — Safe Section Content Replacement Implementation**

It should begin with characterization tests from this contract, determine the exact accepted `OdtElement`/structured-content input set, then implement only the proven green zone.

Do not broaden into whole-section replacement, removal, cloning, instantiation, or automatic identity rewriting in SECTION-02B.

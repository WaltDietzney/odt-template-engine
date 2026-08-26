# NAMED-RANGE-01A — Bookmark Text Replacement Semantics & Fixture Characterization

## A. Status and purpose

NAMED-RANGE-01A defines the semantic contract that must exist before
`BookmarkTarget` receives its first mutation operation.

This is intentionally a design/characterization slice. It does **not** add
`replaceText()`, mutate ODT XML, change public APIs, or attempt general range
editing.

The motivating API remains:

```php
$template->bookmark('FirstName')->replaceText('Walter');
```

The apparent simplicity of that call hides an important ODF fact established
by PRODUCT-01B: bookmarks are ranges, not containers. Their start/end markers
can occur inside paragraphs, spans, list-item paragraphs, table cells, or
across heterogeneous block structures. Therefore the engine must define which
range shapes can be changed without guessing how ODF structure or formatting
should be rewritten.

The governing rule for the first mutation slice is:

> A bookmark text replacement is allowed only where the engine can prove a
> deterministic text-flow mutation that preserves the surrounding native ODF
> structure. Unsupported topology must fail explicitly rather than normalize,
> flatten, split, or rebuild document structure heuristically.

## B. Evidence carried forward

This contract builds on:

- PRODUCT-01B native ODF addressability fixtures and closeout;
- PRODUCT-01C addressable document model;
- ADDRESSABLE-01 inspection/descriptors;
- ADDRESSABLE-02 typed resolution boundary;
- the current `BookmarkDescriptor` topology classifications;
- the current identity-backed `BookmarkTarget`.

The relevant established facts are:

1. A bookmark may be a collapsed `<text:bookmark/>` or a paired
   `<text:bookmark-start/>` / `<text:bookmark-end/>` range.
2. Paired markers do not create a detachable subtree.
3. Bookmark boundaries may be embedded in paragraph/list/table structure.
4. `BookmarkDescriptor::text()` is a conservative textual snapshot only; it is
   not a structured-content representation.
5. Malformed bookmarks remain inspectable but cannot resolve to a typed target.
6. ADDRESSABLE-02 targets are identity-backed and re-resolve current document
   state rather than retaining DOM nodes.

## C. Operation semantics

The first write operation should mean exactly:

```text
replaceText(value)
    replace the textual selection represented by one valid paired bookmark
    while preserving the bookmark identity and all surrounding document
    structure that the operation is not explicitly authorized to rewrite
```

This is **not** equivalent to:

- replacing a section/container;
- replacing arbitrary structured content;
- deleting arbitrary nodes between two markers;
- flattening all selected spans into plain text;
- rebuilding a paragraph/list/table region;
- interpreting the replacement value as ODF/XML or RichText;
- applying template processing implicitly.

After a successful replacement the same bookmark name should remain
addressable unless a later API explicitly defines marker removal.

`replaceText()` and future `replaceContent()` therefore remain separate
capabilities.

## D. Formatting preservation principle

For a template engine, replacing visible placeholder text should normally keep
the formatting supplied by the template.

Example:

```xml
<text:span text:style-name="Strong">
    <text:bookmark-start text:name="FirstName"/>Max<text:bookmark-end text:name="FirstName"/>
</text:span>
```

Conceptually:

```php
$template->bookmark('FirstName')->replaceText('Walter');
```

should leave the bookmark and the `Strong` span in place and replace only the
selected text.

This does **not** imply a general formatting-preservation algorithm. The first
mutation contract should preserve formatting structurally by changing text
inside an already unambiguous text run/context. It should not infer which of
multiple competing styles should be applied to a replacement string.

This is deliberately narrower than TEMPLATE-FORMAT-PRESERVATION-01. That
future topic must not be pulled wholesale into NAMED-RANGE-01.

## E. Why `inline` alone is not sufficient

ADDRESSABLE-01 currently classifies a bookmark as `inline` when both markers
are within the same `text:p` or `text:h`.

That classification is useful for document understanding, but it is too broad
by itself to authorize mutation.

All of these may be `inline` at paragraph level while having different write
semantics:

```xml
<!-- one plain run -->
<text:p>Dear <text:bookmark-start text:name="Name"/>Max<text:bookmark-end text:name="Name"/>,</text:p>

<!-- one styled run -->
<text:p><text:span text:style-name="A"><text:bookmark-start text:name="Name"/>Max<text:bookmark-end text:name="Name"/></text:span></text:p>

<!-- range contains one complete styled span -->
<text:p><text:bookmark-start text:name="Name"/><text:span text:style-name="A">Max</text:span><text:bookmark-end text:name="Name"/></text:p>

<!-- competing formatting contexts -->
<text:p><text:bookmark-start text:name="Name"/><text:span text:style-name="A">Max</text:span><text:span text:style-name="B"> Mustermann</text:span><text:bookmark-end text:name="Name"/></text:p>
```

The mutation slice therefore needs a finer **replacement safety profile** in
addition to the existing broad topology descriptor.

## F. Replacement safety profile

NAMED-RANGE-01B should characterize a small internal safety classification for
text replacement. Exact implementation names are not frozen here.

Conceptually:

```text
SINGLE_TEXT_CONTEXT
    paired markers select text in one unambiguous text formatting context

SINGLE_WRAPPED_RUN
    paired markers surround one complete inline formatting run whose wrapper
    can remain structurally intact

MULTI_STYLE_INLINE
    selected text crosses multiple independently styled inline runs

EMPTY_RANGE
    paired markers contain no selected text

COLLAPSED_POINT
    native text:bookmark insertion point rather than a paired selection

BLOCK_SPANNING
    paragraph/list/table/mixed structural range

MALFORMED
    invalid/unpaired/ambiguous marker identity
```

The purpose is not to expose a permanent public enum immediately. It is to
make authorization of the first write operation explicit and testable.

## G. Initial safety matrix

The following matrix is the recommended contract to test before implementation:

| Range shape | First `replaceText()` contract | Reason |
|---|---|---|
| Paired range, plain text, one parent text context | **YES** | Deterministic text-only mutation |
| Paired range wholly inside one styled span | **YES** | Existing wrapper/style can remain unchanged |
| Paired range surrounding exactly one styled span | **CONDITIONAL** | Likely safe if wrapper is retained and only its selected textual payload changes; characterize LibreOffice output first |
| Paired range crossing multiple spans with identical style/context | **DEFER initially** | Structural normalization policy is not yet defined |
| Paired range crossing spans with different styles | **NO initially** | No deterministic answer to which style the new text should inherit |
| Empty paired range | **DEFER** | Semantically closer to insertion than replacement |
| Collapsed `<text:bookmark/>` | **NO for `replaceText()`** | It selects no text; future `insertText()` semantics may be appropriate |
| Paragraph-spanning range | **NO initially** | Replacement would require block/text-flow policy |
| List-spanning range | **NO** | May require list-item/container surgery |
| Table-spanning range | **NO** | May cross cell/table boundaries |
| Mixed-block range | **NO** | Arbitrary structured mutation |
| Malformed/ambiguous bookmark | **NO** | Typed resolution already rejects invalid identity |

The green zone should remain intentionally small for the first implementation.
A later characterization can expand it without weakening the original safety
contract.

## H. Important semantic distinction: selection versus insertion point

A paired bookmark range and a collapsed bookmark are different editing
concepts.

```text
paired bookmark
    identifies selected content
    candidate for replaceText()

collapsed bookmark
    identifies one insertion point
    candidate for a future insertText() operation
```

The first mutation slice must not make a collapsed bookmark behave as though it
selected hidden text. If `replaceText()` is called on a collapsed bookmark, it
should fail with a deterministic unsupported-operation/topology error unless a
later contract deliberately changes that API meaning.

This distinction is especially useful to developers and coding agents because
it removes an otherwise surprising overload of the word "replace".

## I. Multi-style ranges

A range such as:

```xml
<text:bookmark-start text:name="Name"/>
<text:span text:style-name="GivenName">Max</text:span>
<text:span text:style-name="Surname"> Mustermann</text:span>
<text:bookmark-end text:name="Name"/>
```

must not silently become:

```xml
<text:span text:style-name="GivenName">Walter Dietz</text:span>
```

or plain paragraph text merely because either transformation is easy to
implement.

Possible future policies include preserving the first run, preserving multiple
runs by a mapping rule, accepting `RichText`, or requiring an explicit style.
None is semantically obvious. Therefore multi-style replacement remains
outside the first `replaceText()` contract.

## J. Existing variables inside bookmarks

A bookmark may select text that itself contains existing template syntax, for
example:

```text
{{firstName}}
```

This does not change bookmark replacement semantics.

A future call such as:

```php
$template->bookmark('FirstName')->replaceText('Walter');
```

should be understood as direct document mutation of that named range. It should
not implicitly invoke or reinterpret `TemplateProcessor`.

The interaction with later `render()` must be characterized in implementation
tests: if direct bookmark mutation removes a placeholder before render, the
placeholder is naturally no longer available for template-language processing.
This is sequencing, not a reason to couple the bookmark mutation service to
`TemplateProcessor`.

## K. Marker preservation

For the first paired-range replacement contract, bookmark markers should
survive the operation.

Conceptually:

```xml
<text:bookmark-start text:name="FirstName"/>Max<text:bookmark-end text:name="FirstName"/>
```

becomes:

```xml
<text:bookmark-start text:name="FirstName"/>Walter<text:bookmark-end text:name="FirstName"/>
```

This preserves:

- native bookmark identity;
- repeated addressability;
- inspect → mutate → inspect workflows;
- deterministic behavior for agents.

Removing bookmark markers is a separate future operation and must not be an
implicit side effect of `replaceText()`.

## L. Replacement value semantics

The first `replaceText(string $value)` operation should treat its argument as
literal text.

It must not interpret:

- XML markup;
- HTML;
- ODF fragments;
- RichText;
- template syntax as an instruction during the replacement itself.

Characters requiring XML escaping must be represented safely by DOM text-node
semantics.

Newline handling must be characterized rather than guessed. A PHP string
containing `\n` does not automatically define whether the desired ODF result is
`text:line-break`, a new paragraph, literal whitespace, or something else.
Therefore multiline replacement should be explicitly rejected or deferred in
the first implementation unless existing engine semantics provide a clearly
reusable, compatible rule.

## M. Whitespace semantics

ODF whitespace can require structural representation such as `text:s` for
significant repeated spaces. The first implementation must not silently assume
that assigning arbitrary `nodeValue` always preserves visible whitespace.

Characterization must include at least:

- ordinary single spaces;
- leading/trailing spaces inside the bookmarked selection;
- repeated spaces;
- tabs if accepted;
- line breaks/newlines.

If faithful representation is not already supported by an existing bounded
helper, the first mutation contract should restrict accepted replacement values
rather than introduce a broad text-normalization subsystem in this slice.

## N. Fixture matrix for NAMED-RANGE-01B

Before production mutation code, create or characterize minimal native ODT/XML
fixtures for at least:

1. **plain inline range** — markers and text directly in one `text:p`;
2. **inside one styled span** — markers and text share one `text:span`;
3. **around one styled span** — bookmark contains exactly one complete span;
4. **partial styled span boundary** — one marker inside a span and the other
   outside it;
5. **multiple spans, same style**;
6. **multiple spans, different styles**;
7. **empty paired range**;
8. **collapsed bookmark**;
9. **paragraph-spanning range**;
10. **list-item/list-spanning range**;
11. **table-cell/table-spanning range**;
12. **mixed-block range**;
13. **bookmark selecting an existing `{{variable}}`**;
14. **special XML characters** — `&`, `<`, `>`;
15. **significant whitespace** — leading/trailing/repeated spaces;
16. **newline/tab input contract**;
17. **repeated replacement of the same bookmark**;
18. **replacement followed by inspect()**;
19. **replacement followed by render()/save()/reopen**;
20. **load()/refresh() after obtaining the identity-backed BookmarkTarget**.

Where possible, use real LibreOffice-authored fixtures or compare synthetic XML
against LibreOffice-authored structure. The purpose is to characterize native
ODF behavior, not merely prove that a DOM manipulation is technically
possible.

## O. What the characterization must observe

For each candidate green-zone fixture, NAMED-RANGE-01B should verify:

1. bookmark markers remain present and paired;
2. bookmark identity remains unchanged;
3. `inspect()` returns the new text;
4. broad bookmark topology remains valid;
5. surrounding paragraph/heading structure remains unchanged;
6. surrounding style wrappers remain unchanged where preservation is claimed;
7. unrelated sibling content remains byte/structure-equivalent where practical;
8. content.xml remains well-formed;
9. the ODT package remains valid;
10. save/reopen retains the mutation;
11. repeated replacement is deterministic;
12. unsupported topologies fail before mutation and leave the DOM unchanged.

For visual formatting claims, a LibreOffice regression is required once
production mutation exists. XML validity alone cannot prove appearance
preservation.

## P. Error semantics for the first mutation slice

The current typed resolution exceptions remain responsible for:

- target not found;
- ambiguous target;
- malformed target.

A valid BookmarkTarget may nevertheless reject `replaceText()` because its
range is not safe for the operation. That is a different failure category.

NAMED-RANGE-01B should therefore evaluate a small typed mutation exception,
conceptually something like:

```text
UnsafeRangeMutationException
or
UnsupportedBookmarkOperationException
```

The exact name is deferred to implementation evidence.

The exception should expose enough structured information for an agent to know:

- bookmark name;
- operation requested;
- topology/safety reason.

Do not make callers parse an English error string to distinguish a malformed
bookmark from a valid but unsupported range.

## Q. Implementation boundary

The future mutation implementation should not be placed directly as complex
DOM surgery inside `BookmarkTarget`.

Preferred responsibility shape:

```text
BookmarkTarget
    typed public operation and identity
        ↓
small named-range mutation service
    validates current range/safety
    performs bounded text mutation
        ↓
OdtDocumentContext
    authoritative DOM
```

The exact service name is not frozen. The important boundary is that
`BookmarkTarget` remains a typed API handle rather than becoming a large XML
algorithm class.

`DocumentInspector` remains read-only and must not become the mutation engine.
`TemplateProcessor` remains owner of the visible template language and must not
become the bookmark range editor.

## R. Relationship to TEMPLATE-FORMAT-PRESERVATION-01

NAMED-RANGE-01A deliberately adopts one narrow preservation rule:

> When replacement is authorized because the selection has one unambiguous
> existing text formatting context, preserve that surrounding context instead
> of rebuilding it.

This does not resolve the broader TEMPLATE-FORMAT-PRESERVATION-01 topic.

Still deferred are questions such as:

- mapping arbitrary replacement text across multiple styled runs;
- effective/inherited style resolution;
- replacing mixed inline content while retaining semantic formatting;
- preserving arbitrary field, hyperlink, annotation, or change-tracking
  structures;
- structured RichText replacement in named ranges.

## S. Public API recommendation

If NAMED-RANGE-01B confirms the green-zone behavior, the preferred first public
mutation remains:

```php
$template->bookmark('FirstName')->replaceText('Walter');
```

This is superior to adding another facade method such as:

```php
$template->replaceBookmarkText('FirstName', 'Walter');
```

because ADDRESSABLE-02 already established typed targets and future bookmark-
specific operations belong naturally on `BookmarkTarget`.

No `replaceContent()` should be added at the same time.

No collapsed-bookmark insertion should be hidden inside `replaceText()`.

## T. Recommended implementation gate

`replaceText()` should not be implemented until NAMED-RANGE-01B has proven at
least these green-zone cases:

```text
1. plain inline selected text
2. selected text wholly inside one styled span/context
3. literal XML-special characters
4. marker preservation
5. repeated replacement
6. inspect after mutation
7. save/reopen lifecycle
8. deterministic rejection with no DOM change for unsupported topology
```

The "bookmark surrounding one complete styled span" case may join the green
zone only if fixture evidence shows that retaining the wrapper gives a simple,
deterministic mutation without hidden structural normalization.

## U. Deferred capabilities

Explicitly deferred:

- arbitrary structured range replacement;
- `replaceContent(RichText)` for bookmarks;
- range deletion/clear semantics;
- marker deletion;
- collapsed-bookmark insertion;
- paragraph-spanning replacement;
- list/table/mixed-block replacement;
- multi-style text distribution;
- HTML/ODF fragment replacement;
- broad formatting-preservation work;
- section mutation/cloning;
- table-row cloning;
- frame mutation.

## V. Recommendation

Proceed with:

> **NAMED-RANGE-01B — Safe Bookmark Text Replacement Implementation**

but make its first step fixture/characterization tests for the green-zone and
boundary cases above.

The implementation should then add only the behavior proven safe by those
tests. The desired first end-state is intentionally modest but valuable:

```php
$template->bookmark('FirstName')->replaceText('Walter');
```

for a well-characterized inline text selection, with the native bookmark and
template formatting preserved, while complex ranges fail predictably and leave
the document untouched.

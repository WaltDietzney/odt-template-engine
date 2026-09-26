# NAMED-RANGE-01B — Safe Bookmark Text Replacement

## Status

Implemented as a deliberately bounded first bookmark mutation. The public
operation is:

```php
$template->bookmark('FirstName')->replaceText('Walter');
```

This slice does not introduce general range editing, structured replacement,
or bookmark insertion semantics.

## Evidence and characterized shapes

The implementation is based on NAMED-RANGE-01A, PRODUCT-01B, the
ADDRESSABLE-01/02 inspection and typed-resolution slices, and the current
`OdtDocumentContext` lifecycle. Focused tests characterize plain inline ranges,
ranges inside one `text:span`, ranges surrounding an inline span, empty and
collapsed bookmarks, multi-span and block-spanning ranges, literal special
characters, whitespace, repeated replacement, inspection, render, and
save/reopen behavior.

The tests use native DOM shapes corresponding to the PRODUCT-01B fixtures. A
range is not treated as a detachable DOM container: its two marker elements
remain in their original parent and only selected direct text nodes may be
replaced.

## Accepted safety profile

`replaceText()` accepts a non-empty paired range when:

1. exactly one start and one end marker exist for the resolved name;
2. both markers have the same immediate parent;
3. the parent is a `text:p`, `text:h`, or one existing `text:span`;
4. every node between the markers is a direct XML text node; and
5. the replacement is single-line, tab-free, and has no leading, trailing, or
   repeated spaces.

The second accepted shape is therefore a bookmark wholly inside one existing
span. The span is retained; no style is inferred or rewritten. The operation
also accepts an empty replacement value, which removes selected text while
leaving both markers in place.

## Rejected shapes

The following are rejected with a typed `BookmarkMutationException` before the
DOM is changed:

- empty paired ranges (insertion semantics are deferred);
- collapsed `text:bookmark` points;
- ranges surrounding or crossing an element such as a span;
- multiple inline spans, whether styles are equal or different;
- partial span boundaries;
- paragraph-, list-, table-, or mixed-block ranges;
- newline or tab input;
- leading, trailing, or repeated spaces.

These restrictions are intentional. `inline` in the inspection descriptor is
an addressability classification, not sufficient authorization for mutation.
Complex ranges remain inspectable and can be resolved where ADDRESSABLE-02
permits, but `replaceText()` refuses them.

## Marker and formatting preservation

Mutation removes only the direct text nodes selected between the markers and
inserts one literal text node immediately before the end marker. Start and end
markers, their `text:name`, their parent, and any existing surrounding span
remain unchanged. XML-special characters are supplied through DOM text-node
semantics and serialize as escaped XML rather than markup.

The implementation does not flatten spans, create styles, or invoke
`TemplateProcessor`.

## Replacement value contract

Values are literal text. XML, HTML, ODF fragments, RichText, and template
syntax are not interpreted. Newline and tab semantics are deliberately not
guessed. Ordinary single-space-free text is supported; leading/trailing and
repeated spaces are rejected because this bounded operation does not provide
an ODF whitespace serializer for `text:s` semantics.

## Error semantics and atomicity

Existing typed resolution errors remain responsible for missing, ambiguous,
and malformed identities. A valid but unsafe bookmark mutation raises
`BookmarkMutationException`, exposing:

- `bookmarkName()`;
- `operation()`;
- `topology()`; and
- machine-readable `reason()`.

Validation completes before any selected node is removed. Tests compare the
serialized DOM and the inspection snapshot after every represented rejection,
demonstrating that markers, text, and surrounding structure are unchanged.

## Ownership and lifecycle

`BookmarkTarget` remains the typed public handle. It delegates DOM work to
`BookmarkMutationService`; `DocumentInspector` remains read-only and
`TemplateProcessor` remains independent. The service receives the current
`OdtDocumentContext` and stores no DOM node references.

Targets remain identity-backed. The target re-resolves its descriptor and the
mutation service locates markers in the current context at operation time.
Consequently repeated replacement works, a target can operate after document
context replacement when the named bookmark still exists, and a missing
bookmark continues to fail through the existing strict resolver.

`replaceText()` returns the same `BookmarkTarget`, allowing a second operation
without creating a new handle. After save and reopen, the bookmark remains
addressable and reports the replacement text.

## Template processing sequencing

Bookmark replacement is direct document mutation and does not call
`TemplateProcessor`. If the selected text is `{{firstName}}`, replacing it
with `Walter` removes that placeholder before a later `render()` call. Any
template syntax remaining elsewhere is still processed normally by the
template engine.

## Validation

The focused suite covers:

- plain and styled safe ranges;
- marker and wrapper preservation;
- XML-special characters;
- supported/rejected whitespace;
- repeated replacement and inspection;
- collapsed, empty, multi-style, partial-span, block, list, table, and mixed
  rejection;
- atomic failure;
- template-processing sequencing;
- current-context lifecycle behavior;
- package save/reopen and native bookmark persistence.

The full repository test suite and package/XML checks are part of the slice
preflight. Visual regression is required for the formatting claim when the
LibreOffice environment permits it; the established baseline must not be
rewritten. In this implementation run, the focused suite passed with 18
tests and 82 assertions, and the full suite passed with 142 tests and 998
assertions (one existing PHPUnit deprecation notice). `composer validate`,
recursive PHP lint, and `git diff --check` also passed. The local environment
does not provide `zensical`, so no documentation build was claimed. This
slice did not change any tracked sample output or visual baseline. The
project renderer was also attempted against a temporary mutated ODT, but
LibreOffice produced no PDF after `failed to launch javaldx` and a read-only
dconf-path error; therefore visual equivalence is environment-blocked, not
claimed.

## Deferred capabilities

This slice intentionally does not implement `replaceContent()`, `remove()`,
`clear()`, collapsed-bookmark insertion, paragraph/list/table/mixed mutation,
multi-style replacement, RichText/HTML/ODF replacement, generic range editing,
or the broader TEMPLATE-FORMAT-PRESERVATION-01, Style Context, and Asset
Context work.

The next named-range work should be chosen only after reviewing this narrow
contract and its implementation evidence; likely follow-up areas are an
explicit insertion contract for collapsed bookmarks or a separately bounded
topology-safe range capability, not an automatic expansion of
`replaceText()`.

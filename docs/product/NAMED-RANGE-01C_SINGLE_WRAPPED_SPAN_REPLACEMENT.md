# NAMED-RANGE-01C — Single Wrapped Span Replacement

## A. Motivation and real LibreOffice evidence

NAMED-RANGE-01B intentionally accepted only paired bookmarks whose selected
content consisted of direct text nodes in one text context. While preparing
public Sample 22, ordinary LibreOffice authoring produced a practically
important additional shape: the author formatted `Software Developer`, then
created the `Position` bookmark around the selection.

LibreOffice authored the native structure as:

```xml
<text:bookmark-start text:name="Position"/>
<text:span text:style-name="T1">Software Developer</text:span>
<text:bookmark-end text:name="Position"/>
```

This is not a synthetic convenience structure and is useful for the
ODT-as-template workflow.

## B. Characterization result

The following neighboring shapes remain rejected:

- two sibling spans, even with identical styles;
- plain text plus a span;
- nested spans or other nested elements;
- a marker beginning or ending inside a span;
- empty wrapped spans;
- block-spanning, list-spanning, table-spanning, and mixed ranges.

Focused tests prove that exactly one complete `text:span` between the paired
markers is text-only and can be changed without moving or rebuilding the
wrapper. The existing `text:style-name` and arbitrary span attributes remain
intact.

## C. Accepted single-wrapped-span contract

`replaceText()` now accepts a `SINGLE_WRAPPED_SPAN` profile when:

1. the bookmark has exactly one paired start/end;
2. both markers share the same immediate parent;
3. there is exactly one node between them;
4. that node is `text:span`;
5. the span contains one or more direct text nodes only; and
6. the replacement value satisfies the unchanged NAMED-RANGE-01B literal
   value rules.

The span is complete inside the bookmark. It is not interpreted as a generic
range container and no nested formatting policy is introduced.

## D. Mutation and preservation semantics

The existing span element is retained and its direct text-node children are
replaced with one literal text node. The operation preserves:

- bookmark start/end markers and `text:name`;
- the existing `text:span` node;
- `text:style-name` and unrelated span attributes;
- the containing paragraph and unrelated siblings.

No styles are registered or inferred, no markers are moved, and
`TemplateProcessor` is not invoked. This is structural wrapper preservation,
not the broader TEMPLATE-FORMAT-PRESERVATION-01 feature.

## E. Atomic failure

All validation, including the wrapped-span child-shape check and replacement
value check, happens before DOM mutation. Unsupported neighboring structures
therefore retain byte-equivalent serialized XML in the focused tests.

## F. Lifecycle and ownership

`BookmarkTarget` remains the public typed handle. `BookmarkMutationService`
owns the bounded mutation algorithm and uses the current
`OdtDocumentContext`; it stores no DOM references. The same identity-backed
target can be used for repeated replacement and after a current-context
replacement when the bookmark still exists.

Save/reopen validation against the real Sample-22 template confirms that all
three bookmarks remain addressable and that the wrapped `Position` span
retains its style while reporting the new text.

## G. Replacement-value semantics

NAMED-RANGE-01B remains unchanged. Values are literal text. XML-special
characters are safe through DOM text-node semantics. Newlines, tabs, leading
spaces, trailing spaces, and repeated spaces remain rejected. No rich text,
HTML, ODF fragment, or template-language interpretation is added.

## H. Real-template validation

The manually authored `samples/templates/sample_22_bookmarkTextReplacement.odt`
was not modified. Temporary output generated from it contained:

- `Walter Dietz` in `FullName`;
- `ODT Template Engine Developer` inside the original `T1` span for
  `Position`;
- `Herford` in `Location`.

The output passed ZIP integrity and XML well-formedness checks for
`content.xml`, `styles.xml`, `meta.xml`, and `META-INF/manifest.xml`. The
original template and generated sample output remain outside this commit.

## I. Tests and compatibility

The focused replacement suite covers acceptance, span identity and
attributes, marker preservation, repeated replacement, XML-special values,
rejected sibling/plain/nested/partial structures, atomic failure, and the
real-template save/reopen path. Existing NAMED-RANGE-01B behavior remains
covered. No existing public API was removed or renamed.

## J. Visual validation

The project renderer was not used to alter or replace any baseline. In the
current agent environment, LibreOffice is known to fail before PDF creation
with `failed to launch javaldx` and a read-only dconf path. Therefore visual
equivalence is not claimed here; local visual validation is required before
using Sample 22 as a rendered formatting example.

## K. Deferred capabilities

This slice does not add collapsed-bookmark insertion, multi-span replacement,
structured range editing, `replaceContent()`, marker removal, RichText/HTML
replacement, Style Context, Asset Context, or the public Sample 22 script.
Sample 22 can resume once this implementation is reviewed and its template
is used unchanged.

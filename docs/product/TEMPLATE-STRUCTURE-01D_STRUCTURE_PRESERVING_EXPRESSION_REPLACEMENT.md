# TEMPLATE-STRUCTURE-01D — Structure-Preserving Expression Replacement

Status: implemented for scalar and scalar-filter text replacement. SECTION-03E
is not part of this slice.

## A. Problem and evidence

The previous replacement path operated on individual text nodes and could not
reliably replace an expression spanning spans. In combination with the old
normalizer, this could remove formatting or move text past bookmark content.
Sample 25 provides the primary evidence: `{{position}}` is styled `T25`, while
`{{activity}}` spans `T29`/`T30` and intersects the `Activity` bookmark.

## B. Architecture and responsibility split

`TemplateExpressionProjector` remains the read-only logical model and
`TemplateStructureNormalizer` remains the physical canonicalization layer.
`TemplateExpressionReplacementService` now applies evaluated values to the
physical contributing text nodes. `TemplateProcessor` retains expression and
filter evaluation and delegates only the physical application step. Section
instantiation reuses `TemplateProcessor::replaceScalarTextInSubtree()`, so
clone-local binding receives the same preservation behavior.

The replacement service does not own styles, bookmarks, sections, package
resources, or template-language parsing.

## C. Physical-fragment mutation model

One text-flow scope is projected into a logical string with references to its
text nodes. Expressions are located in that logical string, then replaced from
right to left so earlier offsets remain valid. For each token, the first
contributing fragment receives the evaluated value; only token characters are
removed from later fragments. Prefixes, suffixes, spans, markers, and
unrelated siblings remain in place.

No replacement appends text to a parent or recreates a surrounding span.

## D. Simple styled replacement

```xml
<text:span text:style-name="T25">{{position}}</text:span>
```

becomes:

```xml
<text:span text:style-name="T25">Senior Projektmanager</text:span>
```

The existing span, style, attributes, parent, and sibling position survive.
Embedded text such as `Role: {{position}} | Company` is changed in place,
preserving both surrounding strings and the span.

## E. Same-style fragmented expressions

01C normally canonicalizes complete adjacent same-style fragments before
processing. The replacement service still supports fragmented input directly.
It does not require physical normalization to be correct and processes each
logical occurrence independently.

## F. Different-style fragmentation

For:

```xml
<text:span text:style-name="T29">{{ac</text:span>
<text:span text:style-name="T30">tiv</text:span>
<text:span text:style-name="T29">ity}}</text:span>
```

replacement `Leitung` produces `Leitung`, an empty `T30` span, and an empty
`T29` span. The first fragment policy is deterministic and does not infer a
winning style. Empty style-bearing spans are retained rather than cleaned up
speculatively.

## G. Bookmark-intersecting replacement

Bookmark markers are transparent for logical recognition but are never
mutated by expression replacement. A marker may consequently remain between
the first and later physical fragments, and the resulting bookmark range may
cover only part of the replacement or an empty retained fragment. This
preserves authored topology instead of guessing a new range.

This operation is distinct from `BookmarkTarget::replaceText()`, which owns
explicit native bookmark-range replacement semantics.

## H. Collapsed and paired bookmarks

Collapsed bookmarks remain collapsed and at their original location. Paired
bookmarks retain their start/end order and name. Sample 25's `FromTo` and
`Company` bookmarks are regression-protected. Replacing a nearby expression
cannot move the expression past a bookmark.

## I. Sibling order and surrounding text

`{{position}} | Unternehmen, Ort` becomes
`Senior Projektmanager | Unternehmen, Ort`. The replacement is applied to the
original text-node ranges; no node is removed and appended later. Prefixes,
suffixes, paragraph/list structure, section membership, and unrelated
content are not rebuilt.

## J. Multiple expressions and filters

Multiple occurrences, including repeated variable names, are independently
projected and replaced in reverse physical order. Filter evaluation remains in
`TemplateProcessor`; the replacement service receives only the evaluated
string. Existing upper/lower/date/number and other scalar-filter behavior is
therefore preserved without teaching the replacement service filter syntax.

## K. `nl2br`, conditions, and foreach

Plain scalar/filter replacement is the 01D boundary. `nl2br` and list-like
structured output retain their existing specialized paths because they can
produce ODF nodes rather than one text value. Conditions and foreach blocks
retain existing TemplateProcessor behavior and were not redesigned here.
Their complete structure-preserving migration remains future work.

## L. Section instantiation

`SectionInstantiationService` continues to clone, rewrite identities, and bind
only the detached clone. Its scalar binding now uses the structure-preserving
replacement service. The source prototype remains untouched, and the returned
instance remains addressable.

## M. Sample 25 results

The `note` and `position` values remain in their original styled paragraphs;
`position` stays before the separator and `Company` bookmark with `T25`.
The `activity` value is inserted into the first contributing `T29` fragment;
the remaining `T29`/`T30` spans and `Activity_1` marker topology remain. The
nested `ActivityEntry_1` and list structure survive. Header variables outside
the instantiated section remain unchanged, as does the source prototype.

## N. Atomicity and repeated processing

An unrecognized or malformed token is not destructively flattened. A token is
planned from the current projected scope before its nodes are changed. Reverse
processing makes multiple replacements deterministic. Once replaced, the
original scalar token is absent, so repeated processing does not duplicate the
value. Detached section instantiation provides the stronger clone-level
failure boundary.

## O. Package and lifecycle validation

Temporary Sample-25 outputs remain ZIP-valid and all core XML parts parse.
The source inspection API continues to inspect the original archive structure,
while replacement operates on the authoritative live DOM or detached clone.
Save/load/render lifecycle behavior remains covered by the existing integration
suite.

## P. Compatibility and remaining limitations

This slice resolves the plain scalar/style-preservation boundary, not every
template-format-preservation problem. Control structures, structured
replacement output, effective inherited styles, and arbitrary malformed
physical topology remain outside the contract. The broader
`TEMPLATE-FORMAT-PRESERVATION-01` roadmap item remains open.

## Q. Tests and recommendation

Focused tests cover styled and embedded values, attributes, same- and
different-style fragments, marker preservation, collapsed/paired bookmark
behavior, multiple occurrences, filters, Sample-25 position/activity/header
regressions, and prototype preservation. The full suite passes with 195 tests
and 1,286 assertions, plus one existing PHPUnit deprecation.

The next bounded work should characterize structure-preserving control-block
and structured-output replacement before SECTION-03E. Do not generalize the
first-fragment policy into rich-text style distribution.

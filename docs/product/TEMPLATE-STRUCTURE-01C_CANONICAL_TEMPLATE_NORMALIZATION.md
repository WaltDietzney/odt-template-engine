# TEMPLATE-STRUCTURE-01C — Canonical Template Normalization

Status: implemented as a bounded physical repair layer. This slice does not
begin SECTION-03E.

## A. Problem and evidence

LibreOffice can split one logical expression across several ODF spans. The
previous load-time `normalizeTemplateDom()` removed those nodes and appended a
plain text node, which destroyed styles and could change sibling order. Sample
25 demonstrated both failures: styled `{{position}}` moved after the
`Company` bookmark, and split activity text was flattened.

The 01B projector established that logical recognition and physical repair are
different operations. 01C adds the smallest proven physical repair while
retaining the read-only projection and diagnostics model.

## B. Canonical representation

Canonical does not mean every expression must be an unstyled text node. A
canonical expression is one coherent physical fragment where that can be done
without guessing, with its local style, native markers, original position, and
surrounding ODF structure preserved. Existing plain text and styled-span
expressions are both canonical.

## C. Normalizer architecture

`TemplateStructureNormalizer` first invokes the non-mutating
`TemplateExpressionProjector` for evidence and diagnostics. It then plans and
applies only same-style, complete-fragment repairs. The projector never
mutates. The normalizer owns all physical mutation and returns an immutable
`TemplateStructureNormalizationResult` containing `changed()`, repairs,
skipped candidates, and diagnostics.

No style registry, asset owner, template evaluator, or section service was
added to the normalization path.

## D. Safe repair rule

The supported repair is a complete logical expression represented by at least
two adjacent, same-kind text fragments with the same declared local style,
inside one text-flow scope, with no marker or structural node between them.

For example:

```xml
<text:span text:style-name="T1">{{pos</text:span>
<text:span text:style-name="T1">ition}}</text:span>
```

becomes one `T1` span containing `{{position}}`. The first fragment remains at
the original sibling position and retains all its attributes. The remaining
fragment nodes are removed only after the complete candidate has been
validated.

Plain text fragments with no style are treated analogously. A fragment that
contains unrelated prefix/suffix text is not merged.

## E. Style preservation and sibling order

The first physical node is the repair target; it is never replaced by a newly
created unstyled node. The declared `text:style-name` and other attributes on
that node remain intact. The operation changes only the contributing text and
removes redundant same-style fragment siblings.

Because the repair inserts nothing before the first fragment and never appends
the result to the parent, surrounding text and bookmarks retain their original
order. This directly prevents the Sample-25 `position` relocation regression.

## F. Different-style fragmentation

Expressions using multiple declared styles, such as Sample 25's `T29`, `T30`,
`T29` activity expression, are logically recognized but not physically
flattened. They produce style-conflict evidence and remain physically
unchanged. The normalizer does not select a winning style or invent effective
style semantics.

## G. Bookmark preservation

Bookmark elements are hard separators for physical repair. Collapsed
bookmarks remain collapsed, paired start/end markers remain ordered and named,
and no marker is moved, removed, or absorbed into a span. An expression whose
logical stream intersects a bookmark remains projected for reading but is
skipped for physical normalization with
`bookmark_intersects_template_expression`.

This applies to Sample 25's `Activity` marker topology. The `Company` paired
bookmark and `FromTo` collapsed bookmark remain unchanged.

## H. Boundaries and atomicity

Repairs are limited to one paragraph, heading, list-item, table-cell, section,
or text-box scope. Paragraph/list/cell/section boundaries cannot be crossed.
Each candidate is fully checked for complete fragment coverage, matching node
kind, matching style, and absence of marker separators before any node is
changed. Unsupported, malformed, mixed-style, and boundary-crossing cases are
left unchanged.

## I. Idempotence

After a successful merge, the expression has one physical fragment and is no
longer a repair candidate. A second pass reports no change. Tests also cover
canonical expressions and repeated normalization. This prevents progressive
DOM restructuring across load/render/save cycles.

## J. Legacy `fixBrokenVariables()` findings

`fixBrokenVariables()` remains used during render and remains a compatibility
concern. It recursively joins direct text nodes and can still be destructive
for some nested or fragmented structures. It is not the new canonicalizer and
was not removed speculatively. The new load-time path prevents the known safe
same-style cases from reaching it as fragmented spans.

## K. Legacy `normalizeTemplateDom()` findings

The old `TemplateProcessor::normalizeTemplateDom()` was the source of the
style-loss/order regression and is no longer used by `prepareLoadedTemplate()`.
Its protected compatibility method remains present but is not the load-time
canonicalization path. Existing structured insertion still needs an exact-key
compatibility join, so `OdtTemplate` performs a narrowly targeted join for the
requested structured placeholder only; this does not normalize unrelated
template expressions.

## L. Load-time integration

Preparation now follows the bounded normalizer for content and styles, then
continues with the existing default-style setup. Normal rendering, filters,
conditions, foreach handling, resource insertion, and addressable operations
were otherwise left intact.

## M. Normalization result

`TemplateStructureNormalizationResult` is immutable and reports repairs with
expression, repair type, previous/resulting fragment counts, and preserved
style. Skipped candidates report the reason and fragment/style context.
Projector diagnostics remain available for deferred or unsafe structures.
No raw DOM is returned.

## N. Sample-25 result

Load/save now preserves the header placeholders' styled containers and order.
`{{position}}` remains before the separator and `Company` bookmark, with its
`T25` span. `Company`, collapsed `FromTo`, nested `ActivityEntry`, lists, and
the Activity marker topology remain intact. The split `{{activity}}` expression
is still logically recognized with `T29`/`T30` metadata but is intentionally
not physically normalized.

## O. SECTION-03D regression

The existing clone/identity/local-binding tests remain green. The normalizer
does not rewrite values, section identities, bookmarks, lists, styles, or
resources. Clone-local processing therefore remains a separate responsibility;
any later formatting loss in evaluation must be handled at the processing
boundary rather than hidden in normalization.

## P. Compatibility and limitations

The compatibility suite still covers legacy fragmented structured placeholders
through the exact-key path. The normalizer does not repair different-style
fragments, marker-intersecting expressions, control structures, malformed
syntax, or expressions crossing structural boundaries. It does not resolve
effective inherited styles or perform generic template-language evaluation.

These limitations are deliberate: physical repair would require additional
semantic evidence. `TEMPLATE-FORMAT-PRESERVATION-01` remains the broader future
investigation of preservation during all template-language operations; 01C is
only the safe load-time canonicalization boundary.

## Q. Tests and validation

Focused tests cover canonical and styled expressions, two/three-fragment
same-style repair, style and attribute preservation, sibling order, mixed
styles, bookmarks, hard boundaries, malformed/unsupported input, idempotence,
DOM stability, and Sample 25 regression evidence. The full suite passes:
191 tests and 1,266 assertions, with one existing PHPUnit deprecation.

PHP lint, Composer validation, `git diff --check`, and the strict Zensical
build also pass. No visual rendering was performed in the agent environment;
the change is intended to preserve the existing native layout and should be
locally rendered before visual claims are made.

## R. Recommendation

The next bounded slice should move template evaluation toward the same logical
fragment map, especially for replacement of differently styled or
bookmark-intersecting expressions. Do not broaden physical normalization until
those operations define explicit style and marker semantics. SECTION-03E
should remain deferred until this processing-boundary work is characterized.

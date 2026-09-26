# TEMPLATE-STRUCTURE-01B — Non-Mutating Validation and Logical Projection

Status: implemented as a read-only inspection slice. The legacy load-time
normalizer remains active.

## A. Goal

This slice lets the engine inspect LibreOffice-authored template expressions
without changing the document DOM. It provides logical expression projection,
immutable descriptors, machine-readable diagnostics, and preservation-aware
fragment metadata.

## B. Architecture

`TemplateExpressionProjector` traverses bounded text-flow scopes and produces
logical expressions. `TemplateStructureInspector` packages those expressions
and diagnostics in `TemplateStructureInspection`. No public result exposes a
DOM node. `OdtTemplate::inspectTemplateStructure()` reads the original
`content.xml` archive part read-only, because normal construction still runs
the historical destructive normalizer.

The live `OdtDocumentContext` remains unchanged by inspection. `DocumentInspector`
continues to own native sections, bookmarks, tables, frames, and their
diagnostics.

## C. Logical projection

Text nodes are joined only inside one text-flow scope. Text spans and native
bookmark marker elements are transparent for recognition. Each expression
retains its raw logical text, contributing fragment count, declared local text
styles, and bookmark names encountered between its character boundaries.

The projection is deliberately not a replacement map and never merges spans,
creates text nodes, moves markers, or rewrites expressions.

## D. Expression descriptor

`TemplateExpressionDescriptor` is immutable and reports:

- raw text and expression kind;
- scalar variable, filter, and filter option where applicable;
- scope (`text:p`, `text:h`, or `section:<name>`);
- fragment count and split status;
- declared style names;
- transparent bookmark names;
- logical classification;
- physical-normalization classification;
- stable diagnostic codes.

`toArray()` is deterministic and contains no DOM objects.

## E. Expression kinds

The implementation recognizes the current bounded grammar:

- `SCALAR`: `{{name}}`;
- `FILTERED_SCALAR`: `{{upper:name}}`, `{{date:start|d.m.Y}}`;
- `SPECIAL`: current `nl2br`, `ul`, and `ol` forms;
- `CONDITION_OPEN`, `CONDITION_ELSE`, `CONDITION_END`;
- `FOREACH_OPEN`, `FOREACH_END`;
- `UNSUPPORTED` for balanced but unrecognized forms.

No template syntax was added or changed.

## F. Hard boundaries

Projection does not cross `text:p`, `text:h`, `text:list-item`, table cells,
`text:section`, or `draw:text-box`. Custom-shape parent context is not itself
rejected; expressions in its contained text-flow paragraph are inspected in
that paragraph scope. Document/body and other higher container boundaries are
also not treated as one continuous token stream.

Consequently an expression split between paragraphs, list items, cells, or
sections is not recognized as one valid expression. The inspector reports an
unbalanced/boundary diagnostic rather than guessing.

## G. Transparent markers

`text:bookmark`, `text:bookmark-start`, and `text:bookmark-end` contribute no
logical characters but are recorded when they occur inside an expression.
Their physical order and topology are untouched. An intersecting bookmark
produces `bookmark_intersects_template_expression` and makes physical
normalization `UNSAFE`, while logical recognition remains available when the
expression itself is otherwise valid.

## H. Style metadata and classification

Declared local `text:style-name` values are recorded in physical order. A
single-fragment expression is `VALID`. A split expression with one declared
style and no marker intersection is `REPAIRABLE`. Multiple styles or marker
intersection remain logically recognizable but are `VALID` only for logical
inspection and `UNSAFE` for generic physical normalization. Unsupported syntax
is `UNSAFE`.

This deliberately separates logical validity from permission to flatten or
rewrite ODF structure.

## I. Malformed syntax

Balanced but unsupported tokens receive `unsupported_template_expression`.
Unbalanced braces within a scope receive `malformed_template_expression`.
When an apparent open expression in one scope is followed by its close in the
next scope, `expression_crosses_text_flow_boundary` is also reported. Ordinary
prose braces without a plausible template opening/closing pair are not
individually treated as fatal syntax.

## J. Bookmark integration

The template layer reports expression-specific bookmark relationships only.
Native bookmark existence, pairing, collapsed form, and duplicate topology
remain the responsibility of `DocumentInspector`. This avoids duplicating the
ADDRESSABLE bookmark model while still exposing the important intersection
for future preservation-aware processing.

## K. Section context

Expressions are attributed to their nearest text-flow scope. Expressions in a
nested section remain in the nested section's paragraphs and are not projected
as part of an outer section's direct text stream. Sample 25's `ActivityEntry`
expression is therefore reported in its paragraph scope within the nested
section, while the unusual custom-shape parent does not invalidate it.

## L. Inspection result and facade

`TemplateStructureInspection` contains immutable expression descriptors and
diagnostics, with `valid()`, `repairable()`, `unsafe()`,
`expressionsByVariable()`, `expressionsInScope()`, and deterministic `toArray()`
helpers. The additive public facade is:

```php
$structure = $template->inspectTemplateStructure();
```

It reads the original archive XML and does not alter the prepared working
document. Raw XML, DOM nodes, and XPath are not public inspection values.

## M. Original-DOM access decision

Because `prepareLoadedTemplate()` still calls the legacy normalizer, inspecting
the live context would misrepresent authoring structure. `OdtPackage::sourceDom()`
parses an XML part directly from the original archive into a detached DOM used
only by the read-only facade. It does not replace or reload the live context,
and the facade does not return that DOM.

## N. Legacy normalizer coexistence

`normalizeTemplateDom()` and `fixBrokenVariables()` were intentionally not
changed. Existing rendering and compatibility behavior therefore remains
unchanged. The new projection is infrastructure for a future migration; it is
not a second evaluator and is not invoked by normal rendering.

## O. Sample 25 characterization

The original fixture reports `firstname`, `lastname`, `profession`, `note`,
`position`, and `activity`. `position` is one styled `T25` expression in its
original paragraph position. `activity` is projected across multiple physical
spans, records `T29` and `T30`, and records the intersecting `Activity` bookmark.
The nested `ActivityEntry`, lists, paired `Company` bookmark, and collapsed
`FromTo` bookmark remain physically untouched.

The characterization also confirms the legacy path's known damage: styled
`{{position}}` becomes a plain text node and is appended after the company
bookmark content; the split activity fragments are flattened. This is evidence
for migration work, not a change to SECTION-03D semantics.

## P. Immutability guarantee

Inspection is read-only by construction. Focused tests serialize synthetic DOM
before and after inspection and assert byte-equivalent canonical XML. Public
inspection parses a detached copy of the original archive part, so it cannot
mutate the prepared context.

## Q. Tests and compatibility

Focused tests cover simple, split, same-style, different-style, bookmark-
intersecting, filtered, condition, foreach, malformed, unsupported,
cross-boundary, custom-parent, Sample-25 expressions, deterministic arrays,
and DOM immutability. The complete existing suite remains responsible for
rendering, clone/instantiate, addressable targets, structured insertion,
resources, and lifecycle compatibility.

No legacy normalizer, sample, template, output, style, asset, or frame-layout
behavior was changed by this slice.

## R. Limitations

This is not a physical repair engine, control-structure validator, bookmark
topology validator, effective-style resolver, or HTML/ODF serializer. Complex
control matching, special structured-value semantics, and physical repair of
different-style fragments remain future work. The current descriptor
classification is intentionally conservative and operation-sensitive.

## S. Recommendation for TEMPLATE-STRUCTURE-01C

Use this projection to characterize and then migrate the legacy load-time
normalizer. The next slice should decide whether processing consumes virtual
logical groups directly, whether a narrow safe physical-repair mode is needed,
and whether a compatibility path is required. It should preserve Sample 25's
styles, ordering, and bookmark topology before proceeding to SECTION-03E.

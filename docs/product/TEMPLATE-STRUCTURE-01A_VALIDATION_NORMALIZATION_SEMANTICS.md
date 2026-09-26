# TEMPLATE-STRUCTURE-01A — Template Validation and Normalization Semantics

Status: semantics and characterization only. No validator or normalizer is
implemented by this slice.

## A. Problem statement

A ZIP-valid, well-formed ODF document is not automatically a valid ODT
Template Engine template. LibreOffice is free to represent one logical
expression with several text nodes, spans, and marker elements. The engine
must understand that logical structure without destroying authored formatting,
bookmark topology, ordering, or native containers.

The working distinction for future implementation is:

- **VALID** — the physical structure is unambiguous and needs no repair;
- **REPAIRABLE** — intent is unambiguous, so a bounded logical projection or a
  formatting-preserving repair may be possible;
- **UNSAFE / AMBIGUOUS** — the engine must report the structure rather than
  guess at author intent.

The classification is operation-sensitive. A structure can be valid for
logical recognition while being unsafe for destructive physical normalization.

## B. Evidence reviewed

This characterization reviewed PRODUCT-01C, ADDRESSABLE-01/02, SECTION-01,
SECTION-02A–D, SECTION-03A–D, the FRAME-LAYOUT-01 documents, ARCH-05 and
ARCH-07 materialization/ownership documentation, and the existing
`TEMPLATE-FORMAT-PRESERVATION-01` roadmap entry. It also inspected
`OdtTemplate`, `TemplateProcessor`, `TemplateExpressionIdentityRewriter`,
`SectionInstantiationService`, the addressable descriptors/resolvers, and
Sample 25 before and after the current load/save path.

## C. Physical representation versus logical structure

Physical ODF is the node tree: text nodes, `text:span` elements, bookmark
markers, paragraphs, lists, tables, and sections. Logical template structure is
the token stream interpreted by the template language. For example:

```xml
<text:span>{{pos</text:span><text:span>ition}}</text:span>
```

is logically `{{position}}`, but it is not equivalent to one unstyled text
node. Recognition must therefore be able to project a logical stream while
retaining a mapping to the original physical nodes.

Normalization must not mean “flatten every paragraph.” It is a narrowly
bounded repair operation, if one is needed at all.

## D. Expression model

The current language includes scalar expressions (`{{name}}`), filtered
scalars (`{{upper:name}}`, `{{date:start|d.m.Y}}`), condition markers
(`{{#if:key}}`, `{{#ifnot:key}}`, `{{#elseif:key}}`, `{{#else}}`,
`{{#endif}}`), foreach markers (`{{#foreach:key}}`, `{{#endforeach}}`), and
special structured forms such as `nl2br`, `ul`, and `ol` where supported by
the existing processor.

Future validation should parse these as logical expression objects rather than
searching individual text nodes. The variable identity in a filtered scalar is
the value token (`name` or `start`), not the filter name. Control expressions
need matching and scope validation; they must not be treated as ordinary
scalars.

## E. Logical text scope and boundaries

Within one semantic text-flow scope, the following may be transparent to
logical token recognition:

- adjacent text nodes;
- `text:span` elements;
- `text:bookmark`;
- `text:bookmark-start` and `text:bookmark-end`.

The following are hard boundaries and must not be crossed implicitly:

- `text:p` and `text:h` boundaries;
- `text:list-item` boundaries;
- table-cell and covered-cell boundaries;
- `text:section` boundaries;
- `draw:text-box` and custom-shape text-flow boundaries;
- document/body/container boundaries.

This matches the bounded logical grouping already used by SECTION-03C/03D.
Bookmark markers are transparent for token recognition, but remain physical
native objects and must never be moved or deleted by normalization.

## F. Style fragmentation

A complete expression in one span is VALID. A split expression across spans
with equivalent style is REPAIRABLE in principle, provided the physical spans
are preserved or merged only when equivalence is proven.

Different styles require a more conservative result. The logical expression
may still be recognized for inspection or a bounded operation, but destructive
physical normalization is UNSAFE: selecting one style or flattening to plain
text would invent formatting semantics. A future implementation should use a
virtual token projection by default and reject physical repair unless an
operation explicitly defines the style choice.

Sample 25 uses `T29`, `T30`, and `T29` across the logical `{{activity}}`
expression. This is evidence for logical recognition, not permission to
flatten the expression.

## G. Bookmark intersections

The following are acceptable native relationships when their topology is
otherwise valid:

- a bookmark outside an expression;
- a paired bookmark surrounding an expression;
- a collapsed bookmark adjacent to an expression;
- a marker between physical fragments of one logical expression.

The last case is VALID for native inspection and bounded identity-preserving
operations, but is UNSAFE for a generic physical normalizer unless marker
placement is proven unchanged. It should produce a diagnostic such as
`bookmark_intersects_expression` and remain an explicit authoring concern.

Unpaired, mismatched, incorrectly ordered, or overlapping bookmark ranges are
UNSAFE. Existing ADDRESSABLE bookmark diagnostics remain authoritative; this
document does not redefine them.

## H. Section rules

An engine-valid named section has a non-empty `text:name`, a unique name within
the section namespace in the final addressable template, and a structurally
well-formed native boundary. Nested sections are allowed and remain distinct
scopes. An unusual parent, such as Sample 25's custom-shape text flow, is not
invalid merely because it is uncommon.

An expression must not begin outside a section and finish inside it, or cross a
nested section boundary. Lists, tables, and drawing text flows remain their
own structural contexts. A section may contain those structures when their
native boundaries are complete.

## I. Bookmark rules

Both collapsed `text:bookmark` and paired start/end bookmarks are supported
native forms. Paired markers must have the same non-empty name and valid order.
Duplicate same-name ranges, unmatched markers, and ambiguous nesting are
invalid for a final template, though inspection may report them as
diagnostics. A valid final template keeps same-type names unique; namespaces
remain type-specific.

## J. Variable and bookmark coexistence

A bookmark and a visible template expression may occupy the same logical
content. This is tolerated only when both native bookmark topology and logical
expression scope are unambiguous. It does not create an implicit data-binding
relationship: a template value does not automatically mutate a bookmark, and
bookmark names are not inferred from data keys.

Sample 25's `Activity` bookmark intersecting `{{activity}}` is therefore
readable and usable by the bounded SECTION-03D clone-local binding path, but
is not a license for destructive normalization or automatic bookmark binding.

## K. Classification matrix

| Physical case | Logical recognition | Physical normalization |
|---|---|---|
| One span containing `{{name}}` | VALID | No repair needed |
| `{{na` / `me}}` in adjacent equivalent-style spans | VALID | REPAIRABLE if styles and topology are preserved |
| Same-style split expression with no markers | VALID | REPAIRABLE, preferably virtually |
| Split expression across incompatible styles | VALID for reading | UNSAFE to flatten; physical repair deferred |
| Split expression with a transparent bookmark marker between fragments | VALID for bounded reading | REPAIRABLE only by marker-preserving operation; generic flattening unsafe |
| Bookmark pair around a complete token | VALID | No generic repair needed |
| Expression crossing `text:p`/`text:h` | UNSAFE | Reject |
| Expression crossing a section boundary | UNSAFE | Reject |
| Expression crossing a table cell | UNSAFE | Reject |
| Expression crossing a list item | UNSAFE | Reject |
| Filter token split within one permitted scope | VALID/REPAIRABLE | Only parser-aware, style-preserving repair |
| Condition or foreach marker split within one permitted scope | REPAIRABLE for logical validation | Physical/control-structure repair requires dedicated rules |
| Unmatched `{{` or `}}` | UNSAFE | Reject |
| Unmatched, overlapping, or mismatched bookmark markers | UNSAFE | Reject |
| Nested structural containers treated as transparent | UNSAFE | Never normalize across them |

The distinction between “logical recognition” and “physical normalization” is
intentional. It prevents a validator from forcing every recognizable token
into a single physical text node.

## L. Normalization philosophy

The preferred future meaning of normalization is:

> repair only physical structures whose logical intent is unambiguous, while
> preserving formatting, ordering, bookmark topology, and native ODF
> structure.

The safest first mechanism is a non-mutating logical token map. A physical
repair may be considered for equivalent-style fragments with no hard-boundary
crossing and no marker ambiguity. It must preserve the original style spans or
merge only demonstrably equivalent nodes. It must not recreate styles, move
text around markers, or flatten unrelated content.

## M. Load-time normalization decision analysis

The current path is load-time mutation: `prepareLoadedTemplate()` calls
`normalizeTemplateDom()` for content and styles, and the processor reconstructs
balanced placeholder text by removing nodes and appending one plain text node.
This is simple and historically compatible with some fragmented placeholders,
but it is destructive and can change both formatting and physical order.

The alternatives are:

1. **Non-mutating validation/inspection** — safest and most predictable, but
   requires processing code to consume logical groups.
2. **Safe physical normalization** — compatible with existing downstream code
   for a narrow green zone, but difficult to prove for styles and markers.
3. **Lazy logical token projection** — preserves the source DOM and gives the
   processor a semantic stream, with moderate implementation complexity.
4. **Hybrid** — validate/project by default and permit only explicit,
   proven-safe physical repairs.

The evidence recommends the hybrid direction, beginning with non-mutating
validation and lazy logical projection. A future implementation must first
characterize templates that rely on current destructive behavior and decide
whether a legacy compatibility path is needed. This slice does not change the
load behavior.

## N. Diagnostics model

Future structure diagnostics should be typed data, not prose-only exceptions.
Useful fields are `code`, `severity`, `location`, `containerType`, target or
bookmark name, logical expression, `repairable`, reason, and suggested fix.

Candidate codes include:

- `split_template_expression`;
- `style_conflict_in_expression`;
- `bookmark_intersects_expression`;
- `expression_crosses_paragraph_boundary`;
- `expression_crosses_section_boundary`;
- `expression_crosses_table_cell_boundary`;
- `malformed_template_expression`;
- `duplicate_section_name`;
- `malformed_bookmark_range`;
- `ambiguous_template_structure`.

Exact codes should be finalized with the 01B implementation, but the fields
must support programmatic/AI recovery without parsing English messages.

## O. Strict and lenient modes

A future strict mode is recommended for CI and AI-assisted authoring: unsafe
structures fail before rendering or mutation, and repairable structures are
reported explicitly. A lenient mode could allow only the safe logical
projection/repair subset; it must never silently guess across hard boundaries
or ambiguous styles/markers. No modes are implemented here.

## P. Preliminary authoring contract

Authors should prefer one semantic expression in one styled span, stable named
sections, and stable native bookmark names. Splitting across equivalent-style
spans is tolerable but less robust. Bookmark markers may coexist with a token
only when their topology is intentional and preserved. Authors should avoid
crossing paragraph, list-item, table-cell, section, and drawing text-flow
boundaries. Mismatched bookmarks, overlapping ranges, and incomplete tokens
are invalid.

## Q. Sample 25 forensic findings

The unnormalized fixture contains:

- one outer `ExperienceEntry` section inside a custom-shape text flow;
- nested `ActivityEntry` and list/list-item structures;
- `Company` as a paired bookmark around `Unternehmen, Ort`;
- collapsed `FromTo` bookmark;
- `Activity` paired markers intersecting the split logical `{{activity}}`
  token;
- styled `{{note}}` and `{{position}}` placeholders;
- activity fragments using `T29`, `T30`, and `T29`.

The current load/save characterization was run directly against the fixture.
The styled position content is serialized as a plain `{{position}}` text node
after the `Company` bookmark, rather than remaining in its original styled
span position. The activity fragments are reduced to plain `{{activity}}`
text between the bookmark markers. This confirms both formatting loss and
placeholder relocation. The native sections, lists, and bookmark markers are
still present, so SECTION-03D's clone/identity/local-binding semantics are not
fundamentally invalid; the load-time normalization is the preservation debt.

## R. Compatibility risks

Current samples and tests may rely on `fixBrokenVariables()` and global
normalization to turn fragmented placeholders into text that later scalar,
filter, conditional, foreach, `nl2br`, and list processing can consume. Removing
that behavior without characterization could break existing templates or
change repeated render/save behavior. Different-style fragments, control tags,
structured placeholders, and row-local foreach behavior are especially risky.

The existing `TEMPLATE-FORMAT-PRESERVATION-01` roadmap item covers preservation
of formatting and DOM structure during template processing. This milestone is
related but distinct: it defines what authors and a future validator may
consider a safe engine template. The two efforts must share boundary and token
semantics rather than create contradictory rules.

## S. Future validation API direction

The likely direction is a separate typed template-structure report, exposed
later through a carefully named facade operation such as `validateTemplate()`.
Whether that report is returned by `inspect()` or by a separate
`TemplateStructureInspection` should remain open until 01B characterization is
implemented. `DocumentInspector` should continue to own native sections,
bookmarks, tables, frames, and their diagnostics; it should not become the
template-language validator.

## T. Responsibility boundaries

- `DocumentInspector`: enumerate and diagnose native named document objects.
- Future template-structure validator: validate expression topology,
  boundaries, bookmark interaction, and authoring safety.
- `TemplateProcessor`: evaluate the existing template language.
- `SectionInstantiationService`: clone-local identity rewrite and bounded
  local binding.

These responsibilities should not be collapsed into one normalizer or one
universal document object.

## U. Relationship to SECTION-03D

SECTION-03D is functionally implemented: native cloning, deterministic identity
rewriting, and clone-local scalar binding work logically. Sample 25 exposed a
pre-existing load-time normalization problem that damages formatting and
physical topology. This milestone is therefore a prerequisite to visual and
format-preserving confidence before SECTION-03E; it does not redefine
SECTION-03D as a failed clone model.

## V. Future TEMPLATE-STRUCTURE-01B test plan

The implementation slice should characterize at least:

1. load/save preserves styled placeholders;
2. placeholder order remains unchanged;
3. same-style split expressions are recognized;
4. different-style split expressions follow the explicit conservative rule;
5. bookmark-intersecting expressions preserve marker positions;
6. collapsed bookmarks adjacent to expressions;
7. paired bookmarks around expressions;
8. paragraph-boundary crossing is rejected;
9. section-boundary crossing is rejected;
10. table-cell-boundary crossing is rejected;
11. malformed expressions are diagnosed;
12. malformed bookmarks are diagnosed;
13. existing simple samples remain unchanged;
14. repeated render/save is stable;
15. Sample 25 remains structurally and visually preservation-safe.

## W. Recommendation

Implement TEMPLATE-STRUCTURE-01B as a non-mutating validator and logical token
projection first. Reuse the already bounded transparent-marker and hard-boundary
rules, preserve physical spans during evaluation where possible, and add
regression tests around Sample 25 before changing `prepareLoadedTemplate()`.
Only after that evidence should the project decide whether safe physical
normalization is needed. Do not begin SECTION-03E until the preservation and
validation contract is characterized.

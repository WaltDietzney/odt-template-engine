# ARCH-04B3A — Control Structure Characterization

## 1. Scope

This document records the current behavior of repeating blocks and
conditionals before any ARCH-04B3 extraction. It is characterization evidence,
not a behavior change or an implementation proposal for new syntax.

The active render order remains:

1. scalar and structural placeholder processing;
2. text-box processing;
3. repeating blocks;
4. conditionals.

Tracked generated sample outputs were not used or modified for this audit.

## 2. Active foreach implementation

The active `render()` path reads `OdtTemplate::$repeatStack` and calls
`applyRepeatingInDom()` once for each registered key, first for `content.xml`
and then for `styles.xml`.

`applyRepeatingInDom()`:

- finds a start paragraph using `//text:p[contains(text(),
  '{{#foreach:<key>}}')]`;
- searches only following sibling nodes for an element whose `textContent`
  contains `{{#endforeach}}`;
- collects and removes all sibling nodes between the markers;
- removes the marker paragraphs;
- deep-clones each collected node once per row;
- substitutes row values in every cloned text node through
  `replacePlaceholdersInNode()` and `replaceInText()`;
- inserts the clones before the original end-marker successor.

The repeated unit is therefore an arbitrary sibling DOM node, although the
markers themselves must be found in `text:p` paragraphs and the end marker
must be in a following sibling element. Table rows or other structures are
not independently recognized as loop units.

Empty rows remove the block and insert nothing. Missing or unclosed markers
leave the relevant source structure in place. Multiple registered keys are
processed in `repeatStack` order.

`setRepeatingData()` is a separate public compatibility path. It performs
placeholder repair and calls `applyAllRepeatingBlocksInDom()` directly for
both document regions; it does not populate the normal `repeatStack`.

## 3. Active condition implementation

The active `render()` path calls `applyConditionalsInDom()` for both
`content.xml` and `styles.xml` after repeating blocks.

`applyConditionalsInDom()`:

- snapshots all `text:p` nodes in document order;
- identifies a start paragraph containing `{{#if:...}}` or
  `{{#ifnot:...}}`;
- scans later paragraphs for `{{#elseif:...}}`, an exact `{{#else}}`, and
  `{{#endif}}`;
- evaluates branches with `evaluateCondition()`;
- retains only the selected range of paragraphs;
- removes marker paragraphs and unselected branch paragraphs;
- refreshes the paragraph snapshot after each processed block.

The branch body is retained as existing DOM nodes. A true branch therefore
keeps its spans and other paragraph children intact; a removed branch loses
its complete paragraph nodes.

The implementation assumes paragraph-delimited markers and branches. It does
not define a general arbitrary-DOM conditional model.

## 4. Exact state dependencies

Repeating processing reads:

- `repeatStack` during normal `render()`;
- row arrays supplied by `assignRepeating()`, `setRepeating()`, or the direct
  `setRepeatingData()` compatibility path;
- the current content/styles DOMs;
- `replacePlaceholdersInNode()` and `replaceInText()`.

Conditional processing reads:

- `valueStack` from `render()`;
- the current content/styles DOMs;
- `evaluateCondition()`.

Neither active path owns package state, document context, or static template
state.

## 5. DOM assumptions

The active paths assume:

- markers are discoverable in paragraph text nodes;
- foreach start/end markers are sibling paragraph/element structures;
- conditional markers delimit whole paragraphs;
- row substitution can operate on cloned text nodes;
- the same paragraph-oriented assumptions apply to `styles.xml` when it is
  processed.

Existing styled spans inside repeated content are deep-cloned. Conditional
selection retains or removes the existing paragraph nodes rather than
reconstructing their text.

## 6. Supported syntax

The currently active syntax is:

- `{{#foreach:key}} ... {{#endforeach}}`;
- `{{#if:expression}} ... {{#endif}}`;
- `{{#ifnot:expression}} ... {{#endif}}`;
- `{{#elseif:expression}}`;
- `{{#else}}`.

Condition expressions support truthiness checks and the operators `==`, `!=`,
`>`, `<`, `>=`, and `<=`. Numeric operands are compared numerically;
otherwise the implementation uses PHP loose comparison semantics.

## 7. Combination matrix

| Scenario | Observed result | Classification |
|---|---|---|
| Simple foreach with scalar row values | Sibling content is cloned once per row and values are replaced | SUPPORTED |
| Multiple rows | Each row receives an independent deep clone | SUPPORTED |
| Empty or missing repeat data | Markers and body are removed; no clones remain | SUPPORTED |
| Unclosed foreach | Source structure remains unchanged | SURPRISING / LEGACY |
| Styled content inside foreach | Existing cloned spans and style attributes remain | SUPPORTED |
| Foreach inside a table-like sibling structure | Only paragraph/sibling discovery is used; no dedicated table-row semantics exist | PARTIALLY SUPPORTED |
| Scalar filter inside foreach | Row substitution treats the complete filtered token as a key and normally removes it | SURPRISING / LEGACY |
| `nl2br` inside foreach | Structural processing has already happened before row cloning; row substitution itself does not create line-break nodes | PARTIALLY SUPPORTED |
| `ul`/`ol` inside foreach | No list transformation occurs during row substitution | UNSUPPORTED |
| OdtElement/RichText value inside foreach | Text substitution requires string conversion and is not a supported structured insertion path | UNSUPPORTED |
| Nested foreach | No nested-block model is defined; sibling scanning and marker substitution can consume or flatten inner markers | UNSUPPORTED / UNCERTAIN |
| `if` true/false | Paragraph ranges are retained or removed | SUPPORTED |
| `ifnot` | Result of `evaluateCondition()` is inverted | SUPPORTED |
| `elseif` and `else` | First true branch wins; otherwise else range is retained | SUPPORTED |
| Missing, false, `0`, `"0"`, empty truthiness values | Evaluated through `filter_var(..., FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)` and return coercion | SUPPORTED / LEGACY SEMANTICS |
| Comparison operators | All six documented operators are active | SUPPORTED |
| Malformed/unclosed condition | No complete block is processed | SURPRISING / LEGACY |
| Styled conditional body | Retained DOM nodes preserve existing spans when the branch survives | SUPPORTED |
| Condition markers in tables | Paragraph discovery can process nested `text:p` nodes, but there is no table-specific branch model | PARTIALLY SUPPORTED |
| Condition inside foreach | Foreach row substitution runs first and does not provide row values to later condition evaluation | PARTIALLY SUPPORTED / LEGACY |
| Foreach inside condition | No dedicated nesting model; behavior depends on paragraph ordering and marker discovery | UNCERTAIN |
| Nested conditions | Paragraph snapshot and linear marker scan do not provide a general nesting model | UNSUPPORTED / UNCERTAIN |
| Repeated render | Consumed markers/content are not restored; a second render is generally stable but cannot repeat consumed blocks | LEGACY BEHAVIOR |

The characterization tests in `TemplateControlStructuresArch04B3Test` cover
the supported scalar/styled paths, empty and malformed blocks, comparisons,
row substitution, structured-value failure, styles-DOM processing, and a
condition/foreach interaction.

## 8. Formatting-preservation findings

Foreach uses `cloneNode(true)`, so existing DOM descendants, including styled
`text:span` nodes, are cloned rather than reconstructed. This preserves the
source node structure for ordinary scalar row replacement, subject to the
text-node replacement behavior.

Conditionals retain existing paragraph nodes for selected branches and remove
whole paragraph nodes for discarded branches. This preserves formatting in a
surviving branch but necessarily removes formatting contained in a discarded
branch.

The audit did not implement or solve formatting preservation. Split markers,
markers mixed with unrelated text, tabs, hyperlinks, and more complex span
boundaries remain covered by the future concern
`TEMPLATE-FORMAT-PRESERVATION-01`.

## 9. RichText/OdtElement findings

Structured insertion occurs during the earlier `setValuesInDom()` pass.
Repeating blocks then clone existing DOM and run `replaceInText()` on cloned
text nodes. A row value that is an `OdtElement`, such as `RichText`, is not
handed back to `setValuesInDom()` and is not inserted through
`toDomNode()`; string conversion is attempted instead and is not supported.

Conditionals can retain an already inserted structured element only if that
element was inserted into a paragraph that survives branch selection. They do
not create structured elements from row data or from conditional expressions.

This supports the future principle that control structures should decide
whether existing document fragments are kept, removed, or repeated, while
structured content remains the responsibility of the ODT element layer. The
current blocker is that repeating row substitution is still text-based rather
than a general fragment/value-aware operation.

## 10. Legacy and alternate paths

| Method | Classification | Evidence |
|---|---|---|
| `applyRepeatingInDom()` | ACTIVE | Called by `render()` for each `repeatStack` entry and both DOM regions |
| `applyAllRepeatingBlocksInDom()` | COMPATIBILITY-SENSITIVE | Called by public `setRepeatingData()` |
| `applyRepeatingInDomTextBased()` | LEGACY CANDIDATE | No repository call sites found |
| `applyConditionalsInDom()` | ACTIVE | Called by `render()` for both DOM regions |
| `applyConditionalsInDomTextBased()` | LEGACY CANDIDATE | No repository call sites found |
| `splitConditionalsInTextNodes()` | LEGACY CANDIDATE | No repository call sites found |
| `setRepeatingData()` | COMPATIBILITY-SENSITIVE | Public method; no sample/test call site found, but externally callable |
| `replaceInText()` | ACTIVE / COMPATIBILITY-SENSITIVE | Called by `replacePlaceholdersInNode()`, which is used by active foreach paths; protected and externally overridable |

The unused text-based alternatives must not be removed during
characterization. Their protected visibility means external subclasses may
still depend on them.

## 11. Internal ordering dependencies

The current sequence is observable in several ways:

- scalar and structural passes run before repeating blocks;
- repeating clones receive row substitution after those earlier passes;
- conditions run after repeating blocks and evaluate the outer `valueStack`,
  not a row-local value stack;
- `OdtElement` insertion occurs before repeating and conditional processing;
- `styles.xml` follows the same broad ordering as `content.xml`.

Consequently, filters and structural placeholders inside row data are not
re-run as row-aware transformations, and conditions cannot generally inspect
row-local values. These are architectural dependencies to preserve during
B3B, not user-facing semantic guarantees to strengthen here.

## 12. Unsupported and surprising behavior

The principal surprising behaviors are text-based row substitution, lack of
general nested control-structure support, paragraph-only condition boundaries,
and the inability to insert an `OdtElement` from a row. Unclosed blocks remain
untouched rather than raising an exception.

These findings are retained as compatibility evidence. No behavior is changed
by ARCH-04B3A.

## 13. Architectural implications

The control-structure boundary can evolve toward a processor that operates on
existing DOM fragments: repeating should clone fragments, and conditions
should retain or remove them. However, row-local substitution and structured
element insertion must remain explicit dependencies rather than being hidden
inside generic string replacement.

The future processor must receive repeat/value state as inputs and must not
own package state, document context, or global mutable state. It should not
assume that all document regions are limited to `content.xml` and
`styles.xml`, even though those are the current facade inputs.

## 14. Recommended extraction boundary

ARCH-04B3 should eventually extract:

- active foreach block discovery and fragment cloning;
- active condition branch discovery and selection;
- condition expression evaluation;
- explicit row/value inputs;
- supplied DOM region processing.

The facade should retain protected compatibility wrappers and continue to
orchestrate document regions, render ordering, package lifecycle, and
structured OdtElement insertion. The public `assignRepeating()`, `setRepeating()`,
`setRepeatingData()`, and `render()` APIs must remain unchanged.

## 15. Recommended B3B implementation sequence

1. Add direct protected-hook characterization for repeating and condition
   dispatch where needed.
2. Extract condition evaluation and the active paragraph-branch selector
   behind a stateless processor operation, preserving facade dispatch.
3. Extract active foreach sibling discovery, cloning, and row substitution
   without consolidating the unused alternatives.
4. Preserve `setRepeatingData()` as a compatibility path until a separate
   review proves delegation safe.
5. Re-run the combination matrix and public sample smoke coverage.
6. Perform manual LibreOffice regression for representative control-structure
   documents.

Nested loops, row-local structural filters, OdtElement values in rows,
format-preserving replacement, and legacy-path removal remain outside this
sequence.

## 16. Open questions

- Should B3B preserve text-based row substitution exactly or introduce a
  narrow DOM-aware row substitution seam first?
- Can active and compatibility repeating entry points share implementation
  without changing public `setRepeatingData()` timing?
- Which protected legacy methods have external subclass dependencies?
- What explicit behavior should be documented for nested blocks?
- Should conditional expressions remain PHP-coercion-compatible or receive a
  dedicated compatibility-tested evaluator?
- How can control-structure selection coexist with future formatting
  preservation without reconstructing unrelated inline content?

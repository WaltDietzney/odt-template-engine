# ARCH-04B — Template Processor Change Contract

Status: **Design complete — no production-code changes**

## 1. Purpose and target boundary

ARCH-04B defines the safest future extraction boundary for template-language
processing. It does not introduce `TemplateProcessor`, change syntax, move
production methods, or alter public APIs.

The future processor should own template-language transformations that operate
on supplied document DOM regions:

- plain variable replacement;
- scalar filter evaluation;
- placeholder normalization required for language parsing;
- `nl2br` transformation;
- `ul`/`ol` list placeholders;
- repeating-block expansion;
- condition evaluation and branch selection;
- the compatibility-preserving sequencing of those passes.

The processor should not own ODT package lifecycle, styles, metadata, page
layout, image assets, finalization, or structured `OdtElement` insertion.

The public facade remains stable:

```php
$template->assign([...]);
$template->assignRepeating('items', [...]);
$template->render();
```

## 2. State ownership decision

### Options considered

#### A — Processor owns values and repeating data

This would make the processor stateful and require the facade to forward every
assignment. It risks duplicated state during the migration and makes repeated
render behavior dependent on processor lifetime.

#### B — Introduce `TemplateData` or `TemplateContext`

This could become useful if template processing is reused outside
`OdtTemplate`, but it is an additional abstraction before that reuse case has
been demonstrated. It would also require deciding how legacy `$values` and
public assignment methods map into the new object.

#### C — Keep state temporarily on `OdtTemplate` and pass it to processing

This preserves the current public API and gives one mutable source of truth.
The processor can receive the current values and repeat rows for each render
operation without storing a second mutable copy. PHP arrays remain ordinary
operation inputs under copy-on-write semantics.

### Selected strategy: C for ARCH-04B

`OdtTemplate::$valueStack` and `$repeatStack` remain the authoritative mutable
state during ARCH-04B. `assign()`, `assignRepeating()`, `setValues()`, and
`setRepeating()` retain their current behavior. A future processor receives
the current state and supplied DOM regions as operation inputs.

This gives:

- one source of truth;
- no duplicated mutable state;
- unchanged public assignment APIs;
- straightforward unit testing with explicit inputs;
- preserved repeated-render behavior;
- no unnecessary context object before processor reuse is established.

The legacy `$values` field remains a compatibility concern and must not be
silently merged into a second processor state store. ARCH-04B must first
determine whether it has external/subclass significance before changing it.

If later work demonstrates processor reuse across facades or renderers, a
document-scoped data object may be introduced as a separate design decision.

## 3. Render pipeline compatibility contract

The current order is normative for extraction. For both `content.xml` and
`styles.xml`, `render()` performs:

1. `fixBrokenVariables()`;
2. `replaceNl2brInDom()`;
3. `replaceListsInDom()`;
4. `setValuesInDom()`;
5. `renderTextBoxes()`;
6. repeating blocks via `applyRepeatingInDom()`;
7. conditions via `applyConditionalsInDom()`.

No ARCH-04B slice may reorder these stages merely to create a cleaner design.

| Stage | Input/state | Mutation | Processor status |
| --- | --- | --- | --- |
| `fixBrokenVariables()` | supplied DOM; no value state | joins split placeholder fragments | candidate normalization implementation; compatibility seam retained |
| `replaceNl2brInDom()` | supplied DOM and value stack | replaces one special placeholder with text and `text:line-break` nodes | move in B2 as structural processor pass |
| `replaceListsInDom()` | supplied DOM and value stack | replaces containing `text:p` with native `text:list` | move in B2 as structural processor pass |
| `setValuesInDom()` | supplied DOM and values | plain/filter replacement; recognizes `OdtElement` values | scalar part candidate; structured branch deferred |
| `renderTextBoxes()` | supplied DOM and value stack | direct replacement inside `draw:text-box` paragraphs | defer until DOM-region/text-box seam is characterized |
| `applyRepeatingInDom()` | supplied DOM and repeat rows | removes marker siblings, clones rows, replaces row text | move in B3 without semantic changes |
| `applyConditionalsInDom()` | supplied DOM and value stack | removes non-selected paragraph branches | move in B3 without semantic changes |

The processor must be able to process more than one supplied region without
assuming that `content.xml` is the only document area. The initial facade can
continue to supply exactly the current content and styles DOMs.

## 4. `AbstractOdtTemplate` seam decisions

| Method | ARCH-04B decision | Reason |
| --- | --- | --- |
| `setValuesInDom()` | retain protected compatibility wrapper; extract only scalar branch initially | it also recognizes `OdtElement` values and calls structured insertion |
| `replacePlaceholderWithDom()` | defer to ARCH-05 | this is structured ODF insertion, not scalar language processing |
| `replacePlaceholdersInNode()` | retain for row replacement during B3 | current loop semantics depend on it; its recursive DOM behavior must remain |
| `replaceInText()` | retain as compatibility/helper seam during B3 | row replacement currently uses it and does not apply filters |
| `fixBrokenVariables()` | retain protected wrapper; implementation may move with normalization | used by `render()`, `setRepeatingData()`, and `setElement()` |
| `parseTemplateContent()` | leave in facade/base for now | `extractTemplateVariables()` is a public inspection API with a separate parser |
| `extractTemplateVariables()` | remain public compatibility API | it is not the active renderer and must not be silently changed |
| `setElement()` | defer to ARCH-05 | styles, images, and structured DOM insertion are coupled here |
| `ensure*Style*()` / `registerStyles()` | remain document/style responsibilities | outside processor scope |

The processor must not receive or emit `OdtElement` DOM nodes as an accidental
replacement for `setElement()`. If `setValuesInDom()` retains an
`OdtElement` branch for compatibility, that branch should continue to delegate
to the existing insertion wrapper.

## 5. Protected-method compatibility strategy

`replaceNl2brInDom()`, `replaceListsInDom()`, `applyConditionalsInDom()`,
`applyRepeatingInDom()`, `renderTextBoxes()`, `applyFilter()`, and related
protected methods may be overridden by external subclasses even where the
repository has no call site.

The preferred extraction strategy is:

1. keep the existing protected method signatures;
2. keep `render()` calling those protected seams during the migration;
3. move private implementation into an internal collaborator only where the
   wrapper can preserve override dispatch;
4. postpone any method whose override behavior cannot be preserved cleanly;
5. never change a public-to-protected call path solely to reduce code size.

This follows the ARCH-03B `setPageMargins()` polymorphism finding: replacing a
facade/wrapper call with a direct collaborator call can silently bypass a
subclass override. ARCH-04B must preserve the equivalent behavior for
template-processing seams.

`setValuesInDom()` and `replacePlaceholderWithDom()` are especially sensitive:
the former mixes scalar language replacement with the latter's structured
insertion responsibility. They should not be collapsed into one new processor
method.

## 6. Filter contract

### Scalar filters

The current `applyFilter()` behavior is:

| Filter | Current behavior |
| --- | --- |
| `upper` | `mb_strtoupper($value)` |
| `lower` | `mb_strtolower($value)` |
| `trim` | `trim($value)` |
| `date` | `date($option ?: 'd.m.Y', strtotime($value))` |
| `number` | parses comma/dot input and uses `number_format(..., precision, ',', '.')` |
| `currency` | number formatting with two decimals, comma decimal separator, dot thousands separator, and ` €` |
| `checkbox` | `☑` for PHP-truthy input, otherwise `☐` |

Unknown scalar filters return the original value. Filtered replacement uses
the current regex and option parsing; ARCH-04B must not broaden accepted names
or options without characterization.

### Structural special cases

`nl2br` is a no-op in `applyFilter()` and is implemented by a DOM pass that
creates `text:line-break` nodes. `ul` is also a no-op in the scalar matcher;
`replaceListsInDom()` handles `ul` and `ol` by replacing a paragraph with a
native list. These must remain structural processor passes, not be forced
through a scalar filter return value.

## 7. Repeating-block contract

The active `assignRepeating()` path stores rows in `repeatStack`. During
rendering, each key is located with a paragraph query containing the exact
`{{#foreach:key}}` marker. The implementation expects the end marker to occur
in a following sibling node containing `{{#endforeach}}`.

For each block it:

1. collects and removes nodes between start and end markers;
2. removes both marker nodes;
3. deep-clones the collected nodes for each row;
4. calls `replacePlaceholdersInNode()` with the row data;
5. inserts each clone at the original reference position.

The same operation is performed in `content.xml` and `styles.xml`.

Important compatibility constraints:

- ordinary outer values are processed before repeating;
- row placeholders are replaced by `replaceInText()`, not `applyFilter()`;
- filters inside repeated row clones are therefore not an assumed supported
  combination until explicitly characterized;
- missing repeat data means no active block pass for that key and markers may
  remain;
- nested, same-level, or condition-wrapped loops are not a promised feature;
- after a successful render consumes the markers, a second render has no
  remaining block to expand;
- `setRepeatingData()` remains a separate immediate legacy path and must not
  be silently redirected during the first extraction slice.

## 8. Condition contract

The active condition path operates on `text:p` paragraph nodes and searches
for a complete block from an `if`/`ifnot` marker through `elseif`, `else`, and
`endif` paragraph markers.

Current semantics:

- a truthy condition uses `filter_var($value, FILTER_VALIDATE_BOOLEAN,
  FILTER_NULL_ON_FAILURE)`;
- missing values, `false`, `0`, string `"0"`, and an empty string evaluate as
  false in the normal truthy path;
- `ifnot` negates the evaluated result;
- supported comparison operators are `==`, `!=`, `>`, `<`, `>=`, and `<=`;
- numeric operands are compared as floats when both are numeric;
- `elseif` branches are evaluated in source order and the first true branch
  wins;
- `else` is selected if no preceding branch matches;
- non-selected paragraphs are removed, including marker paragraphs;
- conditions run after repeating blocks, so repeated content is available to
  the condition pass;
- conditions inside arbitrary inline text or non-paragraph structures are not
  promised by the active path.

The protected text-node implementation is separate, has different behavior,
and is not active in `render()`.

## 9. Proposed ARCH-04B implementation slices

### ARCH-04B1 — Processor/state seam, normalization, plain values, scalar filters

Expected production files:

- new internal TemplateProcessor implementation;
- `src/OdtTemplate.php` facade wrappers/orchestration;
- `src/AbstractOdtTemplate.php` only where compatibility delegation is needed.

Required tests first:

- plain variables;
- all scalar filters;
- missing values and scalar conversion;
- split placeholders and normalization;
- processing of both content and styles DOMs;
- repeated render behavior.

Constraints:

- keep mutable state on `OdtTemplate` initially;
- preserve protected method dispatch;
- do not move `OdtElement` insertion;
- do not change package, styles, image, metadata, or page-layout code.

Manual samples: 01, 02, 09, 10, and 21.

### ARCH-04B2 — Structural language passes

Expected scope:

- `nl2br`;
- `ul`/`ol` list placeholders;
- supplied DOM-region handling.

Required tests:

- multiline values;
- unordered and ordered lists;
- missing/empty list values;
- content/styles region behavior;
- coexistence with existing structured-element tests.

Manual samples: 02, 08, 10, 18, and 19.

### ARCH-04B3 — Repeating blocks and active conditions

Expected scope:

- active `applyRepeatingInDom()` behavior;
- active paragraph-based condition behavior;
- exact current ordering and row replacement semantics.

Required tests:

- foreach rows and variables;
- missing repeat data;
- repeated rendering;
- `if`, `elseif`, `else`, `ifnot`;
- false, zero, string `"0"`, empty, and missing values;
- condition/repetition combinations only where current fixtures prove support.

Manual samples: 01, 03, 10, 18, and 21.

### ARCH-04B4 — Facade integration and compatibility review

Expected scope:

- final `OdtTemplate::render()` integration;
- protected wrapper review;
- `setValues()`/`setRepeating()`/`setRepeatingData()` compatibility;
- text-box and styles.xml processing review;
- public sample and LibreOffice regression pass.

Required tests:

- `OdtElement` coexistence;
- images and structured insertion remain outside processor ownership;
- styles.xml placeholders;
- all public samples through `PublicSampleSmokeTest`;
- full integration and lifecycle suite.

Manual samples: 01, 02, 03, 09, 10, 14, 18, 19, and 21.

## 10. Future document-region design

The initial processor should conceptually operate on supplied DOM regions,
not directly own `OdtDocumentContext`. The facade can initially supply a
small ordered collection consisting of `content.xml` and `styles.xml`.

This keeps package/context ownership in ARCH-02 and avoids assuming that the
processor may mutate every document region. Later headers, footers, master
pages, or sections can add processable regions through an internal document
composition contract without changing the language semantics.

No public region API, header/footer behavior, or master-page support is
defined by ARCH-04B.

## 11. Required characterization matrix

Before each implementation slice, tests must cover the relevant current
behavior:

| Area | Required characterization |
| --- | --- |
| Plain values | one value, multiple values, missing value |
| Scalar filters | upper, lower, trim, date, number, currency, checkbox |
| Boolean/value semantics | false, `0`, `"0"`, empty string, missing value |
| Structural special cases | `nl2br`, `ul`, `ol` |
| Repetition | rows, row variables, missing data, content/styles DOMs |
| Repetition/filter interaction | explicitly test or record unsupported behavior |
| Conditions | if, elseif ordering, else, ifnot, comparisons |
| Combinations | conditions/repetition only where currently supported |
| Normalization | split placeholders and span behavior |
| Structured insertion | `OdtElement` coexistence; defer implementation to ARCH-05 |
| Lifecycle | render once, render twice, save after render, save without render |
| Document regions | content.xml and styles.xml |

Unsupported combinations must be recorded as unsupported rather than
silently promoted into new feature requirements.

## 12. Design principles and exclusions

ARCH-04B must enforce:

- one mutable state owner;
- current behavior before improvement;
- unchanged public APIs and template syntax;
- protected subclass compatibility wherever practical;
- composition over a larger inheritance hierarchy;
- structured insertion as an ARCH-05 concern;
- no new static/global template-language state;
- no unrelated cleanup.

Explicitly out of scope:

- `OdtElement` insertion redesign;
- image handling or asset lifecycle;
- metadata and page layout;
- style finalization and `StyleContext`;
- package/document ownership;
- document defaults;
- legacy method removal;
- HTML rendering, pagination, headers, footers, sections, or page styles.

## 13. Remaining design uncertainties

- whether a future processor should eventually own state or continue to
  consume facade-owned state;
- whether filters inside repeated clones should remain unsupported;
- how much inline formatting normalization can preserve;
- whether text-box replacement belongs with language processing or structured
  document editing;
- which protected legacy methods external subclasses use;
- how processor-generated style requirements will integrate with
  `STYLE-CONTEXT-01`.

ARCH-04B design is ready for implementation in the stated sub-slices, but
these questions must not be answered by accidental behavior changes during
ARCH-04B1.

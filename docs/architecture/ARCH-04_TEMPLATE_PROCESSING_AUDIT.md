# ARCH-04A — Template Processing Audit

Status: **Characterization complete — no production-code changes**

## 1. Scope

ARCH-04A audits the template-language behavior currently distributed between
`OdtTemplate` and `AbstractOdtTemplate`. It does not introduce a
`TemplateProcessor`, change syntax, remove compatibility methods, or move
production code.

The intended future public workflow remains:

```php
$template->assign([...]);
$template->assignRepeating('items', [...]);
$template->render();
```

The audit treats `content.xml` and `styles.xml` as separate DOM targets where
the current implementation processes both.

## 2. Current public API

Template-language entry points on `OdtTemplate` are:

| API | Current role |
| --- | --- |
| `assign(array)` | merge values into `valueStack` |
| `assignRepeating(string, array)` | register rows in `repeatStack` |
| `render()` | execute the active DOM-based processing pipeline |
| `setValues(array)` | compatibility alias for assigning values |
| `setRepeating(string, array)` | compatibility alias for assigning rows |
| `setRepeatingData(array)` | legacy immediate repeating-block path |
| `extractTemplateVariables()` | public inspection/diagnostic API |
| `setElement(string, OdtElement)` | structured insertion, outside the processor boundary |

`save()`, `load()`, metadata, images, page layout, and package lifecycle are
facade/document responsibilities rather than template-language processing.

## 3. Supported template syntax

### Plain variables

The active value path replaces placeholders such as:

```text
{{name}}
```

Values are read from `valueStack`. Missing values in the ordinary DOM
replacement path become an empty string through the replacement expression.

### Filters

The active filtered-variable pattern supports forms such as:

```text
{{upper:name}}
{{lower:email}}
{{trim:name}}
{{date:created|Y-m-d}}
{{number:amount|2}}
{{currency:amount}}
{{checkbox:enabled}}
```

The built-in `applyFilter()` cases are `upper`, `lower`, `trim`, `nl2br`,
`ul`, `date`, `number`, `checkbox`, and `currency`. Unknown filter names
fall through to the original value. `nl2br` and `ul` are special cases: the
DOM pipeline handles them before ordinary filtered replacement.

The ordinary filtered-variable regex accepts a word-like filter and key and
an optional non-`}` option. `extractTemplateVariables()` uses a narrower
parser for inspection and is not the rendering implementation.

### Newline and list placeholders

The active special placeholders are:

```text
{{nl2br:comment}}
{{ul:items}}
{{ol:items}}
```

`nl2br` creates `text:line-break` nodes. `ul` and `ol` replace the containing
`text:p` with a native `text:list`, using the current built-in list style
names.

### Conditions

The active paragraph-based path supports:

```text
{{#if:active}}
{{#elseif:score>50}}
{{#else}}
{{#endif}}

{{#ifnot:is_admin}}
{{#endif}}
```

`evaluateCondition()` supports truthy checks and `==`, `!=`, `>`, `<`, `>=`,
and `<=` comparisons. Numeric operands are cast to floats when both sides
are numeric. Missing values evaluate as false for truthy checks.

### Repeating blocks

The active DOM-based path supports:

```text
{{#foreach:items}}
  {{produkt}} — {{preis}}
{{#endforeach}}
```

`assignRepeating()` stores rows for `render()`. Each block is located by
paragraph siblings, its template nodes are removed, cloned per row, and row
placeholders are replaced recursively in each clone.

### Placeholder normalization

Construction and `load()` normalize template DOMs before rendering. The
normalization joins placeholder fragments split across paragraph children or
`text:span` nodes. `fixBrokenVariables()` is also called by structured
insertion, `setRepeatingData()`, and at the start of `render()`.

Normalization can discard inline span structure when it reconstructs a broken
placeholder. This is existing behavior and must be characterized before any
processor extraction changes it.

## 4. Exact active `render()` pipeline

The current execution order in `OdtTemplate::render()` is:

```text
valueStack / repeatStack
    ↓
fixBrokenVariables(content.xml)
fixBrokenVariables(styles.xml)
    ↓
replaceNl2brInDom(content.xml)
replaceNl2brInDom(styles.xml)
    ↓
replaceListsInDom(content.xml)
replaceListsInDom(styles.xml)
    ↓
setValuesInDom(content.xml)
setValuesInDom(styles.xml)
    ├── plain variables
    ├── filtered variables
    └── OdtElement values through replacePlaceholderWithDom()
    ↓
renderTextBoxes(content.xml)
renderTextBoxes(styles.xml)
    ↓
for each repeatStack entry:
    applyRepeatingInDom(content.xml)
    applyRepeatingInDom(styles.xml)
    ↓
applyConditionalsInDom(content.xml)
applyConditionalsInDom(styles.xml)
```

This is not a generic parse/evaluate/render pipeline. It is a sequence of
DOM transformations with separate special-case passes.

Important consequences:

- ordinary variables are processed before repeating blocks;
- row values are inserted later by `replacePlaceholdersInNode()`;
- row replacement uses `replaceInText()`, not `applyFilter()`, so filtered
  placeholders inside repeated clones require dedicated characterization;
- conditions run after repetition and inspect paragraph structure;
- text boxes have a separate direct replacement pass before repetition and
  conditions;
- styles.xml receives the same language passes, so placeholders in headers,
  styles, or other style content can be affected;
- `setElement()` performs structured replacement outside `render()` and can
  itself fix split placeholders before insertion.

## 5. Responsibility map

### `OdtTemplate`

| Method | Classification | State/DOM | Callers and visibility |
| --- | --- | --- | --- |
| `setValues()` | J/K compatibility facade | writes `valueStack` | public callers, samples and tests |
| `assign()` | C variable handling / public facade | writes `valueStack` | public samples and tests |
| `setRepeating()` | E/K compatibility facade | writes `repeatStack` | non-public samples and possible external users |
| `assignRepeating()` | E public facade | writes `repeatStack` | public samples and tests |
| `setRepeatingData()` | E/K legacy immediate path | content/styles DOM and row data | repository call sites are absent; public method remains |
| `render()` | J orchestration of A–E | reads stacks; mutates content/styles DOM | public samples/tests |
| `replaceNl2brInDom()` | A/B template transformation | current DOM text nodes | called only by `render()` |
| `replaceListsInDom()` | A/B template transformation | current DOM; creates native list nodes | called only by `render()` |
| `applyConditionalsInDom()` | D active condition handling | current paragraph nodes; calls `evaluateCondition()` | called only by `render()` |
| `evaluateCondition()` | D condition evaluation | reads supplied values | active and legacy condition paths |
| `applyRepeatingInDom()` | E active loop handling | current DOM; clones/removes siblings | called only by `render()` |
| `applyAllRepeatingBlocksInDom()` | E legacy/immediate loop handling | current DOM; clones/removes siblings | called by `setRepeatingData()` |
| `applyFilter()` | C variable/filter handling | no persistent state | called by inherited `setValuesInDom()` |
| `normalizeTemplateDom()` | B normalization | current DOM before processing | constructor/load only |
| `renderTextBoxes()` | G/H specialized document structure | content/styles text-box paragraphs | called only by `render()` |
| `applyConditionalsInDomTextBased()` | D/K legacy alternative | text nodes in supplied DOM | no repository call sites found |
| `applyRepeatingInDomTextBased()` | E/K legacy alternative | text nodes and cloned nodes | no repository call sites found |
| `splitConditionalsInTextNodes()` | B/D legacy preparation candidate | text-node structure | no repository call sites found |

The image methods `setImage()`, `replaceImageInDom()`,
`replaceImageByName()`, and `replaceImageInNamedDom()` are G/H. They use
placeholder lookup, but image copying, draw-frame generation, manifest/package
handling, and image-style registration are not template-language processing.

`load()`, `save()`, `refresh()`, `cleanup()`, and package synchronization are
I. `setMeta()`/`getMeta()` and page-layout access are document services. The
protected compatibility aliases and helpers remain K.

### `AbstractOdtTemplate`

| Method/group | Classification | Boundary finding |
| --- | --- | --- |
| `setValuesInDom()` | A/C | active plain/filter replacement for both DOMs; also recognizes `OdtElement` values |
| `replacePlaceholderWithDom()` | F | structured insertion and placeholder replacement; defer to ARCH-05 |
| `replacePlaceholdersInNode()` / `replaceInText()` | E/C | active row-clone replacement; separate from filter evaluation |
| `fixBrokenVariables()` | B | shared normalization support used by template and structured paths |
| `setElement()` | F/G/H/J | public structured-element facade; registers styles/assets and replaces placeholders |
| `ensure*Style*()` / `registerStyles()` | H | style/document responsibility, not processor scope |
| `ensureDefault*()` | H | load-time document defaults/list setup, not processor scope |
| `injectImageStyles()` / `adjustBulletIndentation()` | H | finalization, documented by ARCH-03C |
| `extractTemplateVariables()` / `parseTemplateContent()` | J/K | public inspection parser; not the active render parser |
| `prepareNamespaces()` / `ensureXmlnsAttributes()` | H | document/XML support |
| debug methods | J/K | diagnostic API |

The most important split is that `AbstractOdtTemplate::setValuesInDom()` is
the current bridge between template-language replacement and structured
element insertion. It cannot be moved wholesale without either retaining an
injected insertion collaborator or waiting for ARCH-05.

## 6. State and ownership map

| State | Current owner | Scope | ARCH-04 implication |
| --- | --- | --- | --- |
| `valueStack` | `OdtTemplate` | document instance | natural processor input/state |
| `repeatStack` | `OdtTemplate` | document instance | natural processor input/state |
| `values` | inherited/legacy field | compatibility/document | clarify before moving |
| content/styles DOMs | package/context aliases | document instance | processor needs explicit DOM access |
| element registrations | `AbstractOdtTemplate`/element objects | document instance plus static styles | insertion must wait for ARCH-05/style context |
| image registrations/assets | image methods, `StyleMapper`, package | mixed document/process/package | not processor state |
| filters | no separate registry; `match` in `applyFilter()` | code-defined | processor can own evaluation later |
| style registries | `StyleMapper` static properties | process-wide | unresolved `STYLE-CONTEXT-01` dependency |
| debug log/mode | `AbstractOdtTemplate` | document instance | facade/diagnostic compatibility |

No global template-language value store was found. The major global coupling
relevant to ARCH-04 is style/image registration, not variable or loop state.

## 7. Legacy and duplicate paths

### Active paths

- `assign()` / `assignRepeating()` → `render()`;
- `setValues()` and `setRepeating()` remain public compatibility entry points;
- `replaceNl2brInDom()`, `replaceListsInDom()`, `setValuesInDom()`,
  `renderTextBoxes()`, `applyRepeatingInDom()`, and
  `applyConditionalsInDom()` are the active render passes;
- `extractTemplateVariables()` is active as a public inspection method, but
  its parser is not the renderer.

### Compatibility-sensitive paths

- `setValues()` and `setRepeating()` are public and used by historical code;
- `setRepeatingData()` is public and applies its own immediate path;
- protected methods may be overridden or called by external subclasses;
- `setElement()` is public and combines placeholder replacement with styles,
  images, and structured ODF nodes.

### Repository-unused or uncertain paths

No source, test, or sample call sites were found for:

- `applyConditionalsInDomTextBased()`;
- `applyRepeatingInDomTextBased()`;
- `splitConditionalsInTextNodes()`.

They remain protected methods and are therefore not safe to delete in ARCH-04A.
Their exact historical purpose and external subclass usage are uncertain.

## 8. Ordering and compatibility risks

- Moving conditions before loops would change the current DOM behavior.
- Moving ordinary variable replacement after loops could change how outer and
  row values resolve.
- Filter evaluation is not shared by the row-clone replacement path.
- Paragraph-based conditions depend on block markers being in distinct
  `text:p` nodes; the legacy text-node path has different semantics.
- Placeholder normalization can discard formatting around split placeholders.
- The same passes run against styles.xml, so a processor must not assume only
  body content is affected.
- Structured elements may register styles and images before or during
  placeholder replacement; extracting them with template processing would
  duplicate ARCH-05 responsibilities.
- `render()` mutates the DOM and currently has no reset/reparse step. Repeated
  rendering is stable only after placeholders have been consumed, as covered
  by characterization tests.
- Public/protected compatibility methods prevent a simple private-method move.

## 9. Existing and added coverage

Existing coverage includes public samples and integration tests for:

- ordinary variables and repeating rows;
- filters and conditions through Samples 02 and 03;
- structured RichText, lists, tables, HTML import, and images;
- split placeholders through structured-element fixtures;
- package save/reopen and lifecycle behavior;
- public Samples 01–21 via `PublicSampleSmokeTest`.

ARCH-04A added `TemplateProcessingArch04ATest` with characterization for:

1. filters, `nl2br`, and an active conditional branch;
2. comparison conditions, `ifnot`, missing values, zero, and false semantics;
3. repeating row replacement;
4. rendering twice after placeholders are consumed.

The tests intentionally assert current behavior rather than introducing new
syntax or correcting surprising ordering.

## 10. Proposed future TemplateProcessor boundary

The smallest coherent ARCH-04B boundary is a document-scoped collaborator
responsible for:

- receiving assigned values and repeating rows;
- placeholder normalization required specifically for language parsing;
- ordinary and filtered variable evaluation;
- `nl2br` and list-placeholder passes;
- loop expansion;
- condition evaluation and branch removal;
- text-box template-variable replacement only if its DOM dependency is
  explicitly injected.

`OdtTemplate` should remain the stable facade. It should retain public
assignment methods, `render()`, lifecycle methods, structured insertion,
images, metadata, styles, and compatibility aliases while delegating the
language passes.

Structured `OdtElement` insertion, style registration, image asset handling,
and DOM placement belong to ARCH-05 or later document services. A future
processor should receive a narrow insertion collaboration rather than
absorbing `setElement()`.

## 11. Recommended ARCH-04B sequence

ARCH-04B should be split into multiple small substeps:

1. define a characterization-backed processor contract without changing the
   public facade;
2. extract value/filter evaluation and plain DOM replacement;
3. extract special `nl2br`/list passes;
4. extract active repeating-block processing while preserving row replacement
   semantics;
5. extract active paragraph-based conditions;
6. reassess text-box replacement and normalization coupling;
7. keep legacy `setRepeatingData()` and text-based condition/repeating methods
   as compatibility paths until call-site and subclass evidence supports a
   separate decision;
8. run the full public sample and LibreOffice regression suite after each
   substep.

No final public `TemplateProcessor` API is defined by ARCH-04A.

## 12. Explicit exclusions

ARCH-04A and the proposed ARCH-04B boundary do not implement:

- `STYLE-CONTEXT-01` or document defaults;
- package/document ownership changes;
- structured element insertion (ARCH-05);
- image rendering or asset lifecycle redesign;
- metadata or page-layout changes;
- style serialization or finalization extraction;
- new template syntax;
- removal of protected/public compatibility methods;
- HTML rendering, pagination, sections, headers, or footers.

## 13. Open questions

- Should filters inside repeated row clones be supported, or should current
  raw row replacement remain the compatibility contract?
- Should `nl2br` and list placeholders be processor features or injected
  document transformations?
- Can placeholder normalization preserve more inline formatting without
  changing current output?
- Should text-box replacement remain in the processor or become part of a
  later structured document editor?
- What external subclasses depend on the protected legacy methods with no
  repository call sites?
- How should `setRepeatingData()` coexist with `assignRepeating()` once the
  active processor is extracted?
- How should processor-produced styles interact with the future
  document-scoped `StyleContext`?

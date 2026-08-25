# ARCH-04B4 — Facade and Compatibility Review

## 1. Scope

This review assesses the post-ARCH-04B template-processing boundary after:

- ARCH-04B1 scalar replacement and scalar filters;
- ARCH-04B2 `nl2br` and `ul`/`ol` structural placeholders;
- ARCH-04B3A control-structure characterization;
- ARCH-04B3B1 active conditional extraction;
- ARCH-04B3B2 active foreach extraction.

The review does not redesign the template language, remove compatibility
methods, or begin ARCH-05 structured insertion work.

## 2. ARCH-04B extraction summary

`TemplateProcessor` now provides stateless operations over supplied DOM nodes
or text values. It owns no package, document context, assignment stack,
repeat stack, or static template state.

`OdtTemplate` remains the public facade and render orchestrator. It supplies
DOM regions and callbacks where protected subclass behavior must remain
observable.

`AbstractOdtTemplate` still owns structured ODT element insertion and the
protected compatibility helpers used by that insertion and by active foreach
row substitution.

## 3. Final TemplateProcessor responsibility map

| Responsibility | Method | Input/state | DOM mutation or callback | Compatibility assumptions |
|---|---|---|---|---|
| Placeholder normalization | `normalizeTemplateDom()` | Supplied `DOMDocument` | Joins placeholder fragments in paragraph children | Existing split-node behavior, including formatting trade-offs, is preserved |
| Split-placeholder repair | `fixBrokenVariables()` | Supplied `DOMNode` | Merges text fragments across nested spans | Called through the existing protected facade seam |
| Plain/scalar replacement | `replaceScalarText()` | Text, values, filter callback | Returns replacement text | Filter callback returns through protected `OdtTemplate::applyFilter()` |
| Scalar filters | `applyFilter()` | Filter, value, option | No DOM mutation | Existing coercion and passthrough behavior is retained |
| `nl2br` | `replaceNl2brInDom()` | DOM, values | Inserts text and `text:line-break` nodes | Historical surrounding-text behavior is retained |
| `ul`/`ol` placeholders | `replaceListsInDom()` | DOM, values | Replaces qualifying paragraph with an ODT list | Existing list style names and paragraph assumptions remain |
| Active condition processing | `applyConditionalsInDom()` | DOM, values, evaluator callback | Retains/removes existing paragraph nodes | Evaluator callback preserves protected subclass dispatch |
| Condition evaluation | `evaluateCondition()` | Expression and values | No DOM mutation | Existing PHP truthiness and loose comparison behavior remains |
| Active foreach processing | `applyRepeatingInDom()` | DOM, key, rows, row-substitution callback | Removes markers/body, deep-clones and inserts nodes | Callback preserves `replacePlaceholdersInNode()` / `replaceInText()` dispatch |

The processor has no responsibilities for package persistence, metadata,
styles, images, page layout, structured OdtElement insertion, or mutable
template state.

## 4. OdtTemplate facade responsibility map

`OdtTemplate` now primarily provides:

- public assignment and rendering APIs;
- `valueStack` and `repeatStack` ownership;
- render-pass orchestration for content and styles DOMs;
- protected compatibility wrappers around `TemplateProcessor`;
- callbacks into protected replacement/evaluation methods;
- package/document lifecycle delegation;
- metadata, image, page/document finalization, and text-box responsibilities.

The active render sequence remains:

1. `fixBrokenVariables()`;
2. `replaceNl2brInDom()`;
3. `replaceListsInDom()`;
4. `setValuesInDom()`;
5. `renderTextBoxes()`;
6. active repeating blocks;
7. active conditionals.

`render()` dispatches through facade methods rather than calling
`TemplateProcessor` directly where protected polymorphism is part of the
compatibility contract.

Remaining active template-processing wrappers are:

- `normalizeTemplateDom()`;
- `replaceNl2brInDom()`;
- `replaceListsInDom()`;
- `applyConditionalsInDom()`;
- `evaluateCondition()`;
- `applyRepeatingInDom()`;
- `applyFilter()`.

These are intentionally narrow wrappers, not duplicate active algorithms.

## 5. AbstractOdtTemplate remaining responsibilities

### TemplateProcessor compatibility seams

- `fixBrokenVariables()` delegates to `TemplateProcessor` and remains a
  protected override point.
- `setValuesInDom()` delegates scalar text/filter work while retaining the
  structured `OdtElement` branch.
- `applyFilter()` is invoked through the existing protected dispatch from the
  scalar replacement callback.

### Structured insertion / ARCH-05 boundary

- `setElement()`;
- `replacePlaceholderWithDom()`;
- `replacePlaceholdersInNode()`;
- `replaceInText()`;
- `hasPlaceholder()`;
- related DOM replacement and style-registration helpers.

`replacePlaceholdersInNode()` and `replaceInText()` remain active because the
active foreach processor receives them through a callback. They must not be
silently replaced with scalar processor semantics.

### Public inspection and legacy candidates

- `extractTemplateVariables()` remains public inspection/compatibility API.
- `parseTemplateContent()` has no repository runtime call site found and is a
  legacy/inspection candidate, but it is not removed.
- `enableDebugMode()`, `getDebugLog()`, and logging remain public/debug
  responsibilities.

### Document/style responsibility

Style registration, default styles, image-style injection, and finalization
remain document responsibilities outside the template processor.

AbstractOdtTemplate itself is not removed or split in ARCH-04B4.

## 6. Public API compatibility status

The following public APIs remain source-compatible and retain their existing
observable lifecycle behavior:

- constructor;
- `assign()`;
- `setValues()`;
- `assignRepeating()`;
- `setRepeating()`;
- `setRepeatingData()`;
- `setElement()`;
- `render()`;
- `save()`;
- `load()`;
- `refresh()`.

`setRepeatingData()` is intentionally a separate compatibility path. It calls
`applyAllRepeatingBlocksInDom()` directly after placeholder repair rather than
populating `repeatStack` and using the normal `render()` path. It was not
consolidated during ARCH-04B4.

## 7. Protected compatibility seams

| Method | Current workflow | Status |
|---|---|---|
| `fixBrokenVariables()` | Constructor/load preparation, render, structured insertion | Delegating compatibility seam |
| `normalizeTemplateDom()` | Constructor/load preparation | Delegating compatibility seam |
| `setValuesInDom()` | Active render path | Active facade/structured insertion seam |
| `applyFilter()` | Scalar replacement callback | Delegating compatibility seam |
| `replaceNl2brInDom()` | Active render path for both DOMs | Delegating compatibility seam |
| `replaceListsInDom()` | Active render path for both DOMs | Delegating compatibility seam |
| `applyConditionalsInDom()` | Active render path for both DOMs | Delegating compatibility seam |
| `evaluateCondition()` | Conditional processor callback | Delegating polymorphic seam |
| `applyRepeatingInDom()` | Active render path for both DOMs | Delegating compatibility seam |
| `replacePlaceholdersInNode()` | Active foreach row substitution and structured paths | Active compatibility/structured seam |
| `replaceInText()` | Called by `replacePlaceholdersInNode()` | Active protected replacement seam |

Characterization tests confirm that subclass overrides of structural hooks,
condition evaluation, foreach processing, and row replacement remain
observable.

No methods were deprecated or removed.

## 8. Active versus legacy paths

| Path | Classification | Repository evidence |
|---|---|---|
| `TemplateProcessor::applyConditionalsInDom()` | ACTIVE | Called through `OdtTemplate::render()` |
| `TemplateProcessor::applyRepeatingInDom()` | ACTIVE | Called through `OdtTemplate::render()` |
| `OdtTemplate::applyAllRepeatingBlocksInDom()` | COMPATIBILITY-SENSITIVE | Used by public `setRepeatingData()` |
| `OdtTemplate::setRepeatingData()` | PUBLIC COMPATIBILITY CANDIDATE | Public API; no repository call site found |
| `applyConditionalsInDomTextBased()` | REPOSITORY-UNUSED BUT EXTERNALLY POSSIBLE / LEGACY CANDIDATE | No repository call site found; protected visibility remains |
| `splitConditionalsInTextNodes()` | REPOSITORY-UNUSED BUT EXTERNALLY POSSIBLE / LEGACY CANDIDATE | No repository call site found; protected visibility remains |
| `applyRepeatingInDomTextBased()` | REPOSITORY-UNUSED BUT EXTERNALLY POSSIBLE / LEGACY CANDIDATE | No repository call site found; protected visibility remains |
| `replacePlaceholdersInNode()` | ACTIVE / COMPATIBILITY-SENSITIVE | Used by active foreach and structured insertion |
| `replaceInText()` | ACTIVE / COMPATIBILITY-SENSITIVE | Called by `replacePlaceholdersInNode()` |
| `parseTemplateContent()` | LEGACY/INSPECTION CANDIDATE | Definition exists; no runtime call site found |

No path was classified as safe dead code because protected/public visibility
allows external consumers or subclasses to depend on it.

## 9. Render orchestration after extraction

The facade still processes both `content.xml` and `styles.xml`. The pass order
is unchanged, and no user-facing ordering guarantee was newly introduced.

The main intentional orchestration duplication is the explicit two-region
dispatch in `render()`. This remains appropriate because the processor accepts
supplied DOM regions and does not own `OdtDocumentContext`.

The separate `setRepeatingData()` route is the principal alternate
orchestration path and remains intentionally unmodified.

## 10. ARCH-05 boundary

The current boundary is sufficiently clean to begin ARCH-05 later, subject to
its own design review.

The following remain outside `TemplateProcessor`:

- `OdtElement`;
- `RichText`;
- `Paragraph`;
- `RichTable`;
- `ListElement`;
- `replacePlaceholderWithDom()`;
- structured placeholders;
- element placement and insertion behavior;
- style registration associated with structured elements.

The processor decides template-language transformations over existing DOM
regions. It does not construct or place structured ODT elements.

## 11. Test coverage summary

Current ARCH-04 coverage includes:

- plain and filtered scalar values;
- missing values and coercion behavior;
- split placeholders;
- `nl2br` and list placeholders;
- content and styles DOM processing;
- conditions and comparisons;
- foreach rows and styled deep clones;
- malformed and empty control structures;
- repeated processing behavior;
- protected facade and evaluator/replacement polymorphism;
- OdtElement coexistence outside foreach row substitution;
- public sample smoke coverage.

The existing public compatibility path `setRepeatingData()` remains
documented as separate coverage/deprecation-review work rather than being
expanded in ARCH-04B4.

## 12. Future-development links

Relevant existing roadmap items are:

- `TEMPLATE-FORMAT-PRESERVATION-01` — formatting and DOM preservation;
- `TEMPLATE-AUTHORING-UX-01` — authoring complexity and template UX;
- `STYLE-API-02` — compatibility/deprecation strategy for legacy direct APIs;
- `STYLE-CONTEXT-01` — document-scoped style state.

No additional future-development item was added. The current compatibility
findings are sufficiently covered by the existing roadmap items and this
architecture review.

## 13. Remaining technical debt

- `AbstractOdtTemplate` still combines structured insertion, style/document
  concerns, and compatibility helpers.
- Public/protected legacy methods have no formal deprecation policy.
- `setRepeatingData()` and active `render()` repeating paths remain separate.
- Unused text-based conditional and repeating alternatives remain in place.
- `parseTemplateContent()` remains an unreferenced inspection candidate.
- Formatting preservation and complex template authoring remain future work.

These items do not block ARCH-04B completion and should not be cleaned up by
default during this slice.

## 14. Recommended ARCH-04 completion status

ARCH-04B is ready for final preflight. The intended template-language
extraction boundary is implemented without changing public APIs or removing
protected compatibility seams.

No production cleanup is required to declare ARCH-04B complete.

## 15. Recommended next architectural milestone

Proceed to the ARCH-04 final preflight and then the next roadmap milestone,
while keeping ARCH-05 structured insertion as a separate design and
implementation effort. Any future cleanup of legacy template APIs should be
characterized and planned separately from the extraction work.

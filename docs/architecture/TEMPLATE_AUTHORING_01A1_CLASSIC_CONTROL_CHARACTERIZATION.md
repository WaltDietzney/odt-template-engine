# TEMPLATE-AUTHORING-01A1 — Classic Control Format-Preservation Characterization

Status: ACTIVE CHARACTERIZATION / NO PRODUCTION CHANGE

## Goal

Understand what the current classic control implementation actually does to native ODF structure before deciding whether behavior is correct, compatible legacy behavior, or a defect.

The governing rule for this pass is:

> Preserve semantics first; do not infer correctness from visually plausible output alone.

Characterization therefore focuses on native XML ownership, styles, identities, and structure.

## Active and compatibility paths

Current control processing is not represented by only one code path.

### Main render path

`OdtTemplate::render()` currently executes, in order:

1. placeholder repair;
2. `nl2br`;
3. list placeholders;
4. scalar/structured value replacement;
5. text-box compatibility handling;
6. foreach processing through `TemplateProcessor::applyRepeatingInDom()`;
7. conditional processing through `TemplateProcessor::applyConditionalsInDom()`.

This order is part of current observable behavior.

### Direct repeating compatibility path

`OdtTemplate::setRepeatingData()` still calls the facade-local `applyAllRepeatingBlocksInDom()` implementation rather than the extracted `TemplateProcessor` implementation.

That duplicate path is compatibility-sensitive and must be characterized before any consolidation.

### Historical protected helpers

The facade still contains additional historical helpers such as text-based foreach / conditional splitting methods. Search evidence currently shows no active production caller for those helpers. They remain architectural provenance until proven dead/removable by a separate compatibility review.

## First characterization questions

### Conditions

- Does a selected styled paragraph remain byte/structure-equivalent apart from removed control markers?
- Are inline text styles preserved?
- What happens when an unselected branch contains a table, list, Section, frame, or other block whose descendant paragraph is removed?
- Does the control engine remove the structural object, or only paragraphs inside it?
- What happens to bookmarks crossing selected/unselected boundaries?

### Foreach

- Are cloned paragraph and inline styles preserved?
- Are complete structural siblings such as tables cloned intact?
- What happens to document-global native identities such as:
  - `table:name`;
  - `text:name` on Sections;
  - bookmark names;
  - frame names?
- Does classic foreach rewrite identities or duplicate them verbatim?
- How does that compare with the completed SECTION-03 identity-rewriting model?

### Nesting

- Does an `if` inside a classic foreach see row-local data or only the outer/global value map?
- What happens when foreach is inside if, given that render expands repeaters before conditions?
- Which combinations are actually supported versus merely parseable?

### Lifecycle

- Are results stable after save/reopen?
- Are repeated render/save operations stable?
- Does the direct `setRepeatingData()` compatibility path behave the same as `render()`?

## Initial automated characterization

The first test slice is:

`tests/Template/TemplateAuthoring01A1ClassicControlFormatPreservationCharacterizationTest.php`

It deliberately asserts current behavior, including behavior that may later be classified as defective.

The initial cases cover:

1. selected conditional paragraph + span style preservation;
2. an unselected conditional table retaining its table shell while its descendant paragraph is removed;
3. foreach paragraph + span style cloning;
4. foreach cloning a named table without identity rewriting;
5. foreach cloning bookmark names without identity rewriting;
6. foreach cloning a named Section without identity rewriting;
7. an if inside foreach resolving against outer/global condition data rather than row-local data.

No test in this file authorizes the behavior as desirable.

## Expected architectural significance

The likely distinction to verify is:

```text
format/style preservation
    may already be better than historic visual impressions suggest

structural ownership / identity preservation
    may be the deeper defect class
```

For example, cloning a table or Section with `cloneNode(true)` naturally preserves styles, but also preserves native names verbatim. Under the current document model those names are semantic identities, so duplicated names are not equivalent to safe structural repetition.

Similarly, paragraph-based conditional deletion may preserve the selected paragraph's style perfectly while still leaving invalid or semantically empty parent structures behind for an unselected complex block.

This is exactly why TEMPLATE-AUTHORING-01A1 evaluates XML structure rather than only visual formatting.

## Next characterization slices after the first gate

If the first gate confirms the code-derived expectations, continue with:

### A1.2 — Facade/full-render characterization

Use real ODT fixtures through `OdtTemplate::render()` to include normalization, scalar replacement, render ordering, content.xml/styles.xml processing, and save/reopen behavior.

### A1.3 — Compatibility-path comparison

Compare `render()` repeater behavior with `setRepeatingData()` and document any divergence before considering consolidation.

### A1.4 — LibreOffice regression fixtures

Create deliberately styled Writer templates for the high-value cases and compare native XML findings with actual Writer rendering.

Production fixes begin only after these characterization passes establish the failure classes and compatibility boundaries.

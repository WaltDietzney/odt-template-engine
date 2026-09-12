# TEMPLATE-AUTHORING-01A1 — Classic Control Format-Preservation Characterization

Status: COMPLETE / CHARACTERIZATION CLOSED / NO PRODUCTION CHANGE

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


## A1.2 finding — nested condition markers are consumed by facade foreach binding

The first full-render gate exposed an important difference between the isolated `TemplateProcessor` characterization and the actual `OdtTemplate::render()` facade path.

The extracted `TemplateProcessor::applyRepeatingInDom()` accepts a row-replacement callback. In the isolated A1 test, that callback used structure-aware scalar replacement and therefore left control tokens such as:

```text
{{#if:active}}
{{#else}}
{{#endif}}
```

intact for the later conditional pass.

The production facade does **not** use that replacement service for classic foreach row binding. It supplies `OdtTemplate::replacePlaceholdersInNode()`, which delegates each text node to the legacy regex:

```php
/{{(.*?)}}/
```

and treats the entire body of every template token as a data key.

Therefore, inside a repeated block:

```text
{{#if:active}}
```

is interpreted as the row key:

```text
#if:active
```

Because such a key is normally absent, the marker is replaced with an empty string during foreach expansion. The same happens to `{{#else}}` and `{{#endif}}`.

The later conditional pass therefore sees no control markers at all and leaves **both branches** in each repeated instance.

The full-render behavior is thus:

```text
foreach clone
    ↓
legacy row-local replacement consumes nested control markers
    ↓
conditional pass has nothing left to evaluate
    ↓
both conditional branches survive
```

This is stronger than the initial hypothesis that nested conditions merely resolve against the outer/global value map.

It also establishes an architecture distinction that must be preserved during later design work:

```text
TemplateProcessor structural/scalar replacement semantics
    !=
legacy OdtTemplate foreach row-binding semantics
```

This is a defect candidate, but A1 remains characterization-only. No production change is authorized yet.


## A1.4 Writer evidence — revised problem statement

The LibreOffice-authored A1.4 fixture materially refines the original concern about classic template controls.

The key findings are:

- styled foreach paragraphs preserve their Writer formatting in the tested case;
- a fully styled Writer table can be cloned visually intact by classic foreach;
- the same clone duplicates native table identity verbatim;
- repeated Sections and Bookmarks likewise duplicate their native names;
- a paragraph-delimited IF cannot reliably control a sibling Writer table;
- nested IF inside foreach is visibly broken because foreach row binding removes the condition markers before the conditional phase.

Therefore the dominant problem is **not generic formatting destruction**.

The stronger architecture statement is:

> Classic control processing is weak at native structural ownership, native identity, and nested lifecycle semantics even when visual formatting itself survives.

This distinction matters for later design.

We should preserve proven strengths:

- native node cloning;
- Writer-authored paragraph/table styles;
- row-local scalar binding where semantics are unambiguous.

We should not carry the following behaviors into the new high-level template path:

- inferring heterogeneous block ownership solely from paragraph marker positions;
- raw cloning of named native objects without identity rewriting;
- consuming nested control tokens as ordinary row placeholders;
- lifecycle behavior that makes control semantics depend on accidental processing order.

The Writer findings support a structured authoring model in which declarative controls are attached to native objects, especially Sections, and reuse existing identity-aware Section mechanics.

No production change is authorized by A1.4. These findings feed the A1 synthesis and the later TEMPLATE-AUTHORING-01B/C/D/E design passes.


# A1 Synthesis / Closeout

## Decision

TEMPLATE-AUTHORING-01A1 is complete.

A1.1 through A1.4 established the current semantics of classic visible template controls across isolated DOM processing, the real facade/render lifecycle, the direct repeating compatibility path, save/reopen behavior, native XML structure, and actual LibreOffice rendering.

The closeout does **not** authorize production repairs. Its purpose is to freeze the evidence and define the requirements that later TEMPLATE-AUTHORING phases must satisfy.

## What A1 disproved

The investigation began partly from the practical observation that templates containing classic Smarty-like control syntax can differ substantially from the intended output and that formatting appeared to be lost around control blocks.

The evidence does not support the broad statement:

> classic IF/FOREACH generally destroys Writer formatting.

In the characterized cases, ordinary Writer paragraph, inline, and table formatting can survive classic foreach cloning well.

The deeper problems are structural and semantic.

## Established behavior safe to preserve

The following current behavior is valuable and should not be discarded merely because a new high-level authoring path is introduced.

### Native Writer structure and styling can survive cloning

Classic foreach cloning preserves native nodes rather than reconstructing their visual formatting. In the tested Writer fixtures this preserved:

- paragraph styles;
- inline text formatting;
- paragraph spacing/indentation;
- table geometry;
- cell backgrounds/borders;
- table text formatting.

This is a genuine strength.

### Simple visible placeholders remain appropriate

Classic `{{variable}}`-style binding remains a useful portable authoring mechanism for scalar content where no native Writer semantic object provides a concrete advantage.

The new template philosophy is additive, not a replacement for simple template syntax.

### Row-local scalar replacement is useful

Within an otherwise unambiguous repeated subtree, row-local scalar data binding is useful behavior and should remain available in future orchestration.

### Existing imperative APIs remain valid

A future inspectable/declarative render path must complement rather than replace the lower-level imperative APIs.

## Compatibility behavior that must remain isolated

A1 also identified behavior that may need to remain available for backward compatibility but must **not** define the semantics of the new high-level path.

### Paragraph-marker structural inference

Classic conditions infer a branch from visible control-marker paragraphs and intervening paragraph positions.

That model cannot reliably own heterogeneous native siblings such as:

- tables;
- Sections;
- frames;
- other block-level Writer structures.

Compatibility may require preserving classic behavior, but new structured controls must not use this as their semantic foundation.

### Raw clone identity semantics

Classic foreach can duplicate native identities verbatim, including characterized cases for:

- `table:name`;
- Section `text:name`;
- bookmark names.

The output may look correct while being structurally ambiguous.

Future structured repetition must use identity-aware document mechanics.

### Legacy row placeholder matching

The facade foreach path treats every `{{...}}` token inside a repeated block as a possible row key. This includes nested control tokens.

That compatibility behavior must not become the parser/binding contract of the new orchestration path.

### Processing-order dependence

Current classic semantics depend materially on render ordering. That ordering is observable compatibility behavior, but it is not a suitable semantic definition for future nested declarative controls.

## Evidenced defect candidates

A1 establishes the following defect candidates without repairing them.

### DC-1 — Conditional structural ownership

A classic IF around a real Writer table can leave/render the table even when the intended branch is false.

The visible marker syntax does not provide a reliable native subtree boundary.

### DC-2 — Duplicate native identities during repetition

Classic foreach clones named Writer structures without identity rewriting.

This is visually plausible but structurally unsafe.

### DC-3 — Nested IF inside FOREACH

In the full render path, foreach row replacement consumes `{{#if:...}}`, `{{#else}}`, and `{{#endif}}` before the later conditional pass.

Both branches therefore survive.

This is a visible functional defect, not merely an internal architecture concern.

### DC-4 — Classic branch leakage across Writer structure

The Writer fixture demonstrated surviving content from a branch that the author intended to remove. This reinforces that paragraph-delimited branch ownership is not equivalent to native structural ownership.

## Compatibility-path conclusion

The direct repeating compatibility path and the main render path are not permission to consolidate implementation opportunistically.

Where paths differ, the divergence must remain explicit until a later change has:

1. a defined semantic target;
2. characterization coverage;
3. a compatibility decision;
4. a migration or facade strategy where required.

A1 therefore closes with **no compatibility-path consolidation**.

## Architecture requirements derived from A1

The following requirements now constrain TEMPLATE-AUTHORING-01B through 01F.

### R1 — Inspection must describe both simple and native template semantics

Unified inspection must be able to discover supported template meaning across at least the relevant categories established in the roadmap:

- classic variables/filter expressions;
- classic controls where supported;
- Sections;
- bookmarks;
- named tables;
- named frames/structured objects where supported;
- bounded native fields;
- declarative structural controls;
- diagnostics and dependencies.

Inspection must expose what the template actually declares. It must not silently invent or fuzzy-correct semantics.

### R2 — Structural controls require real native ownership boundaries

A future declarative structural control must operate on a native subtree rather than infer ownership from visually adjacent marker paragraphs.

Writer Sections are the primary evidenced candidate because they already provide:

- a native named object;
- a bounded subtree;
- existing engine addressing;
- existing Section instantiation mechanics;
- identity-aware architecture from SECTION-03.

A name such as:

```text
#foreach:experience
#if:photo
#ifnot:photo
```

can therefore carry template meaning while LibreOffice remains the visual designer.

The exact public syntax/API still requires its dedicated design contract.

### R3 — Repetition must be identity-aware

Structured repetition must not use raw `cloneNode(true)` semantics as its complete contract when the subtree contains named native objects.

It should build on the established Section/document identity-rewriting mechanics rather than create a competing identity system.

### R4 — Nested controls need explicit data scope and evaluation semantics

Future nesting must define, before implementation:

- global data scope;
- foreach row-local scope;
- lookup precedence;
- nested foreach scope;
- condition evaluation inside repetition;
- behavior for missing values;
- deterministic evaluation order.

The A1.6 behavior is explicitly **not** an acceptable model for the new path.

### R5 — Scalar binding and control parsing must be semantically distinct

A token representing template control syntax must not accidentally be consumed as an ordinary scalar row key.

Future orchestration needs a semantic distinction between:

```text
data binding
control declarations
native object declarations
```

even if classic compatibility syntax continues to share visible braces.

### R6 — Preserve authored ODF instead of rebuilding presentation

The new path should preserve the successful property of current cloning: Writer-authored formatting remains native.

The engine should orchestrate existing ODF structure rather than reconstruct Writer layout in PHP.

### R7 — High-level render must orchestrate, not replace lower-level APIs

The conceptual target remains:

```php
$schema = $template->inspect();
$template->render($mappedData);
```

but A1 does not approve those exact signatures.

The future render pipeline should coordinate existing capabilities and new declarative semantics while preserving compatible imperative entry points.

### R8 — Diagnostics are part of template semantics

Because visually plausible output can be structurally unsafe, inspection/rendering should eventually be able to report unsupported or ambiguous declarations rather than silently producing misleading output.

Examples include malformed declarative names, unsupported nesting, ambiguous native identities, or declarations on unsupported native object types.

### R9 — Repeated render/save lifecycle must remain explicit

Future orchestration must define and test repeated render/save and save/reopen behavior. A declarative template contract is incomplete if it works only for a single transient DOM pass.

## Implications for the remaining milestone

A1 provides the evidence base for the remaining TEMPLATE-AUTHORING-01 sequence.

### Phase B — Unified Template Inspection

Next.

B should first define the inspection model and discovery semantics. It should not begin by implementing a broad public API from assumptions.

The primary question becomes:

> What template contract can be discovered reliably from the actual ODF document before rendering?

### Phase C — Native Field Binding

C should investigate bounded Writer field semantics where they improve authoring compared with visible placeholders. A1 does not justify replacing `{{variable}}` for ordinary scalar values.

### Phase D — Declarative Structural Controls

D should define native structural declarations over real Writer objects, with Sections as the primary candidate. It must specify nesting, data scope, identity handling, and diagnostics before implementation.

### Phase E — High-Level Render Pipeline

E should compose the established simple and structured mechanisms into deterministic orchestration. It must not merely call the current classic passes in an accidental order and label that a new architecture.

### Phase F — Authoring Documentation & Samples

F is part of the 1.0 deliverable, not cosmetic cleanup.

The documentation must teach developers how to create templates whose layout remains Writer-authored while their data/control contract is machine-discoverable. This is essential to the intended application pattern:

```text
form/application data
    -> mapping
    -> inspectable ODT template contract
    -> high-level render
    -> native ODT
```

The polished 1.0 samples should demonstrate this philosophy rather than only individual low-level API calls.

## A1 final architecture statement

TEMPLATE-AUTHORING-01A1 closes with the following architecture conclusion:

> The classic template language remains valuable as a simple, portable text-binding layer, and its ability to preserve native Writer formatting should be retained. It is not, however, a sufficient structural document model. Complex template logic must be anchored to real ODF ownership boundaries, repeated native objects must be identity-aware, and nested control/data scope must be explicit. TEMPLATE-AUTHORING-01 should therefore evolve the engine toward an additive, inspectable template model in which simple visible syntax and native structured declarations cooperate rather than compete.

## Closeout gate

A1 is **COMPLETE** when the repository records:

- isolated classic-control characterization;
- full-render/facade characterization;
- compatibility-path comparison;
- LibreOffice/native XML evidence;
- the safe-to-preserve behavior;
- compatibility-only behavior;
- evidenced defect candidates;
- architecture requirements for B/C/D/E/F.

Those conditions are now satisfied.

No production behavior was changed as part of A1.

**TEMPLATE-AUTHORING-01A1: COMPLETE / CHARACTERIZATION CLOSED.**

Next architecture slice:

```text
TEMPLATE-AUTHORING-01B — Unified Template Inspection
```

# TEMPLATE-AUTHORING-01A1.3 — Compatibility Path Comparison

Status: ACTIVE CHARACTERIZATION / NO PRODUCTION CHANGE

## Purpose

Compare the two currently active classic repeating paths before any consolidation or refactoring:

```text
render()
    -> repeatStack
    -> OdtTemplate::applyRepeatingInDom()
    -> TemplateProcessor::applyRepeatingInDom()

setRepeatingData()
    -> OdtTemplate::applyAllRepeatingBlocksInDom()
```

The two paths are similar in implementation, but they are not lifecycle-equivalent and they cross different protected facade boundaries.

## Characterization matrix

The first comparison slice covers:

1. simple repeated paragraphs;
2. duplicated named-table identity;
3. interaction with global scalar values;
4. nested control markers;
5. protected facade dispatch.

## Expected findings from code inspection

### Simple structure

For a simple repeat block containing only row-local placeholders, both paths should produce equivalent visible content.

### Native identity

Both paths clone native nodes with `cloneNode(true)` and do not perform SECTION-03 identity rewriting. Named tables, bookmarks, Sections, and frames therefore remain candidates for duplicated native identity.

### Scalar lifecycle ordering

The render path applies global scalar values **before** foreach expansion:

```text
global scalar replacement
    ↓
foreach row replacement
```

The direct `setRepeatingData()` path expands foreach immediately and uses the legacy row-local replacement regex. Unknown placeholders inside the repeated block are removed as missing row keys before a later render can bind them.

This creates an observable divergence.

Example:

```text
template:
    {{name}} / {{global}}

global data:
    global = GLOBAL

row:
    name = Alpha
```

Expected current behavior:

```text
render path:
    Alpha / GLOBAL

setRepeatingData() then render:
    Alpha /
```

This is a compatibility/lifecycle distinction, not yet an approved defect fix.

### Nested controls

Both active repeat paths use `OdtTemplate::replacePlaceholdersInNode()` for row binding.

That legacy replacement treats any `{{...}}` token as a row key, so nested control markers such as `{{#if:active}}`, `{{#else}}`, and `{{#endif}}` are consumed before the later conditional pass.

The behavior should therefore be shared by both paths.

### Protected facade boundaries

The paths dispatch through different protected methods:

```text
render()
    -> applyRepeatingInDom()

setRepeatingData()
    -> applyAllRepeatingBlocksInDom()
```

External subclasses could theoretically override either protected method. Any future consolidation must therefore account for polymorphic compatibility and cannot silently bypass existing protected hooks.

## Test

The active characterization test is:

`tests/Integration/TemplateAuthoring01A13CompatibilityPathComparisonTest.php`

No production code is changed by A1.3.

## Evaluation rule

If the test confirms these expectations, the architecture conclusion is not that the paths should immediately be merged.

Instead, later design must decide:

- which behavior is authoritative for the 1.0 high-level render pipeline;
- which legacy behavior must remain compatibility-preserved;
- whether protected facade wrappers are required during extraction/consolidation;
- how global and row-local data scopes should interact;
- how unknown placeholders inside repeated blocks should behave;
- how nested declarative/classic controls should be ordered.

Only after these semantics are explicit should implementation change.

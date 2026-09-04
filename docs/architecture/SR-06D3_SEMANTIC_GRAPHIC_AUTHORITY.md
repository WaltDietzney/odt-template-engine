# SR-06D.3 — Semantic Graphic Authority Transition

Status: implementation and compatibility transition slice

Branch: `architecture/sr-06d3-semantic-graphic-authority`

Depends on:

- `SR-06D1_GRAPHIC_RESOLUTION_CONTRACT.md`
- `SR-06D2_GRAPHIC_STYLE_MATERIALIZER.md`
- `SR-06C1_GRAPHIC_PRODUCER_SEMANTICS_CONTRACT.md`

## 1. Purpose

SR-06D.2 made semantic `graphic` definitions real target-document styles. D.3 addresses the next question:

> When a structured drawing object is materialized, which graphic style identity does its `draw:style-name` actually reference?

For `CircularImageElement`, semantic and legacy graphic style identities already coincide because both are derived from the same approved appearance properties.

For `DrawTextBox`, they do not necessarily coincide. The semantic identity excludes drawing structure, geometry, placement, flow, overlap, and unclassified legacy properties, while the historical frame-style identity hashes the full mapped option set.

D.3 introduces a bounded authority transition without silently migrating frame-layout semantics.

## 2. Core rule

A `DrawTextBox` references its semantic graphic style when all non-semantic options are already represented directly on the drawing object and no legacy graphic style is still required as the carrier of behavior.

If a non-semantic property still depends on the legacy graphic style carrier, `DrawTextBox` continues to reference the legacy style identity.

This yields:

```text
semantic appearance only
+ directly emitted geometry / placement
        ↓
semantic draw:style-name
```

but:

```text
semantic appearance
+ legacy-carried layout / flow / overlap / unclassified property
        ↓
legacy draw:style-name
```

This is intentionally a transition rule, not the final removal of legacy graphic styles.

## 3. Why the transition must be bounded

A direct unconditional switch from the legacy style name to the semantic style name would lose behavior for properties that SR-06C deliberately excluded from semantic graphic identity but that are still serialized through `style:graphic-properties` by the legacy path.

Examples include current compatibility options such as:

- `draw:wrap-influence-on-position`;
- `loext:allow-overlap`;
- `style:flow-with-text`;
- corner-radius properties currently mapped as `svg:rx` / `svg:ry`;
- unknown mapper passthrough properties that have not yet been classified.

Migrating those properties is not part of SR-06. In particular, D.3 must not absorb `FRAME-LAYOUT-01`.

## 4. Directly materialized non-semantic properties

The current `DrawTextBox::toDomNode()` already emits these option categories directly on `draw:frame`:

- width;
- height;
- anchor;
- horizontal position;
- horizontal relation;
- vertical position;
- vertical relation.

These properties may therefore differ between two drawing objects without forcing their shared semantic appearance style to differ.

D.3 allows such objects to reference the semantic style identity directly.

## 5. Semantic appearance authority

When the bounded direct-reference condition is met:

1. `getOwnStyleRequirements()` produces the semantic graphic definition;
2. `OdtTemplate::setElement()` registers and materializes that definition before drawing DOM insertion;
3. `DrawTextBox::toDomNode()` emits the semantic requirement name as `draw:style-name`;
4. legacy frame-style registration may still occur for compatibility, but the rendered drawing object no longer depends on that legacy style for appearance.

This is the first point in SR-06 where DrawTextBox semantic graphic identity becomes directly authoritative for rendered appearance.

## 6. Compatibility carrier fallback

If mapped frame options contain any non-semantic property that is not one of the directly emitted drawing attributes listed above, the legacy style remains the rendered carrier.

This is conservative by design.

D.3 does not attempt to prove that arbitrary passthrough properties are safe to discard or relocate. Unknown or unclassified properties therefore keep the object on the compatibility path.

The semantic graphic definition may still be registered and materialized in this case, but it is not yet the sole rendered carrier.

## 7. Structure-only text boxes

A structure-only `DrawTextBox` produces no semantic graphic definition under SR-06C.

D.3 preserves this rule. Such an object continues to use its current legacy graphic style identity rather than inventing a semantic appearance style merely to replace the compatibility name.

## 8. CircularImageElement

`CircularImageElement` already derives both semantic and legacy graphic style names from the same bitmap-fill appearance definition.

Therefore its `draw:custom-shape` already references the semantic style identity after D.2 materialization.

D.3 does not change CircularImage production code.

The referenced `draw:fill-image` declaration remains a separate legacy dependency until SR-06E.

## 9. OdtTemplate orchestration

D.3 deliberately does not restructure `OdtTemplate::setElement()`.

The existing order remains valuable during the transition:

1. collect semantic requirements;
2. register them in document-local `StyleContext`;
3. materialize semantic definitions;
4. run current paragraph/text and HasStyles compatibility preparation;
5. copy resources;
6. materialize the drawing DOM;
7. collect/register post-materialization legacy graphic/fill-image requirements.

The important D.3 property is that a semantic style referenced by a drawing object is already present before the object is inserted.

Broader lifecycle/orchestration normalization remains outside SR-06D.

## 10. Existing-document authority

If the semantic style name already exists in the target document, D.1/D.2 authority rules preserve that authored definition.

A DrawTextBox that qualifies for semantic direct reference therefore references the existing target-document style with that semantic name rather than creating or switching to a different legacy appearance identity.

No authored style is overwritten.

## 11. Legacy state remains compatibility state

D.3 does not remove:

- frame style requirements;
- image style requirements;
- fill-image requirements;
- `HasStyles` compatibility behavior;
- post-materialization legacy collection;
- `StyleWriter` legacy finalization.

Unused legacy styles may still be registered/materialized during this transition. Their reduction belongs to SR-06F.

## 12. Required regression coverage

Tests must prove at minimum:

1. DrawTextBox semantic identity still excludes geometry and placement;
2. a DrawTextBox with semantic appearance plus directly emitted geometry references the semantic graphic style;
3. that semantic style has already been materialized in `styles.xml` through `setElement()`;
4. semantic and legacy identities can differ while the semantic identity is rendered;
5. a DrawTextBox with an unmigrated legacy-carried property such as `allow-overlap` still references the legacy style;
6. a structure-only DrawTextBox continues to use the legacy carrier;
7. CircularImage semantic/legacy identity behavior remains unchanged;
8. D.1 resolution and D.2 materialization tests remain green;
9. C-era compatibility preflight remains green.

## 13. Explicit non-goals

SR-06D.3 does not:

- migrate frame layout/flow semantics;
- move `allow-overlap`, wrapping, flow, corner radius, or arbitrary passthrough properties into semantic graphic identity;
- create a new layout-style requirement family;
- redesign `StyleMapper`;
- redesign `StyleWriter`;
- remove legacy graphic style materialization;
- migrate fill-image dependencies;
- change normal `ImageElement` semantics;
- fix CircularImage rendering;
- clean up duplicated `setElement()` collection passes;
- begin SR-06E/F or SR-07.

## 14. Exit condition

SR-06D.3 is complete when semantic graphic styles are directly referenced wherever doing so is behavior-preserving under the current classified boundary, while compatibility-only graphic carriers remain active wherever they are still required.

This establishes a real but bounded authority transition:

> semantic appearance becomes authoritative where the engine can prove that no unmigrated legacy-carried behavior would be lost.

SR-06D.4 can then perform integration and compatibility preflight across the complete D-series before SR-06E begins fill-image dependency migration.

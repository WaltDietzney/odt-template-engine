# FRAME-LAYOUT-01C Slice 5 — Compatibility Layout-policy Migration Review

Status: CLOSED / GATE GREEN

## Scope

Slice 5 migrated retained compatibility layout policies onto the normalized semantic graphic-style path without widening the friendly `setFrameLayout()` API.

Migrated compatibility concerns:

- `flowWithText()` -> `style:flow-with-text`;
- `wrap-influence` -> `draw:wrap-influence-on-position`;
- `setAllowOverlap()` -> `loext:allow-overlap`.

These remain compatibility/specialized APIs. They are not new friendly master-array keys.

## Call-order contract

Primary wrap and compatibility policy state are independent concerns.

For example:

```php
$box
    ->flowWithText(true)
    ->setFrameWrap('parallel')
    ->setAllowOverlap(false);
```

must preserve all three semantic properties.

A later `setFrameWrap()` call changes only `style:wrap` and does not clear flow-with-text, wrap influence, or overlap.

## Compatibility behavior

Historical compatibility input remains callable.

In particular, historical wrap-influence values are preserved verbatim and are not silently normalized or reinterpreted.

The public compatibility surface therefore remains backward compatible while style ownership moves to the semantic requirement path.

## Semantic style identity

Equivalent compatibility-policy state now participates in one coherent graphic style identity together with migrated friendly frame-layout properties and appearance properties.

The focused tests verify:

- one semantic graphic requirement per element;
- stable style identity for equivalent state independent of setter order;
- semantic style deduplication;
- stable repeated materialization/save;
- both frames referencing the same semantic graphic style.

Unmigrated legacy-only properties remain covered by the legacy-carrier path.

## Automated gate

The focused Slice 5 gate passed locally after updating expected characterization at the explicitly approved migration boundary and correcting namespace-aware test access.

No production rollback or compatibility workaround was required.

`git diff --check` was clean.

## Architectural conclusion

FRAME-LAYOUT now has one coherent style-side carrier for:

```text
appearance
+ friendly frame-layout properties
+ retained compatibility layout policies
```

while object geometry remains outside graphic style identity.

This preserves the intended ownership split:

```text
draw:frame object attributes
    anchor
    width / height
    x / y

semantic graphic style
    alignment / relation
    wrap
    flow-with-text
    wrap influence
    allow-overlap
    appearance
```

No Change Contract stop condition was triggered.

## Closeout

Slice 5 is closed.

Next: Slice 6 — Integration / public sample / visual regression / final FRAME-LAYOUT closeout.

# FRAME-LAYOUT-01C Slice 1 — DrawingLayout Core Review

Status: CLOSED / GATE GREEN

Reviewed:

- `src/Elements/DrawingLayout.php`
- `src/Elements/DrawingLayoutProjector.php`
- `tests/Elements/FrameLayout01CSlice1DrawingLayoutTest.php`
- `FRAME_LAYOUT_01C_CHANGE_CONTRACT.md`

## 1. Overall verdict

The Slice 1 architecture is sound and matches the Change Contract well.

Confirmed strengths:

- one immutable semantic authority;
- no document/global mutable state;
- alignment and offset are distinct modes;
- reference area is explicit;
- object geometry and graphic-layout projection are separated;
- `as-char` inline-flow semantics can be derived without inspecting DOM;
- percentage pseudo-position is rejected;
- conflicting alignment+offset input is rejected;
- call-order replacement on one axis is canonical;
- no DrawTextBox/ImageElement producer behavior has been changed yet.

No stop condition from the Change Contract is triggered.

## 2. Finding A — anchor changes can invalidate existing axis state

Current:

```php
$layout = DrawingLayout::fromArray([
    'anchor' => 'paragraph',
    'horizontal' => [
        'alignment' => 'center',
        'relative-to' => 'paragraph',
    ],
]);

$layout->withAnchor('page');
```

re-validates the retained horizontal relation against the new anchor and throws because `paragraph` is not valid for a page anchor.

Likewise changing to `as-char` while horizontal placement exists throws.

This behavior is semantically defensible and preferable to silently rewriting an explicitly authored relation.

Decision for Slice 1:

> Preserve strict behavior: `withAnchor()` changes only anchor; if retained axis state becomes invalid, throw `InvalidArgumentException`.

Do not silently rewrite:

```text
paragraph -> page
paragraph -> baseline
```

because that would guess author intent.

A focused test should freeze this rule before public convenience methods delegate to it in Slice 2/3.

## 3. Finding B — width/height currently accept negative lengths

The shared length validator accepts signed absolute lengths for all concerns:

```text
-2cm
+1cm
```

Negative offsets are meaningful and should remain allowed.

Negative frame width/height are not meaningful friendly geometry and should be rejected.

Required correction:

- width/height: positive absolute lengths only;
- x/y offsets: signed absolute lengths allowed.

This is a semantic validation correction inside Slice 1 and does not affect legacy producer behavior.

## 4. Finding C — as-char middle/bottom need contract evidence gate

The core currently accepts:

```text
as-char + top/middle/bottom + baseline
```

The Change Contract explicitly says top/middle/bottom must be verified before production support for all three is considered complete.

Slice 1 may represent these values, but producer migration must not claim Writer acceptance until the required evidence/test exists.

Required action:

- record this as a Slice 2/4 acceptance gate;
- no need to remove the values from the semantic core now.

## 5. Finding D — public master API is correctly not wired yet

Slice 1 has not yet added `setFrameLayout()` to DrawTextBox/ImageElement.

This is acceptable because the contract says the Slice 1 core should first prove semantic state/projection independently from broad producer migration.

Public producer wiring belongs naturally to Slice 2 and Slice 3.

No correction required.

## 6. Finding E — projector responsibility is appropriately bounded

`DrawingLayoutProjector` currently projects:

- object attributes;
- graphic-layout properties;
- inline-flow predicate.

It does not:

- create DOM;
- register styles;
- own document context;
- mutate the semantic state.

This is contract-compliant.

The inline-flow predicate may later be moved behind a dedicated insertion enum/capability in Slice 4 without invalidating the semantic core.

## 7. Required Slice 1 closeout corrections

Before closing Slice 1:

1. reject negative width/height while retaining signed offsets;
2. add a test that anchor mutation rejects incompatible retained axis state rather than silently rewriting it;
3. add explicit signed-offset coverage;
4. retain the as-char top/middle/bottom evidence requirement for later producer/insertion acceptance.

After these focused corrections, Slice 1 can close and Slice 2 may begin.


## 8. Closeout

The final Slice 1 gate passed locally:

```text
34 tests
148 assertions
0 failures
0 errors
1 pre-existing PHPUnit deprecation
```

`git diff --check` was clean.

Slice 1 is therefore closed. FRAME-LAYOUT-01C may proceed to Slice 2 — DrawTextBox migration.

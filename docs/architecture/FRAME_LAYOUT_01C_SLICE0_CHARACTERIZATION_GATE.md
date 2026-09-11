# FRAME-LAYOUT-01C Slice 0 — Characterization Gate

Status: IN PROGRESS / PRODUCTION CODE UNCHANGED

Contract: FRAME_LAYOUT_01C_CHANGE_CONTRACT.md

## 1. Gate rule

Slice 0 MUST NOT change production behavior. The tests characterize current behavior even where that behavior is known to be invalid or Writer-ineffective.

A green characterization test means: this is what the engine does before FRAME-LAYOUT migration — not that the behavior is the target semantic contract.

## 2. Existing coverage carried into the gate

FrameLayout01A0CurrentApiCharacterizationTest already freezes:

- short/long horizontal setter families and differing default relations;
- short/long vertical setter families and differing default relations;
- DrawTextBox floating paragraph wrapper versus bare as-char frame;
- Sample 17 100% horizontal pass-through;
- Sample 17 50% horizontal/vertical pass-through;
- wrap-influence legacy carrier behavior;
- flowWithText();
- setAllowOverlap().

Existing SR-06 / graphic-boundary tests additionally freeze semantic graphic appearance identity, legacy graphic carrier selection, ImageElement materialization mutation stability, current image style identity behavior, and repeated equivalent materialization behavior.

## 3. New compatibility characterization

FrameLayout01CSlice0CompatibilityCharacterizationTest adds:

1. legacy from-left/from-top setters without x/y do not invent coordinates;
2. ImageElement align=left/right/center/absolute mapping;
3. align=absolute does not invent svg:x;
4. explicit ImageElement svg:x/y pass-through;
5. ImageElement width-only autoscaling;
6. ImageElement height-only autoscaling;
7. observable ImageElement materialization mutation remains stable.

These tests deliberately preserve historical output rather than normalize it.

## 4. New insertion-boundary characterization

FrameLayout01CSlice0InsertionCharacterizationTest freezes the H1-H7 defect:

- body ImageElement as-char currently replaces its paragraph with a bare draw:frame;
- header ImageElement as-char currently replaces its paragraph with a bare draw:frame;
- as-char DrawTextBox currently follows the same materializer rule;
- floating DrawTextBox currently returns a text:p -> draw:frame wrapper before replacement.

This difference is frozen before Slice 4 changes insertion ownership.

## 5. Deliberately reused existing coverage

Slice 0 relies on established suites rather than duplicating every SR-06 assertion for semantic graphic style requirement scope/identity, legacy frame/image compatibility registration, fill-image/resource ownership, CircularImageElement custom-shape boundaries, page-flow/header resource characterization, and public sample smoke behavior.

## 6. Local gate command

Run:

    vendor/bin/phpunit \
      tests/Integration/FrameLayout01A0CurrentApiCharacterizationTest.php \
      tests/Integration/FrameLayout01CSlice0CompatibilityCharacterizationTest.php \
      tests/Integration/FrameLayout01CSlice0InsertionCharacterizationTest.php \
      tests/Integration/StyleContextGraphicDrawingBoundaryCharacterizationTest.php \
      tests/Integration/DrawTextBoxSemanticGraphicProducerTest.php \
      tests/Integration/ImageElementSemanticGraphicProducerTest.php

Then:

    git diff --check

Expected result: all characterization green; production source unchanged.

## 7. Exit condition

Slice 0 is complete when:

1. the focused characterization gate is green locally;
2. no production file changed in the slice;
3. git diff --check is clean;
4. unexpected failures are resolved by correcting characterization or documenting a repository contradiction, not by changing production code.

Only then may FRAME-LAYOUT-01C proceed to Slice 1 — DrawingLayout semantic core.
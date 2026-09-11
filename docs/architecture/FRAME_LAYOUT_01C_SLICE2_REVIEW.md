# FRAME-LAYOUT-01C Slice 2 — DrawTextBox Migration Review

Status: CLOSED / AUTOMATED AND WRITER GATES GREEN

## Scope

Slice 2 migrated `DrawTextBox` onto the FRAME-LAYOUT semantic core while preserving legacy compatibility paths.

Implemented and verified:

- `setFrameLayout()` master API;
- friendly anchor, size, horizontal/vertical alignment, offset and wrap semantics;
- convenience methods sharing one `DrawingLayout` authority;
- separation of native frame geometry from graphic-style layout properties;
- legacy setters remain callable and retain pass-through compatibility behavior;
- friendly semantics reject percentage pseudo-positioning;
- call-order replacement between alignment and offset is deterministic.

## Automated gate

The focused Slice 2 test set passed locally after the migration, including:

- Slice 1 semantic-core tests;
- DrawTextBox Slice 2 tests;
- DrawTextBox materialization integration test;
- A0 API characterization;
- Slice 0 compatibility and insertion characterization;
- semantic graphic producer characterization;
- StyleContext drawing-boundary characterization.

`git diff --check` was clean.

## LibreOffice Writer evidence

The migrated `samples/sample_17_textfield.php` output was opened directly in LibreOffice Writer without a repair error.

Manual visual inspection confirmed all three intended placement classes:

1. floating right-aligned text box: rendered at the right side with surrounding text behavior;
2. horizontally/vertically centered floating text box: rendered in the intended page-content-relative center area;
3. `as-char` inline text box: remained in text flow and rendered plausibly relative to the baseline.

This is the first direct Writer acceptance evidence for the new FRAME-LAYOUT friendly producer path.

## Architectural conclusion

The original positioning problem in sample 17 was not evidence that Writer cannot position `draw:frame` text boxes. The migrated output confirms the carrier split established by FRAME-LAYOUT:

- frame/object attributes own anchor, size and explicit coordinates;
- graphic style properties own alignment mode, relation and wrap;
- insertion semantics remain a separate concern.

The new semantic path therefore resolves the practical DrawTextBox positioning case without requiring a rewrite of Writer's layout model in PHP.

## Closeout

No FRAME-LAYOUT stop condition was triggered.

Slice 2 is closed.

Next: Slice 3 — ImageElement migration onto the same semantic frame-layout authority.

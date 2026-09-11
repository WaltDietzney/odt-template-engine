# FRAME-LAYOUT-01C Slice 4 — Anchor-sensitive Structured Insertion Review

Status: CLOSED / AUTOMATED GATE GREEN / LIBREOFFICE VISUAL REGRESSION GREEN

## Scope

Slice 4 moved anchor-sensitive insertion responsibility to the generic structured insertion boundary.

The production change introduces explicit insertion semantics:

- `StructuredInsertionMode::BLOCK`
- `StructuredInsertionMode::INLINE_TEXT_FLOW`
- `StructuredInsertionMode::PRESERVE_TEXT_CONTAINER`

`OdtElement` defaults to block insertion. Frame-backed elements opt into insertion semantics according to the native carrier they require. `as-char` uses inline text-flow insertion; floating `ImageElement` insertion preserves a text paragraph carrier.

Current participating elements:

- `DrawTextBox`
- `ImageElement`

The existing protected `OdtTemplate::replacePlaceholderWithDom()` facade keeps its public/protected compatibility signature. Element insertion semantics are supplied out-of-band for the duration of the replacement and restored afterwards.

## Behavioral rule

For block insertion, the established structured replacement behavior remains unchanged.

For inline text-flow insertion, the materializer replaces only the placeholder text and preserves its containing paragraph.

This establishes the native ODF carrier rule:

```text
as-char draw:frame
    -> child of text:p / text:h
    -> paragraph carrier preserved
```

rather than:

```text
text:p containing placeholder
    -> replaced by bare draw:frame
```

## Automated gate

The focused Slice 4 gate is green.

Coverage includes:

- as-char `ImageElement` reports inline text-flow insertion;
- floating `ImageElement` preserves a paragraph/text container carrier;
- as-char `DrawTextBox` reports inline text-flow insertion;
- body paragraph preservation;
- surrounding text preservation;
- header paragraph preservation;
- unchanged block replacement behavior;
- Slice 0 insertion characterization;
- Slice 2 DrawTextBox behavior;
- Slice 3 ImageElement behavior and integration;
- semantic graphic producer compatibility.

## LibreOffice visual regression

The cross-part image research matrix was rerun after Slice 4.

The decisive cases are:

```text
H8 — Friendly ImageElement, as-char, body
H9 — Friendly ImageElement, as-char, header
```

Before Slice 4 both placeholders were consumed but the generated images were Writer-invisible.

After Slice 4:

- H8 renders the generated image visibly in the body;
- H9 renders the generated image visibly in the header;
- the untouched counterpart placeholder remains visible in each fixture, confirming that the intended target part was modified.

This is the expected visual result.

## Root-cause resolution

The A0 research hypothesis is now experimentally confirmed.

The original visibility defect was not primarily caused by:

- image resource packaging;
- manifest registration;
- ImageElement geometry;
- graphic-style ownership;
- header prohibition;
- a need for an ImageElement-specific writer workaround.

The decisive defect was structural:

```text
as-char draw:frame
+ removal of its text:p carrier during structured replacement
= Writer-invisible result
```

Preserving the paragraph carrier resolves the defect in both `content.xml` and header content in `styles.xml`.

## Architectural conclusion

FRAME-LAYOUT now has a clean responsibility split:

```text
DrawingLayout
    semantic frame layout authority

DrawTextBox / ImageElement
    element-specific frame production
    + effective insertion semantics

StructuredElementMaterializer
    anchor-sensitive structural insertion

StyleRequirement pipeline
    graphic-style materialization
```

No ImageElement-specific header workaround is required.

The same structural rule is reusable by future frame-backed/draw elements.

## Compatibility conclusion

The compatibility strategy remains intact:

- default structured elements remain block-inserted;
- only elements explicitly reporting non-default insertion semantics change generic block replacement behavior;
- legacy block behavior is preserved;
- the protected replacement facade signature remains unchanged;
- anchor semantics, rather than concrete element type, select the structural insertion path.

## Closeout

No Change Contract stop condition is triggered.

Slice 4 is closed.

The H8/H9 visual evidence closes the original cross-part as-char visibility defect that motivated this slice.


## Post-closeout correction discovered during Slice 6

The first Slice 4 closeout correctly resolved the `as-char` body/header visibility defect, but final public-sample regression exposed a second structural case:

- paragraph-anchored `ImageElement` frames could still become Writer-invisible when the placeholder paragraph carrier was removed;
- placeholders inside `text:span` could cause generated frames to remain nested inside the span instead of becoming direct `text:p` / `text:h` children.

The final insertion model therefore adds `PRESERVE_TEXT_CONTAINER` and promotes generated frame nodes out of inline span wrappers while preserving surrounding text/formatting fragments.

This correction does not change the FRAME-LAYOUT semantic ownership model. It completes the insertion side of the same native carrier rule discovered in Slice 4.

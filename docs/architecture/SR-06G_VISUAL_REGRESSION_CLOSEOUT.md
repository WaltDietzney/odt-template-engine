# SR-06G — Visual Regression Closeout

## Status

**FINAL GO**

This document records the rendering-sensitive closeout for SR-06 — Semantic Graphic Style Requirements.

SR-06 changed graphic-style ownership, semantic materialization, fill-image dependencies, and the legacy graphic compatibility boundary. Automated XML and PHPUnit coverage is necessary but cannot establish that LibreOffice still renders affected documents acceptably. SR-06 therefore requires a manual LibreOffice visual regression gate before final closeout.

## Validation pipeline

The regression workflow used the project samples as executable document fixtures:

```text
samples/sample_*.php
        ↓
samples/output/*.odt
        ↓ LibreOffice headless
tmp/visual-regression/pdf/*.pdf
        ↓ pdftoppm, 150 DPI
tmp/visual-regression/images/*.png
        ↓
comparison with the preserved pre-SR-06 baseline
```

The restored helper under `tools/visual-regression/render.sh` performs only the ODT → PDF → PNG conversion. It does not generate sample ODTs and it does not modify baseline artifacts.

Generated files under `samples/output/` and `tmp/visual-regression/` remain local regression artifacts and are not release/source artifacts.

## Selected regression samples

The visual gate deliberately concentrates on drawing and graphic behavior affected by SR-06 rather than treating every sample as equally relevant.

| Sample | Primary coverage | Result |
| --- | --- | --- |
| `sample_05_replaceImage.php` | image replacement / image frame | PASS — pixel-identical to baseline |
| `sample_05b_replaceImage.php` | alternate image replacement / frame case | PASS — pixel-identical to baseline |
| `sample_06_imageSettings.php` | image sizing / placement settings | PASS — pixel-identical to baseline |
| `sample_17_textfield.php` | DrawTextBox / frame appearance / inline text styles / legacy positioning behavior | PASS — reviewed difference, see below |
| `sample_21_cvProfile.php` | realistic multi-feature document with images and complex layout | PASS — both rendered pages pixel-identical to baseline |

Additional structured-section samples may be used as current-state smoke coverage where no preserved pre-SR-06 image baseline exists; they are not described as pixel-regression comparisons without such a reference.

## Sample 17 — reviewed visual difference

`sample_17_textfield.php` is not pixel-identical to the preserved baseline. The difference is localized to the drawing/text-box area and was reviewed against both the generated LibreOffice document and the sample/element code.

The current rendering correctly preserves the text styling requested by the sample inside the text boxes, including bold, italic, underline, and combined text styles. The rendered frame appearance is present as expected.

The sample also exposes a separate, pre-existing layout limitation: comments and convenience values imply right/center positioning while the current frame-positioning semantics do not reliably implement those meanings. In particular, percentage-like horizontal/vertical values are not a defined engine-level abstraction that can be assumed to mean LibreOffice `right` or `center` positioning.

This is **not an SR-06 regression** and must not be opportunistically corrected as part of semantic graphic-style migration.

The limitation is already tracked in the project backlog:

- `FRAME-LAYOUT-01 — Unified frame positioning` defines the future shared positioning model for drawing content;
- `FRAME-LAYOUT-02 — DrawTextBox positioning` requires DrawTextBox positioning to be resolved through or consistently with that shared model;
- `IMAGE-LAYOUT-01 — Image anchor, wrap, and position` requires image-specific behavior to build on the general frame semantics rather than duplicate it.

This matters beyond DrawTextBox. Anchor, relation/reference area, horizontal/vertical position, wrap, and geometry are drawing/frame concerns shared by multiple element types. SR-06 deliberately established the prerequisite separation between those concerns and semantic `graphic` style properties without redesigning the layout API.

Therefore the Sample 17 result is classified as:

> **PASS — known pre-existing frame-positioning limitation remains explicitly deferred to FRAME-LAYOUT-01/02 and IMAGE-LAYOUT-01; the reviewed text-style rendering is consistent with the sample's requested semantics.**

The baseline must not be silently regenerated merely to hide this reviewed difference.

## Architectural conclusion

The visual results support the central SR-06 separation:

```text
drawing element
├── structure
├── geometry / size
├── anchor
├── positioning / relation
├── wrapping
├── dependencies / resources
└── semantic graphic style
```

SR-06 migrated and bounded the semantic graphic-style/dependency ownership without claiming to solve the other drawing-layout dimensions.

The pixel-identical image and realistic CV cases provide strong evidence that the compatibility closeout did not introduce broad rendering drift. The one reviewed non-identical case corresponds to a known layout domain that was explicitly excluded from SR-06 and already has dedicated future work.

## Closeout decision

SR-06G receives **FINAL GO**.

The manual LibreOffice gate does not reveal a blocking SR-06 rendering regression.

Known frame/image positioning semantics remain future work under `FRAME-LAYOUT-01`, `FRAME-LAYOUT-02`, and `IMAGE-LAYOUT-01`. They must be researched from real LibreOffice-authored ODF and addressed as layout semantics, not folded back into graphic-style ownership.

With this visual gate complete, SR-06 can proceed to final milestone closeout.
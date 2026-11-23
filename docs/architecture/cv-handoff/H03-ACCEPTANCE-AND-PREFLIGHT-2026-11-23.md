# H03 native geometry — acceptance and preflight (23 November 2026)

## Scope and baseline

- Branch: `feature/h03-native-geometry`, based on `forgejo/develop` (`22fe57b` at H03 start).
- Contract: `H03-NATIVE-GEOMETRY-CHANGE-CONTRACT.md`; investigation: `H03-WRITER-ROUNDTRIP-FINDING.md`.
- First slice: native editable rectangle (solid fill), ellipse (bitmap fill), and arrow contour (solid fill), inserted through the structured element API. No CV-specific contract or legacy shape rewrite.
- The final style correction is commit `5cb1d7b`: H03's automatic graphic style must **not** inherit the template's `Frame` style, which visibly flattened shapes. The automatic style remains in `content.xml`; the bitmap fill-image declaration remains in `styles.xml`.

## Automated evidence

- Post-correction full `composer test`: **exit 0**, 1,065 tests, 8,533 assertions, no failed tests; PHPUnit 11.5.58 on PHP 8.5.4, runtime 38.763 seconds.
- PHP syntax lint of `src/` and `tests/`: **exit 0**.
- `composer validate --no-check-publish`: **exit 0**, `composer.json` valid.
- `git diff --check`: **exit 0**.
- Focused H03 tests after correction: **6 tests, 37 assertions**, passed. A headless Writer ODT-to-ODT roundtrip of the corrected minimal ellipse preserved one native `draw:custom-shape` before and after. The earlier paragraph-anchored three-shape roundtrip also preserved each native shape.
- Existing visual renderer `tools/visual-regression/render.sh` generated PDFs/PNGs for the shapes; the corrected minimal bitmap ellipse was inspected and appeared circular with the bitmap fill. Renderer output is untracked under the isolated H03 worktree's `tmp/` and is **not** a committed sample baseline.

## Manual LibreOffice human gate

- **User acceptance confirmed verbally on 23 November 2026:** the user inspected the H03 shapes in LibreOffice and reported that the forms work correctly and can be accepted. This is the user's manual visual/editability acceptance, not an automated assertion.
- Review artifacts were supplied under `/tmp/h03-geometry-check/in/`: `h03-rectangle.odt`, `blank-single-ellipse.odt` (the corrected minimal ellipse), and `h03-arrow-contour.odt`. These are temporary files, not stable repository fixtures. Earlier `h03-ellipse.odt` predates the corrected minimal ellipse and must not be mistaken for the accepted bitmap case.
- **Human gate: accepted.** No further visual change is requested by the user.

## Known non-blocking test diagnostics

A diagnostic full-suite rerun using `--display-warnings --display-deprecations --display-phpunit-deprecations` also passed (1,065 tests, 8,533 assertions), with **1 warning, 4 PHP deprecation categories, and 8 PHPUnit test-runner deprecations**:

1. `tests/Integration/S03StructuredProfessionalReportTest.php:154`: `mkdir(): File exists`; directory copy helper attempts to create an existing destination.
2. `src/Elements/Paragraph.php:340`: implicitly nullable `$style` parameter in `addKeyValueLine()`; public signature needs compatibility-aware review before changing.
3. `tests/Integration/SectionCloneTest.php:369` and `SectionInstantiationTest.php:187`: `ReflectionProperty::setAccessible()` deprecated on PHP 8.5.
4. `tests/Integration/StyleContextGraphicImageCharacterizationTest.php:228`: `ReflectionMethod::setAccessible()` deprecated on PHP 8.5.
5. PHPUnit metadata in doc-comments, reported eight times across `BookmarkTargetReplacementTest`, `D5GLegacyStructuredLifecycleCharacterizationTest` and `FrameLayout01CSlice0CompatibilityCharacterizationTest`; PHPUnit 12 requires attributes.

These findings do not originate in the new H03 shape classes. **Disposition:** separate maintenance slice; no warning cleanup or unrelated behavior change in H03.

## Handoff and remaining release actions

- H03 first-slice implementation, automated regression, Writer roundtrip characterization, visual rendering, and user human gate are complete for the agreed three-shape scope.
- Before any PR/merge: review final diff and branch base/head, checks, mergeability, and scope. A PR targeting `develop` can be prepared after review; **merge requires the user's separate explicit authorization**. Do not merge into `master`.
- Do not add temporary `/tmp` documents, generated `tmp/` renders, `samples/output/*.odt`, or LibreOffice lockfiles to the commit. Permanent regression artifacts, if needed, should be curated separately rather than copying uncontrolled temporary outputs.

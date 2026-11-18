# H02 C2 implementation note

Status: implemented in the current `feature/h02-page-frame-composition` worktree; automated acceptance is complete for the covered contract cases. Human visual acceptance and normative closeout completed on 2026-11-18.

## Implemented

- document-local two-stop gradient and PNG/JPEG bitmap definitions with strict names, formats, references, idempotence/conflict checks and package rollback;
- native page and header/footer fill projection, including transitions that remove stale fill attributes while preserving unrelated authored properties;
- synchronized `style:drawing-page-properties` for every master using a filled layout;
- deterministic private drawing-page styles when a shared carrier is also used by an unrelated layout, plus synchronization for newly defined masters;
- bounded fill information in page-layout inspection;
- `DrawTextBox::setBackground(array $options)` while retaining the existing string color API and legacy circular-image path;
- focused rollback, reference validation, resource identity, shared-layout and textbox regression coverage.

## Evidence and limitations

The implementation follows the native structures in the read-only Writer reference fixtures under `tests/fixtures/libreoffice-reference/odt`. The generated visual regression artifacts are kept only in `tmp/h02-c2-agent/`.

Headless LibreOffice is installed, but this managed runtime did not emit PDF or round-trip outputs; it reported read-only dconf-profile warnings and produced no conversion artifacts. Visual inspection in Writer is therefore still pending. No claim is made here that every gradient type renders identically across Writer versions. H02 C3 content mutation and D-scope CV construction are intentionally outside this slice.

## Correction and active-region roundtrip (2026-11-18)

Shared semantic fill validation now covers textbox options and recursively validates named resources before style registration in body/placeholder and master-region preparation. Explicit textbox background transitions clear previous fill options. Regression tests cover invalid options and missing references.

Full suite on Patil: 1055 tests, 8396 assertions, no errors or failures; 1 warning, 4 deprecations, 8 PHPUnit deprecations. The earlier sandbox HTTP test failures did not reproduce.

Active-region visual candidates: `tmp/h02-c2-agent/H02-active-bitmap.odt` and `H02-active-gradient.odt`. Writer roundtrip copies in `roundtrip/`. Both preserve active header/footer fills, page fills and four textbox fill types (gradient, bitmap, solid, none). Bitmap SHA-256 unchanged. Writer adds fallback/default attributes and renames internal styles/assets. Earlier candidates lacked active master regions and were insufficient to test region persistence. Four rendered PNG pages in `tmp/visual-regression/images/H02-active-*.png`; manual visual acceptance subsequently completed. No production code change was needed for the additional roundtrip.

## Owner visual acceptance (2026-11-18)

Owner confirmed manual visual acceptance of the active-region C2 candidates. Normative ODF findings are recorded below; no commit, push or merge authorized by this acceptance alone.

## Normative closeout (2026-11-18)

Sources: OASIS ODF 1.2 OS Part 1 (2011-09-29), sections 16.40.1/6, 17.2/3/5/21/25, 18.3.1/31/32, 19.112/114/129/130/218.2/511, 20.111–117/175/333; Part 3 section 4.3 (manifest). Cross-checked ODF 1.3 OS Part 3 (2021-04-27), sections 17.2, 17.5 and 19.218.2: https://docs.oasis-open.org/office/OpenDocument/v1.3/os/part3-schema/OpenDocument-v1.3-os-part3-schema.html.

- Named gradient and bitmap declarations in office:styles, degree units, percentages, graphic and drawing-page fills, and background-image child with repeat/stretch are supported by the normative model. Radial gradients ignore angle.
- Public elliptical maps to native ellipsoid; rectangular remains rectangular. New resource names must be XML NCNames. Focused tests cover all six gradient types and invalid names.
- Page-layout and header-footer properties permit fo:background-color and style:background-image, but not draw:fill, its named resource attributes or direct style:repeat in strict ODF 1.2 or 1.3. The approved Writer-native dual projection is retained as a compatibility representation, not described as strict schema conformance (nor automatically as conforming ODF extended markup). Active visual candidates use office:version 1.3. No version conversion or strict export mode is introduced. Strict portability of gradient region fills is not promised. Owner approved retaining this compatibility scope on 2026-11-18.
- Writer roundtrip and owner visual acceptance establish Writer behavior, not general ODF schema validity. No full-document schema certification was performed.

Final automated preflight: 1056 tests, 8405 assertions, no errors or failures; 1 warning, 4 deprecations, 8 PHPUnit deprecations. Owner approved Writer compatibility and C2 commit/push to the existing Forgejo feature branch. No merge.

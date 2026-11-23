# H03 Slice 2 — Shape Catalog technical completion report

Date: 2026-11-23
Branch: `feature/h03-shape-catalog`
Scope: Shape Catalog package 1 only. SkillBar, merge, push, and branch changes outside this worktree were not performed.

## Delivered

- Corrected native `draw:custom-shape` emission to include the Writer-compatible empty `text:p` child before `draw:enhanced-geometry`.
- Preserved and emitted enhanced-geometry attributes, equations, modifiers, and handles for adjustable E009-backed presets.
- Added focused XML, paragraph-anchor, style, lifecycle, and geometry regression coverage.
- Added reproducible generator: `tools/h03-generate-shape-catalog-acceptance.php`.
- Generated clean acceptance ODTs under `tmp/h03-shape-catalog-acceptance/`: overview, 20 individual preset files, layout test, checklist, and ZIP. A package validation pass found 22 valid ODT ZIPs, 43 native custom shapes, and no unresolved placeholders.

Regenerate with:

```bash
php tools/h03-generate-shape-catalog-acceptance.php
tools/visual-regression/render.sh tmp/h03-shape-catalog-acceptance/*.odt
php tools/h03-generate-shape-catalog-acceptance.php
```

The final generator run packages any available render candidates into the ZIP.

## Evidence and compatibility

- Writer-authored E009 evidence: `isosceles-triangle`, `ring`, and the three flowchart presets; their formulas, modifiers, handles, and geometry were retained.
- LibreOffice Impress reference evidence: exact hexagon match and rounded-rectangle reference. This is not Writer-authored evidence.
- Characterized/invented paths: triangle alias, pentagon, chevron, star5, cross, speech bubble, directional arrows, and left-arrow implementation where no equivalent Writer-authored reference was established. Their XML/render feasibility must not be described as GUI editability proof.
- Headless roundtrip/render evidence does not establish Writer GUI selection, resize, reposition, color/outline editing, or handle behavior. `MANUAL-ACCEPTANCE.md` is the remaining human gate.
- No grouping API or unrelated refactoring was introduced. Existing legacy sample outputs and lock files were not modified.

## Verification

- H03 focused PHPUnit: **13 tests, 96 assertions — pass**.
- `PublicSampleSmokeTest`: **2 tests, 474 assertions — pass**.
- Full `composer test`: **1,072 tests reached; 8,456 assertions; 2 unrelated failures** in `HtmlImportP2ATest` remote resolver tests, with 2 warnings, 4 PHPUnit deprecations, 8 PHPUnit deprecation notices, and 1 skipped test. These were not changed.
- PHP lint for all `src/` and `tests/`: **pass**.
- `composer validate --no-check-publish`: **pass**.
- `git diff --check`: **pass**.

## Render gate and recommendation

The mandated `tools/visual-regression/render.sh` was invoked for all acceptance ODTs. In this managed environment, LibreOffice 26.2.6.3 exited with status 1 without producing PDFs, including when given isolated writable user profiles and when tested against an existing repository sample. Therefore no PDF/PNG rendering claim is made and the ZIP currently contains the genuine ODT package and generator but no fabricated renderings.

Before approval, run the render command in a functioning LibreOffice environment, add the resulting PDF/PNG candidates to the acceptance package, and complete the Writer GUI checklist. The explicit recommendation is an **intermediate review now**, followed by manual Writer acceptance before any SkillBar work, merge, or push.

## Post-report acceptance and package correction (2026-11-23)

- The acceptance generator had written `meta.xml` and `settings.xml` without corresponding `META-INF/manifest.xml` file entries. LibreOffice rejected the generated ODT (`source file could not be loaded`), while a repository ODT and a plain text control converted successfully.
- Added both missing manifest entries in `tools/h03-generate-shape-catalog-acceptance.php` and regenerated the acceptance package. `Shape-Ring.odt`, `Shape-Catalog-Overview.odt`, and `Shape-Layout-Test.odt` then converted successfully. A subsequent full headless conversion produced 22 PDFs for 22 ODTs in `tmp/h03-shape-catalog-acceptance/rendered-pdf/`. This supersedes the earlier render-blocker statement for the regenerated package.
- **Human gate: accepted.** The user inspected all 22 ODT acceptance documents in LibreOffice Writer on 2026-11-23 and reported no defects. This is a user-reported manual visual acceptance, not an independently instrumented GUI assertion.
- The two `HtmlImportP2ATest` failures seen in the prior full-suite run were not reproduced when the test file was rerun independently: 7 tests, 23 assertions passed (one deprecation). Their full-suite cause remains unproven; do not characterize them as resolved without another complete suite run.
- Shape Catalog package 1 is accepted; SkillBar remains separate package 2. No sample outputs or baselines are in scope.

- **Final full-suite rerun (2026-11-23):** `composer test` passed: 1,072 tests, 8,592 assertions; 1 warning, 4 deprecations and 8 PHPUnit deprecations. The two prior HTML importer failures did not recur in the complete suite.

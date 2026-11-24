# H03 Slice 2 — Reuse audit of CV Generator Pro E009 (2026-11-23)

## Existing authoritative evidence discovered

The CV Generator Pro research already contains **Writer-authored** ODT reference documents at `/srv/projects/cv-generator-pro/research/visual-template-references/prototypes/E009/preview/e009-{1,2,3}.odt` and their roundtrip copies under `originals-rt/`. The accompanying `research/E009-POC-2026-11-17.md` records that the user authored e009-1 and e009-2 in Writer, inspected and accepted e009-3 PDF, and that all three documents had identical before/after PNG hashes. The acceptance applies to the PoC only, not the H03 engine implementation.

The structured `research/engine-handoff/E009-ODF-EVIDENCE.json` has already been copied to the Engine at `docs/architecture/cv-handoff/E009-ODF-EVIDENCE.json`; it contains 13 enhanced-geometry entries including variants, with attributes and nested equations/handles. The `research/engine-handoff/README.md` explicitly says to reuse this evidence across projects rather than repeat completed experiments.

## Relevant geometry types

`rectangle`, `ellipse`, `diamond`, `isosceles-triangle`, `ring`, `non-primitive`, `flowchart-stored-data`, `flowchart-off-page-connector`, `flowchart-punched-tape`. The Writer-authored triangle uses formula-based enhanced geometry and **is not equivalent** to the simplified `triangle` candidate created in H03. Additional H03-only candidate types (`pentagon`, `hexagon`, `chevron`, `star5`) remain candidate paths without equivalent Writer-authored E009 evidence. The locally installed LibreOffice Impress `Candy.otp` independently matches the H03 hexagon path exactly but does not establish Writer editability.

## H03 implementation implications

1. Use existing Writer-authored E009 sources as the starting geometry and editability evidence; do not recreate those cases.
2. For adjustable shapes, preserve relevant modifiers, equations, handles and viewBox instead of copying only enhanced-path. Current `ShapeGeometry` models just type/path/viewBox, so expanding it requires an explicit compatibility-aware design.
3. Produce **new** Writer references only for shapes not covered by E009 (rounded rectangle, chevron, star, arrow variants etc.) and any newly required interaction scenarios.
4. Retain H03-specific XML, roundtrip and `tools/visual-regression/render.sh` gates and the later human review.

No original CV research artifact, baseline, sample output or PHP source was modified by this audit.

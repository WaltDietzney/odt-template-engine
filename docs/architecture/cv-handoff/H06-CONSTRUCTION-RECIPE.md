# H06 – Competence indicator construction recipe

Aktueller Status 24.11.2026: native Machbarkeit/Gestaltung im dokumentierten Umfang abgenommen. [Konsolidierung](P2-CONSOLIDATION-2026-11-24.md) trennt verbleibende Engine-Integration und optionale Grenzen; ältere offene Abnahmevermerke sind historisch.

Stars, segments, full ring and native 70% ring visually accepted.
Evidence: ../../../research/p2-capability-audit/v01/generate.php, extend.py,
p2-v01-engine.odt and p2-v01-native.odt.
Reuse ShapeElement, ShapeGeometry presets, ShapeAppearance, DrawingLayout;
inspect SkillBarGroup for grouping and resource ownership.
- Five star5 shapes, 0.7cm square, x step 0.8cm; first three blue #337799,
  remainder gray #DDDDDD. Five round-rectangle segments 0.7x0.3cm,
  same x step, first four blue. Full ring preset 1.5cm square.
- Partial ring: two coincident 2cm square custom shapes, viewBox 21600 square,
  center (10800,10800), outer radius 10800, inner 7000. Track 360 degrees gray,
  progress 252 degrees blue (70%), z-index 20/21. Sample outer arc clockwise
  from top using x=c+r*sin(a), y=c-r*cos(a), inner arc in reverse, close path
  with Z N. Prototype uses 3-degree polygon steps; arbitrary endpoints must
  be included explicitly in future implementation.
- Solid fill, no stroke, paragraph anchor, foreground run-through.
Open: zero/full progress edge cases, arbitrary values, grouping, label sizing,
cross-part insertion and repeated save. Rating semantics belong to consumer;
soft skills remain unscored. No new indicator class approved.


## P2-I05 integration — 2026-11-25

The owner-approved ShapeGeometry::annularSector factory now covers zero/full and arbitrary endpoints using existing native shape/group components. [Implementation report and evidence](P2-I05-IMPLEMENTATION-REPORT.md). Geometry, style-part and lifecycle gaps listed above are technically checked; new variant visual acceptance and interactive editing remain separate. Labels/rating semantics remain consumer-owned.

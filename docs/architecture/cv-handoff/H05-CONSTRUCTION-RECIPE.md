# H05 – Photo and overlay construction recipe

Aktueller Status 24.11.2026: native Machbarkeit/Gestaltung im dokumentierten Umfang abgenommen. [Konsolidierung](P2-CONSOLIDATION-2026-11-24.md) trennt verbleibende Engine-Integration und optionale Grenzen; ältere offene Abnahmevermerke sind historisch.

Partial evidence; native image geometry and reversed ordering visually accepted.
Reuse ImageElement, CircularImageElement, ShapeAppearance::bitmap, DrawingLayout,
FillImageRequirement and resource collection. Do not conflate frame replacement
with bitmap fill replacement.
Evidence: ../../../research/p2-capability-audit/v01/extend.py, finish.py,
p2-v01-native.odt and p2-v01-final.odt.
- Native draw:frame/as-char with draw:image, embedded resource and manifest entry.
  Reference 240x120 PNG: 4x2cm proportional, 2x2cm stretched, 2x2cm crop candidate
  with fo:clip="rect(0cm, 1cm, 0cm, 1cm)". Crop semantics not independently proven.
- Overlapping paragraph-anchored shapes: rectangle 4x1.5cm at (0,0.6),
  ellipse 2x1.5cm at (2,0.8); explicit z-index 10/9 reverses baseline stacking.
  DrawingLayout currently lacks z-index support; establish shared ordering contract.
- Bitmap candidate: independent draw:fill-image declarations in styles.xml,
  distinct PNG resources and manifest entries, graphic styles draw:fill=bitmap,
  draw:fill-image-name references, style:repeat=stretch; 2x2cm ellipse shapes.
  Final visual gate open; actual replacement lifecycle not tested.
Open: aspect-aware crop, shared resource isolation during replacement, page flow,
wrap, both XML parts, repeated save and no concealed data. No new photo class approved.

## Owner acceptance – bitmap fills and P2-V01 closeout
2026-11-24: owner confirmed prior visual acceptance of independent bitmap
fills. Together with rectangular inline tag padding/spacing acceptance,
P2-V01 visual feasibility is closed. This supersedes earlier pending visual
gates. Actual bitmap replacement lifecycle, crop semantics, optional rounded
auto-grow tags and remaining edge cases belong to H05/H06/H08 contracts.
No production implementation or new API approved.

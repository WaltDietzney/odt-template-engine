# P2-I03/I03R native integration evidence

Source HEAD 473ea17705288ad431c3960835fafa05ec2d5340 plus pending I01b/I02 and
this bounded implementation. [Report](../../../docs/architecture/cv-handoff/P2-I03-I03R-IMPLEMENTATION-REPORT.md).
All commands run from /srv/projects/odt-template-engine-p2. No originalworktree or
sample outputs are written. Existing reference evidence is read only.

## Reproduce

```sh
php research/p2-capability-audit/drawing-integration/rounded/generate.php
python3 research/p2-capability-audit/drawing-integration/rounded/render.py showcase showcase-repeat showcase-reloaded multipage
php research/p2-capability-audit/drawing-integration/contacts/composition/generate.php
php research/p2-capability-audit/drawing-integration/contacts/composition/lifecycle.php
python3 research/p2-capability-audit/drawing-integration/contacts/composition/render.py
php research/p2-capability-audit/drawing-integration/stories/generate.php
python3 research/p2-capability-audit/drawing-integration/stories/render.py
php research/p2-capability-audit/drawing-integration/regressions/generate.php
python3 research/p2-capability-audit/drawing-integration/regressions/render.py
python3 research/p2-capability-audit/drawing-integration/verify.py
python3 research/p2-capability-audit/drawing-integration/extract-xml.py
vendor/bin/phpunit tests/Integration/DrawingCompositionTest.php
composer test
```

PHP 8.5.4, Writer 26.2.6.3 and Poppler are the actual tested runtime. Writer conversion
uses disposable isolated profiles. Render scripts keep native sources, Writer ODTs,
PDFs, PNGs and command logs. PHP bootstrap resolves worktree classes; no adapter
classes, output XML surgery or third-party runtime icon service is used.
Contact consumer factories stay research/consumer code, not public engine types.
Trusted existing icon assets are reused; this does not close I04 SVG safety/runtime gates.

## Findings and rejected paths

- Existing round-rectangle preset + explicit Auto-Grow + automatic drawing text styles
  reproduces accepted H08-R. Direct frame corner radius and hand-built growing rect
  remain failed historical paths in rounded-tags/; no reinvestigation or promotion.
- Group/image property maps can coincide while parent semantics differ. Initially
  sharing their generated identity caused a semantic style conflict. Group identities
  are now separate; migrated contact generation demonstrates the correction.
- Naively retaining generated Writer style names in drawing contexts could shadow
  authored/ordinary styles. Isolated drawing identities and new-subtree rebinding
  replace that approach. Conflicting authored reserved identities fail atomically.
- Group bounds are child-derived. Historical H07 group width/height attributes are
  intentionally omitted; the accepted pixels remain identical. No frame-coordinate
  projector is blindly applied to draw:g.
- An initial graphical-heading PNG comparison used different resolutions (1400px
  versus historical 150dpi). Matching resolution removes that comparison error.
  Canonical XML comparison ignores harmless XML quote/empty-element serialization.
- Native fields in drawing text remain rejected. The historical data-loss control
  is retained at v04/composition/carrier.odt; the migrated suite does not recreate it.

The 0/1/n contact and tag cases reuse the already accepted design scope. Actual
paint order is raster-tested; retaining numeric indexes alone is insufficient.
Interactive Writer selection/move/edit is still a separate human check.

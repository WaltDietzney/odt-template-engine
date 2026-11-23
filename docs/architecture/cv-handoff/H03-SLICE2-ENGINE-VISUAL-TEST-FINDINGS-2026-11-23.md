# H03 Slice 2 — Engine visual test finding (2026-11-23)

The first Engine-generated ODT visual test used `tests/Fixtures/LegacySamples/templates/sample_textfeld.odt`. It produced blue rectangles in the PDFs for `isosceles-triangle`, `ring` and `hexagon`, although `content.xml` contains one correctly typed `draw:custom-shape` per generated file. A control render of the **unmodified template** shows the same large blue rectangle. Therefore the blue rectangle is **pre-existing template content**, and the initial visual comparison is confounded; it cannot establish a shape-rendering defect or successful shape appearance. An experiment adding `svg:x/y` to the generated hexagon did not change the appearance.

Next: render the engine-generated shapes in a clean, dedicated shape-only template (no pre-existing opaque drawing); inspect the actual rendered shape, then run ODT roundtrip and the mandatory `tools/visual-regression/render.sh` again. Preserve existing sample outputs and baselines.

PHP changes currently add E009-backed `isosceles-triangle` and `ring` geometry including modifiers, equations, handles and extra geometry attributes. Focused PHPUnit results: H03NativeShapeTest 6 tests/27 assertions; H03ShapeOdtIntegrationTest 2 tests/25 assertions. No full test suite or manual Writer editing gate yet.

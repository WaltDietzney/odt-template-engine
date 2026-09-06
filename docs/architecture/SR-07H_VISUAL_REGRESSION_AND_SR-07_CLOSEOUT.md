# SR-07H — Visual Regression and SR-07 Closeout

Status: **COMPLETE / FINAL GO**

Baseline: `develop` after SR-07G / PR #55 (`65c93807c080950ec1530e9b98661d7a3c3289a8`)

## Purpose and environment

SR-07H is the final visual and interoperability gate for the SR-07 semantic
table-family migration. It verifies the approved ownership changes against the
public table samples and separately proves the intentionally activated
`min-row-height` row-style behavior.

The manual LibreOffice review used LibreOffice `24.2.7.2`, with the existing
repository visual-regression baseline. The generated ODTs, extracted XML, and
rendering experiments remain local validation artifacts and are not source
fixtures.

## Final visual matrix

| Target | Expected behavior | Observed behavior | Classification | Intentional change |
| --- | --- | --- | --- | --- |
| Sample 11 | Explicit `2cm` / `10cm` column widths and the registered table style | Current ODT contains valid semantic `table-column` definitions for `2cm` and `10cm`; the first column is visibly narrow and the sample text wraps accordingly | **PASS / EXPECTED CORRECTION** | Yes. The archived baseline contained malformed/duplicate automatic-style behavior and did not faithfully represent the explicit widths. |
| Sample 13 | Existing styled-cell and span layout remains stable | Visually unchanged from baseline | **PASS / IDENTICAL** | No |
| Sample 19 | HTML-imported table compatibility remains stable | Visually unchanged from baseline | **PASS / IDENTICAL** | No |
| Sample 20 | `setColumnWidthRatios([2, 1, 1])` renders approximately `50% / 25% / 25%` | Three real logical columns render approximately `50% / 25% / 25%` in LibreOffice | **PASS / EXPECTED CORRECTION** | Yes. The old baseline preserved a pre-SR-07 virtual-column semantic defect. |
| `min-row-height` proof | A row with `2cm` minimum height is visibly taller while an unstyled row remains normal | Engine XML matches the LibreOffice-authored oracle and manual review confirms the minimum-height difference | **PASS** | Yes, this behavior was intentionally activated in SR-07E2. |

The earlier `FAIL/UNCLEAR` interpretation of the row proof is withdrawn. It
was caused by table borders being difficult to distinguish in the converted
view, not by an absent or ineffective row style.

## Sample 20 relative-column correction

The historical implementation represented `[2, 1, 1]` through a virtual
12-column grid and artificial cell spans `6 / 3 / 3`. ODF
`table:number-columns-repeated` repeats equivalent column definitions; it is
structural repetition and does not express relative physical widths. That
representation therefore preserved a legacy semantic bug rather than the
public API's intended 2:1:1 ratio.

SR-07H now gives each ratio entry one semantic `table-column` requirement and
one real structural `table:table-column`. Ratio-derived artificial spans and
the virtual repeated-column grid are gone. The semantic definitions are
automatic, content-local styles using `style:rel-column-width`.

LibreOffice Writer interoperability requires normalized relative values in its
finite 16-bit variable table-width space. For positive integer weights:

```text
sum  = sum(weights)
unit = floor(65535 / sum)
width[i] = unit * weight[i]       for i < final column
width[last] = 65535 - sum(previous widths)
```

Thus `[2, 1, 1]` is materialized as:

```text
32766* / 16383* / 16386*
```

The final-column remainder makes the total exactly `65535`. This is a
LibreOffice Writer interoperability/materialization rule backed by Writer
behavior; it is not a claim that ODF itself mandates a total of `65535`.

The manual experiments established that neither an explicit table width nor
absolute `style:column-width` hints are required for this relative-column
representation to render correctly. SR-07H did not add a table-width API or
calculate absolute widths.

## Row minimum-height proof

The focused proof uses:

```php
addRow(['...'], ['min-row-height' => '2cm']);
```

The engine emits a single automatic/content-local `table-row` style with:

```xml
style:family="table-row"
style:table-row-properties
style:min-row-height="2cm"
```

The unstyled row has no generated row style reference. A dedicated
LibreOffice-authored oracle created with a 2.00 cm minimum row height and
dynamic adjustment enabled emits the same semantic structure. The engine proof
renders with the visibly increased minimum row height and remains structurally
sound.

## Architecture distinction preserved

SR-07H records the following separate concepts for future work:

| Concern | Meaning |
| --- | --- |
| Table width | The overall table's automatic, absolute, or percentage/relative width |
| Absolute column width | `style:column-width` on a `table-column` style |
| Relative column width | `style:rel-column-width` on a `table-column` style |
| Structural repetition/spans | `table:number-columns-repeated`, genuine colspan, and rowspan |

These concerns must not be conflated. Relative column normalization does not
decide future table-width semantics.

## Scope boundaries

SR-07H and the SR-07 closeout do not introduce a table-width API, redesign the
row model, broaden the ratio API, or begin unrelated `TABLE-LAYOUT` work. They
also do not redesign explicit widths, genuine colspan/rowspan, table-cell
ownership, static compatibility registries, or lifecycle architecture.

The next documented lifecycle architecture work remains D5F/D5G; this closeout
does not silently reorder or redesign it.

## Final status

SR-07 is **COMPLETE / FINAL GO** after the automated preflight and the manual
LibreOffice visual review described above. The visual baseline is not expected
to be byte/pixel-identical for Samples 11 and 20 because it preserved the
malformed explicit-width behavior and the legacy virtual-column ratio bug.

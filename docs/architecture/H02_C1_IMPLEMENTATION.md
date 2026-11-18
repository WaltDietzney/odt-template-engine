# H02 C1 implementation note

Status: implemented on `feature/h02-page-frame-composition`, 2026-11-18. No
merge, push, sample output, or lock-file changes are part of this slice.

## Inspection shape

`inspectPageLayout($name)` returns a detached array with this bounded shape:

```php
[
    'name' => string,
    'master-pages' => list<[
        'name' => string,
        'drawing-style-name' => ?string,
    ]>,
    'properties' => array<string, string>|[
        // plus `columns` when authored:
        'columns' => [
            'attributes' => array<string, string>,
            'children' => list<[
                'name' => string,
                'attributes' => array<string, string>,
            ]>,
        ],
    ],
    'regions' => [
        'header' => ?array<string, string>,
        'footer' => ?array<string, string>,
    ],
    'unsupported' => list<string>,
]
```

`inspectPageStyle($name)` returns `name`, `page-layout-name`,
`drawing-style-name`, nullable `next-style-name`, and `header`/`footer` entries
with `present`, `content`, and `properties` keys, plus `unsupported`. It does
not expose DOM nodes or promise arbitrary style round-tripping.

## Mapping evidence and limits

The implementation uses the native names observed in the checked-in Writer
fixtures:

- `tests/fixtures/libreoffice-reference/extracted/PAGE-01-02/styles.xml`
  shows page geometry and the empty header/footer style structure.
- `tests/fixtures/libreoffice-reference/odt/PAGE-STYLE-AUTHORING-02-reference.odt`
  shows `style:columns`, `svg:height`, `fo:min-height`, `fo:margin-*`,
  `fo:border*`, `fo:padding`, `style:border-line-width-bottom`, and
  `style:shadow` on the corresponding native property elements.
- The solid reference fixture shows the authored column-separator ordering and
  existing unrelated children that C1 preserves.

Those fixtures are verified local evidence, not normative ODF citations. No
versioned local ODF schema or authoritative specification text was available
in this checkout to substantiate section-number claims. Fill projection,
resource definitions, drawing-page synchronization, and content mutation are
therefore reported as unsupported/deferred to C2/C3; C1 does not guess a fill
mapping.

## C1 correction evidence — 2026-11-18

The correction pass reviewed the complete C1 diff, including the previously untracked
`src/Document/PageLayoutOptions.php`. The focused page integration tests pass with 10
tests and 56 assertions. The H01/page regression selection passes with 42 tests and 479
assertions, with 2 existing deprecations and 1 existing skip.

The regressions cover namespace-local attribute removal, shorthand replacement and side
patch expansion for border/padding/double-line widths, mixed double-border cleanup,
explicit shadow `none`, strict nested validation, effective double-border validation,
page-column balance rejection (including `false`), count-one reset, authored-child order
and preservation using a synthetic bitmap/footnote fixture, extended rollback including
new containers, legacy seven-key partial mutation, save/reopen, and repeated saves.
All `src/` and `tests/` PHP files lint successfully, and `git diff --check` is clean.

Remaining normative/evidence gaps are unchanged and bounded: C2 fill projection and
resource definitions, C3 header/footer content mutation, and actual LibreOffice
save/reopen plus PDF/PNG visual acceptance require the host environment. This note makes
no normative ODF section citation; the checked-in Writer fixtures remain implementation
evidence only. No C2 work, sample output, lock-file, commit, push, or worktree change was
performed by this correction pass.

## Manual Writer acceptance (2026-11-18)

Owner explicitly confirmed all four visual cases (portrait, landscape unequal columns, three columns, mutation) without remarks, and saving in Writer. Existing files in tmp/h02-c1-visual/ are Writer-normalized (master H02Visual references Mpm9); no separate -writer files found. Regenerated engine originals independently in tmp/h02-c1-fresh/ using the same generator; did not overwrite owner files. ZIP integrity passed for all four Writer files. Native layout comparison confirms page geometry, border/padding/shadow, fixed header/minimum footer and spacing, unequal 21845*/43690* columns with 0.4cm half-gaps, equal three columns with 0.6cm gap, red separator, and one-column reset. Writer normalizes lengths and adds defaults/footnote separator. Original render produced eight PNG pages; first portrait image inspected, remaining seven not independently inspected yet. Double-border visual case still outstanding. Host full suite 1044 tests/8312 assertions, one warning, four deprecations, eight PHPUnit deprecations; same counts as H01 baseline. Exact diagnostic identity not independently verified. C1 remains pending final visual evidence and normative mapping verification; no commit or push.

## Final visual evidence and height decision (2026-11-18)

The remaining seven original PNG pages were inspected: portrait continuation,
both landscape pages, both three-column pages and both mutation pages. No
unexpected clipping or overlap was observed in those cases. The double-border
case was generated through the public API and rendered using
`tools/visual-regression/render.sh`. Page, header and footer double borders
rendered on both pages. A fixed 1.5cm header clipped its text; a fixed 2cm
header rendered the text on the inspected first page. With a 1.5cm minimum
height instead, both pages rendered the complete header and footer text and
double borders. The owner selected minimum height for this use case.

Retain explicit fixed-height semantics; recommend min-height for content that
must grow with borders, padding or text. Do not silently enlarge fixed heights
or implement a PHP layout estimator. No production code change is needed for
this observed case. A native Writer-authored fixed-height control was not
performed, so the precise cause of clipping is not independently established.
Local evidence: tmp/h02-c1-double/ and tmp/visual-regression/images/h02-c1-double-*.
Normative ODF verification remains outstanding; this is visual closure only.

## Normative double-border correction (2026-11-18)

ODF 1.2 Part 1 (OASIS Standard, 29 September 2011), sections
20.241–20.245 and 18.3.26, require strictly positive lengths for the
inner line, gap and outer line. The owner approved correcting the
previous nonnegative contract on 2026-11-18. PageLayoutOptions now rejects
zero in any of the three positions; the same validator serves page,
header and footer shorthand and side-specific options. The regression
covers all three positions in each region and both option forms, including
atomic rollback. Focused integration suite: 11 tests, 92 assertions, pass.
Existing positive double-border visual evidence remains applicable.

## C1 final review — 2026-11-18

Normative mapping reviewed against the final OASIS ODF 1.2 Part 1 standard
(29 September 2011), not the committee draft. Sections 16.5–16.13 establish
layout/master/content ownership; 17.2 and 17.5 establish page and region
property ownership; 20.176.2–20.176.6 and 20.210–20.214 cover borders and
padding; 20.241–20.245 and 18.3.26 establish positive double-line widths;
20.205.2 and 20.397.2 distinguish minimum and specified height; 20.256
defines growth into the body gap, separately from minimum height; 20.349
covers shadow; 17.12–17.14 cover column structure and 19.232–19.233,
19.510.3 and 19.518.2 cover count, gap, relative width and separator width.
20.325 covers print orientation. This is a targeted normative mapping
review, not whole-package Relax NG conformance certification. API
restrictions and mutation semantics remain the engine contract.

Full suite after the approved correction: 1045 tests, 8348 assertions,
no failures/errors; one warning, four deprecations and eight PHPUnit
deprecations, matching baseline counts (not independently matched by
diagnostic identity). Focused suite and changed-file lint passed.
Visual acceptance and Writer roundtrip evidence are recorded above.
C1 implementation and targeted mapping review complete; C2 remains separate.
No commit or push performed.

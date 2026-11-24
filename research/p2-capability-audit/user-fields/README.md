# Native User Fields versus custom document properties

Report and proposed construction boundary:
[Architecture note](../../../docs/architecture/cv-handoff/P2-I01A-USER-FIELDS-AND-METADATA.md).

Run from this directory:

```sh
python3 generate.py
php lifecycle.php
python3 render.py control metadata field both metadata-only
python3 verify.py
```

Uses the repository table fixture and actual-worktree bootstrap; no production
source, accepted H07 evidence or sample outputs are modified. Requires PHP with
DOM/ZIP, Python, LibreOffice and Poppler. Tested PHP 8.5.4 / Writer 26.2.6.3.
Outputs are research-only and may be regenerated here. Existing accepted evidence
in other directories must never be overwritten. First invalid namespace attempt
is retained under failed-initial and is excluded from verification.

verification.json records source/Writer values and artifact SHA256; lifecycle.json
records metadata save/reopen and the metadata-only NOT_FOUND result. PDF/PNG and
native Writer-roundtrip ODTs are retained. This bounded probe does not establish
manual Writer editing, all field types/regions or CV composition parity.

## Implemented public construction (P2-I01a)

See [implementation report](../../../docs/architecture/cv-handoff/P2-I01A-IMPLEMENTATION-REPORT.md).

```sh
php construct.php
python3 render.py constructed constructed-repeat constructed-reloaded
python3 verify-construction.py
python3 integration/prepare-seed.py
cd integration/composition
php generate.php
php lifecycle.php
php carrier.php
python3 render.py
python3 render.py carrier
python3 verify.py
```

The integration copy replaces native field authoring with declareUserField and
Paragraph::addUserField. The accepted original research stays unchanged. The
existing authored name header is retained; new contact declarations are created
through the public method. DrawingParagraph places graphics only. Other drawing/
frame research adapters are deliberately retained for their separate P2 slices.
Carrier failure evidence is a negative control, not a supported field-in-group API.
Full-suite warnings, PHP 8.2 runtime and interactive Writer editing are separately
reported gates, not silently counted as fully completed release checks.

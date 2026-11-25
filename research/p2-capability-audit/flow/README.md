# P2-I02 — ordered inline content and native Writer frame flow

Source baseline: 473ea17705288ad431c3960835fafa05ec2d5340, plus preserved
uncommitted P2-I01b. start-state.json pins the starting Paragraph bytes.
No original accepted evidence or sample output is modified.

## Reproduce direct native flow controls

```sh
python3 prepare.py
php generate.php
python3 render.py
python3 verify.py
```

Five positive documents / six pages: ordered body/header/footer content, short and
long native field frames, 90 tags over two pages, and a fixed-width wrapping fallback.
Repeated saves, full PDF contents, no off-page/overlapping words and identical
Writer-roundtrip images are checked. Frame growth moves FOLLOWING by 57.75pt.

## Reproduce contacts without FlowTextBox

```sh
python3 prepare-contacts.py
cd contacts/composition
php generate.php
php lifecycle.php
php carrier.php
python3 render.py
python3 render.py carrier
python3 verify.py
```

The migration removes the FlowTextBox serializer override and uses native
DrawTextBox::setTextBoxSizing(min-height). Ten variants match the accepted
reference pixels exactly, including long, rebound, missing and empty profiles.
Other drawing-context adapters remain inputs to later P2 contracts.

Requirements: PHP DOM/ZIP, Python, LibreOffice, Poppler and installed fonts.
Verified PHP 8.5.4 / Writer 26.2.6.3. PHP 8.2 and interactive Writer editing remain
unperformed gates. Scripts operate only inside this evidence directory.

## Explicitly rejected capability

native-max-bounds-rejected preserves the ODT/PDF/Writer result where 4cm min/max
and false drawing Auto-Grow did not enforce fixed width. Writer discarded maximums.
The new API therefore accepts native minima and drawing flags, but rejects max
keys and contradictory minima/false-grow combinations. Fixed-width wrapping is
proved separately. This is not a general responsive layout/overflow detector.

initial-syntax-and-bounds retains earlier render intermediates. The generator
initially used the wrong DrawingLayout key (align instead of alignment) and tried
to redefine an existing master page; existing validation rejected both. Corrections
use the established API, not changes to legacy layout behavior. Writer may collapse
identical per-side padding to shorthand; verification checks equivalent values.

preflight.json, verification.json, test logs and contacts/composition/verification.json
record scope and checks. Production source changes are Paragraph and DrawTextBox.
No commit, push, merge, deployment or new owner visual acceptance is implied.

# P2-I01b — native heading construction

Baseline: 473ea17705288ad431c3960835fafa05ec2d5340.
Production API: Paragraph::setOutlineLevel(?int), supported levels 1–10;
null returns to text:p serialization. No new production class.

From this directory:

```sh
python3 prepare.py
php construct.php
python3 render.py decorated decorated-repeat decorated-reloaded levels levels-repeat
python3 verify.py
```

Requirements: repository source/autoload bootstrap, PHP with DOM/ZIP, Python,
LibreOffice, pdftoppm and pdftotext. Verified PHP 8.5.4 / Writer 26.2.6.3.
prepare.py reads the accepted v03/semantic evidence without modifying it and
creates input seeds. construct.php authors all headings through the public API.
The decorated variants replace input placeholder paragraphs with newly constructed
headings; no saved engine output is patched.

The AcceptedHeadingDecoration research adapter preserves exactly the five
accepted shapes and their authored styles from the prepared input. It is a
bounded evidence-input adapter, not a new generic drawing API or a replacement
for CC03/CC04. Text appears once in the body heading; decorations remain textless.
Their calibrated geometry is reused, not generalized.

Five lifecycle documents prove native headings, PDF bookmarks, no duplicate
labels, repeated save/reload and pixel-identical Writer roundtrips. Three decorated
pages also equal the owner-accepted reference pixel for pixel. The levels control
uses 1/2/3/10 and a native field in a level-1 heading. Unit tests cover all 1–10.

verification.json and preflight.json record results; logs retain test/build details.
Interactive Writer editing and PHP 8.2 execution remain separate unperformed gates.
No commit/push/merge/deployment is implied by these results. Original accepted
research and sample outputs stay unchanged.

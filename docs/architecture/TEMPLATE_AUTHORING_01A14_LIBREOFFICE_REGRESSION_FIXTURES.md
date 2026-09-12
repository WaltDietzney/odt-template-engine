# TEMPLATE-AUTHORING-01A1.4 — LibreOffice Regression Fixtures

Status: ACTIVE MANUAL CHARACTERIZATION / NO PRODUCTION CHANGE

## Purpose

Validate the XML findings from A1.1–A1.3 against real LibreOffice-authored ODT structure and actual Writer rendering.

This pass is intentionally manual and evidence-oriented. It does not introduce production fixes.

> Does the current classic template-control behavior preserve the native Writer structure, identity, and visual semantics that the authored template actually expresses?

## Fixture policy

The Writer-authored source fixture is a local research artifact:

```text
research/template-authoring-a14.odt
```

The generated result should live at:

```text
research/output/template-authoring-a14-output.odt
```

These files remain local regression artifacts unless an explicit later decision promotes a fixture into the repository.

A committed helper script is provided at:

```text
tools/research/template-authoring-a14.php
```

## Writer fixture layout

Create one ODT document with six clearly separated test areas and visible headings.

### Case 1 — Styled IF branch

Template:

```text
A14.1 — Styled IF

{{#if:show_profile}}

PROFILE HEADING
This paragraph has intentionally distinctive paragraph formatting.
This sentence contains bold, italic, colored, and underlined spans.

{{#else}}

FALLBACK HEADING
Fallback paragraph with visibly different paragraph formatting.

{{#endif}}
```

Authoring requirements:

- selected heading: distinctive named/custom paragraph style;
- selected body: another paragraph style;
- at least three distinct inline formats in the selected body;
- fallback formatting visibly different.

Expected data: `show_profile = true`.

Questions: Are selected paragraph and inline styles preserved exactly? Are marker paragraphs removed cleanly? Does removed marker formatting leave spacing artifacts?

### Case 2 — IF around a table

Template:

```text
A14.2 — IF around table

{{#if:show_table}}

[real Writer table]
Column A | Column B
{{table_value}} | Static cell

{{#else}}

Table hidden fallback.

{{#endif}}
```

Create a real Writer table with visible background/borders and a recognizable paragraph style in the placeholder cell.

Expected data: `show_table = false`, `table_value = SHOULD NOT APPEAR`.

Questions: Does an empty/degenerate table shell remain? Does Writer repair it on open/save? Is retained spacing visible?

### Case 3 — Styled FOREACH paragraphs

```text
A14.3 — Styled FOREACH

{{#foreach:people}}

{{name}}
{{role}}

{{#endforeach}}
```

Use different paragraph styles for name and role, visible spacing, and inline formatting.

Rows:

```text
Ada Lovelace / Analyst
Grace Hopper / Engineer
Katherine Johnson / Mathematician
```

Question: Are paragraph styles, inline formatting, and spacing cloned faithfully?

### Case 4 — FOREACH containing a named Writer table

```text
A14.4 — FOREACH named table

{{#foreach:orders}}

[Writer table named OrderTable]
Customer | {{customer}}
Amount   | {{amount}}

{{#endforeach}}
```

Use Writer's table naming UI and name the table exactly `OrderTable`. Give it visible formatting.

Rows:

```text
Alpha GmbH / 120.00 EUR
Beta AG / 240.00 EUR
```

Questions: Are both tables rendered? What does Writer do with duplicate `table:name="OrderTable"` identities on open/save?

### Case 5 — FOREACH containing named native objects

Inside the repeated block create:

- a Writer bookmark named `RepeatedBookmark`;
- a Writer Section named `RepeatedSection`.

Outline:

```text
A14.5 — FOREACH native identities

{{#foreach:entries}}

[Section RepeatedSection]
Bookmark RepeatedBookmark marks:
{{label}}

{{#endforeach}}
```

Rows: `First entry`, `Second entry`.

Questions: Does Writer silently rewrite duplicate identities? Can both objects still be addressed? Does visible content remain intact despite ambiguous identity?

### Case 6 — IF inside FOREACH

```text
A14.6 — Nested IF inside FOREACH

{{#foreach:people}}

{{name}}

{{#if:active}}
ACTIVE
{{#else}}
INACTIVE
{{#endif}}

{{#endforeach}}
```

Rows should include both active=true and active=false. Also assign global `active = false`.

Current characterized expectation: foreach row binding consumes the nested control markers before the conditional pass, so both ACTIVE and INACTIVE branches remain for every row.

## Manual generation

After creating the Writer fixture:

```bash
php tools/research/template-authoring-a14.php
```

Then render it:

```bash
tools/visual-regression/render.sh research/output/template-authoring-a14-output.odt
```

The PDF/PNG candidates will appear under `tmp/visual-regression/`.

## Evidence to record

For each case capture:

1. source Writer screenshot;
2. generated Writer screenshot;
3. generated PDF/PNG where useful;
4. relevant `content.xml` before render;
5. relevant `content.xml` after render;
6. if Writer changes the file on save, the post-Writer XML as a third state.

Classify observations as:

```text
PRESERVED
VISUALLY_OK_BUT_STRUCTURALLY_UNSAFE
WRITER_REPAIRED
VISIBLE_DEFECT
UNSUPPORTED_BOUNDARY
```

Do not use PASS/FAIL yet. A1 is characterization.

## Expected architectural value

A1.4 should distinguish three failure classes historically conflated as "formatting gets lost":

```text
A. actual style/format loss
B. retained visual formatting with invalid/ambiguous native identity
C. structural control operating at the wrong native ODF boundary
```

The XML characterization so far suggests B and C may be more important than A.

## Stop point

No production repair belongs in A1.4.

After Writer evidence is recorded, A1 should close with a synthesis separating behavior safe to preserve, compatibility behavior that should not define the new high-level path, evidenced defect candidates, and architecture requirements for TEMPLATE-AUTHORING-01B/C/D/E.

# TEMPLATE-AUTHORING-01A1.4 — LibreOffice Regression Fixtures

Status: CHARACTERIZED / NO PRODUCTION CHANGE

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


## Characterization results

The Writer-authored fixture and generated output were reviewed visually and at the native `content.xml` level.

### A14.1 — Styled IF

Observed output:

```text
PROFILE HEADING
[selected profile paragraphs]
FALLBACK HEADING
```

The selected profile paragraph and inline formatting remain visually intact. However, the fallback heading survives even though `show_profile = true`.

Classification:

```text
VISIBLE_DEFECT
```

Interpretation:

- this is not primarily style loss;
- selected Writer formatting is preserved;
- paragraph-based control does not reliably correspond to the intended structural branch when heterogeneous Writer paragraph/style structure is present.

### A14.2 — IF around table

Expected with `show_table = false`:

```text
Table hidden fallback.
```

Observed output:

```text
[fully rendered Writer table]
SHOULD NOT APPEAR
Table hidden fallback.
```

The table remains visually complete and the scalar inside the table is rendered before the conditional phase.

Classification:

```text
VISIBLE_DEFECT
UNSUPPORTED_BOUNDARY
```

Interpretation:

The classic conditional engine operates on `text:p` control/branch paragraphs. A sibling `table:table` is not semantically owned by that paragraph control block, so the visible Smarty-style markers cannot safely express "this native table belongs to the IF branch".

This is direct evidence for native structural controls such as Section-based declarations.

### A14.3 — Styled FOREACH

Observed:

- all three rows render;
- name/role paragraph formatting remains;
- visible indentation and spacing remain;
- no obvious style degradation is visible.

Classification:

```text
PRESERVED
```

Interpretation:

Classic foreach cloning can preserve ordinary Writer paragraph and inline formatting surprisingly well because it clones native nodes rather than rebuilding their styles.

The historic phrase "foreach loses formatting" is therefore too broad.

### A14.4 — FOREACH named table

Observed visually:

- both Writer tables render correctly;
- cell colors, borders, widths, and text formatting remain;
- row-local values are inserted correctly.

Native XML observation:

The source has one native table identity:

```xml
table:name="OrderTable"
```

The generated document contains two table instances carrying the same native name.

Classification:

```text
VISUALLY_OK_BUT_STRUCTURALLY_UNSAFE
```

Interpretation:

The clone operation preserves the table structure and styling, but classic foreach does not understand document-global/native identity. This contrasts with the completed SECTION-03 instantiation model, which owns identity rewriting explicitly.

### A14.5 — FOREACH native identities

Observed visually:

- both repeated blocks render;
- both row values are present;
- visible content remains intact.

Native XML observation:

The authored template contains one:

```text
text:section text:name="RepeatedSection"
text:bookmark-start/end text:name="RepeatedBookmark"
```

The generated output contains two copies with the same Section and Bookmark names.

Classification:

```text
VISUALLY_OK_BUT_STRUCTURALLY_UNSAFE
```

Interpretation:

This confirms that classic foreach clones native identities verbatim. Visual correctness is therefore insufficient evidence for structural correctness.

Any future structured/declarative repeat path must reuse identity-aware Section/document mechanics rather than raw clone semantics.

### A14.6 — IF inside FOREACH

Observed for every row:

```text
<name>
ACTIVE
INACTIVE
```

The result matches the A1.2 automated characterization exactly.

Classification:

```text
VISIBLE_DEFECT
```

Interpretation:

Classic foreach row binding consumes nested conditional markers before the later conditional pass. Writer therefore receives both branches as ordinary surviving content.

The finding is now supported on three levels:

```text
automated characterization
    +
native XML inspection
    +
actual LibreOffice rendering
```

## Revised A1.4 conclusion

The manual Writer evidence changes the emphasis of the original problem statement.

The broad historic statement:

> classic conditions/foreach lose formatting

is not accurate enough.

The better characterization is:

```text
1. ordinary paragraph/text styling can be preserved well;
2. classic controls do not reliably own heterogeneous native ODF structures;
3. raw foreach cloning duplicates native identities;
4. nested classic controls have broken lifecycle/data-scope behavior.
```

The principal defect classes are therefore:

### A — Structural-boundary failure

Visible control markers expressed as paragraphs cannot reliably define ownership of sibling native structures such as tables, Sections, frames, or other block objects.

### B — Native-identity failure

Raw cloning preserves native names verbatim and therefore produces visually plausible but structurally ambiguous documents.

### C — Nested-control lifecycle failure

Classic foreach row binding consumes nested template-control tokens before the conditional phase can evaluate them.

### D — Actual style loss

Not established as the dominant failure class by this fixture. Ordinary paragraph and table styling survived the tested foreach cases well.

This distinction is important for TEMPLATE-AUTHORING-01: the new structured/native template philosophy should not replace working style-preserving clone behavior unnecessarily. It should solve the missing structural ownership, identity, inspection, and control semantics around it.

## Architecture consequence

A1.4 provides concrete evidence for the planned dual template philosophy:

```text
Simple Template Processing
    visible {{...}} syntax
    best for scalar values and lightweight paragraph-local logic

Structured / Native Template Processing
    named Writer objects
    declarative Section controls
    identity-aware instantiation
    unified inspection
```

A native declaration such as:

```text
#if:show_table
#foreach:orders
```

on an actual Writer Section gives the engine a real native subtree to retain/remove/instantiate. It removes the need to infer structural ownership from two visible text-marker paragraphs.

This is now an evidence-based architecture direction rather than only an authoring-UX preference.

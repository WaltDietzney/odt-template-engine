# TEMPLATE-AUTHORING-01B — LibreOffice Visual Regression Preflight

Status: PLANNED / REQUIRED BEFORE PHASE-B CLOSEOUT

## Purpose

Phase B is inspection-only, but it touches template-expression projection, condition parsing,
source lifecycle, native-object discovery, source provenance, and semantic scope projection.

Automated tests prove structural and semantic invariants. They do not replace a manual Writer
regression. Phase B therefore requires a LibreOffice visual preflight before Slice 5 and the
overall phase can be closed.

The visual gate has two goals:

1. prove that representative existing generated documents still render normally in LibreOffice;
2. provide one LibreOffice-authored reference template that visibly demonstrates the native
   structures now described by inspectTemplate().

No sample output file is part of the implementation contract. Files under samples/output may be
regenerated locally for this manual gate and must not be committed merely because the regression
was performed.

LibreOffice lock files (.~lock.*#) must never be committed.

## Existing regression set

The manual regression should remain intentionally small and representative.

### Classic template processing

Run:

    php samples/sample_10_smarties.php

Inspect:

    samples/output/output_10_smarties.odt

Check in LibreOffice:

- document opens without repair warning;
- classic conditions render plausibly;
- foreach-generated content is present;
- variable/filter replacement is complete;
- rich text, lists, tabs, image placement, and metadata-driven document content remain visually sane;
- no visible template control markers remain in rendered output.

This sample is the primary visual regression for the classic template-language path affected by
the Phase-B condition-grammar reconciliation.

### Professional layout benchmark

Run:

    php samples/sample_21_cvProfile.php

Inspect:

    samples/output/output_21_cvProfile.odt

Check in LibreOffice:

- document opens without repair warning;
- two-column CV layout remains intact;
- sidebar and main content align as before;
- image, native lists, headings, spacing, and named paragraph styles remain visually coherent;
- no unexpected page-flow or style regression is visible.

This sample remains the practical architecture benchmark for professional authored ODT output.

### Table layout regression

Run:

    php samples/sample_26_tableLayout.php

Inspect:

    samples/output/output_26_tableLayout.odt

Check in LibreOffice:

- document opens without repair warning;
- absolute/relative table geometry is plausible;
- table alignment and column ratios remain intact;
- row-height and vertical-alignment examples remain visibly distinct.

### Frame layout regression

Run:

    php samples/sample_27_frameLayout.php

Inspect:

    samples/output/output_27_frameLayout.odt

Check in LibreOffice:

- document opens without repair warning;
- text boxes/images remain at their intended positions;
- paragraph/page anchoring and wrapping remain plausible;
- no frame unexpectedly moves outside its intended carrier.

## New Phase-B LibreOffice reference fixture

Existing samples are useful runtime regressions, but none shows the complete Phase-B inspection
model in one authored Writer document.

Create a new independent LibreOffice reference fixture:

    tests/fixtures/libreoffice-reference/odt/
    TEMPLATE-AUTHORING-01B-inspection-contract.odt

The fixture must be created manually in LibreOffice, not by the template engine.

### Authored structure

The document should remain a compact one-page profile/CV-like document.

Page/header:

- one normal Writer header on the Standard master page;
- visible text containing {{document_title}};
- one named Frame in the header, for example HeaderBadge.

Body:

- visible title/name placeholders:
  - {{name}}
  - {{profession}}

- one normal named Section:
  - ProfileNotes

- one declarative candidate Section:
  - #if:show_profile
  containing visible profile text and {{profile}}

- one declarative candidate Section:
  - #foreach:experience
  containing:
  - a named Table: ExperienceTable
  - visible placeholders {{company}} and {{role}}
  - a named Frame inside the table or section: ExperienceBadge

- inside #foreach:experience, one nested declarative candidate Section:
  - #ifnot:hidden
  containing visible text

- one Bookmark in the body:
  - ProfileBookmark

- one second Table or Section using a normal non-control name to prove that ordinary native names
  remain ordinary.

The layout should be visually deliberate but simple enough that the structure is easy to inspect
in LibreOffice Navigator.

### Visual inspection of the authored fixture

Before any engine operation:

- open the fixture in LibreOffice;
- verify header, sections, table, frame, and bookmark are visible/inspectable in Navigator;
- verify the Section names are exactly as authored;
- verify normal named Sections are not confused with declarative candidates;
- verify the document has no repair warning.

Phase B does not execute the native #if/#ifnot/#foreach Sections. The fixture demonstrates authored
structure and inspection semantics only.

### Provenance record

After creation, extend:

    tests/fixtures/libreoffice-reference/README.md

with:

- fixture ID/name;
- LibreOffice version;
- operating system/platform;
- creation date;
- exact manual creation procedure;
- whether reopened/resaved;
- SHA-256 of the original ODT;
- relevant ODF version if available.

Do not silently regenerate this reference with another LibreOffice version.

## Engine inspection gate for the new fixture

After the fixture exists, add a focused integration test that loads it through OdtTemplate and
asserts at least:

- inspectTemplate() returns contract_version = 1;
- body and styles.xml header evidence are both present;
- {{document_title}} is sourced from page-owned styles.xml content;
- {{name}}, {{profession}}, and {{profile}} are binding evidence;
- #if:show_profile is RECOGNIZED;
- #foreach:experience is RECOGNIZED;
- nested #ifnot:hidden is RECOGNIZED;
- ExperienceTable and ExperienceBadge retain native ownership;
- ProfileBookmark is inventoried;
- ProfileNotes remains an ordinary native Section;
- derived dependencies include:
  - document_title
  - name
  - profession
  - show_profile
  - profile
  - experience[]
  - experience[].company
  - experience[].role
  - experience[].hidden
- dependency_mapping remains READY;
- serialization is deterministic;
- no DOM/process-local identity leaks into the public contract.

The test must inspect the original authored source and must not mutate or render the fixture.

## Phase-B visual closeout gate

Phase B may be closed only when all of the following are true:

- complete automated test suite is green;
- PHP lint is green;
- composer validate is green;
- git diff --check is green;
- existing visual regression set passes in LibreOffice;
- the new LibreOffice-authored Phase-B fixture has documented provenance;
- the new fixture passes its focused inspectTemplate() integration test;
- no local sample-output or LibreOffice lock artifact is accidentally committed.

Only after this gate should Slice 5 and TEMPLATE-AUTHORING-01B receive final COMPLETE / GATE GREEN
closeout documentation.

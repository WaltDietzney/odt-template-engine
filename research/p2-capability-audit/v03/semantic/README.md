# H09 — bounded native heading semantics closeout

2026-11-24. Source baseline f8679a782a23b82ca2b9a0536d7affff6554301c.
No production source/API changes. Original accepted v03 geometry and artifacts
are preserved, not overwritten or re-investigated.

## Source-first finding

Paragraph emits text:p even when styled as Heading 1. H01 explicitly documents
this boundary. Existing authored text:h is supported by template projection,
replacement/processing, structured insertion, Section mutation/reading, page
style handling and inspection. A new public Heading class is not required for
the demonstrated authored-template route.

The accepted p2-v03-fixed.odt contains zero text:h elements: its visible labels
are editable text inside drawing shapes. Graphical acceptance alone therefore
does not establish document outline semantics.

## Bounded construction

Reuse the accepted A/B/C contours and fixed dimensions. For semantic headings,
emit the visible label exactly once as body text:h, text:outline-level=1.
A textless native drawing group supplies the decoration behind that text.
No invisible duplicate heading, duplicate shape label or rasterized text.

generate.py derives an authored template from the preserved accepted ODT:
- keeps all five shape contours, widths, heights and accepted relative geometry;
- removes visible labels from the decoration;
- uses actual document-flow headings with controlled text/paragraph formatting;
- adds a 0.45cm vertical decoration offset for this reference's native heading
  line metrics; this is calibrated evidence, not a generic positioning formula;
- keeps horizontal contour coordinates unchanged; paragraph left margin 0.25cm
  provides text inset, with bounded right clearance for the 12cm band;
- places the fold/accent with their existing relative geometry.

bind.php runs existing OdtTemplate assign/render/save on authored headings,
repeats render/save, reopens and saves again. No production XML post-save patch
is used. Classic scalar expressions here exercise the existing heading binding
path; the exclusive native-User-Field contact contract is unchanged.

## Verification

Three lifecycle documents, three headings each:
- engine bound output, repeated render/save, and reopened output;
- exact native headings/outline levels survive Writer save;
- visible heading text occurs once, not again inside shapes;
- actual PDF outlines contain the three expected headings in order;
- all three PNG pages are byte-identical after Writer roundtrip;
- repeated engine save has identical content.xml/styles.xml.

The final page was visually inspected for text placement and existing contour
appearance. Writer Navigator GUI interaction was not manually operated; native
outline structure and real exported PDF bookmarks are the technical evidence.
This is not a PDF/UA or general accessibility-compliance claim.

The first decoration offset clipped the white labels visually even though PDF
text extraction and bookmarks were correct. That failure is preserved in
failed-offset/. The final calibrated offset restores text within the bands.
Do not use text extraction alone as evidence for visible text.

Reproduce from this directory:

```sh
python3 generate.py
php bind.php
python3 render.py semantic-headings semantic-headings-repeat semantic-headings-reloaded
python3 verify.py
```

Uses existing local accepted ODT, PHP engine source and system Writer/Poppler.
No network or new dependency installation. Machine evidence: verification.json;
PDF/PNG, Writer saves and render logs are retained alongside it.

## Implementation handoff

Keep two legitimate recipes distinct: accepted drawing-text headings for
graphics, and body text:h plus textless decoration when outline semantics are
needed. Do not silently claim a shape label is a document heading.

Programmatic text:h construction through a public element API, dynamic/overlong
labels, generic anchor/height/width policies, cross-part contexts and interactive
editing remain bounded implementation-contract questions. They do not require
repeating accepted geometry or prevent closing native feasibility/design.
No new production API, commit, push or merge is approved by this research.

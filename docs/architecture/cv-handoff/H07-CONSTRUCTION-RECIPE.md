# H07 – Dynamic timeline construction recipe

## Current status — H07 owner closeout, 2026-11-24
**H07 native feasibility/design is accepted and closed in its bounded scope.**
See [H07 closeout](H07-CLOSEOUT-2026-11-24.md) for accepted timeline/contact
variants, authoritative evidence, limits and implementation handoff.
Earlier pending-owner statements below are historical and superseded for this
accepted scope. Engine integration/public API and H10 remain separate.

Linear and alternating multipage variants visually accepted by owner.
Script-driven composition explicitly approved as CV-specific exception to
template-structure-first authoring; reuse template styles where possible.
Evidence: ../../../research/p2-capability-audit/v02/native.py, centered.py,
alternating.py, p2-v02-centered.odt and p2-v02-alternating-flow.odt.
- One text:section per station, section style with two columns, gap 0.6cm,
  relative widths 16384*/49151*, each column start/end indent 0.3cm.
- style:column-sep width 0.04cm, height 100%, color #87accb, vertical-align middle.
  Native Writer flow determines station height and axis length.
- First left paragraph carries date (linear) or heading (alternating).
  Right column begins with paragraph style fo:break-before=column.
  Alternating variant moves detail paragraphs before the column-break paragraph
  for left-content stations, right column then carries date.
- Marker: native ellipse, rect or diamond custom shape, 0.4cm square,
  paragraph anchor, foreground run-through, solid #375e80, no stroke.
  Horizontal from-left relative paragraph; vertical middle relative paragraph.
  x=8.30cm, y=0, calibrated to prototype page only; PDF center 297.30pt
  matches separator. Do not infer general coordinate formula from this offset.
  Diamond path M 10800 0 L 21600 10800 10800 21600 0 10800 Z N.
- Twelve alternating stations, station six with 45 extra paragraphs, four pages;
  all station labels and final detail present; roundtrip PNGs pixelidentical.
Rejected: tables; fixed-position frames with connectors (overlap); nested
sections inside columns (span width and interrupt flow despite XML survival).
Reuse SectionElement, Paragraph/RichText, existing shapes and style services.
Open: generic marker positioning, zero/one/many lifecycle, contact/user fields,
reading order, component ownership, cross-part behavior and repeated save.
No new public API approved. Contact composition remains a separate evidence gap.

## Contact composition – P2-V04
Use authored native User Fields for repeated values (existing setUserField API),
classic paragraph conditions with explicit boolean show_* flags for optional
rows, normal flowing paragraphs for contact text. No new API needed for baseline.
Bare ordinary string conditions fail with TypeError under existing boolean
filter semantics; preserve legacy behavior. Consumer computes presence flags.
Evidence in research/p2-capability-audit/v04: full, minimal and long variants,
body/header field binding, Writer PDF and roundtrip comparisons. Human gate open;
narrow sidebar and all-empty cases remain to investigate.

## Owner correction – contact authoring contract
Contact values must exclusively use native Writer User Fields, including
address components, phone, mobile, email and website. No classic template
variables or classic conditional markers in the contact template.
The initial V04 classic-condition prototype is historical partial evidence,
not an accepted construction recipe. Symbols must be independently variable.
Investigate native conditional paragraph visibility including anchored icons;
distinguish hiding from actual removal and assess retained data. No new API
or production change approved.

## Contact design continuation
See [H07 contact composition design](H07-CONTACT-COMPOSITION-DESIGN.md).
The owner favors configurable graphical compositions inside sidebar frames,
with independently variable icons and exclusively native User Fields.
The earlier classic-condition recipe is superseded for contacts.

## Native contact feasibility — 2026-11-24 continuation
V04 A–F research now lives in repository evidence file `research/p2-capability-audit/v04/native/README.md`.
Four native-field visual directions, dynamic rows, long values, structural omission,
SVG families/custom artwork, resources, actual engine binding/replacement, and
Writer/PDF roundtrips are available. Owner visual acceptance remains pending.
31 documents / 36 PNG pages passed structure and roundtrip comparison; the
intentional capacity-overflow candidate is explicitly rejected (website/profile
missing from PDF). No engine implementation or public API has been approved.
PHP 8.5.4 recognizes these SVGs through getimagesize; mapped SVG preflight
rejects the same recognized SVG because the expected-MIME switch lacks SVG.
This supersedes the assumption that getimagesize always fails on SVG on Patil.
PHP 8.2 compatibility remains unproven. Existing field binding does not create
fields or remove rows. Hidden native paragraphs retain XML/resources.
Use construction-time omission for these prototypes; future authored-field
present-to-empty recomposition and overflow policies need explicit contracts.

## Compact composition continuation — 2026-11-24
The current class-based research implementation is recorded in repository file
`research/p2-capability-audit/v04/composition/README.md`. It reuses the existing
Shape/Image/Paragraph/DrawTextBox classes, with ContactItem, ContactBlock and
ContactStrip as research composition responsibilities. Style variants include
compact, uniform circle/rounded-rectangle sidebar, labeled serif/color and
full-width horizontal above/inline symbols. No tables/classic contact syntax.
Targeted valid-XML Writer evidence: native User Fields disappear in Shape text
and draw:g (including grouped DrawTextBox), but survive in Writer text frames.
Thus only icon/backing use draw:g; the complete contact uses a Writer frame.
Minimum-height authoring and drawing-context style placement need bounded
existing-component integration contracts. No production API is approved.
Source shape/catalog/skillbar evidence is reused rather than reopened.
Visual owner acceptance and later implementation/preflight remain separate.

### Owner composition direction — indented horizontal region
2026-11-24: owner proposes a wrapping Section with left/right indents to place
the above-icon strip within a narrower region. Section owns outer clearance;
the strip owns internal left/center/right distribution. Its current explicit
width must match that region; automatic available-width inference remains open.
Reuse existing Section capabilities; no new wrapper API is implied.

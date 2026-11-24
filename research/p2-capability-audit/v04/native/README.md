# H07 native contact composition — V04 A–F

2026-11-24. Research evidence, **owner visual acceptance pending**.
Engine baseline and Forgejo develop verified at
f8679a782a23b82ca2b9a0536d7affff6554301c.
No production source/API changes. No commit, push, merge or deployment.

## Review entry points

- contact-classic.odt: hanging-indent paragraphs, icons left, native fields.
- contact-sidebar.odt: dark outer Writer frame, dynamic two-column table,
  independent native circle/ellipse/rectangle/rounded-rectangle/square backings.
- contact-horizontal.odt: fixed-width as-character item frames, three per row,
  icon above editable native field paragraphs; Writer wraps each value.
- contact-editorial.odt: same table carrier, independent label paragraphs.
- contact-sidebar-long.odt and contact-horizontal-long.odt: narrow layout,
  long address and unbroken email/URL, no inserted discretionary characters.
- contact-minimal.odt, contact-one-address.odt, contact-empty.odt:
  omitted components/rows and completely absent contact frame.
- contact-header-sidebar.odt: field composition in styles.xml master header,
  native fixed page sidebar, independent body clearance. H02 pattern reused;
  this is not a new multipage H02 acceptance.
- contact-design-options.odt: Lucide, Tabler and original custom SVG;
  icon on right, top/middle/bottom cell alignment, labels and native separators.
- contact-resources*.odt: content-hash deduplication in research serializer,
  independent color/stroke variants, named image replacement isolation.
- contact-*-bound*.odt / *-reloaded.odt / contact-crosspart-bound.odt:
  real engine setUserField, repeated render/save, reload, cross-part references.
- contact-engine-svg*.odt: real ImageElement → appendBody → package → Writer,
  explicit SVG dimensions on the installed PHP 8.5.4.
- contact-hidden*.odt and contact-sidebar-blank-bound.odt: visibility/binding
  diagnostics, **not structural omission recipes**.
- contact-overflow-rejected.odt: intentionally rejected capacity stress case.

Corresponding PDF, PNG and Writer-saved ODT are in pdf/, images/, roundtrip/.
pdf-rt/ and images-rt/ represent a second Writer load and PDF export.
xml/ contains exact initial content/styles/manifest for generated originals.
verification.json records each input and roundtrip field/resource inventory,
PDF-value completeness and byte comparisons of every rendered PNG page.
Raw PDF extraction is used for value completeness: spatial -layout extraction
interleaves simultaneous columns and cannot test whole field strings.

## Reproduce

Run from any directory; scripts resolve their own research directory:

```sh
python3 /srv/projects/odt-template-engine-p2/research/p2-capability-audit/v04/native/generate.py
php /srv/projects/odt-template-engine-p2/research/p2-capability-audit/v04/native/engine-probe.php
python3 /srv/projects/odt-template-engine-p2/research/p2-capability-audit/v04/native/variants.py
php /srv/projects/odt-template-engine-p2/research/p2-capability-audit/v04/native/extra-probes.php
python3 /srv/projects/odt-template-engine-p2/research/p2-capability-audit/v04/native/render.py
python3 /srv/projects/odt-template-engine-p2/research/p2-capability-audit/v04/native/verify.py
```

Dependencies: Python stdlib, PHP with DOM/ZIP, LibreOffice Writer, pdftoppm,
pdftotext. No dependency on historical /tmp fixtures or a composer autoloader
for fixture generation/binding. render.py uses an isolated temporary Writer
profile for every conversion, checks output existence, and bounds commands to
90 seconds. Pass document stems to render.py for focused rerenders.
Reruns replace only this script-owned evidence set; inspect owner edits first.
No samples/output file is regenerated. Original V04 evidence is unchanged.

Assets are already vendored; offline regeneration needs no downloads.
assets/provenance.json pins each upstream URL, exact commit and SHA-256.
Lucide license includes ISC **and** the Feather MIT notice (smartphone subset);
Tabler uses MIT. Full notices are retained locally and inside generated ODTs.
Custom mail hexagon is original research artwork in variants.py.
No external icon font or runtime network dependency.

## Native recipe

Every contact value is a text:user-field-get tied to a string-valued
text:user-field-decl. Address pieces remain separate fields, joined by native
spaces and line breaks only for present pieces. No classic variables or
classic conditions are present. Text stays native Writer text, never SVG text.

Outer carrier: draw:frame / draw:text-box, based on the common graphic
parent style Frame. Per-side padding and fixed width; Writer determines
height. Classic list: hanging indent and native tab stop, icon as-char.
Sidebar/editorial: borderless two-column table, 0.85/4.35 cm reference widths,
minimum row height 0.65 cm, no fixed height, top-aligned cells and explicit
padding. These values are examples, not API defaults.

Icons: embedded SVG resources, 24-unit viewBox, explicit 0.45 cm square
draw:frame and image/svg+xml manifest. Common Graphics parent for standalone
Writer images in a text frame. SVG artwork and native ODF backing shapes
remain independently editable/replacable objects. Backed icons use a local
draw:g; shape z-index 0, icon z-index 1 and explicit foreground run-through.
Fixed coordinates apply only within this small icon, never to contact rows.
Ellipse/rectangle icon offsets center the image inside the shorter backing.
The last, unbacked dark-sidebar icon uses a light contrasting color.

Horizontal policy: bounded item width, top-aligned as-char frames, explicit
three-item rows. Width changes reflow whole items within the paragraph;
this is not a general responsive-width algorithm or a guarantee that unequal
item widths fill every row. Values wrap independently and subsequent rows
follow the actual tallest item. Tables remain an alternate horizontal carrier
if a consumer needs strict shared column geometry.

Omission is performed before generating rows. Missing rows emit no label,
icon, separator, reference or unused declaration. All-empty emits no contact
frame or image resources. This does not imply automatic structural reflow
when an existing authored field is later changed from present to empty.

## Source reuse and real gaps

- Existing setUserField/UserFieldAnalyzer/UserFieldBinder bind authored string
  declarations; unknown field binding throws. They do not construct fields.
  Cached field display changes only when Writer reevaluates; old cached values
  can remain in engine-saved XML before that Writer step.
- Paragraph/RichText can carry flow; Paragraph currently places text parts
  before embedded elements. General icon-first interleaving and native field
  construction require a bounded contract, not an invented Contact class.
- RichTable, existing H04 column identity and cell layout provide the alternate
  dynamic carrier. Cross-part table/style ownership must be addressed explicitly
  before generating such content through production engine services.
- H02 provides page/sidebar composition. H03 provides native geometry,
  appearance, text placement, local groups and cross-part style evidence.
  Do not copy SkillBarGroup's fixed text-row stepping.
- ImageElement and named replacement work with explicit SVG dimensions on this
  PHP 8.5.4 runtime. getimagesize returns type 21 and image/svg+xml here.
  **Mapped preflight rejects the same valid SVG**: when getimagesize succeeds,
  isSupportedImage's expected MIME match has no svg case. This is a runtime-
  dependent inconsistency, not evidence for a production fix in this audit.
- PHP 8.2 compatibility, SVGs without explicit intrinsic dimensions, units,
  non-square ratios, production resource deduplication, same-basename isolation,
  external SVG sanitization and cross-part replacement remain separate gates.
  The research content-hash resource policy is not existing engine behavior.
- SVG import may generate a PNG fallback during Writer save; the SVG remains
  embedded. Do not claim Writer preserves archive/resource names byte for byte.

## Failed approaches and bounded interpretations

1. failed-v1/: no Frame parent style. Writer imported the container as drawing
   text: tables disappeared and native fields within the frame were lost.
   Parent-style addition was isolated in the next candidate.
2. failed-v2/: correct Writer text frame, but namespace-prefixed SVG root.
   Images were dropped, though an equivalent SVG default namespace works.
   svg-default-namespace comparison preserved there.
3. Standalone body SVG works without Graphics parent; the same image inside a
   Writer text frame disappears unless the Writer Graphics inheritance is
   supplied. *-graphics-parent candidates isolate this change.
4. Group backings initially covered their image children. Explicit foreground
   run-through on both shape and image, plus child z-order, restores symbols.
   Earlier generator variants are preserved under failed-v2/.
5. failed-header-wrap/: a fixed header sidebar with wrap=none enlarged the
   header and pushed body text below the frame. Use H02-style independent
   placement and explicit foreground run-through for this probe.
6. Missing meta.xml prevented engine loading the first hand-built package.
   The final generator emits it. Explicit loext namespace avoids warnings from
   engine-injected default list formatting in otherwise minimal fixtures.
7. Native hidden paragraphs are visibility, not removal: XML field/reference
   and image resources remain. A blank setUserField leaves its row and icon.
8. contact-overflow-rejected is intentionally beyond frame capacity: website
   and profile are absent/incomplete in PDF. **Never silently ship this case.**
   A capacity/overflow gate, explicit fallback or a separately proven linked
   frame flow is required before production. No generic pagination API approved.

## Security boundary

The prototypes only process a small pinned trusted subset of SVG shapes,
reject DTD/entities, unexpected element types, event/href/url attributes and
oversized roots. This is a research allowlist, **not a complete SVG sanitizer**.
Engine mapped preflight only recognizes SVG XML; recognition is not safe
processing. A future contract must define allowed origins and SVG subset,
external references, scripts/CSS, size/complexity limits, failure atomicity and
resource ownership. No malicious assets were passed to Writer during research.

## Evidence and gates

See engine-probe.json, extra-probes.json, verification.json and
phpunit-user-fields.txt. Focused existing C0/C1/C2 tests: 20 tests,
308 assertions, no failures, three PHPUnit deprecations. No production source
changed, so a full implementation/release preflight was not claimed.
All candidate XML/package/PDF checks and PNG comparisons are technical
evidence; manual native selection/editing and owner visual acceptance remain
separate. H07 is not closed, H09 semantics/H10 have not been started here.

ODF source: supplied OASIS OpenDocument 1.2 Part 1, §§7.4.7–7.4.9,
7.7.11, 10.3.15, 10.4.2–10.4.4. The standard allows tables/paragraphs within
text boxes; observed Writer import differences above are application behavior,
not a general ODF prohibition.

### Final technical verification
31 documents, 36 PNG pages: native field declarations and references survive
Writer; all PNG pages are byte-identical after roundtrip. Only the intentionally
rejected overflow document is missing field values in PDF (website/profile).
Native hidden-paragraph condition is reversible after binding a non-empty phone,
but retains its objects/resources. Cross-part name references reevaluate to
CROSSPART UPDATED. Extra engine probes emitted no PHP warnings.
Strict Zensical build passed using the installed /tmp/h04-docs-venv runner.
The requested docs/README.md does not exist at this baseline; docs/index.md
is the actual documentation entry point and was read instead.

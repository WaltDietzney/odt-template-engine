# TEMPLATE-AUTHORING-01F — Sample Registry and Migration Evidence

F1 establishes `samples/sample-registry.php` as the explicit inventory of
numbered sample artifacts. The file returns a small PHP data array so the
Sample Explorer, sample-generation allow-list, and `PublicSampleSmokeTest`
share the same metadata without a parser or new runtime dependency.

Each entry records a stable ID, title, current role, primary ownership model,
PHP entry point, explicit template/output paths, purpose, migration status,
planned L/C/S destinations, distribution availability, and whether execution
generates an ODT or reports inspection data. IDs beginning with `legacy.` are
migration records; planned L/C/S destinations do not claim that canonical
files already exist. Ownership values describe simple template processing,
PHP-owned ODT elements, addressable native ODT structures, or an honest
combination. Inspection is an integration activity, not a fourth authoring
model.

The registry currently includes numbered Samples 01–29. Samples 01–27 are
packaged, runnable ODT examples. Samples 28 and 29 are explicitly
`repository-only` because their declared templates live under `tests/fixtures/`,
which Composer excludes. Sample 28 is inspection-only and has no output ODT;
Sample 29 has its existing output path but is not represented as a
self-contained packaged example. The later L10 migration must supply its own
public template.

The repository's exact Sample 22–24 identities are:

- Sample 22: `sample_22_bookmarkTextReplacement.php` with
  `sample_22_bookmarkTextReplacement.odt`;
- Sample 23: `sample_23_sectionContentReplacement.php` with
  `sample_23_sectionContentReplacement.odt`;
- Sample 24: `sample_24_sectionImageReplacement.php` with
  `sample_24_sectionImageReplacement.odt` and the image asset
  `sample_23_image.png`.

## F5 — Canonical Native Objects, User Fields, and Inspection

F5 completes the canonical Learn sequence with `L09` Native Objects, `L10`
Writer User Fields, and `L11` Template Inspection. L09 exercises only the
existing bounded bookmark and Section mutations; named tables and frames are
resolved for descriptor/read-only use. L10 packages its own public template
containing a real Writer string User Field referenced in the body and header,
closing the public packaging gap exposed by historical Sample 29. Sample 29
remains repository-only and explicitly points to L10 as its canonical
migration destination. L11 is an inspection-mode sample with no output path:
it shows the original source-oriented TemplateContract projections and does
not render or mutate the document. Sample 28 remains as historical
inspection-output evidence and points to L11.

The registry now represents all Learn IDs L01–L11. Collection-oriented
Section instantiation remains a C04/S01b concern; the L09 native-object lesson
does not absorb Sample 25's structured collection behavior. Sample 24's
combined Section-plus-ImageElement replacement remains registered with L09
and L06 as migration targets because those lessons cover its target and
generated-image components separately; the historical example remains the
focused combined regression evidence.

The Sample Guide remains historical during F1. Unnumbered development and
historical scripts are not promoted into the registry. Sample 21's existing
CV hero remains a temporary presentation special case, while its card identity,
role, title, purpose, and migration destination come from the registry.

## F2 — Canonical Learn Basics

F2 introduces the first real L/C/S entries, `L01` Variables & Filters, `L02`
Conditions, and `L03` Repeating Content. Each is a Composer-distributed
`simple-template` sample with an explicit PHP entry point, LibreOffice ODT
template, and reproducible output path. All remaining numbered examples stay
registered as migration entries; no historical PHP sample or template was
deleted.

The migration records now reflect the remaining or corresponding canonical
destinations:

- legacy Sample 01 maps its remaining image-insertion behavior to L06; its
  scalar and repeating lessons now have canonical L01/L03 examples;
- legacy Sample 02's filters/`nl2br` map to L01 and its conditional branch
  maps to L02; all of its demonstrated behavior is now covered, but the
  historical entry remains until a later explicit retirement decision;
- legacy Sample 03's comparison conditions and `ifnot` map to L02; it too
  remains registered with no outstanding migration target.
- the unnumbered `sample_nl2br.php` and `sample_repeating.php` artifacts remain
  physically present; F2 does not perform historical cleanup.

L03 uses separate template-authored paragraphs rather than repeated table
rows. The active `assignRepeating()` renderer locates marker paragraphs and
clones their intervening sibling nodes; existing evidence does not establish
table-row repetition as the stable canonical mechanism. An explicitly
assigned empty collection removes the prototype and creates zero item blocks;
it does not synthesize an empty-state message.

The three templates use the same restrained teal/blue heading and label
language, with ordinary editable Writer paragraphs and tables. They avoid
depending on Writer splitting template expressions across styled spans.

The Sample Explorer and generator needed no F2-specific discovery logic:
their F1 registry-driven metadata automatically exposes the three canonical
Learn samples and their explicit paths.

## F3 — Programmatic Content Fundamentals

F3 adds canonical `L04` Rich Content and `L05` Lists as
`programmatic-elements` samples, and `L06` Images as a `mixed` ownership
sample. L04/L05 keep a LibreOffice-authored document shell and insertion point
while PHP supplies native `RichText`/`Paragraph` or `ListElement` structures.
L06 distinguishes replacement of an existing LibreOffice-authored named
image frame from insertion of a PHP-generated `ImageElement`. Its
placeholder-oriented `setImage()` API remains documented as a distinct
convenience path, not presented as an equivalent third ownership model.

Migration targets were updated conservatively. Sample 18 retains an L05
target for its mixed-style nested-list example, which is not promoted as a
stable canonical visual pattern. Samples 01, 05, 05b, and 06 retain L06
targets because their placeholder image insertion, width-only legacy sizing,
or multiple-frame behaviors are not all demonstrated by the small canonical
sample. Samples 07, 09, 14, and 16 retain
targets for specialized tab/paragraph or mixed-content behavior. Sample 14
also retains L06 for its placeholder-oriented `setImage()` path and S02 for
its integrated metadata/business-document concerns; Sample 16 points to C03
for layout-oriented image positioning. No legacy sample or template is deleted
in F3.

Visual characterization found that a nested `ListElement` with a different
type from its parent can be structurally nested but LibreOffice may display
the child using the surrounding numbering style. L05 therefore uses numbered
steps separately and a homogeneous nested bullet hierarchy; mixed-style
nesting remains an explicitly unpromised layout combination and Sample 18 is
retained for later review rather than declared fully migrated.

LibreOffice headless open/save/reopen of the three generated outputs retained
the visible paragraphs, nested lists, and both L06 images. Writer normalized
list-style identifiers and renamed embedded image resources; the resulting
image references and manifest entries remained consistent. These are package
normalizations, not changes to the sample's ownership semantics.

## F4 — Structured Content and HTML Import

F4 adds canonical `L07` Tables and `L08` HTML Import. L07 uses a
LibreOffice-authored shell/insertion point and constructs a native editable
`RichTable` with a header-row group, styled `RichTableCell` values, and a
multi-run `Paragraph` cell. It deliberately avoids table geometry, spans, and
array-style convenience APIs; those remain migration evidence for C02 or later
focused examples.

L08 keeps the broad Sample 08 capability lineage and incorporates Sample 19's
HTML-table-to-native-table behavior. Its deterministic input uses a local
image and a data URL; it does not fetch remote resources. Remote HTTP/HTTPS
images remain disabled unless `allow_remote_images` is explicitly true. The
current resolver limits response reads to 5,000,000 bytes, uses a 5-second
timeout, does not follow redirects, validates recognizable image content, and
tracks temporary assets for shutdown cleanup.

Characterization also recorded deliberate limits rather than promoting old
claims as guarantees: HTML `th` is not distinguished from `td` by the importer,
`thead` is not represented as repeating ODF header rows, inline CSS is a
bounded `StyleMapper` subset, and nested/adjacent list extraction can detach
or reorder content. Sample 08 therefore retains an L08 migration target for
nested-list behavior. Sample 19's HTML-table behavior is represented in L08.
`sample_html_images.php` float/display/absolute-position experiments are
preserved for C03 review rather than claimed as browser-like layout support.
Sample 12 retains an L07 target for its named paragraph alignment and
per-edge cell border/padding examples; Sample 13 retains L07 for spans and
its broader cell-configuration combinations. Sample 11's remaining target is
C02 table geometry, while Sample 15's focused styled-table structure is
represented by L07. C02 targets remain for actual geometry.

## F6 — Capability Showcases

F6 registers C01 Page & Flow Layout, C02 Advanced Table Layout, C03 Frame
Layout, C04 Declarative Structured Collections, and C05 Mapping & Automation as Composer-distributed capability
samples. The existing registry-driven Explorer and smoke test consume their
explicit entry/template/output paths. Historical samples remain preserved.
C01 contrasts paragraph-flow intent with Writer-owned master-page succession;
C02 avoids incompatible positional column-style requirements; C03 uses the
existing shared `DrawingLayout` API; C04 executes existing Phase-D Section
semantics with template-shaped values; C05 demonstrates inspection, explicit
mapping, concrete preflight, common atomic automation, and explicit save. Thus
C04 is direct template-shaped execution, while C05 is application-shaped data
mapped and preflighted before Phase-E automation.

The additive `OdtTemplate::executeDeclarative()` facade exposes the already
accepted Phase-D behavior while keeping `DeclarativeConditionExecutor`
internal. Sample 25 remains migration evidence for imperative Section
collections and S01b; it is not replaced by C04's declarative model.

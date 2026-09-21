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

# TEMPLATE-AUTHORING-01F — Sample Registry Foundation

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

No canonical L/C/S sample is created or implied by this foundation. Actual
sample migration, suite redesign, and the later Explorer presentation work
remain subsequent Phase-F slices.

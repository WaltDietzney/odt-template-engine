# P2-I04 — SVG and graphic resources

Date: 2026-11-25 (started 2026-11-24). Status: **implemented in the P2 worktree;
native Writer/PDF evidence and runtime checks passed with explicit notices/skips**.
Contract CC05. Branch architecture/p2-capability-audit; source HEAD
473ea17705288ad431c3960835fafa05ec2d5340 plus preserved pending I01b/I02/I03 work.
No commit, push, merge or deployment. Original worktree and sample outputs untouched.

## Result

The PHP 8.5 SVG preflight contradiction is fixed. A bounded internal ImageInfo
utility now supplies the same local static SVG validation and intrinsic metadata
to ImageElement/CircularImageElement, ShapeAppearance bitmap sources, named fill
authoring, legacy image insertion/replacement, mapping preflight and native action
execution. No suitable shared metadata component existed; this one stateless/value
utility replaces duplicate getimagesize/XML paths rather than introducing another
resource registry. SVG packaging revalidates changed source files.

The parser uses a default-namespace static geometry subset, rejects active/external
content, and bounds input size, node count, depth and attribute length. It does not
sanitize arbitrary SVG or rewrite existing authored package resources. Explicit
viewport dimensions take priority for the intrinsic ratio; otherwise viewBox is used.
One dimension derives the other in its original unit. Two explicit dimensions and
legacy fallback dimensions retain their existing meanings. A safe ratio-less SVG
remains admissible with preserved/two explicit dimensions; inference fails explicitly.

Resource preparation retains legacy basenames when free or byte-identical and allocates
a deterministic SHA-256-suffixed path when different content would overwrite a shared
file. Repeated identical preparation reuses that path. Structured insertion retains its
existing atomic rejection of conflicting same-basename bytes; it does not silently
change its identity policy. This is path-based reuse, not global content deduplication
across unrelated filenames. Manifest path comparison decodes XML escaping to avoid
repeat-save duplication with ampersands/quotes. New SVG entries use image/svg+xml.

## Approved bounded API and reuse

Owner explicitly approved:

```php
$template->replaceBitmapFillByName($objectName, $imagePath);
```

This resolves exactly one named frame/custom-shape/rect/ellipse across content.xml
and styles.xml and replaces only its existing bitmap fill. It clones the graphic
style into the object's automatic-style scope, creates a separate fill-image
requirement/declaration, and protects shared source styles/declarations/resources.
Geometry, anchor, text and unrelated appearance properties are retained. Supported
common-style inheritance and inherited background-image hrefs are handled with
cycle/depth checks. A fully specified bitmap does not require synthesizing an
undeclared conventional Frame parent. Missing/ambiguous/non-bitmap targets fail
without partial XML or resource mutation at the existing package snapshot boundary.
PNG/JPEG and supported static SVG sources are admitted.

Implementation extends FillResourceAuthoring and uses FillImageRequirement,
FillImageRequirementMaterializer, OdtPackage preparation and existing document
ownership. No generic object-model, style-flattening framework or contact/icon class
was added. Direct draw:image replacement stays separate. The new bitmap API is not
implicitly exposed as another automation mapping action or page-fill mutation API.

Existing replaceImageByName sizing defaults, duplicate-name behavior and protected
replacement facade signatures/dispatch remain intact. Its resources are now protected
against same-basename overwrite. Fresh setImage output is namespace-addressable for
immediate named replacement. Existing Writer SVG/PNG alternative MIME hints are updated
with their replacement hrefs; unused original fallback bytes are not globally deleted.

## Native evidence and failed approaches

Evidence: research/p2-capability-audit/graphics-resources/, with reproducible generation,
source ODTs, Writer ODTs, PDFs, PNGs, selected actual XML and verification JSON.

- SVG gallery: Lucide, Tabler and custom icon variants in three sizes, width-only
  40mm/20mm non-square SVG, explicit 3cm/1.5cm dimensions, and an SVG bitmap-filled
  native rounded shape. Four source SVG resources serve repeated uses.
- Shared-resource before/after: body fill A and header fill change to orange; body
  fill B and direct image B stay blue. Direct image A changes independently, despite
  the replacement source sharing the basename panel.svg with the original.
- Writer-finalized input: the same replacement is repeated after Writer has renamed
  resources and added PNG alternatives. Actual color probes establish the same
  isolation. This is stronger than checking replacement hrefs alone.
- Seven source/lifecycle documents produce seven pages; every source/roundtrip PNG
  pair is byte-identical. Repeat-save XML/manifest and save/reload invariants pass.
  Protected prior research artifact hashes remain unchanged.
- Upstream notices/provenance are retained beside consumer-generated SVG variants.
  The engine has no runtime icon-library dependency or semantic icon selector.

Failures are documented in the evidence README and retained logs: the original
recognized-SVG preflight rejection; overly strict ratio requirements; same-document
importNode aliasing instead of cloning; an intermediate mixed-namespace attribute
attempt (failed-namespace-attributes.odt); and PHP 8.2 DOM owner-lifetime problems.
Passing outputs are separated from the retained failed package.

## Minimum-runtime finding and bounded correction

The first full PHP 8.2 run exposed ten DOM lifetime errors, including pre-existing
H03 and Section authoring cases. A separate git-archive of committed HEAD reproduced
three representative failures (php82-baseline-tests.log); these were not introduced
by SVG handling. SectionMutationService now retains the staging document and transfers
validated nodes to the live owner. Same-document text import clones before insertion,
preventing child movement during traversal. Tests that held only descendants now retain
their root/document owners. No public API or pagination behavior was changed by this
compatibility correction. The affected 101-test runtime suite and full suites pass.

## Validation and remaining gates

| Check | Actual result |
| --- | --- |
| Focused SVG/resources suite | 31 tests, 79 assertions on PHP 8.5; no failures |
| PHP 8.5.4 full composer test | 1173 tests, 9345 assertions; no failures/errors; known 1 warning, 4 PHP deprecation sources, 8 PHPUnit deprecations |
| PHP 8.2.36 full suite | 1173 cases, 9219 assertions; no failures/errors; 1 warning, 8 PHPUnit deprecations, 1 explicit skip |
| PHP 8.2 skip | Existing H01 external soffice test: no Writer installed inside the isolated PHP container. The host full suite and native Writer evidence cover actual rendering separately. |
| Native Writer | Writer 26.2.6.3, Poppler; seven roundtrips, resource/color/dimension/lifecycle checks passed |
| Runtime metadata | Both PHP versions agree on shared SVG metadata, proportional dimensions and preflight; native PHP SVG recognition is not used as authority |

PHP lint, Composer validation, diff whitespace and strict docs build are recorded in
preflight.json/logs. The PHP 8.2 image is test-only; source is mounted read-only,
execution has no network and tests run in a disposable copy. No host PHP replacement
or project dependency was added.

Owner visually accepted the presented P2-I04 evidence on 2026-11-25 ("Sehr schön, abgenommen").
Interactive Writer selection/editing remains a separate gate; visual acceptance does not imply that this editing check was performed. No universal SVG, arbitrary prefix normalization, crop/cover,
resource garbage collection, automatic field-data interpretation or release readiness
is claimed. Next implementation slice: P2-I05/CC06, bounded partial-ring completion.


## Owner acceptance — 2026-11-25

The owner accepted the presented PDF/native ODT evidence: “Sehr schön, abgenommen”.
P2-I04 visual status: ACCEPTED. This does not authorize commit, push, merge or deployment.
Next functional slice remains P2-I05/CC06.

# P2-I04 — SVG and graphic resources

Worktree /srv/projects/odt-template-engine-p2, architecture/p2-capability-audit.
Started 2026-11-24, completed/verified 2026-11-25. Source HEAD 473ea17705288ad431c3960835fafa05ec2d5340
plus pending I01b/I02/I03 work. [Implementation report](../../../docs/architecture/cv-handoff/P2-I04-IMPLEMENTATION-REPORT.md).
No changes to the original worktree or existing research outputs; no commit/push.

## Reproduction

```sh
php research/p2-capability-audit/graphics-resources/characterize.php
php research/p2-capability-audit/graphics-resources/generate.php
python3 research/p2-capability-audit/graphics-resources/render.py
php research/p2-capability-audit/graphics-resources/rebound.php
python3 research/p2-capability-audit/graphics-resources/render.py writer-rebound
python3 research/p2-capability-audit/graphics-resources/verify.py
vendor/bin/phpunit tests/Integration/SvgGraphicsResourceTest.php
composer test
```

Writer conversion uses isolated temporary profiles. All native sources, Writer ODTs,
PDFs, PNGs, actual XML extracts and command logs are in this directory. The failed
namespace experiment is explicitly separate from passing outputs. No production
output XML patching or rasterized SVG substitution is used.

PHP 8.2 uses a separate test-only Docker image with zip; no project dependency or
host PHP installation is changed. The source mount is read-only, containers have
no network and run tests in a disposable copy:

```sh
docker build -f research/p2-capability-audit/graphics-resources/Dockerfile.php82 -t odt-p2-php82-test:local research/p2-capability-audit/graphics-resources
docker run --rm --network none --read-only --tmpfs /tmp:rw,exec -v /srv/projects/odt-template-engine-p2:/source:ro odt-p2-php82-test:local sh -c 'cp -a /source /tmp/p2 && cd /tmp/p2 && php vendor/bin/phpunit --display-skipped'
```

The Docker build initially downloads the official PHP base and zip build dependencies;
the test execution itself has no network. Existing native Writer tests also run on the
host, since the PHP test container intentionally has no LibreOffice executable.

## Findings, corrections and failed approaches

1. `before.json`: PHP 8.5 recognizes SVG as image type 21, but preflight's raster
   branch had no SVG MIME case and rejected it. A shared runtime-independent parser
   now validates a bounded static SVG subset for both PHP versions.
2. An initial metadata rule required an intrinsic ratio for every SVG. Existing
   E4 tests showed that ratio-less SVG is legitimate when both dimensions are
   explicit/preserved. That conditional boundary is retained.
3. Same-document `importNode()` is not a reliable cloning operation. Explicit
   cloneNode before import protects shared graphic styles and inherited backgrounds,
   especially header/styles.xml targets. Tests preserve the source style after both
   body and header replacement.
4. Fresh legacy setImage nodes were not namespace-addressable by the named resolver.
   Correct namespace creation permits immediate replacement. An intermediate mixed
   namespace-attribute attempt produced duplicate attributes; the failed package is
   retained as failed-namespace-attributes.odt. Creation and update now consistently
   use namespace-aware attributes, with save/reopen coverage.
5. Writer adds PNG alternatives to SVG frames. Replacement updates their href and
   existing MIME hints consistently; the old fallback bytes are not globally deleted.
6. The first PHP 8.2 full run exposed pre-existing DOM owner-lifetime failures.
   php82-baseline-tests.log reproduces representative failures from committed HEAD.
   The bounded correction retains the staging document, transfers validated nodes
   to the live owner, and clones text before same-document import to avoid moving
   children while iterating. Test fixtures retain their parent/root references.
   Initial error/failure logs remain separate from final passing runtime checks.
7. Manifest paths are XML-unescaped when comparing existing entries, preventing
   duplicates on repeat-save for filenames containing ampersands/quotes.

## Resource and licensing boundaries

SVG examples reuse the already researched Lucide/Tabler/custom assets from
../v04/native/assets. Their notices and provenance are preserved beside this suite's
variants. No engine dependency or semantic contact-icon registry was added.
Generated panel artwork is synthetic. Different payloads with basename panel.svg
prove isolated replacement: the selected fill/image turns orange while shared
consumers stay blue, before and after a Writer roundtrip. Source gallery resources
are packaged once per path; Writer may add/rename resources and raster fallbacks.

Input policy and package resource behavior are documented in the API reference.
No universal SVG support, global authored-package sanitization, arbitrary namespace
normalization or crop fitting is claimed. Interactive Writer editing and owner visual
acceptance remain separate from native headless rendering and raster inspection.

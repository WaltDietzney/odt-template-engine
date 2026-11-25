# P2-I07 / H10 — S01b-like integration evidence

## Aktueller Abschluss — 25.11.2026

P2-I01–I07 einschließlich H10 sind im vereinbarten Umfang umgesetzt und abgenommen. Beide Review-v2-Templates sind visuell akzeptiert; der Owner bestätigt die interaktive Writer-Prüfung. Versionierung/Veröffentlichung bleiben separat offen. Verbindlicher Status: [P2-Implementierungsabschluss](P2-CLOSEOUT-2026-11-25.md) und [maschinenlesbarer Stand](P2-CLOSEOUT-STATE.json). Offene Folgepunkte werden dort ausdrücklich aufgeführt.

### Historische Planung und Fortschreibung

Die nachfolgenden älteren Status- und Arbeitsaufträge sind historische Quellen; aktuelle Arbeit wird vom oben verlinkten Abschluss aus gewählt.


2026-11-25. Contract CC08. Status: native integration implemented, technical preflight passed with explicitly bounded raster normalization. Owner visual acceptance and interactive Writer editing remain OPEN; H10 is not finally closed.

Consumer worktree: /srv/projects/cv-generator-pro-p2, implementation/p2-consumer-composition, base cad1e1c8c42329ef52c92cb67f60c06e549ba3fb. Engine P2 branch/Forgejo HEAD remains 473ea17705288ad431c3960835fafa05ec2d5340 plus prior pending P2 changes; develop remains f8679a782a23b82ca2b9a0536d7affff6554301c. Remote refs were verified read-only. No new engine source/public API changes were needed. All 177 engine source hashes and the original consumer worktree status are preserved.

The accepted H02/C08 first/continuation-master/sidebar/main-Section pattern is reused with a 6.4cm sidebar and 12.4cm main region. The native CV combines photo, SVG contact symbols and native User Fields, hard-skill bars, partial ring, individually formatted RichText timeline, semantic headings, rounded unscored tags and project text. Contact information is not represented by classic expressions or drawing text. Typography and CV roles remain consumer responsibilities; Composer release dependency and applicant schema were not changed.

Evidence/scripts and detailed findings: /srv/projects/cv-generator-pro-p2/research/p2-i07/README.md. Source generator dev/generate-h10.php, isolated Writer renderer dev/render-h10.py, verification dev/verify-h10.py, native reopen/inspect dev/inspect-h10.php, review packaging dev/package-h10.py. Generated binaries live only in the consumer's ignored research/p2-i07/artifacts directory.

Five documents / nine pages: normal 2, long 3, minimal 1, empty 1, rebound derivative 2. Complete PDF text, native fields, omitted optional sections, resource/style resolution, gutter/page bounds, repeated continuation contacts and visible icons, headings/PDF outlines, repeat render/save and reopen/inspect/bind are verified. PDF words and bounding boxes are identical after Writer roundtrip. First-page contact symbols have bounded raster differences (368 changed pixels with full contact data; 35 for minimal), confined by the verifier to symbol rectangles at the 1400px scale. Continuation pages are pixel-identical. Do not call all H10 pages pixel-identical.

Integration fixes are in the consumer: ContactItem/Block/Strip now use existing explicit frame spacing and Auto-Grow semantic state, so header graphic styles are actually materialized; a dedicated two-master test prevents dangling graphic references. Continuation contact graphics use white inline symbols, because paragraph-anchored decorations in the nested continuation frame were invisible. Ring children use the accepted explicit foreground recipe; its plain native text label shares the group for alignment. The portrait has an explicit semantic foreground layout. No arbitrary new graphics API or output XML patch was added.

Accepted I06 artifacts were preserved: automatic approval review refused overwriting them, and a fresh regression was generated in H10 artifacts/i06-regression. All 20 prior consumer candidates / 26 pages pass there. Historical failed generator/render/test approaches remain described and their test logs are preserved. The first isolated engine copy lacked demo/; including it resolved the single SampleRegistryTest failure. The PHP 8.2 image lacks Composer, so the exact PHPUnit command behind composer test was used.

Engine full suite on PHP 8.5: 1200 tests / 12393 assertions, no failures/errors, known 1 warning, 4 PHP deprecation sources and 8 PHPUnit deprecations. PHP 8.2 full suite: 1200 tests / 12267 assertions, no failures/errors, known 1 warning, 8 PHPUnit deprecations and 1 Writer skip because that container lacks soffice. Host Writer evidence is separate and complete. Consumer tests: 20 / 66 assertions on PHP 8.2/8.5. Lint, Composer validation, diff checks and strict docs build are included in the preflight; the existing consumer unspecified-license warning is retained.

The previously characterized native-User-Field/nested-Section identity failure remains open. This selected CV uses fields in header frames and rich plain timeline content, so that unsupported combination is not required here. No broad support for that combination is implied.

Headless Writer open/save/render is complete; an interactive editing gate is still required. Final visual acceptance belongs to the owner. Existing repository portrait/footer provenance is reused for internal review, without granting new distribution rights. No commit, push, PR, merge, release or deployment is authorized. After the remaining gates: consolidate P2 and prepare a separately approved publication/review toward develop, never master.


## Owner visual approval — 2026-11-25

Owner explicitly confirmed: “Ja absolut, visuelle Zustimmung erteilt. Und das ist schon ein Premium Template.” The presented S01b-like H10 composition is visually APPROVED WITH OPEN MINOR CORRECTION: alignment/spacing of “Englisch · B2” beside the sidebar partial ring. This supersedes earlier OPEN owner-visual status for the presented composition, but does not assert that the correction has already been implemented or accepted. Interactive Writer editing remains OPEN. H10 overall closeout remains pending these remaining checks. No commit, push, merge or deployment is authorized by this visual approval.


## Caption correction and folded-heading variant — 2026-11-25

Owner explicitly requested the English/B2 correction and one alternate heading design. New review artifacts are isolated in artifacts/review-v2/band and artifacts/review-v2/fold; the visually approved original files are preserved. The first attempts to style the caption inside drawing text still fell back to a black serif font. A common named style alone did not resolve that Writer drawing-text path. The final caption is native Writer paragraph text in a small explicit auto-growing frame, with the graphical ring separately paragraph-anchored beside it. This preserves color/font, left alignment and native editability without broadening the engine. The verifier now checks the caption's actual PDF font/color and raster alignment with the ring, not just presence of the text.

The folded variant reuses exact accepted H09-B artwork, with its three existing graphic styles authored into a NEW blank input seed before engine construction. No generated output XML is patched. Headings remain single native body text:h titles, with textless decorative groups. AuthoredHeadingDecoration gained an internal optional instance-name prefix, preserving its previous default behavior while giving repeated fold/group elements unique names. No arbitrary path API or engine source change was introduced.

Reproduce with an explicit new output directory and heading selector:

    ODT_ENGINE_P2_ROOT=/srv/projects/odt-template-engine-p2 php dev/generate-h10.php /absolute/new/output band
    H10_PROOF_ROOT=/absolute/new/output python3 dev/render-h10.py
    H10_PROOF_ROOT=/absolute/new/output python3 dev/verify-h10.py

Use fold instead of band for the alternative; dev/heading-seed.php supplies its matching fixed artwork styles. Existing first-page contact raster normalization remains documented separately. New visual review is open for the requested correction and the alternate heading style; the original approval is not silently extended to the new variant. Interactive Writer editing remains open.

Future product positioning is recorded in /srv/projects/cv-generator-pro-p2/docs/PREMIUM-TEMPLATE-NOTES.md: the owner wants this design offered as premium in a later version, with possible feature monetization considered separately. No price, paywall, licensing change or commercial release was implemented. No commit/push/merge/deployment is authorized.


### Review-v2 final construction and verification

The final language caption is a normal Writer paragraph in an explicit 0.9cm frame; only the ring is graphical. This avoids the failed drawing-text styling path. PDF word metadata confirms white Liberation Sans, and raster probes check vertical alignment with the ring. Checking the entire PDF font list alone was too broad (unused/fallback font resources can still exist), so the actual caption font is checked.

For the folded variant, initial use of the reference's 0.45cm band offset clipped text at some flowing heading positions. A global shift alone was also insufficient while paragraph top margins collapsed differently. The final bounded recipe translates the fixed artwork by -0.4cm (contour/size unchanged), sets heading top margin to zero and uses a small native keep-with-next spacer paragraph. This makes placement consistent on first and continuation pages. The optional translation and name prefix are internal consumer asset-instance controls; previous defaults are unchanged. New probes check blue backing at upper/lower text rows, including whitespace around narrow glyphs such as ampersands.

Both variants pass the five-case/nine-page H10 verification (18 pages total). The earlier fully pixel-identical claim is not extended: the already documented bounded first-page contact-symbol raster normalization remains explicit. Consumer tests: 22 tests / 74 assertions; engine source unchanged. Original approved H10/I06 outputs remain intact. The requested correction is implemented and the alternative is ready for owner review; interactive Writer editing remains open. Source generator/render/verify accept explicit new output directories to preserve prior evidence.


## Final owner visual acceptance of both variants — 2026-11-25

Owner: “Okay, beide Exemplare sind abgenommen.” Both review-v2 band and fold variants are visually ACCEPTED, including the English/B2 caption correction. This supersedes earlier OPEN variant-review and minor-caption-correction status. Interactive Writer editing remains a separate OPEN gate; owner visual approval is not represented as that edit test. Premium positioning remains planned for a later version. The existing nested-Section/User-Field failure remains characterized and unresolved. Next: interactive edit evidence, consolidate the P2 completion/remaining-issues records, then prepare bounded commits and publication only after explicit Git authorization. No commit/push/merge/deployment is authorized by visual acceptance.

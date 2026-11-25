# P2-I06 — Consumer composition implementation

## Aktueller Abschluss — 25.11.2026

P2-I01–I07 einschließlich H10 sind im vereinbarten Umfang umgesetzt und abgenommen. Beide Review-v2-Templates sind visuell akzeptiert; der Owner bestätigt die interaktive Writer-Prüfung. Versionierung/Veröffentlichung bleiben separat offen. Verbindlicher Status: [P2-Implementierungsabschluss](P2-CLOSEOUT-2026-11-25.md) und [maschinenlesbarer Stand](P2-CLOSEOUT-STATE.json). Offene Folgepunkte werden dort ausdrücklich aufgeführt.

### Historische Planung und Fortschreibung

Die nachfolgenden älteren Status- und Arbeitsaufträge sind historische Quellen; aktuelle Arbeit wird vom oben verlinkten Abschluss aus gewählt.


2026-11-25. Status: implemented and automatically verified; owner visual acceptance and interactive Writer editing remain OPEN. No Git publication authorized.

## Location and architecture

The consumer implementation is isolated at `/srv/projects/cv-generator-pro-p2`, branch `implementation/p2-consumer-composition`, base `cad1e1c8c42329ef52c92cb67f60c06e549ba3fb`. The original CV Generator Pro worktree and pending research are preserved. Internal `CvGeneratorPro\Composition` classes assemble existing native engine elements: ContactBindings, ContactItem/Block/Strip, TagCloud, Timeline and DecoratedHeading. No engine source changes or new public engine API were needed. All 177 engine source hashes match the I06 start snapshot.

The consumer's published Composer requirement still points at its historical metadata branch. The explicit development bootstrap requires `ODT_ENGINE_P2_ROOT` and checks P2 capabilities. This bridges to the pending I01b–I05 implementation in the P2 worktree; it is not a published dependency upgrade. No VTD-00/applicant schema, UI, import path, old CAP acceptance or master branch was changed.

The accepted H07 frame composition supersedes historical E005 table candidates. Contacts contain native User Fields outside graphics groups, optional complete row omission and independently chosen SVGs. Typography, colors, dimensions, spacing and labels remain consumer inputs. Horizontal composition supports 1–3 entries, above/left icons, left/center/right alignment and explicit narrower Section regions.

Tags reuse rectangular text frames or rounded native shapes with inline auto-grow and native paragraph flow. Semantic heading text occurs once as a body heading with textless background decoration. This bounded programmatic heading variant is the existing rectangular band; accepted B/C contour artwork remains an authored-template choice, not a new arbitrary-path API.

Timeline markers derive their axis from explicit region width and column weights. An initial whole-station multi-page section caused Writer to refill the other column on the next page. The corrected consumer uses native two-column heading/detail row Sections, keeps each detail paragraph together and lets Writer paginate. No tables, fixed station heights or PHP pagination calculations were introduced. The zero paragraph-gap default produces a continuous separator; configurable paragraph gaps may visibly separate line segments.

## Evidence

Consumer scripts and detailed limits: `/srv/projects/cv-generator-pro-p2/research/p2-i06/README.md`. Regenerate with `dev/generate-p2.php`, `dev/render-p2.py`, `dev/verify-p2.py`; provide the explicit engine worktree path. Generated files are under the consumer's ignored `research/p2-i06/artifacts/` directory and are not staged. An accepted research seed is blanked before engine use; generated composition output is never XML-patched.

- 16 native ODT candidates: compact/sidebar/editorial contacts, long/one/empty rows, horizontal above/inline and 1/2/3 entries, a 14cm inset strip, rectangular/rounded tags, three timeline variants (18cm/12cm, linear/alternating), semantic headings.
- 21 PDF pages, each Writer-roundtripped. Exact PNG equality before/after roundtrip for every page.
- Native fields/declarations/references and heading outlines verified, complete PDF text, page bounds, timeline description side, absence of tables/classic contact syntax and repeat-save XML checked.
- 10 focused PHPUnit tests / 25 assertions pass on PHP 8.5.4 and PHP 8.2.36. PHP lint and diff checks pass. Composer validates with the existing unspecified-license warning.
- Full engine tests from I05 remain the latest engine regression run; unchanged engine source does not justify rerunning the entire suite for this consumer-only slice.

No automated check is represented as owner acceptance. Programmatic image inspection covered contacts, both tag carriers, corrected heading bands and timeline continuation. Interactive editing remains separate.

## Boundaries and next gate

Frames must fit their intended region; a contact block is not an unbounded multi-page story. Atomic tags and single timeline detail paragraphs must individually fit the region/page. There is no silent shrinking or arbitrary layout solver. The detailed consumer report records these limits and the two corrected failed approaches. Stage-specific rendering checks are still required for real template widths/fonts.

Owner visual review is next. After I06 acceptance, P2-I07/H10 assembles the selected S01b/LOCAL-039 combination and performs the combined preflight, interactive Writer check and separate owner review. Publishing engine dependencies, commit, push, PR and merge remain separate steps.


## Owner review corrections — 2026-11-25

The first submission is superseded for inline strips, station spacing and heading variants. The reviewer identified a real icon/value gap in strip-two-inset: a paragraph-anchored icon remained at the field origin while text was right aligned. Inline strips now place icon and native field in the same text flow, with explicit baseline alignment. All strip columns use the same inline carrier to prevent a mixed-anchor row from changing its baseline after Writer roundtrip. Gap lengths follow Writer's twip/0.001cm storage resolution. Constructor anchor metadata is also explicit so legacy image-style registration does not confuse paragraph and as-char requirements. No engine source was changed.

Timeline stationGap defaults to 0.35cm (approximately one body line), is independent of paragraphGap and can be set to 0cm. A native two-column spacer row between stations keeps the separator continuous. No spacer is inserted before the first or after the last station.

Heading variants A/B/C are now included as heading-variants.odt using the exact accepted contours and matching SemanticHeading template styles. AuthoredHeadingDecoration imports only three fixed, versioned, textless XML assets from resources/headings; there is no arbitrary-path/public XML import API. This is a bounded authored-template integration, not a standalone resizable shape factory. All three titles remain native semantic body headings, one occurrence each. Native geometry is compared structurally with the accepted H09 reference. The earlier rectangle-only scope was insufficient for this review and is superseded by this addition.

Regenerate review packages with python3 dev/package-review.py. P2-I06-Korrekturen.pdf and P2-I06-Korrekturen-ODT.zip focus on the changed strip, both timeline directions and all heading variants. Final owner acceptance remains OPEN.


Review corrections verified: 17 native documents / 22 roundtrip pages, exact PNG equality on all pages; 12 tests / 31 assertions on PHP 8.2 and 8.5. Owner acceptance remains open.


## RichText timeline extension — 2026-11-25

Owner requested existing RichText content on both timeline sides. Implemented physical left/right string/Paragraph/RichText input and compatible rich date/title/details. Caller paragraph styles, named styles, span formatting and hyperlinks are preserved through a small internal inherited-flow-style adapter (TimelineParagraph). Existing string input remains supported; station spacing and native paragraph-row pagination are retained. Details and limits: /srv/projects/cv-generator-pro-p2/docs/TIMELINE-COMPOSITION.md.

20 native documents / 26 roundtrip pages now pass complete-text/structure/style checks and exact PNG equality. Focused tests cover rich physical/semantic inputs, named style inheritance, caller-input preservation, empty rich sides, hyperlinks and repeat-save, ambiguous input and unsupported RichTable blocks. The minimal nested-Section/native-User-Field identity failure is characterized separately on PHP 8.2/8.5, without Timeline; it is an existing engine limitation, not a new supported capability. No engine source changed. Visual owner review remains OPEN.


## Owner visual acceptance — 2026-11-25

Owner: “Sehr schön, abgenommen, wie geht es weiter?” P2-I06 including corrected inline strip, station spacing, A/B/C authored headings and the RichText timeline extension is visually ACCEPTED. This supersedes earlier OPEN visual-owner status in this document. Evidence remains 20 native candidates / 26 Writer-roundtrip pages and focused tests on PHP 8.2/8.5. Interactive Writer editing is a separate gate and remains OPEN. The characterized nested-Section/native-field identity issue is not resolved by visual acceptance. No commit/push/merge/deployment is authorized. Next: P2-I07/H10 under CC08, checking whether the known section/field issue affects the chosen integration before claiming a completed overall preflight.

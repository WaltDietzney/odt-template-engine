# H02-A — Native page-style property analysis

Status: analysis started, 2026-11-18. H02 A–D sequence approved by project owner; no API contract or implementation approved by this document.
Baseline: Forgejo develop bd47e2d, branch feature/h02-page-frame-composition.

## Purpose and scope

Support generic template construction, including a future external Template Builder. The builder creates native editable ODT templates; the editor/generator consumes their structure and formatting. Neither application nor PHP pagination belongs in the engine. H02-D uses a complex document to expose concrete integration gaps rather than repeating accepted foundational experiments.

## Accepted evidence

- PAGE_STYLE_AUTHORING_01.md and PageStyleAuthoring01Test: programmatic page layout/master creation, successor, assignment, structured header/footer content.
- FRAME_LAYOUT_01_CLOSEOUT.md: shared DrawingLayout and frame carrier semantics; visual body/header image evidence.
- S01B_PAGE_FLOW_REGRESSION.md: three-page Writer visual regression, native flow and paragraph keep chains.
- H01_SECTION_AUTHORING_CLOSEOUT.md and SECTION_AUTHORING_02_CONTRACT.md: native generated Sections and section styles.
- cv-handoff/h02-research/README.md: pinned CV research, E001 and LOCAL-039. Owner clarified E001 means a native Writer frame with independently formatted contents, not a drawing text-field substitute. DrawTextBox already produces draw:frame/draw:text-box. No new frame class implied.

## Native ownership (ODF 1.2 Part 1)

Sections 16.5–16.13, 17.2–17.5, 20.205.2, 20.256, 20.397.2. Page layout lives in styles.xml automatic styles; master page in master styles. Page layout properties own page geometry, background, borders, padding, columns and footnote separator. Header/footer style children each own header-footer-properties. Master page owns actual header/footer content, identity and successor; paragraph style references master page. Left-page content variants are distinct native children; style:display is available. Do not infer Writer UI sharing semantics without fixture evidence.

| Property | Native owner / representation | Current API / evidence |
| --- | --- | --- |
| Size, margins, orientation | page-layout-properties, fo:page-width/height, fo:margin-*, style:print-orientation | existing define/set |
| Page background | fo:background-color; style:background-image child | not exposed; Writer fill representation to characterize |
| Page border/padding | fo:border*, fo:padding*, style:border-line-width* | not exposed; Writer fixture needed |
| Page columns | style:columns child | not exposed; reuse H01 width semantics only after page-specific evidence |
| Page usage | style:page-usage on page-layout | not exposed; left/right/mirrored evidence needed |
| Header/footer fixed height | svg:height on header-footer-properties | not exposed; S01b first header |
| Header/footer minimum height | fo:min-height | not exposed; S01b continuation and PAGE-01-02 |
| Header/footer gap | header fo:margin-bottom; footer fo:margin-top | not exposed; both fixtures |
| Header/footer lateral margins | fo:margin-left/right | not exposed; S01b |
| Dynamic spacing | style:dynamic-spacing boolean | not exposed; S01b false; distinct from minimum height |
| Header/footer background/border/padding | fo:background-color, fo:border*, fo:padding*, background-image child | not exposed; transparent and draw:fill=none observed; additional Writer evidence needed |
| Header/footer content | style:header/footer under master-page | definePageStyle OdtElement; no dedicated replacement or inspection API |
| Successor/assignment | master next-style-name / paragraph master-page-name | existing |

## Inspected native fixtures

PAGE-01-02-layout-master-page.odt: Standard -> Mpm1, landscape 29.7 x 21.001cm, header/footer min-height 0cm, header bottom and footer top gap 0.499cm, transparent background and draw:fill none. Both master content regions exist.

S01b template: First_20_Page -> Mpm2 -> CV_20_Continuation -> Mpm3; both layouts 21.001 x 29.7cm, top/left 0cm, bottom/right 2cm. First header fixed svg:height 0.6cm, gap 0.499cm; continuation min-height 1.6cm, gap 1.499cm. Both lateral margins 0cm, dynamic-spacing false, transparent background, draw:fill none. Both headers contain paragraph-carried native draw:frame/draw:text-box, 7.999 x 29.7cm. First anchor paragraph, continuation char; both graphic styles position relative to paragraph, not page. Frame names sidebar and Rahmen2, contents placeholders CVSidebarPage1 and CVSidebarPage2. Full-height frame does not imply full-height header. E001 page-relative positioning remains a distinct requirement. Writer layout values show unit quantization; do not require byte-identical roundtrip lengths.

## Current code boundaries and risks

PageLayoutOdtTemplate is compatibility facade. PageLayoutManager defines page-layout with empty header/footer styles and only seven geometry keys; setLayout resolves master to referenced layout. PageStyleManager defines master and materializes OdtElement content/styles/resources in styles.xml; no dedicated update of existing header/footer or inspection. DocumentStyles currently owns paragraph/Section styles, not page styles. Preserve existing facade and ownership; no new manager assumed.

Shared page-layout identity: setLayout mutates referenced layout, potentially affecting multiple masters. Must explicitly decide whether new APIs target layout identity or master-specific copy; do not silently change legacy behavior. Existing setter applies options sequentially and ignores unknown keys; new validation/atomicity semantics need contract, not incidental refactor. Header/footer content activation is separate from geometry; empty style nodes alone do not prove enabled content. Fixed/minimum height conflict and transition behavior need characterization. Assigning a master to a paragraph style can introduce page breaks (ODF 16.9); use a dedicated start paragraph, not repeated station styles.

## Remaining A work before contract B

Targeted Writer-authored fixture(s) for fixed/automatic height, dynamic spacing true/false, left/right margins, background/border/padding, page usage and header/footer sharing/activation; page background fill and page columns. Inspect actual XML, distinguish standard ODF and Writer extensions, verify save/reopen and render where behavior matters. Prioritize header/footer geometry and page background/borders; image resources, columns and variants require explicit scope decisions, not automatic implementation. Existing evidence should be reused; no need to repeat S01b or completed frame experiments. No production code changes or new public API yet.

## Owner-provided complementary Writer references (2026-11-18)

Both original ODT packages were inspected directly in the conversation sandbox; ZIP integrity passed. LibreOffice 24.2.7.2, no embedded Pictures resources or author fields in meta.xml. Public fixture suitability still requires review. Proposed fixture names PAGE-STYLE-AUTHORING-02-reference.odt (original seitentemplate.odt) and PAGE-STYLE-AUTHORING-02-reference-solid.odt. Binary placement on Patil pending owner copy; do not claim fixtures are already present.

Both use Mpm2, A4, 2cm margins, master Untersuchung / Untersuchung2, header and footer with empty native paragraphs. Existing built-in master succession is accepted evidence, not a new research task.

| Property | Gradient reference | Solid reference |
| --- | --- | --- |
| Page fill | fo:background-color #47140f, draw:fill gradient, draw:fill-gradient-name Mahogany, draw:gradient-step-count 0 | fo:background-color #7a2f02, draw:fill solid, draw:fill-color #7a2f02 |
| Master drawing style | draw:style-name Mdp1, family drawing-page, gradient reference | Mdp1, solid #7a2f02, draw:background-size full |
| Page border/padding | absent | 4.51pt solid, top/bottom #fb7e34, left/right #e134fb, padding 0.049cm |
| Columns | 2766:3706:3165, explicit half-gap indents 0.249cm | 21845 each, gap 0.497cm, explicit half-gap indents 0.248cm |
| Separator | absent | 0.273cm solid #89fd78, height 100% |
| Header | svg:height 1.6cm, dynamic-spacing true, bottom gap 0.499cm, padding 0.4cm, asymmetric borders, double bottom with border-line-width-bottom 0.026cm 0.004cm 0.053cm, shadow #808080 -0.176cm 0.176cm, transparent fill | fo:min-height 0.998cm, dynamic-spacing false, left/right margins 1/2cm, bottom gap 0.499cm, solid #000077 |
| Footer | min-height 0.998cm, top gap 0.499cm, padding 0.049cm, asymmetric border, transparent fill | min-height 0.998cm, top gap 0.499cm, solid #4efb34 |

Header/footer solid fill uses fo:background-color plus draw:fill and draw:fill-color directly on header-footer-properties. Page fill is duplicated in page-layout-properties and referenced drawing-page style; characterize synchronized authoring/mutation in B/C, not assume one representation suffices. Gradient resource authoring and bitmap backgrounds are distinct from solid fill; explicit scope decision required. Page column weights can reuse the H01 normalizer; no assumption that Section balancing or all Section options apply to pages. Solid Writer fixture contains both gap and explicit indents; preserve authored representation, define generated representation in B. No further owner-authored fixture required for the agreed core; optional image fills and sharing variants deferred unless explicitly needed. No new visual or roundtrip acceptance claimed for generated properties yet.

## Fixture placement verified / A core closeout

Owner copied both files into tests/fixtures/libreoffice-reference/odt/ on the regular H02 checkout. ZIP integrity verified on Patil; no Pictures resources. SHA256:

- PAGE-STYLE-AUTHORING-02-reference.odt: 6afb98122a6c97454ac83eb0bff95ba04f33c98aa72c402e1aa7d22ac3dc6805
- PAGE-STYLE-AUTHORING-02-reference-solid.odt: 4ea16c97904044ed6a8406c0bec8521a6760922894de3e68cbf88a09fd6a6219

Correction to earlier table: gradient master Untersuchung references Mdp2, not Mdp1. Solid Untersuchung2 references Mdp1. Both drawing-page styles have draw:background-size full. Both files contain LibreOffice lock files alongside the originals; exclude locks from version control. Core property evidence complete; optional background image and sharing variants remain outside the agreed core. Generated Writer roundtrip/visual proof belongs to C/D. No public mirror approval implied.

## Supplemental bitmap evidence (2026-11-18)

Owner-provided PAGE-STYLE-AUTHORING-02-reference-bitmap.odt verified in tests/fixtures/libreoffice-reference/odt/. SHA256 67f91977b2e8f312d7329217e741dff57469b1b4da73101fa799cc31b7dc77b3; ZIP integrity passes. Writer 24.2.7.2, ODF 1.3, no author fields observed. Three distinct JPEG assets, all manifest entries image/jpeg. Master Untersuchung2 -> Mpm2 and Mdp1. Three draw:fill-image declarations in office:styles: LogoBewerbungstools (page), Lawn (header), Bathroom_20_Tiles (footer). All use xlink:type simple, show embed, actuate onLoad, package Pictures href. Page and header use style:repeat repeat, zero cm image dimensions, centered reference point, zero offsets; footer uses stretch, no explicit dimensions. All three property groups additionally contain style:background-image with the same package href as the named declaration; footer child explicitly has style:repeat stretch. Page drawing-page properties duplicate bitmap fill attributes, not background-image child, and retain draw:background-size full. No fo:background-color in bitmap property groups; draw:fill-color retains previous solid color. This confirms existing FillImageRequirement declaration compatibility but requires consumer-specific projection and synchronized cleanup on fill transitions. Three different assets do not prove same-image deduplication. Owner requests no further exhaustive bitmap option research. Public mirror blocked pending rights review (Bewerbungstools logo and Writer bundled textures); preserve original privately, use synthetic assets for public regression if needed.

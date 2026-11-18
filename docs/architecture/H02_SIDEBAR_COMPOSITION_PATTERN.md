# H02 — Page-height sidebar composition pattern

Date: 2026-11-18. Status: authored Writer reference established; small
programmatic header boxes verified; single- and two-page full-height programmatic composition verified and owner accepted; final H02 D open.
Baseline: bfe250fbc0fc6f2972f007e1216c3f86d09b62d4.
Source: samples/templates/template_S01b_cv_structured.odt, styles.xml and
content.xml; H02 D diagnostic files in tmp/h02-d (local only).
Related: H02_PAGE_STYLE_AUTHORING_CHANGE_CONTRACT.md, FRAME_LAYOUT_01_CLOSEOUT.md,
H01_SECTION_AUTHORING_CLOSEOUT.md, S01B_PAGE_FLOW_REGRESSION.md.

## Recipe and ownership

A page-height sidebar is a floating native draw:frame containing draw:text-box,
anchored in a master-page header. The header is an anchor carrier, not the
sidebar's layout box. Its height must not be inferred from the frame height.
The main content is an independent flowing text:section with a left indent
that clears the sidebar. Writer owns pagination. Use distinct first and
continuation masters and their next-style relationship. Do not substitute a
large page margin for the Section wrapper or simulate pagination in PHP.

## Exact authored S01b evidence

| Property | First_20_Page | CV_20_Continuation |
|---|---|---|
| Page width / height | 21.001 / 29.7 cm | same |
| Page top / left margins | 0 / 0 cm | same |
| Page bottom / right margins | 2 / 2 cm | same |
| Header height | svg:height=0.6cm | fo:min-height=1.6cm |
| Header bottom spacing | 0.499 cm | 1.499 cm |
| Header dynamic spacing | false | false |
| Header paragraph style | Header | Header |
| Frame anchor | paragraph | char |
| Frame width / height | 7.999 / 29.7 cm | same |
| Frame x / y | -0.115 / 0 cm | -0.118 / 0 cm |
| Frame horizontal / vertical relation | paragraph / paragraph | same |
| Frame horizontal / vertical position | from-left / from-top | same |
| Graphic parent | Frame | Frame |
| Effective wrap (inherited) | parallel | parallel |
| draw:wrap-influence-on-position | once-concurrent | same |
| loext:allow-overlap | true | true |

Mfr1/Mfr2 inherit Frame's wrap and 0.201 cm margins. Both use solid
#000077, no border, left/right padding 0.499 cm, bottom padding 1 cm;
top padding 5.001 cm first / 3 cm continuation. These are reference values,
not universal design defaults. The overlap property is a Writer extension,
not a portable ODF guarantee. First master points to CV_20_Continuation.
The continuation master has no explicit next-style-name.

## Engine mapping and diagnostic findings

Use definePageLayout for region geometry, definePageStyle/updatePageStyle
for header content, DrawTextBox::setFrameLayout for anchor/size/position/wrap,
setBackground for fill, assignPageStyle for the actual first body paragraph,
setNextPageStyle for succession, DocumentStyles::defineSection and
SectionElement for main-flow geometry. Verify exact emitted properties;
do not assume all authored Writer attributes have a public API.

DrawTextBox supplied directly as header content emits text:p > draw:frame >
draw:text-box. Do not add an unnecessary enclosing Paragraph. In the small
left/right tests (5 x 2 cm, solid fill, 3 cm fixed header, paragraph anchor,
run-through), both frames and body text were extracted from Writer PDFs.
The initial two-page output was traced to an empty Standard paragraph
preceding HeaderStart, which activates HeaderTest only on the second page.
Removing only that empty paragraph in diagnostic ODT copies produced one
page in both tests. This is a diagnostic XML edit, not an approved engine
implementation. Assigning Standard globally also caused unwanted page
transitions in the earlier test; use a dedicated start paragraph.

Earlier full-height prototypes (15 and 8 pages) failed; sidebar text absent
from PDF extraction. Do not infer a single root cause from these failures.
Small boxes do not prove full-height floating behavior or visual acceptance.

## Completed geometry gate and remaining acceptance

Generate one page from a blank shell using existing APIs: 0 cm top/left
margins, fixed 0.6 cm header, 8 x 29.7 cm frame, paragraph-relative
position, parallel wrap, dedicated first paragraph, Section left indent
8.5 cm (test parameter, not measured reference value). Verify XML, Writer
PDF and visual appearance: visible full-height sidebar, no overlap with
main text, no blank page, header does not consume page height.
Then add continuation master and natural multi-page flow, shared fills,
save/reopen and Writer roundtrip per H02 D. Record any missing API property
as a concrete gap before changing production code.

## Skill Builder rule

Select this recipe when the visual design requires a repeated or
page-specific sidebar with independent flowing content. Parameterize
page geometry, sidebar side/width/height, anchor, wrap, region geometry,
first/continuation content and main Section clearance independently.
Do not confuse the sidebar's height with header height. A recipe is
eligible for automatic generation only after the relevant engine and
visual gates pass. No new public API or skill schema is approved here.

## 2026-11-18 — successful full-height composition

Owner visually accepted full-height.odt (one page) and the subsequent
full-height-multipage.odt (two pages). Local scripts and PDF evidence:
tmp/h02-d/full-height.php and full-height-multipage.php, corresponding
ODT/PDF files. No production changes or post-save XML edits.
Both use the blank C06 shell, direct DrawTextBox header content, 8 x
29.7 cm frames, paragraph-relative offsets 0 cm, parallel wrap,
setAllowOverlap(true), solid fill and MainFlow Section indent 8.5 cm.
First header fixed 0.6 cm / spacing 0.499 cm; continuation min-height
1.6 cm / spacing 1.499 cm, char anchor. FirstPage links to NextPage.
12 body paragraphs fit on one page; 100 flow across exactly two pages.
PDF extraction confirms both sidebar labels and last body paragraph.
The actual shell start paragraph uses Standard assigned to FirstPage;
all main body paragraphs explicitly use MainBody, avoiding repeated
master activation. This bounded approach works; do not assign Standard
to every body paragraph. Dedicated start-style authoring remains preferable
where available. Full H02 D fill/lifecycle/public sample gates remain open.

## S01b reconstruction and native fields

Owner requests a programmatic S01b-like CV with named Experience/JobSection,
ActivitySection, Education/EducationSection and AdditionalQualifications/
QualificationSection prototypes, and native User Fields for contact data.
Existing S01b sample uses classic placeholders and literal sidebar text.
setUserField binds existing string declarations/references only; no public
declaration/reference authoring API exists. See FUTURE_DEVELOPMENT.md,
ODT construction candidate String User Field creation. This is a concrete
use case for that bounded candidate, not approval of a new API or expansion
of H02. Preserve the distinction between constructing a reusable template
and binding applicant data. Do not silently replace requested native fields
with literals in the final CV template.

## S01b programmatic reconstruction — 2026-11-18

Local evidence: tmp/h02-d/s01b-author.php and s01b-fill.php generate
S01b-generated-template.odt from the C06 shell, reopen it, bind classic
variables and instantiate nested job/activity, education and qualification
sections. Writer exports a two-page CV. Owner accepted the composition
with photo and name placement requiring refinement. Native User Field
authoring explicitly deferred; classic variables approved for this slice.

V2 preserves the original prototype and page geometry. Separate photo
paragraph with centered inline image, proportional 3.4 x 4.08 cm size
for the 375 x 450 source, and explicit name spacing/size. Scripts:
s01b-author-v2.php and s01b-fill-v2.php; outputs S01b-generated-v2-template.odt,
S01b-generated-v2.odt and PDF. Original S01b uses a centered paragraph-
anchored photo inside a separate frame; V2 uses an inline composition
rather than reproducing that frame. V2 superseded by accepted V3.
No production changes. See V3 acceptance below.

## V3 acceptance and public C08 — 2026-11-18

Owner explicitly accepted V3 visually. Both header frames reference one
CVSidebarGradient; both footer regions reference one CVFooterBitmap
with 8.5 cm left margin. Writer ODT roundtrip and PDF export retain two
pages, both footer texts and shared fill definitions. Writer renames
package image paths consistently. Public C08 reproduces V3 from the C06
shell, using the existing public photo and synthetic footer texture.
No production API changes. Public sample and regression checks are
the remaining technical closeout; no merge or release authorized.

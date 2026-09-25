# Sample Guide

The `samples/` directory is both a runnable example collection and a map of the engine's public capabilities.

The canonical L/C/B/S path is the complete public learning path. Current entry
points, templates/outputs, ownership descriptions, and package status are
recorded in the [sample registry](../../samples/sample-registry.php).
Historical numbered samples are maintained only as internal regression
fixtures outside the public `samples/` tree.

## Canonical Learn path

The twelve canonical samples complete the Learn path. L01–L03
keep document structure in LibreOffice and use PHP to supply data. L04–L06
retain a LibreOffice-authored shell/insertion point while PHP generates native
ODT subtrees or image resources where that is the appropriate owner. L07 adds
native generated tables; L08 adapts substantial controlled HTML into editable
ODT elements. L09–L10 address native Writer-authored structures; L11 inspects
the original template contract without producing an output document; L12
populates a Writer-authored named table while preserving Writer ownership.

| ID | Sample | Learn about |
| --- | --- | --- |
| [L01](../../samples/sample_L01_variables_filters.php) | Variables & Filters | [`assign()` and filters](../template-language/variables-and-filters.md), including ODT line breaks |
| [L02](../../samples/sample_L02_conditions.php) | Conditions | [`if`, `elseif`, `else`, and `ifnot`](../template-language/conditions-and-loops.md) |
| [L03](../../samples/sample_L03_repeating_content.php) | Repeating Content | [`assignRepeating()` and `foreach`](../template-language/conditions-and-loops.md#repeating-blocks) |
| [L04](../../samples/sample_L04_rich_content.php) | Rich Content | [RichText and Paragraph](../rich-documents/richtext-and-paragraphs.md): PHP-generated native paragraphs and inline content |
| [L05](../../samples/sample_L05_lists.php) | Lists | [Native ODT lists](../rich-documents/lists.md), with numbered steps and a nested bullet-list hierarchy |
| [L06](../../samples/sample_L06_images.php) | Images | [Image ownership choices](../rich-documents/images.md): replace a template frame or insert an ImageElement |
| [L07](../../samples/sample_L07_tables.php) | Tables | [RichTable and RichTableCell](../rich-documents/tables.md): PHP-generated native editable table structure |
| [L08](../../samples/sample_L08_html_import.php) | HTML Import | [HtmlImporter](../advanced/html-import.md): substantial controlled HTML translated into native ODT elements |
| [L09](../../samples/sample_L09_native_objects.php) | Native Objects | [Addressable native structures](../rich-documents/addressable-document.md): bounded bookmark/Section mutation plus typed table/frame addressing |
| [L10](../../samples/sample_L10_writer_user_fields.php) | Writer User Fields | [String User Field binding](../advanced/template-inspection.md#native-writer-user-fields): bind a template-owned document-global field |
| [L11](../../samples/sample_L11_template_inspection.php) | Template Inspection | [TemplateContract](../advanced/template-inspection.md): source-oriented bindings, controls, native objects, dependencies and capabilities |
| [L12](../../samples/sample_L12_writer_table_population.php) | Writer Table Population | Populate a Writer-authored named table while preserving native headers, kept rows, structure and formatting |

## Canonical capability samples

The capability layer adds native ODF behavior above the Learn path.

| ID | Sample | Additional capability |
| --- | --- | --- |
| [C01](../../samples/sample_C01_page_flow_layout.php) | Page & Flow Layout | Paragraph-flow intent with Writer-owned page/master-page structure; Writer computes pagination |
| [C02](../../samples/sample_C02_advanced_table_layout.php) | Advanced Table Layout | Whole-table geometry, relative column proportions, row geometry, and separate vertical/horizontal alignment |
| [C03](../../samples/sample_C03_frame_layout.php) | Frame Layout | Shared frame-layout semantics for floating and as-character text/image elements |
| [C04](../../samples/sample_C04_declarative_structured_collections.php) | Declarative Structured Collections | Direct Phase-D execution of Writer-authored nested Sections with template-shaped data |
| [C05](../../samples/sample_C05_mapping_automation.php) | Mapping & Automation | Explicit mapping, concrete preflight, and common atomic Phase-E execution |

## Canonical builder samples

Builder samples demonstrate programmatic construction of substantial editable
ODT documents or template candidates with the structured PHP API. They differ
from Learn samples, which teach focused vocabulary; Capability samples, which
demonstrate larger engine/ODF behaviors; and Professional Showcases, which
demonstrate final composition with stable visual ownership in LibreOffice.

| ID | Sample | Builder lesson |
| --- | --- | --- |
| [B01](../../samples/sample_B01_invoice_template_builder.php) | Invoice Template Builder | PHP constructs a credible editable invoice/template candidate with semantic paragraph styles, native tab stops, deliberate RichTable composition, and visible template syntax for later Writer refinement |
| [B02](../../samples/sample_B02_report_builder.php) | Professional Report Builder | PHP composes a fictional multi-page annual report from existing RichText, tables, images, page-flow, and frame-layout capabilities |

A Builder sample is a distinct ownership lesson rather than a promise of a
matching showcase. B01 and B02 demonstrate substantial PHP-owned construction;
S01b and S03 demonstrate the current professional Writer/template-owned
showcase path.


## Samples as architectural teaching material

The public sample suite is designed for both human readers and AI coding
agents. Samples should not merely prove that an API call works; they should
make the engine's intended solution patterns visible. Learn samples introduce
focused vocabulary, capability samples demonstrate larger behaviors, Builder
samples demonstrate structured programmatic document/template construction,
and professional showcases demonstrate how to compose capabilities into
credible editable documents with the intended LibreOffice/PHP ownership boundary.

For a professional showcase, correctness therefore has four dimensions:
idiomatic public-API usage for developers, a clear ownership boundary for
LibreOffice template authors, professional rendered output for end users, and
code/structure that an AI coding agent can use as reliable architectural
precedent. Existing strong examples should be reused as references rather than
reimplemented in a weaker form.

## Professional showcase samples

| ID | Sample | Ownership lesson |
| --- | --- | --- |
| [S01b](../../samples/sample_S01b_cv_structured.php) | Professional CV · Structured Template | LibreOffice owns the CV page design and native Section structure; PHP supplies collections, replaces the authored image resource, and owns bounded RichText sidebar regions |
| [S03](../../samples/sample_S03_structured_professional_report.php) | Structured Professional Report | One Writer-authored report template supports bounded native-object updates and a second structured composition path while preserving template-owned layout |

C04 and C05 intentionally take different data paths: C04 passes values already
named like the template's dependencies to `executeDeclarative()`; C05 maps
application-shaped names through MappingDefinition, preflights them, and then
uses atomic `automate()`.
L06 intentionally shows two ownership models, not two interchangeable APIs:
`replaceImageByName()` replaces image content at a frame authored in
LibreOffice; `ImageElement` creates a new frame/resource as part of the PHP
generated subtree. `setImage()` remains a placeholder-oriented convenience
path documented with the image APIs, not a third equal model in this sample.

## Migration-era numbered samples

The numbered examples below are historical migration entries, not the
recommended canonical learning path. They remain available while later F
slices account for their remaining behavior; consult the registry for current
migration targets and package status.

### Historical numbered examples

| Sample | Focus | Read it when you need... |
| --- | --- | --- |
| 01 | Simple variables | the smallest placeholder replacement example |
| 02 | Filters | formatting such as upper/lower/date/number behavior |
| 03 | Logic elements | conditions and basic template logic |
| 04 | Metadata | `meta.xml`, save/reload, and metadata display |
| 05 / 05b | Image replacement | images in existing template structures |
| 06 | Image settings | image sizing and placement options |
| 07 | Contact list / paragraphs | generated paragraph-oriented content |
| 08 | HTML import | converting controlled HTML to editable ODT elements |
| 09 | RichText block | composing richer generated sections |
| 10 | Template language | a larger variables/filters/logic example |
| 11 | Table | generated table basics |
| 12 | Advanced table | richer table content and styling |
| 13 | Cell settings | table-cell configuration and style responsibilities |
| 14 | Advanced tabs | paragraph geometry, tabs, margins, and borders |
| 15 | Styled table | a compact styled-table example |
| 16 | Basic tabs | tab-stop-oriented text layout |
| 17 | Text field | text-box / field-related document structure |
| 18 | List styles | native bullet, numbered, and nested lists |
| 19 | HTML table | HTML table import into native ODT table structures |
| 20 | Table ratios | relative native table-column widths |
| 21 | Generated CV profile | a real-world document composed from large PHP-generated regions |
| 22 | Bookmark text replacement | addressing and replacing text in native named bookmarks |
| 23 | Section content replacement | replacing the children of a native named section with structured ODT content |
| 24 | Section image replacement | replacing section content with an image and package resource |
| 25 | Native CV section collections | LibreOffice-authored repeatable sections, nested collections, and scalar binding |
| 28 | Template Contract inspection | historical full contract dump; canonical compact introduction is L11 |
| 29 | Writer User Field binding | historical fixture-dependent entry; the self-contained Composer example is L10 |

The repository also contains additional focused or historical sample scripts outside the numbered sequence. Treat the numbered samples as the primary learning path.

## Run a sample

From the repository root after Composer installation, run a canonical sample:

```bash
php samples/sample_L01_variables_filters.php
```

Generated documents are normally written below:

```text
samples/output/
```

Open the resulting `.odt` file in LibreOffice to verify both structure and visual behavior.

## Visual regression workflow

For changes that affect rendered ODT output, automated PHPUnit and integration
tests are necessary but are not sufficient for visual approval. Use the
repository renderer after generating the ODT through the normal sample or CLI
path:

```bash
tools/visual-regression/render-odt.sh path/to/document.odt
```

The renderer performs:

```text
ODT → LibreOffice (headless) → PDF → pdftoppm → PNG
```

Inspect the actual rendered PNG files before issuing a Visual GO. Visual review
means checking the complete rendered output against the intended sample
semantics and, where available, a known-good baseline—not only confirming that
the feature changed in the current slice is visible. The sample code and its
corresponding LibreOffice template are authoritative evidence for those
semantics. A report, successful XML or ZIP validation, automated tests, or
merely opening or generating the ODT does not substitute for inspection.

Project visual-regression artifacts belong under `tmp/visual-regression/`;
do not use system `/tmp` for them. Visual-regression work must preserve
unrelated local artifacts in the primary checkout and must not clean, reset,
restore, regenerate, or delete them.

If LibreOffice cannot execute in an agent or sandbox environment, report the
visual check as **BLOCKED BY ENVIRONMENT**, not PASS or FAIL. The ODT may then
be rendered from a working local environment with the same repository tool;
the resulting PNG files remain the evidence for visual review.

## Samples and templates belong together

Canonical samples that use a LibreOffice template keep that template in:

```text
samples/templates/
```

For example:

```text
samples/sample_L08_html_import.php
samples/templates/template_L08_html_import.odt
```

Read both sides when learning a feature. The PHP file shows what the application supplies; the ODT template shows what remains the responsibility of LibreOffice.

That distinction is central to the engine's design.

## Learning by feature

For **template language**, start with L01–L03 and the Template Language documentation.

For **programmatic content**, start with L04–L08. Historical specialized examples remain internal regression fixtures and are not part of the public sample path.

For **addressable native ODT structures**, start with [L09 Native Objects](../../samples/sample_L09_native_objects.php) and [Named Sections](../rich-documents/named-sections.md).

For **Writer User Fields**, start with [L10](../../samples/sample_L10_writer_user_fields.php). The canonical L10 template is self-contained and Composer-distributed.

For **template inspection**, start with [L11](../../samples/sample_L11_template_inspection.php).

For **tables**, start with [L07](../../samples/sample_L07_tables.php) for
content and [C02](../../samples/sample_C02_advanced_table_layout.php) for
advanced geometry.

For **HTML**, use [L08](../../samples/sample_L08_html_import.php) for general
and table import.

For **images**, start with [L06](../../samples/sample_L06_images.php).

For **frame layout**, use [C03](../../samples/sample_C03_frame_layout.php).
For integrated **mapping and automation**, use
[C05](../../samples/sample_C05_mapping_automation.php).

## Historical CV architecture evidence

The historical CV experiments demonstrated PHP-owned generated regions and
Writer-owned native Section collections. They are no longer public entry
points. The canonical professional CV is
[S01b](../../samples/sample_S01b_cv_structured.php), which deliberately combines
the established ownership lessons: Writer owns stable page design, Frames,
styles and repeatable Sections; PHP supplies application data and only bounded
generated regions where PHP genuinely owns the structure.

Use [Building Complex Documents](building-complex-documents.md) when studying
the historical generated-region pattern and [Named Sections](../rich-documents/named-sections.md)
for the native structured-template pattern.

## Verification samples

The project also has integration tests that execute important public samples. This is deliberate: samples are user-facing code and should not silently drift away from the public API.

When changing a public sample, run the normal test suite in addition to opening the generated document in LibreOffice.

## A practical rule

Do not copy the largest sample when a smaller one demonstrates the feature you need.

A useful progression is:

```text
small API sample
      ↓
feature guide
      ↓
choose the ownership model
      ↓
L01–L12: focused vocabulary
then C01–C05: capabilities
then B01/B02 or S01b/S03 according to ownership
```

This keeps application rendering code understandable and makes ODT-specific problems much easier to isolate.

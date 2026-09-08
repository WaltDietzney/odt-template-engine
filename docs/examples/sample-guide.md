# Sample Guide

The `samples/` directory is both a runnable example collection and a map of the engine's public capabilities.

Start with the smallest sample that demonstrates the feature you need. The later samples deliberately combine more of the engine and are better suited to architectural study than first contact.

## Recommended path

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

The repository also contains additional focused or historical sample scripts outside the numbered sequence. Treat the numbered samples as the primary learning path.

## Run a sample

From the repository root after Composer installation:

```bash
php samples/sample_01_simple_variables.php
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

Most numbered samples have a corresponding LibreOffice template in:

```text
samples/templates/
```

For example:

```text
samples/sample_08_html.php
samples/templates/template_08_html.odt
```

Read both sides when learning a feature. The PHP file shows what the application supplies; the ODT template shows what remains the responsibility of LibreOffice.

That distinction is central to the engine's design.

## Learning by feature

For **template language**, read Samples 01–03 and 10 together with the Template Language documentation.

For **rich generated content**, read Samples 09, 14, and 18 before moving to Sample 21.

For **addressable native ODT structures**, start with Sample 22, continue with 23 and 24, and then study Sample 25 together with [Addressable ODT Structures](../rich-documents/addressable-document.md) and [Named Sections](../rich-documents/named-sections.md).

For **tables**, start with 11, then 13, and use 20 when you need relative table-column widths.

For **HTML**, use 08 for general import and 19 for table import.

For **images**, compare 05/06 with the generated-image use in Sample 21 and the native-section replacement in Sample 24. These show three different ownership models: replacing an existing image position, generating an image inside a PHP-owned content block, and replacing the content of a native named section.

## Two CV architecture showcases

Samples 21 and 25 are both real-world CV examples, but they demonstrate different and complementary architecture patterns.

### Sample 21 — programmatically generated regions

Sample 21 uses a LibreOffice-designed two-column shell with large placeholders. PHP constructs the dynamic regions with `RichText`, `Paragraph`, `ListElement`, `ImageElement`, and document-local paragraph styles, then inserts those regions with `setElement()`.

Use this pattern when PHP genuinely owns the dynamic document structure inside a larger template-owned layout.

Read [Building Complex Documents](building-complex-documents.md) for this approach.

### Sample 25 — native structured template sections

Sample 25 keeps more of the repeatable document structure in LibreOffice. PHP assigns scalar values and expands native named `ExperienceEntry` and nested `ActivityEntry` section prototypes with `instantiateMany()`.

Use this pattern when the repeatable structure should remain visually authored in LibreOffice and application code should address semantic template objects rather than rebuild them.

Read [Named Sections](../rich-documents/named-sections.md) and the [Practical ODT template authoring guide](../getting-started/template-authoring-guide.md) for this approach.

Neither sample replaces the other. They demonstrate two different ownership boundaries between the ODT template and PHP.

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
Sample 21: PHP-generated regions
or
Sample 25: native structured sections
```

This keeps application rendering code understandable and makes ODT-specific problems much easier to isolate.

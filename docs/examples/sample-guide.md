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
| 20 | Table ratios | ratio-based dynamic table layout |
| 21 | CV profile | a real-world composed document using multiple engine layers |

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

For **tables**, start with 11, then 13, and use 20 only when you specifically need the ratio-based layout mechanism.

For **HTML**, use 08 for general import and 19 for table import.

For **images**, compare 05/06 with the generated-image use in Sample 21. This shows the difference between replacing a LibreOffice-owned image position and creating an image as part of a generated content block.

## Sample 21 is different

Sample 21 is intentionally not a minimal API demonstration. It is the repository's real-world composition example.

It combines:

- a LibreOffice-designed two-column template;
- `PageLayoutOdtTemplate`;
- reusable semantic paragraph styles;
- `RichText` section builders;
- paragraphs and inline text styles;
- native lists;
- an embedded profile image;
- multiple application-data collections;
- dynamic replacement of large document regions.

Read [Building Complex Documents](building-complex-documents.md) before using Sample 21 as a model for application architecture.

## Verification samples

The project also has integration tests that execute important public samples. This is deliberate: samples are user-facing code and should not silently drift away from the public API.

When changing a public sample, run the normal test suite in addition to opening the generated document in LibreOffice.

## A practical rule

Do not copy the largest sample when a smaller one demonstrates the feature you need.

A good progression is:

```text
small API sample
      ↓
feature guide
      ↓
combine two or three concepts
      ↓
Sample 21 architecture
```

This keeps application rendering code understandable and makes ODT-specific problems much easier to isolate.

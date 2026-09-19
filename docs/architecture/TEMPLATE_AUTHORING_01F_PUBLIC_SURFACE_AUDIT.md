# TEMPLATE-AUTHORING-01F Public Surface Audit

**Status:** F0 findings — review baseline  
**Target:** Version 1.0  
**Baseline:** `develop` after Release 1.0 Planning Reconciliation  
**Scope:** Public documentation, samples, templates, Sample Explorer, and related sample infrastructure

## Purpose

This document records the F0 inventory and product-surface findings for TEMPLATE-AUTHORING-01F.

F0 is an investigation and classification step. It does not itself authorize API changes, deletion of historical samples, regeneration of local sample outputs, or changes to completed TEMPLATE-AUTHORING-01A–E semantics.

The central product question is no longer only which engine features exist. Version 1.0 must make it obvious what developers can build with those features and which authoring model they should choose.

## Product perspective

The public sample suite should be derived from the capabilities of version 1.0, not from the chronological order in which those capabilities were developed.

A public sample is a product artifact:

```text
PHP example
    +
LibreOffice-authored ODT template
    +
generated editable ODT result
    +
documentation context
```

Its filename, template, output, documentation, and visible result should communicate the same primary lesson.

Public examples must be safe for a human developer or coding/AI agent to interpret as recommended supported usage.

## Current public-surface findings

The repository currently exposes several overlapping definitions of a public sample:

- `docs/examples/sample-guide.md` presents the numbered 01–25 sequence as the primary learning path;
- `demo/sample-explorer/index.php` discovers numeric samples 01–25;
- `tests/Integration/PublicSampleSmokeTest.php` treats numeric samples 01–27 as public and asserts exactly 27 samples;
- newer work includes Sample 29 for Writer User Field binding;
- `samples/` also contains unnumbered historical/development scripts using older lifecycle and compatibility APIs.

This creates avoidable ambiguity for both developers and AI agents.

The Sample Explorer additionally contains Sample-21-specific JavaScript showcase logic and a hard-coded GitHub `master` link. The public presentation therefore encodes historical sample identity rather than a reusable sample role.

Sample 29 currently depends on a template under `tests/fixtures/`. Because tests are excluded from the Composer archive while samples are not, that is not an acceptable long-term shape for a canonical public sample.

The existing documentation already contains a strong authoring model. In particular, the Template Authoring Guide and How the Engine Works establish LibreOffice as the visual authoring environment and distinguish template expressions, PHP-owned generated content, and addressable native ODT structure. Phase F should consolidate and update that model rather than replace it.

## Version-1.0 public sample taxonomy

The target public suite has three semantic roles.

### Learn

Small canonical examples answer one developer question and are suitable for copying.

| ID | Target sample | Primary purpose |
| --- | --- | --- |
| L01 | Variables & Filters | Scalar assignment, filters, and bounded text transformation such as nl2br |
| L02 | Conditions | if / elseif / else / ifnot |
| L03 | Repeating Content | Classic foreach / repeating assignment |
| L04 | Rich Content | RichText, Paragraph, links, tabs, and generated-region ownership |
| L05 | Lists | Native editable ODT lists and list styles |
| L06 | Images | Template-owned image replacement versus PHP-owned ImageElement content |
| L07 | Tables | Programmatically generated native tables, cells, spans, and basic styling |
| L08 | HTML Import | HTML as an integration boundary into native ODT content |
| L09 | Native Objects | Addressable bookmarks and Sections with their distinct supported semantics |
| L10 | Writer User Fields | Bounded native string User Field binding |
| L11 | Template Inspection | TemplateContract, dependencies, native objects, capabilities, and diagnostics |

### Capabilities

Capability samples demonstrate larger architectural or layout behavior.

| ID | Target sample | Primary purpose |
| --- | --- | --- |
| C01 | Page & Flow Layout | Real multi-page flow and existing page-flow semantics |
| C02 | Advanced Table Layout | TABLE-LAYOUT-01 widths, columns, row geometry, and alignment |
| C03 | Frame Layout | FRAME-LAYOUT-01 anchors, positioning, offsets, wrap, and flow |
| C04 | Structured Collections | LibreOffice-authored Sections, nested collections, and zero-item behavior |
| C05 | Mapping & Automation | Inspection, mapping, concrete preflight, and common atomic automation |

### Showcases

Professional showcases demonstrate useful finished documents rather than isolated APIs.

| ID | Target showcase | Product story |
| --- | --- | --- |
| S01 | Professional CV | Same professional design language demonstrated through generated-region and structured-template ownership variants |
| S02 | Professional Invoice | Accessible business-document workflow using template language, tables, images, metadata, and page design |
| S03 | Automated Report | Flagship structured-template workflow using contract inspection, native structure, mapping, preflight, and automation |

The showcase documents should be visually strong enough to use in repository and website presentation.

## Proposed naming convention

The semantic role should be visible in repository filenames.

Preferred shape:

```text
sample_L01_variables_filters.php
sample_L02_conditions.php
...
sample_C05_mapping_automation.php
sample_S01a_cv_generated_regions.php
sample_S01b_cv_structured_template.php
sample_S02_professional_invoice.php
sample_S03_automated_report.php
```

Corresponding public templates and outputs should use the same identity:

```text
template_L01_variables_filters.odt
template_C03_frame_layout.odt
template_S02_professional_invoice.odt

output_L01_variables_filters.odt
output_C03_frame_layout.odt
output_S02_professional_invoice.odt
```

The exact physical-template arrangement for S01a/S01b remains an implementation decision. The two variants should share a visual design language, but their different ownership boundaries may require distinct ODT templates.

## Migration map — numbered samples

The following classifications describe capability migration, not immediate deletion permission.

| Current sample | Current template | Valuable capability | Version-1.0 target | Classification |
| --- | --- | --- | --- | --- |
| 01 Simple Variables | `template_01_simple_variables.odt` | variables, repeat, image currently mixed | L01 / L03 / L06 | REPLACE / MIGRATE |
| 02 Filter | `template_02_filter.odt` | text/date/number/currency filters | L01 | MIGRATE |
| 03 Logic Elements | `template_03_logic_elements.odt` | conditions | L02 | KEEP / MODERNIZE |
| 04 Metadata | `template_04_metadata.odt` | metadata and reload behavior | S02 / S03 + docs | MIGRATE |
| 05 / 05b Image Replacement | `template_05*.odt` | template-owned image replacement | L06 | MIGRATE |
| 06 Image Settings | `template_06_imageSettings.odt` | ImageElement versus template image API | L06 | KEEP / REBUILD |
| 07 Contact List | `template_07_contactList.odt` | Paragraph, tabs, hyperlinks | L04 | MIGRATE |
| 08 HTML | `template_08_html.odt` | broad HTML import | L08 | KEEP / MODERNIZE |
| 09 RichText Block | `template_09_richtextblock.odt` | mixed RichText content | L04 | MIGRATE |
| 10 Smart Business Letter | `template_10_smarties.odt` | integrated business-document features | S02 | REPLACE |
| 11–15 table-focused samples | corresponding 11–15 templates | table/cell/span/style behavior | L07 / C02 | MIGRATE / CONSOLIDATE |
| 16 Tabs Basic | `template_16_tabsBasic.odt` | tabs, lines, image in generated content | L04 | MIGRATE |
| 17 Text Field | `template_17_textfield.odt` | DrawTextBox/frame-oriented content | C03 | MIGRATE / REPLACE |
| 18 List Styles | `template_18_ListStyles.odt` | native lists and list styles | L05 | KEEP / MODERNIZE |
| 19 HTML Table | `template_19_htmlTable.odt` | HTML table import | L08 | MIGRATE |
| 20 Table Ratio | `template_20_tableRatio.odt` | relative column geometry | C02 | MIGRATE |
| 21 CV Profile | `template_21_cvProfile.odt` | PHP-owned generated CV regions | S01a | KEEP / PROFESSIONALIZE |
| 22 Bookmark demo | current native bookmark template | bounded bookmark replacement | L09 | KEEP / MIGRATE |
| 23 Section content demo | current Section template | Section content replacement | L09 | KEEP / MIGRATE |
| 24 Section image demo | research-oriented template | Section content/resource replacement | L09 or S03 | MIGRATE |
| 25 Section Instantiation | `sample_25_sectionClone.odt` | native/nested Section collections | S01b + C04 | KEEP / NEW PROFESSIONAL TEMPLATE |
| 26 Table Layout | `template_26_tableLayout.odt` | TABLE-LAYOUT-01 | C02 | KEEP / PROMOTE |
| 27 Frame Layout | `template_27_frameLayout.odt` | FRAME-LAYOUT-01 | C03 | KEEP / PROMOTE |
| 29 User Field Binding | test fixture dependency | Writer User Field + contract inspection | L10 | KEEP CONCEPT / REPLACE TEMPLATE |

The exact tracked filenames for the documented Samples 22–24 must be verified during migration rather than inferred from documentation. Their documented capabilities remain part of the migration plan.

## Migration map — unnumbered historical/development samples

These files should not remain public merely because they are historical. Unique concepts must first be migrated or preserved in tests.

| Current file | Current template | Valuable concept | Target | Classification |
| --- | --- | --- | --- | --- |
| `sample_final_invoice.php` | `template_with_tabstops.odt` | invoice concept, tabular/key-value content | S02 | IDEA MIGRATE, then REMOVE |
| `sample_html_images.php` | `template_html.odt` | HTML images | L08 | MIGRATE, then REMOVE |
| `sample_komplex_image.php` | `template_element_image.odt` | ImageElement in rich content | L06 | MIGRATE, then REMOVE |
| `sample_nl2br.php` | `test_nl2br_template.odt` | nl2br | L01 | MIGRATE, then REMOVE |
| `sample_repeating.php` | `template_repeating.odt` | legacy repeating example | L03 | MIGRATE, then REMOVE |
| `sample_richtable.php` | `template_table.odt` | RichTable | L07 | MIGRATE, then REMOVE |
| `sample_richtext.php` | `template_richtext.odt` | RichText and legacy mixed APIs | L04 / L06 | MIGRATE, then REMOVE |
| `sample_richttext_simple.php` | `template_richtextblock.odt` | basic RichText | L04 | REMOVE after comparison |
| `sample_test_stylewriter.php` | `template_stylewriter.odt` | development/style experiment | tests/docs if still needed | REMOVE FROM SAMPLES |
| `sample_textblock.php` | `template_textblock.odt` | basic generated text block | L04 | REMOVE after coverage |
| `test.php` | `template_13_settingCells.odt` | development duplicate of table work | tests/C02 if unique | REMOVE FROM SAMPLES |
| `test_fonts.php` | development fixtures | font/debug experimentation | tests | REMOVE FROM SAMPLES |
| `test_paragraph_methods.php` | `test_paragraph_methods.odt` | paragraph API experimentation | tests/L04 if unique | REMOVE FROM SAMPLES |

No removal occurs until the target sample or test proves that the relevant behavior has been retained.

## Public-surface gaps

F0 identified three important capabilities that are implemented or architecturally complete but not represented clearly enough in the current public sample path:

1. Template Inspection — needs a small canonical L11 example.
2. Mapping / Preflight / Automation — needs C05 and should be demonstrated end-to-end in S03.
3. Page Flow — completed PAGE-FLOW-01 semantics need a visible C01 capability example.

Writer User Field binding also needs a self-contained public template instead of a test-fixture dependency.

## Template-design workload

Not every surviving sample needs a showcase-grade redesign.

### Full professional design

- S01 Professional CV;
- S02 Professional Invoice;
- S03 Automated Report.

### Focused public-template modernization

Learning and capability templates should share a coherent visual language: readable typography, spacing, headings, explanatory context, and deliberate layout. They do not require the same design effort as the showcases.

### No design investment before retirement

Historical or redundant templates scheduled for migration/removal should not be cosmetically renovated before their unique capability has been migrated.

## Documentation findings

The existing documentation should be evolved rather than replaced.

The canonical authoring story remains:

```text
Who owns the structure?
    |
    +-- LibreOffice template expressions for scalar/lightweight logic
    +-- PHP-owned ODT elements for generated subtrees
    +-- LibreOffice-owned native ODT structures for semantic template objects
```

Inspection, mapping, preflight, and automation are an integration/automation layer over those authoring choices, not a fourth competing authoring model.

Phase F must update stale pre-completion statements from PAGE-FLOW-01, FRAME-LAYOUT-01, and TEMPLATE-AUTHORING-01A–E and link feature documentation to canonical L/C/S samples.

The Sample Guide should stop presenting historical numbering as the product information architecture.

## Sample Explorer and website findings

The current Sample Explorer:

- hard-codes numeric discovery for Samples 01–25;
- derives categories heuristically from filenames;
- contains special-case JavaScript for Sample 21;
- contains a hard-coded GitHub `master` link for that showcase.

The version-1.0 Explorer should understand semantic sample roles explicitly rather than infer them from historical numbering.

The product website should remain a bounded Phase-F alignment, not a separate redesign. Its discovery story should lead with real editable ODT outcomes and the three professional showcases, then guide users through Learn and Capabilities into documentation and the Sample Explorer.

## Repository boundaries

For version 1.0 the intended repository meaning is:

```text
samples/   supported public examples
tests/     regression and behavioral evidence
research/  ODF and architecture research evidence
docs/      current user guidance plus clearly identified architecture evidence
```

Local `samples/output/*.odt` files remain regression artifacts and must not be restored, deleted, regenerated, or committed merely as part of Phase F cleanup.

The local `research/` material remains valuable and must not be treated as disposable sample clutter.

## F0 conclusion

The public 1.0 surface should communicate a deliberate developer journey:

```text
LEARN
  L01–L11
      |
      v
CAPABILITIES
  C01–C05
      |
      v
SHOWCASES
  S01 CV
  S02 Invoice
  S03 Report
```

The current repository already contains most of the underlying capability evidence. Phase F is primarily a consolidation, modernization, presentation, and discoverability effort, with a small number of genuinely new public examples.

The next step is governed by the TEMPLATE-AUTHORING-01F Change Contract.
# TEMPLATE-AUTHORING-01F S03 — Structured Professional Report Change Contract

**Status:** APPROVED FOR IMPLEMENTATION  
**Milestone:** TEMPLATE-AUTHORING-01F / S03  
**Base:** `develop`  
**Branch:** `architecture/template-authoring-01f-s03`

## 1. Purpose

S03 is the professional counterpart to B02. B02 demonstrates that PHP can construct a structured, editable professional report. S03 demonstrates the complementary authoring model: LibreOffice Writer designs and owns the professional document, while the application addresses and mutates only explicitly exposed native/document-semantic targets.

S03 is not a second report builder and must not reproduce Writer layout in PHP.

The completed S03 showcase must provide two distinguishable report outcomes:

1. **S03-A — Updated report instance:** the same Writer-authored report structure is reused with changed application-owned report data and results.
2. **S03-B — Structurally different report instance:** the same structured-template approach produces a substantially different report by exercising existing typed native capabilities to change larger semantic components without rebuilding document layout in PHP.

S03-A is the first implementation slice. S03-B follows only after S03-A has been accepted.

## 2. Core ownership rule

### Writer owns

- page and master-page layout;
- headers and footers;
- typography and styles;
- paragraph geometry and spacing;
- native Section structure;
- table geometry, column widths, headers, and formatting;
- frames and text boxes;
- image geometry and placement;
- list structure and formatting where not explicitly made application-owned;
- static labels and content not exposed through a semantic target.

### PHP owns

- document-wide scalar report identity explicitly exposed as Writer User Fields;
- bounded text explicitly exposed through bookmarks;
- mutable tabular datasets explicitly exposed through named Writer tables;
- replacement image resources explicitly exposed through named image frames;
- document metadata where appropriate;
- explicit runtime choices already supported by an existing typed capability.

The application must not reconstruct Writer-owned layout.

## 3. S03-A semantic objective

S03-A proves that a substantial professional Writer document can be reused as a new report instance without converting it into a PHP-built document.

The output must clearly represent a new fictional organization/program/report instance while preserving the authored report design and semantic structure.

S03-A must not introduce classic template syntax merely to make the sample easier to implement. In particular, the canonical S03-A template must not depend on `{{variable}}`, `{{#foreach}}`, template conditions, or other classic placeholder expressions.

This restriction applies to S03-A. It is not a deprecation of the simple-template processing model.

## 4. Existing capabilities permitted in S03-A

S03-A is an integration showcase of existing public APIs. It does not authorize a new engine API.

Permitted mutation paths are:

```php
$template->setUserField(...);
$template->bookmark(...)->replaceText(...);
$template->table(...)->populate(...);
$template->replaceImageByName(...);
$template->setMeta(...);
```

The following are intentionally outside the S03-A implementation model:

```text
assign()
assignRepeating()
classic render-time template expressions
setElement()
PHP-built RichText/ListElement/Paragraph report regions
direct DOM/XPath mutation from the sample
```

`render()` must not be called merely by habit. It should be used only if an actual lifecycle requirement is demonstrated.

## 5. Writer User Fields

The current Writer-authored report exposes these document-wide scalar values as string User Fields:

```text
organization_name
organization_short_name
report_title
report_year
program_name
report_period
report_status
report_date
```

S03-A must bind all eight through `setUserField()`. Punctuation, separators, placement, and formatting remain Writer-owned.

The implementation and tests must respect the already-characterized User Field behavior: authoritative declarations are mutated, while Writer may reevaluate displayed field text when the document is opened. Manual LibreOffice regression is therefore part of S03-A acceptance.

## 6. Bookmarks

Bounded variable text in the Writer-authored report is application-owned through named bookmarks and is updated through:

```php
$template->bookmark($name)->replaceText($value);
```

The sample should group bookmark values as application data and iterate over that data in ordinary PHP. Such iteration is not template collection processing.

Bookmark marker identity must survive bounded text replacement.

## 7. Native Writer table population

S03-A uses TABLE-ROW-01 through existing named Writer tables.

### ActivitiesDeliveredTable

The output must demonstrate growth beyond the Writer-authored mutable source rows. The intended characterization target is six application data rows.

### PerformanceAgainstTargetsTable

The output must demonstrate shrinkage below the Writer-authored mutable source rows. The intended characterization target is four application data rows.

Native Writer header rows, table identity, geometry, and formatting remain Writer-owned.

No `keepRows` behavior is required unless inspection of the final authored S03 template proves that a protected ordinary source row is semantically necessary.

## 8. Named image frame

The participant-outcomes graphic is replaced through the existing compatibility operation:

```php
$template->replaceImageByName('ParticipantOutcomesImage', $imagePath, [
    'width' => '15.799cm',
    'height' => '10.007cm',
]);
```

The explicit dimensions preserve the currently authored frame geometry and avoid the legacy default dimensions of `replaceImageByName()`.

S03-A does not introduce a new Frame mutation API and does not generate the chart in PHP. The replacement graphic is a prepared sample asset under `samples/assets/`.

## 9. Sections and collection semantics

The Writer-authored S03 template contains semantic Sections including content regions such as:

```text
ProgramObjectivesContent
ChallengesContent
RecommendationsContent
```

S03-A does not replace these regions with PHP-built `OdtElement` content and does not add classic placeholders solely to feed `instantiateMany()`.

These regions may therefore remain Writer-owned static content in S03-A where that content remains valid for the updated report instance.

This is a bounded S03-A decision, not a conclusion that Writer-authored Sections cannot contain clonable native objects.

### Important repository finding

Current `SectionCloneService` explicitly rewrites native identities inside a cloned Section, including:

- `text:section/@text:name`;
- `text:bookmark/@text:name`;
- `text:bookmark-start/@text:name`;
- `text:bookmark-end/@text:name`;
- `table:table/@table:name`;
- `draw:frame/@draw:name`.

Therefore the architecture already preserves the technical basis for cloned Sections containing bookmarks.

However, the current public `SectionTarget::instantiate()` / `instantiateMany()` data-binding contract binds scalar **template expressions** in the cloned Section. `SectionTarget` currently exposes scoped nested-Section resolution, but no equivalent scoped `bookmark()` accessor for addressing a logical bookmark inside each returned Section instance.

Consequently, S03-A must **not** claim that native bookmark-driven collection instantiation is unsupported in principle, removed, or impossible. The exact historical/current path for "Section collection + contained bookmarks as item fields" is an architectural follow-up to characterize separately before changing APIs.

No regression or new API is to be inferred from S03-A alone.

## 10. S03-A report data

The S03-A output must unmistakably be a new fictional report instance rather than the Asteria B02 report with a few numbers changed.

The application data should include a different fictional organization and program, report identity, reporting period, KPI values, narrative findings, activity rows, performance rows, and participant-outcomes image.

The exact fictional names and prose are sample content and may be refined during implementation without changing this contract.

The selected data must remain semantically compatible with Writer-owned static regions retained by S03-A.

## 11. Canonical files

The intended canonical artifacts are:

```text
samples/sample_S03_structured_professional_report.php
samples/templates/template_S03_structured_professional_report.odt
samples/assets/s03-participant-outcomes-2027.png
samples/output/output_S03_structured_professional_report.odt
```

The manually prepared Writer document becomes the canonical template. It must not be regenerated programmatically.

As usual, `samples/output/*.odt` is a local/manual regression artifact unless the task explicitly requires committing a canonical output.

## 12. Sample registry

S03 is registered as a canonical showcase:

```text
id:           S03
title:        Structured Professional Report
role:         showcase
ownership:    addressable-native-odt
status:       canonical
distribution: composer
execution:    odt
```

The registry purpose must communicate the B02/S03 distinction: a professional LibreOffice Writer-authored report is updated through native User Fields, bookmarks, named tables, and image-frame replacement without PHP-owned layout construction.

## 13. Automated acceptance

S03-A requires focused integration evidence in addition to the general sample smoke tests.

At minimum verify:

- generated file is a valid ODT package;
- canonical S03-A source template and output contain no classic `{{...}}` expressions;
- expected User Field declarations carry the new values in relevant ODF parts;
- representative bookmark values are replaced and bookmark markers survive;
- `ActivitiesDeliveredTable` retains native identity/header and contains the intended grown data region;
- `PerformanceAgainstTargetsTable` retains native identity/header and contains the intended shrunk data region;
- `ParticipantOutcomesImage` retains its `draw:name` and authored `15.799cm × 10.007cm` geometry;
- replacement image is packaged and represented in the manifest;
- major named Sections remain present;
- sample does not reconstruct those Sections with programmatic RichText/list/paragraph content.

Normal preflight includes focused S03 tests, `SampleRegistryTest`, `PublicSampleSmokeTest`, full `composer test`, PHP lint for relevant PHP, and `git diff --check`.

## 14. Manual LibreOffice acceptance

Automated tests do not replace visual Writer regression. Open the generated S03-A report in LibreOffice and verify:

1. professional multi-page layout remains intact;
2. header and footer display the new report identity;
3. cover User Fields display the new values;
4. KPI and information tables retain their layout;
5. grown Activities table retains Writer-authored formatting;
6. shrunk Performance table retains Writer-authored formatting;
7. replacement participant-outcomes image retains intended geometry and placement;
8. bookmark mutations produce no visible artifacts;
9. pagination and page flow remain credible;
10. save, close, and reopen succeeds without structural damage.

## 15. S03-A authoring findings and regression evidence

S03-A exposed three authoring findings that are now part of the Phase-F
documentation baseline.

### Bookmark range ownership

Bookmark replacement is intentionally range-based. The exact Writer-authored
bookmark boundaries therefore define the application's mutation authority.
Template authors should bookmark only the semantic value the application owns,
not adjacent punctuation, labels, separators, or other Writer-owned text.

An oversized bookmark is easy to create visually in LibreOffice and may still
be structurally valid ODF. The resulting replacement can therefore look like an
engine mutation defect even though the engine correctly replaced the authored
range. Canonical templates must verify bookmark start/end boundaries as part of
authoring review.

### Repeated document identity

A scalar that represents one document-wide fact and occurs in several regions
should be authored once as a Writer User Field and referenced wherever needed,
including master-page headers and footers. Duplicating the same logical value as
separate bookmarks creates unnecessary synchronization responsibility in
application code.

### Section clones and contained bookmarks

The repository establishes the following chain:

```text
Writer-authored Section
-> Section clone
-> deterministic rewriting of contained bookmark identities
-> structurally distinct bookmarks in each clone
```

What is not currently established is a public instance-relative addressing
operation that lets application code take a returned Section instance and
address the logical contained bookmark without knowing its generated rewritten
name.

This is a concrete capability gap, not evidence that Section cloning with
bookmarks is invalid. S03 must not work around it by coupling application code
to generated bookmark suffixes. The semantics and public surface for
instance-relative native-object addressing require separate characterization
before any API is approved.

### S03-A regression gate

The S03-A implementation passed its repository test/preflight work and the
rendering-sensitive result was accepted through the project visual-regression
workflow and manual LibreOffice inspection. The Writer-owned multi-page layout,
native tables, image-frame geometry, bookmark mutations, User Field display,
page flow, and save/reopen behavior form the accepted baseline for the next S03
slice.

This evidence closes the S03-A implementation gate. It does not close S03 as a
whole: S03-B remains required, followed by the explicit TEMPLATE-AUTHORING-01F
Final Review.

## 16. S03-B boundary

S03-B follows only after S03-A is accepted.

S03-B must demonstrate a deliberately different report composition using existing typed native capabilities. Its purpose is structural variation, not merely another data update.

S03-B must not be used as justification for a universal named-element mutation API, a theme/style-switcher API, programmatic Writer Section creation, or another speculative abstraction.

The exact S03-B structural operations will be fixed after S03-A regression evidence and after the contained-bookmark/Section-collection behavior has been characterized.

## 17. Explicit non-goals

S03 does not, by itself, authorize a universal Named Element API, programmatic Writer Section creation / `SectionElement`, a new style/theme switching API, native table-row semantic markers, TEMPLATE-FORMAT-PRESERVATION-01, TEMPLATE-AUTHORING-UX-01, STYLE-API-02, STYLE-CONTEXT-01, or changes to classic foreach scope semantics.

Those remain separate architecture topics.

## 18. Completion gate

S03-A is complete only when implementation, focused tests, repository preflight, and manual LibreOffice regression all pass.

After S03-A, the contained-bookmark/Section-collection behavior identified in Section 9 must be characterized before S03-B relies on it.

After S03-A and S03-B are complete, TEMPLATE-AUTHORING-01F still requires its explicit Final Review before FINALIZATION-01 begins.

# Future Development

## Table Layout

### TABLE-LAYOUT-01 — Explicit table width

- Priority: High
- Status: Planned improvement / current limitation

The engine does not currently provide a sufficiently reliable,
documented mechanism for defining the explicit overall width of a
generated table. No replacement API is defined here.

### TABLE-LAYOUT-02 — Explicit column widths

- Priority: Very high
- Status: Confirmed/incomplete capability

`RichTable` and `StyleWriter` already contain an ODF table-column style
path using `table:table-column` and `style:column-width`, but explicit
column sizing has not proven sufficiently reliable in LibreOffice. The
current implementation also contains ratio and virtual-column
workarounds.

Future work should first investigate why the existing ODF-oriented
column-width path is unreliable before designing a replacement. Fixed
column widths are not completely unimplemented.

### TABLE-LAYOUT-03 — Explicit row height and minimum row height

- Priority: Medium-high
- Status: Planned improvement

Generated tables should eventually support reliable explicit row height
and/or minimum row height using appropriate native ODF structures. No
public API is defined here.

### TABLE-LAYOUT-04 — Reliable relative column width distribution

- Priority: High
- Status: Planned improvement / existing workaround

The current table implementation contains ratio and virtual-column /
`colspan` mechanisms for controlling column proportions.

Future work should replace or formalize these workarounds with a
reliable ODF-based relative column sizing model where possible.

## Frames and Images

### FRAME-LAYOUT-01 — Unified frame positioning model

- Priority: High
- Status: Architectural improvement

Images and text boxes rely on ODF frame positioning concepts including
anchors, horizontal/vertical positioning, relation targets and
wrapping.

Future work should establish a consistent internal model for these
shared frame-layout responsibilities. This entry does not define a new
model.

### FRAME-LAYOUT-02 — Reliable DrawTextBox positioning

- Priority: Medium-high
- Status: Confirmed limitation

`DrawTextBox` can generate editable ODF text frames, but advanced
positioning behavior is not currently reliable in LibreOffice. Sample
17 demonstrates this known pre-existing limitation. This is not a P1
regression.

### IMAGE-LAYOUT-01 — Reliable image anchor, wrapping and positioning

- Priority: High
- Status: Confirmed complex/incomplete area

Image insertion works, but advanced combinations of anchoring, wrapping
and positioning have historically been difficult to make reliable
across LibreOffice rendering scenarios.

Future work should investigate this together with the shared ODF frame
model where appropriate. Existing image APIs are unchanged by this
roadmap entry.

## Lists

### LIST-LAYOUT-01 — Explicit list indentation controls

- Priority: Medium
- Status: Planned improvement

Native ODF lists work, but developers need clearer and more reliable
control over list indentation and label/text spacing.

P1 already fixed an unrelated bug where list post-processing could
remove normal paragraph margins. That fix is not reopened here.

### LIST-LAYOUT-02 — Improved nested-list style control

- Priority: Medium
- Status: Planned improvement

Multi-level native lists are supported in existing scenarios, but
nested-list styling and per-level control should be made more explicit
and predictable.

## Table Cells

### TABLE-CELL-01 — Vertical table-cell alignment

- Priority: Medium
- Status: Planned improvement

Provide a reliable and documented mechanism for controlling vertical
alignment inside generated table cells. The final public API is not
defined here.

## HTML Import

### HTML-IMPORT-01 — Expanded and formally specified HTML compatibility

- Priority: Later
- Status: Planned improvement

`HtmlImporter` supports a useful subset of HTML, including formatted
text, lists, tables and images.

Future work should formally document the supported subset and expand it
only where justified by real use cases and reliable ODF mapping.

P2-A already hardened image resolution, remote-image opt-in and
temporary asset handling. Those changes are not duplicated here.

## Style Architecture

### STYLE-CONTEXT-01 — Document-scoped style registry / StyleContext

- Priority: Medium-high
- Status: Confirmed architectural limitation

P2-B demonstrated that explicit static registrations through
`StyleMapper::registerParagraphStyle(...)` and
`StyleMapper::registerTextStyle(...)` can leak into a subsequently
generated document in the same PHP process.

Normal element-generated styles did not show the same observable
cross-document contamination in the P2-B diagnostic test.

Future work should replace process-wide static style state with a
document-scoped registry or `StyleContext` without silently breaking
existing registration behavior. A constructor reset is not proposed.

### STYLE-API-02 — Compatibility/deprecation strategy for legacy direct style APIs

- Priority: Medium
- Status: Architectural/API debt

Compatibility helpers and public writer APIs have overlapping style
responsibilities. Their long-term role needs to be decided without
silently breaking external users. No API is deprecated by this roadmap
entry.

## Runtime / Temporary Assets

### TEMP-ASSET-01 — Explicit temporary-asset lifecycle for long-running workers

- Priority: Low
- Status: Future hardening

P2-A introduced automatic shutdown cleanup for importer-created
temporary assets. This is appropriate for normal CLI executions and web
requests.

Long-running workers may eventually need an explicit per-document or
per-job cleanup lifecycle so temporary assets do not remain until the
worker process terminates. `TemporaryAssetRegistry` is unchanged by
this roadmap entry.

## Sample Infrastructure

### SAMPLE-INFRA-01 — Public sample execution and artifact lifecycle

- Priority: Medium
- Status: Infrastructure / repository lifecycle improvement

Public samples now have an executable contract covering standalone
Composer bootstrap, canonical output names and valid ODT package
structure. Automated smoke testing should remain isolated from tracked
generated artifacts. The repository still contains historical and
canonical generated ODT artifacts whose long-term ownership and
retention policy should be formalized.

## Template Processing

### TEMPLATE-FORMAT-PRESERVATION-01 — Preserve unrelated ODT inline formatting

- Priority: Medium-high
- Status: Future work / not part of ARCH-04B1

The Smarty-inspired template language currently performs DOM
transformations that can lose or alter existing ODT inline formatting,
especially when placeholders or control structures are distributed across
multiple `text:span` or text nodes. This is pre-existing behavior and is not
an ARCH-04B1 regression.

The issue applies to the complete template-language family, including plain
placeholders, scalar filters, structural `nl2br`/`ul`/`ol` placeholders,
conditionals and repeating blocks. LibreOffice may split visually
continuous expressions across nodes; normalization and replacement can then
repair the expression by changing the DOM or text structure and affect
formatting.

ARCH-04B2 explicitly characterized an additional case: when a placeholder
such as `prefix {{nl2br:value}} suffix` is contained in one XML text node,
the current `nl2br` implementation removes the complete original text node
and replaces it with generated value and line-break nodes. The surrounding
prefix and suffix are therefore lost. This is historical behavior, now
covered by tests, and is not an ARCH-04B2 regression. Future
format-preserving processing must preserve unrelated surrounding text and
XML content as well as inline formatting.

A dedicated future development round should first audit formatting-loss
scenarios and add characterization coverage for deliberately formatted
placeholders, placeholders split across formatted spans, bold/italic/color/
font styles, formatting around control structures, and formatting inside
repeating blocks and conditional branches. It should define preservation
expectations for `content.xml` and `styles.xml`, investigate a node-aware
replacement strategy, and include LibreOffice regression testing,
Collabora testing where practical, and a dedicated public/sample document.

No implementation strategy is defined by this entry. The architectural
principle is that template-language processing should transform template
semantics without unnecessarily destroying unrelated document formatting.

### TEMPLATE-AUTHORING-UX-01 — More intuitive authoring of complex ODT templates

- Priority: Medium-high
- Status: Future work / not part of ARCH-04

The current template language is functional and intentionally lightweight,
but complex documents require authors to understand the structural placement
of template markers inside LibreOffice/ODT documents. Sample 10 is a useful
real-world reference because it combines scalar placeholders, filters,
conditions, foreach blocks, tables, styled content and document layout. The
resulting document is useful, while its underlying template exposes the
technical nature of the current authoring model.

Conditions such as `{{#if:...}}`, `{{#elseif:...}}`, `{{#else}}` and
`{{#endif}}`, together with `{{#foreach:...}}` / `{{#endforeach}}`, depend on
structural properties of the ODT DOM. Future work should investigate whether
authoring can become more intuitive without hiding important ODT behavior or
creating an unnecessarily complex programming language inside LibreOffice.

ARCH-04B3 characterization exposed interactions that must be considered by
that investigation: row-local filters are not re-evaluated during foreach
processing; `nl2br` and list transformations are not generally row-local;
conditions inside foreach use outer values rather than row-local state;
nested control structures are unsupported or uncertain; and RichText/
OdtElement values are not supported as foreach row values. These are known
compatibility limitations, not ARCH-04 defects, and are not changed by this
roadmap entry.

An architectural question for future work is whether increasingly complex
behavior belongs in the template language or should remain the responsibility
of programmatic `RichText`/`OdtElement` construction. In particular, future
investigation may consider whether foreach rows should support structured
values, without assuming that they should. The project should preserve a
useful separation between template-driven authoring and programmatic rich
document construction rather than reproducing a full programming language in
an ODT template.

This topic is related to, but distinct from,
`TEMPLATE-FORMAT-PRESERVATION-01`: that entry concerns preserving formatting
and DOM structure while processing an existing document, whereas this entry
concerns how developers and document authors create and understand templates
in LibreOffice. The concerns interact but should be investigated separately.

The project now has an established operational visual validation workflow for
rendering-sensitive changes:

```text
ODT → LibreOffice headless → PDF → PNG → visual review
```

The workflow can compare an untouched input template with its generated output
and can compare a newly generated output with a previously accepted local
reference baseline. The current local/reference baseline covers the public
sample set and is not represented as committed permanent golden-master
binaries in the repository.

`TEMPLATE-AUTHORING-UX-01` remains future work. The validation workflow does
not solve visual template authoring UX, complex template readability,
LibreOffice authoring ergonomics, automated visual-diff acceptance, semantic
interpretation of visual changes, or template-object authoring workflows.
Sample 10 remains an important candidate for future authoring/reference work;
other representative samples may be selected later.

None of these authoring, visual-reference or template-language design ideas
belong to the current ARCH-04 extraction work. ARCH-04 remains concerned with
architectural extraction and behavior preservation; no template-language
redesign is part of ARCH-04.

## Lifecycle APIs

### LIFECYCLE-API-01 — Clarify load / refresh / reset lifecycle semantics

- Priority: Medium
- Status: API / lifecycle clarification

Current `refresh()` persists the current DOM state into the workspace and
immediately calls `load()`, while `load()` resets the workspace from the
original source template. The persisted workspace state is therefore
discarded. This behavior predates ARCH-02 and is intentionally preserved
for compatibility.

Future work should evaluate explicit semantics for reloading or reparsing
the current workspace separately from resetting from the source template.
No replacement API is defined or implemented by this roadmap entry.

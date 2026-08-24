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

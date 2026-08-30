# ROADMAP-REFRESH-01 — Post PRODUCT-01 / SECTION-03 Reconciliation

## Purpose

This documentation-only slice reconciles the strategic roadmap and future-development backlog with the repository state after the completed PRODUCT-01 / SECTION-03 structured-document milestone.

## Why the refresh is required

The previous roadmap still treated ARCH-07 as the effective architectural baseline and listed `DOCUMENT-DEFAULTS-01` as the immediate next milestone. It also described sections primarily as future document-structure work and described template-format preservation from a baseline that predates the completed TEMPLATE-STRUCTURE work.

Those descriptions no longer match the implemented `develop` line.

## Reconciled baseline

The refreshed planning documents recognize the completed capabilities around:

- addressable native template structures;
- named sections and bookmarks;
- exact section cloning;
- deterministic identity rewriting;
- data-bound and nested section instantiation;
- collection lifecycle and prototype finalization;
- template-structure inspection;
- safe normalization;
- structure-preserving scalar expression replacement;
- authored whitespace preservation;
- the complete CV showcase and practical authoring guidance.

## Planning decisions

### STYLE-CONTEXT-01 before DOCUMENT-DEFAULTS-01

`STYLE-CONTEXT-01` is now the preferred next architecture block.

Reason: document defaults should have a document-scoped owner and should not be implemented on top of process-wide mutable style-registration state.

This is a sequencing decision only. It does not define the eventual public API for style contexts or document defaults.

### Template format preservation requires re-audit

`TEMPLATE-FORMAT-PRESERVATION-01` remains relevant, but its original broad framing is stale after TEMPLATE-STRUCTURE.

Future work must first identify the remaining gaps in conditions, foreach/control structures, structural placeholders, and complex ODF boundaries rather than reopening solved scalar-expression behavior.

### Sections are an implemented capability

Future `DOC-STRUCTURE-03` work should focus on page-flow semantics such as page breaks, keep-with-next, keep-together, and page-style transitions. Further section work must extend the established section model rather than restart section discovery.

### Named object operations are a future research direction

The structured section model suggests future object-specific operations for other named native ODF objects.

The roadmap distinguishes:

- replace content;
- replace object;
- clone;
- remove.

No universal `replaceElementByName()` API is approved. Typed targets and object-specific capabilities remain the preferred design direction until evidence supports something broader.

### Dynamic graphics are a use case, not yet a core architecture

Charts/graphs, QR codes, generated images, and similar content are recorded as potential dynamic content for named template objects.

For charts, SVG, PNG, and native ODF chart structures remain open alternatives. Native ODF chart work must begin with inspection of real LibreOffice-authored documents and interoperability behavior.

## Scope

This slice changes documentation only.

It does not:

- change production PHP code;
- introduce a public API;
- implement style context or document defaults;
- implement named-object operations;
- implement chart generation;
- change sample outputs;
- change compatibility behavior.

## Result

After this refresh, `ROADMAP.md` defines the strategic sequence from the actual post-PRODUCT-01 / SECTION-03 baseline, while `FUTURE_DEVELOPMENT.md` records the corresponding issue-oriented backlog without presenting research directions as approved APIs.

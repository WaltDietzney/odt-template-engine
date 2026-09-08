# STYLE-API-02G — Writer Boundary Cleanup

Status: **ACCEPTED CHANGE CONTRACT — SEMANTICS BEFORE IMPLEMENTATION**
Baseline: `develop` at `8d69f62af2b30ceb414b4ba5e7ffd5c25dd90b1a`
Branch: `architecture/style-api-02g-writer-boundary-cleanup`

## Problem

`StyleWriter` currently mixes ODF serialization with discovery of process-wide
StyleMapper compatibility registries. It also has process-wide duplicate and
font caches. This makes the writer a second style repository and leaves legacy
assign/render graphics dependent on global state even though normal structured
insertion is document-local.

## Current evidence

- `writeAllStyles()` reads legacy text, paragraph, graphic, table-cell and
  table registries, maps paragraph options, filters names, and serializes them.
- `writeTextStyles()` and `writeFontFaces()` are specialized legacy-only paths
  using static `$generatedTextStyles` and `$fontsUsed`; no production caller
  uses them.
- `writeColumnStyles()` receives explicit widths and writes directly to the
  supplied content DOM; `RichTable` is its only production caller.
- `OdtTemplate::save()` calls `writeAllStyles()` only to finalize compatibility
  state. Semantic requirements and font/fill materializers already own normal
  document output.
- The legacy assign/render path currently registers frame, image and fill-image
  requirements through `StyleMapper` and later adopts them during save.
- `StyleContext` already has document-local frame, image and fill-image
  requirement stores and materialization support.
- `StyleContext` paragraph/text fallback through `LegacyStyleRegistry` and the
  retained text compatibility facade remains a separate reference-resolution
  compatibility concern.

## Target boundary

```text
StyleMapper       maps options and generates identities
StyleContext      owns current-document semantic requirements
materializers     materialize explicit document-local requirements
StyleWriter       serializes explicit DOM data only
```

`StyleWriter` must not discover application ownership, query process-global
style registries, filter the current document from global state, or retain
process-global writer memory.

## Compatibility decisions

### Direct StyleMapper → StyleWriter registration

The direct registry-to-writer ownership path is retired in this milestone.
The 02F P0 tests remain historical evidence and are changed into explicit
retirement expectations. The retained StyleMapper registration methods whose
only purpose was direct writer compatibility are removed with their carrier
state.

### Legacy assign/render graphics

The path remains supported, including frame, image and fill-image output, but
its requirements are registered into the current `StyleContext` during
materialization. It no longer uses process-global StyleMapper graphic state.
Repeated render/save and document isolation remain required behavior.

### LegacyStyleCompatibilityState

All six families are removed: text, table-cell, image, fill-image, frame and
table. No replacement global carrier is introduced.

### LegacyStyleRegistry and StyleContext fallback

`LegacyStyleRegistry` remains only for narrow paragraph/text
reference-resolution compatibility. `StyleWriter` no longer depends on it.
The existing paragraph/text fallback semantics are not redesigned in this
milestone.

### Writer methods

- `writeAllStyles()` is removed as a registry-discovery writer.
- `writeTextStyles()` and `writeFontFaces()` are removed as dead specialized
  registry/cache paths.
- `writeColumnStyles()` remains because it serializes explicit column data
  directly into the target DOM and is not a registry owner.
- duplicate checks are performed by semantic materializers or the target DOM,
  not process-global writer state.

### Mapping

Mapping is removed from writer finalization. Paragraph/text mapping remains in
the existing semantic or compatibility resolution paths before serialization.

## Non-goals

- general STYLE-API-02H legacy getter/facade cleanup;
- STYLE-API-02I documentation/API closeout;
- table layout redesign;
- graphic semantic redesign;
- template language or assign/render lifecycle redesign;
- StyleContext public exposure;
- new registries or a generic StyleManager;
- removal of `LegacyStyleRegistry` itself;
- removal of `writeColumnStyles()`;
- unrelated StyleMapper mapping cleanup.

## Exit criteria

1. `StyleWriter` has no mutable static state and no registry-reading methods.
2. No normal or legacy structured production path requires
   `LegacyStyleCompatibilityState`.
3. Normal paragraph/text, table/cell, graphic/image/fill-image output remains
   document-local and semantically materialized.
4. Legacy assign/render graphics retain their observable ODF output through
   the current document context.
5. Direct StyleMapper registry-to-StyleWriter behavior is intentionally removed
   and explicitly tested as a breaking compatibility decision.
6. `writeColumnStyles()` continues to produce explicit table-column styles.
7. Repeated save/render and multiple-template isolation tests pass.
8. No replacement process-global ownership mechanism is introduced.

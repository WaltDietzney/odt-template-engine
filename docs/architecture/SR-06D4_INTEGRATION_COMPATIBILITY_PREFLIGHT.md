# SR-06D.4 — Integration / Compatibility Preflight

Status: integration and compatibility preflight slice

Branch: `architecture/sr-06d4-integration-compatibility-preflight`

Depends on:

- `SR-06D1_GRAPHIC_RESOLUTION_CONTRACT.md`
- `SR-06D2_GRAPHIC_STYLE_MATERIALIZER.md`
- `SR-06D3_SEMANTIC_GRAPHIC_AUTHORITY.md`
- `SR-06C5_INTEGRATION_COMPATIBILITY_PREFLIGHT.md`

## 1. Purpose

SR-06D.4 closes the D-series by validating the complete semantic graphic path as one integrated document lifecycle.

No new graphic semantics are introduced in this slice. The purpose is to prove that the contracts established in D.1-D.3 compose correctly through the public `OdtTemplate::setElement()` path and persisted ODT package output.

The integrated path is:

```text
producer
  ↓
semantic StyleRequirement
  ↓
StyleContext resolution
  ↓
semantic materialization
  ↓
structured DOM materialization
  ↓
semantic or bounded compatibility draw:style-name authority
  ↓
legacy compatibility registration where still required
  ↓
save / reload lifecycle
```

## 2. D-series contract under test

D.4 validates the following combined guarantees.

### D.1 resolution

- semantic `graphic` requirements use the generic document-local resolution model;
- existing target-document definitions are authoritative;
- semantic document-local definitions resolve references;
- legacy frame/image registries are not semantic resolution sources.

### D.2 materialization

- semantic `graphic` definitions are written as native `style:style` elements;
- `style:family="graphic"` and native `style:graphic-properties` are preserved;
- duplicate authored definitions are not overwritten;
- materialization is idempotent.

### D.3 rendered authority

- DrawTextBox references the semantic graphic style when no unmigrated legacy carrier is required;
- DrawTextBox retains its legacy style carrier when layout/flow/unclassified properties still depend on it;
- nested DrawTextBox elements receive the same semantic authority treatment;
- CircularImage semantic graphic identity remains directly referenced;
- normal ImageElement remains on its characterized legacy-only style path.

## 3. Compatibility model

D.4 explicitly validates coexistence rather than premature cleanup.

During this transition, a saved ODT may legitimately contain both:

- the semantic graphic style used by the rendered object;
- an unused or compatibility-only legacy frame/image style still registered by the existing finalization path.

That is not a D-series defect. Removal of redundant legacy graphic state belongs to SR-06F.

Likewise, CircularImage still depends on the legacy fill-image declaration path even though its graphic style is semantic. Fill-image dependency ownership belongs to SR-06E.

## 4. Required integrated cases

The D.4 preflight covers four representative end-to-end cases.

### 4.1 Direct semantic DrawTextBox authority

A DrawTextBox with appearance plus directly emitted geometry must:

- produce a semantic graphic definition;
- persist that semantic style in `styles.xml`;
- reference that semantic style from `content.xml`;
- preserve the current legacy frame-style state without making it the rendered appearance authority.

### 4.2 Compatibility carrier DrawTextBox

A DrawTextBox using a still-unmigrated property such as `allow-overlap` must:

- still produce/materialize its semantic appearance definition;
- continue to reference the legacy frame style in `content.xml`;
- persist the legacy carrier containing the unmigrated property;
- not silently drop appearance or compatibility behavior.

### 4.3 CircularImage transition

CircularImage must:

- persist and reference its semantic graphic style;
- retain the fill-image declaration required by the bitmap fill;
- retain the package image resource and manifest entry.

This validates the intended D-to-E boundary.

### 4.4 Normal ImageElement compatibility

Normal ImageElement must remain unchanged by D:

- no semantic graphic definition is invented;
- its legacy image-style path continues to persist;
- its drawing structure and package resource remain valid.

## 5. Lifecycle and repeatability

The D-series must remain document-local and repeatable.

Existing lifecycle coverage remains authoritative for:

- `load()` resetting semantic and legacy graphic state;
- independent document contexts not sharing semantic definitions;
- repeated style materialization remaining idempotent.

D.4 does not duplicate every lower-level D.1/D.2 lifecycle assertion. Instead it combines them with persisted-package integration coverage and requires all focused D-series tests to remain green.

## 6. Existing-document authority

Existing target-document authority remains part of the D.4 gate through the D.1 and D.2 focused suites.

D.4 does not create a second integration-specific resolution algorithm or inject authored styles into every end-to-end fixture merely to duplicate lower-level coverage.

The composition rule remains:

> Resolution determines authority; materialization preserves it; structured rendering references the resolved semantic identity where D.3 permits semantic authority.

## 7. Explicit non-goals

SR-06D.4 does not:

- modify production code unless the preflight exposes a real D-series integration defect;
- remove legacy frame/image/fill-image state;
- migrate fill-image dependencies;
- alter normal ImageElement semantic classification;
- migrate frame layout/flow semantics;
- redesign `OdtTemplate::setElement()`;
- redesign `StyleMapper` or `StyleWriter`;
- fix CircularImage rendering;
- begin SR-06E/F or SR-07;
- perform the final visual LibreOffice regression gate reserved for SR-06G.

## 8. Automated preflight gate

Before D.4 receives FINAL GO, the following must pass:

1. `Sr06DGraphicIntegrationCompatibilityPreflightTest`;
2. `GraphicStyleResolutionCharacterizationTest`;
3. `GraphicStyleRequirementMaterializerTest`;
4. `DrawTextBoxSemanticGraphicProducerTest`;
5. `CircularImageElementSemanticGraphicProducerTest`;
6. `ImageElementSemanticGraphicProducerTest`;
7. `StyleContextNestedGraphicStyleCompatibilityTest`;
8. `StyleContextGraphicDrawingBoundaryCharacterizationTest`;
9. `Sr06CGraphicProducerCompatibilityPreflightTest`;
10. full `composer test`;
11. PHP lint for changed tests;
12. `git diff --check`.

The known unrelated PHPUnit deprecation remains non-blocking if unchanged and no new warning/deprecation is introduced by D.4.

## 9. D-series exit condition

SR-06D is complete when D.4 demonstrates that:

- semantic graphic resolution is document-local and authoritative;
- semantic graphic definitions materialize natively;
- rendered structured drawing objects use semantic graphic identity wherever the classified boundary permits it;
- compatibility carriers remain functional where still required;
- CircularImage retains its explicit D-to-E fill-image dependency boundary;
- normal ImageElement remains backward-compatible;
- saved ODT packages preserve required styles and resources;
- the full automated suite remains green.

At that point the next architecture slice is:

```text
SR-06E — Fill-Image Dependencies
```

The final SR-06 GO still remains gated by SR-06F compatibility closeout and the mandatory SR-06G visual LibreOffice regression preflight.

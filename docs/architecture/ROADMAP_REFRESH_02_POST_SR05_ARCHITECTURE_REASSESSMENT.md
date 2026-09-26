# ROADMAP-REFRESH-02 — Post SR-05 Architecture Reassessment

## Status

This document records the architecture reassessment performed after the integration of the D5C–D5E ownership/resource work and SR-01 through SR-05 semantic style-requirement work into `develop`.

It is a planning and architecture decision document. It does not approve a new public API and does not implement SR-06, SR-07, D5F, document defaults, frame layout, or table layout.

The reassessment exists because the previous roadmap still described `STYLE-CONTEXT-01` as the preferred next architecture block, while the implementation baseline has moved substantially beyond that state.

## 1. Evidence baseline

This reassessment is based on the current `develop` architecture and the following existing project decisions and research:

- `STYLE-CONTEXT-01FD5_COMPOSITE_MATERIALIZATION_CONTRACT.md`;
- `ODF_LIBREOFFICE_SEMANTIC_REFERENCE_MATRIX.md`;
- `ODF_LIBREOFFICE_PHASE1_RESEARCH_FINDINGS.md`;
- `ODF_DOCUMENT_MATERIALIZATION_MODEL.md`;
- `ODF_DOCUMENT_MODEL_ENGINE_GAP_ANALYSIS.md`;
- `STYLE_REQUIREMENT_CHANGE_CONTRACT.md`;
- `STYLE_REQUIREMENT_SR05_FONT_DEPENDENCIES_CHANGE_CONTRACT.md`;
- the current `OdtTemplate::setElement()` orchestration;
- the current `StyleRequirementCollector` semantic and compatibility projections.

The ODF specification remains normative. LibreOffice-authored reference documents remain empirical evidence. Current engine output is not used as the definition of correct ODF semantics.

## 2. Reassessment result

The central conclusion is:

> D5's semantic ownership direction remains valid. The remaining architecture gap is the incomplete migration of style/dependency families to the semantic requirement model, not the ownership traversal itself.

D5 established the correct high-level separation:

```text
semantic ownership tree
        |
        +-- semantic dependency projection
        +-- physical resource projection
        +-- native content materialization
```

The ODF research did not invalidate this model. It showed that the original style-requirement payload was semantically too weak. SR-01 through SR-05 have since enriched that channel for the migrated families.

## 3. Current completed architecture baseline

The following are no longer future STYLE-CONTEXT goals; they are part of the current architecture baseline:

- document-local style ownership through `OdtDocumentContext` / `StyleContext`;
- one semantic structured-element ownership view through `ownedElements()`;
- transitive requirement discovery without concrete-type traversal in `OdtTemplate`;
- transitive physical image-resource discovery and package-owned preparation;
- conflict-preserving requirement collection;
- semantic `StyleRequirement` values;
- definition versus reference semantics;
- ODF style family;
- common versus automatic style scope;
- owning document part (`content.xml` / `styles.xml`) where required;
- parent-style dependencies;
- typed ODF property groups;
- semantic paragraph/text requirement production and materialization;
- preservation of already-native ODF style properties;
- document-local font-face dependency discovery, resolution, conflict handling, and materialization;
- compatibility bridges for legacy style paths where migration is not complete.

Therefore `STYLE-CONTEXT-01` must no longer be described as work that has not started.

## 4. D5 status

### D5C–D5E

D5C–D5E are accepted architecture baseline:

- D5C established the semantic ownership capability;
- D5D established conflict-preserving transitive style requirement discovery;
- D5E established transitive physical resource discovery and package preparation.

These decisions remain in force.

### D5F

D5F remains deliberately paused.

The reason has changed as the architecture progressed. The earlier blocker was that the flattened style requirement model could not carry enough ODF semantics for safe lifecycle unification. SR-01 through SR-05 have resolved that problem for the migrated paragraph/text/font path.

The current blocker is that structured insertion still contains two requirement worlds:

```text
semantic requirement path
    paragraph / text
    + font dependencies

legacy compatibility requirement path
    graphic/frame/image/fill-image
    + remaining non-migrated style families
```

`OdtTemplate::setElement()` therefore remains transitional orchestration. D5F must not normalize this transition state into a permanent lifecycle abstraction.

D5F may resume only after the remaining style families needed by structured insertion have been characterized and migrated or explicitly retained behind a bounded compatibility contract.

## 5. Next architecture slice: SR-06

### SR-06 — Semantic Graphic Style Requirements

SR-06 is the preferred next architecture slice.

Goal:

> Migrate graphic-style definitions and references used by structured elements from engine-role legacy buckets into the semantic `StyleRequirement` model without changing frame-layout behavior or public layout APIs.

The important semantic distinction is:

```text
draw:frame
    |
    +-- structural frame semantics
    |     anchor / size / position / name / relation
    |
    +-- graphic style reference
    |     draw:style-name
    |          -> style:style family="graphic"
    |          -> style:graphic-properties
    |
    +-- contained content/resource
          draw:image / text box / other drawing content
```

Historical engine categories such as `frame` and `image` may describe producer or usage roles, but they must not be treated as ODF style families when the native family is `graphic`.

### SR-06 boundaries

SR-06 must not:

- redesign frame positioning;
- introduce `FRAME-LAYOUT-01` APIs;
- redesign image anchor/wrap APIs;
- move physical image resources into `StyleContext`;
- merge package resource handling with style materialization;
- fix unrelated rendering defects such as CircularImage behavior;
- remove legacy compatibility entry points without characterization.

SR-06 should begin with audit and characterization of current graphic/frame/image/fill-image requirement producers and their actual LibreOffice/ODF placement semantics, followed by a Change Contract before implementation.

## 6. Following slice: SR-07

### SR-07 — Semantic Table / Table-Cell Requirements

After SR-06, the next preferred style migration is the table family.

The semantic requirement model already has the vocabulary needed to distinguish ODF families and property groups. SR-07 should investigate and migrate the currently relevant table-related definitions, especially table-cell semantics, without simultaneously inventing new table geometry APIs.

Likely semantic families include:

- `table`;
- `table-column`;
- `table-row`;
- `table-cell`.

SR-07 must preserve the established rule that style family and property group are independent concepts.

`TABLE-LAYOUT-*` and `TABLE-CELL-01` remain separate product/layout work. SR-07 may prepare their architecture but must not silently absorb their behavior changes.

## 7. D5F and D5G after semantic family migration

The preferred completion sequence is now:

```text
SR-06 Graphic Requirements
        |
        v
SR-07 Table / Table-Cell Requirements
        |
        v
D5F Lifecycle / Materialization Integration
        |
        v
D5G Compatibility Closeout
        |
        v
STYLE-CONTEXT-01 final closeout
```

D5F should then simplify lifecycle/orchestration around an already coherent semantic model rather than building abstractions around a mixed semantic/legacy transition state.

D5G should explicitly review compatibility paths, protected extension surfaces, repeated render/save behavior, and remaining legacy registration/finalization behavior before STYLE-CONTEXT-01 is declared fully closed.

## 8. Document defaults reassessment

`DOCUMENT-DEFAULTS-01` remains important but is no longer the immediate implementation step after document-local style ownership.

The ODF/LibreOffice font research shows that the phrase "document default" can refer to distinct mechanisms, including:

- ODF `style:default-style` semantics;
- LibreOffice's Default Paragraph Style / `Standard` style;
- authored named base styles;
- application-level LibreOffice basic-font defaults;
- page-layout defaults.

These mechanisms must not be collapsed into one API merely because they appear as "defaults" to an application developer.

Therefore `DOCUMENT-DEFAULTS-01` is reclassified as a research/design milestone after STYLE-CONTEXT closeout. Its public API remains undecided.

The intended user-facing goal remains valid: applications should eventually be able to express appropriate document-wide defaults without repeating the same options on every element. The native ODF mechanism used for each category must first be established.

## 9. FONT-02 / FONT-03 reference reconciliation

The SR-05 font-dependency contract cites:

- FONT-02 — document base/default font behavior;
- FONT-03 — base/default font with a style override.

During repository cleanup, corresponding locally created LibreOffice reference ODTs were found outside version control. They are not currently part of the repository reference-fixture baseline.

This is a source-of-truth inconsistency: an architecture contract cites empirical cases whose binary reference fixtures are not currently versioned with the reference matrix.

A separate bounded documentation/reference-fixture follow-up should determine their provenance, inspect their actual ODF structures, align their case descriptions with the reference matrix, and only then decide whether to add the fixtures to the repository.

This reconciliation is not part of SR-06 implementation and must not be used to smuggle a document-default API into SR-05.

## 10. Layout roadmap after STYLE-CONTEXT closeout

`FRAME-LAYOUT-01` and table-layout work remain high-value capabilities, especially for professional CV and application-document templates.

Their sequencing is refined rather than rejected:

```text
semantic graphic requirement model
        -> FRAME-LAYOUT-01 research / contract

semantic table requirement model
        -> TABLE-LAYOUT / TABLE-CELL research / contracts
```

This reduces the risk that new layout APIs are built on legacy style buckets or accidental LibreOffice behavior.

## 11. Boundaries that remain correct

The reassessment explicitly preserves these architecture decisions:

1. `OdtElement` or an explicitly element-associated collaborator remains the owner of native ODF content semantics.
2. `ownedElements()` remains the authoritative semantic ownership projection.
3. `OdtTemplate` remains an orchestration facade, not a concrete-element renderer.
4. `OdtDocumentContext` remains the document-local state boundary and must not become a God context.
5. `StyleContext` remains the document-local owner of style requirement state; its existence is not reopened by this reassessment.
6. Physical resources remain package concerns, separate from style requirements.
7. `OdtPackage` remains responsible for package/workspace lifecycle, physical resource preparation, manifest synchronization, and ZIP finalization.
8. Existing page/master mutation through `PageLayoutManager` remains separate where its semantics are already explicit.
9. Compatibility paths remain compatibility paths and must not silently define the target architecture.
10. Refactoring and behavior changes should remain separate wherever practical.

## 12. Revised strategic sequence

The preferred architecture sequence after this reassessment is:

```text
Current develop baseline
        |
        v
SR-06 Semantic Graphic Style Requirements
        |
        v
SR-07 Semantic Table / Table-Cell Requirements
        |
        v
D5F Lifecycle / Materialization Integration
        |
        v
D5G Compatibility Closeout
        |
        v
STYLE-CONTEXT-01 final closeout
        |
        +--> DOCUMENT-DEFAULTS-01 research/design
        |
        +--> FRAME-LAYOUT-01
        |
        +--> TABLE-LAYOUT-* / TABLE-CELL-01
        |
        +--> template authoring / format-preservation re-audit
        |
        +--> higher document structure and page flow
        |
        +--> named-object operations / dynamic content
        |
        +--> import / round-trip work later
```

The ordering after STYLE-CONTEXT closeout remains revisitable based on evidence and product needs.

## 13. Immediate next action

The next architecture action is **SR-06A — Graphic Requirement Audit and Characterization**.

Before any SR-06 implementation:

1. inspect all current graphic/frame/image/fill-image producers and consumers;
2. identify active, legacy, and compatibility paths;
3. compare current engine output with relevant LibreOffice reference structures;
4. distinguish structural frame attributes from graphic-style properties and physical resources;
5. add characterization tests for current compatibility behavior;
6. document an SR-06 Change Contract;
7. only then implement the smallest semantic migration slice.

D5F remains paused until this sequence and the corresponding SR-07 work make lifecycle unification semantically safe.

# D5F-E — Lifecycle Regression Closeout

Status: **FINAL GO — D5F lifecycle/materialization integration complete**

Base: `architecture/d5f-d-post-pass-narrowing` at `17325d4`

## 1. Scope and evidence

D5F-E is the regression closeout for the D5F lifecycle work. It does not
introduce a new lifecycle abstraction or alter production behavior. The
evidence combines the D5F-B characterization suite, the D5F-C orchestration
record, the D5F-D post-pass narrowing, focused structured-element tests, and
representative package comparisons between D5F-C and D5F-D.

The accepted lifecycle is:

```text
constructed OdtElement subtree
    -> semantic requirements, font dependencies,
       typed fill-image dependencies, physical resources
    -> document/package preparation
    -> semantic declaration/style materialization
    -> StructuredElementMaterializer::insert()
       -> element->toDomNode()
    -> bounded legacy compatibility adoption
       -> frame / image / fill-image
```

The semantic path is authoritative. Rendering-local mutations and legacy
compatibility state are deliberately separate concerns.

## 2. D5F sequence

* D5F-A audited the mixed pre-/post-materialization lifecycle.
* D5F-B characterized semantic, legacy, dependency, and resource state before
  and after `toDomNode()`.
* The D5F Change Contract authorized an explicit pre-materialization semantic
  path without removing compatibility behavior.
* D5F-C made the phases explicit in `OdtTemplate` and preserved protected
  callback dispatch.
* D5F-D removed redundant paragraph/text actions from the post-pass while
  retaining graphic compatibility adoption.
* D5F-E verifies the resulting invariants and records the handoff to D5G.

## 3. Final lifecycle invariants

### Semantic PRE path

The following semantic producers are complete before native materialization:

| Producer | Semantic PRE state | Post semantic discovery | Result |
| --- | --- | --- | --- |
| Paragraph / text spans | paragraph and text requirements | none observed | stable |
| RichText | owned paragraph/text requirements | none observed | stable |
| ListElement | owned list-item requirements | none observed | stable |
| DrawTextBox | graphic requirement | none observed | stable |
| CircularImageElement | graphic requirement | none observed | stable |
| RichTable | table, column, row, and cell requirements | none observed | stable |

ImageElement currently has no semantic graphic producer; its semantic collector
is empty both before and after rendering. This is an explicit current boundary,
not a missing post-discovery pass.

### Typed dependencies and physical resources

`FillImageRequirementCollector` returns CircularImageElement's typed
fill-image dependency before rendering and returns the same requirement after
rendering. `StructuredResourceCollector` likewise discovers direct and nested
physical image assets before native DOM insertion. Fill-image declarations are
document-owned; bitmap copying remains an `OdtPackage` responsibility.

### Document ownership

Style and requirement state remains document-local in `OdtDocumentContext` and
`StyleContext`. Physical resources remain package-owned. The lifecycle
characterization covers independent templates and shows no cross-document
semantic style, resource, or image-reference leak. No current-document global
pointer or lifecycle-global registry was introduced.

### Materializer boundary

`StructuredElementMaterializer` remains responsible only for placeholder
normalization, native subtree insertion/replacement, and dispatch through the
existing facade callbacks. It does not own styles, resources, fonts,
fill-image dependencies, or lifecycle policy.

### Bounded POST compatibility

`finalizeStructuredCompatibility()` invokes the legacy collector only to adopt
the remaining graphic compatibility families:

```text
frame       -> legacy frame carrier
image       -> post-render ImageElement state
fill-image  -> CircularImageElement legacy state
```

Paragraph and text are no longer re-registered after materialization. Their
compatibility registration and `ensureParagraphStylesExist()` /
`ensureTextStylesExist()` handling remain in the pre-materialization phase.
The remaining post-pass is compatibility adoption, not semantic discovery.

## 4. Producer matrix

| Producer | Semantic PRE | Typed dependency PRE | Physical resource PRE | Rendering-local mutation | POST compatibility |
| --- | --- | --- | --- | --- | --- |
| Paragraph | paragraph/text complete | none | none | none relevant | none |
| RichText | owned children complete | none | nested assets complete | none relevant | none |
| ListElement | owned list content complete | none | nested assets complete | none relevant | none |
| ImageElement | no semantic graphic producer | none | image asset complete | derived wrap/position values copied into `imageOptions` | image definition adoption retained |
| CircularImageElement | graphic complete | fill-image complete | image asset complete | legacy fill/style fields populated | fill-image/graphic legacy adoption retained |
| DrawTextBox | graphic requirement complete | none | none | native frame structure only | frame carrier retained |
| RichTable | table/column/row/cell complete | none unless nested image | nested assets complete | structural DOM only | existing compatibility fallbacks retained |

The ImageElement style identity is stable across repeated `toDomNode()` calls;
the legacy definition changes only because deterministic wrap and placement
values are synchronized into its options. CircularImageElement's semantic
requirements do not appear after rendering; only `$fillImageName`,
`$circularStyleName`, and `$circularStyleOptions` become available to legacy
getters.

## 5. Regression evidence

The D5F lifecycle characterization suite passes with 15 tests and 108
assertions. It covers ImageElement alignment/position variants and repeated
materialization, semantic collector stability, nested resource discovery,
typed fill-image discovery, setElement/save, repeated save, and independent
documents.

The broader D5F structured/compatibility regression set passes with 172 tests
and 1,343 assertions. The complete Composer suite passes with 562 tests and
3,474 assertions. The repository's existing PHPUnit warning and deprecation
are recorded in the validation report; no new failure was introduced.

Representative ODT package comparisons between D5F-C and D5F-D for Paragraph,
ImageElement, CircularImageElement, DrawTextBox, and RichTable found identical
SHA-256 values for `content.xml`, `styles.xml`, and
`META-INF/manifest.xml`. Picture entries were unchanged. Consequently no new
LibreOffice regression round was required for D5F-E; the previously accepted
SR-07 visual evidence remains applicable.

## 6. Compatibility and D5G handoff

The following remain intentionally available and unchanged: the legacy
`StyleRequirementCollector::collect()` projection, `HasStyles`, StyleMapper
and StyleWriter compatibility paths, protected facade hooks,
`assign()`/`render()`, save/finalization bridges, ImageElement mutation,
CircularImageElement legacy state, and DrawTextBox frame carriers.

D5G remains the place for any policy decision about those APIs, static legacy
registries, repeated render/save compatibility, and further narrowing of
legacy carriers. D5F does not claim that compatibility closeout is complete.

The SR-07 row behavior now included in the validated baseline is intentionally
narrow: `addRow(..., ['min-row-height' => '...'])` produces an automatic
content-local `table-row` semantic requirement with
`style:min-row-height`; unsupported row keys remain ignored.

## 7. Final decision

No semantic producer was found that requires post-materialization discovery.
The only post-render changes are deterministic ImageElement compatibility
synchronization and CircularImageElement legacy state adoption, plus the
deliberately retained frame carrier boundary. The authoritative PRE path and
bounded POST compatibility phase are therefore accepted.

**FINAL GO — D5F is complete. D5G Compatibility Closeout is next.**


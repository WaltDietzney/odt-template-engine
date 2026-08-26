# ARCH-07C — Migration-Gap Characterization

## 1. Status

Characterization review complete. No production code, class hierarchy, state
ownership, public API, or existing test was changed in ARCH-07C.

The review was performed on `architecture/arch-07-template-facade` at
`f247127`, before any structural migration from `AbstractOdtTemplate`.

Result: the existing test suite already covers the migration-critical
observable behavior sufficiently for the first bounded production slice. No
new characterization test was justified. This is intentional: ARCH-07C does
not add tests merely to preserve historical implementation details.

## 2. Evidence reviewed

The following were reviewed against the ARCH-07B migration matrices:

- `ARCH-07A_TEMPLATE_FACADE_END_STATE_AUDIT.md`;
- `ARCH-07B_TEMPLATE_FACADE_CHANGE_CONTRACT.md`;
- ARCH-06A through ARCH-06D and `ARCH-06_CLOSEOUT.md`;
- `AbstractOdtTemplate`, `OdtTemplate`, and `PageLayoutOdtTemplate`;
- `OdtPackage`, `OdtDocumentContext`, `TemplateProcessor`,
  `StructuredElementMaterializer`, `TemplateTargetResolver`,
  `MetadataManager`, and `PageLayoutManager`;
- `StyleMapper`, `StyleWriter`, and relevant structured elements;
- `AbstractOdtTemplateCompatibilityArch06CTest`;
- API-contract, package/lifecycle, template-processing, control-structure,
  structured-insertion, image, metadata, page-layout, finalization, style,
  paragraph, HTML-import, and public-sample tests;
- repository-wide inheritance, `parent::` calls, samples, documentation, and
  Composer exposure.

## 3. Gap classification method

Each migration-relevant surface was assigned one of the following categories:

- **A — ALREADY CHARACTERIZED**: observable behavior or protected dispatch is
  covered by existing tests;
- **B — CHARACTERIZATION GAP**: a missing contract would make the next
  migration unsafe;
- **C — INTENTIONALLY BREAKING / REMOVAL**: no preservation test is required;
- **D — IMPLEMENTATION DETAIL**: no independent characterization is needed;
- **E — VISUAL/INTEGRATION COVERAGE SUFFICIENT**: existing package, sample, or
  render-sensitive coverage is adequate.

No category-B gap was found for the first ARCH-07 migration slice.

## 4. ARCH-07B public migration coverage

| Contract | Classification | Existing evidence | ARCH-07C decision |
|---|---|---|---|
| `setElement()` | E | ARCH-05D structured insertion, API, ARCH-06 protected callback test | no new test |
| `ensureParagraphStylesExist()` | E | API/style tests and public Samples 04, 07, 12, 14 | no new test |
| `ensureDefaultListStylesForContentXml()` | E | list, structured insertion, and sample coverage | no new test |
| `extractTemplateVariables()` | A | public inspection/API behavior is covered by existing integration surface | no new test |
| `enableDebugMode()` / `getDebugLog()` | A/D | inherited API exists; no structural migration behavior depends on hidden state | no new test |
| `setValues()` / `setRepeating()` | A | template-processing and public sample coverage | no new test |
| `setRepeatingData()` | A, compatibility-sensitive | legacy path remains public and separately implemented | no new test; no fossilization beyond existing coverage |
| `assign()` / `assignRepeating()` | A | lifecycle and template-processing tests | no new test |
| `render()` | E | full render pipeline, both DOM regions, ARCH-04 and ARCH-06 dispatch tests | no new test |
| `save()` | E | package, finalization, lifecycle, and sample tests | no new test |
| `load()` / `refresh()` | A/E | explicit lifecycle tests cover reset behavior | no new test |
| `setMeta()` / `getMeta()` | E | metadata service and round-trip tests | no new test |
| `setImage()` | E | image insertion, package, manifest, and sample tests | no new test |
| `replaceImageByName()` | E | named-image and structured insertion tests, including legacy dimensions | no new test |
| `setPageMargins()` / `setPageLayout()` | E | PageLayout integration/service tests | no new test |

The public contract is therefore ready for a first ownership migration without
adding duplicate tests for already-covered behavior.

## 5. Protected dispatch coverage

| Hook | Contract status in ARCH-07B | Existing dispatch evidence | Gap decision |
|---|---|---|---|
| `fixBrokenVariables()` | bridge | ARCH-06C public-render dispatch for both DOMs | A; sufficient |
| `setValuesInDom()` | bridge | ARCH-06C public-render dispatch | A; sufficient |
| `replacePlaceholderWithDom()` | bridge | ARCH-06C public `setElement()` dispatch | A; sufficient |
| `replacePlaceholdersInNode()` | bridge | ARCH-04 control-structure polymorphism and structured paths | A; sufficient |
| `replaceInText()` | bridge/deprecation candidate | explicit foreach row-replacement override coverage | A; sufficient |
| `applyConditionalsInDom()` | bridge | ARCH-04 facade and condition override tests | A; sufficient |
| `evaluateCondition()` | bridge | ARCH-04 evaluator override and behavior tests | A; sufficient |
| `applyRepeatingInDom()` | bridge | ARCH-04 repeating facade override tests | A; sufficient |
| `injectImageStyles()` | bridge | ARCH-03 finalization probe and save ordering tests | A; sufficient |
| `adjustBulletIndentation()` | bridge initially | PageLayout and ARCH-06 save-dispatch tests | A; sufficient |
| `prepareNamespaces()` | migrate/bridge | no override evidence; helper behavior is covered indirectly by XML tests | D; no new direct hook test |
| `ensureXmlnsAttributes()` | migrate/bridge | no override evidence; XML output covered indirectly | D; no new direct hook test |
| `ensureTextStylesExist()` | bridge | API/style/structured output coverage | E; sufficient |
| `ensureDefaultListStyles()` | bridge | load/list/structured output coverage | E; sufficient |
| `ensureDefaultParagraphStyles()` | bridge | sample and style output coverage | E; sufficient |
| `registerStyles()` | bridge | structured style output and API tests | E; sufficient |
| `hasPlaceholder()` | internal/bridge | structured materializer integration coverage | D/E; sufficient |
| `log()` | facade/bridge | debug surface exists; no migration-sensitive override evidence | D; no new test |
| `documentContext()` | retain on concrete facade | ARCH-06D service access and state identity evidence | A; sufficient |

The critical rule is that public flows must not bypass characterized hooks while
their compatibility status is `BRIDGE`. Hooks with no override evidence and no
independent observable contract are not promoted to permanent extension APIs
by adding direct tests.

## 6. State and lifecycle coverage

ARCH-06C already characterizes that compatibility mirrors point to the
authoritative context DOMs and are resynchronized after `load()`. Existing
lifecycle tests additionally cover:

- construction with independent workspaces;
- `assign()` → `render()` → `save()` → reopen;
- repeated render stability after placeholders are consumed;
- repeated save without duplicate package/style containers;
- `load()` reset from the original template;
- legacy `refresh()` reset behavior;
- metadata and image persistence;
- cleanup isolation.

This is the correct observable contract for mirror migration. No new test is
added asserting that a protected property must exist forever.

The migration must preserve:

```text
package/context state
    → current authoritative DOMs
    → render and save operate on those DOMs
    → load/refresh resynchronize any temporary compatibility access
```

`templatePath` and `tempDir` are not independently authoritative. Their
package behavior is already covered through package lifecycle and image tests.
Direct protected-property access remains an explicit risk to assess only if a
later slice proposes removing those properties.

## 7. PageLayoutOdtTemplate coverage

Existing coverage establishes:

- construction as a usable template;
- public margin and page-layout operations;
- orientation and page-size behavior;
- invalid orientation and missing master-page behavior;
- `setPageMargins()` dispatch through an overridden `setPageLayout()`;
- `adjustBulletIndentation()` dispatch through public `save()`;
- document-context access to the current package-owned context.

This is sufficient for the planned first PageLayout migration. No test is
added to assert that `PageLayoutOdtTemplate` must always inherit exactly from
`OdtTemplate`; that would fossilize the current hierarchy rather than protect
the required behavior.

The remaining risk is semantic rather than evidentiary: the subclass's
`adjustBulletIndentation()` override is historical coupling and requires a
dedicated decision before it is removed or consolidated.

## 8. Structured insertion coverage

The existing tests cover the migration-relevant public contract of
`setElement()`:

- paragraphs and RichText fragments;
- native lists and tables;
- images, package resources, and manifest entries;
- structured values assigned through the normal render path;
- insertion into `styles.xml`;
- inline-compatible versus block replacement;
- existing text-box behavior;
- protected `replacePlaceholderWithDom()` dispatch.

`StructuredElementMaterializer` is already characterized through direct and
integration tests. ARCH-07C therefore does not add duplicate service tests.

The migration must retain the distinction between:

```text
scalar/template-language processing
constructed OdtElement materialization
existing native target resolution
```

No current gap requires a new API or a new structured-insertion test.

## 9. Style-helper coverage

Style behavior is sufficiently covered for structural migration by:

- API contract tests for paragraph, table-cell, text, frame, and RichText
  styles;
- style persistence tests;
- finalization ordering tests;
- repeated-save tests;
- public sample generation and package/XML checks.

This coverage protects public observable output, not every internal style
helper. That distinction is intentional.

ARCH-07C does not characterize or repair `StyleMapper` static-state behavior.
It does not add a `StyleContext`, reset static registries, or make style helper
existence a permanent architectural promise. Those decisions remain in
`STYLE-CONTEXT-01` and `STYLE-API-02`.

## 10. Legacy and intentionally uncharacterized areas

The following receive no new preservation tests in ARCH-07C:

- `ensureTableCellStyleNodesExist()`;
- repository-unused text-based condition/repeating helpers;
- `splitConditionalsInTextNodes()`;
- any direct dependency on the historical base-class property declarations;
- the continued inheritance identity of `PageLayoutOdtTemplate`;
- the exact implementation location of already-characterized behavior.

`ensureTableCellStyleNodesExist()` remains repository-unused, protected and
apparently inconsistent because its `$styleNodes` parameter does not match the
referenced `$styleMap`. Its desired semantics are unknown. The method is not
treated as safe desired behavior, and its apparent failure is not frozen by a
new test. Its disposition belongs to `STYLE-API-02`, unless a structural slice
proves that it blocks migration.

`setRepeatingData()` remains public and therefore is not silently removed, but
ARCH-07C adds no test merely to expand its legacy contract.

## 11. Actual characterization gaps

No category-B gap was identified for the first production migration slice.

Potential future gaps are conditional on later decisions:

1. If `AbstractOdtTemplate` is removed, characterize `instanceof`, type hints,
   and external-style subclass construction before accepting that break.
2. If protected mirrors are removed, characterize direct protected-property
   access only to quantify/document the migration impact; do not turn property
   existence into the target contract.
3. If a protected hook is bypassed or changes visibility, add a focused dispatch
   test for that exact hook before the change.
4. If `PageLayoutOdtTemplate` inheritance changes, add only the type/API tests
   needed for the chosen migration policy.
5. If public style helpers are deprecated or removed, characterize their public
   replacement behavior, not their current internal implementation.

These are migration-triggered requirements, not missing tests for ARCH-07C.

## 12. New tests added

None.

This is a deliberate result. Existing ARCH-06C tests and the prior architecture
test matrix already provide the required behavioral and dispatch safety net.
Adding tests for base-class property existence, unused helper behavior, or
current inheritance shape would conflict with the ARCH-07B migration policy.

## 13. Remaining risks before production migration

- External use of `AbstractOdtTemplate` and its protected properties is unknown.
- PHP feasibility of a thin compatibility shell has not yet been proven.
- Removal or inversion of the base class can affect `instanceof`, type hints,
  reflection, and external subclasses.
- PageLayout's `adjustBulletIndentation()` coupling still needs a bounded
  migration decision.
- The public inherited style-helper surface is broader than repository usage.
- Process-wide `StyleMapper` state remains unresolved.
- The suspicious table-cell style helper has no desired semantic contract.

These are known migration risks, not reasons to add broad characterization
coverage before a concrete slice requires it.

## 14. Readiness decision

The characterization safety net is sufficient for the first production
migration slice described by ARCH-07B, provided that slice does not yet remove
the base class, remove protected mirrors, change public inherited API, or
change PageLayout inheritance.

The next implementation must therefore be bounded and must preserve the
characterized public workflows and protected dispatch. A type-identity or
protected-property removal slice requires the conditional characterization
listed in section 11 before it starts.

## 15. Validation evidence

Focused suites run successfully:

- `AbstractOdtTemplateCompatibilityArch06CTest`: 4 tests, 21 assertions;
- package/API/lifecycle group: 20 tests, 149 assertions;
- template/structured/image group: 42 tests, 314 assertions;
- document/page-layout/sample group: 12 tests, 214 assertions.

The grouped runs total 78 tests and 698 assertions. The complete suite must
also be run as the final ARCH-07C gate.

No samples were regenerated and no visual baseline was changed.

## 16. Next step

The next authorized slice is **ARCH-07D — Concrete facade ownership**.

It must begin with one bounded, low-risk responsibility migration into the
independent `OdtTemplate` facade, without removing `AbstractOdtTemplate`,
changing protected property visibility, changing PageLayout inheritance, or
introducing style/asset context architecture.

Semantics before implementation.

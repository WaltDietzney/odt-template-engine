# ARCH-06C AbstractOdtTemplate Compatibility Characterization

**Status:** Characterization complete; no production-code change
**Milestone:** ARCH-06 — Reassess `AbstractOdtTemplate`
**Branch:** `architecture/arch-06-abstract-template`

## 1. Scope

ARCH-06C characterizes the compatibility behavior that a future
`AbstractOdtTemplate` facade/state refactoring must preserve. It focuses on
observable public flows and protected polymorphism rather than testing every
protected method by reflection.

No production behavior, API, abstract method contract or state ownership was
changed.

## 2. Existing coverage reused

The following existing tests already provide substantial characterization and
were deliberately reused rather than duplicated:

| Area | Existing evidence |
| --- | --- |
| Active template processing and repeated render | `TemplateProcessingArch04ATest` |
| Conditional and foreach protected polymorphism | `TemplateControlStructuresArch04B3Test` |
| Finalization hook order and repeated save | `DocumentFinalizationArch03CTest` |
| Page-layout facade behavior | `PageLayoutOdtTemplateTest` |
| Structured insertion and text-box paths | `StructuredInsertionArch05DTest` |
| Package/load/refresh lifecycle | `OdtTemplatePackageLifecycleTest` |
| Public structured/API compatibility | `ApiContractP0Test`, `ApiContractP1Test` |
| Named image and resource compatibility | ARCH-05G/05H integration tests |

## 3. Coverage/gap matrix

| Surface | Visibility | Existing coverage | Polymorphic relevance | State dependency | ARCH-06C addition |
| --- | --- | --- | --- | --- | --- |
| `setElement()` / structured insertion | public inherited | strong ARCH-05D/API coverage | protected replacement callback can be bypassed | content/styles DOM, package/styles | yes: facade callback dispatch |
| `extractTemplateVariables()` | public inherited | existing API/inspection coverage | low | content/styles DOM | no |
| debug mode/log | public/protected inherited | basic API surface exists | low | base debug state | no |
| `fixBrokenVariables()` | protected | indirectly used by ARCH-04/05 flows | high during render | content/styles DOM | yes: public render dispatch |
| `setValuesInDom()` | protected | behavior covered, dispatch gap | high during render | content/styles DOM/value stack | yes: both DOM passes |
| `replacePlaceholderWithDom()` | protected | direct structured behavior covered | high during `setElement()` | content/styles DOM | yes: public structured flow |
| `replacePlaceholdersInNode()` / `replaceInText()` | protected | ARCH-04B3 direct polymorphism coverage | high for foreach | cloned row DOM | no duplication |
| `injectImageStyles()` / finalization | protected | ARCH-03 finalization probe | high during save | styles/package | no duplication |
| `adjustBulletIndentation()` | protected | base finalization and PageLayout behavior | high; concrete subclass override | styles DOM | yes: subclass through save |
| `documentContext()` | protected on `OdtTemplate` | used by document-service tests | relevant to subclass services | package/context | yes: mirror identity |
| package/context mirrors | protected state | lifecycle behavior covered, identity gap | external subclasses may read them | package/context DOM | yes: identity before/after load |
| repeated render/save/load | public lifecycle | strong lifecycle coverage | indirectly facade-sensitive | package/context and DOM state | no new duplicate |
| `ensureTableCellStyleNodesExist()` | protected | no call site/test | externally possible | styles/content DOM | no; separate legacy follow-up |

## 4. New characterization added

Added:

`tests/Integration/AbstractOdtTemplateCompatibilityArch06CTest.php`

The four tests establish:

1. public `render()` dispatches through inherited protected
   `fixBrokenVariables()` and `setValuesInDom()` hooks for both content and
   styles DOMs;
2. public `setElement()` reaches the inherited protected
   `replacePlaceholderWithDom()` compatibility seam;
3. the historical `domContent`, `domStyles` and `domMeta` mirrors reference
   the same DOM instances as `OdtDocumentContext` before and after rendering,
   and are resynchronized after `load()` replaces the core documents;
4. a `PageLayoutOdtTemplate` subclass override of
   `adjustBulletIndentation()` is reached through public `save()` dispatch.

Result: **4 tests, 21 assertions**.

## 5. Protected polymorphic seams confirmed

The combined existing and new evidence confirms dynamic dispatch through
public operations for:

- conditional facade/evaluator hooks;
- foreach facade and row replacement hooks;
- filter/list/nl2br wrappers;
- finalization hooks during save;
- `PageLayoutOdtTemplate::adjustBulletIndentation()` during save;
- `fixBrokenVariables()` and `setValuesInDom()` during render;
- structured placeholder replacement during `setElement()`;
- page-layout delegation through the protected `documentContext()` seam.

These hooks must remain reachable through facade dispatch if future
implementation moves into composed services.

## 6. State/mirror behavior

The current observable relationship is:

```text
OdtPackage → OdtDocumentContext → authoritative DOM instances
                         ↓
          OdtTemplate compatibility mirrors
```

The new state probe confirms:

- construction synchronizes all three core DOM mirrors with the context;
- render mutates the same DOM instances rather than creating a second document
  state;
- `load()` replaces the context documents and synchronizes the mirrors to the
  new instances;
- content, styles and metadata remain aligned after reload.

The tests do not establish the protected properties as public API. They
protect only the compatibility relationship relevant to future state-access
refactoring.

## 7. Lifecycle behavior

Existing lifecycle tests confirm:

- construction and independent package workspaces;
- `assign()` → `render()` → `save()` → reopen;
- repeated `render()` stability after placeholders are consumed;
- repeated `save()` produces valid packages without duplicate font-face
  containers;
- `load()` resets from the original template;
- `refresh()` retains its historical reset behavior;
- metadata and image/package persistence remain compatible.

ARCH-06C did not add duplicate lifecycle scenarios. These existing tests are
the baseline for any future state-access or facade refactor.

## 8. `content.xml` and `styles.xml`

The current processing workflow acts on both core template DOMs. Existing
ARCH-04 tests characterize active processing in both regions where applicable;
ARCH-05D characterizes structured insertion in content and styles paths; and
the new render-dispatch test confirms `fixBrokenVariables()` and
`setValuesInDom()` are dispatched twice, once per DOM region.

No new region abstraction or document traversal was introduced.

## 9. PageLayoutOdtTemplate findings

`PageLayoutOdtTemplate` remains a real repository subclassing example.
`setPageMargins()` dynamically dispatches through `setPageLayout()`, and the
existing page-layout tests preserve that behavior.

The new test confirms that public `save()` dynamically reaches a subclass
override of `adjustBulletIndentation()`. The historical connection between
page layout and bullet indentation remains intact, even though it is an
unpleasant inheritance coupling. ARCH-06C does not repair or consolidate it.

## 10. Structured insertion and TemplateProcessor findings

ARCH-05D already provides the necessary structured insertion behavior baseline,
including Paragraph, RichText fragments, tables, images, structured assigned
values, styles.xml, inline/block replacement and text boxes. ARCH-06C adds
only the missing proof that the inherited protected replacement seam is
reached through public `setElement()`.

ARCH-04 tests already prove that active condition, evaluator, foreach,
row-replacement, filter, list and `nl2br` facade seams remain compatible.
ARCH-06C does not retest the template language.

## 11. Suspicious and dead legacy paths

`ensureTableCellStyleNodesExist(array $styleNodes)` still references the
undefined `$styleMap` variable in its implementation. Repository search found
no caller and no test. It is therefore classified as:

```text
repository-unused, externally possible through subclassing, likely stale/buggy
```

No characterization test was added because a test that invokes the undefined
path would risk freezing a failure as desired semantics without evidence that
the path is supported. This remains a separate style/API cleanup candidate.

Other legacy paths such as `setRepeatingData()` and text-based conditional or
repeating helpers remain outside this slice and retain their existing tests or
documented status.

## 12. Surprising behavior preserved

ARCH-06C deliberately preserves rather than improves:

- duplicated historical DOM/path properties on the facade/base hierarchy;
- the page-layout subclass's bullet-indentation coupling;
- inherited public style/helper methods with unclear long-term API status;
- legacy `refresh()` reset behavior;
- separate `setRepeatingData()` and active render repeating paths;
- unused or externally possible protected helpers;
- the undefined-variable table-cell style helper, which was not repaired.

## 13. Remaining characterization gaps

The following remain unresolved and should be addressed only when a concrete
refactoring requires them:

- external consumers subclassing `AbstractOdtTemplate` directly;
- external reads/overrides of protected DOM/path mirrors not represented in
  repository tests;
- intended public status of inherited style helpers;
- behavior expectations for the unused table-cell style helper;
- whether the PageLayout bullet-indentation override can ever be removed;
- a migration/deprecation policy for any eventual base-class reduction.

These gaps do not justify production changes in ARCH-06C.

## 14. Implications for ARCH-06D

ARCH-06D may investigate a narrow facade/state-access boundary, provided it:

- keeps `OdtPackage` and `OdtDocumentContext` authoritative;
- preserves compatibility mirrors until a migration path exists;
- keeps public signatures unchanged;
- preserves protected dynamic dispatch;
- uses the new tests and existing suites as a regression gate;
- does not add abstract methods or begin base-class removal.

ARCH-06D should begin only after reviewing this characterization result.

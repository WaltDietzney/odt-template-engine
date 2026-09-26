# ARCH-07I — Final Review / Documentation / Preflight

## 1. Scope

This document closes ARCH-07 after an independent review of the ARCH-07A
through ARCH-07H slices. It verifies the change contract, the resulting class
and ownership structure, compatibility decisions, automated validation,
package validation, and the completed local visual regression.

No production code was changed in ARCH-07I. The only implementation work
reviewed here is the already committed ARCH-07H base-class resolution.

## 2. ARCH-07A-H summary

ARCH-07 moved the project from a historically broad inheritance structure to a
composition-first facade in bounded steps:

- ARCH-07A audited the facade and evaluated the possible base-class end states;
- ARCH-07B defined the migration and compatibility contract;
- ARCH-07C confirmed the characterization coverage and migration gaps;
- ARCH-07D moved concrete structured-facade ownership to `OdtTemplate`;
- ARCH-07E moved processing/structured coordination to the concrete facade;
- ARCH-07F migrated internal state access to authoritative owners;
- ARCH-07G removed the PageLayout-specific finalization coupling;
- ARCH-07H removed `AbstractOdtTemplate` and completed the base-class
  resolution.

## 3. Change-contract verification

| Requirement | Evidence | Status |
|---|---|---|
| Composition-first `OdtTemplate` | `src/OdtTemplate.php` is standalone and composes package, document, processing, structured, metadata, target, and layout collaborators | PASS |
| No active broad `AbstractOdtTemplate` base | `src/AbstractOdtTemplate.php` is absent; no production reference remains | PASS |
| No artificial abstract methods | No replacement abstract contract was introduced | PASS |
| Public API migration | Core workflows, structured insertion, metadata, image, inspection, debug, style helpers, and PageLayout APIs are directly available | PASS |
| Protected API migration | Relevant hooks are directly declared on `OdtTemplate`; dynamic dispatch remains covered | PASS |
| State-mirror migration | DOM/path mirrors and `synchronizePackageState()` were removed; context/package access is direct | PASS |
| PageLayout compatibility | `PageLayoutOdtTemplate` is a thin `OdtTemplate` subclass delegating to `PageLayoutManager` | PASS |
| Style/asset boundary | No `StyleContext`, `ASSET-CONTEXT`, or static-style redesign was introduced | PASS |
| Legacy cleanup policy | Unused defective `ensureTableCellStyleNodesExist()` was removed and documented | PASS |
| Lifecycle compatibility | Construction, render, save, load, refresh, and repeated operations pass the lifecycle suite | PASS |
| Structured insertion | `StructuredElementMaterializer` remains the materialization owner and facade callbacks preserve behavior | PASS |
| Template processing | `TemplateProcessor` remains the template-language owner | PASS |
| Package ownership | `OdtPackage` owns workspace, resources, paths, persistence, and cleanup | PASS |

## 4. Final class architecture

```text
OdtTemplate                         public facade/session orchestration
├── OdtPackage                      package, workspace, resources, persistence
│   └── OdtDocumentContext          content/styles/meta DOM state
├── TemplateProcessor               template-language transformations
├── StructuredElementMaterializer   constructed structured insertion
├── TemplateTargetResolver         existing native target resolution
├── MetadataManager                 metadata operations
├── PageLayoutManager               page-layout operations
└── temporary style/finalization    transitional implementation boundary

PageLayoutOdtTemplate extends OdtTemplate
└── setPageMargins(), setPageLayout()
```

`OdtTemplate` owns facade orchestration, assignment/render-session state,
debug state, and the public compatibility surface. It does not own a second
DOM or package state.

## 5. Ownership model and responsibility review

### `OdtTemplate`

The facade owns public orchestration: assignment, rendering, saving,
structured insertion, image operations, metadata delegation, inspection,
debugging, and the currently unavoidable compatibility/finalization calls.

### Existing collaborators

- `OdtPackage` owns package extraction, workspace paths, package resources,
  manifest handling, persistence, and cleanup.
- `OdtDocumentContext` owns the mutable `content.xml`, `styles.xml`, and
  `meta.xml` DOMs.
- `TemplateProcessor` owns template-language transformations.
- `StructuredElementMaterializer` owns constructed ODF subtree materialization.
- `TemplateTargetResolver` resolves existing native targets.
- `MetadataManager` owns metadata DOM operations.
- `PageLayoutManager` owns page-layout DOM operations.

### Temporary debt

Style registration, default styles, namespace preparation, image-style
injection, and finalization remain technical facade implementation until the
document-scoped style work is designed. This is documented debt, not a new
service boundary or a second style owner. ARCH-07 did not implement
`STYLE-CONTEXT-01`, `STYLE-API-02`, or `ASSET-CONTEXT`.

The remaining methods are cohesive enough for the current transition and do
not justify a speculative `TemplateInspector`, generic context, or a service
per helper group.

## 6. Public API result

The following workflows remain available and tested:

- construction and immediate package loading;
- `assign()`, `assignRepeating()`, and legacy assignment paths;
- `render()`, `save()`, `load()`, `refresh()`, including repeated operations;
- `setElement()`, `setImage()`, and `replaceImageByName()`;
- metadata operations;
- template inspection and debug APIs;
- retained public style/default helpers;
- `PageLayoutOdtTemplate::setPageMargins()` and `setPageLayout()`.

No unintended public API loss was found.

Intentional pre-1.0 changes are:

- removal of `AbstractOdtTemplate`;
- removal of `OdtTemplate instanceof AbstractOdtTemplate` identity;
- migration requirement for direct subclasses/type hints using that class;
- removal of protected historical DOM/path mirrors;
- removal of the unused defective `ensureTableCellStyleNodesExist()` helper.

## 7. Protected compatibility result

Relevant processing, structured-insertion, finalization, inspection, and
debug hooks now dispatch on the concrete `OdtTemplate` instance. The tests
continue to exercise observable dynamic dispatch where it is a characterized
compatibility seam, including processing and finalization paths.

The old base-class identity itself is not preserved. This is deliberate: no
repository production code or sample depends on direct base subclassing, and
keeping a broad compatibility base would contradict the agreed end state.

## 8. State ownership result

```text
OdtPackage
    package/workspace/resources/persistence/path state

OdtDocumentContext
    content.xml/styles.xml/meta.xml DOM state

OdtTemplate
    assignment/render-session, facade, debug state
```

The historical `domContent`, `domStyles`, `domMeta`, `templatePath`, and
`tempDir` mirrors are absent from the active facade. There is no
`synchronizePackageState()`. Internal code accesses the current context or
package directly, so no duplicate mutable Source of Truth was introduced.

## 9. PageLayout result

`PageLayoutOdtTemplate` remains a thin convenience/compatibility facade over
`OdtTemplate` and `PageLayoutManager`. It contains only PageLayout operations.
The unrelated `adjustBulletIndentation()` override and its historical direct
style-state coupling are gone. Page margins, page size, orientation, list
rendering, and persistence remain covered by tests and package checks.

## 10. Style and asset boundary

ARCH-07 deliberately leaves the following for later milestones:

- `DOCUMENT-DEFAULTS-01` for document-wide defaults;
- `STYLE-CONTEXT-01` for document-scoped style registration/state;
- `STYLE-API-02` for public legacy style API compatibility/deprecation;
- `ASSET-CONTEXT` and related temporary-asset lifecycle work.

No global style registry replacement, asset context, or document-default API
was added during ARCH-07.

## 11. Legacy review

Repository-wide review found no active source reference to
`AbstractOdtTemplate`, its mirrors, `synchronizePackageState()`, or
`ensureTableCellStyleNodesExist()`. Remaining occurrences are historical
architecture records, migration documentation, test names, or unrelated
method parameter names such as test fixture `templatePath`.

Historical ARCH-01 through ARCH-07 documents intentionally retain the names
needed to describe their state at the time. Current roadmap documentation now
marks ARCH-07 complete and describes `AbstractOdtTemplate` as removed.

## 12. Automated validation

- Full PHPUnit: **105 tests, 847 assertions — PASS**
- PHP lint for all `src/` and `tests/` PHP files: **PASS**
- `composer validate --no-check-publish`: **PASS**
- `git diff --check`: **PASS**
- `zensical build --strict`: not executed because `zensical` is not installed

The assertion count is lower than earlier pre-removal runs because tests no
longer assert the existence/identity of intentionally removed mirrors.

## 13. Package/XML validation

Temporary representative packages for basic processing, structured insertion,
images, PageLayout, lists/styles, and load/re-render were validated with:

- `unzip -t`;
- well-formedness checks for `content.xml`, `styles.xml`, `meta.xml`, and
  `META-INF/manifest.xml`.

All checks passed. Image resources, PageLayout attributes, list-style
attributes, and post-load content were also verified in representative
packages. No tracked sample output was used or changed.

## 14. Visual regression

**ARCH-07H local visual regression: PASS**

The local/manual validation was completed outside the restricted agent
environment against the established baseline:

- Sample 01 — pixelidentisch;
- Sample 06 — pixelidentisch;
- Sample 09 — pixelidentisch;
- Sample 10 — pixelidentisch;
- Sample 14 page 1 — pixelidentisch;
- Sample 14 page 2 — only the expected dynamic date/time difference;
- Sample 16 — pixelidentisch;
- Sample 18 — pixelidentisch;
- Sample 21 pages 1 and 2 — pixelidentisch.

The known Sample 21 second-page template/layout artifact is accepted and is
not a regression. The restricted agent environment still cannot run
LibreOffice due to `javaldx`/`dconf` filesystem errors; this does not negate
the completed local visual PASS and the baseline was not replaced.

## 15. Full ARCH-07 diff review

The branch contains the complete ARCH-07 slice history relative to
`origin/develop`, including the architecture records, bounded production
changes, characterization adjustments, and this closeout. Review found:

- no StyleContext or AssetContext implementation;
- no named-object, cloning, or unrelated document-model expansion;
- no remaining active base-class dependency;
- no duplicate DOM/package state;
- no stale production import of `AbstractOdtTemplate`;
- no generated outputs, LibreOffice lock files, or local `tools/` artifacts in
  the ARCH-07 commits.

The working tree still contains pre-existing local changes under
`.gitignore`, `samples/output/`, and untracked `tools/`; these were not staged
or modified by the closeout.

## 16. Deferred work

The following remain intentionally outside ARCH-07:

- `DOCUMENT-DEFAULTS-01`;
- `STYLE-CONTEXT-01`;
- `STYLE-API-02`;
- `ASSET-CONTEXT` and `TEMP-ASSET-01`;
- template-format preservation and authoring UX;
- named-object operations, cloning, removal, and broader document structure;
- final extraction of transitional style/finalization implementation.

## 17. Merge recommendation

The ARCH-07 change contract is implemented, the automated and package/XML
preflight is green, and the local visual regression is documented as PASS.
The branch is:

**READY FOR MERGE TO DEVELOP**

The merge itself is intentionally not performed by this task.

## 18. Next milestone recommendation

The next evidence-based milestone is **DOCUMENT-DEFAULTS-01 — Document-level
default settings**. It should build on the now-independent `OdtTemplate`
facade and coordinate its document-scoped model with the separately planned
`STYLE-CONTEXT-01`, without beginning either asset-context or generalized
document-composition work prematurely.

# ARCH-07B — Template Facade Structural Change Contract

## 1. Status

Design/change contract for the remaining ARCH-07 structural-foundation work.
No production implementation, test change, class removal, class rename, or
new public API is authorized by this document alone.

The contract is based on ARCH-07A and the current repository state at
`f247127` on `architecture/arch-07-template-facade`.

The implementation goal is not to rename the existing inheritance tree. The
goal is to make `OdtTemplate` a coherent independent public facade while
retiring the misleading broad `AbstractOdtTemplate` implementation role in
bounded, characterized slices.

## 2. Evidence basis

The contract is based on:

- `ARCH-07A_TEMPLATE_FACADE_END_STATE_AUDIT.md`;
- ARCH-06A through ARCH-06D and `ARCH-06_CLOSEOUT.md`;
- ARCH-01 through ARCH-05 architecture records;
- `OdtTemplate`, `AbstractOdtTemplate`, and `PageLayoutOdtTemplate`;
- `OdtPackage` and `OdtDocumentContext`;
- `TemplateProcessor`, `StructuredElementMaterializer`,
  `TemplateTargetResolver`, `TemplateTarget`, `MetadataManager`, and
  `PageLayoutManager`;
- `StyleMapper`, `StyleWriter`, and structured element classes;
- package/lifecycle, API-contract, template-processing, structured-insertion,
  page-layout, finalization, and ARCH-06 compatibility tests;
- repository-wide inheritance, `parent::` calls, samples, documentation, and
  Composer exposure.

Current evidence establishes:

- only one direct production subclass of `AbstractOdtTemplate`;
- no abstract methods and no mandatory subclass-provided operation;
- real protected dispatch through public workflows;
- authoritative package and DOM ownership outside the inheritance hierarchy;
- a large remaining base-class implementation mix;
- no repository evidence of additional external subclasses, though Composer
  consumers may exist;
- a pre-1.0 public release at version 0.90.

## 3. Problem statement

The current hierarchy makes implementation inheritance look like a domain
abstraction:

```text
AbstractOdtTemplate
        ↓
    OdtTemplate
        ↓
PageLayoutOdtTemplate
```

In reality, `AbstractOdtTemplate` has become a compatibility container for
historical ODF helpers, style handling, structured insertion callbacks,
template helpers, diagnostics, and mirrored state. It does not define a
meaningful abstract contract.

Continuing to grow this base would make the upcoming document defaults, style
context, and asset context work likely to accumulate in the wrong place. A
simple copy of its methods into `OdtTemplate` would also fail: it would leave
the same mixed responsibility structure under a different class name.

ARCH-07 therefore establishes a structural migration contract, not a broad
refactoring permission.

## 4. Target architecture

The required long-term target is:

```text
OdtTemplate
├── OdtPackage
├── OdtDocumentContext
├── TemplateProcessor
├── StructuredElementMaterializer
├── TemplateTargetResolver
├── MetadataManager
├── PageLayoutManager
└── future document-scoped collaborators
    ├── document defaults
    ├── StyleContext
    └── asset context
```

Responsibilities:

| Component | Contractual responsibility |
|---|---|
| `OdtTemplate` | concrete public facade, assignment state, lifecycle orchestration, compatibility facade |
| `OdtPackage` | source package, workspace, package resources, manifest, persistence, ZIP lifecycle, cleanup |
| `OdtDocumentContext` | authoritative `content.xml`, `styles.xml`, and `meta.xml` DOM state |
| `TemplateProcessor` | stateless template-language transformations over supplied DOM regions |
| `StructuredElementMaterializer` | materialization/insertion of constructed `OdtElement` subtrees |
| `TemplateTargetResolver` | read-only, typed resolution of existing native ODF targets |
| `MetadataManager` | metadata DOM operations for one document context |
| `PageLayoutManager` | page-layout DOM operations for one document context |
| future style/default/asset collaborators | only after their own contracts are defined |

`OdtTemplate` may coordinate these collaborators, but must not become a new
all-purpose implementation container. New code must not introduce a second
authoritative package, DOM, style, or asset state.

## 5. Compatibility policy

### 5.1 Core public workflows

The following workflows remain behavior-compatible:

```php
$template = new OdtTemplate($path);
$template->assign($values);
$template->assignRepeating('items', $rows);
$template->render();
$template->save($output);
```

The following public behavior is also preserved unless a later slice explicitly
records a pre-1.0 compatibility decision:

- construction and immediate template loading;
- `assign()`, `setValues()`, `assignRepeating()`, and `setRepeating()`;
- `render()` ordering and processing of both core DOM regions;
- `save()`, `load()`, `refresh()`, and cleanup behavior;
- structured insertion through `setElement()`;
- `setImage()` and `replaceImageByName()`;
- metadata operations;
- package persistence, manifest, and resource behavior;
- existing structured element serialization.

ARCH-07 is an ownership migration. It must not silently change template
syntax, replacement ordering, image dimensions, text-box behavior, lifecycle
reset semantics, or style output.

### 5.2 Public inherited methods

Public methods are evaluated individually below. `RETAIN` means the public
method remains available on the facade; it does not require retaining the
current implementation location. `DEPRECATE` and `REMOVE` require explicit
documentation and a dedicated migration slice.

### 5.3 Protected methods

Protected visibility is not automatically a permanent extension contract.
Nevertheless, ARCH-06C proves real dynamic dispatch for several hooks. A hook
may be removed only when its evidence, compatibility impact, and migration
are explicitly handled.

Allowed statuses are:

- `RETAIN`: end-state protected behavior remains available and dispatched;
- `BRIDGE`: temporarily retained protected wrapper while implementation moves;
- `DEPRECATE`: compatibility wrapper remains for a documented transition;
- `REMOVE`: deliberately removed after characterization and migration review.

### 5.4 Pre-1.0 policy

The 0.90 status permits deliberate, limited breaking changes. The default is
still preservation for core workflows. Breaking changes may be accepted only
when all of the following are true:

1. the current structure materially prevents the target architecture;
2. repository usage and override evidence have been checked;
3. the exact affected API is named;
4. an alternative compatibility bridge was considered;
5. migration guidance is documented;
6. the change is isolated from unrelated behavior changes;
7. the release/documentation impact is explicit.

Theoretical external subclassing is a risk, not an absolute veto. Conversely,
absence of repository usage is not sufficient evidence for silent removal.

## 6. Public API migration matrix

| Method | Current owner/use | Target owner | Status | Compatibility contract | Characterization / slice |
|---|---|---|---|---|---|
| `setElement()` | inherited public facade; samples and structured/API tests | `OdtTemplate` facade coordinating existing services | RETAIN | same structured insertion, styles, assets, content/styles paths, and callback dispatch | existing ARCH-05/06 baseline; facade slice |
| `ensureParagraphStylesExist()` | inherited public style helper; several samples | `OdtTemplate` compatibility facade until style contract | RETAIN temporarily | same supported style output | existing API/style tests; style boundary slice |
| `ensureDefaultListStylesForContentXml()` | inherited public helper; load/list support | facade/document-style compatibility path | RETAIN temporarily | same default list behavior | existing list/sample coverage; style boundary slice |
| `extractTemplateVariables()` | inherited public inspection API | `OdtTemplate` facade | RETAIN | same result shape and parser behavior | existing API/inspection coverage; facade slice |
| `enableDebugMode()` | inherited public diagnostic API | `OdtTemplate` facade | RETAIN | same debug activation behavior | existing API evidence; facade slice |
| `getDebugLog()` | inherited public diagnostic API | `OdtTemplate` facade | RETAIN | same result shape | existing API evidence; facade slice |
| `setValues()` | public assignment compatibility method | `OdtTemplate` | RETAIN | merge semantics unchanged | existing template tests; no behavior change |
| `setRepeating()` | public assignment compatibility method | `OdtTemplate` | RETAIN | repeat-stack semantics unchanged | existing template tests; no behavior change |
| `setRepeatingData()` | public legacy immediate processing path | `OdtTemplate` compatibility facade | RETAIN initially; later DEPRECATE candidate | separate immediate path remains unchanged | existing behavior; dedicated legacy review |
| `assign()` | public active assignment API | `OdtTemplate` | RETAIN | merge semantics unchanged | existing lifecycle/template tests |
| `assignRepeating()` | public active repeat API | `OdtTemplate` | RETAIN | active render path unchanged | existing lifecycle/template tests |
| `render()` | public orchestration | `OdtTemplate` | RETAIN | ordering and both DOM regions unchanged | ARCH-04/06 tests; every structural slice |
| `save()` | public finalization orchestration | `OdtTemplate` | RETAIN | style/finalization/package order unchanged | ARCH-03/06 tests; render-sensitive slices |
| `load()` | public source-template reset | `OdtTemplate` | RETAIN | reset-from-original behavior unchanged | lifecycle tests; state-mirror slice |
| `refresh()` | public legacy reset behavior | `OdtTemplate` | RETAIN initially; clarification deferred | persisted workspace is still discarded by `load()` | lifecycle tests; no semantic redesign |
| `setMeta()` / `getMeta()` | public facade delegating to manager | `OdtTemplate` facade + `MetadataManager` | RETAIN | metadata keys, creation, persistence unchanged | existing document-service tests |
| `setImage()` | public image placeholder operation | `OdtTemplate` facade + package/image logic | RETAIN | dimensions, anchoring, wrapping, resource behavior unchanged | image/package tests |
| `replaceImageByName()` | public named-frame image operation | `OdtTemplate` facade + target/package logic | RETAIN | duplicate-frame and legacy dimension semantics unchanged | ARCH-05 named-image tests |
| `setPageMargins()` / `setPageLayout()` | public `PageLayoutOdtTemplate` methods | page-layout facade/service | RETAIN during ARCH-07 | same validation and chaining | page-layout tests; PageLayout slice |

No public method is removed by the first structural slice. Any later
`DEPRECATE` or `REMOVE` decision must be isolated from implementation movement.

## 7. Protected hook migration matrix

| Hook | Current caller | Override/test evidence | End-state status | Dispatch requirement | Slice |
|---|---|---|---|---|---|
| `fixBrokenVariables()` | constructor/load, render, structured insertion | ARCH-06C; active processor seam | BRIDGE, possibly RETAIN | preserve dispatch while facade calls it | processing/facade slice |
| `setValuesInDom()` | `render()` | ARCH-06C; structured-value behavior | BRIDGE | preserve scalar/structured split | processing/facade slice |
| `replacePlaceholderWithDom()` | `setElement()`, materializer callback | ARCH-06C | BRIDGE | preserve structured replacement override | structured facade slice |
| `replacePlaceholdersInNode()` | active foreach and legacy structured paths | ARCH-04 characterization | BRIDGE | preserve row-clone callback behavior | processing/compatibility slice |
| `replaceInText()` | `replacePlaceholdersInNode()` | explicit subclass override tests | BRIDGE, possibly DEPRECATE | preserve row substitution semantics during migration | processing/compatibility slice |
| `adjustBulletIndentation()` | `save()` | PageLayout and ARCH-06C override | BRIDGE | no direct service bypass until decision | page-layout/finalization slice |
| `injectImageStyles()` | `save()` | finalization probe | BRIDGE | preserve save ordering and override reachability | finalization-compatible slice |
| `prepareNamespaces()` | base ODF helpers | no repository override found | MIGRATE/BRIDGE | preserve behavior where inherited access exists | facade ownership slice |
| `ensureXmlnsAttributes()` | base style/XML helpers | no repository override found | MIGRATE/BRIDGE | preserve XML output | style-boundary slice |
| `ensureTextStylesExist()` | structured style preparation | no override found; API-adjacent | BRIDGE | preserve style output and public helper behavior | style-boundary slice |
| `ensureDefaultListStyles()` | template preparation | no override found | BRIDGE | preserve load-time defaults | style-boundary slice |
| `ensureDefaultParagraphStyles()` | template preparation | no override found | BRIDGE | preserve load-time defaults | style-boundary slice |
| `registerStyles()` | `setElement()` | no override found | BRIDGE | preserve style registration inputs and ordering | structured/style slice |
| `hasPlaceholder()` | `StructuredElementMaterializer` callback | no override found | INTERNAL/BRIDGE | preserve insertion lookup semantics | structured facade slice |
| `log()` | debug methods | no override found | RETAIN on facade or BRIDGE | preserve debug log behavior | facade slice |
| `documentContext()` | metadata/page-layout services and probes | ARCH-06D; no override | RETAIN on concrete facade | must return current package-owned context | facade/state slice |

`MIGRATE/BRIDGE` means the final exact status is resolved by the relevant
implementation slice. It does not authorize a silent visibility change.

## 8. State ownership and mirror migration

### 8.1 Authoritative ownership

The following ownership is binding:

```text
OdtPackage
    package path, workspace, package files, resources, manifest, persistence

OdtDocumentContext
    content.xml, styles.xml, meta.xml DOM instances

OdtTemplate
    valueStack, repeatStack, render/session orchestration, public facade state
```

`StyleMapper` remains process-wide in ARCH-07. ARCH-07 must not reinterpret it
as document-scoped or introduce a replacement style registry.

### 8.2 Historical mirrors

| Property | Current role | End-state policy |
|---|---|---|
| `domContent` | direct inherited rendering/insertion access; mirror of context | remove internal dependency in bounded slices; retain only as temporary compatibility bridge if required |
| `domStyles` | direct style/finalization/insertion access; mirror of context | same; do not create a second owner |
| `domMeta` | concrete-facade metadata mirror | route internal access through context/manager; temporary bridge only if needed |
| `templatePath` | historical base/facade path state | package remains owner; retain only for compatibility until direct access is characterized |
| `tempDir` | historical image/package path access | package remains owner; migrate internal resource access to package APIs |
| `values` | legacy inherited field | do not make it a second render-state source; disposition requires separate evidence |
| `valueStack` | active assignment state | retain as authoritative facade session state during ARCH-07 |
| `repeatStack` | active repeat state | retain as authoritative facade session state during ARCH-07 |
| `log`, `debugMode` | diagnostic state | retain facade behavior; not document ownership |

The implementation must not maintain two mutable DOM sources. During migration,
mirrors may point to context-owned objects, but all reload paths must
resynchronize them after `load()`/`refresh()` as currently characterized.

### 8.3 Mirror removal criteria

A mirror may be removed only after:

1. all internal callers use package/context access;
2. repository subclasses no longer require direct access;
3. protected-property compatibility has been explicitly characterized or
   deliberately accepted as a pre-1.0 break;
4. `load()`, `refresh()`, repeated render/save, and structured insertion remain
   valid;
5. no second state owner was introduced;
6. migration documentation names the affected property and replacement.

## 9. `AbstractOdtTemplate` migration contract

The migration is staged and may not be implemented as a wholesale copy.

### Phase 1 — Establish independent facade ownership

`OdtTemplate` becomes the explicit owner of public facade methods and session
orchestration. Existing composed services remain the implementation owners
where already available. No behavior change, style redesign, or API expansion
is allowed.

### Phase 2 — Separate residual implementation dependencies

Move or wrap one bounded responsibility at a time, beginning with low-risk
facade/diagnostic/inspection coordination. Internal calls should stop relying
on base-class state where authoritative package/context access is available.

### Phase 3 — Migrate protected dispatch deliberately

For each characterized hook, preserve a facade wrapper or explicit bridge until
the contract says otherwise. Public operations must continue to dispatch
through hooks whose override behavior is retained.

### Phase 4 — Decouple page-layout specialization

`PageLayoutOdtTemplate` must rely on `PageLayoutManager` and the concrete
facade/session boundary, not accidental access to base-class implementation.
The `adjustBulletIndentation()` relationship must be characterized before it
is removed or consolidated.

### Phase 5 — Resolve the base class

After all required public/protected behavior has an explicit owner, choose one
of these deliberate outcomes:

- remove `AbstractOdtTemplate` from the active architecture and provide only a
  documented migration mechanism if needed; or
- retain a minimal, time-bounded compatibility shell with no broad new domain
  responsibility.

An unbounded broad compatibility base is not an accepted end state.

### Removal criteria

ARCH-07 may close only when normal engine usage no longer depends on
`AbstractOdtTemplate`, its remaining compatibility role is explicitly
documented, and no future responsibility is being added to it. If a shell is
retained, its removal trigger, affected APIs, and migration path must be
written down before closeout.

## 10. `PageLayoutOdtTemplate` contract

The preferred ARCH-07 direction is a thin compatibility facade:

```php
class PageLayoutOdtTemplate extends OdtTemplate
{
    // only semantically page-layout-specific public compatibility methods
}
```

Its page-layout methods continue to delegate to `PageLayoutManager`. The
subclass must not accumulate general style, list, or document-state logic.

`adjustBulletIndentation()` is not accepted as permanent page-layout domain
behavior merely because it is currently overridden there. It must be either:

- consolidated into the appropriate finalization/document owner while
  preserving required dispatch;
- retained temporarily as a documented compatibility hook; or
- removed as a deliberate pre-1.0 protected compatibility change.

The first ARCH-07 slices must retain it. Its final disposition belongs in the
page-layout compatibility slice after the facade migration is proven.

An eventual deprecation of `PageLayoutOdtTemplate` in favor of page-layout
operations on `OdtTemplate` is possible, but is not required to complete the
first structural extraction. It requires a separate public API decision.

## 11. Style and asset deferral boundary

ARCH-07 preserves existing style and asset behavior but does not redesign it.

Allowed:

- move facade ownership while preserving current style calls and ordering;
- route existing package resource operations through `OdtPackage`;
- retain compatibility wrappers required by structured insertion;
- remove a legacy helper only if a separate explicit disposition proves it is
  not part of the supported surface.

Forbidden:

- introducing `StyleContext`;
- resetting static `StyleMapper` state in constructors;
- changing style registration scope or precedence;
- introducing a new asset model or global asset registry;
- changing image/frame semantics;
- extracting a finalizer whose inputs are still process-wide style state;
- changing document defaults, page structure, headers, footers, or layout APIs.

`STYLE-CONTEXT-01`, `STYLE-API-02`, and `ASSET-CONTEXT` remain later contracts.

## 12. Template inspection and normalization

No new `TemplateInspector` or `TemplateNormalizer` service is introduced by
ARCH-07. The current code has related but distinct responsibilities:

- `extractTemplateVariables()` is a public inspection API;
- `parseTemplateContent()` is its protected parser helper;
- `normalizeTemplateDom()` and `fixBrokenVariables()` support active
  processing and structured insertion.

They may be moved behind the concrete facade or existing `TemplateProcessor`
where a slice requires it, but the behavior and parser contracts remain
unchanged. A new service requires a separate consumer and explicit inputs /
outputs; mechanical relocation is not sufficient.

## 13. Legacy cleanup policy

### `ensureTableCellStyleNodesExist()`

This method is classified as:

```text
repository-unused
protected and therefore externally possible
apparently inconsistent ($styleNodes` parameter vs `$styleMap` body)
desired semantics unknown
```

ARCH-07 does not repair it. The default contract is to leave it untouched and
assign its final disposition to `STYLE-API-02`, unless its presence blocks a
specific structural migration. If it must be removed for structural reasons,
that removal requires a dedicated characterization/compatibility decision and
must not freeze its apparent failure as desired behavior.

Other legacy paths follow the same policy:

| Area | Default ARCH-07 disposition |
|---|---|
| `setRepeatingData()` | retain; separate deprecation review later |
| text-based conditional/repeating helpers | retain as compatibility candidates; no silent deletion |
| `splitConditionalsInTextNodes()` | retain unless explicit evidence permits removal |
| `parseTemplateContent()` | retain behind public inspection behavior |
| direct style helpers | preserve until style/API contract |
| `refresh()` semantics | preserve; lifecycle clarification deferred |

## 14. Characterization requirements

Already covered and used as the regression baseline:

- public render/save/load/refresh lifecycle;
- repeated render/save behavior;
- package/context ownership and mirror identity;
- protected render and structured-insertion dispatch;
- page-layout subclass dispatch;
- template-language ordering and legacy control behavior;
- structured elements, images, text boxes, tables, lists, styles, and HTML
  import;
- public sample smoke behavior;
- metadata, package, manifest, and save/reopen behavior.

Additional characterization is required before a slice only where it changes a
surface not covered by the existing evidence:

- direct protected-property access for any mirror planned for removal;
- `instanceof AbstractOdtTemplate` and type-hint behavior if the type is
  removed or inverted;
- external-style subclass construction and inherited public methods;
- `PageLayoutOdtTemplate` behavior after any change to its inheritance;
- exact protected hook dispatch for any hook moved out of the hierarchy;
- public helper availability if a helper is deprecated or removed.

Tests must not be added solely to preserve an unneeded broken legacy helper.
For intentional breaking changes, the migration decision replaces a
preservation test; replacement behavior still needs tests.

## 15. Visual regression requirements

Pure documentation, type-only, or non-rendering forwarding changes require
PHPUnit and package/XML validation but not automatically a new visual run.

The following later slices require the established visual path in addition to
automated tests:

- changes to render ordering or placeholder normalization;
- structured insertion or text-box handling;
- image/frame/resource or manifest behavior;
- style registration, style serialization, or finalization ordering;
- page-layout or list-indentation behavior;
- save/reload serialization that changes generated XML;
- any change affecting public sample output.

Validation must compare against the existing baseline. The baseline must not be
regenerated to conceal differences. Sample 21's accepted two-page
template/layout artifact is known and must not automatically be classified as
an ARCH-07 regression.

## 16. Implementation slice plan

### ARCH-07C — Migration-gap characterization

Goal: add only the tests needed for the selected migration boundary.

Allowed:

- tests for protected property access, type identity, inherited public API,
  and exact dispatch gaps;
- no production refactoring.

Forbidden:

- behavior redesign;
- tests that canonize unused broken code without a migration need;
- changes to existing tests merely to fit the future structure.

Compatibility: all existing behavior remains unchanged.

Visual: no visual run unless characterization exposes a render-sensitive gap;
if it does, use the existing baseline without updating it.

End state: a complete evidence matrix for the selected migration.

### ARCH-07D — Concrete facade ownership

Goal: move the first bounded, low-risk responsibility into the independent
`OdtTemplate` facade and make composition ownership explicit.

Preferred scope:

- debug/inspection facade behavior or a similarly bounded coordination seam;
- no style-context, asset-context, or broad method copy.

Allowed:

- moving implementation with unchanged public signatures;
- narrow private/protected forwarding where required;
- preserving existing package/context access.

Forbidden:

- removing `AbstractOdtTemplate` yet;
- changing public/protected behavior;
- changing styles, images, or render semantics.

Visual: PHPUnit/package checks; visual run only if generated XML changes.

End state: one responsibility has a clear facade owner and no duplicate state.

### ARCH-07E — Processing and structured-facade migration

Goal: migrate the remaining active facade coordination while preserving the
separation between `TemplateProcessor`, structured materialization, and native
target resolution.

Allowed:

- bounded movement of compatibility wrappers;
- explicit callbacks preserving protected dispatch;
- internal use of existing collaborators.

Forbidden:

- merging scalar processing with structured insertion;
- changing row replacement, condition ordering, or text-box behavior;
- new generic context or service abstractions.

Visual: required for any render/DOM behavior change; at minimum run
representative samples and compare generated ODT/PDF/PNG output.

End state: active template and structured operations no longer depend on broad
base implementation except through explicit, documented bridges.

### ARCH-07F — State-mirror migration

Goal: remove internal dependence on historical DOM/path mirrors while retaining
authoritative package/context ownership.

Allowed:

- package/context access through existing methods;
- mirror synchronization only as a temporary compatibility bridge;
- removal of individual mirrors only when criteria in section 8.3 are met.

Forbidden:

- new duplicate DOM/path state;
- changing load/reset semantics;
- broad external compatibility assumptions without evidence.

Visual: required if DOM access changes output; otherwise lifecycle/package tests
are mandatory.

End state: internal code uses authoritative owners; remaining mirrors have an
explicit compatibility status.

### ARCH-07G — Page-layout compatibility resolution

Goal: make `PageLayoutOdtTemplate` a thin, explained compatibility facade and
resolve `adjustBulletIndentation()`.

Allowed:

- preserving page-layout public methods and manager delegation;
- moving the indentation behavior to the correct owner with characterized
  dispatch;
- documenting a separate deprecation path if chosen.

Forbidden:

- page-style redesign;
- header/footer or document-structure work;
- silent removal of the subclass or protected hook.

Visual: required; page layout and Sample 21 must be checked against the
accepted baseline.

End state: page-layout inheritance is either thin and intentional or has an
explicit documented migration path.

### ARCH-07H — Base-class resolution

Goal: achieve the actual structural end state.

Allowed:

- remove `AbstractOdtTemplate` from normal engine architecture; or
- reduce it to a minimal, time-bounded compatibility shell;
- update only the necessary API/documentation/tests for that decision.

Forbidden:

- leaving the broad historical base as an indefinite default;
- adding abstract methods merely to justify its name;
- unrelated style, asset, or document-model changes.

Visual: required if any runtime path changes; full representative sample and
LibreOffice validation is mandatory.

End state: normal `OdtTemplate` use is independent of a semantically false
abstract base, with all retained/deprecated/removed compatibility behavior
documented.

### ARCH-07I — Final review and preflight

Goal: verify that the implementation matches this contract and close ARCH-07.

Required:

- full PHPUnit suite;
- public sample smoke suite;
- package/XML validation;
- required LibreOffice/PDF/PNG review;
- API/protected-surface review;
- documentation update to the actual end state;
- `git diff --check` and repository preflight.

No new architecture feature is introduced in this slice.

## 17. Explicit non-goals

ARCH-07 must not implement or redesign:

- `DOCUMENT-DEFAULTS-01`;
- `STYLE-CONTEXT-01`;
- `ASSET-CONTEXT`;
- `STYLE-API-02` beyond a strictly necessary disposition;
- `TEMPLATE-FORMAT-PRESERVATION-01`;
- `TEMPLATE-AUTHORING-UX-01`;
- named text-box replacement;
- table-target mutation;
- whole-object replacement or removal;
- Exact Clone, Template Clone / Template Instance, or Structural Clone;
- generic named-object APIs;
- page/master-page redesign;
- headers, footers, sections, or pagination;
- table, frame, image, or list layout improvements;
- generic `TemplateContext` or speculative interfaces;
- new global mutable state;
- artificial abstract methods.

## 18. Completion criteria for ARCH-07

ARCH-07 is complete only when all of the following are true:

1. `OdtTemplate` has a semantically clear role as the concrete public facade.
2. Normal engine use does not require a broad `AbstractOdtTemplate` base.
3. No artificial abstract methods were introduced.
4. `OdtPackage` remains authoritative for package/workspace/resource state.
5. `OdtDocumentContext` remains authoritative for core DOM state.
6. `OdtTemplate` remains authoritative for assignment/render-session state.
7. No duplicate mutable package, DOM, style, or asset state was introduced.
8. Core public workflows remain behavior-compatible.
9. Retained, deprecated, and removed public methods are documented
   individually.
10. Protected dispatch seams are either preserved or deliberately migrated
    with explicit compatibility decisions.
11. Historical state mirrors have an explicit final status and no longer act
    as hidden authoritative owners.
12. `PageLayoutOdtTemplate` has a documented, semantically defensible role.
13. `adjustBulletIndentation()` is no longer accidental unexplained page-layout
    behavior.
14. Template inspection/normalization has not been fragmented into speculative
    services.
15. `STYLE-CONTEXT-01` and `ASSET-CONTEXT` have not been implemented early.
16. Legacy cleanup is evidence-based and does not silently canonize broken
    unused behavior.
17. Full PHPUnit and `PublicSampleSmokeTest` are green.
18. Required visual regression checks are complete against the existing
    baseline.
19. Documentation describes the actual resulting structure.

## 19. Next step

The next authorized slice is **ARCH-07C — Migration-gap characterization**.
It should begin by identifying only the missing evidence for the selected
facade/base migration, especially protected-property access and type-identity
compatibility. No implementation should begin before that characterization
and the resulting contract review are complete.

Semantics before implementation.

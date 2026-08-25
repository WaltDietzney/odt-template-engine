# ARCH-06B AbstractOdtTemplate Base-Class Contract

**Status:** Design / change contract; no production implementation
**Milestone:** ARCH-06 — Reassess `AbstractOdtTemplate`
**Repository:** `WaltDietzney/odt-template-engine`
**Branch:** `architecture/arch-06-abstract-template`
**Base:** `develop`

## 1. Purpose

ARCH-06B defines the semantic contract that can reasonably be assigned to
`AbstractOdtTemplate` after ARCH-02 through ARCH-05 extracted package,
document, template-language, metadata, page-layout, structured-materialization
and typed-target responsibilities.

This document is a design contract only. It does not add abstract methods,
change public or protected APIs, or begin the ARCH-06 refactoring.

The central conclusion is deliberately conservative:

> The current evidence supports retaining `AbstractOdtTemplate` as a
> compatibility base during a migration period, but does not yet justify a
> new, artificial abstract domain contract.

An eventual composition-based reduction or removal remains possible. The
class name alone is not sufficient reason to preserve or expand inheritance.

## 2. Evidence reviewed

The review used:

- `src/AbstractOdtTemplate.php`;
- `src/OdtTemplate.php`;
- `src/PageLayoutOdtTemplate.php`;
- `src/OdtPackage.php` and `src/OdtDocumentContext.php`;
- `src/Template/TemplateProcessor.php`;
- `src/Document/StructuredElementMaterializer.php`;
- `src/Document/TemplateTargetResolver.php` and `TemplateTarget.php`;
- `src/Document/MetadataManager.php` and `PageLayoutManager.php`;
- `src/Utils/StyleMapper.php` and `StyleWriter.php`;
- relevant inheritance and protected-polymorphism tests;
- ARCH-01, ARCH-02, ARCH-03, ARCH-04 and ARCH-05 architecture records;
- `docs/ROADMAP.md` and `docs/FUTURE_DEVELOPMENT.md`.

The requested file
`docs/architecture/ARCH-06A_ABSTRACT_ODT_TEMPLATE_AUDIT.md` is not present
on this branch. It could not be read or verified. This is a repository/document
baseline contradiction and is itself recorded as an ARCH-06 documentation
follow-up. The conclusions below therefore verify the available audit history
against the current implementation rather than treating the missing document
as authoritative.

## 3. Confirmed and corrected ARCH-06A findings

The available architecture history and current code confirm that:

- `AbstractOdtTemplate` is formally `abstract` but declares no abstract
  methods;
- it contains substantial shared ODF/XML, style, placeholder, structured
  insertion, inspection and debugging implementation;
- `OdtTemplate` is the concrete public lifecycle and rendering facade;
- `PageLayoutOdtTemplate` is a specialized public facade that mainly inherits
  document access and overrides one protected finalization hook;
- package and core XML ownership now belongs to `OdtPackage` and
  `OdtDocumentContext`, while compatibility mirrors remain on `OdtTemplate`;
- ARCH-04 moved active template-language operations to `TemplateProcessor`;
- ARCH-05 moved structured materialization and introduced typed target
  resolution, but intentionally retained compatibility seams.

The current code adds an important qualification: the protected
`documentContext()` accessor is implemented on `OdtTemplate`, not on
`AbstractOdtTemplate`. It is therefore a concrete-facade seam rather than a
base-class contract.

## 4. Current inheritance semantics

The current hierarchy is:

```text
AbstractOdtTemplate
        ↓
   OdtTemplate
        ↓
PageLayoutOdtTemplate
```

### `AbstractOdtTemplate`

The base declares no abstract operation. A subclass is not required by the
language to provide a template-specific implementation. Its effective role is
shared implementation plus inherited extension surface.

It provides public methods for structured insertion, style helpers, template
inspection and debug access, and protected helpers for styles, DOM replacement,
normalization, row substitution and diagnostics.

### `OdtTemplate`

`OdtTemplate` supplies the actual public document lifecycle:

- construction and package loading;
- `load()`, `save()`, `refresh()`, `cleanup()`;
- assignment and repeating state;
- render orchestration;
- metadata, image and named-image operations;
- active template-language wrapper dispatch;
- package/document synchronization.

It redeclares the base properties `templatePath`, `tempDir`, `domContent` and
`domStyles`, then adds `domMeta`, `valueStack`, `repeatStack` and the private
`OdtPackage`. This is a compatibility mirror arrangement, not two intended
document owners.

### `PageLayoutOdtTemplate`

`PageLayoutOdtTemplate` adds `setPageMargins()` and `setPageLayout()`, which
delegate to `PageLayoutManager` through the inherited protected
`documentContext()` seam. It also overrides `adjustBulletIndentation()` and
directly reads inherited `domStyles`.

The subclass is therefore semantically a page-layout facade, but its current
inheritance is partly historical: it needs access to the existing template
facade and protected document state rather than a base-class page-layout
contract.

### Inheritance classification

| Relationship | Classification | Evidence |
| --- | --- | --- |
| `OdtTemplate` inherits public structured/debug helpers | compatibility-sensitive seam | Public library methods are inherited from the base |
| `OdtTemplate` overrides active processing hooks | intended compatibility extension point | ARCH-04 wrappers and subclass tests preserve dispatch |
| `PageLayoutOdtTemplate::adjustBulletIndentation()` | compatibility-sensitive protected seam with duplicated logic | `DocumentFinalizationArch03CTest` and page-layout code override it |
| Base DOM/style properties used by subclasses | accidental inheritance coupling | package/context already own authoritative document state |
| Shared namespace/style/DOM helpers | ordinary shared implementation | no template-specific abstract variation is expressed |

Repository tests prove protected polymorphism for conditionals, evaluation,
foreach row replacement, filter/list processing, finalization and page-layout
delegation. External subclasses could also override any non-final protected
method even where the repository has no explicit test.

## 5. Responsibility classification

The following is the proposed semantic classification, not a movement plan.

| Responsibility | Classification | Rationale |
| --- | --- | --- |
| Public compatibility methods inherited from the base | D — compatibility facade only | Existing callers may invoke `setElement()`, style helpers, inspection and debug methods through the base type |
| Package and workspace ownership | B/C — `OdtTemplate` plus `OdtPackage` | `OdtPackage` already owns extraction, workspace, resources, manifest, persistence and cleanup |
| Core DOM ownership | C — `OdtDocumentContext` | Content, styles and metadata DOMs have a document-scoped owner already |
| Package/context access | B — concrete facade seam | `OdtTemplate::documentContext()` is used by metadata/page-layout services and subclasses |
| Assignment and repeat state | B — `OdtTemplate` | `valueStack` and `repeatStack` are render-session state, not universal base state |
| Render ordering/orchestration | B — `OdtTemplate` | It coordinates `TemplateProcessor`, text boxes, repeating blocks and conditions |
| Active template-language algorithms | C — `TemplateProcessor` | ARCH-04 established a stateless processor and protected facade callbacks |
| Structured element materialization | C — `StructuredElementMaterializer` plus facade coordination | ARCH-05 extracted insertion while retaining compatibility wrappers and style/resource preparation |
| Native target resolution | C — `TemplateTargetResolver` | It is independent of constructed content and package state |
| Metadata operations | C — `MetadataManager` | ARCH-03B already established the document-scoped service |
| Page layout operations | C — `PageLayoutManager` | ARCH-03B already established the document-scoped service |
| Style serialization and default styles | C/D — document/style services plus compatibility facade | The responsibility is real but still distributed and constrained by `StyleMapper`/`StyleWriter` |
| Placeholder/DOM compatibility helpers | D/C — protected facade seam with future service candidates | `replaceInText()` and related helpers remain observable through foreach and subclasses |
| Template inspection/parser | D/E — compatibility inspection helper | `extractTemplateVariables()` is public; `parseTemplateContent()` has no repository runtime call site |
| Debug log and debug mode | D — compatibility facade only | Useful public behavior, but not a reason for inheritance or document ownership |

The minimum meaningful base-class responsibility is therefore not “all ODT
template behavior”. It is a compatibility surface for inherited document and
structured/template helper methods while those methods are progressively
delegated to composed collaborators.

## 6. State ownership contract

### Authoritative state

```text
OdtPackage
├── templatePath
├── workspacePath / tempDir
├── package files and Pictures/
└── persistence, manifest and cleanup

OdtDocumentContext
├── content.xml DOM
├── styles.xml DOM
└── meta.xml DOM

OdtTemplate
├── valueStack
├── repeatStack
└── render orchestration / public facade state
```

`OdtPackage` and `OdtDocumentContext` are the authoritative owners for package
and document state. `OdtTemplate::$templatePath`, `$tempDir`, `$domContent`,
`$domStyles` and `$domMeta` are compatibility mirrors synchronized by
`synchronizePackageState()`. They must not become a second source of truth.

`AbstractOdtTemplate::$log` and `$debugMode` are currently base-owned
compatibility state. They may remain available during migration, but they do
not justify document ownership or a new abstract contract.

### Base-class rule

`AbstractOdtTemplate` must not acquire a second package, workspace, DOM,
manifest or resource registry. Any future base access to document state should
use a narrow facade/service seam and ultimately resolve through
`OdtDocumentContext`/`OdtPackage`.

The current protected DOM properties cannot simply disappear: `PageLayoutOdtTemplate`
and possible external subclasses may access them. Their eventual removal
requires characterization and a compatibility migration strategy.

## 7. Compatibility and protected-method map

### Public inherited surface

| Method | Current role | Contract status |
| --- | --- | --- |
| `setElement()` | structured insertion facade | Preserve signature and observable insertion/style/resource behavior |
| `ensureParagraphStylesExist()` | public style helper | Preserve until an explicit API strategy exists |
| `ensureDefaultListStylesForContentXml()` | public ODF helper | Preserve; unusual public exposure is not grounds for removal in ARCH-06B |
| `extractTemplateVariables()` | public inspection API | Preserve independently of active rendering |
| `enableDebugMode()` / `getDebugLog()` | public diagnostics | Preserve behavior; not a base-class semantic driver |

### Protected surface

| Method/group | Current classification | Required treatment |
| --- | --- | --- |
| `prepareNamespaces()`, `ensureXmlnsAttributes()` | shared ODF/XML helpers | Preserve dispatch; future utility extraction requires characterization |
| `injectImageStyles()`, `adjustBulletIndentation()` | finalization/style seams | Preserve subclass overrides; finalization tests prove observability |
| `ensureTextStylesExist()`, `ensureParagraphStylesExist()`, `insertAutomaticStyle()` | style compatibility helpers | Do not move or redesign under ARCH-06B |
| `ensureTableCellStyleNodesExist()` | suspicious legacy helper | Preserve for now; characterize separately before any change |
| default style helpers | document/style implementation | Keep behavior; defer style ownership to `STYLE-CONTEXT-01` |
| `setValuesInDom()` | mixed scalar/structured compatibility seam | Preserve structured-value path and subclass dispatch |
| `replacePlaceholderWithDom()`, `hasPlaceholder()` | structured insertion seam | Preserve delegation and block/inline behavior |
| `replacePlaceholdersInNode()`, `replaceInText()` | active foreach row-substitution seams | Preserve polymorphism; do not replace with scalar processor behavior |
| `registerStyles()` | structured/style compatibility helper | Preserve style registration behavior |
| `parseTemplateContent()` | inspection/legacy helper | No repository call site found; do not remove |
| `fixBrokenVariables()` | TemplateProcessor compatibility wrapper | Preserve protected dispatch |
| `log()` | diagnostic helper | Preserve for external subclasses; no new semantic contract |

`OdtTemplate` adds additional protected seams, including
`documentContext()`, `copyImageResource()`, template-language wrappers,
`replaceImageInDom()`, `replaceImageInNamedDom()`, `normalizeTemplateDom()`,
`renderTextBoxes()` and legacy text-based paths. ARCH-06 must not assume that
protected methods without repository overrides are safe to delete.

## 8. Abstract-hook analysis

No new abstract method is recommended in ARCH-06B.

### `documentContext()`

This is a useful concrete protected accessor on `OdtTemplate`, but it is not
currently required by every possible `AbstractOdtTemplate` subclass. Adding
`abstract protected function documentContext()` to the public abstract base
would break external subclasses that currently instantiate successfully after
implementing no abstract methods. It would also force subclasses to understand
ARCH-02 internals that the current base does not own.

### Package/resource hook

A package/resource hook is not a universal semantic requirement of an abstract
template object. It belongs to the concrete lifecycle facade and package
collaboration. Making it abstract would turn an implementation detail into a
mandatory external contract.

### Template lifecycle hook

`load()`, `render()`, `save()`, `refresh()` and cleanup are concrete public
facade behavior on `OdtTemplate`, not variation points demonstrated across
multiple template implementations. Their protected override behavior must be
preserved where already observable, but no artificial abstract hook should be
introduced.

### Compatibility consequence

Adding any new abstract method to this public base is a potential source-
compatibility break for external subclasses. A future decision to introduce a
real abstract contract would require, at minimum:

1. an inventory of external-subclass assumptions;
2. a deprecation/migration period or a new opt-in base/interface;
3. tests proving the existing concrete facade and page-layout facade remain
   compatible;
4. a separately approved public API change.

## 9. Remaining domain logic in `AbstractOdtTemplate`

The remaining implementation is not one coherent template-domain algorithm.
It is a mixture of:

- ODF namespace and DOM helpers;
- direct style serialization and default style creation;
- structured-element preparation and compatibility callbacks;
- scalar/structured value dispatch;
- row-local replacement helpers;
- template inspection and normalization wrappers;
- diagnostics.

The active template-language algorithms themselves are now in
`TemplateProcessor`. Structured insertion is in `StructuredElementMaterializer`,
with style/resource coordination still crossing the facade boundary. Metadata,
page layout, package lifecycle and target resolution are composed services.

The following remain explicitly outside ARCH-06:

- `STYLE-CONTEXT-01` and static `StyleMapper` redesign;
- `STYLE-API-02` cleanup/deprecation of legacy style helpers;
- `TEMPLATE-FORMAT-PRESERVATION-01`;
- `TEMPLATE-AUTHORING-UX-01`;
- new structured target operations, table mutation or text-box replacement;
- cloning and Template Instances;
- broad ODF object-model or page-layout redesign.

## 10. Legacy finding: `ensureTableCellStyleNodesExist()`

The current method signature accepts `$styleNodes`, but its body iterates
`$styleMap` and treats entries as raw style options. `$styleMap` is not defined
in the method. The finding is therefore confirmed in the current source.

Repository search found no runtime call site and no test that exercises this
protected method. It is potentially externally reachable through inheritance,
so it cannot safely be called dead solely from repository evidence.

Current classification:

```text
repository-unused, externally possible, likely stale/buggy compatibility path
```

This is not repaired in ARCH-06B. It should receive a dedicated
characterization/ownership decision, likely under `STYLE-API-02` or a focused
style-compatibility slice. A repair would be a behavior decision, not a
mechanical base-class cleanup.

## 11. Alternative architecture models

### Model A — Real abstract base class

`AbstractOdtTemplate` would become a small, deliberate abstract ODT-template
contract with genuine extension points.

| Criterion | Assessment |
| --- | --- |
| Semantic clarity | Potentially high, but current implementations do not demonstrate the required variation |
| Backward compatibility | High risk if new abstract methods are added |
| Implementation risk | High; would require separating compatibility implementation from contract |
| External subclass impact | High and currently unknown |
| Maintainability | Good only if at least two real implementations need the same contract |
| ARCH-02–05 consistency | Weak unless the contract delegates cleanly to existing services |
| CV extensibility | No demonstrated practical benefit yet |

This model is not justified by current evidence.

### Model B — Compatibility base class

Keep `AbstractOdtTemplate` as a non-expanding compatibility layer while
`OdtTemplate` remains the concrete public facade and composed services own
domains.

| Criterion | Assessment |
| --- | --- |
| Semantic clarity | Moderate if explicitly documented as transitional compatibility |
| Backward compatibility | Best current option |
| Implementation risk | Low |
| External subclass impact | Preserved |
| Maintainability | Acceptable if no new responsibilities are added |
| ARCH-02–05 consistency | Strong as an incremental migration model |
| CV extensibility | Preserves all existing public and protected behavior |

This is the recommended near-term target.

### Model C — Composition / eventual removal

Gradually move remaining implementation behind composed collaborators, retain
protected wrappers during a migration period, and eventually reduce or
deprecate `AbstractOdtTemplate`.

| Criterion | Assessment |
| --- | --- |
| Semantic clarity | Highest long-term potential |
| Backward compatibility | Manageable only with a deliberate migration path |
| Implementation risk | Medium/high if attempted as a rewrite |
| External subclass impact | Significant and currently unknown |
| Maintainability | Strong after boundaries stabilize |
| ARCH-02–05 consistency | Strongest architectural direction |
| CV extensibility | Best fit for package/document, template, structured and page-layout composition |

This is the recommended long-term direction, but not authorization for removal
or deprecation in ARCH-06B.

## 12. Recommended target semantics

The target semantics are:

```text
OdtTemplate
├── public facade and lifecycle orchestration
├── value/repeat assignment state
├── compatibility wrappers
└── composed document/template services

AbstractOdtTemplate
├── retained compatibility base during migration
├── no new abstract methods
├── no new document/package ownership
└── no new domain responsibilities

OdtPackage / OdtDocumentContext / services
└── authoritative document, package and domain state
```

Invariants:

- public method signatures and lifecycle behavior remain unchanged;
- inherited public helpers remain callable until an explicit compatibility
  policy exists;
- protected polymorphic seams remain observable;
- package/context state remains authoritative;
- `TemplateProcessor`, `StructuredElementMaterializer`, metadata, page-layout
  and target services remain independently scoped;
- no new abstract method is introduced merely to formalize current concrete
  implementation;
- any future extraction is preceded by characterization where external
  subclass behavior could be affected.

## 13. CV and real-world benchmark

The existing professional CV usage validates the facade/service split, not a
new base-class hierarchy:

- `RichText`, `Paragraph`, lists and tables need structured materialization;
- `ImageElement` and `setImage()` need package resources plus document XML;
- `replaceImageByName()` needs typed frame resolution and legacy dimensions;
- page margins use `PageLayoutManager` through `PageLayoutOdtTemplate`;
- experience and education repetition uses existing template-language state;
- multi-page layout and styles require document/package ownership.

None of these use cases requires a second subclass-specific implementation of
an abstract template algorithm. They benefit from composition behind the
existing `OdtTemplate` facade and from preserving compatibility wrappers.
A new abstract method would add framework theory without helping the CV
benchmark.

## 14. Explicit non-goals

ARCH-06B does not:

- remove, rename or deprecate `AbstractOdtTemplate`;
- add abstract methods or public/protected API;
- move production code or change tests;
- redesign package or document context ownership;
- consolidate `StyleMapper`/`StyleWriter` or implement `STYLE-CONTEXT-01`;
- redesign template syntax, formatting preservation or authoring UX;
- implement named text-box/table operations, cloning, removal or whole-object
  replacement;
- repair `ensureTableCellStyleNodesExist()`;
- redesign PageLayoutOdtTemplate or remove its override;
- change `setRepeatingData()` or legacy text-based paths.

## 15. Proposed implementation slices

These are reviewable follow-up candidates, not implementation authorization.

### ARCH-06C — Base-class compatibility characterization

Add focused tests for inherited public methods, protected overrides, package /
context mirrors, page-layout finalization and the remaining structured/value
seams. Include a deliberate external-subclass compatibility fixture where
practical. No production movement.

### ARCH-06D — Documented facade/state access boundary

If characterization supports it, introduce only narrow internal access paths
that make `OdtPackage`/`OdtDocumentContext` authoritative while retaining
protected compatibility mirrors and methods. No new abstract methods.

### ARCH-06E — One responsibility extraction at a time

Select a genuinely bounded remaining area, probably a style/ODF helper group
only after `STYLE-API-02` and `STYLE-CONTEXT-01` constraints are resolved.
Retain wrappers and do not combine style cleanup with base-class removal.

### Later — Composition migration / deprecation decision

Only after external-subclass and protected-seam evidence exists should the
project decide whether to offer a composition-first replacement path or a
major-version deprecation/removal plan for the base class.

## 16. Open questions

1. Where is the missing ARCH-06A audit document, and what exact findings did it
   contain?
2. Which external users subclass `AbstractOdtTemplate` or rely on its protected
   properties?
3. Which inherited public style helpers are intentionally supported API versus
   historical exposure?
4. Should protected DOM mirrors eventually be replaced by protected accessors,
   and what migration path preserves subclasses that read them directly?
5. Is `adjustBulletIndentation()` still a meaningful variation point after the
   corrected base implementation?
6. Should `parseTemplateContent()` remain an inspection compatibility helper or
   receive a deprecation plan?
7. How should the undefined-variable style-cell helper be handled without
   changing unknown external behavior?
8. Can a document-scoped style context be introduced without coupling it to
   the base-class decision?
9. What public/documentation policy should govern eventual base-class
   deprecation?

## 17. Recommendation

ARCH-06B should adopt **Model B as the compatibility target** and **Model C as
the long-term direction**. No real abstract hook is justified by current
repository evidence. The next safe step is ARCH-06C characterization, not a
base-class rewrite and not the addition of abstract methods.

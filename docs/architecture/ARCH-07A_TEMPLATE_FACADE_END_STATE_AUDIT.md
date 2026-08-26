# ARCH-07A — Template Facade / Base Structure End-State Audit

## Status

Architecture/design audit complete. No production-code, test, API, class,
sample, or roadmap changes are part of ARCH-07A.

Repository basis: `architecture/arch-07-template-facade` at `071ecb1`.

The purpose of this audit is to define a credible structural end-state before
`DOCUMENT-DEFAULTS-01`, `STYLE-CONTEXT-01`, and `ASSET-CONTEXT`. This document
is not a change contract and does not authorize implementation.

## 1. Executive conclusion

The repository evidence does not support retaining `AbstractOdtTemplate` as a
permanent abstract type. It has no abstract method contract, there is only one
direct production implementation, and its remaining contents are a mixture of
compatibility seams, ODF/style helpers, structured-insertion coordination,
template inspection, and debugging.

The recommended end state is therefore:

```text
OdtTemplate
├── OdtPackage
├── OdtDocumentContext
├── TemplateProcessor
├── StructuredElementMaterializer
├── TemplateTargetResolver
├── MetadataManager
├── PageLayoutManager
└── future document-scoped style/default/asset collaborators

PageLayoutOdtTemplate
└── a deliberately reconsidered page-layout facade or composition wrapper
```

`OdtTemplate` should become the independent public template facade. The
current `AbstractOdtTemplate` implementation should be dismantled in bounded
slices, with public methods retained where they are still part of the
supported facade and protected hooks retained only where characterization and
migration require them.

This is a recommendation for the end state, not an implementation decision in
ARCH-07A. A later change contract must decide the exact compatibility bridge,
including whether `AbstractOdtTemplate` remains temporarily as a deprecated
compatibility layer.

## 2. Evidence and scope

The audit reviewed:

- `src/AbstractOdtTemplate.php`;
- `src/OdtTemplate.php`;
- `src/PageLayoutOdtTemplate.php`;
- package, document, template-processing, structured-element, metadata,
  page-layout, style, and target-resolution services;
- ARCH-01 through ARCH-06 and the ARCH-06 closeout;
- API, lifecycle, protected-polymorphism, structured-insertion, style,
  package, page-layout, and public-sample tests;
- repository-wide inheritance, `parent::` calls, samples, documentation, and
  Composer exposure.

The repository is pre-1.0, currently published as 0.90. This makes a small,
explicitly documented compatibility change possible, but not a reason to
discard observable public behavior without characterization and migration
planning.

## 3. Current structural model

The actual hierarchy is:

```text
AbstractOdtTemplate
        ↓
    OdtTemplate
        ↓
PageLayoutOdtTemplate
```

`AbstractOdtTemplate` is formally abstract but defines no abstract methods.
`OdtTemplate` is the only direct production subclass. `PageLayoutOdtTemplate`
adds page-layout convenience methods and overrides
`adjustBulletIndentation()`.

The extracted composition around the hierarchy is already substantial:

- `OdtPackage` owns extraction, workspace, package resources, manifest,
  persistence, ZIP rebuilding, and cleanup.
- `OdtDocumentContext` owns the mutable `content.xml`, `styles.xml`, and
  `meta.xml` DOM instances.
- `TemplateProcessor` owns active stateless template-language operations.
- `StructuredElementMaterializer` owns constructed `OdtElement` DOM insertion.
- `TemplateTargetResolver` resolves typed existing native targets without
  mutating them.
- `MetadataManager` and `PageLayoutManager` own focused document services.

The remaining inheritance is therefore no longer the main composition model.
It is primarily an implementation-inheritance and compatibility mechanism.

## 4. Responsibility inventory: `AbstractOdtTemplate`

### 4.1 Public facade/API

`AbstractOdtTemplate` currently exposes:

- `setElement()`;
- `ensureParagraphStylesExist()`;
- `ensureDefaultListStylesForContentXml()`;
- `extractTemplateVariables()`;
- `enableDebugMode()`;
- `getDebugLog()`.

These methods are inherited by `OdtTemplate` and therefore appear to callers
as part of the `OdtTemplate` API. The methods have different semantic owners;
they should not be treated as one coherent base-class contract.

`setElement()` is a genuine public structured-content facade operation. Its
long-term home is the public facade, while preparation and materialization
should remain delegated to focused collaborators.

The public style helpers are unusual API exposure. Samples use
`ensureParagraphStylesExist()` directly, and API tests cover style persistence.
They cannot be removed as incidental cleanup without an explicit pre-1.0
compatibility decision.

`extractTemplateVariables()` is a useful public inspection operation, but its
parser is separate from active rendering and is not evidence for an abstract
template type.

Debug methods are facade compatibility behavior, not document-model behavior.

### 4.2 Protected compatibility and extension seams

The following protected methods are reached from public execution paths or
are used by repository subclasses/tests:

| Method | Current role | Evidence | Long-term assessment |
|---|---|---|---|
| `fixBrokenVariables()` | placeholder repair wrapper | render, load, structured insertion, ARCH-06 tests | compatibility seam; processor-facing |
| `setValuesInDom()` | scalar replacement plus structured-value branch | render and ARCH-06 dispatch test | facade seam; structured branch must remain explicit |
| `replacePlaceholderWithDom()` | structured insertion callback | `setElement()`, ARCH-06 dispatch test | compatibility seam around materializer |
| `replacePlaceholdersInNode()` | row-clone replacement and recursive DOM replacement | active foreach and structured paths | compatibility-sensitive; not equivalent to scalar processing |
| `replaceInText()` | raw row text replacement | foreach subclasses/tests | compatibility-sensitive protected hook |
| `adjustBulletIndentation()` | finalization post-processing | save and PageLayout override tests | historical hook; migration required |
| `injectImageStyles()` | finalization/style injection | save and finalization tests | compatibility hook, not base-class semantics |
| `prepareNamespaces()` | ODF XPath helper | inherited implementation | likely document/XML helper |
| `ensureXmlnsAttributes()` | ODF XML setup | inherited implementation | document/XML helper |
| `ensureTextStylesExist()` | direct style creation | structured/style paths | style compatibility path |
| `ensureDefaultListStyles()` | default ODF list styles | load preparation | document defaults/style concern |
| `ensureDefaultParagraphStyles()` | default paragraph styles | load preparation | document defaults/style concern |
| `registerStyles()` | direct style serialization | structured insertion | style/materialization coordination |
| `hasPlaceholder()` | DOM inspection for insertion | materializer callback | narrow insertion helper |
| `log()` | debug state update | debug API | diagnostic compatibility |

Protected visibility is not by itself proof of a supported extension API.
However, ARCH-04 and ARCH-06 provide direct evidence that dynamic dispatch is
observable for several of these methods. They must therefore be classified
individually rather than removed as a group.

### 4.3 Template processing

Historically, `AbstractOdtTemplate` contained scalar replacement,
placeholder repair, and recursive row replacement. Active scalar/filter,
condition, foreach, `nl2br`, list, and normalization operations now delegate
to `TemplateProcessor` through facade wrappers in `OdtTemplate` or the base.

The remaining `setValuesInDom()` method is not purely template processing: it
also recognizes `OdtElement` values and invokes structured insertion. Moving
it wholesale would conflate the two boundaries that ARCH-04 and ARCH-05
deliberately separated.

The correct end-state is a facade-level coordination seam which passes scalar
work to `TemplateProcessor` and structured values to structured insertion.

### 4.4 Structured insertion

`setElement()` currently coordinates:

1. style discovery and registration;
2. direct style-node preparation;
3. image-resource copying;
4. placeholder normalization;
5. content and styles DOM materialization;
6. inline/block replacement semantics.

`StructuredElementMaterializer` now owns the actual DOM replacement rules,
while the facade/base still owns compatibility preparation and callbacks.

This is a real public facade responsibility, but not a coherent reason for an
abstract base class. The eventual facade should retain `setElement()` while
making the collaborator boundaries explicit.

### 4.5 Template inspection and normalization

The relevant methods are:

- `extractTemplateVariables()`;
- `parseTemplateContent()`;
- `fixBrokenVariables()`;
- `normalizeTemplateDom()` in `OdtTemplate`/`TemplateProcessor`.

These are related but not identical:

- inspection parses a serialized DOM for reporting;
- normalization repairs editor-split placeholders;
- `fixBrokenVariables()` repairs node fragments during active workflows.

They are mostly stateless and have clear inputs/outputs, but the repository
does not establish a need for a public inspection subsystem or reuse outside
the facade. An immediate new service would mostly move code mechanically and
could create a second parser/normalization abstraction.

The recommended near-term ownership is a narrow facade/document-processing
collaboration, with a separate `TemplateInspector` or normalizer considered
only if a concrete second consumer or independent test contract appears.

### 4.6 Style registration and writing

The base still contains:

- namespace and XML setup;
- image-style injection;
- text and paragraph style creation;
- automatic-style insertion;
- table-cell style handling;
- default list/paragraph styles;
- style registration;
- list-label indentation post-processing.

These operations are real ODF/style responsibilities, but they are not one
single base-class responsibility. They overlap with `StyleMapper`,
`StyleWriter`, element-specific direct DOM paths, and future
`STYLE-CONTEXT-01`.

No new style service should be invented in ARCH-07A. The style subsystem needs
its own document-scoped state decision. Structural consolidation should
preserve the existing facade entry points until that work is specified.

### 4.7 Image and resource handling

`OdtTemplate::setImage()` and `replaceImageByName()` remain mostly in the
concrete facade. The base participates in structured element image assets and
style registration.

The concerns are different:

- package resource copying belongs to `OdtPackage` today;
- image/frame DOM generation belongs to image/structured document behavior;
- manifest synchronization belongs to `OdtPackage`;
- style registration remains coupled to `StyleMapper` and finalization.

An `AssetManager` is not justified merely to remove methods from the base.
`ASSET-CONTEXT` should define document-scoped ownership later.

### 4.8 DOM/ODF helpers

Namespace preparation, XPath setup, style-node insertion, placeholder lookup,
and ODF default-node creation are technical helpers. Some are used directly by
the existing compatibility surface; others are only internal.

They should migrate according to their consumer and document ownership, not
to a generic `BaseTemplate` replacement. A small XML/ODF helper is justified
only where multiple independent document services need the same behavior and
the helper has stable semantics.

### 4.9 Debugging

`log`, `debugMode`, `enableDebugMode()`, `log()`, and `getDebugLog()` are
document-instance diagnostic state. They are not evidence for inheritance and
need not be moved before structural consolidation. Their public behavior must
remain covered if the base is removed.

### 4.10 Lifecycle/state compatibility

`AbstractOdtTemplate` declares historical `templatePath`, `tempDir`,
`domContent`, and `domStyles` properties. `OdtTemplate` redeclares several of
them and adds `domMeta`. The concrete facade synchronizes these mirrors from
`OdtPackage`/`OdtDocumentContext` after construction and `load()`.

The authoritative state is outside the base. The declarations remain only
because inherited methods and possible external subclasses can access them.

## 5. Responsibility inventory: `OdtTemplate`

`OdtTemplate` is already the actual public facade and should remain so.

Its durable responsibilities are:

- constructing and coordinating one document-generation session;
- owning assignment state (`valueStack`, `repeatStack`);
- exposing the public assignment/render/save/load lifecycle;
- dispatching active template processing over content and styles DOMs;
- exposing structured insertion and image operations;
- delegating metadata and package operations;
- coordinating style/finalization ordering;
- preserving public/protected compatibility seams during migration.

Responsibilities that should not remain as permanent implementation detail in
the facade are package ZIP mechanics, active template algorithms, native
structured materialization, and focused metadata/page-layout implementation.
Those already have collaborators. Style and asset ownership remain future
phase work rather than ARCH-07 extraction targets.

## 6. Responsibility inventory: `PageLayoutOdtTemplate`

`PageLayoutOdtTemplate` provides:

- `setPageMargins()`;
- `setPageLayout()`;
- a page-layout-specific `adjustBulletIndentation()` override.

`setPageLayout()` delegates to `PageLayoutManager` through
`documentContext()`. This is composition behind an inherited public facade.

The class is semantically related to `OdtTemplate`: it processes the same ODT
template lifecycle and adds page-layout operations. That is a plausible
subtype relationship at the API level, but the current implementation also
contains accidental inheritance coupling. In particular,
`adjustBulletIndentation()` is unrelated to page-layout semantics and exists
because of historical implementation interaction.

The strongest end-state options are either:

- retain a thin page-layout compatibility facade that delegates to a
  `PageLayoutManager`; or
- expose page-layout operations on the main facade and retire the specialized
  subclass after a documented migration period.

The current evidence does not justify preserving the subclass as a broad
implementation base. It does justify treating it as a real compatibility
surface until a separate API decision is made.

`documentContext()` is a useful protected access seam on `OdtTemplate`. It
returns the package-owned current context and remains valid after reload. It
should not be copied into `AbstractOdtTemplate` or made a new abstract method.

## 7. Inheritance findings

### Genuine subtype semantics

`PageLayoutOdtTemplate` is a reasonable public subtype in the limited sense
that it is an `OdtTemplate` with additional page-layout operations and the same
constructor/render/save lifecycle.

The subtype semantics are weakened by the fact that page-layout behavior is
already delegated to a service and the subclass override of
`adjustBulletIndentation()` is historical rather than domain-driven.

### Implementation inheritance

`OdtTemplate` inherits a large amount of implementation from
`AbstractOdtTemplate`, including style helpers, structured insertion support,
debugging, and protected DOM operations. Much of this is implementation reuse,
not a common abstract type contract.

### Compatibility inheritance

The strongest current reason for the base class is compatibility:

- inherited public methods;
- protected override points;
- protected property access;
- external subclasses that may exist outside the repository.

ARCH-06C proves this is not merely theoretical for repository subclasses, but
the repository cannot establish the scale of external use.

### Historical coupling

The duplicated state declarations, direct base DOM access, style/finalization
hooks, and page-layout bullet-indentation override are historical coupling.
They should not determine the end-state architecture.

Repository-wide search found no additional production classes extending
`AbstractOdtTemplate` or `OdtTemplate`. Test-only subclasses deliberately
exercise protected dispatch. Samples and public documentation instantiate
`OdtTemplate` and `PageLayoutOdtTemplate`; they do not subclass them.

## 8. Public/protected API matrix

| Surface | Visibility | Actual use | Override evidence | Long-term role | Risk |
|---|---|---|---|---|---|
| `setElement()` | public inherited | samples, integration/API tests | protected replacement callback | retain on facade | high |
| `ensureParagraphStylesExist()` | public inherited | Samples 04/07/12/14, tests | none found | retain or explicitly deprecate after style review | high |
| `ensureDefaultListStylesForContentXml()` | public inherited | indirect/internal | none found | compatibility only unless a use case is confirmed | medium |
| `extractTemplateVariables()` | public inherited | public inspection API | none found | retain on facade or compatibility wrapper | medium |
| `enableDebugMode()` / `getDebugLog()` | public inherited | API surface | none found | retain on facade | medium |
| `fixBrokenVariables()` | protected | load/render/structured paths | ARCH-06 dispatch | migration seam | high |
| `setValuesInDom()` | protected | render | ARCH-06 dispatch | facade seam | high |
| `replacePlaceholderWithDom()` | protected | `setElement()` | ARCH-06 dispatch | insertion seam | high |
| `replacePlaceholdersInNode()` | protected | foreach/structured paths | ARCH-04 characterization | migration seam | high |
| `replaceInText()` | protected | row replacement | ARCH-04 subclass override | migration seam | high |
| `adjustBulletIndentation()` | protected | save | PageLayout/ARCH-06 override | isolate, preserve during migration | high |
| `injectImageStyles()` | protected | save | finalization probe | style compatibility seam | medium-high |
| `prepareNamespaces()` | protected | inherited helpers | no override found | move by consumer if needed | medium |
| `ensureXmlnsAttributes()` | protected | inherited helpers | no override found | move by consumer if needed | medium |
| `ensureTextStylesExist()` | protected | structured style paths | no override found | style compatibility seam | medium-high |
| `ensureDefaultListStyles()` | protected | load preparation | no override found | document/style responsibility | medium |
| `ensureDefaultParagraphStyles()` | protected | load preparation | no override found | document defaults/style responsibility | medium |
| `registerStyles()` | protected | structured insertion | no override found | style/materialization coordination | medium-high |
| `hasPlaceholder()` | protected | materializer callback | no override found | narrow insertion helper | medium |
| `log()` | protected | debug methods | no override found | diagnostic compatibility | low-medium |

The matrix separates actual evidence from theoretical external use. In
particular, methods without repository overrides are not automatically safe to
remove because protected visibility permits external subclassing, but neither
are they automatically permanent extension APIs.

## 9. State mirrors

### Current use

The historical properties are used directly by inherited or concrete methods:

- `$domContent`: rendering, structured insertion, list defaults,
  inspection, image operations;
- `$domStyles`: rendering, style registration, image styles, finalization,
  page-related and structured operations;
- `$domMeta`: metadata compatibility state on `OdtTemplate`;
- `$templatePath`: compatibility and lifecycle state;
- `$tempDir`: image/resource and historical package-path operations.

### Subclass use

Repository subclasses primarily use protected methods, not direct property
access. ARCH-06C nevertheless characterizes mirror identity because external
subclasses may read or mutate these properties. No repository evidence proves
that external direct property access exists.

### End-state need

The mirrors are not needed for authoritative internal ownership. They are
needed only as a possible compatibility bridge while inherited methods and
external subclasses remain supported.

The recommended migration is staged:

1. keep mirrors synchronized while public/protected behavior is moved;
2. route new internal code through `OdtPackage`/`OdtDocumentContext` or narrow
   facade accessors;
3. characterize direct protected-property compatibility if removal is planned;
4. deprecate or remove mirrors only through an explicit pre-1.0 policy;
5. do not retain them as a second source of truth.

Removing a protected property can break external subclasses at parse time or
runtime. That risk is real, but it is lower than preserving a misleading base
architecture indefinitely if a clear migration path is supplied.

## 10. Service-boundary findings

Existing services are appropriately bounded:

- `OdtPackage`: physical package and resource lifecycle;
- `OdtDocumentContext`: core DOM state;
- `TemplateProcessor`: stateless template language;
- `StructuredElementMaterializer`: constructed ODF subtree insertion;
- `TemplateTargetResolver`: typed, read-only native target resolution;
- `MetadataManager`: metadata DOM operations;
- `PageLayoutManager`: page-layout DOM operations.

The following should remain coordinated by `OdtTemplate` for now:

- assignment state and render orchestration;
- public compatibility methods;
- the distinction between scalar values and `OdtElement` values;
- image API compatibility;
- finalization ordering;
- compatibility callbacks into protected seams.

No additional service is justified solely by the current ARCH-07A evidence.

Potential future services are phase-specific:

- document-scoped style state belongs to `STYLE-CONTEXT-01`;
- document defaults belong to `DOCUMENT-DEFAULTS-01`;
- asset lifecycle belongs to `ASSET-CONTEXT`;
- a finalizer should be reconsidered only after style inputs are
  document-scoped.

Creating `TemplateInspector`, `OdfHelper`, `Finalizer`, or `AssetManager`
immediately would risk service explosion and duplicate mutable state.

## 11. Legacy and suspicious areas

### `ensureTableCellStyleNodesExist()`

The method accepts `$styleNodes` but its implementation references `$styleMap`.
Repository search found no caller or test. The evidence supports this
classification:

```text
repository-unused
externally possible through protected inheritance
likely stale or buggy
desired semantics unknown
```

It is not safe to call it “dead” or repair it during structural consolidation.
It should be handled in a dedicated style/API compatibility decision, unless a
future bounded migration requires its disposition.

### Other legacy candidates

- `setRepeatingData()` is public and remains a separate immediate-processing
  path.
- `applyConditionalsInDomTextBased()` is protected and repository-unused.
- `applyRepeatingInDomTextBased()` is protected and repository-unused.
- `splitConditionalsInTextNodes()` is protected and repository-unused.
- `parseTemplateContent()` has no runtime call site beyond public inspection.
- inherited direct style helpers have unclear external usage.

These are not all equivalent. Public methods are compatibility-sensitive;
protected repository-unused methods are externally possible but unproven;
private unused code would have a lower removal risk. No method in this group
has sufficient evidence for unqualified removal in ARCH-07A.

## 12. End-state alternatives

### Model 1 — Retain a real compatibility/base type

```text
OdtTemplateBase                    (normal or abstract, but narrow)
        ↓
    OdtTemplate
        ↓
PageLayoutOdtTemplate
```

The base would need a semantically coherent contract: common template
capabilities, deliberate context access, and explicitly supported extension
points. Existing broad style and DOM implementation would have to move out or
be clearly marked as transitional.

Advantages:

- lowest immediate source-compatibility disruption;
- protected hooks can remain centralized;
- existing subclasses can migrate gradually;
- familiar inheritance shape for current users.

Costs and risks:

- the repository has not identified a genuine mandatory abstract operation;
- a new abstract contract would likely be artificial;
- a normal base with a new name would still need a large compatibility body;
- protected property mirrors and inherited implementation would remain a
  long-term coupling pressure;
- future style/default/document services could continue accumulating on the
  base by convenience;
- naming and responsibility would remain difficult for new developers unless
  the base became very small.

Assessment: viable only as a deliberately temporary compatibility architecture,
not the preferred final structure. If retained temporarily, `TemplateBase` or
`OdtTemplateCompatibilityBase` would be more honest names than
`AbstractOdtTemplate`, but neither should be introduced without a concrete
migration purpose.

### Model 2 — Composition-first independent facade

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
```

`OdtTemplate` owns the public lifecycle and document-session orchestration.
Protected compatibility methods become facade wrappers where necessary, and
legacy inherited methods either become explicit facade methods or receive a
time-bounded compatibility bridge.

Advantages:

- accurately reflects the architecture already established by ARCH-02 to
  ARCH-06;
- removes the false implication of an abstract domain contract;
- makes authoritative state ownership obvious;
- gives future document defaults, style state, and assets a clear facade-level
  integration point;
- prevents new behavior from being added to a historical base class;
- is easier for new developers to understand.

Costs and risks:

- removing `AbstractOdtTemplate` from the active type hierarchy can break
  `instanceof`, type hints, reflection, and external subclasses;
- protected method/property compatibility requires an explicit bridge;
- the first slices may temporarily duplicate forwarding code;
- `PageLayoutOdtTemplate` needs a deliberate compatibility decision;
- tests must prove dispatch and lifecycle behavior after each move.

Assessment: best semantic and long-term architectural fit, with manageable
pre-1.0 migration risk if the compatibility policy is explicit.

### Model 3 — Keep `AbstractOdtTemplate` only as a deprecated compatibility shell

```text
OdtTemplate                     (independent implementation/facade)
    ↑
AbstractOdtTemplate             (thin deprecated compatibility shell)
```

This model separates active architecture from historical type compatibility.
It is useful only if PHP inheritance can preserve the required old type and
protected behavior without reintroducing duplicate state ownership. It may
require careful forwarding or an adapter arrangement and therefore needs a
technical prototype in the change-contract phase.

Advantages:

- preserves a migration path for external subclasses and `instanceof` users;
- active architecture can be composition-first;
- gives a clear deprecation endpoint.

Costs and risks:

- PHP inheritance direction and protected member compatibility may make a
  clean shell difficult;
- a shell can become permanent accidental architecture;
- forwarding protected hooks may preserve too much implementation coupling;
- exact type and constructor behavior require characterization.

Assessment: a promising migration mechanism, not yet a confirmed feasible
implementation. It should be evaluated before choosing a hard removal.

## 13. Recommendation

Adopt Model 2 as the target architecture, with Model 3 investigated as the
preferred compatibility migration mechanism if it can be implemented without
duplicating authoritative state or preserving the entire historical base.

Do not add abstract methods to `AbstractOdtTemplate`. Do not rename it merely
to make the current implementation look better. Either keep it temporarily as
an explicitly transitional compatibility artifact or remove it from the
active architecture through a planned migration.

The end-state naming should be:

- `OdtTemplate`: public, concrete, composition-first facade;
- `PageLayoutOdtTemplate`: retained only while its compatibility value exceeds
  the cost of a migration, and otherwise replaced by explicit page-layout
  facade access;
- no permanent `AbstractOdtTemplate` unless a future evidence-based contract
  emerges, which is currently unlikely.

This recommendation is stronger than ARCH-06's intentionally conservative
near-term compatibility-base position. ARCH-06 characterized the current
state; ARCH-07A now has the mandate to select an end-state and can use the
pre-1.0 context to prefer structural clarity.

## 14. Relationship to Phase B

The composition-first facade provides the cleanest attachment points:

```text
OdtTemplate
├── documentContext()
│   ├── document defaults
│   ├── StyleContext
│   └── document-scoped asset state
├── OdtPackage
│   └── physical package resources and persistence
└── document/template collaborators
```

More precisely:

- `DOCUMENT-DEFAULTS-01` should attach defaults to the document-generation
  session/context, with explicit element settings taking precedence.
- `STYLE-CONTEXT-01` should replace process-wide style ownership with
  document-scoped state, while defining how legacy static registration is
  imported or preserved.
- `ASSET-CONTEXT` should define document-scoped asset lifecycle without
  making package mechanics or image target semantics universal.

The facade should orchestrate these collaborators but should not become their
implementation container. A retained broad abstract base would make such
future growth more likely to return to inheritance.

## 15. Proposed ARCH-07 implementation slices

The following is a proposal for a later change contract, not authorization to
implement now.

### ARCH-07B — Change Contract

Define the selected migration model precisely:

- active versus compatibility API;
- exact fate of `AbstractOdtTemplate`;
- `PageLayoutOdtTemplate` strategy;
- public and protected method mapping;
- property mirror policy;
- deprecation and pre-1.0 breaking-change policy;
- no style/default/asset redesign.

### ARCH-07C — Compatibility gap characterization

Add tests only where the existing evidence is insufficient for the selected
migration, such as:

- `instanceof` and type-hint behavior;
- direct protected-property access through a test subclass;
- inherited public method availability;
- external-style subclass construction assumptions;
- `PageLayoutOdtTemplate` compatibility.

Existing ARCH-04/05/06 tests remain the regression baseline and should not be
duplicated without a specific migration gap.

### ARCH-07D — Facade ownership slice

Move one bounded responsibility or compatibility wrapper into the independent
`OdtTemplate` facade while preserving public behavior and protected dispatch.
The first slice should avoid styles and assets; lifecycle/document-context
access or debug/inspection compatibility is safer than broad style movement.

### ARCH-07E — Structured/template-processing facade slices

Move or wrap structured insertion and template-processing compatibility paths
one at a time. Preserve the distinction between scalar processing, constructed
materialization, and native target resolution.

### ARCH-07F — State mirror migration

Route internal consumers to authoritative package/context state, retain mirrors
as a temporary bridge if needed, and characterize their final removal or
deprecation separately.

### ARCH-07G — Page-layout compatibility decision

Decide whether to retain `PageLayoutOdtTemplate` as a thin facade, add an
explicit page-layout entry point to `OdtTemplate`, or support both for a
documented transition period. Remove the historical bullet-indentation
coupling only when dispatch and output behavior are characterized.

### ARCH-07H — Legacy disposition and closeout

Handle only legacy items required by structural consolidation. Leave style
legacy work, `ensureTableCellStyleNodesExist()`, and broad static-state changes
to their appropriate contracts unless they block the selected structure.

Run full validation, review the resulting public/protected surface, and close
ARCH-07 before Phase B begins.

## 16. Render-sensitive validation for later slices

ARCH-07A changes no runtime behavior and requires no visual baseline update.

Later slices require LibreOffice/PDF/PNG validation when they affect:

- render ordering;
- DOM normalization or placeholder replacement;
- structured insertion or text-box handling;
- styles or automatic styles;
- image frames, dimensions, anchors, or manifest/resource paths;
- page-layout delegation or list indentation;
- save/reload serialization;
- any public sample output.

Pure type/forwarding changes still require package/XML and lifecycle tests,
because dispatch and reload state are compatibility behavior even when the
intended XML is unchanged.

## 17. Contradictions and uncertainties

- ARCH-06 correctly described retaining `AbstractOdtTemplate` as the near-term
  compatibility target, but its closeout explicitly says that this is not the
  desired final structure. ARCH-07A therefore treats it as migration state,
  not as an end-state constraint.
- No repository evidence establishes the amount of external subclassing,
  protected property access, or `instanceof AbstractOdtTemplate` usage.
- The feasibility of a thin deprecated compatibility shell in PHP is not
  established and requires a prototype/characterization in ARCH-07B/C.
- `PageLayoutOdtTemplate` has legitimate public subtype semantics but also
  historical coupling through `adjustBulletIndentation()`; the right final API
  shape is not yet an implementation-level decision.
- The desired semantics of `ensureTableCellStyleNodesExist()` remain unknown.
- `extractTemplateVariables()` is public, while `parseTemplateContent()` is
  protected and appears to be an internal implementation detail; their future
  separation has not been formalized.
- Style state remains process-wide in important paths. Structural consolidation
  must not imply that `OdtTemplate` already owns clean document-scoped style
  state.

## 18. Final audit decision

ARCH-07A recommends a composition-first, concrete `OdtTemplate` facade as the
long-term structural foundation. `AbstractOdtTemplate` should not receive a
new abstract contract and should not remain a permanent broad implementation
base. A thin, deprecated compatibility bridge may be retained temporarily if
it can preserve necessary public/protected behavior without preserving
duplicate authoritative state.

The next step is ARCH-07B: define the exact migration/change contract and
characterization gaps. No implementation begins from this audit alone.

Semantics before implementation.

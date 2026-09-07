# STYLE-API-02B — Target Public Style API Change Contract

Status: **DRAFT CHANGE CONTRACT — SEMANTICS BEFORE IMPLEMENTATION**  
Baseline: `develop` at `e4c48c859a4ef8a1362b8c303d3f3944ebb52d4c`  
Branch: `architecture/style-api-02b-target-public-api`

## 1. Purpose

STYLE-API-02A established that the current public style surface contains several
architectural generations at once. P0 then characterized the three boundaries
that must be understood before designing a target API:

1. normal application styling is primarily element-centric;
2. reusable named paragraph styles are an intentional advanced public use case;
3. custom elements and template subclasses still observe both semantic and
   compatibility hooks.

This document defines the target public style model that future STYLE-API-02
implementation slices should converge toward.

It is a **change contract**, not an implementation patch. It does not authorize
removing compatibility APIs whose exact behavior is still gated by P1/P2
characterization.

The central question is:

> Which style concepts should a library user or extension author understand,
> and which historical mechanisms should remain compatibility implementation
> rather than equal peers in the public mental model?

## 2. Sources and accepted baseline

This contract is based on the current `develop` architecture after PR #60 and
therefore assumes the completed STYLE-CONTEXT-01 semantic ownership model.

Accepted facts include:

- `OdtDocumentContext` / `StyleContext` own modern document-local style
  semantics;
- structured elements emit semantic `StyleRequirement` definitions and
  references;
- physical package resources remain owned by `OdtPackage`;
- normal application documentation teaches `Paragraph`, `RichText`,
  `RichTableCell`, image/frame elements, and friendly style option arrays;
- `StyleMapper::registerParagraphStyle()` is intentionally documented and used
  by the professional CV sample as advanced named-style authoring;
- direct `StyleWriter` is not taught as the normal application-facing API;
- semantic and legacy element hooks remain dynamically dispatched;
- protected/public `OdtTemplate` compatibility facades remain
  subclass-observable;
- the top-level `OdtTemplate::setElement()` `HasStyles` compatibility phase is
  currently non-dispatching because the unqualified interface name does not
  resolve to `Contracts\HasStyles`.

No 02B decision may silently contradict those facts.

## 3. Design goals

The target public style API should make the following statements easy to
understand:

1. **Style the element you are creating.**
   Friendly style options on structured elements are the normal authoring API.
2. **Reference an existing LibreOffice/ODF style by name when the template owns
   that style.**
   A named reference does not imply PHP-side registration.
3. **Define a reusable generated named style on the current document when
   application code owns that style.**
   Generated named definitions must be document-scoped in the target API.
4. **Implement semantic requirements when authoring custom structured
   elements.**
   Extension authors should not need process-global registries to participate
   in the modern style pipeline.
5. **Treat historical registries and direct writers as compatibility/low-level
   surfaces.**
   They may remain public for compatibility, but they are not equal peers in
   the recommended API model.

The target API must preserve ODF distinctions rather than pretending that text,
paragraph, graphic, and table styles are one CSS-like concept.

## 4. Target public layers

STYLE-API-02 adopts four explicit public layers.

### Layer A — Recommended application authoring

This is the primary API for ordinary document generation.

Representative surface:

```php
$paragraph = new Paragraph(null, [
    'margin-bottom' => '0.2cm',
]);

$paragraph->addText('Important', [
    'bold' => true,
    'color' => '#a40000',
]);

$cell = new RichTableCell('Total', [
    'background' => '#eeeeee',
    'padding' => '0.15cm',
    'text-align' => 'right',
]);
```

Contract:

- friendly style options remain the preferred entry point;
- style option semantics belong to the element/property layer they describe;
- the engine may generate automatic style names internally;
- users should not need to know whether a generated definition is written to
  `styles.xml` or `content.xml`;
- internal semantic collection/materialization must remain transparent to this
  layer.

This layer is **SUPPORTED PRIMARY API**.

### Layer B — Named style reference and document-scoped definition

Named styles represent two different operations and the target API must keep
those operations distinct.

#### B1. Reference an authored/existing style

Example conceptual use:

```php
$paragraph = new Paragraph('CVEntryTitle');
```

This means:

> Use the style named `CVEntryTitle` in the current ODT document/template.

It does **not** mean:

> Look up or create a process-global PHP registration.

A reference may resolve against an authored style already present in the
current ODT package or against a document-local generated definition.

This is a **SUPPORTED PRIMARY/ADVANCED API** and is central to using
LibreOffice as the visual template designer.

#### B2. Define a generated named style for the current document

The current advanced pattern is:

```php
StyleMapper::registerParagraphStyle('CVEntryTitle', [...]);
```

The capability is valid, but the process-global carrier is not the target
semantic model.

The target public capability is:

```text
define named paragraph/text style
        ↓
current OdtTemplate / document
        ↓
StyleContext semantic definition
```

The canonical future facade should therefore be **document-scoped**, exposed
through the public document/template facade rather than through a required
process-global registry.

The preferred API shape for implementation design is explicit methods on
`OdtTemplate`, for example conceptually:

```php
$template->defineParagraphStyle('CVEntryTitle', [
    'margin-top' => '0.1cm',
    'margin-bottom' => '0.03cm',
]);
```

and, where justified by actual public use:

```php
$template->defineTextStyle('ImportantText', [
    'bold' => true,
    'color' => '#a40000',
]);
```

The exact method signatures are approved only after the relevant P1 registry
behavior is characterized. The architectural decision made here is the
**scope and ownership**:

> New canonical named-style definition APIs are document-scoped facade APIs,
> not new process-global registries.

Why facade methods rather than exposing `StyleContext` directly:

- `StyleContext` is document infrastructure and should not become a general
  mutable public context object merely to solve API naming;
- `OdtTemplate` already represents the public current-document lifecycle;
- facade methods can preserve validation, compatibility adoption, and future
  internal refactoring without exposing semantic storage internals;
- this avoids creating another general-purpose `StyleManager` unless later
  evidence proves one is necessary.

### Layer C — Custom structured-element extension API

The target extension model is semantic ownership.

Preferred hooks:

- `getOwnStyleRequirements()`;
- `ownedElements()`;
- corresponding semantic resource/dependency hooks where applicable;
- `toDomNode()` for native element materialization.

Contract:

- a custom `OdtElement` describes its own semantic style definitions and
  references;
- ownership traversal composes child requirements;
- custom elements should not need to mutate `StyleMapper` to participate in
  normal `setElement()`;
- semantic hooks remain polymorphic and therefore must continue to dispatch to
  external subclasses.

Legacy `getOwnRequired*()` getter families remain **SUPPORTED COMPATIBILITY
FACADE** until an explicit later migration proves that wrappers can preserve
observable behavior.

`HasStyles` is not promoted as the canonical modern extension contract in 02B.
The current top-level compatibility dispatch is inactive, and reactivating it
would be a behavior change. Its eventual status requires a separate explicit
compatibility decision after the target semantic extension contract is in
place.

### Layer D — Low-level and compatibility API

This layer contains mechanisms that may remain callable but are not the
recommended mental model for ordinary application code.

Examples include:

- process-global `StyleMapper` registries;
- mutable public registry properties;
- legacy `registerStyles()` / `getStyleDefinitions()` projections;
- direct `StyleWriter` calls;
- historical registration/getter variants.

Contract:

- public visibility alone does not make a mechanism a recommended authoring
  API;
- existing compatibility behavior must be preserved until explicitly
  deprecated or migrated;
- no new application documentation should teach these mechanisms where Layer A
  or B expresses the requirement cleanly;
- implementation code may continue to use them as compatibility transport while
  migration is incomplete.

The exact keep/deprecate/wrap decision for individual methods is subject to P1
and P2 gates.

## 5. Canonical terminology

The public model should use terminology that communicates semantics and scope.

### 5.1 `style options`

Friendly developer-facing arrays attached to an element or text run.

Examples:

- `bold`;
- `font-size`;
- `margin-bottom`;
- `background`;
- `padding`.

These are **authoring options**, not ODF style definitions by themselves.

### 5.2 `style reference`

A named reference used by document content, such as a Paragraph that points to
`CVEntryTitle`.

A reference does not own or create its definition.

### 5.3 `style definition`

The properties defining a named or generated ODF style in a specific family and
scope.

Definitions belong to the current document in the modern semantic model.

### 5.4 `define`

Preferred verb for a new canonical document-scoped named-style API.

Reasoning:

- `register` is historically associated with `StyleMapper`'s process-global
  registry;
- `set` is ambiguous about replacement semantics;
- `add` does not communicate duplicate/conflict behavior;
- `define` describes the semantic operation without promising a particular
  backing store.

Exact duplicate/conflict semantics remain tied to `StyleContext` behavior and
must be reflected by implementation tests.

### 5.5 `map`

Reserved for stateless conversion of friendly options into normalized ODF
property structures.

A method named `map...` should not also mutate process-global registry state.
Where historical methods violate this separation, compatibility wrappers may
remain, but new APIs should respect it.

### 5.6 `write` / `materialize`

Serialization operations. These are not normal authoring verbs.

Public application code should not need to manually invoke style
materialization for standard `OdtTemplate` workflows.

## 6. Family-specific public semantics

02B deliberately does **not** introduce one generic public
`defineStyle($family, ...)` API.

ODF style families have different property models, locations, references, and
lifecycle constraints. A generic method would expose internal family strings
and create a false promise of uniform semantics.

The target is explicit family-oriented capability where public need is proven.

### Paragraph

- friendly paragraph options: primary API;
- named Paragraph reference: supported;
- generated named paragraph definition: approved capability for a
  document-scoped facade;
- current `StyleMapper::registerParagraphStyle()` remains compatibility during
  migration.

### Text

- friendly inline text options: primary API;
- generated automatic text definitions remain transparent;
- explicit named text definition is an advanced capability, but exact canonical
  method scope waits for GAP-04 characterization because the current registry
  variants are not semantic synonyms.

### Table / table-cell / table-column / table-row

- element-specific options and structural APIs remain primary;
- do not expose a generic named-style registry merely because semantic
  `StyleRequirement` now supports these families;
- future public named table-style definition requires demonstrated application
  need and P1/P2 characterization of current table compatibility paths.

### Graphic / frame / image

- element layout/style options remain primary;
- do not use STYLE-API-02 to redesign anchor, wrap, or positioning semantics;
- FRAME-LAYOUT-01 / IMAGE-LAYOUT-01 remain separate work;
- no generic graphic-style registration API is approved here.

## 7. Compatibility contract

Backward compatibility remains a first-class requirement.

### 7.1 Current `StyleMapper` named paragraph API

`StyleMapper::registerParagraphStyle()` is explicitly documented and used in a
representative professional sample. It therefore remains a **SUPPORTED
COMPATIBILITY API** during STYLE-API-02 migration.

02B does not authorize immediate deprecation.

A future document-scoped facade may become the recommended API only after:

1. equivalent use cases are proven;
2. migration behavior is tested;
3. current process-global persistence differences are documented;
4. the documentation/sample migration strategy is explicit.

### 7.2 Legacy element getters

Evidence-backed `getOwnRequired*()` methods remain polymorphic compatibility
facades.

Implementation may eventually derive their result from semantic requirements,
but only if characterization proves equivalent override behavior. They must not
be bypassed by non-polymorphic shortcuts while still supported.

### 7.3 `HasStyles`

Do not repair the top-level namespace mismatch as incidental cleanup.

Possible later outcomes include:

- retain the interface for direct/composite compatibility but leave the
  top-level phase inactive;
- restore top-level dispatch deliberately with characterization of the added
  effects;
- provide a semantic facade and later deprecate the interface.

02B makes no irreversible choice among those outcomes because the modern
extension model does not require the decision to proceed.

### 7.4 `StyleWriter`

`StyleWriter::writeAllStyles()` retains its direct-call compatibility behavior
unless P1 proves a narrower contract is safe.

No new recommended application API should depend on callers invoking
`StyleWriter` directly.

### 7.5 Public mutable static properties

No public static registry property may be privatized or removed before GAP-02
characterization determines observable direct-write behavior.

## 8. Duplicate/conflict semantics for new document-scoped definitions

A new canonical definition facade must delegate to document-local semantic
conflict rules rather than invent a second policy.

Target behavior:

- same semantic identity + equivalent definition: idempotent;
- same semantic identity + conflicting definition: explicit failure;
- existing authored style in the template: treated according to current
  `StyleContext` resolution/materialization semantics, not silently overwritten;
- definition lifetime: current logical document/template instance;
- `load()`/new template lifecycle follows document-context lifecycle rather than
  static process lifetime.

The public exception type/message can be refined in implementation, but silent
last-write-wins behavior is not the target for the new document-scoped API.

This is intentionally different from some retained first-write-wins legacy
registries. Compatibility wrappers may continue their historical behavior;
new canonical APIs should follow semantic document-local rules.

## 9. Documentation contract

After implementation begins, public documentation should clearly separate the
layers.

Recommended structure:

```text
Styling
├── Element style options              ← start here
├── Using existing named ODT styles    ← LibreOffice template styles
├── Defining reusable generated styles ← advanced, document-scoped
├── Custom element style requirements  ← extension authors
└── Legacy / low-level compatibility   ← migration/reference
```

The stale statement in `docs/styling/style-model.md` that `StyleContext` is a
future architecture must be corrected when the 02B contract is accepted.

Documentation should not hide compatibility APIs, but it should stop presenting
process-global registration as the conceptual owner of modern style state.

## 10. Non-goals

STYLE-API-02B does not approve or design:

- document defaults or `style:default-style`;
- `setDefaultFont()` or other DOCUMENT-DEFAULTS-01 APIs;
- frame/image positioning, anchor, or wrap redesign;
- table width or column geometry APIs;
- list layout redesign;
- template syntax changes;
- lifecycle redesign of `assign()` / `render()` / `save()`;
- removal of process-global compatibility state;
- generic public exposure of `StyleContext`;
- a general-purpose `StyleManager` object;
- one universal family-agnostic `defineStyle()` API;
- silent activation of the currently inactive top-level `HasStyles` phase.

## 11. P1/P2 gates that still constrain implementation

The target layer model can be approved before every compatibility detail is
resolved, but implementation decisions remain gated as follows.

### P1 — contract gates

Before changing the affected public surface, characterize:

1. GAP-07 — direct `StyleWriter` audience;
2. GAP-02 — direct writes to public static registry properties;
3. GAP-03 — `mapTableCellStyle()` vs `mapTableCellStyleOptions()`;
4. GAP-04 — text registry variants;
5. GAP-06 — `RichTable::getTableStyleDefinitions()` side effects.

These results may alter wrappers/deprecation sequencing, but they should not
change the four-layer model unless new evidence shows a genuinely different
public use case.

### P2 — implementation gate

Before merging/removing secondary table-cell registry state, characterize
GAP-05.

## 12. Proposed implementation sequence after contract approval

Implementation should proceed in small slices.

### 02C — Document-scoped named paragraph definition facade

Characterize all behavior required by the current named paragraph use case,
then introduce the smallest public document-scoped facade that can replace the
recommended need for global `StyleMapper::registerParagraphStyle()`.

Expected properties:

- facade on `OdtTemplate`;
- delegates to current document semantic style ownership;
- no global registration required for the new path;
- named Paragraph references continue to work;
- repeated definitions/conflicts tested;
- existing authored style resolution tested;
- repeated `save()` / `load()` lifecycle tested;
- no deprecation of the old API in the same slice.

### 02D — Documentation/sample migration for named paragraph styles

Update the style guide and professional CV sample to demonstrate the canonical
new document-scoped API while documenting the legacy process-scoped facade.

No sample output artifacts should be committed merely because the sample code
changes.

### 02E — Text style API decision

Close GAP-04 and decide whether explicit named text-style definition merits a
matching document-scoped facade. Do not create it solely for symmetry.

### 02F — Compatibility surface decisions

After relevant P1 characterizations, classify individual `StyleMapper`,
`StyleWriter`, mutable registry, getter, and mapper methods as:

- supported compatibility;
- deprecated compatibility wrapper;
- internal-accidental candidate;
- retained low-level API.

Deprecation and visibility changes should be separate from the primary facade
implementation where practical.

### 02G — Final docs / API consistency closeout

Reconcile README, public styling guides, API examples, ROADMAP, and
FUTURE_DEVELOPMENT with the accepted model and perform final compatibility
preflight.

## 13. Acceptance criteria for STYLE-API-02B

02B is ready for approval when all of the following are accepted:

1. element-centric style options are the primary authoring API;
2. named style **reference** and named style **definition** are distinct public
   concepts;
3. new canonical generated named-style definitions are document-scoped;
4. the public `OdtTemplate` facade, not direct `StyleContext` exposure, is the
   preferred ownership boundary for such definitions;
5. custom elements use semantic style requirement hooks as the preferred modern
   extension model;
6. legacy element getters and template facade callbacks remain compatibility
   dispatch until explicitly migrated;
7. `StyleMapper::registerParagraphStyle()` remains supported during migration;
8. direct `StyleWriter` and static registries are low-level/compatibility, not
   the recommended application model;
9. no generic family-agnostic public style registry is introduced;
10. P1/P2 gates remain binding before affected compatibility changes.

## 14. Decision summary

The target public model is:

```text
Application author
    │
    ├── element style options -------------------- primary
    │
    ├── named style reference -------------------- existing/authored or generated
    │
    └── document-scoped named style definition --- advanced facade

Custom element author
    │
    └── semantic StyleRequirement ownership ------ modern extension contract

Compatibility / low-level caller
    │
    ├── StyleMapper static registries
    ├── legacy getters / HasStyles projections
    └── StyleWriter direct serialization
```

The architectural direction is therefore **not** to expose more of the internal
style machinery. It is to make the existing document-local semantic ownership
visible through a smaller, clearer public facade while preserving historical
surfaces as explicit compatibility rather than allowing them to define the
main API model.

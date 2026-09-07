# STYLE-API-02B — Target Public Style API Change Contract

Status: **ACCEPTED CHANGE CONTRACT — SEMANTICS BEFORE IMPLEMENTATION**  
Baseline: `develop` at `e4c48c859a4ef8a1362b8c303d3f3944ebb52d4c`  
Branch: `architecture/style-api-02b-target-public-api`

## 1. Purpose

STYLE-API-02A established that the current public style surface contains several
architectural generations at once. P0 characterized the important observable
boundaries before target-API design:

1. normal application styling is primarily element-centric;
2. reusable named paragraph styles are an intentional advanced use case;
3. custom elements and template subclasses still observe both semantic and
   legacy compatibility hooks;
4. historical public visibility does not by itself prove that a mechanism
   belongs to the long-term public API.

This document defines the target public style model that future STYLE-API-02
implementation slices must converge toward.

The contract is intentionally more ambitious than simple compatibility
preservation. At the time of this decision, the library has almost no external
adoption, while the style subsystem contains clear historical overlap. This is
therefore the right stage to remove or internalize accidental public mechanisms
where their useful behavior can be preserved through a cleaner semantic API.

The governing principle is:

> Characterization protects observable behavior. It does not automatically
> preserve the historical mechanism that produced that behavior.

## 2. Accepted architectural baseline

This contract assumes the completed STYLE-CONTEXT-01 semantic ownership model.

Accepted facts include:

- `OdtDocumentContext` / `StyleContext` own modern document-local style
  semantics;
- structured elements emit semantic `StyleRequirement` definitions and
  references;
- physical package resources remain owned by `OdtPackage`;
- normal application authoring uses structured elements such as `Paragraph`,
  `RichText`, `RichTableCell`, image/frame elements, and friendly option arrays;
- `StyleMapper::registerParagraphStyle()` is currently documented and used by
  project-owned samples, but this does not make the process-global registry the
  desired long-term ownership model;
- direct `StyleWriter` usage is not the normal application-facing authoring
  model;
- semantic and legacy element hooks are currently subclass-observable;
- protected/public `OdtTemplate` compatibility facades remain dynamically
  dispatchable;
- the top-level `OdtTemplate::setElement()` `HasStyles` compatibility phase is
  currently non-dispatching because the unqualified interface name does not
  resolve to `Contracts\HasStyles`;
- `OdtElement` already provides the modern semantic ownership hooks required by
  the STYLE-CONTEXT-01 pipeline.

No implementation slice may silently contradict these facts.

## 3. Target design principles

The public style API should make the following statements easy to understand.

### 3.1 Style the element you are creating

Friendly style options on structured elements are the normal authoring API.

### 3.2 Reference an existing style when the document owns it

A named style reference means that content uses a style already authored in the
ODT template or otherwise defined in the current document. A reference does not
implicitly register a process-global PHP style.

### 3.3 Define reusable generated styles on the current document

When application code owns a reusable named style, that definition belongs to
the current logical document. New canonical APIs must therefore be
document-scoped.

### 3.4 Custom elements describe semantic requirements

Custom `OdtElement` implementations participate through semantic requirements
and ownership traversal. They should not need to mutate process-global style
registries.

### 3.5 Historical implementation surfaces are not equal public peers

Current public visibility, internal project samples, or historical usage are
not sufficient reasons to preserve a mechanism indefinitely. Legacy style
infrastructure should be characterized, migrated, reduced, internalized, or
removed where possible.

### 3.6 Names should describe responsibilities

- a mapper maps;
- a writer writes/materializes;
- a document style facade exposes document-oriented authoring operations;
- semantic ownership remains in `OdtDocumentContext` / `StyleContext`.

The target API must preserve ODF family distinctions rather than pretending
that paragraph, text, graphic, and table styles are one CSS-like concept.

## 4. Target public API model

STYLE-API-02 defines three canonical public API layers.

Legacy style infrastructure is treated separately as a migration/removal zone,
not as a fourth equal public layer.

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
- semantic collection and materialization remain transparent to this layer.

This layer is the **SUPPORTED PRIMARY API**.

### Layer B — Document style authoring

Layer B handles named style references and explicit document-scoped
style definitions. These are different operations and the API must keep them
separate.

#### B1. Reference an authored or existing named style

Example:

```php
$paragraph = new Paragraph('CVEntryTitle');
```

This means:

> Use the style named `CVEntryTitle` in the current ODT document/template.

It does not mean:

> Look up or create a process-global PHP registration.

A reference may resolve against an authored style already present in the
current ODT package or against a generated definition owned by the current
document.

Named references are a **SUPPORTED PRIMARY/ADVANCED API** and are important for
using LibreOffice as the visual template designer.

#### B2. Canonical document style facade

The canonical target API for reusable generated named styles is a small
style-authoring facade reached from `OdtTemplate`:

```php
$template->styles()->defineParagraph('CVEntryTitle', [
    'margin-top' => '0.1cm',
    'margin-bottom' => '0.03cm',
]);
```

Conceptually:

```text
OdtTemplate
    ↓
styles()
    ↓
small public document-style facade
    ↓
OdtDocumentContext
    ↓
StyleContext
```

The public facade is an access and authoring boundary. It is **not** the
semantic owner of style state.

Semantic ownership remains document-local:

```text
OdtDocumentContext -> StyleContext
```

The facade must therefore not become a second mutable style store.

#### B3. Facade lifecycle

A style facade represents the `OdtTemplate` instance and its **current logical
document**.

Conceptually:

```php
$styles = $template->styles();
$template->load($otherTemplate);
$styles->defineParagraph('Heading', [...]);
```

The operation must target the template's current document state. The facade
must not retain a stale `StyleContext` from a previously loaded document.

Implementation may achieve this through delegation, a context provider, or
another narrow mechanism, but the observable lifecycle rule is fixed by this
contract.

#### B4. Convenience methods

Direct convenience methods on `OdtTemplate` may later be added, for example:

```php
$template->defineParagraphStyle('CVEntryTitle', [...]);
```

If such methods are introduced, they must be thin delegates to the canonical
`styles()` facade and must not create a second semantic API or separate state.

STYLE-API-02 does not require these convenience methods.

#### B5. No generic public style manager

This contract does not expose `StyleContext` directly and does not introduce a
general-purpose mutable `StyleManager`.

The facade should remain small, document-oriented, and authoring-focused.
Internal operations such as requirement registration, XML writing,
materialization, or direct context access do not belong on the public facade.

### Layer C — Custom structured-element extension API

The target extension model is semantic ownership.

Preferred hooks include:

- `getOwnStyleRequirements()`;
- `ownedElements()`;
- semantic resource/dependency hooks where applicable;
- `toDomNode()` for native element materialization.

Contract:

- a custom `OdtElement` describes semantic style definitions and references;
- ownership traversal composes child requirements;
- custom elements should not need process-global `StyleMapper` registries;
- semantic hooks remain polymorphic and must continue to dispatch to external
  subclasses while they are part of the supported extension contract.

Direct construction of `StyleRequirement` is the canonical semantic extension
model for STYLE-API-02. This contract does **not** claim that it must remain the
final convenience surface forever. A later helper/builder API may be introduced
if real usage demonstrates value, but 02B does not invent one speculatively.

## 5. Legacy infrastructure policy

Historical style infrastructure is not part of the target public mental model
merely because it is currently public.

The target is:

```text
Application author
    ├── element style options
    └── $template->styles()

Custom element author
    └── semantic StyleRequirement ownership

Internal style infrastructure
    ├── mapping
    ├── semantic context
    ├── collection/materialization
    └── ODF writing
```

Legacy mechanisms may be retained temporarily while their behavior is migrated,
but the desired endpoint is a smaller, clearer surface.

### 5.1 `HasStyles`

`HasStyles` is a **LEGACY REMOVAL CANDIDATE**.

Its current contract is based on the historical model:

```text
element -> register styles -> expose style definition arrays
```

The modern model is:

```text
element -> describe semantic requirements -> document context owns state
```

In addition, the intended top-level `OdtTemplate::setElement()` compatibility
phase is currently inactive because of the namespace mismatch characterized in
02A.

Therefore 02B does not promote `HasStyles` as a permanent compatibility API.
Before removal:

1. characterize remaining direct/composite behavior that still depends on it;
2. migrate useful behavior to semantic ownership hooks;
3. remove redundant `implements HasStyles`, `registerStyles()`, and
   `getStyleDefinitions()` paths when they no longer serve a distinct purpose;
4. do not "repair" the inactive top-level dispatch first unless a deliberate
   migration requires that behavior.

The default target is removal, not indefinite preservation.

### 5.2 `StyleMapper`

`StyleMapper` should converge toward what its name says: **stateless mapping**.

Target responsibility:

```text
friendly authoring options -> normalized ODF property structures
```

Examples of appropriate responsibilities:

- paragraph option mapping;
- text option mapping;
- table-cell option mapping;
- frame option mapping;
- CSS-like inline option parsing where this remains semantically coherent.

Historical responsibilities that do not belong to the target mapper include:

- process-global paragraph/text/table/graphic registries;
- mutable public registry properties;
- document ownership;
- deciding which registered styles belong to a document.

Accordingly:

- `StyleMapper::registerParagraphStyle()` is a migration target for
  `$template->styles()->defineParagraph()`;
- text/table/graphic registration variants are migration/removal candidates
  subject to their P1 characterization;
- public mutable registry properties are removal/internalization candidates;
- overlapping mapper methods should be consolidated after characterization;
- style-name generation may remain temporarily but should be evaluated
  separately because naming is not mapping.

No new process-global style registry API may be introduced.

### 5.3 `StyleWriter`

`StyleWriter` should converge toward what its name says: **ODF style
serialization/materialization**.

Appropriate responsibility includes:

- creating ODF style nodes;
- writing property groups;
- appending style structures to the correct DOM location;
- serialization details required by the semantic materialization pipeline.

`StyleWriter` must not be the semantic owner of which styles belong to a
document.

Current direct reads from global `StyleMapper` registries and element-specific
direct writer calls are migration targets. They are evidence about current
behavior, not proof that those couplings belong to the target architecture.

Direct application-facing `StyleWriter` usage is not part of the recommended
public API.

### 5.4 Legacy element getters and template facades

Legacy `getOwnRequired*()` families and historical template facade callbacks are
observable today and therefore must be characterized before removal or semantic
replacement.

However, 02B does not declare them permanent public concepts.

Where equivalent modern semantic hooks can preserve the useful behavior and
polymorphic extension semantics, legacy getters may be migrated, reduced, and
removed in explicit later slices.

Protected facade removal still requires special care because subclass override
behavior is observable.

## 6. Characterization and compatibility principle

STYLE-API-02 uses characterization as a migration safety mechanism, not as an
automatic compatibility veto.

The rule is:

> Preserve intentional useful behavior where practical; do not preserve an
> obsolete mechanism merely because a characterization test proves that it
> exists today.

A characterization test may lead to three different outcomes:

1. **retain** — behavior is still intentional and the current mechanism remains
   appropriate;
2. **migrate** — behavior is useful but should move behind the target semantic
   API;
3. **remove** — behavior or mechanism is accidental, redundant, or no longer
   part of the desired product contract.

Public or protected compatibility behavior may be removed when the accepted
target architecture no longer requires it, but such removal must be an explicit
migration decision. Breaking changes must not be hidden inside refactoring and
must be documented and release-signalled where applicable.

If removal intentionally changes behavior, the change must be explicit in the
implementation slice and documentation rather than being hidden inside a
refactor.

Project-owned samples and documentation are migration targets when they teach an
API that the accepted architecture supersedes.

## 7. Canonical terminology

The public model should use terminology that communicates semantics and scope.

### 7.1 `style options`

Friendly developer-facing arrays attached to an element or text run.

Examples:

- `bold`;
- `font-size`;
- `margin-bottom`;
- `background`;
- `padding`.

These are authoring options, not ODF style definitions by themselves.

### 7.2 `style reference`

A named reference used by document content, such as a `Paragraph` that points
to `CVEntryTitle`.

A reference does not own or create its definition.

### 7.3 `style definition`

Properties defining a named or generated ODF style in a specific family and
scope.

Definitions belong to the current document in the modern semantic model.

### 7.4 `define`

Preferred verb for the canonical document-scoped named-style API.

Reasoning:

- `register` is historically associated with process-global registries;
- `set` is ambiguous about replacement semantics;
- `add` does not communicate duplicate/conflict behavior;
- `define` describes the semantic operation without promising a backing store.

### 7.5 `map`

Reserved for stateless conversion of friendly options into normalized ODF
property structures.

New methods named `map...` must not also mutate process-global state.

### 7.6 `write` / `materialize`

Serialization operations. These are not normal application authoring verbs.

## 8. Family-specific public semantics

02B deliberately does **not** introduce one generic public
`defineStyle($family, ...)` API.

ODF style families have different property models, locations, references, and
lifecycle constraints. A generic family string would leak internal concepts and
create a false promise of uniform semantics.

### Paragraph

- friendly paragraph options: primary API;
- named paragraph reference: supported;
- generated named paragraph definition: approved through
  `$template->styles()->defineParagraph(...)`;
- current `StyleMapper::registerParagraphStyle()` becomes a migration target,
  not a permanent canonical API.

### Text

- friendly inline text options: primary API;
- automatic generated text styles remain transparent;
- explicit named text definition may be added to the document style facade only
  if GAP-04 and actual usage justify it;
- do not add `defineText()` merely for symmetry.

### Table / table-cell / table-column / table-row

- element-specific options and structural APIs remain primary;
- no generic named table-style registry is introduced merely because semantic
  `StyleRequirement` supports the family;
- existing table registry behavior should be characterized and migrated or
  removed according to demonstrated need.

### Graphic / frame / image

- element layout/style options remain primary;
- STYLE-API-02 does not redesign anchor, wrap, or positioning semantics;
- no generic public graphic-style registry is introduced;
- process-global frame/image/fill registries are migration/removal candidates.

## 9. Duplicate and conflict semantics

The canonical document style facade must delegate to document-local semantic
conflict rules rather than inventing a second policy.

Target behavior:

- same semantic identity + equivalent definition: idempotent;
- same semantic identity + conflicting definition: explicit failure;
- authored style already present in the template: handled according to
  document-local resolution/materialization semantics, never silently
  overwritten by an unrelated generated definition;
- definition lifetime: current logical document/template instance;
- `load()` and new-template lifecycle follow document-context lifecycle rather
  than static process lifetime.

Exact public exception classes/messages may be refined during implementation.
Silent last-write-wins behavior is not the target.

## 10. Public facade ownership and naming

The exact concrete class name returned by `OdtTemplate::styles()` is not fixed by
02B. A name such as `DocumentStyles` is conceptually appropriate because the
object is a document-oriented authoring facade rather than a manager of all
style internals.

The public contract is fixed at the behavioral level:

```php
$template->styles()->defineParagraph(...);
```

The facade must:

- be document-oriented;
- remain small;
- delegate to current semantic document ownership;
- avoid owning duplicate mutable style state;
- avoid exposing `StyleContext` internals;
- avoid low-level XML/materialization APIs;
- resolve the current document lifecycle correctly.

## 11. P1/P2 gates

The target architecture is accepted now, but compatibility-sensitive
implementation still requires focused characterization before affected
mechanisms are changed.

### P1 — behavior gates

Before changing the relevant mechanisms, characterize:

1. GAP-07 — direct `StyleWriter` audience;
2. GAP-02 — direct writes to public static registry properties;
3. GAP-03 — `mapTableCellStyle()` vs `mapTableCellStyleOptions()`;
4. GAP-04 — text registry variants;
5. GAP-06 — `RichTable::getTableStyleDefinitions()` side effects.

The purpose of these gates is to understand what must be retained, migrated, or
intentionally removed. They do not automatically require permanent wrappers.

### P2 — implementation gate

Before merging/removing secondary table-cell registry state, characterize
GAP-05.

## 12. Proposed implementation sequence

Implementation should proceed in small slices and should keep semantic change
separate from mechanical cleanup where practical.

### 02C — Canonical document style facade + named paragraph definition

Characterize the behavior required by the current named paragraph use case,
then introduce the smallest canonical facade:

```php
$template->styles()->defineParagraph(...);
```

Required properties:

- no global registration required for the new path;
- semantic definition owned by the current document context;
- named `Paragraph` references continue to work;
- repeated equivalent definitions are idempotent;
- conflicting definitions fail explicitly;
- authored template style resolution is covered;
- facade lifecycle across `load()` / repeated `save()` is covered;
- no stale `StyleContext` reference survives document replacement;
- no convenience alias is required in the same slice.

### 02D — Named paragraph migration

Migrate project-owned documentation and representative samples from
`StyleMapper::registerParagraphStyle()` to the canonical `styles()` facade.

This slice should determine whether the old registration method still serves a
meaningful supported compatibility purpose. If not, it may be prepared for
removal/deprecation in a later explicit cleanup slice.

No `samples/output/*.odt` artifacts should be committed merely because sample
code changes.

### 02E — `HasStyles` retirement analysis and migration

Characterize remaining active direct/composite `HasStyles` behavior.

Then:

- migrate useful behavior to semantic hooks;
- remove redundant `registerStyles()` / `getStyleDefinitions()` paths where
  possible;
- remove `HasStyles` when no distinct supported contract remains;
- do not silently activate the currently inactive top-level dispatch.

### 02F — Mapper/registry cleanup

Close GAP-02, GAP-03, GAP-04, GAP-05, and relevant registry behavior.

Then move `StyleMapper` toward a stateless mapping role:

- consolidate overlapping map methods;
- remove or internalize process-global registries where semantic ownership has
  replaced them;
- remove public mutable registry properties where no intentional contract
  remains;
- decide whether style-name generation remains there or moves to a narrower
  responsibility.

### 02G — Writer boundary cleanup

Close GAP-07 and migrate `StyleWriter` toward pure writing/materialization:

- no semantic document ownership;
- reduce/remove direct dependency on process-global registries;
- retain only direct-call compatibility that is still intentionally supported;
- keep low-level serialization concerns out of application authoring APIs.

### 02H — Legacy getter/facade cleanup

Review legacy `getOwnRequired*()` methods and protected/public template
compatibility facades against the accepted semantic extension model.

Migrate or remove only with explicit characterization of polymorphic behavior.

### 02I — Final documentation and API consistency closeout

Reconcile README, styling guides, API examples, ROADMAP,
FUTURE_DEVELOPMENT, and architecture documentation with the accepted model.

## 13. Documentation contract

Public documentation should converge toward this structure:

```text
Styling
├── Element style options
├── Using existing named ODT styles
├── Defining reusable generated styles with $template->styles()
├── Custom element style requirements
└── Migration / legacy notes where still relevant
```

Documentation should not present process-global registration as the conceptual
owner of modern style state.

The stale statement in `docs/styling/style-model.md` that `StyleContext` is
future architecture must be corrected during STYLE-API-02 documentation
migration.

Project-owned samples should demonstrate the recommended current API rather
than preserve historical mechanisms for their own sake.

## 14. Non-goals

STYLE-API-02B does not design:

- document defaults or `style:default-style`;
- `setDefaultFont()` or other DOCUMENT-DEFAULTS-01 APIs;
- frame/image positioning, anchor, or wrap redesign;
- table width or column geometry redesign;
- list layout redesign;
- template syntax changes;
- lifecycle redesign of `assign()` / `render()` / `save()` beyond what is
  necessary to make document style facade lifecycle correct;
- direct public exposure of `StyleContext`;
- a general-purpose mutable `StyleManager`;
- one universal family-agnostic `defineStyle()` API;
- speculative builder APIs for custom element style requirements.

## 15. Acceptance criteria

STYLE-API-02B is accepted with the following decisions:

1. element-centric style options are the primary application authoring API;
2. named style **reference** and named style **definition** are distinct public
   concepts;
3. new canonical generated named-style definitions are document-scoped;
4. `$template->styles()` is the canonical public document-style authoring
   facade;
5. the facade is not the semantic owner; ownership remains in
   `OdtDocumentContext` / `StyleContext`;
6. a retained style facade must follow the template's current logical document
   and must not retain stale document context state;
7. direct `OdtTemplate::define...Style()` methods, if added later, are optional
   thin convenience delegates only;
8. custom elements use semantic `StyleRequirement` ownership as the preferred
   modern extension model;
9. direct `StyleRequirement` construction is canonical semantics but is not
   declared the final possible convenience surface forever;
10. `HasStyles` is a legacy removal candidate, not a target permanent public
    contract;
11. `StyleMapper` should converge toward stateless mapping;
12. process-global style registries and mutable registry properties are
    migration/removal candidates;
13. `StyleWriter` should converge toward writing/materialization and must not
    own document style semantics;
14. historical public visibility and project-owned samples do not by themselves
    require permanent API preservation;
15. characterization protects behavior but does not automatically preserve the
    historical mechanism;
16. P1/P2 gates remain binding before compatibility-sensitive removals;
17. no generic family-agnostic public style registry is introduced.

## 16. Decision summary

The accepted target public model is:

```text
Application author
    │
    ├── element style options -------------------- primary
    │
    ├── named style reference -------------------- authored/existing/generated
    │
    └── $template->styles() ---------------------- document style authoring
             │
             └── defineParagraph(...)

Custom element author
    │
    └── semantic StyleRequirement ownership ------ modern extension contract

Internal architecture
    │
    ├── OdtDocumentContext / StyleContext -------- semantic authority
    ├── collectors / materializers --------------- pipeline
    ├── StyleMapper ------------------------------ target: stateless mapping
    └── StyleWriter ------------------------------ target: ODF writing

Legacy infrastructure
    │
    ├── HasStyles -------------------------------- removal candidate
    ├── process-global registries ---------------- migration/removal candidates
    ├── mutable registry properties -------------- migration/removal candidates
    └── legacy getters/facades ------------------- characterize, migrate, remove
                                                   where safely possible
```

The architectural direction is therefore not to expose more internal machinery
and not to freeze historical public APIs unnecessarily.

The direction is to provide a small, coherent, document-oriented authoring API,
keep semantic ownership document-local, give extension authors a clear semantic
contract, and actively retire legacy mechanisms whose responsibilities are
better represented by the modern architecture.

# STYLE-API-02F — Mapper / Registry Cleanup Change Contract

Status: **ACCEPTED CHANGE CONTRACT — SEMANTICS BEFORE IMPLEMENTATION**
Baseline: `develop` at `fb459226239b9b77072f69cbee617ceb99b7a1d9`
Branch: `architecture/style-api-02f-mapper-registry-cleanup`

## 1. Purpose

STYLE-API-02F removes document ownership from `StyleMapper` and completes the
next major step in the public style API cleanup established by STYLE-API-02B.

The target architecture is:

```text
friendly authoring options
        ↓
    StyleMapper
        ↓
normalized ODF property arrays
        ↓
 StyleRequirement / typed dependency
        ↓
     StyleContext
        ↓
semantic materialization
        ↓
       ODT
```

`StyleMapper` remains useful, but only as a stateless transformation utility.
It must not remain a process-global owner of document-specific style state.

The governing rule for this milestone is:

> `StyleMapper` maps. `StyleContext` owns document semantics. Writers
> materialize. Process-global style state is compatibility debt, not target
> architecture.

STYLE-API-02F deliberately distinguishes normal semantic production paths from
legacy compatibility paths. It does not blindly delete every static mechanism
in one refactor.

## 2. Accepted architectural baseline

This contract builds on the completed architecture through STYLE-API-02E.

Accepted facts:

- `OdtDocumentContext` / `StyleContext` own modern document-local style
  semantics;
- structured elements describe styles through semantic `StyleRequirement`s;
- fill-image declarations and physical image resources have typed/document or
  package ownership;
- `DocumentStyles` is the canonical document style authoring facade;
- `$template->styles()->defineParagraph(...)` is the canonical public API for
  generated reusable named paragraph styles;
- `HasStyles`, element-level `registerStyles()`, and element-level
  `getStyleDefinitions()` are retired;
- current element authoring is primarily option-based and semantic;
- process-global StyleMapper registration is not the desired long-term model;
- direct `StyleWriter` compatibility is not the normal application authoring
  model;
- historical public visibility does not automatically make a mechanism part of
  the target API.

No STYLE-API-02F implementation may contradict those facts.

## 3. Problem statement

`StyleMapper` currently combines several unrelated responsibilities:

1. mapping friendly style options to ODF property arrays;
2. parsing CSS-like input;
3. generating style names;
4. storing process-global style registries;
5. exposing registry mutation APIs;
6. exposing registry lookup APIs;
7. indirectly supplying style ownership to semantic and writer paths.

This creates multiple architectural problems:

- document-specific state can survive across template instances;
- process-wide registration can leak styles into later documents;
- modern semantic ownership and legacy registry ownership coexist;
- some elements still mirror the same style into both semantic requirements
  and global static state;
- `StyleWriter` still consumes several StyleMapper registries directly;
- table style semantics still depend on a global registry lookup;
- table-cell state contains historical duplicate stores;
- the public API exposes mutation mechanisms that no longer belong to the
  target architecture.

STYLE-API-02F resolves the ownership problem first and performs mapper cleanup
second.

## 4. Core architectural decision

The accepted target for `StyleMapper` is a **stateless utility**.

Target responsibilities may include:

- `mapParagraphStyle()`;
- `mapTextStyleOptions()`;
- `mapTableCellStyleOptions()`;
- `mapFrameStyleOptions()`;
- `mapImageStyleOptions()`;
- `parseInlineStyle()`;
- CSS/property splitting helpers where still justified;
- deterministic style-name generation where still justified.

Target responsibilities do **not** include:

- paragraph registry ownership;
- text registry ownership;
- frame registry ownership;
- image registry ownership;
- fill-image registry ownership;
- table registry ownership;
- table-cell registry ownership;
- font registry ownership;
- process-global current-document state;
- a replacement generic style manager.

The implementation may temporarily retain explicit compatibility storage
outside the target mapper surface where this is required by a characterized
legacy path. Such storage is not target architecture and must remain bounded.

## 5. Evidence summary by style family

### 5.1 Paragraph

Modern paragraph definitions already have document-local semantic ownership.

A `Paragraph` with style options emits a semantic paragraph definition.
A named paragraph without local options is a semantic reference.

Generated reusable named paragraph authoring already has a canonical API:

```php
$template->styles()->defineParagraph('CVEntryTitle', [
    'margin-top' => '0.1cm',
    'margin-bottom' => '0.03cm',
]);
```

Legacy paragraph registration remains available through
`StyleMapper::registerParagraphStyle()` and `LegacyStyleRegistry`, and
`StyleContext` can still resolve paragraph references against that compatibility
source.

Target:

- normal authoring must not depend on global paragraph registration;
- direct legacy registration may remain only as bounded compatibility until its
  final retirement decision;
- the `StyleContext` legacy fallback is not semantic authority and is a
  migration/removal candidate;
- `LegacyStyleRegistry` is not part of the long-term target architecture.

### 5.2 Text

Modern `Paragraph` instances already own inline text definitions through
semantic `StyleRequirement`s.

However, legacy global text registration remains in several paths, including
historical `Paragraph` rendering fallbacks and `HtmlImporter` side effects.

Target:

- ordinary `Paragraph` and `HtmlImporter` use must not register text styles into
  process-global state;
- inline text identity is generated locally and the definition is emitted
  semantically;
- any remaining direct static text-registration compatibility is isolated from
  normal document production;
- redundant text registry state is removed when no longer required.

### 5.3 Table cell

`RichTableCell` already emits a semantic table-cell definition, but still
registers the same style globally.

The current mapper contains historical overlapping state:

- `registeredTableCellStyles`;
- `tableCellStyles`.

The actually used registration/getter pair operates on `tableCellStyles`, while
`registeredTableCellStyles` survives in legacy aggregate reporting.

Target:

- table-cell ownership is exclusively element/document semantic ownership;
- normal `RichTableCell` mutation never registers process-global state;
- internal refresh means normalize properties + regenerate identity, not
  register globally;
- redundant historical stores are removed;
- `mapTableCellStyleOptions()` is the canonical table-cell mapper unless P0
  characterization proves a required distinction;
- the older narrow `mapTableCellStyle()` is a removal candidate.

### 5.4 Table

Table style ownership is the most important remaining semantic migration in
STYLE-API-02F.

Current behavior allows:

```php
StyleMapper::registerTableStyle('MyFixedTableStyle', [...]);
$table->setTableStyleName('MyFixedTableStyle');
```

and `RichTable::getOwnStyleRequirements()` converts the global registration into
a semantic table definition.

This is backwards ownership: the semantic element still depends on a
process-global registry.

Target semantics distinguish:

1. **Named reference**

   ```php
   $table->setTableStyleName('AuthoredTableStyle');
   ```

   means: reference a style already present in the current document/template.

2. **Element-owned generated definition**

   Table style options owned by the `RichTable` instance produce a semantic
   table definition directly, without global registration.

The exact public method name for element-owned table options is fixed during
implementation only after the P0 output-equivalence characterization, but the
semantic ownership rule is fixed by this contract.

No new generic `defineTable()` document facade is introduced by STYLE-API-02F.

### 5.5 Frame / graphic

The modern `DrawTextBox` path already maps frame options and emits a semantic
`graphic` requirement. It no longer needs direct global frame registration for
normal `setElement()` insertion.

Global frame state remains relevant primarily to explicit legacy structured
`assign()` / `render()` compatibility and direct writer compatibility.

Target:

- normal semantic structured insertion never depends on `StyleMapper::$frameStyles`;
- public mutable frame registry state is removed from the target mapper API;
- any temporary legacy frame carrier is explicit, isolated, and bounded to the
  characterized compatibility path.

### 5.6 Image

Modern `ImageElement` no longer needs global image registration for normal
semantic insertion. It maps options, derives an identity, exposes image style
requirements, and exposes physical image assets separately.

Target:

- no normal image insertion depends on process-global image state;
- legacy image registry behavior may survive temporarily only for the explicit
  legacy structured path or direct writer compatibility;
- registry APIs are not target public API.

### 5.7 Fill image

`CircularImageElement` already exposes typed `FillImageRequirement` semantics
and a semantic graphic style definition.

Target:

- typed fill-image requirements and package resources are authoritative;
- global fill-image registration is compatibility-only;
- normal `setElement()` insertion must not depend on process-global fill-image
  state.

### 5.8 Fonts

Modern font handling uses document-local requirement discovery and
materialization. `StyleWriter::writeAllStyles()` additionally discovers font
references from the current document.

The old `StyleMapper::$registeredFonts` state has no identified active writer.

Target:

- remove the unused StyleMapper font registry and obsolete getter/XML helper;
- do not introduce any replacement global font registry;
- do not pull document-default font work into this milestone.

## 6. Runtime path classification

### 6.1 Modern structured insertion

Canonical path:

```text
OdtElement
  ↓
StyleRequirementCollector::collectSemantic()
  ↓
OdtDocumentContext / StyleContext
  ↓
semantic materializers
  ↓
StructuredElementMaterializer / toDomNode()
```

This path is the authority for new architecture.

After STYLE-API-02F-A, this path must not require process-global StyleMapper
registry state for paragraph, text, table, table-cell, graphic, image,
fill-image, or font semantics.

### 6.2 Legacy structured assign/render path

The historical `assign()` / `render()` route can still materialize
`OdtElement`s through `setValuesInDom()` and register graphic/image/fill-image
compatibility state.

This path is explicitly legacy.

STYLE-API-02F may isolate its state, but must not silently break it without the
required characterization.

### 6.3 Direct StyleWriter compatibility

`StyleWriter::writeAllStyles()` still exposes broad compatibility behavior and
reads several legacy StyleMapper registries.

STYLE-API-02G owns general writer-boundary cleanup.

STYLE-API-02F changes only the minimum writer coupling necessary to remove
normal document ownership from `StyleMapper`.

## 7. StyleMapper target API classification

### KEEP as stateless mapping/transformation

Subject to focused tests and naming cleanup:

- `mapParagraphStyle()`;
- `mapTextStyleOptions()`;
- `mapTableCellStyleOptions()`;
- `mapFrameStyleOptions()`;
- `mapImageStyleOptions()`;
- `parseInlineStyle()`;
- `splitCssProperties()` if current import behavior still requires it;
- `generateStyleName()`;
- `generateParagraphStyleName()` if current generated-paragraph behavior still
  requires it.

### REMOVE / MIGRATE from target StyleMapper

Registry ownership APIs and fields, including:

- text registration and lookup;
- paragraph registration and lookup;
- table-cell registration and lookup;
- image registration and lookup;
- fill-image registration and lookup;
- frame registration and lookup;
- table registration and lookup;
- obsolete font registry access;
- aggregate registry inspection methods that only expose legacy state;
- public mutable `$frameStyles`;
- public mutable `$tableStyles`.

Removal timing depends on P0 compatibility characterization and the 02F/02G
boundary.

### REMOVE if characterization confirms no distinct behavior

- `mapTableCellStyle()`.

It is currently a narrow predecessor/overlap of
`mapTableCellStyleOptions()` and has no identified active production caller.

## 8. `LegacyStyleRegistry` policy

`LegacyStyleRegistry` is explicitly compatibility-only process-wide paragraph
state. It is not target architecture.

Accepted policy:

- do not expand it into a new general registry abstraction;
- do not move every old StyleMapper registry into it merely to preserve the
  same architecture under a different class name;
- retain only the minimum compatibility state proven necessary by P0;
- remove it in STYLE-API-02F if direct compatibility can be retired safely;
- otherwise retain it as a narrowly documented transition bridge until the
  writer/legacy cleanup milestone that removes its last caller.

No new public authoring documentation may recommend it.

## 9. Table style target semantics

STYLE-API-02F establishes the following distinction.

### Reference semantics

```php
$table->setTableStyleName('ExistingTableStyle');
```

means:

> Use a named table style owned by the current ODT document/template.

It does not implicitly consult or create a process-global PHP registration.

### Generated element-owned definition semantics

A `RichTable` must be able to own generated table properties directly.

Conceptually:

```php
$table->setStyle([
    'table:width' => '15cm',
    'table:align' => 'left',
    'style:rel-width' => '100%',
]);
```

The exact accepted public method name may differ if existing naming conventions
make another name clearer. Regardless of name, the behavior is fixed:

```text
RichTable instance
    ↓
normalized table properties
    ↓
semantic table StyleRequirement
    ↓
StyleContext
```

No global `registerTableStyle()` lookup participates in the modern path.

## 10. Table-cell target semantics

`RichTableCell` owns its mapped cell properties and generated identity.

A mutation such as background/border/padding changes must perform only:

```text
update local options/properties
    ↓
normalize/map
    ↓
recompute style identity
```

It must not perform:

```text
update local state
    ↓
mutate process-global StyleMapper registry
```

The existing `registerStylesAndRefresh()` name describes obsolete behavior.
Implementation may rename/internalize it as part of 02F, provided public
compatibility implications are explicitly reviewed.

## 11. HtmlImporter policy

`HtmlImporter` may continue to use stateless StyleMapper parsing and mapping.

Allowed:

- `parseInlineStyle()`;
- text mapping;
- paragraph mapping;
- table-cell mapping.

Not target behavior:

- registering the same text style globally before passing it to `Paragraph`;
- registering a paragraph globally when the constructed `Paragraph` already
  owns the semantic definition;
- forcing table-cell global registration through compatibility refresh calls.

STYLE-API-02F removes those redundant registration side effects without turning
`HtmlImporter` into a general refactor target.

## 12. StyleContext compatibility fallback

`StyleContext::resolveReference()` currently allows paragraph/text references to
resolve against legacy StyleMapper registration when neither authored document
styles nor document-local semantic definitions resolve them.

This fallback is compatibility behavior only.

Resolution priority remains conceptually:

1. authored/current document style;
2. document-local semantic definition;
3. legacy compatibility registration;
4. unresolved.

STYLE-API-02F may remove level 3 only if P0 direct-compatibility characterization
supports that decision.

The fallback must never become a reason to retain process-global registration
as a normal authoring model.

## 13. Lifecycle and contamination policy

Existing diagnostics have already demonstrated that explicit static paragraph
and text registrations can leak into a subsequently generated document in the
same PHP process.

This contract therefore treats process-global registration as a confirmed
architectural limitation, not a hypothetical code-quality concern.

Target invariants:

- normal semantic style state belongs to one logical document;
- `StyleContext::reset()` resets document-local state;
- `OdtTemplate::load()` must not inherit semantic definitions from a prior
  logical document through StyleMapper registries;
- multiple `OdtTemplate` instances in one PHP process must not share normal
  generated style ownership;
- repeated `render()` / `save()` preserves current supported behavior;
- any process-global compatibility state that temporarily remains must be
  explicitly bounded and must not be consumed by the normal semantic path.

## 14. Mandatory P0 characterization gate

Before production implementation, three focused characterization areas are
required.

### P0.1 Direct StyleWriter compatibility

Characterize the current observable behavior of representative direct calls of
the form:

```text
StyleMapper::registerX(...)
    ↓
StyleWriter::writeAllStyles(...)
```

At minimum cover the legacy families whose direct writer behavior affects the
02F/02G boundary:

- paragraph;
- text;
- table;
- frame/graphic where applicable.

Purpose:

- decide which compatibility behavior survives 02F;
- avoid accidental writer-API redesign;
- make any breaking removal explicit.

### P0.2 Legacy structured assign/render graphic path

Characterize current frame/image/fill-image behavior when an `OdtElement` is
materialized through the legacy structured `assign()` / `render()` route.

Purpose:

- prove which global carriers are still necessary;
- isolate them without breaking the old path;
- prevent normal `setElement()` semantics from being coupled back to them.

### P0.3 Table style output equivalence

Characterize the current output of:

```php
StyleMapper::registerTableStyle('Name', $properties);
$table->setTableStyleName('Name');
```

The future element-owned table definition must preserve the required ODF
semantics and visual result.

Purpose:

- establish exact migration behavior;
- migrate `sample_11_table.php` safely;
- separate named reference semantics from generated-definition semantics.

No additional P0 suite should be created unless implementation reveals a real
compatibility ambiguity.

## 15. P1 / P2 evidence

P1 items may be handled within implementation when local and well bounded:

- exact direct-public usage of individual registry getters;
- public mutation of `$frameStyles` / `$tableStyles` outside known internal
  paths;
- naming/visibility of `RichTableCell::registerStylesAndRefresh()`;
- remaining HtmlImporter registration side effects;
- aggregate StyleMapper registry inspection APIs.

P2 cleanup includes:

- dead secondary table-cell state;
- dead font registry state;
- stale comments/docblocks describing registration as central architecture;
- diagnostic-only obsolete registry naming.

## 16. Implementation plan

STYLE-API-02F intentionally uses **two substantial implementation slices**.

### STYLE-API-02F-A — Document ownership migration

Primary goal:

> Normal production semantics no longer depend on process-global StyleMapper
> registries.

Expected scope:

- add element-owned generated table style semantics to `RichTable`;
- migrate `sample_11_table.php` away from global table registration;
- preserve explicit named table-style reference semantics;
- remove normal `RichTableCell` global registration side effects;
- replace registration-oriented cell refresh behavior with local
  normalization/identity refresh;
- remove redundant `HtmlImporter` paragraph/text global registration;
- remove or replace obsolete `Paragraph::toDomNode()` text registration fallback
  where characterization confirms it is unreachable/redundant;
- ensure paragraph/text/table/table-cell normal semantic insertion is wholly
  document-owned;
- keep modern graphic/image/fill-image insertion document-owned;
- isolate any required legacy structured graphic carriers from normal
  `setElement()` behavior;
- adjust only the minimum StyleWriter coupling needed for ownership migration.

Required validation:

- focused P0 tests;
- paragraph/text semantic tests;
- table/table-cell semantic tests;
- graphic/image/fill-image compatibility tests;
- HtmlImporter tests;
- PublicSampleSmokeTest;
- full `composer test`;
- PHP lint;
- `composer validate` where relevant;
- `git diff --check`;
- manual LibreOffice regression for table output and any rendering-affecting
  sample changes.

### STYLE-API-02F-B — Make StyleMapper a mapper

Primary goal:

> Reduce `StyleMapper` to stateless mapping/parsing/identity helpers and remove
> obsolete registry surface where compatibility no longer requires it.

Expected scope:

- remove dead/duplicate registry fields;
- remove obsolete font registry/helper;
- remove or internalize registry mutation/getter APIs no longer used;
- remove public mutable `$frameStyles` and `$tableStyles` from the target
  surface when the last required compatibility caller has moved;
- remove redundant table-cell stores;
- remove `mapTableCellStyle()` if P0/P1 confirms no distinct behavior;
- reduce or remove `LegacyStyleRegistry` according to remaining compatibility
  callers;
- update tests, samples, and current documentation;
- preserve historical architecture documents as historical records;
- perform final STYLE-API-02F preflight.

02F-B must not expand into the general StyleWriter redesign reserved for 02G.

## 17. Compatibility policy

Compatibility remains important, but mechanisms are not preserved merely
because they are public or historical.

The governing rule remains:

> Characterization protects observable behavior. It does not automatically
> preserve the historical mechanism that produced it.

Therefore:

- documented modern workflows must remain supported;
- authored ODT style references remain first-class;
- normal element style options remain supported;
- direct static registry workflows may be intentionally retired if their useful
  behavior has a canonical replacement and the break is explicit;
- protected/public compatibility facades must not be changed accidentally;
- legacy `assign()` / `render()` behavior is characterized before removing its
  carriers;
- no accidental process-global behavior is promoted into the target API merely
  to avoid a breaking change.

## 18. Invariants

STYLE-API-02F must preserve:

- normal `setElement()` semantic style output;
- document-local `StyleContext` ownership;
- semantic conflict/idempotence behavior;
- authored-template style authority;
- named-reference semantics;
- package ownership of physical resources;
- typed fill-image dependency semantics;
- repeated `save()` behavior;
- current `load()` logical-document lifecycle;
- supported legacy structured rendering until explicitly migrated;
- protected polymorphic behavior not directly targeted by this milestone;
- ODF family distinctions;
- successful public samples after their intentional migration.

## 19. Non-goals

STYLE-API-02F does **not** include:

- general STYLE-API-02G `StyleWriter` boundary cleanup;
- general STYLE-API-02H legacy getter/facade cleanup;
- STYLE-API-02I final API consistency closeout;
- document defaults;
- default font API redesign;
- `defineText()`;
- `defineTable()`;
- `defineGraphic()`;
- generic `defineStyle()`;
- public `StyleContext` exposure;
- generic `StyleManager` creation;
- frame/image anchor/wrap redesign;
- table geometry redesign;
- list layout redesign;
- template language changes;
- general `HtmlImporter` refactor;
- unrelated warnings/deprecations;
- unrelated `samples/output/*.odt` changes.

## 20. Sample-output safety

`samples/output/*.odt` remain manual/local regression artifacts unless a task
explicitly targets them.

During STYLE-API-02F:

- do not commit incidental generated sample outputs;
- do not restore/delete unrelated local sample output changes;
- do not commit LibreOffice `.~lock.*#` files;
- when a sample must be executed for regression, verify whether its tracked
  output changed before staging anything.

## 21. Documentation policy

Current user-facing docs and samples must converge on the target API.

Historical architecture documents remain historical evidence and should not be
rewritten merely because they describe now-retired mechanisms.

After STYLE-API-02F:

- current docs must not recommend process-global StyleMapper registration as
  normal authoring;
- current docs may describe retired compatibility behavior only when clearly
  marked as historical/legacy;
- StyleMapper documentation must describe mapping/transformation rather than
  document ownership.

## 22. Success criteria

STYLE-API-02F is complete when all of the following are true:

1. normal semantic structured insertion does not depend on process-global
   StyleMapper registries;
2. `RichTable` generated table-style ownership is document/element local;
3. named table-style references remain distinct from generated definitions;
4. `RichTableCell` no longer globally registers its normal styles;
5. `HtmlImporter` no longer performs redundant global paragraph/text
   registration for modern elements;
6. modern graphic/image/fill-image insertion remains document-local;
7. any retained legacy registry state is explicitly bounded to characterized
   compatibility paths;
8. dead/duplicate registry state is removed;
9. StyleMapper's remaining target responsibility is stateless
   mapping/parsing/identity generation;
10. no new process-global registry abstraction is introduced;
11. tests and public samples pass;
12. rendering-affecting table changes receive manual LibreOffice regression;
13. no unrelated sample outputs or lock files are committed.

## 23. Forward boundary

After STYLE-API-02F, STYLE-API-02G may address the remaining writer boundary.

Expected 02G questions include:

- which direct `StyleWriter` APIs remain intentionally public;
- whether broad `writeAllStyles()` compatibility behavior should be reduced;
- whether writer-owned static generated-style/font state remains justified;
- whether legacy compatibility serialization should be internalized or removed;
- how the writer should consume only explicitly supplied/document-owned state.

STYLE-API-02F must leave those questions visible rather than silently solving
them inside registry cleanup.

## 24. Decision

The accepted decision is:

> STYLE-API-02F removes document ownership from `StyleMapper`. `StyleMapper`
> remains as a stateless transformation utility. Document style semantics are
> owned by structured elements, `OdtDocumentContext`, and `StyleContext`.
> Process-global registry state is legacy compatibility debt and may remain only
> where focused characterization proves it is temporarily necessary. The
> implementation proceeds in two substantial slices: document ownership
> migration followed by mapper/registry surface cleanup.

Implementation begins only after the three mandatory P0 characterization areas
are covered.
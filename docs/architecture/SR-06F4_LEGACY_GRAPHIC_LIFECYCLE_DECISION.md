# SR-06F.4 Legacy Graphic Lifecycle Decision

## 1. Current-state summary

SR-06F.3 characterized the implementation after
`11ed28a1d7831e8149d1793fd03110cf3412b380`:

- normal `setElement()` uses document-local `StyleContext` graphic state and
  semantic dependency registries;
- legacy `assign()/render()` registers frame, image, and fill-image state in
  process-global `StyleMapper` registries;
- one legacy render performs the structured element path once for each core
  DOM, so registration and `toDomNode()` are repeated;
- `save()` runs the legacy image injector when the legacy structured flag is
  set, while `refresh()` does not;
- `load()` restores the original template and resets document-local state and
  the legacy structured flag, but does not reset static registries;
- static legacy state can therefore leak into a later legacy document.

The F.3 characterization tests and existing SR-06 lifecycle tests are the
evidence for this decision. F.4 does not repair any of these observations.

## 2. Ownership alternatives

### Process-global ownership

This is the current behavior of the legacy StyleMapper and
LegacyStyleRegistry APIs. It is compatible with context-free direct callers,
but it cannot identify which of several active documents owns a registration.
It conflicts with the established document-local ownership contract and
permits cross-document materialization leakage.

### Render-local ownership

This would scope registrations to one render/finalization cycle. It could
avoid long-lived leakage, but would make repeated `save()` and later
finalization depend on an implicit cycle boundary. It would also require a
new transfer mechanism for direct legacy callers and would not match the
document-local lifetime of `OdtDocumentContext`.

### Document-local ownership

This scopes semantic and document-aware compatibility state to the current
`OdtDocumentContext`. It matches `StyleContext`,
`FillImageRequirementRegistry`, loaded-DOM authority, `load()` reset behavior,
and the semantic requirement model. It is the selected ownership model for
document generation.

## 3. Chosen ownership semantics

The authoritative model is document-local:

```text
current OdtTemplate / OdtDocumentContext
  -> semantic requirements and dependencies
  -> document-local compatibility state when explicitly adopted
  -> document finalization
```

Static legacy registries remain available as compatibility state for
context-free direct callers. They are not implicit initial state for a newly
constructed document and must not be treated as the authoritative source for
normal document generation.

This is a future lifecycle contract, not a claim that the current legacy
assign/render implementation already satisfies it. The known static leakage
is an intentionally retained compatibility bug until a later implementation
slice defines the transfer/reset bridge.

## 4. Frame/image/fill-image decision matrix

| Family | Normal `setElement()` authority | Legacy `assign()/render()` compatibility | F.4 decision |
|---|---|---|---|
| Frame | Current DrawTextBox semantic graphic materialization plus document-local raw frame carrier where current output still requires it | Static `StyleMapper::$frameStyles` consumed by StyleWriter when legacy finalization is enabled | Keep public/static compatibility; future document-aware adoption must be explicit and must preserve protected hooks |
| Image | ImageElement remains a legacy producer; its current normal path uses document-local `StyleContext::imageStyles()` | Static registered image styles consumed by `injectLegacyImageStyles()` | Keep static direct API; do not make it authoritative for normal documents; migrate or bridge ImageElement only in a dedicated slice |
| Fill-image | Typed document-local `FillImageRequirementRegistry` is authority for migrated CircularImage normal path; raw StyleContext fill state remains a compatibility mirror | Static registered fill images consumed by `injectLegacyImageStyles()` | Keep legacy fill API/state for assign/render; do not bulk-import it into unrelated documents; preserve semantic authority in normal path |

The families must not be forced through one identical migration mechanism.
Fill-image has a separate typed dependency and package-resource relationship;
frame and image remain graphic-style compatibility channels.

## 5. Compatibility-facade requirements

The following remain callable and compatibility-sensitive:

- `StyleMapper::registerImageStyle()`, `registerFillImage()`, frame
  registration APIs, and corresponding getters;
- `LegacyStyleRegistry::registerParagraphStyle()` and its first-registration-
  wins behavior;
- `OdtElement` legacy requirement accessors;
- `OdtTemplate::injectImageStyles()`,
  `injectLegacyImageStyles()`, `setValuesInDom()`, and related protected hooks;
- public `assign()`, `setValues()`, `render()`, `save()`, `refresh()`, `load()`,
  `setImage()`, and `replaceImageByName()`.

A future compatibility facade may adopt a registration into the current
document only when the OdtTemplate path has an explicit document boundary.
A context-free static caller cannot be silently redirected to one of several
documents without reintroducing a hidden global current-document pointer.
Direct static callers therefore remain process-global compatibility behavior
until an explicit API or transfer rule is approved.

## 6. Save and refresh decision

The existing lifecycle contract defines `save()` as finalization of the
current document. Repeated saves of an unchanged document should remain
idempotent and must not import state from another document.

`refresh()` is different: current `OdtTemplate::refresh()` persists core DOMs,
then calls public `load()`, and `load()` restores the original template. Thus
refresh is currently a reset-to-original-template operation with a
pre-reset-finalization side effect, not a general "persist current state and
reload it" operation.

Decision:

- legacy structured graphic definitions are not required to survive
  `assign()/render() -> refresh()` under the current public contract;
- F.4 does not add Save/Refresh parity;
- the observed loss of legacy image/fill definitions after refresh remains a
  deferred compatibility bug/behavior decision;
- any future change must explicitly choose between reset semantics and full
  current-state reload semantics, then characterize save, refresh, and load
  together.

This preserves the documented rule that refresh semantics must not be
silently redefined by a style-ownership refactor.

## 7. Compatibility risk analysis

### Direct static StyleMapper registrations

They are context-free and currently process-global. Existing callers may rely
on later legacy finalization consuming them. They must remain available, but
their state must not become the implicit authoritative input for a separate
document-local semantic path.

### Protected overrides

Subclasses may override `setValuesInDom()`, `injectImageStyles()`, or
`injectLegacyImageStyles()`. Dispatch, visibility, and observable hook timing
must remain stable while any future bridge is introduced.

### Repeated render/save

Repeated legacy renders repeat `toDomNode()` and registration, but equivalent
name-keyed registrations are stable. Repeated saves rely on existing
existence checks and must not acquire additional cross-document state.

### Refresh/load

`load()` resets document-local state and restores the original template. It
does not reset static registries. `refresh()` performs finalization before
that reset. Both facts are compatibility-sensitive observations, not desired
semantic ownership rules.

### Mixed normal and legacy paths

Normal `setElement()` uses document-local state; legacy `assign()/render()`
uses static registries. Mixing them in one process makes ownership source and
finalization timing significant. A future bridge must keep the paths explicit
and must not let a normal document inherit unrelated static registrations.

### CircularImageElement mutable state

`$fillImageName` is assigned by `toDomNode()`. The semantic fill dependency is
available before that call, while the historical array API is populated after
it. This asymmetry is part of legacy compatibility and must remain observable
until the legacy lifecycle is deliberately migrated.

## 8. Behavior classification

### Intentional compatibility contracts

- public/static legacy registration APIs remain callable;
- protected finalization hooks remain overrideable;
- legacy `assign()/render()` continues to use its existing two-DOM path;
- `load()` restores the original template;
- normal semantic and legacy paths retain their current output contracts.

### Accidental historical behavior / probable bugs

- static frame/image/fill registries leak definitions across independent
  documents;
- legacy `assign()/render() -> refresh()` does not preserve the graphic
  definitions that `save()` would materialize.

These are safe to change only after characterization and an explicit follow-up
implementation contract. F.4 does not change them.

### Observable but not yet classified for removal

- the exact number and timing of `toDomNode()` calls during legacy rendering;
- external dependence on first-registration-wins or cross-document static
  accumulation;
- whether refresh's current loss of legacy definitions is relied upon as part
  of reset behavior.

## 9. Next implementation boundary

The next implementation slice should be narrowly scoped to a document-aware
compatibility bridge for one family at a time, preferably beginning with the
legacy ImageElement path because it exposes the clearest static-to-physical
leak. It should:

1. preserve direct static StyleMapper calls;
2. establish an explicit adoption boundary for an OdtTemplate document;
3. prevent unrelated static entries from being materialized into that
   document;
4. preserve protected hooks and legacy save behavior for callers that remain
   on the legacy path;
5. characterize frame, image, and fill-image separately before applying the
   same rule to another family.

Refresh parity/reset semantics should be a separate lifecycle decision or a
later sub-slice, not an incidental part of the first registry bridge.

## 10. Explicit non-goals

F.4 does not:

- reset static registries;
- change `save()`, `refresh()`, `render()`, `assign()`, or `load()`;
- remove or rename StyleMapper, LegacyStyleRegistry, or protected hooks;
- migrate ImageElement or redesign CircularImageElement;
- alter semantic graphic/fill-image requirements;
- introduce a process-global current-document pointer;
- modify tables, fonts, resources, page styles, samples, `tmp/`, or lock
  files;
- create a broad new context or dependency framework.

No separate Change Contract is created in F.4. If the next implementation
slice needs to change static ownership or refresh semantics, it should first
publish a focused contract naming the affected family, adoption rule, reset
policy, and compatibility guarantees.

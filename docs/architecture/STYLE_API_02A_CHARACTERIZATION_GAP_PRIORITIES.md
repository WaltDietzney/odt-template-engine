# STYLE-API-02A — Characterization Gap Priorities

Status: **PRIORITIZATION — NO API REDESIGN / NO PRODUCTION CHANGE**  
Source-of-truth baseline: `develop` at `67507b663b55a0177b5c6e95ac6aead8e24f0134`  
Working branch: `architecture/style-api-02a-public-api-audit`

## 1. Purpose

`STYLE_API_02A_PUBLIC_STYLE_API_AUDIT.md` identified nine focused
characterization gaps before irreversible public style API decisions.

Not all of those gaps need to be closed before STYLE-API-02B can begin.
Blocking 02B on every historical implementation detail would mix API boundary
design with compatibility archaeology and would make the design phase larger
than necessary.

This document therefore classifies the gaps by the decision they can affect:

1. **02B ENTRY GATE** — must be characterized before the target public API
   layers are designed;
2. **02B CONTRACT GATE** — may be characterized while 02B is underway, but
   must be closed before 02B approves migration, deprecation, visibility, or
   compatibility decisions for the affected surface;
3. **IMPLEMENTATION GATE** — does not block 02B architecture and can remain
   open until a concrete implementation slice would touch the mechanism.

The purpose is sequencing, not deciding the outcome of any gap.

## 2. Prioritization principle

STYLE-API-02B must first answer three architectural questions:

- what normal library users should be taught to use;
- what custom `OdtElement` / subclass authors are expected to implement or
  override;
- what low-level compatibility API remains explicitly supported.

A characterization gap is an **02B entry blocker** only when its unknown
behavior can change one of those three boundaries.

A gap is not an entry blocker merely because the implementation is untidy,
duplicated, or historical.

This preserves the project rule:

> Semantics before implementation.

It also preserves compatibility by requiring stronger evidence before any
existing public/protected surface is narrowed.

## 3. Current public-usage baseline

The root README teaches the application-authoring layer through
`OdtTemplate::setElement()`, `Paragraph`, `RichText`, fluent element methods,
and friendly style option arrays. It describes styles as a structured-element
capability but does not teach process-global `StyleMapper` registries or direct
`StyleWriter` calls as the normal application model.

That is important evidence for 02B: the strongest intentionally taught public
surface is already the element/authoring API.

This does **not** prove that low-level public APIs are unused or unsupported.
Tests and historical compatibility behavior provide separate evidence for such
surfaces. It only means that technically public implementation mechanisms
should not automatically be treated as equally recommended user APIs.

## 4. Priority summary

| Gap | Priority | Must be closed before | Reason |
|---|---|---|---|
| 1. `HasStyles` dispatch | **P0 — 02B ENTRY GATE** | 02B starts | Changes the effective custom-element compatibility contract and currently contradicts architecture documentation. |
| 8. Subclass override surface | **P0 — 02B ENTRY GATE** | 02B starts | 02B cannot define the extension layer without knowing which legacy/protected hooks are observably override-sensitive. |
| 9. Documentation/sample usage | **P0 — 02B ENTRY GATE, bounded scope** | 02B starts | Distinguishes intentionally taught API from merely public PHP surface. Only the style-related public teaching baseline is needed initially. |
| 7. Direct `StyleWriter` audience | **P1 — 02B CONTRACT GATE** | low-level API contract is approved | `writeAllStyles()` has compatibility evidence; lower-level helpers need audience classification before visibility/deprecation decisions. |
| 2. Direct static property writes | **P1 — 02B CONTRACT GATE** | registry-property migration/deprecation is approved | Public mutable state is compatibility-sensitive, but its exact write behavior does not determine the high-level 02B layer model. |
| 3. Overlapping mapper functions | **P1 — 02B CONTRACT GATE** | canonical mapper naming/migration is approved | Semantic overlap must be characterized before choosing wrappers or deprecations, but it does not block defining the API layers. |
| 4. Text registry variants | **P1 — 02B CONTRACT GATE** | text registry canonicalization is approved | Replacement/idempotence differences matter for compatibility wrappers, not for the initial target architecture. |
| 6. Getter side effects | **P1 — 02B CONTRACT GATE** | getter purity or RichTable migration is approved | Existing side effects must be preserved or deliberately migrated, but they do not determine the top-level authoring model. |
| 5. Secondary table-cell state | **P2 — IMPLEMENTATION GATE** | an implementation removes/merges that state | Appears to be internal redundancy. It should not drive public API design unless a concrete change touches it. |

## 5. P0 — 02B entry gate

### 5.1 GAP-01 — `HasStyles` dispatch

**Priority:** P0 — first characterization slice.

The current source has a material contradiction:

- concrete structured elements implement
  `OdtTemplateEngine\Contracts\HasStyles`;
- `OdtTemplate::registerStructuredHasStylesCompatibility()` uses an
  unqualified `instanceof HasStyles`;
- the inspected `OdtTemplate` imports do not include the contract;
- the STYLE-CONTEXT-01 final audit previously described this compatibility
  phase as active.

Before 02B decides whether `HasStyles` is a permanent compatibility interface,
a semantic facade, or a deprecation candidate, its **effective current
behavior** must be proven.

Required characterization should cover at least:

1. a normal engine element implementing `Contracts\HasStyles` on
   `setElement()`;
2. an external/probe `OdtElement` implementing `Contracts\HasStyles`;
3. whether `registerStyles()` / `getStyleDefinitions()` are actually dispatched
   by the intended OdtTemplate compatibility phase;
4. whether current successful output depends on another path instead;
5. the observed behavior without silently fixing the namespace discrepancy.

Expected output:

- focused characterization tests;
- a short architecture note recording ACTIVE / INACTIVE / PARTIALLY SHADOWED
  behavior;
- no API redesign in the same slice.

This is the **highest-priority gap** because an incorrect assumption here would
make 02B design the wrong custom-element extension contract.

### 5.2 GAP-08 — Subclass override surface

**Priority:** P0 — second characterization slice.

STYLE-CONTEXT-01 deliberately retained protected facades and legacy getters
because external subclasses may override them. 02B needs a clear extension
contract and therefore must know which hooks have actual dispatch evidence.

The initial characterization should focus on style-related hooks only:

- legacy `OdtElement` getter families;
- modern `getOwnStyleRequirements()` / `ownedElements()` hooks;
- `HasStyles` methods after GAP-01 is understood;
- protected `OdtTemplate` style/materialization compatibility callbacks that
  were retained specifically for polymorphism.

The goal is **not** to exhaustively test every protected method in the library.
The goal is to classify each relevant hook as one of:

- proven external/subclass dispatch contract;
- internally used but not yet externally characterized;
- legacy facade whose dispatch must be retained even if implementation changes;
- no evidence yet of extension relevance.

02B should not convert an override-sensitive hook into a non-dispatching
shortcut without this evidence.

### 5.3 GAP-09 — Documentation and sample usage

**Priority:** P0, but deliberately bounded.

02B needs to know which API the project intentionally teaches as the normal
style model. It does **not** need an exhaustive archaeology of every historical
sample before design can start.

The entry-gate review should answer:

1. Which style APIs are shown in the root README and current developer docs?
2. Which style APIs are used by current representative samples as user-facing
   authoring patterns?
3. Are `StyleMapper` registries, mutable static properties, direct
   `StyleWriter`, `HasStyles`, or raw requirement getters explicitly taught as
   public application API?
4. Are any current docs still teaching architecture that contradicts the
   document-local semantic model?

Current root README evidence already points strongly toward structured element
options/fluent methods as the recommended application layer. The remaining
P0 work is to confirm that this is also true across the current style-focused
documentation and representative samples.

Historical usage can still be evidence for compatibility, but it should be
classified separately from **recommended current API**.

## 6. P1 — 02B contract gate

These gaps do not prevent 02B from defining the desired layer model. They do
prevent 02B from approving a concrete migration/deprecation contract for the
affected methods.

### 6.1 GAP-07 — Direct `StyleWriter` audience

`StyleWriter::writeAllStyles()` already has explicit direct-call compatibility
status from STYLE-CONTEXT-01. The unresolved question is how much of the
lower-level public writer surface is genuinely supported for external callers.

Characterize before 02B states which writer methods remain public low-level
API or become future visibility/deprecation candidates.

The characterization should distinguish:

- direct tests/samples/docs;
- external-looking call patterns;
- engine-internal-only call sites;
- methods needed by protected subclass facades.

Do not infer that every public helper is a promised library API merely from
visibility.

### 6.2 GAP-02 — Direct writes to public static properties

`StyleMapper::$frameStyles` and `$tableStyles` are public mutable global state.
At least frame-state observability is already covered by API-contract testing,
so they cannot be casually privatized.

Before any migration/deprecation decision, characterize:

- direct writes versus reads;
- first-write/replacement semantics when callers bypass registration methods;
- interaction with current-document filtering;
- representative tests/samples;
- whether compatibility can later be preserved through a facade.

This is high compatibility risk but does not determine the primary 02B
application-authoring model.

### 6.3 GAP-03 — `mapTableCellStyle()` versus `mapTableCellStyleOptions()`

The source already proves semantic differences:

- `mapTableCellStyle()` supports a narrow historical option set;
- `mapTableCellStyleOptions()` accepts a broader vocabulary, side-specific
  borders/padding, direct ODF-prefixed properties, weight/color, and aliases.

Before choosing a canonical public mapper, characterize actual call sites and
edge-case differences. A future wrapper must not silently change mappings for
legacy callers.

This is a method-level canonicalization question, not an 02B entry blocker.

### 6.4 GAP-04 — Text registry variants

`registerTextStyle()` generates a style name and writes both registered text
state paths. `setTextStyle()` accepts a caller-provided name and only writes
when the name is not already registered. Getter names also overlap.

The implementation already shows that these are not simple synonyms.
Characterization is required before naming one canonical and wrapping or
retiring the other.

Required evidence should include:

- same-name repeated calls;
- differing definitions under the same name;
- generated versus caller-supplied names;
- getter visibility of each path;
- direct `StyleWriter` effects.

### 6.5 GAP-06 — `RichTable::getTableStyleDefinitions()` side effects

A future API may reasonably want getters to be side-effect free, but changing
this historical getter without characterization could alter legacy table style
registration timing.

Before 02B approves getter-purity migration, prove:

- what static state changes on invocation;
- whether output differs if the getter is not called;
- which lifecycle paths invoke it;
- whether external subclass/direct callers can observe the side effect.

Until then, the misleading name is an API debt finding, not permission to
rewrite behavior.

## 7. P2 — implementation gate

### 7.1 GAP-05 — Secondary table-cell state

`StyleMapper` contains both `$registeredTableCellStyles` and `$tableCellStyles`.
The active registration method writes `$tableCellStyles`, while broader
registry reporting also exposes `$registeredTableCellStyles`.

This remains suspicious, but the evidence currently classifies it as an
internal redundancy candidate rather than a public API boundary question.

It therefore should **not block 02B**.

Characterize it when an implementation slice proposes to:

- merge the fields;
- remove one field;
- change `getAllRegisteredStyles()`;
- change table-cell writer behavior;
- alter table-cell registry compatibility.

If later evidence shows external observability beyond existing getter output,
its priority can be raised.

## 8. Explicit non-blockers for 02B

The following topics should remain outside the entry gate unless new evidence
shows a direct public API dependency:

- document defaults and `style:default-style` semantics;
- frame/image positioning redesign;
- table width/column geometry;
- paragraph/text StyleContext ownership, which is already settled;
- process-global registry lifetime redesign;
- static font helper cleanup / document-default policy;
- assign/render lifecycle redesign;
- broad `StyleWriter` default behavior already retained for direct legacy
  callers.

These can influence later API decisions, but they are separate architectural
issues and should not expand the characterization phase.

## 9. Recommended execution order

The recommended work sequence before 02B is:

```text
STYLE-API-02A audit
        ↓
GAP-01 HasStyles dispatch characterization
        ↓
GAP-08 subclass/override style surface characterization
        ↓
GAP-09 bounded docs/samples public-usage check
        ↓
02B ENTRY GATE — GO / NO-GO
        ↓
STYLE-API-02B target API layer design
        ↓
P1 characterizations as required by concrete 02B contract decisions
        ↓
implementation slices
        ↓
P2 characterization immediately before touching secondary state
```

GAP-01 should come first because its result affects how GAP-08 interprets
`HasStyles` as an extension hook.

GAP-09 can be performed partly in parallel with GAP-08, but the final entry-gate
summary should be produced only after both are known.

## 10. 02B entry criteria

STYLE-API-02B may begin when all of the following are true:

1. `HasStyles` effective dispatch is characterized and the existing
   code/documentation contradiction is explicitly classified;
2. the style-related subclass/override surface has a bounded evidence matrix;
3. the currently taught style API in README/current docs/representative samples
   is identified separately from compatibility-only or technically public API;
4. no P0 result reveals a new semantic ownership problem that would invalidate
   the STYLE-CONTEXT-01 baseline.

02B does **not** require every P1/P2 method-level question to be closed before
it can define the target layers.

However, 02B must not make an irreversible migration/deprecation decision for
a P1 surface until that surface's characterization is complete.

## 11. Decision

The nine STYLE-API-02A gaps are therefore prioritized as follows:

```text
P0 — before 02B design starts
  GAP-01 HasStyles dispatch
  GAP-08 subclass override surface
  GAP-09 bounded documentation/sample usage

P1 — before affected 02B compatibility/deprecation contract is approved
  GAP-07 direct StyleWriter audience
  GAP-02 public static property writes
  GAP-03 overlapping table-cell mappers
  GAP-04 text registry variants
  GAP-06 RichTable getter side effects

P2 — before implementation touches the mechanism
  GAP-05 secondary table-cell state
```

This keeps the next step small and evidence-driven. The immediate task is
**GAP-01 — `HasStyles` dispatch characterization**, not STYLE-API-02B design
and not API cleanup.
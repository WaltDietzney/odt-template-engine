# STYLE-API-02A — Public Style API Audit

Status: **AUDIT — NO API REDESIGN / NO PRODUCTION CHANGE**  
Source-of-truth baseline: `develop` at `67507b663b55a0177b5c6e95ac6aead8e24f0134`  
Working branch: `architecture/style-api-02a-public-api-audit`

## 1. Purpose

STYLE-CONTEXT-01 completed the internal semantic ownership migration. Styles
required by modern structured elements are now represented through semantic
requirements and resolved in the document-local `OdtDocumentContext` /
`StyleContext` pipeline. Public static registries, direct `StyleWriter`
behavior, legacy getters, and protected compatibility facades were retained
explicitly for compatibility.

STYLE-API-02 therefore starts from a different question than
STYLE-CONTEXT-01:

> Which style API does a library user or subclass author actually see today,
> and does that public surface form a coherent and understandable API model?

STYLE-API-02A answers that question by inventorying and classifying the
existing surface. It does not define the target API and does not approve any
removal or deprecation.

## 2. Scope

The audit covers four surfaces:

1. public/static style utilities and registries (`StyleMapper`,
   `StyleWriter`, `LegacyStyleRegistry`);
2. element-facing style APIs (`HasStyles`, `OdtElement`, `Paragraph`,
   `RichText`, `ListElement`, `RichTable`, `RichTableCell`, `ImageElement`,
   `DrawTextBox` and related structured producers);
3. `OdtTemplate` style facade and protected extension/compatibility surface;
4. observed use in tests, samples, README/API documentation, and architecture
   documentation.

Each relevant API is considered along these dimensions:

- visibility;
- intended audience;
- role (mapping, definition, registration, lookup, materialization);
- state lifetime (stateless, element-local, document-local, process-global);
- semantic authority versus compatibility role;
- use on the modern `setElement()` path;
- use on legacy/direct paths;
- subclass/override sensitivity;
- overlap with another API;
- whether the name and DocBlock still describe the current semantics.

## 3. Non-goals

STYLE-API-02A does **not**:

- redesign `StyleRequirement` or `StyleContext`;
- introduce a new style system;
- remove `StyleMapper` or `LegacyStyleRegistry`;
- alter registry lifetime/reset behavior;
- redesign `assign()` / `render()` / `save()` lifecycle behavior;
- design document defaults;
- introduce style presets;
- redesign frame/image/table layout;
- change any public or protected method;
- declare any API deprecated.

Unexpected or questionable behavior identified below is an audit finding and,
where necessary, a characterization requirement for later work.

## 4. Classification terminology

The classifications in this document are preliminary audit labels, not change
decisions.

| Classification | Meaning in this audit |
|---|---|
| **MODERN** | Semantics align with the current document-local structured architecture and the surface has a clear current role. |
| **SUPPORTED COMPATIBILITY** | Observable/public/protected behavior is still supported even though it is not the modern semantic authority. |
| **DEPRECATION CANDIDATE** | The surface appears overlapping, misleading, or obsolete enough to justify a later compatibility/design decision. No deprecation is approved here. |
| **INTERNAL-ACCIDENTAL** | Public visibility appears broader than the observed architectural/user role. This requires evidence before visibility can change. |
| **CHARACTERIZATION REQUIRED** | Current behavior or extension impact is insufficiently established for a design decision. |

A method may belong to more than one category. For example, a compatibility
facade can also be a future deprecation candidate.

## 5. Executive findings

The current style API is not one coherent public layer. It is the accumulated
surface of several architectural generations that intentionally coexist after
STYLE-CONTEXT-01:

1. **User-facing style options.** Elements expose convenient arrays and helper
   methods such as `Paragraph::addText(..., $style)`, `Paragraph::setStyle()`,
   `RichTableCell::setBackground()`, and table/image/frame option arrays. This
   is the most understandable application-facing layer and remains useful.
2. **Element-local style definitions.** Producers expose generated style
   names, mapped ODF properties, `getStyleDefinitions()`, and older required
   style getters.
3. **Modern semantic requirements.** `getOwnStyleRequirements()` and related
   typed dependency hooks describe document-local semantic needs.
4. **Global compatibility registration.** `registerStyles()` and
   `StyleMapper::register...()` mirror historical styles into process-global
   maps.
5. **Direct serialization infrastructure.** `StyleWriter` exposes broad public
   helpers that can serialize compatibility state directly.

The internal ownership architecture is therefore substantially cleaner than
the public PHP surface suggests. The central STYLE-API-02 problem is not that
semantic ownership must be migrated again. It is that users and subclass
authors can currently see multiple ways to describe, register, retrieve, and
serialize the same general concept without a clear public hierarchy.

The strongest 02A findings are:

- `StyleMapper` combines stateless option mapping, style-name generation,
  parsing, process-global mutation, lookup, and directly exposed mutable
  registries in one public static class.
- Multiple APIs overlap within the same family (`mapTableCellStyle()` versus
  `mapTableCellStyleOptions()`, `setTextStyle()` versus
  `registerTextStyle()`, and parallel text/table-cell getters).
- `OdtElement` exposes both legacy requirement families and modern semantic
  requirement hooks, so implementation history is visible as public API.
- `HasStyles` still describes global style-manager registration in its
  DocBlock even though modern semantic authority is document-local.
- Several concrete elements still implement `registerStyles()` by mutating
  static compatibility state; some constructors/setters also register as a
  side effect.
- `RichTable::getTableStyleDefinitions()` is especially misleading because a
  getter performs registration and returns global registry state.
- `StyleWriter` has a meaningful direct compatibility contract, but many of
  its lower-level public helpers look more like infrastructure than a coherent
  application-facing API.
- The current `OdtTemplate` source contains an unqualified `HasStyles` check in
  `registerStructuredHasStylesCompatibility()` while the actual interface is
  `OdtTemplateEngine\Contracts\HasStyles`. No root-namespace `HasStyles`
  contract was found in the inspected source tree. This makes the effective
  dispatch of that compatibility phase a characterization issue and also
  conflicts with the earlier final-audit description that treated the hook as
  active. STYLE-API-02A does not silently repair this.

The result supports a STYLE-API-02B design phase, but only after the identified
characterization gaps have been separated from API preference.

## 6. Public static surface — `StyleMapper`

### 6.1 Current responsibilities

`StyleMapper` currently performs at least five distinct jobs:

1. maps friendly style options to ODF properties;
2. generates deterministic style names;
3. parses/normalizes CSS-like style input;
4. registers compatibility definitions in process-global state;
5. exposes those registered definitions to callers/writers.

This is historically understandable but is not a single semantic
responsibility.

### 6.2 Mapping/parsing APIs

Observed public mapping/parsing methods include:

- `mapParagraphStyle()`;
- `mapTableCellStyle()`;
- `mapTableCellStyleOptions()`;
- `mapTextStyleOptions()`;
- `mapFrameStyleOptions()`;
- image/table related mapping helpers;
- `parseCssStyleString()`;
- `generateStyleName()`.

These functions are primarily stateless transformations and are conceptually
different from registry mutation. They are also directly useful to element
implementations.

The table-cell pair is an immediate consistency issue. Both
`mapTableCellStyle()` and `mapTableCellStyleOptions()` map table-cell options,
but the latter accepts a broader vocabulary and direct ODF-prefixed
properties. Their names do not communicate the semantic difference strongly
enough for a public API.

**Preliminary classification:** mapping/name generation is **MODERN INTERNAL
UTILITY / POSSIBLE PUBLIC UTILITY**; overlapping variants are
**DEPRECATION CANDIDATES / CHARACTERIZATION REQUIRED** until direct user usage
is established.

### 6.3 Registry APIs

The current public/static registry families include:

| Family | Public mutation/read surface | State | Audit classification |
|---|---|---|---|
| paragraph | `registerParagraphStyle()`, paragraph getter facade | process-global through `LegacyStyleRegistry` | **SUPPORTED COMPATIBILITY** |
| text | `setTextStyle()`, `registerTextStyle()`, `getTextStyles()`, `getRegisteredTextStyles()` | process-global | **SUPPORTED COMPATIBILITY**, overlapping naming |
| frame | `addFrameStyle()`, `getFrameStyles()`, public `$frameStyles` | process-global and directly mutable | **SUPPORTED COMPATIBILITY**, direct property is API debt |
| image | `registerImageStyle()`, `getRegisteredImageStyles()` | process-global | **SUPPORTED COMPATIBILITY** |
| fill-image | `registerFillImage()`, `getRegisteredFillImages()` | process-global | **SUPPORTED COMPATIBILITY** |
| table | `registerTableStyle()`, `getRegisteredTableStyles()`, public `$tableStyles` | process-global and partly directly mutable | **SUPPORTED COMPATIBILITY**, direct property is API debt |
| table-cell | `registerTableCellStyle()` and multiple table-cell maps/getters | process-global | **SUPPORTED COMPATIBILITY**, overlap requires characterization |
| fonts | registration/XML helper surface | process-global compatibility residue | **CHARACTERIZATION REQUIRED** relative to document-local font dependencies |

STYLE-CONTEXT-01 already established that these registries are not modern
semantic authority. STYLE-API-02 should therefore not describe them as the
primary way to define styles for structured insertion.

### 6.4 Naming consistency

The verbs `set`, `register`, `add`, and `get` currently do not correspond to a
stable behavioral distinction:

- `setTextStyle()` and `registerTextStyle()` both concern text registry state;
- frames use `addFrameStyle()` rather than `registerFrameStyle()`;
- paragraph styles delegate to a separate registry;
- tables expose a public mutable map as well as register/get methods.

A user cannot infer lifetime, first-write-wins behavior, replacement semantics,
or document scope from the method names.

### 6.5 Public mutable properties

`StyleMapper::$frameStyles` and `StyleMapper::$tableStyles` are public mutable
process-global state. Their existence is stronger than a normal public method
contract: callers can bypass validation, naming, idempotence, and any future
lifecycle policy.

They must therefore be treated as compatibility-sensitive. Their design is a
clear candidate for later narrowing, but 02A does not assume that direct
external writes are unused.

## 7. `LegacyStyleRegistry`

`LegacyStyleRegistry` is semantically clearer than `StyleMapper`: it explicitly
states that it is a compatibility-only process-wide registry for paragraph
styles and preserves historical first-write-wins behavior.

Its public methods are:

- `registerParagraphStyle()`;
- `paragraphStyles()`.

This class is not a modern user-facing style model. It is a compatibility
mechanism that `StyleMapper` exposes indirectly and that existing fallback and
direct-writer behavior can observe.

**Preliminary classification:** **SUPPORTED COMPATIBILITY / INTERNAL
TRANSPORT**. Its explicit naming is substantially more accurate than older
`StyleMapper` documentation.

## 8. `StyleWriter`

### 8.1 Two contracts must remain distinguished

STYLE-CONTEXT-01 already established a deliberate dual contract:

- direct callers of `StyleWriter::writeAllStyles()` receive broad historical
  defaults;
- `OdtTemplate` invokes writing/finalization with current-document filters and
  semantic exclusions.

02A does not reclassify the broad direct behavior as a bug.

### 8.2 Public surface

`StyleWriter` exposes `writeAllStyles()` plus a broad set of lower-level public
helpers for fonts, text, paragraphs, frames, table cells, tables, automatic
styles, style-node creation/property expansion, attribute maps, and property
routing.

There are two different likely audiences hidden behind this one visibility
level:

1. callers intentionally using `StyleWriter` as a low-level compatibility API;
2. engine-internal materialization/writer code.

`writeAllStyles()` has strong compatibility evidence. The lower-level helpers
need individual usage evidence before they should be promised as stable
library API.

**Preliminary classification:** `writeAllStyles()` = **SUPPORTED
COMPATIBILITY**. Lower-level public helpers = **SUPPORTED WHERE DIRECT USE IS
EVIDENCED; OTHERWISE INTERNAL-ACCIDENTAL / CHARACTERIZATION REQUIRED**.

## 9. Element-facing base contract — `OdtElement`

`OdtElement` is where architectural generations are most visible side by
side. Its public surface includes:

### Structural/modern hooks

- `ownedElements()`;
- `getOwnStyleRequirements()`;
- `getOwnFillImageDependencies()`;
- `getOwnImageAssets()`.

These express self-owned semantic/resource information while collector
services own traversal. That division aligns well with the current
architecture.

### Legacy/compatibility style hooks

- `getRequiredStyles()`;
- `getOwnRequiredStyles()`;
- `getOwnRequiredParagraphStyles()`;
- `getOwnFrameStyleRequirements()` / `getFrameStyleRequirements()`;
- `getOwnImageStyleRequirements()` / `getImageStyleRequirements()`;
- `getOwnFillImageRequirements()` / `getFillImageRequirements()`;
- `getStyleDefinitions()`;
- `getImageAssets()`;
- `toStyleDomNode()`.

These methods are not automatically removable. Existing architecture work has
shown that they are used by legacy collectors, compatibility adoption, direct
callers, tests, and subclass probes. However, from a public API-design
perspective they expose engine transport details and make it difficult to tell
which hook a new custom element author should implement.

**Preliminary classification:** modern `getOwn...` semantic/dependency hooks =
**MODERN EXTENSION SURFACE**. Legacy getter families = **SUPPORTED
COMPATIBILITY / OVERRIDE-SENSITIVE**.

## 10. `HasStyles`

The `HasStyles` interface currently requires:

- `registerStyles(): void`;
- `getStyleDefinitions(): array`.

Its DocBlock says implementations should register text/paragraph styles in a
style manager such as `StyleMapper`. That description belongs to the older
global-registration architecture. It does not explain the document-local
semantic requirement pipeline and therefore no longer describes modern style
authority.

This creates three distinct issues:

1. **contract semantics drift:** the documentation presents global
   registration as the main model;
2. **responsibility overlap:** `registerStyles()` mutates compatibility state
   while `getOwnStyleRequirements()` can describe the same style semantically;
3. **extension ambiguity:** a new subclass author cannot tell whether
   `HasStyles`, semantic requirements, or both are required.

The interface must nevertheless be treated as compatibility-sensitive because
external element subclasses can implement it and existing concrete elements do
so.

### 10.1 Current `OdtTemplate` dispatch discrepancy

`OdtTemplate::registerStructuredHasStylesCompatibility()` performs an
unqualified `instanceof HasStyles`. The inspected `OdtTemplate.php` imports do
not include `OdtTemplateEngine\Contracts\HasStyles`, while the actual interface
lives in that namespace and the concrete elements explicitly import it.

The source tree inspected for this audit did not reveal a root namespace
`OdtTemplateEngine\HasStyles` interface. If that reading is correct, the
compatibility branch may not dispatch for the intended element implementations
on the current `develop` baseline.

This is significant because `STYLE_CONTEXT_01_FINAL_AUDIT.md` describes
`HasStyles` compatibility registration as active. The code/documentation
relationship therefore needs a focused characterization test before 02B treats
that path as either required active behavior or dead residue.

This audit records the contradiction rather than resolving it silently.

**Preliminary classification:** `HasStyles` = **SUPPORTED SUBCLASS
COMPATIBILITY, SEMANTICALLY STALE, CHARACTERIZATION REQUIRED**.

## 11. Concrete structured element surface

### 11.1 `Paragraph`

`Paragraph` exposes a useful user-facing style layer through text and paragraph
style option arrays. It also exposes local style names/definitions, modern
semantic requirements, legacy required-style getters, and `registerStyles()`.

`registerStyles()` explicitly mirrors text and paragraph styles into
`StyleMapper`. Therefore a single `Paragraph` can participate in both the
modern document-local pipeline and global compatibility state.

This is an intentional transition architecture but a confusing public model.
The user-facing option API should be distinguished from the compatibility
transport in 02B.

### 11.2 `RichText`

`RichText` similarly accepts convenient style arrays and splits paragraph/text
semantics. It recursively delegates to styled child elements, produces semantic
requirements, and retains `HasStyles` registration/definition behavior.

Its public authoring API is coherent at the content level. The ambiguity is in
which lower-level style lifecycle API is canonical.

### 11.3 `RichTableCell`

`RichTableCell::setStyle()` is a strong example of mixed responsibilities. It:

- splits friendly options by table-cell/paragraph/text concern;
- maps the cell properties;
- generates a style name;
- immediately calls `StyleMapper::registerTableCellStyle()`;
- propagates paragraph/text portions into contained `Paragraph` or `RichText`.

The constructor calls style setup, and convenience methods such as
`setBackground()`, borders, and padding refresh/register the style again.
Meanwhile `getOwnStyleRequirements()` provides the modern semantic table-cell
definition.

Thus a normal user-facing setter has an observable process-global compatibility
side effect even though modern ownership is document-local.

This does not automatically mean the side effect should be removed. It does
mean that the current public semantics are broader than the method name
suggests and need explicit compatibility characterization.

### 11.4 `RichTable`

`RichTable` has the same duality at composite scale: element-local table style
options, semantic table/table-column/table-row/table-cell requirements, legacy
required-style projection, and global registration of table/cell styles.

`getTableStyleDefinitions()` is a particularly strong API-consistency finding:
its getter-like name does not communicate that it registers table style state
and then reads from the global `StyleMapper` registry. Getter side effects and
process-global return scope should be addressed in 02B, after usage evidence.

### 11.5 `ImageElement`

`ImageElement` retains image option mapping, style-name generation,
`registerStyles()`, image-style requirement getters, resource getters, and
render-time layout synchronization. `getStyleDefinitions()` currently returns
an empty array even though image-style requirements are available through
other methods.

This illustrates why `HasStyles::getStyleDefinitions()` is not a universal
semantic abstraction: different style families use different compatibility
channels.

Image anchor/wrap/position semantics are explicitly outside STYLE-API-02A and
remain under the dedicated layout backlog.

### 11.6 `DrawTextBox`

`DrawTextBox` retains explicit frame-style registration and can expose a style
DOM node. Existing API-contract coverage calls `registerStyles()` directly and
asserts the resulting graphic family and static frame registry state. That is
strong evidence that this compatibility behavior cannot be classified as
accidental merely because semantic graphic requirements now exist.

### 11.7 `ListElement`

`ListElement` participates primarily through owned paragraph/text/list
structure and recursive compatibility surfaces. Its existence reinforces the
need to separate **content-authoring API** from **style transport API**: users
should not need to understand internal requirement traversal merely to style
list content.

## 12. `OdtTemplate` facade and extension surface

The modern `setElement()` workflow already gives the library a useful public
facade: callers pass structured elements, while the template coordinates
semantic requirement collection, document-local registration/materialization,
resource preparation, insertion, and bounded compatibility adoption.

This is the correct architectural direction for application users: they should
usually style elements rather than manually orchestrate registries and writers.

At the same time, `OdtTemplate` retains protected style hooks because external
subclasses may depend on their dispatch and lifecycle. In particular, style
registration/finalization callbacks must not be made private, bypassed, or
replaced by direct service calls without override characterization.

The public facade and protected extension surface should therefore be audited
separately in 02B:

- application-facing convenience APIs can become clearer without forcing
  extension hooks to disappear;
- protected compatibility wrappers can remain thin facades even if the
  internal implementation changes.

## 13. Observed compatibility evidence

Current tests provide concrete evidence that old-looking APIs are still part
of observable behavior:

- `ApiContractP1Test` constructs styled `RichTableCell`, `RichText`, and
  `DrawTextBox` instances through public option APIs;
- it calls `DrawTextBox::registerStyles()` directly;
- it asserts that `StyleMapper::$frameStyles` is populated;
- integration tests assert the resulting ODF style-family/property separation.

The STYLE-CONTEXT-01 final audit additionally records tests around direct
`StyleWriter` behavior, lifecycle/repeated saves, current-document filtering,
semantic family migration, and subclass compatibility probes.

Therefore 02B must not infer that a method is disposable merely because the
modern `setElement()` semantic path no longer needs it as authority.

Conversely, public visibility alone is not enough to prove intended stable
library API. Low-level writer/mapping helpers without sample, documentation, or
test evidence require a separate compatibility decision.

## 14. Documentation and naming drift

The following documentation/naming issues are already material enough to guide
02B:

| Surface | Current wording/name | Current semantic reality | Audit result |
|---|---|---|---|
| `HasStyles::registerStyles()` | registration in style manager such as `StyleMapper` | compatibility mirror; document-local semantic authority exists separately | stale contract wording |
| `getStyleDefinitions()` | sounds canonical/generic | only one of several family-specific compatibility channels | too broad/ambiguous |
| `getRequiredStyles()` vs `getOwnRequiredStyles()` vs `getOwnStyleRequirements()` | near-identical vocabulary | legacy projection versus semantic requirements | high discoverability risk |
| `mapTableCellStyle()` vs `mapTableCellStyleOptions()` | nearly synonymous | overlapping but different option coverage | naming/API duplication |
| `setTextStyle()` vs `registerTextStyle()` | unclear verb distinction | both manipulate static text compatibility state | naming/state ambiguity |
| `getTableStyleDefinitions()` | getter | mutates/registers global state before returning definitions | side-effect mismatch |
| public `$frameStyles` / `$tableStyles` | direct data properties | process-global compatibility storage | bypasses API semantics |
| `StyleWriter` lower-level public helpers | public implementation utilities | mixed direct-user and internal roles | audience unclear |

A STYLE-API-02 target model should make semantic level and lifetime visible in
the API instead of relying on architecture documents to explain which path is
really authoritative.

## 15. Preliminary public API inventory by audience

### Library/application user

The strongest intentional surface is:

- `OdtTemplate::setElement()` and other document operations;
- constructors and fluent methods on `Paragraph`, `RichText`, `RichTable`,
  `RichTableCell`, image/frame elements;
- friendly style option arrays and layout/content helpers.

These should be considered the primary usability baseline for 02B.

### Custom element / subclass author

Relevant surfaces include:

- `OdtElement::toDomNode()`;
- `ownedElements()`;
- semantic `getOwnStyleRequirements()` and typed dependency/resource hooks;
- protected `OdtTemplate` facade hooks where subclassing is supported;
- retained legacy getters and `HasStyles` where compatibility requires them.

This audience needs a clear extension contract, even if old methods remain as
facades.

### Direct low-level compatibility caller

This audience can currently use:

- `StyleMapper::register...()` / getters;
- mutable static properties;
- `LegacyStyleRegistry`;
- `StyleWriter::writeAllStyles()` and lower-level writer helpers.

02B must decide which of these are intentionally supported low-level API and
which are historical public implementation details.

## 16. Characterization gaps before irreversible API decisions

The audit identifies the following focused gaps:

1. **`HasStyles` dispatch:** prove the effective current behavior of
   `registerStructuredHasStylesCompatibility()` with a normal
   `Contracts\HasStyles` element and with an external probe subclass. Resolve
   the code/documentation contradiction before redesign.
2. **Direct static property writes:** search/characterize supported use of
   `StyleMapper::$frameStyles` and `$tableStyles`, including tests/samples and
   likely external compatibility implications.
3. **Overlapping mapper functions:** characterize differences and direct uses
   of `mapTableCellStyle()` versus `mapTableCellStyleOptions()`.
4. **Text registry variants:** characterize `setTextStyle()` versus
   `registerTextStyle()` and `getTextStyles()` versus registered-text getters,
   including replacement/idempotence semantics.
5. **Secondary table-cell state:** retain the STYLE-CONTEXT-01 gap concerning
   apparently overlapping table-cell maps until targeted tests prove one is
   redundant.
6. **Getter side effects:** characterize `RichTable::getTableStyleDefinitions()`
   so a future pure getter does not silently alter legacy lifecycle behavior.
7. **Direct `StyleWriter` audience:** separate methods with real direct-call
   compatibility evidence from public methods that are only engine-internal in
   practice.
8. **Subclass override surface:** inventory overrides/probes for legacy
   `OdtElement` getter families and protected `OdtTemplate` style callbacks
   before converting them into semantic facades.
9. **Documentation/sample usage:** compare current README and samples against
   the source inventory so 02B distinguishes intentionally taught API from
   technically public API.

These are characterization tasks, not an invitation to broaden STYLE-API-02
into lifecycle or layout redesign.

## 17. Questions for STYLE-API-02B

02B should answer design questions in semantic order rather than method-by-
method cleanup:

1. What is the primary public style authoring model for normal library users:
   friendly element options, named ODT styles, or both, and how are the two
   distinguished?
2. What is the supported extension contract for a custom `OdtElement` that
   needs styles: semantic requirements only, a compatibility facade, or a
   documented combination?
3. Should `HasStyles` remain a permanent public compatibility interface, become
   a thin facade over semantic ownership, or enter a staged deprecation path?
4. Which `StyleMapper` capabilities are genuine public utilities (for example
   stateless option mapping) and which are compatibility-only global state?
5. Should process-global registry mutation remain directly public as a named
   low-level compatibility API, rather than looking like the normal style
   model?
6. Can overlapping method pairs be given one canonical semantic meaning while
   preserving old names as compatibility wrappers?
7. Which public `StyleWriter` methods are intentional low-level API and which
   should eventually become internal implementation detail?
8. Should getter methods be required to be side-effect free in the target API?
9. How should API names communicate scope and lifecycle: element-local,
   document-local semantic requirement, or process-global legacy registry?
10. Which protected hooks must remain dispatchable for subclass compatibility
    even if their implementation becomes a thin wrapper around document
    services?
11. Which APIs are technically public but have no evidence of being taught or
    used as user API?
12. Where should documentation explicitly separate **supported compatibility**
    from **recommended modern API** before any deprecation is attempted?

## 18. Recommended boundary for 02B

The evidence supports proceeding to a design/contract phase, but the target
should be deliberately narrower than “rewrite the style API”.

A productive 02B should define:

- the recommended user-facing style authoring layer;
- the recommended custom-element extension layer;
- the explicitly supported low-level compatibility layer;
- naming/lifetime rules that separate those layers;
- a migration/deprecation policy only after the characterization gaps are
  closed.

It should **not** reopen StyleContext ownership, combine document defaults,
redesign layout semantics, or remove compatibility paths simply because their
implementation is now redundant on the modern semantic path.

## 19. Conclusion

STYLE-API-02A confirms that STYLE-CONTEXT-01 solved the internal ownership
problem but intentionally left a broad historical public surface. That surface
now exposes too many architectural layers at once.

The positive result is that a complete new style architecture is not required.
The strongest public layer already exists in the structured elements and their
friendly style options, and the semantic requirement pipeline already provides
a coherent internal/extension direction.

The remaining task is API boundary design:

```text
application authoring
        ↓
structured element style options / named styles
        ↓
semantic element requirements
        ↓
document-local StyleContext

compatibility callers
        ↓
explicit legacy/static facade
```

STYLE-API-02B should make those layers intentional and understandable while
preserving evidence-backed compatibility. No production API change is approved
by this audit alone.

# STYLE-CONTEXT-01 — Final Audit

Status: **FINAL AUDIT — NO IMPLEMENTATION**  
Base: `d245b5721511a1407adee61f10672105f99b1248`  
Branch: `architecture/style-context-01-final-closeout`

## 1. Executive summary

The current `develop` baseline has already moved semantic style ownership into
the document-local `OdtDocumentContext` / `StyleContext` pipeline. The modern
`setElement()` path collects `StyleRequirement` values before native DOM
materialization and registers them in the current document context. The
semantic families covered by the current producers are paragraph, text,
graphic, table, table-column, table-row, and table-cell.

The remaining global state is not one undifferentiated style authority:

* `StyleMapper` and `LegacyStyleRegistry` remain public/process-global
  compatibility facades for direct callers and the legacy `assign()` /
  `render()` lifecycle.
* `StyleWriter::writeAllStyles()` retains broad defaults for direct legacy
  callers. `OdtTemplate` calls it with narrower, current-document filters.
* `StyleContext` still has an active paragraph/text fallback to static
  `StyleMapper` state when a semantic reference has no document-local or
  authored definition. This is a compatibility resolution path, not the
  source of semantic definitions produced by modern structured elements.
* raw graphic compatibility channels on `StyleContext` and current-document
  adoption helpers remain because public/protected legacy behavior is still
  supported.

Therefore the audit does not support removing static APIs or declaring the
whole style system unfinished. It does identify a focused remaining scope:
characterize and decide the paragraph/text fallback and the lifetime/policy
of the retained compatibility registries. A small closeout may be possible,
but implementation should follow a focused characterization slice.

## 2. Historical context

Earlier architecture documents correctly identified process-global style
registries as an ownership risk. Subsequent work changed the implementation
materially:

* STYLE-CONTEXT-01A–01E separated document-local semantic state from legacy
  registration and preserved the static facade.
* SR-05 established semantic requirements and document-local font handling.
* SR-06 migrated graphic and fill-image semantics while retaining image,
  frame, fill-image, and protected compatibility paths.
* SR-07 migrated table, table-column, table-row, and table-cell semantics.
* D5F made pre-materialization semantic/resource preparation authoritative.
* D5G narrowed current-document adoption and closed the compatibility
  lifecycle without removing public static APIs.

Older audits that describe all style ownership as unresolved global state are
historical evidence, not a description of the current `develop` tree. The
current ROADMAP and FUTURE_DEVELOPMENT documents correctly describe SR-06,
SR-07, D5F, and D5G as complete and STYLE-CONTEXT-01 as a final closeout.

## 3. Current architecture

There are three distinct paths:

```text
modern structured insertion
OdtElement
  -> StyleRequirementCollector::collectSemantic()
  -> OdtDocumentContext / StyleContext
  -> semantic materializers
  -> StructuredElementMaterializer
  -> toDomNode()
  -> bounded legacy adoption

legacy structured insertion
assign(OdtElement) -> render()
  -> legacy getters / static compatibility projections
  -> current-document adoption during save()

direct compatibility
StyleMapper::register...
  -> StyleWriter::writeAllStyles($dom, default arguments)
```

`OdtPackage` owns physical package resources. `OdtDocumentContext` owns the
current content/styles DOMs and document-local semantic dependencies. No
global current-document pointer is used.

## 4. StyleContext audit

`StyleContext` is instantiated by `OdtDocumentContext` and stores, per logical
document:

* semantic definitions and semantic references;
* document/authored/local reference resolution state;
* pending paragraph and text compatibility definitions;
* frame, image, and fill-image compatibility channels;
* the DOMs used to recognize authored common/automatic definitions.

Definitions are keyed by semantic identity (family, name, scope, and document
part). Equivalent definitions are idempotent; conflicting definitions raise a
`LogicException`. References are retained as occurrences and re-resolved when
the local definition set or document parts change. This is document-local
semantic behavior.

### Active global fallback

`StyleContext::resolveReference()` checks, in order:

1. existing authored definitions in the current `styles.xml` / `content.xml`;
2. current document-local semantic definitions;
3. `StyleMapper::getParagraphStyles()` or `getTextStyles()` for paragraph/text
   references;
4. unresolved.

For the third case, `materializationRequirements()` maps the static legacy
definition into a common `StyleRequirement` with parent `Standard`. The
fallback is active code, not dead documentation. It is limited to paragraph
and text families and exists to preserve legacy named-style references. It is
not used to discover the semantic definitions emitted by current Paragraph,
RichText, ListElement, graphic, or table producers on the normal
`setElement()` path.

`StyleContext::reset()` clears document-local definitions, references,
compatibility channels, and resolution caches. It does not clear static
`StyleMapper` / `LegacyStyleRegistry` state.

### Classification

* semantic definitions/references: **DOCUMENT SEMANTIC AUTHORITY**;
* graphic raw channels: **INTERNAL COMPATIBILITY TRANSPORT**;
* paragraph/text static fallback: **PUBLIC/INTERNAL COMPATIBILITY**, requiring
  focused characterization before any narrowing;
* reset and conflict behavior: active document-local contract.

## 5. LegacyStyleRegistry audit

`LegacyStyleRegistry` stores only paragraph styles in a private static map.
`registerParagraphStyle()` retains historical first-write-wins behavior;
`paragraphStyles()` exposes the complete process-global map to the
`StyleMapper` facade.

Observed producers include `StyleMapper::registerParagraphStyle()` and
legacy paragraph/HTML/import compatibility paths. Observed consumers include:

* `StyleMapper::getParagraphStyles()`;
* `StyleContext` paragraph-reference fallback;
* direct `StyleWriter` paragraph serialization when its default is enabled.

It is not the owner of modern document-local paragraph requirements. It is
nevertheless an active public compatibility surface through `StyleMapper`, and
its lifetime/first-write-wins policy is observable. Classification:

* **PUBLIC COMPATIBILITY FACADE** for static callers;
* **INTERNAL COMPATIBILITY TRANSPORT** for legacy fallback;
* **CHARACTERIZATION REQUIRED** before changing lifetime or fallback policy.

## 6. StyleMapper static registry matrix

| Family | Registration / read API | Producer / consumer | Modern path | Legacy path / filtering | Classification |
|---|---|---|---|---|---|
| paragraph | `registerParagraphStyle()` / `getParagraphStyles()` | Paragraph/importers; `StyleContext` fallback; `StyleWriter` | document-local semantic requirements | direct writer broad; OdtTemplate disables broad paragraph write | public compatibility facade |
| text | `registerTextStyle()`, `setTextStyle()` / `getTextStyles()` | Paragraph/RichText/importers; fallback; `StyleWriter` | document-local semantic text requirements | direct writer broad; OdtTemplate disables broad text write | public compatibility facade |
| frame | public `$frameStyles`, `addFrameStyle()`, `getFrameStyles()` | DrawTextBox/legacy registration; `StyleWriter` | document-local graphic/frame state | current draw-style references in OdtTemplate | public + protected compatibility |
| image | `registerImageStyle()` / `getRegisteredImageStyles()` | ImageElement/legacy bridge; OdtTemplate injector | document-local graphic requirement where supported | current graphic references only | public compatibility facade |
| fill-image | `registerFillImage()` / `getRegisteredFillImages()` | CircularImage/legacy bridge; injector | typed document-local dependency in modern path | current fill-image references only | public compatibility facade |
| table | `registerTableStyle()` / `getRegisteredTableStyles()` | RichTable/sample 11; `StyleWriter` | semantic table ownership/adoption | current table references allowlisted | public compatibility facade |
| table-cell | `registerTableCellStyle()` / getter | RichTableCell; `StyleWriter` | semantic table-cell ownership | current references and semantic exclusions | public compatibility facade |
| fonts | internal registration / `getRegisteredFontsXml()` | legacy/style helpers; font materializers | document-local font requirements | legacy helper residue | internal compatibility / future policy |

`StyleMapper` also contains a protected/static table-cell field separate from
the field written by `registerTableCellStyle()`. The current audit found no
basis to remove or repurpose it; it is a **possible redundant candidate**,
not a proven dead API, and needs targeted characterization before change.

The modern `OdtTemplate` path does not make unrelated static table, cell,
frame, image, or fill-image entries authoritative. D5G current-document
filters are the adoption boundary.

## 7. StyleWriter audit

`StyleWriter::writeAllStyles()` can directly serialize static text,
paragraph, frame, table-cell, and table style registries. Its default
arguments intentionally enable the broad legacy behavior. It also contains
public direct helpers such as `writeColumnStyles()` and text/font writers.

The normal `OdtTemplate` save/refresh path supplies explicit narrowing:

* paragraph and text legacy writer branches are disabled;
* frame names are limited to current document references and are enabled only
  when such references exist;
* table and table-cell names are current-document allowlists;
* semantic-owned table-cell identities are excluded from duplicate common
  materialization.

Thus there are two valid contracts, not a contradiction: direct callers get
broad compatibility defaults, while the modern facade passes document-local
evidence. The broad defaults are not a STYLE-CONTEXT-01 bug unless a future
public API decision explicitly changes them.

## 8. OdtTemplate style lifecycle

### Modern path

`setElement()` collects semantic requirements and font/fill dependencies before
native insertion, registers them in the current context, materializes them,
registers required legacy paragraph/text compatibility, copies physical
resources, inserts the subtree through protected callbacks, and performs only
bounded frame/image/fill compatibility adoption afterward.

### Legacy path

`assign()` / `render()` remains a separate lifecycle. `toDomNode()` can update
legacy image and circular-image projections. Save then adopts only current DOM
references for legacy graphic/table families. `styles.xml` placeholders remain
historically processed by `render()`.

### Direct path

External callers can register static styles and call `StyleWriter` directly;
that path remains intentionally broad and does not acquire an
`OdtDocumentContext`.

### Lifecycle persistence

New `OdtTemplate` instances and `load()` reset document-local context and
package state. `StyleMapper` and `LegacyStyleRegistry` survive across
instances and `load()`. D5G tests establish that this global state remains
observable, while current-document filtering prevents unrelated entries from
being serialized by normal OdtTemplate finalization. `refresh()` has its
historical persist/reload semantics and is not equivalent to a clean new
template.

## 9. Structured producer matrix

| Producer | Semantic ownership | Legacy getter/projection | Static side effect | Pre-materialization | Post state |
|---|---|---|---|---|---|
| Paragraph | paragraph/text requirements | required paragraph/text styles, `getStyleDefinitions()` | legacy paragraph/text registration | complete | compatibility retained |
| RichText | owned paragraph/text requirements | recursive legacy style projections | legacy paragraph/text compatibility | complete | compatibility retained |
| ListElement | owned child requirements/list structure | recursive legacy requirements | inherited paragraph/text paths | complete | no semantic discovery after render |
| ImageElement | graphic requirement support is represented by current semantic/legacy boundaries; image layout mutation remains compatibility-sensitive | image requirements, assets, style definitions | image registry on legacy path | resource/style discovery available before normal insertion | derived wrap/position state synchronized by `toDomNode()` |
| CircularImageElement | graphic requirement plus typed fill-image dependency | image/fill getters and circular style fields | legacy image/fill registries | semantic dependency and asset are pre-known | fill/style projection retained after render |
| DrawTextBox | semantic graphic requirement | frame requirements and `HasStyles` definitions | frame registry compatibility | semantic/frame data pre-known | frame compatibility retained |
| RichTable | table/column/row/cell requirements | legacy table/cell projections | table/cell registries for compatibility | complete | legacy assign/render remains separate |
| RichTableCell | table-cell requirement | `HasStyles`/style definitions | table-cell registry compatibility | complete | no modern global authority |

The matrix distinguishes semantic ownership from legacy getters. A getter
being used by a compatibility path does not make its process-global backing
store the modern semantic source of truth.

## 10. HasStyles audit

`OdtElement` implements `HasStyles`, and concrete implementations include
Paragraph, RichText, ListElement, ImageElement, DrawTextBox, RichTable, and
RichTableCell. The interface exposes `getStyleDefinitions()` and is used by
`OdtTemplate` during structured compatibility registration. Composite elements
also use it while traversing owned/nested elements.

This is an extension surface: external subclasses can implement the
interface, and tests contain probe implementations. The returned definitions
may overlap semantic requirements, but the current contract preserves this
compatibility registration and its dispatch. Classification: **PROTECTED /
SUBCLASS COMPATIBILITY** and **INTERNAL COMPATIBILITY TRANSPORT**. It is not
safe to replace it with a document-local semantic shortcut without explicit
override characterization.

## 11. Public legacy getter audit

| Getter family | Current use | Classification |
|---|---|---|
| `getRequiredStyles()`, `getOwnRequiredStyles()` | legacy collector and composite traversal | KEEP AS PUBLIC COMPATIBILITY |
| `getOwnRequiredParagraphStyles()` | paragraph/text legacy collection | INTERNAL USE REMAINS; public override-sensitive |
| `getOwnFrameStyleRequirements()` / `getFrameStyleRequirements()` | semantic/legacy frame collection and adoption | KEEP AS PUBLIC COMPATIBILITY |
| `getOwnImageStyleRequirements()` / `getImageStyleRequirements()` | graphic collection and legacy image adoption | KEEP AS PUBLIC COMPATIBILITY |
| `getOwnFillImageRequirements()` / `getFillImageRequirements()` | typed fill discovery and legacy fill adoption | KEEP AS PUBLIC COMPATIBILITY |
| `getStyleDefinitions()` | `HasStyles`, compatibility registration, tests/samples | KEEP AS PUBLIC COMPATIBILITY |
| `getImageAssets()` | `StructuredResourceCollector` and legacy resource paths | KEEP AS PUBLIC COMPATIBILITY |

The current code uses both self-owned and transitive getters. Some could
eventually become thin facades over semantic ownership, but that would alter
override-sensitive behavior and is not justified by this audit alone.

## 12. Document isolation

D5G-C/D/E characterization establishes the important distinction between a
global registry containing an entry and that entry affecting unrelated
document output. Current-document filtering covers table, table-cell, frame,
image, and fill-image adoption in normal `OdtTemplate` finalization. Semantic
definitions and resources are document-local; physical assets are package-
local.

Direct `StyleWriter` calls intentionally remain an exception because they
explicitly opt into the broad static compatibility surface. Paragraph/text
fallback and direct static font helpers have less complete two-document
evidence than the migrated graphic/table families. This is a
**characterization gap**, not evidence that isolation is broken in the modern
path.

Known retained quirks include legacy ImageElement resource omission and
CircularImage missing-placeholder resource adoption; these are documented
compatibility behavior, not hidden fixes in this audit.

## 13. Lifecycle / global-state persistence

| Event | Document-local state | Static compatibility state |
|---|---|---|
| new template | new context/package | survives in process |
| `setElement()` | semantic requirements/resources added to current document | compatibility mirrors may be populated |
| `render()` | legacy DOM lifecycle; no automatic semantic migration | legacy projections/static entries may persist |
| `save()` | current-document materialization and filters | registries remain global; unrelated entries are filtered |
| repeated save | current definitions are guarded/de-duplicated | static entries remain observable |
| `refresh()` | persist/reload resets document-local lifecycle state | static registries are not cleared |
| `load()` | context/package lifecycle reset | static registries are not cleared |
| multiple documents | contexts/resources separate | static maps shared, but normal adoption is reference-filtered |

## 14. Test coverage / characterization gaps

Existing suites cover semantic StyleContext ownership, SR-06/SR-07 producer
families, D5F/D5G lifecycle behavior, protected hooks, direct StyleWriter
compatibility, current-document adoption, repeated save/render, and
multi-document isolation for the migrated graphic/table families.

The audit identifies these focused gaps before implementation decisions:

1. direct evidence for every paragraph/text `StyleContext` legacy fallback
   branch, including conflict and first-write-wins interactions;
2. explicit two-document isolation characterization for legacy paragraph/text
   fallback and static font helpers;
3. lifecycle evidence for the unused/secondary static table-cell field in
   `StyleMapper`;
4. an explicit inventory test separating public direct `StyleWriter` defaults
   from OdtTemplate's filtered invocation;
5. a final decision on whether public legacy getters remain permanent facades
   or can be reduced only after subclass/override evidence.

Existing characterization expectations must not be rewritten merely to make
these gaps disappear.

## 15. Classification of remaining global mechanisms

| Mechanism | Classification | Current conclusion |
|---|---|---|
| `StyleContext` semantic definitions/references | DOCUMENT SEMANTIC AUTHORITY | already document-local |
| `OdtDocumentContext` font/fill dependency state | DOCUMENT SEMANTIC AUTHORITY | already document-local; resources remain package-owned |
| `LegacyStyleRegistry` paragraph map | PUBLIC COMPATIBILITY FACADE + INTERNAL TRANSPORT | retain; characterize fallback/lifetime |
| StyleMapper paragraph/text maps | PUBLIC COMPATIBILITY FACADE | direct legacy support; not modern authority |
| StyleMapper frame/image/fill/table/cell maps | PUBLIC COMPATIBILITY FACADE | current-document filtered by OdtTemplate |
| `StyleWriter` broad defaults | PUBLIC COMPATIBILITY FACADE | retain for direct callers |
| `HasStyles` | PROTECTED / SUBCLASS COMPATIBILITY | retain and preserve dispatch |
| legacy OdtElement getters | PROTECTED / PUBLIC COMPATIBILITY | retain; possible future thin facades |
| StyleContext paragraph/text fallback | INTERNAL COMPATIBILITY TRANSPORT | active; characterization prerequisite |
| secondary StyleMapper table-cell field | DEAD / REDUNDANT CANDIDATE | unproven; do not remove |
| public style option/mapping design | FUTURE API ISSUE | STYLE-API-02, not this closeout |

## 16. Contradictions with older assumptions

The main contradiction is historical scope, not current behavior:

* Older audits describe static registries as unresolved style ownership. The
  current code and D5G closeout establish them as retained compatibility
  facades with current-document filtering.
* Older SR-06 notes that semantic graphic migration was future work. Current
  SR-06/D5F/D5G code and tests establish semantic graphic ownership for the
  supported normal path while retaining legacy image/frame/fill projections.
* The current `StyleContext` paragraph/text fallback means it is not correct
  to state that no modern-facing code can ever read global state. The precise
  statement is that global state is not the producer authority for current
  semantic definitions, but remains an active fallback for unresolved legacy
  references.

No contradiction was found requiring a production change during this audit.

## 17. Required STYLE-CONTEXT-01 changes

### MUST before declaring the closeout complete

* Add focused characterization for the paragraph/text fallback and its
  document-isolation boundary.
* Decide, from that evidence, whether the fallback remains an explicit
  compatibility bridge or can be narrowed without changing public behavior.
* Keep modern semantic producers and `StyleContext` document-local.
* Preserve direct `StyleWriter` defaults, public StyleMapper APIs, protected
  hooks, and public legacy getters unless a separately approved contract says
  otherwise.

### SHOULD

* Record the secondary table-cell registry field and font helper lifetime in a
  small targeted characterization.
* Consolidate documentation of current-document adoption helpers if this can
  be done without changing routing.

### NOT PART OF STYLE-CONTEXT-01

* public style API redesign (`STYLE-API-02`);
* image resource bug, frame layout, image layout, table redesign, ratio
  semantics, or named template objects;
* assign/render redesign or refresh redesign;
* removal of static APIs, legacy getters, `LegacyStyleRegistry`, or broad
  `StyleWriter` defaults;
* HTML importer redesign or new generic registry/lifecycle abstractions.

## 18. Explicit non-goals

This audit makes no production changes, no API changes, no registry removals,
no lifecycle refactor, no sample regeneration, and no visual regression run.
It does not convert compatibility state into semantic state by relabeling it,
and it does not treat every static map as a defect merely because it is
process-global.

## 19. Proposed implementation slices

The evidence supports a small but not yet implementation-ready sequence:

```text
STYLE-CONTEXT-01-A  Final Audit                         (this document)
        ↓
STYLE-CONTEXT-01-B  Focused paragraph/text fallback and
                    static-lifetime characterization
        ↓
STYLE-CONTEXT-01-C  Small internal narrowing only if B
                    proves it preserves compatibility
        ↓
STYLE-CONTEXT-01-D  Regression closeout and final GO
```

If 01-B proves that the fallback is a required public compatibility bridge,
01-C may be documentation-only and the closeout can be correspondingly
small. No larger implementation slice is justified by the current audit.

## 20. Exit criteria

STYLE-CONTEXT-01 can close when:

1. modern structured styles use document-local `StyleContext` /
   `StyleRequirement` ownership;
2. no modern producer requires process-global state as semantic authority;
3. public static compatibility APIs remain available without determining
   unrelated document output;
4. document isolation is characterized for relevant families, including the
   paragraph/text fallback decision;
5. `LegacyStyleRegistry` has an explicit documented role and lifetime;
6. broad direct `StyleWriter` defaults are separated from modern OdtTemplate
   finalization;
7. public/protected compatibility and legacy getter dispatch remain intact;
8. no behavior correction is hidden in the closeout;
9. residual API questions are handed to STYLE-API-02 or future work;
10. focused and full regression validation is green.

## Final audit verdict

**STYLE-CONTEXT-01 REQUIRES CHARACTERIZATION**

The semantic ownership foundation is in place and the current modern path is
document-local. The remaining open decision is narrow and evidence-based: the
active paragraph/text fallback and the lifetime/policy of retained public
static compatibility state. This document authorizes no implementation and
does not mark STYLE-CONTEXT-01 complete.

# D5F — Lifecycle / Materialization Integration Change Contract

Status: **CHANGE CONTRACT — IMPLEMENTATION MAY PROCEED IN SMALL SLICES**

Evidence base:

- `docs/architecture/D5F_LIFECYCLE_MATERIALIZATION_AUDIT.md`
- `docs/architecture/D5F_B_LIFECYCLE_CHARACTERIZATION.md`
- `tests/Integration/D5FLifecycleCharacterizationTest.php`

This contract supersedes earlier D5F planning assumptions where they conflict with the evidence gathered after SR-06 and SR-07. In particular, it does not adopt a generic pre-/post-materialization discovery lifecycle merely because earlier transitional implementations used post-materialization compatibility collection.

## 1. Purpose

D5F integrates the structured-element insertion lifecycle around one authoritative pre-materialization preparation path for semantic document requirements and physical package resources.

The milestone must simplify lifecycle orchestration without changing the public meaning of structured insertion and without pulling D5G compatibility cleanup into D5F.

The governing rule is:

> Semantic requirements, typed document dependencies, and physical resources owned by a constructed element subtree must be discoverable before native DOM materialization. Native DOM materialization is rendering, not a general semantic discovery phase.

## 2. Evidence and decision

D5F-A audited the current ownership, requirement, resource, materialization, and compatibility paths.

D5F-B then characterized the observable state immediately before and after `OdtElement::toDomNode()`.

The characterization found:

- no semantic `StyleRequirement` producer whose output changes because of `toDomNode()`;
- no physical image-resource requirement that appears only after `toDomNode()`;
- no typed fill-image dependency that appears only after `toDomNode()`;
- deterministic post-render mutation in `ImageElement` legacy image options;
- post-render compatibility state in `CircularImageElement`, while its semantic graphic requirement, typed fill-image dependency, and physical resource are already complete before rendering.

Therefore D5F adopts a **single authoritative semantic pre-materialization lifecycle**.

A generic semantic post-materialization discovery phase is not part of the target architecture.

## 3. Target lifecycle

For the normal `OdtTemplate::setElement()` path, the conceptual lifecycle is:

```text
constructed OdtElement ownership subtree
        |
        +-- collect semantic style requirements
        +-- discover font-face dependencies from semantic requirements
        +-- collect typed fill-image dependencies
        +-- collect physical package resources
        |
        v
adopt requirements into the current OdtDocumentContext
prepare physical resources in the current OdtPackage
        |
        v
materialize required document declarations/styles
        |
        v
StructuredElementMaterializer::insert()
        |
        v
OdtElement::toDomNode()
        |
        v
native subtree replacement complete
```

This diagram defines responsibility and ordering, not a mandatory new class or one specific method decomposition.

## 4. Authoritative pre-materialization inputs

### 4.1 Semantic style requirements

`StyleRequirementCollector::collectSemantic()` and the element ownership tree are the authoritative structured-element source for semantic style requirements.

Semantic requirements are registered in the current document's `StyleContext` before native subtree materialization.

The currently migrated semantic families include:

- paragraph;
- text;
- graphic;
- table;
- table-column;
- table-row;
- table-cell.

D5F must not create a second semantic requirement identity model.

### 4.2 Font-face dependencies

Font-face discovery derived from semantic requirements belongs to the pre-materialization document preparation phase.

Font requirements remain document-local through `OdtDocumentContext`.

D5F must not move font ownership into `StructuredElementMaterializer` or into global mutable state.

### 4.3 Fill-image dependencies

Typed fill-image dependencies are collected from the ownership subtree before native DOM materialization and registered in `OdtDocumentContext`.

Their declarations may be materialized before the structured subtree references them.

Legacy fill-image compatibility getters are not authoritative dependency discovery.

### 4.4 Physical resources

Physical image assets are collected from the ownership subtree before DOM materialization and prepared through `OdtPackage`.

Physical resource ownership remains separate from document style/declaration ownership.

D5F must preserve the boundary:

```text
semantic document requirements -> OdtDocumentContext
physical package resources      -> OdtPackage
```

## 5. Native materialization boundary

`StructuredElementMaterializer` remains responsible for native structured insertion mechanics:

- placeholder normalization callbacks supplied by the facade;
- calling the element's native `toDomNode()` rendering path;
- replacing the structured placeholder in the relevant document part;
- preserving the existing content.xml/styles.xml insertion behavior.

It must not become:

- a style registry;
- a package resource manager;
- a semantic requirement collector;
- a template-language processor;
- a general document lifecycle context;
- a God renderer for all native element types.

`OdtElement::toDomNode()` remains element-local native rendering. D5F does not centralize individual element rendering in `OdtTemplate`.

## 6. No generic semantic post-discovery phase

D5F explicitly rejects a generic architecture of:

```text
pre-discovery
-> toDomNode()
-> semantic post-discovery
-> semantic finalization
```

for the currently characterized structured-element model.

Such a framework would encode a lifecycle requirement that the current producers do not have.

If a future element genuinely cannot know a semantic requirement before native rendering, that case requires separate evidence and an explicit architecture decision. It must not silently reintroduce a generic post-discovery pass.

## 7. ImageElement boundary

`ImageElement` is a deliberate compatibility boundary in D5F.

The current implementation derives frame placement values during `toDomNode()` and synchronizes them back into legacy `$imageOptions`, including:

- `style:wrap`;
- `style:horizontal-pos`;
- `style:horizontal-rel`;
- where applicable, `style:vertical-pos` and `style:vertical-rel`.

D5F-B proves that these values are deterministic from already available input. Their current calculation location does not establish a semantic post-materialization requirement.

However, D5F must **not** silently move, remove, or redefine this observable mutation merely to make the lifecycle look cleaner.

In D5F:

- ImageElement physical resources remain pre-discoverable;
- the existing legacy image-style behavior remains compatible;
- ImageElement is not required to gain a new semantic graphic producer;
- post-render synchronization remains characterized behavior unless a narrowly scoped implementation change can preserve it exactly.

Decisions about retiring or redesigning this legacy synchronization belong to D5G or a separately approved semantic image-style milestone.

## 8. CircularImageElement boundary

`CircularImageElement` already exposes before rendering:

- its semantic graphic requirement;
- its typed fill-image dependency;
- its physical image asset.

Its post-render assignments to legacy circular/fill-image state are compatibility behavior, not semantic discovery.

D5F must use the semantic and typed pre-materialization projections as authoritative inputs while preserving the existing compatibility behavior.

Removal or redesign of the legacy post-render state belongs to D5G.

## 9. Legacy collector boundary

`StyleRequirementCollector::collect()` remains a legacy compatibility projection in D5F.

D5F may reduce redundant lifecycle orchestration around it only where characterization proves that the normal structured insertion result and compatibility-observable behavior are unchanged.

D5F does **not** authorize wholesale removal of:

- legacy paragraph/text projections;
- frame/image/fill-image array requirements;
- compatibility registrations;
- public or protected legacy getters/hooks.

The long-term existence and scope of `collect()` is a D5G decision.

The stale documentation inside the collector that describes semantic graphic migration as unfinished may be corrected as documentation-only cleanup, but this does not itself authorize removal of the compatibility projection.

## 10. OdtTemplate orchestration

`OdtTemplate` remains the public facade and lifecycle orchestrator for structured insertion.

D5F should make the normal `setElement()` path easier to reason about by expressing coherent phases rather than interleaving semantic preparation, legacy compatibility registration, resource copying, native insertion, and post-render adoption without explicit boundaries.

Permitted implementation directions include small private methods or small stateless collaborators where they represent a real responsibility.

D5F must avoid:

- a new broad mutable materialization context;
- duplicated document state outside `OdtDocumentContext`;
- a speculative lifecycle framework;
- moving native element-specific rendering into the facade;
- broad API changes merely to shorten `setElement()`.

Extraction is justified only when it clarifies an existing responsibility and preserves compatibility boundaries.

## 11. Materialization ordering contract

The normal structured insertion path must preserve these ordering constraints:

1. semantic requirements are discovered before native subtree materialization;
2. typed dependencies required by those semantics are discovered before native subtree materialization;
3. physical resources required by the subtree are known and prepared before the inserted subtree references them in the package;
4. document-local requirements are registered in the current `OdtDocumentContext`;
5. required declarations/styles are materialized in the correct owning document part before or as required for valid insertion;
6. native subtree materialization occurs only after the preparation inputs are known;
7. legacy compatibility handling may remain around the native insertion boundary where current behavior requires it, but it is not authoritative semantic discovery.

The implementation may organize individual preparation operations differently where tests prove equivalent behavior and dependency ordering remains valid.

## 12. Lifecycle and isolation guarantees

D5F must preserve existing behavior for:

- repeated `setElement()` calls;
- multiple structured elements in one document;
- independent `OdtTemplate` instances;
- document-local style/dependency state;
- repeated `save()` where currently supported;
- content.xml and styles.xml structured placeholders;
- physical image resource insertion;
- existing protected facade callbacks used by structured insertion.

No structured requirement from document A may leak into document B through static compatibility state or reused services.

D5F must not weaken the reset boundary represented by replacement/reloading of the core document DOMs in `OdtDocumentContext`.

## 13. Compatibility contract

Backward compatibility remains mandatory during D5F.

In particular, D5F must preserve unless separately characterized and approved:

- public `setElement()` behavior;
- existing `OdtElement` rendering contracts;
- protected facade behavior relevant to external subclasses;
- `HasStyles` compatibility behavior;
- legacy style registrations still consumed by save/finalization paths;
- repeated render/save lifecycle behavior;
- content.xml/styles.xml handling;
- current ImageElement and CircularImageElement observable compatibility state.

Refactoring and behavior change must not be mixed.

Unexpected legacy behavior must remain characterized rather than opportunistically corrected.

## 14. Explicit D5G handoff

The following are outside the D5F implementation contract and remain for D5G unless separately approved:

- deciding whether `StyleRequirementCollector::collect()` can be removed or narrowed;
- retiring StyleMapper/StyleWriter compatibility registration/finalization bridges;
- changing protected extension surfaces;
- changing legacy `assign()` / `render()` behavior;
- removing legacy frame/image/fill-image carriers;
- changing save/finalization compatibility semantics;
- removing or redefining ImageElement post-render option synchronization;
- removing or redefining CircularImageElement legacy post-render state;
- broad cleanup of legacy static registries.

D5F may expose these boundaries more clearly but must not resolve them by stealth.

## 15. Non-goals

D5F does not introduce or decide:

- a general PreMaterialization/PostMaterialization framework;
- a new public lifecycle API;
- a new `MaterializationContext`;
- a new semantic ImageElement style API;
- STYLE-API-02;
- STYLE-CONTEXT-01 final closeout;
- TEMPLATE-FORMAT-PRESERVATION-01;
- TEMPLATE-AUTHORING-UX-01;
- new table layout semantics;
- new frame/image positioning semantics;
- named-template-object APIs;
- a rewrite of `OdtTemplate`;
- a rewrite of the element hierarchy.

## 16. Implementation strategy

D5F implementation should proceed in small independently reviewable slices.

Recommended sequence:

### D5F-C — lifecycle orchestration consolidation

Refactor the normal `setElement()` path so the authoritative pre-materialization preparation phases are explicit and coherent while preserving all characterized compatibility behavior.

No legacy compatibility removal in this slice.

### D5F-D — redundant post-pass narrowing

Using D5F-B characterization as the oracle, narrow only those post-materialization operations that are demonstrably redundant for the normal path.

Any post-render compatibility state that remains observable must continue to be preserved.

If safe narrowing cannot be proven without deciding D5G compatibility semantics, leave that operation in place and document the handoff instead of forcing the cleanup.

### D5F-E — lifecycle regression closeout

Verify the consolidated lifecycle across structured producer families, document parts, repeated insertion/save behavior, isolation, and public samples.

Document any compatibility residue explicitly for D5G.

The slice names are planning labels, not public APIs.

## 17. Test contract

Each implementation slice must run focused tests for the touched lifecycle behavior.

Before D5F closeout, validation should include at minimum:

- `D5FLifecycleCharacterizationTest` unchanged or intentionally extended;
- semantic StyleRequirement collection/materialization tests;
- FillImage requirement collection/materialization tests;
- StructuredResourceCollector tests;
- StructuredElementMaterializer tests;
- ImageElement and CircularImageElement compatibility tests;
- RichText/Paragraph/List structured insertion tests;
- RichTable/RichTableCell and table style semantic tests;
- relevant SR-06 and SR-07 regression suites;
- `PublicSampleSmokeTest`;
- full `composer test`;
- PHP lint for `src/` and `tests/`;
- `composer validate` where relevant;
- `git diff --check`.

Manual LibreOffice regression is required if a slice changes generated ODF structure or any rendering-relevant ordering/serialization. Pure orchestration refactoring that produces byte-/structure-equivalent relevant XML may document why a visual run is unnecessary, but automated tests do not waive visual verification when presentation could have changed.

## 18. Exit criteria

D5F is complete when all of the following are true:

1. the normal structured insertion lifecycle has one clearly identifiable authoritative pre-materialization semantic/dependency/resource preparation path;
2. no generic semantic post-materialization discovery framework has been introduced;
3. `StructuredElementMaterializer` remains focused on native subtree insertion;
4. document semantic state remains owned by `OdtDocumentContext` and physical resources by `OdtPackage`;
5. any remaining post-materialization work is explicitly classified as rendering-local or compatibility behavior rather than semantic discovery;
6. current ImageElement and CircularImageElement compatibility behavior is preserved unless a separately approved contract changes it;
7. repeated insertion/save and document isolation remain green;
8. D5G compatibility residue is documented rather than silently removed;
9. focused and full automated validation is green;
10. any rendering-relevant change has received appropriate LibreOffice regression verification.

## 19. Architectural consequence

D5F establishes a simpler invariant for future structured elements:

> A structured element should declare what the document and package need before it renders its native subtree.

`toDomNode()` may still calculate local rendering details, but those details do not become document-level semantic requirements merely because they are calculated during rendering.

This keeps element ownership, semantic document requirements, package resources, and native DOM materialization separate while avoiding a lifecycle abstraction that current evidence does not justify.

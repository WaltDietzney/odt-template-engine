# TEMPLATE-AUTHORING-01E3 — Dependency Automation Change Contract

Status: E2-to-E3 bridge implemented / E3 dependency automation not started

## 1. Purpose

TEMPLATE-AUTHORING-01E3 (E3) adds the bounded execution layer for dependency automation defined by the accepted TEMPLATE-AUTHORING-01E Change Contract.

E3 executes dependency consumers only after the same Phase-E invocation has completed the E2-C concrete preflight successfully. It delegates mutation to established template/dependency mutation semantics and does not introduce a second renderer, a second template model, or a second application-data mapping pass.

This contract records the accepted E3 semantics and the findings of E3-A — Existing Mutation Owner & Execution-Path Analysis.

## 2. Preconditions and semantic authority

E3 execution is permitted only for a Phase-E invocation whose complete E2-C preflight result is READY.

Semantically, E3 executes the already resolved and successfully preflighted dependency operations of that invocation. It MUST NOT:

- repeat MappingDefinition resolution;
- repeat ApplicationPath resolution;
- perform fallback or global application-data lookup;
- reinterpret E2-C payload compatibility;
- turn a failed or unresolved operation into an executable operation.

This requirement does not prescribe a public method signature that directly accepts `ConcretePreflightResult`. The implementation may use a bounded internal representation as long as it does not create a second semantic resolution model.

`TemplateContract` remains the sole source-derived semantic description of authored dependency consumers and controls for Phase E.

## 3. Application boundary and template dependency identity

Application naming ends at the resolution boundary.

E3 execution operates in terms of template dependency identities, template scopes, resolved values, and source-derived consumer/control evidence. Raw application record names MUST NOT leak into template execution merely because they were used to resolve a mapping.

For example, a mapping such as:

```text
jobs[]              -> experience[]
jobs[].employer     -> experience[].company
```

must execute the `experience[]` template collection using template-semantic item values such as `company`, not by handing raw `jobs` records with an `employer` field to the legacy foreach replacement path.

E3 MUST NOT become a generic DTO mapper, object traverser, ETL layer, or expression language.

## 4. Hierarchical collection and item-scope semantics

A collection control owns creation of the corresponding template item scope during execution.

For every resolved collection item, E3 preserves the hierarchy already established by E2-A/E2-B and represented by the template-side scope model. Nested collections MUST remain children of their owning collection item and MUST NOT be flattened.

A nested collection therefore executes conceptually as:

```text
experience item 0
  projects item 0
  projects item 1

experience item 1
  projects item 0
```

and never as one global `projects[]` collection assembled from all parent items.

The implementation MUST NOT introduce a second competing scope model merely for execution.

## 5. Conditions in the current template scope

Conditions execute against the currently active template scope.

A condition authored inside an `experience[]` item scope resolves its referenced dependency in that item scope, not through a global lookup. Root conditions continue to use ROOT scope.

E3 does not define new truth or comparison semantics. Existing supported condition semantics remain authoritative, including established facade dispatch where applicable.

## 6. Consumer evidence and consumption

Execution consumption is tracked semantically at consumer/control evidence level, not by marking an entire dependency globally processed.

One dependency may have multiple consumers owned by different mutation paths. Processing one consumer MUST NOT implicitly suppress another valid consumer of the same dependency.

For example, a dependency may participate both in a condition and in a visible scalar binding. The condition owner consumes the condition evidence; the visible scalar consumer remains eligible for its own owner.

Conversely, consumer evidence already handled within a structural item scope MUST NOT later be rebound by an inappropriate generic ROOT/global pass.

This semantic requirement does not authorize a permanent `ConsumerExecutionGraph`, `RuntimeContext`, registry, AST, or similar infrastructure. The implementation MUST use the smallest mechanism that satisfies the evidence-consumption guarantee.

## 7. Source evidence to Working Document location

`SourceProvenance` records source-derived identity information such as source part, region, source order, physical scope, and native owner chain. It intentionally does not contain a persistent Working-DOM node reference.

E3 may therefore perform bounded execution-target location in the current Working Document in order to apply an already known consumer/control to its corresponding Working-DOM region.

Such target location:

- MUST be driven by source-derived `TemplateContract` evidence;
- MUST respect the recorded source part and region ownership;
- MUST NOT become a second template inspection pass;
- MUST NOT rediscover dependencies, scopes, mappings, or action semantics;
- MUST NOT fall back to an unrelated global consumer search when the recorded evidence cannot be applied predictably.

The implementation must distinguish semantic inspection/resolution from the mechanical location of an already resolved execution target.

## 8. Mutation ownership

E3 is an orchestrator of existing bounded mutation semantics, not a new template renderer.

Established mutation owners remain responsible for their domains wherever their current semantics satisfy this contract.

### 8.1 Writer User Fields

Writer User Field consumers are executed through the established `UserFieldBinder` semantics. Its existing validation-before-mutation behavior and its ability to affect the relevant `content.xml` and `styles.xml` declarations remain intact.

E3 MUST NOT reimplement Writer User Field mutation.

### 8.2 Classic scalar and filter consumers

Supported classic scalar/filter consumers continue to use established `TemplateProcessor` replacement/filter semantics, subject to the source-evidence, scope, and consumption rules in this contract.

E3 does not inherit the legacy `OdtElement` special case from generic imperative scalar assignment. E2-C dependency payload rules remain authoritative.

### 8.3 Special classic consumers

Supported `nl2br`, `ul`, and `ol` consumers continue to use their established bounded template-language semantics. They must execute only for the resolved consumer evidence in the correct template scope.

### 8.4 Structural controls

The existing structural mutation algorithms are the preferred implementation basis. E3 MUST NOT create a second foreach or condition renderer merely to support automation.

However, the legacy global/flat execution behavior is not itself the Phase-E execution contract.

In particular, the existing foreach structural owner already owns the fundamental operation of locating a block, removing the authored marker/template nodes, cloning template nodes per row, and inserting the clones. E3 may introduce a bounded evidence-/region-aware path around or within that owner so that each generated clone is processed in its resolved template item scope.

Raw application records MUST NOT be passed directly into the legacy flat row-replacement behavior as a substitute for scope-aware dependency execution.

## 9. Nested structural controls

The source contract can represent collection item scopes and controls nested within those scopes. This representational ability does not automatically make every nested-control combination a supported Phase-E capability.

ROOT and nested collection-item scope execution required by the parent `TEMPLATE_AUTHORING_01E_CHANGE_CONTRACT.md` is mandatory E3 scope and MUST NOT be removed or deferred merely because individual nested-control combinations require compatibility characterization.

E3 executes individual nested-control combinations only when the applicable contract/capability/preflight path accepts them as supported and READY. Compatibility-limited combinations require characterization before they may be accepted as READY; this gate narrows concrete runtime support and does not narrow the parent contract's approved ROOT/nested collection-item scope support.

When a supported nested structure is READY, execution MUST follow the authored template/scope hierarchy. E3 MUST NOT impose a universal flat internal ordering such as "all foreach controls before all conditions".

Unsupported or compatibility-limited nested-control combinations must remain non-executable through Phase E until their runtime semantics are characterized and accepted.

## 10. Dependency execution ordering

The accepted dependency automation ordering is:

1. Writer User Fields;
2. declarative structural controls;
3. remaining Classic dependency consumers.

This ordering expresses mutation ownership and structural dependency. It is not a universal operator ordering inside the structural-control group.

Within structural controls, authored hierarchy and current template scope determine execution.

Consumer evidence consumed by a structural owner is excluded from an inappropriate later generic Classic pass. Remaining Classic consumers continue to execute through their established owners.

## 11. Compatibility and protected facade dispatch

Existing imperative APIs and the legacy `render()` lifecycle remain first-class and MUST retain their current compatibility behavior unless a separate change contract explicitly changes them.

Phase-E dependency automation preserves established protected facade dispatch where that dispatch owns observable template semantics required by the automated dependency consumer.

In particular, current protected facade dispatch for filter application and condition evaluation is compatibility-significant and MUST continue to participate when the corresponding Phase-E consumer uses those semantics.

Existing protected helpers are NOT implicitly promoted to Phase-E extension points merely because the legacy `render()` path uses them.

Therefore legacy helpers such as flat row placeholder replacement are not automatically authoritative for E3 when their behavior conflicts with the accepted scope-aware execution semantics.

If implementation requires extraction or a new internal service boundary, existing protected facade wrappers required for legacy polymorphism MUST remain intact.

## 12. Legacy render path

E3 MUST NOT implement dependency automation by loading resolved Phase-E values into the existing global assignment stacks and calling `render()`.

The legacy render path has its own established lifecycle, mutation ordering, global assignment state, and compatibility behavior. E3's accepted ordering, source-evidence targeting, hierarchical item scopes, and evidence consumption cannot be reduced to that path without changing semantics.

E3 may reuse the bounded mutation operations below that facade where compatible with this contract.

No E3 change may silently alter existing `assign()`, `assignRepeating()`, `render()`, repeated render/save behavior, or legacy template-language compatibility.

## 13. Failure boundary and atomicity

E2-C remains responsible for all predictable validation before the first Phase-E-owned mutation.

E3 performs dependency mutation after a READY preflight. Existing local validation-before-mutation guarantees of individual mutation owners remain in force.

E3 does NOT introduce invocation-wide rollback or transaction semantics. TEMPLATE-AUTHORING-01E6 owns Phase-E invocation atomicity and restoration of all document-local mutable state touched by automation.

A runtime or I/O failure in E3 does not authorize silent fallback, alternate mapping resolution, or partial semantic reinterpretation.

## 14. E3 completion scope

E3 is complete when the supported dependency families can be executed from a READY Phase-E resolution/preflight while preserving:

- template dependency identity after the application-resolution boundary;
- ROOT and nested collection item scopes;
- strict hierarchical collection lineage;
- current-scope condition semantics;
- source-derived consumer/control targeting;
- consumer-evidence ownership and non-duplication;
- Writer User Field ownership;
- supported Classic scalar/filter/special consumer semantics;
- established compatibility-significant facade dispatch;
- legacy imperative/render behavior.

E3 completion must include characterization and integration coverage for the supported structural combinations rather than assuming nested-control support from the contract model alone.

## 15. Explicit non-goals

E3 does not add or implement:

- a second template/dependency/scope model;
- a renderer-neutral AST;
- a general execution-plan language;
- a generic RuntimeContext or consumer graph unless later evidence proves one necessary;
- DTO/object traversal;
- arbitrary application value to `OdtElement` conversion;
- new template syntax;
- new condition semantics;
- new filter semantics;
- universal nested-control support beyond the parent contract's required nested collection-item scope semantics;
- native object actions;
- Section `replace-content` automation;
- Bookmark `replace-text` automation;
- Frame `replace-image` automation;
- metadata/document-capability automation;
- image replacement option semantics;
- page-layout or style authoring;
- save/finalization/export;
- invocation-wide rollback;
- repeated Phase-E automation/render semantics.

Native Object Action automation remains E4. Document Capability automation remains E5. Invocation atomicity and integration closure remain E6.

## 16. Implementation gate

Before E3 implementation begins, the implementation slice must identify the smallest compatibility-safe bridge between:

- READY dependency resolution/preflight data;
- `TemplateContract` consumer/control evidence;
- Working-DOM target location;
- established mutation owners.

The implementation must prefer small bounded extensions or facade bridges over duplicated renderer logic or new mutable runtime architecture.

The E2-to-E3 consistency review established that current E2 results preserve the concrete information needed for each independently resolved application path, including nested `ApplicationDataResolution` item lineage, but do not yet expose a canonical template-scope-oriented projection that joins sibling dependency resolutions into executable collection-item scopes.

Therefore a bounded E2-to-E3 bridge is REQUIRED before dependency mutation implementation begins. That bridge MUST:

- derive only from the existing `TemplateContract` scope hierarchy, `DependencyMappingResolution` values, and their existing `ApplicationDataResolution` item lineage;
- be immutable and non-mutating;
- preserve ROOT and nested collection-item hierarchy without flattening;
- join independently resolved dependency values by their already resolved collection lineage;
- expose template dependency identities/values needed by E3 without leaking raw application record names into execution;
- perform no new `ApplicationPath` resolution and no traversal of raw application data;
- introduce no fallback, same-name search, mapping reinterpretation, or payload reinterpretation;
- remain a projection of accepted E2 semantics rather than a second dependency/scope model or execution AST.

The bridge belongs before the first E3-owned document mutation. Its concrete class/API name is intentionally not fixed by this contract.

The bridge must include characterization coverage for multi-level nested collection lineage and scoped same-name mappings. In particular, the current multi-level scoped same-name construction must be verified for accepted nested mapping combinations rather than inferred from implementation shape alone.

Any inability to derive an unambiguous template-scope projection from already resolved E2 data is a pre-execution error/design finding. It MUST NOT be repaired inside E3 by re-traversing application data.

Any proposed expansion of supported nested-control combinations requires characterization evidence before it is treated as Phase-E READY behavior.

### Bridge slice implementation status

The required E2-to-E3 dependency scope bridge is implemented by the internal `DependencyScopeProjector`, which accepts only a `TemplateContract` and a READY `ConcretePreflightResult`. Its immutable `DependencyScopeProjection` and `ProjectedDependencyValue` types are read-only views over the resolved template scopes and values; they are not a general mapping language, execution plan, or new scope model. The projection retains template dependency identity, mapping provenance, concrete local value state, and nested item indices while omitting raw application source paths and collection record arrays from the E3-facing values.

This closes the bridge prerequisite after characterization of ROOT, sibling values, explicit overrides, nested scoped-same-name mappings through three collection levels, empty collections, and inconsistent lineage. It does not implement E3 mutation or change the E3 scope defined above.

## 17. Parent-contract review closure

The E3 contract has been reviewed against the accepted parent `TEMPLATE_AUTHORING_01E_CHANGE_CONTRACT.md`.

The review found no remaining contradiction or Phase-E scope expansion after clarifying Section 9. In particular:

- `TemplateContract` remains the sole source-derived semantic authority;
- Working-DOM location remains execution targeting rather than second inspection;
- dependency execution ordering matches the parent contract;
- ROOT and nested collection-item scope support remains mandatory E3 scope;
- compatibility characterization gates only individual nested-control combinations, not the parent-approved nested scope model;
- E3 does not implement the E6 invocation-wide rollback guarantee early, while E6 remains responsible for completing the parent contract's atomicity requirement;
- imperative APIs, legacy `render()` behavior, E4 native actions, E5 document capabilities, and E6 integration remain outside E3's implementation boundary.

Parent-contract review result: **GREEN**.

## 18. E2-to-E3 consistency review closure

E2-A, E2-B, and E2-C have been reviewed against the accepted E3 execution semantics.

The review found:

- E2-A preserves hierarchical item lineage for each resolved application path through `ApplicationDataResolution`;
- E2-B preserves target dependency identity, application source, mapping provenance, and the concrete application-data resolution for each dependency;
- E2-C preserves those dependency resolutions in its machine-readable operations/result and remains valid for its original complete-preflight responsibility;
- the current representation resolves dependency paths independently and therefore does not itself provide the template-scope-oriented sibling join required for direct E3 collection execution;
- constructing that join during mutation would move resolution semantics into E3 and violate the accepted execution boundary.

No defect is assigned to the completed E2-A/B/C slices for their original contracts. The missing scope-oriented projection is an integration requirement discovered at the E2-to-E3 boundary.

Review result:

- E2-A: **GREEN**;
- E2-B: **GREEN for accepted E2-B scope**;
- E2-C: **GREEN for accepted E2-C scope**;
- E2-to-E3 bridge prerequisite: **GREEN / closed** after implementation and characterization.
- E3 dependency automation: **not started**; this bridge does not authorize or implement E3 mutation.

---

This contract is subordinate to the accepted `TEMPLATE_AUTHORING_01E_CHANGE_CONTRACT.md`. Where this document narrows an E3 implementation choice, the narrowing applies only to E3 dependency automation and does not broaden Phase E beyond the parent contract.

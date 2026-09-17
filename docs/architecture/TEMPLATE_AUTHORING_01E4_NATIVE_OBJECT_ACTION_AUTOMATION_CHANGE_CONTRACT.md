# TEMPLATE-AUTHORING-01E4 — Native Object Action Automation Change Contract

Status: Accepted semantics / implementation not started

## 1. Purpose

TEMPLATE-AUTHORING-01E4 implements the mutating Phase-E automation path for the three native-object actions already approved by the parent `TEMPLATE-AUTHORING-01E` contract:

- Section `replace-content`;
- Bookmark `replace-text`;
- Frame `replace-image`.

E4 consumes the complete READY result established by E2-C and delegates mutation to the existing typed native-object semantics or to the smallest compatibility-preserving typed owner extracted from them.

E4 is an execution slice. It does not introduce a second mapping pass, a second native-object model, a general action language, or new template-authoring syntax.

## 2. Preconditions and semantic authorities

E4 executes only explicit Native Object Action mappings that have already passed complete concrete Phase-E preflight.

The authorities remain:

1. `TemplateContract` for source-authored native-object identity and provenance;
2. the engine capability catalog/projection for supported native actions;
3. Mapping Resolution and E2-C Concrete Preflight for application-source resolution, payload compatibility, target kind, uniqueness, support, and applicability;
4. the current Working Document and established typed target/services for mutation.

E4 MUST NOT:

- traverse raw application data;
- invoke `ApplicationDataResolver` or reinterpret `ApplicationPath`;
- rerun Mapping Resolution or scoped same-name resolution;
- infer an action from the PHP payload type;
- discover a new native target because the resolved target cannot be found;
- re-inspect the Working DOM as a second semantic template inspection;
- silently downgrade a failed invariant to a global name search or best-effort mutation.

A Working-DOM lookup of the already resolved native target is permitted and required where the existing typed target/service performs that lookup. Such lookup is execution localization, not a second semantic inspection.

## 3. Common E4 execution rule

The common execution boundary is:

```text
E2-C READY
→ Native Object Action Resolution
→ bounded E4 native-action executor/orchestrator
→ established typed mutation owner
→ Working Document
```

Each resolved native action is processed by exactly one effective mutating owner.

E4 MUST NOT mutate a native object merely because `TemplateContract` discovered it. Mutation requires an explicit resolved action.

No E4 action performs save, finalization, export, metadata mutation, dependency automation, or invocation-wide rollback.

## 4. Section `replace-content`

### 4.1 Accepted semantics

The approved action is:

```text
Target:  native Section
Action:  replace-content
Payload: OdtElement
```

E4 MUST reuse the established `SectionTarget::replaceContent(OdtElement)` semantics.

`replace-content` means that the content of the existing named Section is replaced while the Section remains the addressed native template object. E4 MUST NOT reinterpret this action as remove-and-recreate, clone, instantiate, or instantiate-many.

### 4.2 Payload boundary

E4 accepts the already-preflighted `OdtElement` payload only.

It MUST NOT automatically convert:

- strings to `Paragraph` or `RichText`;
- arrays or records to `OdtElement`;
- DTOs/objects to structured content;
- HTML to structured Section content.

Any future application-level structured-content authoring semantics require a separate accepted contract.

### 4.3 Explicit non-actions

The existing imperative Section capabilities `clone`, `instantiate`, and `instantiateMany` remain available independently but are not Phase-E E4 mapping actions.

## 5. Bookmark `replace-text`

### 5.1 Accepted semantics

The approved action is:

```text
Target:  native Bookmark or Bookmark range
Action:  replace-text
Payload: string
```

E4 MUST reuse the established `BookmarkTarget::replaceText(string)` semantics, including the existing bounded marker/range behavior of that typed target.

### 5.2 Payload boundary

`replace-text` is plain string replacement. E4 MUST NOT interpret the payload as HTML, RichText, Paragraph, or another `OdtElement`.

Structured/RichText insertion at a Bookmark remains outside E4 and requires separate semantics if introduced later.

## 6. Frame `replace-image`

### 6.1 Accepted authority rule

Phase-E Frame replacement follows:

> Preserve by default, override explicitly.

The LibreOffice/ODT template owns existing Frame geometry and other Frame properties unless the explicit Phase-E replacement instruction overrides them according to this contract.

The existing imperative `OdtTemplate::replaceImageByName()` remains a compatibility API. Its legacy default dimensions of `5cm × 3cm` MUST NOT become Phase-E defaults.

### 6.2 Accepted dimension semantics

E4 uses the following complete bounded dimension rule:

| Explicit dimensional options | Phase-E result |
| --- | --- |
| none | preserve the existing Frame width and height |
| `width` only | set width and derive height proportionally from the intrinsic dimensions of the replacement image |
| `height` only | set height and derive width proportionally from the intrinsic dimensions of the replacement image |
| `width` and `height` | set both values exactly as explicitly supplied |

This can be summarized as:

```text
0 dimensions → template geometry is authoritative
1 dimension  → replacement-image aspect ratio supplies the second dimension
2 dimensions → application instruction is fully authoritative
```

There is no `keepRatio` option in E4. The one-dimension form already expresses proportional scaling. No equivalent boolean aspect-ratio switch is introduced by this slice.

### 6.3 Intrinsic aspect ratio

For a one-dimension override, the missing dimension is calculated from the intrinsic pixel dimensions of the replacement image, not from the old Frame ratio.

For example, a replacement image with intrinsic ratio `3:2` and explicit `width = 6cm` yields `height = 4cm`.

Supplying both width and height is an explicit instruction and MAY change/distort the intrinsic image ratio. E4 MUST NOT silently apply contain, cover, crop, fit, or another image-adaptation policy.

### 6.4 Length values and units

E4 does not introduce a universal ODF length type or parser.

The bounded length values accepted by E2-C remain authoritative for this slice: positive decimal values with `cm`, `mm`, `in`, `pt`, `pc`, or `px`.

When E4 derives the second dimension from a single explicit dimension, it MUST preserve the supplied unit and apply the intrinsic ratio to the numeric component. Cross-unit conversion is unnecessary and MUST NOT be introduced merely for this calculation.

Any reusable ODF-length abstraction requires separate architectural justification and MUST NOT be pulled into E4 speculatively.

### 6.5 Preserved Frame state

`replace-image` changes the embedded image resource/reference and only those dimensional properties required by section 6.2.

All unrelated existing Frame/template state MUST be preserved, including where present and not otherwise required by established replacement semantics:

- `draw:name`;
- anchor semantics;
- position;
- style reference;
- wrap-related state;
- z-index;
- unrelated Frame attributes;
- surrounding document structure.

With no dimensional override, existing `svg:width` and `svg:height` MUST remain unchanged.

### 6.6 Image resource ownership

Frame replacement is one semantic action with both package and DOM effects:

1. make the replacement image available as an ODT package resource;
2. update the addressed Frame's direct `draw:image` reference to that resource;
3. apply only the dimension changes defined by section 6.2.

E4 MUST reuse or extract the established named-frame/package replacement behavior rather than implement a competing image insertion path.

A small typed Frame image-replacement owner/service is the preferred architecture if extraction is required. The concrete class/API name is not prescribed by this contract.

### 6.7 Imperative compatibility facade

The existing public `OdtTemplate::replaceImageByName(string $name, string $imagePath, array $options = [])` behavior MUST remain backward compatible, including its legacy `5cm × 3cm` defaults when dimensions are omitted.

If image replacement is extracted behind a shared typed owner:

- the imperative facade may normalize/apply its legacy defaults before delegating;
- Phase E delegates with the E4 preserve/proportional/explicit semantics;
- shared ODF/package mutation does not imply identical public default semantics.

Existing compatibility-sensitive protected methods MUST be preserved or wrapped where required for subclass behavior. Refactoring MUST NOT silently redefine the imperative API.

## 7. Native target identity and uniqueness

Phase E requires the target to have been uniquely and correctly resolved/preflighted before E4 mutation.

E4 mutates exactly the resolved native object. It MUST NOT inherit legacy duplicate-name behavior that intentionally updates multiple matching Frames or other targets.

If the Working Document no longer permits deterministic localization of the resolved target, E4 fails explicitly. It MUST NOT fall back to mutating every object with the same visible name.

This stricter Phase-E execution rule does not change existing imperative compatibility behavior.

## 8. Multiple native actions and interference

E4 defines no invented universal order such as:

```text
Sections → Bookmarks → Frames
```

or any other action-family priority.

The parent contract permits ordering only where an established target/DOM dependency requires it.

### 8.1 Predictable destructive interference

Multiple individually valid native actions can conflict structurally. For example, a Section `replace-content` action may remove a Bookmark or Frame that is also the target of another action in the same E4 invocation.

E4 MUST NOT repair such conflicts by choosing an arbitrary execution order.

Predictable destructive target interference among selected E4 actions MUST be detected before the first E4 mutation and rejected deterministically.

The validation may use only already available source-derived ownership/provenance, resolved native target identities, selected actions, and other established preflight information. It MUST NOT become a second template inspection or general mutation planner.

If an interference cannot be predicted from the accepted semantic evidence and a target later cannot be localized at runtime, the runtime failure propagates. E4 MUST NOT re-resolve or search for a replacement target.

### 8.2 No general action dependency graph

The interference check does not authorize a general execution graph, action DSL, command scheduler, or universal ordering framework. Implement the smallest bounded validation required for the three approved E4 actions.

## 9. E4 executor/orchestrator boundary

A small E4 native-action executor/orchestrator is appropriate.

Its responsibility is limited to:

- accept the same successfully preflighted Phase-E invocation evidence used for E4;
- select the resolved READY native actions;
- validate bounded cross-action interference before mutation;
- localize the already identified Working targets;
- dispatch each action to its established typed owner;
- propagate failures.

It MUST NOT:

- resolve application data;
- reinterpret mappings;
- inspect template syntax;
- implement Section/Bookmark/Frame mutation algorithms itself where an established owner exists;
- save or finalize;
- execute metadata capabilities;
- execute dependency automation;
- provide invocation-wide rollback.

The concrete public integration point is not prescribed. Avoid a speculative general Phase-E dispatcher before E5/E6 establish the complete invocation boundary.

## 10. Mutation and failure boundary

E4 mutates the Working Document and, for Frame replacement, may mutate package-local image resources.

E4 does not implement the outer Phase-E atomic transaction. E6 remains responsible for restoring every document-local/package-local state touched by the complete automation invocation.

Existing local validation-before-mutation or local rollback behavior inside established owners remains valid and MUST NOT be weakened.

A runtime or I/O failure after READY:

- propagates as a failure;
- does not trigger Mapping Resolution again;
- does not search for another native target;
- does not silently skip the action;
- does not invent fallback dimensions or payload conversion.

E6 will later compose E3/E4/E5 under the invocation-wide rollback guarantee.

## 11. Lifecycle and compatibility

E4 remains optional and independent of the normal imperative lifecycle.

Conceptually:

```text
load
→ optional imperative mutations
→ optional Phase-E dependency/native automation
→ optional imperative mutations
→ save
```

E4 MUST NOT call `render()`, `save()`, `refresh()`, or finalization implicitly.

The Phase-E 1.0 guarantee remains one successful automation invocation per Working Document lifecycle unless a later contract extends it.

Existing imperative APIs remain first-class and independently usable.

## 12. Explicit non-goals

E4 does not implement or define:

- Section clone/instantiate/instantiateMany mapping actions;
- native named-table population;
- structured/RichText Bookmark insertion;
- automatic array/object/DTO-to-`OdtElement` conversion;
- HTML interpretation for Section or Bookmark payloads;
- `keepRatio` or another aspect-ratio boolean;
- contain/cover/crop/fit semantics;
- general image cropping or focal-point semantics;
- a universal ODF length API;
- general Frame/layout/style authoring;
- a universal native-action DSL;
- arbitrary PHP methods as actions;
- automatic actions for every discovered native object;
- metadata/document capability automation (E5);
- invocation-wide atomicity/rollback (E6);
- save/finalization/export;
- repeated automation semantics;
- Authoring UX or LibreOffice extension behavior.

## 13. Required characterization and tests

E4 implementation MUST characterize at least the following.

### 13.1 Section

1. explicit READY Section `replace-content` with `OdtElement`;
2. Section identity remains present after content replacement;
3. structured content uses the existing Section mutation/materialization semantics;
4. no clone/instantiate behavior is triggered;
5. incompatible non-`OdtElement` payload remains a preflight failure, not an E4 conversion.

### 13.2 Bookmark

6. explicit READY Bookmark `replace-text` with string;
7. existing bookmark/range marker semantics remain intact according to `BookmarkTarget` behavior;
8. no HTML/RichText/OdtElement interpretation;
9. incompatible payload remains a preflight failure.

### 13.3 Frame resource and preservation

10. explicit READY Frame `replace-image` replaces the image resource/reference;
11. no dimension options preserve authored width and height exactly;
12. width-only derives height from replacement-image intrinsic ratio;
13. height-only derives width from replacement-image intrinsic ratio;
14. width+height uses both explicit dimensions exactly;
15. proportional derivation works with the bounded accepted units without cross-unit conversion;
16. unrelated Frame attributes/style/anchor/position/wrap/z-index remain unchanged;
17. the addressed direct `draw:image` is replaced and unrelated images/Frames are unchanged;
18. invalid/unreadable/non-interpretable image sources remain rejected before E4 mutation as required by E2-C.

### 13.4 Compatibility

19. imperative `replaceImageByName()` with no options still exhibits its legacy `5cm × 3cm` behavior;
20. imperative explicit width/height behavior remains characterized;
21. existing duplicate-frame-name imperative compatibility behavior remains unchanged;
22. Phase-E does not mutate multiple duplicate-name targets;
23. existing public/protected compatibility surfaces remain intact.

### 13.5 Interference and boundaries

24. Section replacement that predictably destroys another selected Bookmark target is rejected before E4 mutation;
25. Section replacement that predictably destroys another selected Frame target is rejected before E4 mutation;
26. independent native actions can execute without an invented family-wide priority;
27. E4 accepts no raw application data and performs no Mapping/ApplicationPath resolution;
28. E4 does not execute E3 dependencies, E5 metadata, E6 rollback, save, or finalization;
29. pre-execution interference failure leaves the Working Document and package resources unchanged.

Tests SHOULD use actual TemplateContract/Mapping/E2-C results where practical rather than constructing unrealistic executor-only state that bypasses the accepted Phase-E boundary.

## 14. Verification and preflight

After implementation, run at minimum:

- focused E4 native-action tests;
- existing SectionTarget/Section mutation tests;
- existing BookmarkTarget tests;
- existing FrameTarget/image replacement tests;
- E1 native capability projection tests;
- E2 native Mapping Resolution and Concrete Preflight tests;
- E3 dependency automation regressions;
- relevant package/image-resource tests;
- `PublicSampleSmokeTest`;
- full `composer test`;
- PHP lint for `src/` and `tests/`;
- `composer validate --no-check-publish`;
- `git diff --check`;
- strict documentation build when available.

Because E4 changes rendered ODT/native-object behavior, a manual LibreOffice regression is required before final E4 closure. The regression MUST cover at least Section replacement, Bookmark text replacement, and Frame image replacement with preservation/proportional sizing behavior.

Sample output files used for local regression MUST NOT be committed unless explicitly part of a separately approved sample-output change.

## 15. Completion criteria

E4 is complete only when:

- all three approved native actions execute from READY Phase-E resolutions;
- Section and Bookmark reuse their established typed semantics;
- Frame `replace-image` implements the accepted 0/1/2-dimension rule;
- no Phase-E path inherits the imperative `5cm × 3cm` defaults;
- imperative image replacement compatibility remains unchanged;
- unrelated Frame/template properties are preserved;
- target uniqueness remains strict in Phase E;
- predictable destructive cross-action interference is rejected before mutation;
- no second mapping/native/template model or general action planner was introduced;
- focused and full automated preflight are green;
- manual LibreOffice regression is green;
- implementation diff review finds no remaining contract violation;
- E4 completion documentation records the final implementation and compatibility state.

## 16. Relationship to later slices

E4 closes only Native Object Action Automation.

E5 remains responsible for bounded metadata/document capability automation.

E6 remains responsible for the outer Phase-E atomic invocation, including rollback coverage for all document/package-local mutable state touched by E3, E4, and E5, lifecycle/integration closure, and final Phase-E compatibility verification.

E4 MUST therefore expose enough bounded execution behavior for E6 to compose it later without turning E4 itself into the global Phase-E lifecycle owner.

---

Accepted E4 semantic decisions A–H:

- **A:** execute only explicit READY Native Object Action resolutions;
- **B:** Section `replace-content` reuses `OdtElement` replacement semantics with no automatic payload conversion;
- **C:** Bookmark `replace-text` reuses plain-string semantics with no structured insertion;
- **D:** Frame geometry uses the 0/1/2-dimension rule: template / proportional / fully explicit;
- **E:** one-dimensional replacement derives the other dimension from the replacement image's intrinsic aspect ratio; no `keepRatio`;
- **F:** Frame replacement preserves all unrelated template properties and never inherits legacy `5cm × 3cm` defaults as Phase-E defaults;
- **G:** shared typed Frame/package mutation may be extracted while `replaceImageByName()` remains a backward-compatible legacy facade;
- **H:** E4 invents no global action order; predictable destructive native-action interference is rejected before mutation rather than repaired through ordering.

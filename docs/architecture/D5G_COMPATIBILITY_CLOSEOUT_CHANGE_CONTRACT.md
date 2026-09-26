# D5G — Compatibility Closeout Change Contract

Status: **CHANGE CONTRACT — IMPLEMENTATION MAY PROCEED IN SMALL SLICES**

Base evidence:

- `D5G_COMPATIBILITY_SURFACE_AUDIT.md`
- `D5G_B_LEGACY_STRUCTURED_LIFECYCLE_CHARACTERIZATION.md`
- `D5F_LIFECYCLE_MATERIALIZATION_CHANGE_CONTRACT.md`
- `D5F_E_LIFECYCLE_REGRESSION_CLOSEOUT.md`

D5G closes transitional compatibility infrastructure around the D5F lifecycle.
It does not redefine the structured-element semantic model established by D5F.

## 1. Governing rule

The modern structured-element lifecycle remains semantically authoritative:

```text
constructed OdtElement subtree
    -> semantic requirements and typed dependencies
    -> document/package preparation
    -> semantic materialization
    -> StructuredElementMaterializer::insert()
    -> OdtElement::toDomNode()
    -> bounded compatibility adoption where still required
```

The public `assign()` / `render()` structured-value path is a compatibility
lifecycle. Its continued support does not make its static registries, document-
wide switches, or post-render projections semantic authorities.

D5G therefore follows this rule:

> Preserve observable public and protected compatibility while narrowing
> internal compatibility mechanisms to the smallest evidence-supported scope.

Compatibility is not synonymous with preserving every incidental internal
mechanism or every accidental serialization difference.

## 2. Evidence fixed by D5G-A and D5G-B

The following observations are accepted as the implementation baseline:

1. `setElement()` and `assign(OdtElement)` / `render()` currently use distinct
   lifecycle models.
2. Legacy `render()` processes `content.xml` and `styles.xml` independently.
3. The same assigned element can therefore receive more than one `toDomNode()`
   call in one render operation.
4. A missing placeholder currently still activates
   `legacyStructuredValuesMaterialized` because the switch is set before
   replacement succeeds.
5. The legacy path does not execute D5F semantic pre-materialization and does
   not automatically adopt semantic requirements into document-local
   `StyleContext`.
6. `legacyStructuredValuesMaterialized` is a coarse document-wide finalization
   switch coupling image, frame, table, and table-cell compatibility decisions.
7. Static StyleMapper/legacy registries remain process-global and observable,
   while current-document filtering prevents the tested unrelated state from
   leaking into another document's serialized output.
8. Legacy `ImageElement` synchronizes deterministic render-derived
   wrap/position options and stable style identity, but the characterized
   legacy path does not copy its physical image asset into the package.
9. `CircularImageElement` exposes semantic graphic/fill/resource requirements
   before rendering but populates legacy circular/fill state during rendering;
   its legacy bridge copies the physical resource.
10. DrawTextBox semantic graphic/frame information is available before
    rendering, while legacy frame finalization remains observable.
11. Legacy RichTable output is not produced through the authoritative SR-07
    semantic ownership/materialization path.
12. Repeated legacy render/save keeps native content stable in the characterized
    producers. Paragraph, RichText, ListElement, CircularImageElement,
    DrawTextBox, and RichTable can change serialized `styles.xml`; ImageElement
    is the characterized stable exception after deterministic synchronization.
13. `load()` resets document-local legacy lifecycle tracking but does not clear
    process-global StyleMapper registries.
14. `refresh()` has distinct historical semantics and reloads the template
    boundary after its own finalization path.
15. Mixed `setElement()` plus legacy `assign(OdtElement)` usage is observable
    and must remain supported unless separately changed by an explicit future
    contract.

These observations are characterization evidence. Where this contract does not
explicitly authorize a behavior change, the observed behavior remains protected.

## 3. Compatibility categories

D5G classifies remaining compatibility surfaces into four categories.

### 3.1 Public compatibility surface

Examples include:

- `assign()` / `render()` behavior for `OdtElement` values;
- public legacy requirement/style/resource getters on `OdtElement`;
- public static StyleMapper APIs where externally callable;
- existing public element APIs and lifecycle methods.

D5G must not remove, rename, narrow visibility, or silently redefine these
surfaces. Public legacy getters may become thin facades only when override and
caller behavior remains intact.

### 3.2 Protected polymorphic compatibility surface

Protected OdtTemplate methods that existing subclasses can override are treated
as compatibility boundaries. This includes replacement, rendering, and
finalization hooks already covered by compatibility tests.

D5G must preserve dynamic dispatch. Internal implementation may move behind a
protected facade, but the facade must remain effective unless a separately
approved breaking-change policy says otherwise.

### 3.3 Internal transitional compatibility infrastructure

Examples include:

- `legacyStructuredValuesMaterialized`;
- `legacyFrameStylesMaterialized`;
- duplicated legacy collector passes;
- save/finalization routing booleans;
- internal adoption from process-global registries;
- compatibility-only post-materialization registration.

These mechanisms are eligible for narrowing or replacement when tests prove
that public/protected compatibility and required serialized output remain
intact.

### 3.4 Process-global compatibility state

Static registries may remain observable for backward compatibility, but they
must not become the semantic source of truth for a document. Current-document
selection/adoption remains required wherever global state is consulted.

D5G may reduce internal reliance on process-global registries. D5G does not
require removal of public static APIs.

## 4. `assign(OdtElement)` / `render()` contract

D5G preserves structured values supplied through the existing public
`assign()` / `render()` API as a compatibility feature.

D5G does **not** authorize simply redirecting this path to `setElement()`.
D5G-B proved observable differences in styles, resources, table ownership, and
lifecycle state. Delegation would therefore be a behavior change, not a pure
refactor.

The legacy path may internally reuse smaller semantic/document-local helpers
only where characterization proves equivalence for the affected behavior.

The ability to target placeholders in both `content.xml` and `styles.xml` is
protected during D5G because it is current observable behavior and existing
compatibility tests exercise styles-part replacement.

## 5. Placeholder success and lifecycle state

The current Boolean name implies successful materialization but the observed
mechanism records entry into the structured legacy path. D5G must not infer
semantic meaning from that name.

A missing placeholder activating the document-wide compatibility mode is
considered an implementation artifact eligible for narrowing **only if** tests
prove that changing it does not alter required public/protected compatibility
or necessary finalization for other successfully rendered structured values.

If lifecycle tracking is narrowed, it should describe actual compatibility
capabilities needed by the current document rather than introduce a new generic
lifecycle framework.

D5G should prefer small booleans/sets or derived current-document evidence over
an abstract mutable CompatibilityContext.

## 6. Save/finalization contract

`save()` is where document-local semantic state and legacy compatibility state
currently meet.

D5G may narrow finalization routing so that legacy behavior is enabled only for
families actually required by the current document. In particular, one legacy
structured value should not force unrelated compatibility families when that
coupling is not required by observed behavior.

Any narrowing must preserve:

- referenced legacy image style finalization where required;
- referenced legacy frame style finalization where required;
- legacy fill-image declarations/resources where required;
- table/table-cell compatibility needed by legacy RichTable output;
- document-local filtering that prevents unrelated global state from leaking;
- repeated save behavior;
- mixed semantic/legacy documents.

D5G does not authorize a wholesale rewrite of `StyleWriter::writeAllStyles()`.
Its compatibility-routing parameters may be reduced only in small,
characterized slices.

## 7. Repeated render and repeated save

D5G preserves functional repeatability: repeated lifecycle calls must not lose
already materialized content, duplicate physical resources incorrectly, change
stable style identities unexpectedly, or cause cross-document leakage.

Exact byte/XML equality of `styles.xml` across repeated legacy render is **not**
made a universal compatibility requirement by this contract. D5G-B showed
producer-specific behavior: several producer families change serialized styles
while ImageElement is stable.

D5G may remove redundant style re-registration/finalization when the resulting
ODF semantics and public/protected behavior are preserved. Any such slice must
compare the affected package parts and, if rendering-relevant XML changes,
receive LibreOffice regression review.

## 8. ImageElement contract

D5G preserves:

- public ImageElement API;
- stable image style identity;
- deterministic synchronization of render-derived wrap/position options;
- legacy getter behavior required by callers/tests;
- current protected compatibility dispatch.

The characterized legacy omission of the physical image asset is **not approved
as desired semantics**, but D5G also does not silently fix it as part of a
compatibility refactor. It must be documented as a separate behavior issue or
handled only in an explicitly approved behavior-change slice with dedicated
package and LibreOffice regression tests.

D5G must not introduce a new semantic ImageElement graphic API merely to close
compatibility infrastructure.

## 9. CircularImageElement contract

Semantic graphic requirements, typed fill-image dependencies, and physical
image assets remain pre-materialization facts for the modern lifecycle.

Legacy `$fillImageName`, `$circularStyleName`, and `$circularStyleOptions`
remain compatibility projections. D5G may narrow internal consumers of these
projections but must preserve observable legacy getter behavior where currently
public/override-sensitive.

The legacy resource-copy bridge may be replaced only if equivalent package
resource preparation is proven for the legacy API path without changing its
other characterized behavior.

## 10. DrawTextBox / frame contract

Semantic graphic/frame requirements remain pre-materialization facts.

Legacy frame registry state and post/save adoption are compatibility mechanisms.
D5G may narrow them to current-document referenced frame styles. Protected and
public frame compatibility surfaces remain intact.

Removal of the frame compatibility path requires evidence that no supported
legacy assign/render producer or subclass override depends on it; absent that
evidence, retain the facade and narrow only its internals.

## 11. RichTable contract

SR-07 remains the semantic authority for table, table-column, table-row, and
table-cell requirements in the modern `setElement()` lifecycle.

Legacy RichTable assign/render behavior remains supported as characterized.
D5G must not silently replace it with SR-07 semantic materialization because
D5G-B found observable content/style differences.

D5G may narrow save-time table/table-cell compatibility routing when tests prove
that legacy table output remains valid and mixed semantic/legacy documents do
not suppress either family's required styles.

Ratio semantics and other SR-07 decisions must not be reopened in D5G.

## 12. Static StyleMapper and StyleWriter contract

StyleMapper static registries are compatibility state, not document semantic
state.

D5G may:

- stop writing unused global registrations into documents;
- adopt only current-document referenced legacy definitions;
- remove internal duplicate registration when equivalent compatibility state is
  already available;
- retain thin static public facades over document-local mechanisms where safe.

D5G must not:

- clear global registries at arbitrary document boundaries if external callers
  can observe them;
- make one document authoritative for another;
- remove public static APIs opportunistically;
- pull STYLE-API-02 or the final STYLE-CONTEXT-01 cleanup into this milestone.

StyleWriter remains a serialization/finalization component. D5G should narrow
compatibility routing rather than turn StyleWriter into a lifecycle manager.

## 13. Public legacy OdtElement getters

Public legacy getters are compatibility surfaces even where the modern semantic
collector no longer needs them.

D5G may classify a getter as internally unnecessary and stop calling it from
modern code. It may not remove the getter or bypass externally observable
overrides merely because no current repository caller remains.

Where practical, legacy getters should become projections/facades over one
underlying element-owned state rather than maintain duplicate mutable semantic
state. Such consolidation must be producer-specific and characterization-led.

## 14. `load()` and `refresh()`

`load()` remains a document/template lifecycle boundary. Its current reset of
per-document compatibility tracking is preserved unless a focused test proves a
narrower equivalent implementation.

Process-global static registries must not be cleared by `load()` merely to make
internal state appear cleaner.

`refresh()` has separately characterized historical behavior, including reload
of template state and different finalization from `save()`. D5G does not make
`refresh()` equivalent to `save()` and does not redesign refresh semantics.
Only compatibility-preserving internal narrowing is allowed.

## 15. Mixed lifecycle contract

A document may use both:

```text
setElement(...)
```

and:

```text
assign(OdtElement)
render()
```

D5G must preserve this mixed use.

Semantic requirements already owned by `OdtDocumentContext` must not be
suppressed by activation of legacy compatibility. Conversely, required legacy
styles/resources must not disappear merely because semantic insertion also
occurred.

Compatibility routing should become additive and family-specific where
possible, rather than selecting one document-wide "semantic or legacy" mode.

## 16. Document isolation

The following invariant is mandatory:

> Process-global compatibility state may be observable, but unrelated state
> must not affect another document's serialized output or physical package.

D5G changes touching StyleMapper, StyleWriter, save finalization, resources, or
legacy adoption require two-document isolation coverage.

## 17. Protected extension surfaces

D5G must preserve tested protected polymorphism.

If a protected OdtTemplate method becomes only a facade, calls from public
lifecycle methods must still dispatch through the protected method where that
behavior is already characterized. Internal direct calls that bypass an
existing override are not acceptable as cleanup.

This applies especially to structured replacement and save/finalization hooks.

## 18. D5G implementation slices

Implementation should proceed in small slices.

### D5G-C — lifecycle compatibility narrowing

Primary scope:

- narrow `legacyStructuredValuesMaterialized`-driven behavior;
- distinguish actual successful/current-document compatibility needs from mere
  entry into the legacy structured code path where safely possible;
- preserve dual-part render and protected dispatch;
- preserve mixed lifecycle behavior;
- do not change ImageElement resource semantics.

Characterization tests must be extended before changing any behavior currently
asserted by D5G-B.

### D5G-D — static/finalization compatibility narrowing

Primary scope:

- reduce redundant internal legacy registrations;
- narrow frame/image/fill/table/table-cell adoption to current-document needs;
- simplify StyleWriter routing only where evidence proves equivalence;
- retain public static and protected facades;
- maintain document isolation.

### D5G-E — compatibility regression closeout

Primary scope:

- verify public/protected compatibility;
- repeated render/save;
- mixed lifecycle;
- content.xml and styles.xml;
- document isolation;
- package resources;
- D5F/SR-06/SR-07 regression coverage;
- document remaining compatibility residue for STYLE-CONTEXT-01.

A smaller sub-slice may be introduced if D5G-C or D5G-D reveals an independent
behavior-change question. Do not enlarge a refactoring slice to absorb it.

## 19. Explicit non-goals

D5G does not include:

- removal or redesign of `assign()` / `render()`;
- automatic delegation of legacy structured values to `setElement()`;
- a new generic lifecycle or compatibility context;
- semantic redesign of ImageElement;
- silent correction of the legacy ImageElement missing-resource behavior;
- redesign of `refresh()`;
- removal of public legacy getters;
- removal of protected compatibility hooks;
- removal of public StyleMapper static APIs;
- STYLE-API-02;
- STYLE-CONTEXT-01 final closeout;
- TEMPLATE-FORMAT-PRESERVATION-01;
- TEMPLATE-AUTHORING-UX-01;
- new table semantics;
- frame/image positioning redesign;
- named template objects.

## 20. Required validation

Each implementation slice must run focused tests for the changed compatibility
family plus relevant existing D5G/D5F/SR-06/SR-07 tests.

Before D5G closeout, normally run:

- D5G characterization tests;
- D5F lifecycle characterization;
- public/protected API compatibility tests;
- structured element integration tests;
- SR-06 graphic/fill-image tests;
- SR-07 table tests;
- `PublicSampleSmokeTest`;
- full `composer test`;
- PHP lint for `src/` and `tests/`;
- `composer validate --no-check-publish`;
- `git diff --check`;
- documentation build when the local toolchain exists.

If rendering-relevant XML or package structure changes, perform representative
LibreOffice regression in addition to automated tests. Automated XML equality
can justify skipping visual regression only when the affected rendering parts
are demonstrably identical.

`samples/output/*.odt`, `tmp/`, and LibreOffice `.~lock.*#` artifacts remain
outside implementation scope unless explicitly requested.

## 21. Exit criteria

D5G is complete when:

1. the D5F semantic lifecycle remains authoritative and unchanged in meaning;
2. `assign(OdtElement)` / `render()` compatibility remains supported;
3. protected polymorphic compatibility remains effective;
4. public legacy getters/static APIs required for compatibility remain intact;
5. document-wide compatibility routing has been narrowed where evidence permits
   or explicitly documented where it must remain;
6. unnecessary duplicate internal registration/finalization has been removed or
   documented as retained compatibility;
7. current-document filtering prevents process-global registry leakage;
8. mixed semantic/legacy documents remain valid;
9. repeated render/save behavior is characterized and regression-covered;
10. ImageElement/CircularImageElement/DrawTextBox/RichTable compatibility residue
    is explicitly documented;
11. no unrelated behavior correction is hidden inside the refactor;
12. full automated preflight is green;
13. rendering-relevant changes receive appropriate package/LibreOffice review;
14. remaining static/global compatibility residue is handed explicitly to
    STYLE-CONTEXT-01 rather than silently pulled into D5G.

## 22. Decision

D5G may now proceed in small implementation slices.

The intended end state is not "no compatibility code". It is:

```text
modern document-local semantic lifecycle
        +
thin, explicit, evidence-backed compatibility facades
        +
bounded current-document adoption of unavoidable legacy state
```

The architectural direction is therefore compatibility **closeout**, not
compatibility deletion.

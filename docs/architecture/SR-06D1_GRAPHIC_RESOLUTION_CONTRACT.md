# SR-06D.1 — Graphic Resolution Contract

Status: architecture contract and characterization slice

Branch target: `architecture/sr-06d1-graphic-resolution-contract`

Depends on:

- `SR-06_SEMANTIC_GRAPHIC_STYLE_REQUIREMENTS_CHANGE_CONTRACT.md`
- `SR-06C1_GRAPHIC_PRODUCER_SEMANTICS_CONTRACT.md`
- `SR-06C5_INTEGRATION_COMPATIBILITY_PREFLIGHT.md`

## 1. Purpose

SR-06D.1 defines and characterizes how semantic `graphic` style requirements participate in the existing document-local `StyleContext` resolution model before semantic graphic materialization is enabled.

The slice is intentionally limited to resolution semantics. It does not change `StyleRequirementMaterializer`, `OdtTemplate::setElement()`, legacy graphic registries, fill-image dependency handling, or rendered output.

The central question is:

> Does the generic semantic resolution model already provide the correct authority, ambiguity, identity, lifecycle, and compatibility semantics for `graphic` requirements?

## 2. Resolution authority

Graphic references use the same semantic precedence model already established for paragraph and text requirements:

```text
existing target-document definition
        ↓
document-local semantic definition
        ↓
legacy compatibility source, only where explicitly supported
        ↓
unresolved
```

For `graphic`, SR-06D.1 fixes the following rule:

> There is no legacy semantic fallback from `StyleMapper` frame/image registries.

Historical `frame` and `image` registries remain compatibility state. They are not semantic sources for `style:family="graphic"` reference resolution.

## 3. Existing target-document authority

A matching `style:style` already present in `content.xml` or `styles.xml` is authoritative for reference resolution.

A document candidate matches by:

- `style:name`;
- `style:family="graphic"`;
- optional reference scope constraint;
- optional reference document-part constraint.

An existing document candidate wins over a matching pending document-local semantic definition.

SR-06D.1 does not require structural equality comparison between the existing document definition and a pending definition. Existing-document authority follows the same bounded model already used by the semantic style architecture: the existing named family/scope/part candidate is preserved rather than overwritten by semantic materialization.

## 4. Document-local semantic resolution

If no existing document definition matches, a semantic graphic reference may resolve to a pending document-local semantic definition.

Resolution must be independent of registration order:

- reference first, definition second;
- definition first, reference second.

Both must result in `document-local` resolution once the matching definition exists.

## 5. Scope and document-part dimensions

Semantic identity remains:

```text
family + name + scope + documentPart
```

For graphic definitions, scope and document part remain independent dimensions.

A broad reference with `scope = null` and `documentPart = null` may therefore match multiple graphic definitions with the same family/name but different scope/part. That condition is ambiguous and must not silently select one.

A narrowed reference may constrain scope and/or document part and select exactly one matching definition.

SR-06D.1 does not introduce new graphic-specific identity rules.

## 6. Idempotence and conflict semantics

Equivalent semantic graphic definitions with the same semantic identity are idempotent.

Two semantic graphic definitions with the same semantic identity but different parent style or property groups are a deterministic conflict and must raise `LogicException` through the existing `StyleContext` conflict path.

Different scope/document-part combinations are different semantic identities and may coexist. A broad reference may then become ambiguous as described above.

## 7. Existing-document ambiguity

If multiple existing document styles match one graphic reference under its constraints, the reference is ambiguous.

The resolver must preserve all candidates for diagnostics and must not silently prefer first/last document order.

A narrowed reference that identifies exactly one candidate resolves to `document`.

## 8. No graphic legacy fallback

Legacy paragraph/text fallback is an explicitly bounded compatibility mechanism.

SR-06D.1 establishes that it must not be generalized to graphic styles merely because legacy graphic registries exist.

Therefore:

- a legacy frame style with a matching name does not resolve a semantic `graphic` reference;
- a legacy image style with a matching name does not resolve a semantic `graphic` reference;
- no semantic graphic definition is synthesized from those registries;
- the reference remains unresolved unless an existing document or semantic document-local candidate exists.

Legacy frame/image registries remain available to the current compatibility finalization path until SR-06F.

## 9. Materialization boundary

SR-06D.1 does not enable semantic graphic materialization.

`StyleRequirementMaterializer` remains intentionally inert for `graphic` during this slice.

This separation is deliberate:

```text
D.1  resolution semantics
  ↓
D.2  graphic style materialization
  ↓
D.3  setElement semantic authority transition
```

A characterization failure in D.1 must be classified as a resolution-model issue before any materializer behavior is changed.

## 10. Lifecycle

Graphic semantic definitions and references are document-local state owned by the current `StyleContext`.

Existing lifecycle rules apply unchanged:

- independent `OdtDocumentContext` instances do not share semantic graphic state;
- `replaceCoreDocuments()` / document reload resets pending semantic state;
- registration order does not create hidden process-global coupling.

No constructor reset or new static graphic registry may be introduced.

## 11. Explicit non-goals

SR-06D.1 does not:

- remove the inert `graphic` branch in `StyleRequirementMaterializer`;
- write semantic graphic styles to XML;
- change `OdtTemplate::setElement()` orchestration;
- add semantic `fill-image` requirements;
- resolve fill-image dependencies;
- retire legacy frame/image/fill-image registries;
- change producer identity from SR-06C;
- change public drawing APIs;
- change drawing geometry or placement;
- fix CircularImage rendering;
- begin SR-06E/F or SR-07.

## 12. Required characterization

Focused tests must prove at minimum:

1. equivalent graphic definition registration is idempotent;
2. same semantic identity with incompatible graphic definition conflicts;
3. graphic reference resolves to document-local definition regardless of registration order;
4. existing target-document graphic definition wins over document-local semantic definition;
5. broad graphic reference becomes ambiguous when multiple scope/part candidates match;
6. narrowed graphic reference selects exactly one candidate;
7. multiple existing target-document graphic candidates remain ambiguous;
8. legacy frame/image registry state does not resolve a semantic graphic reference;
9. graphic semantic state remains isolated/reset by existing document lifecycle.

## 13. Exit condition

SR-06D.1 is complete when the focused characterization passes without production-code changes or when any required production change is explicitly classified and documented before implementation.

A clean D.1 outcome means:

> The current generic `StyleContext` resolution model is already suitable for semantic `graphic` requirements, and SR-06D.2 may concentrate narrowly on XML materialization rather than redesigning resolution.

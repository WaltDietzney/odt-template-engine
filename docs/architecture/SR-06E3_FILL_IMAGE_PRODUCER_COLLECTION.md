# SR-06E.3 — Fill-Image Producer / Transitive Collection

Status: implementation slice

Depends on:

- `SR-06E1_FILL_IMAGE_DEPENDENCY_CONTRACT.md`
- `SR-06E2_DOCUMENT_LOCAL_FILL_IMAGE_DEPENDENCIES.md`
- SR-06D semantic graphic producer/ownership rules

## 1. Purpose

SR-06E.3 removes the discovery-time dependency on `CircularImageElement::toDomNode()` for named fill-image declarations.

The slice introduces a typed semantic producer hook and transitive collector while deliberately preserving the current rendering and legacy compatibility paths.

No fill-image declaration is materialized in this slice.

## 2. Problem being removed

Before SR-06E.3, `CircularImageElement` already exposed a semantic graphic style containing `draw:fill-image-name`, but its legacy fill-image declaration state appeared only after `toDomNode()` mutated the element.

That created an ordering dependency:

```text
semantic graphic requirement exists
        ↓
DOM materialization mutates element
        ↓
legacy fill-image requirement becomes discoverable
```

The desired discovery model is:

```text
element ownership tree
        ↓
typed fill-image dependency producer
        ↓
transitive FillImageRequirementCollector
        ↓
document-local registration/materialization in later slices
```

## 3. Producer contract

`OdtElement` now exposes:

```php
public function getOwnFillImageDependencies(): iterable
```

The method returns only semantic `FillImageRequirement` instances owned directly by the current element.

It is intentionally separate from the historical:

```php
getOwnFillImageRequirements(): array
```

The historical method remains unchanged for compatibility and continues to describe the legacy array-based path.

The two APIs MUST NOT be treated as aliases during SR-06E.

## 4. CircularImageElement

`CircularImageElement` produces one semantic `FillImageRequirement` before DOM materialization:

- document part: `styles.xml`
- identity: resolved fill-image name
- href: `Pictures/<filename>`

The requirement contains no absolute source path.

Physical image discovery remains exposed separately through the structured resource contract.

The existing `toDomNode()` mutation that populates the legacy fill-image compatibility state remains in place during E.3. Removing normal-path dependence on that state belongs to E.4/E.5/F.

## 5. Transitive collection

`FillImageRequirementCollector` traverses the element ownership tree using `ownedElements()`.

The collector:

1. yields the current element's own typed fill-image dependencies;
2. recursively visits owned child elements;
3. performs no deduplication;
4. performs no registration;
5. performs no conflict resolution;
6. performs no XML materialization.

Duplicate requirements remain visible so the document-local `FillImageRequirementRegistry` remains the single owner of idempotence and conflict semantics.

This matches the established D5 ownership model used by semantic style and structured resource collection.

## 6. Explicit non-goals

SR-06E.3 does not:

- register collected dependencies in `OdtDocumentContext`;
- materialize `<draw:fill-image>` declarations;
- modify `OdtTemplate::setElement()`;
- remove the legacy fill-image arrays;
- change the physical bitmap copying path;
- change manifest handling;
- alter graphic style materialization;
- change CircularImage geometry or visual output;
- fix unrelated CircularImage rendering behavior.

## 7. Compatibility boundary

The legacy methods remain available and retain their mutation timing.

Therefore the following coexist temporarily:

```text
semantic dependency
    available before toDomNode()

legacy fill-image requirement
    available only after toDomNode()
```

This coexistence is deliberate and temporary. It allows E.3 to establish semantic discovery without mixing producer migration with materialization or legacy reduction.

## 8. Test contract

The focused tests verify:

1. CircularImage produces its typed dependency before DOM materialization;
2. producing/collecting the dependency does not mutate legacy fill-image state;
3. nested dependencies are collected transitively through `ownedElements()`;
4. normal `ImageElement` produces no fill-image dependency;
5. equivalent duplicate dependencies remain visible for registry-owned idempotence.

Existing E.1 characterization remains authoritative for the legacy lifecycle.

## 9. Exit condition

SR-06E.3 is complete when typed fill-image dependency discovery is independent of DOM materialization and works transitively through the ownership tree without changing saved-document behavior.

The next slice, SR-06E.4, may then register these dependencies document-locally and materialize native `<draw:fill-image>` declarations while preserving target-document authority.

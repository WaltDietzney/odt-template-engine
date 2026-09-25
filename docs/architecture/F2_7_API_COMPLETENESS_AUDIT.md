# F2.7 API Completeness Audit

## Status

**COMPLETE — Public API 1.0 mechanical reconciliation**

This document records the FINALIZATION-01 F2.7 completeness gate. It does not
introduce API semantics or architecture.

## Evidence basis

The audit reconciles:

```text
current public PHP surface under src/
        <->
docs/architecture/PUBLIC_API_INVENTORY.md
        <->
docs/api-reference/
        <->
Recommended / Advanced / Compatibility / Deprecated / Infrastructure-Hidden
```

The existing `PUBLIC_API_INVENTORY.md` contains the completed mechanical
public-symbol enumeration of `src/`, including the final supporting DTO
accessors and the explicit Infrastructure/Hidden disposition. F2.7 reused that
enumeration as audit evidence and checked its dispositions against the finished
public API Reference rather than reclassifying the API.

## Reconciliation result

No unclassified 1.0 capability was found. No implementation/semantics
contradiction requiring a FINALIZATION blocker was found. Infrastructure that
is public for technical composition, serialization, testing, or internal
services remains intentionally hidden from normal end-programmer
documentation.

The reference already covered the Recommended application surfaces, the
Advanced orchestration/extension surfaces, retained Compatibility/Deprecated
behavior, and the user-facing option contracts established by the inventory.

The final cross-check found three bounded reference-completeness omissions in
already-classified supporting read-only surfaces:

1. `RichTableCell` Advanced inspection accessors:
   `getColspan()`, `getRowspan()`, `getStyle()`, and `getStyleName()`.
2. Mapping support accessors:
   `NativeObjectActionMapping::targetKind()` / `actionId()`,
   `DocumentCapabilityMapping::group()`, and the mapping accessors on
   `NativeObjectActionResolution` / `DocumentCapabilityResolution`.
3. Physical template-structure DTO accessors recorded by the inventory,
   including split/topology classification and normalization-result read
   surfaces.

These were documentation gaps only. The API Reference was completed without
changing runtime behavior or inventing new semantics.

## Classification gate

The resulting 1.0 public surface has exactly the intended dispositions:

- **Recommended** — normal supported end-programmer path;
- **Advanced** — supported specialist/tooling/orchestration/extension path;
- **Compatibility / Deprecated** — retained historical behavior with preferred
  replacements and semantic differences documented;
- **Infrastructure / Hidden** — public PHP implementation surface that is not
  promoted into normal application API.

Retired APIs such as `HasStyles` and `LegacyStyleRegistry` remain retired
and are not reintroduced as Compatibility API.

## F2.7 conclusion

The mechanical reconciliation has no unexplained public surface and no
unexplained Recommended/Advanced user-facing option dictionary.

F2.7 is complete subject to the normal documentation/CI validation of the
resulting branch. After that validation passes, F2 Public API 1.0 can be closed
and merged to `develop` before F3 begins.

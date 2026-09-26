# TEMPLATE-AUTHORING-01E6 — Automation Atomicity + Integration Closure Change Contract

## Status

**Accepted architecture contract / implementation-ready**

E6 is the final implementation slice of TEMPLATE-AUTHORING-01E.

It introduces no new mapping semantics or automation capabilities. E1–E5 remain authoritative for resolution, preflight, and the three target families. E6 closes Phase E by providing one common automation invocation, complete atomicity across all document-local mutations owned by that invocation, integration of the existing E3/E4/E5 executors, lifecycle and compatibility validation, and Phase-E integration closure.

## 1. Purpose

Before E6, execution is intentionally split into three paths:

```text
READY ConcretePreflightResult
        │
        ├── E3 Dependency Automation
        ├── E4 Native Object Action Automation
        └── E5 Document Capability Automation
```

E6 adds the outer Phase-E automation unit:

```text
READY ConcretePreflightResult
        ↓
Phase-E Automation Invocation
        ↓
snapshot Working Document
        ↓
E3 + E4 + E5
        ↓
success → mutations retained
failure → restore pre-invocation state
```

## 2. Existing executor authorities remain

E6 does not replace E3, E4, or E5.

`DependencyAutomationExecutor`, `NativeObjectActionExecutor`, and
`DocumentCapabilityAutomationExecutor` remain authoritative for their existing execution semantics. E6 must not copy their domain logic into a new mega-executor.

The E6 orchestrator coordinates those existing paths under one outer transaction boundary. Existing mutation owners remain authoritative.

## 3. Preflight remains the mutation boundary

The common automation invocation operates only on an already complete
`ConcretePreflightResult`.

Before the first automation-owned mutation:

```text
preflight.ready() === true
```

A non-READY preflight causes no automation mutation.

E6 does not perform a second mapping resolution or semantic preflight. It must not re-resolve application data, reinterpret mapping definitions, re-inspect the template semantically, revalidate payload semantics, or repair missing/invalid values.

Existing execution-integrity checks inside E3–E5 remain valid.

## 4. One invocation is one atomic unit

For state `S0` immediately before the Phase-E invocation:

```text
success → resulting state S1
failure anywhere → restore S0
```

No partial state from a failed invocation may remain observable.

Atomicity applies across target families. A failure after earlier E3/E4/E5 mutations rolls back all automation-owned mutations from that invocation.

## 5. Rollback boundary preserves prior imperative work

Rollback restores the state immediately before the Phase-E invocation, not the originally loaded template.

Therefore:

```text
load
→ imperative mutation A
→ imperative mutation B
→ Phase-E invocation fails
```

must retain imperative mutations A and B while removing the failed Phase-E invocation's mutations.

The snapshot boundary begins immediately before the common automation invocation.

## 6. Rollback covers actual document-local mutable state

The rollback boundary is not hard-coded to `content.xml`.

It must cover every document-local mutable state E3–E5 can change. At minimum the implementation must investigate:

- `content.xml`;
- `styles.xml`;
- `meta.xml`;
- package resources / embedded images;
- document-local `StyleContext` or registered style requirements;
- other mutable state in `OdtDocumentContext`;
- internal registries/caches that can affect later document output.

The implementation must characterize the actual mutable state before choosing the snapshot mechanism.

A solution that merely clones three DOMDocuments is not assumed sufficient.

## 7. Package state is part of the investigation

E4 Frame `replace-image` can affect embedded image resources as well as XML.

E6 must establish which package parts/resources can be created, replaced, or changed by a Phase-E invocation.

After rollback, newly introduced automation-owned package assets must not remain in a subsequently saved document, and previously existing assets must not be lost.

Atomicity concerns the semantic Working Document state, not only DOM state.

## 8. Snapshot/restore mechanism

The technical snapshot mechanism remains an implementation decision.

It must:

- capture the complete relevant Working Document state before mutation;
- restore it reliably after failure;
- avoid introducing a competing document authority;
- remain document-local;
- leave existing services connected to the authoritative restored state.

There is exactly one outer E6 transaction boundary. Independent per-executor transaction boundaries must not be introduced.

## 9. Do not distribute E6 rollback logic into E3–E5

E6 must not retrofit independent snapshot/rollback ownership into each executor.

Existing lower-level atomic behavior may remain, but E6 surrounds it with one outer invocation-level boundary.

Atomicity is a property of the common Phase-E invocation.

## 10. Original failure remains authoritative

For an automation exception `E`:

```text
automation fails with E
→ rollback succeeds
→ rethrow E
```

Rollback must not silently replace the original execution failure.

If rollback itself fails, that fact must not be hidden; the implementation must preserve both the original failure and the rollback failure diagnostically.

Failures must not be converted to silent partial success, `false`, or `null`.

## 11. Execution order

E6 does not invent a universal semantic order for arbitrary future capabilities.

The concrete common invocation requires deterministic orchestration. Before fixing that order, implementation must characterize actual dependencies among E3–E5.

The established internal E3 order remains:

```text
Writer User Fields
→ declarative structural controls
→ remaining Classic bindings
```

E6 must not change it.

No artificial semantic dependency between E4 and E5 should be asserted where none exists. The chosen technical orchestration order must be documented and tested, but it is not a universal future Phase-E ordering rule.

## 12. Common public automation facade

E6 may add a narrow public Phase-E facade on `OdtTemplate`, conceptually:

```php
$template->automate($preflight);
```

The concrete name must be checked against the existing public API.

It represents one complete Phase-E automation invocation and coordinates the existing E3/E4/E5 execution paths.

Existing specialized facades remain:

```text
automateDependencies(...)
automateNativeObjectActions(...)
automateDocumentCapabilities(...)
```

They are not removed or silently assigned the new common-invocation atomicity guarantee.

## 13. One successful invocation per Working Document lifecycle

Phase-E 1.0 guarantees one successful common automation invocation per Working Document lifecycle.

After one successful common invocation, a second common invocation in the same lifecycle must be rejected rather than silently treated as supported repeated automation.

A failed invocation followed by complete rollback does not count as a successful invocation. A corrected retry may therefore succeed after full restoration.

Existing imperative APIs and the specialized E3/E4/E5 facades remain outside this new common-invocation lifecycle guarantee unless separately specified.

## 14. Imperative operations before and after automation

The supported lifecycle remains:

```text
load
→ optional imperative operations
→ Phase-E automation
→ optional imperative operations
→ save
```

The common automation invocation does not save, finalize, close the document, or block later imperative APIs.

FINALIZATION-01 remains separate.

## 15. No implicit render

E6 must not redefine existing `render()` behavior as Phase-E automation.

The common Phase-E invocation is explicit and optional. Existing render lifecycle and compatibility remain unchanged.

## 16. No save or finalization

A successful E6 invocation ends with a mutated Working Document, not a generated file.

It performs no save, export, PDF generation, finalization, or document closure.

## 17. No new capability semantics

E6 orchestrates only capabilities already approved and implemented in E1–E5.

It introduces no new dependency types, native actions, metadata targets, payload conversions, mapping rules, application-path syntax, or normalization semantics.

Any apparent need for such a change is an architecture question and must not be improvised inside E6.

## 18. No general transaction framework

E6 atomicity does not authorize a speculative general transaction platform.

E6 must not introduce, without concrete necessity, a generic TransactionManager, UnitOfWork framework, command rollback framework, undo/redo architecture, event sourcing, or document-history system.

A small document-specific snapshot/restore abstraction is acceptable if justified by the characterized mutable state.

## 19. Diagnostics and Authoring UX

E6 does not replace E1/E2 diagnostics with a new error hierarchy.

Preflight failures remain machine-inspectable before mutation. Execution failures remain execution exceptions.

The architecture must continue to distinguish:

```text
preflight failed before execution
vs.
execution started, failed, and was rolled back
```

E6 does not implement TEMPLATE-AUTHORING-UX-01, a GUI, or a general execution-report platform.

## 20. Integration tests

E6 requires real cross-family integration tests.

### Success

A representative template must exercise together:

- dependency automation;
- at least one Section or Bookmark native action;
- Frame `replace-image`;
- metadata automation.

One READY common invocation must apply all selected operations correctly, followed by explicit save/reload verification.

### Failure after earlier mutations

Force an execution failure after at least one earlier family has mutated the Working Document. The resulting state must equal the state immediately before invocation.

### Rollback coverage

Verify as applicable:

- `content.xml`;
- `styles.xml`;
- `meta.xml`;
- package resources;
- relevant document-local registries/state.

### Prior imperative mutation

An imperative mutation made before a failing common invocation must remain after rollback.

### Retry after rollback

A failed invocation followed by successful rollback must allow a corrected common invocation to succeed.

### Repeated successful invocation

A second common invocation after a successful common invocation must be rejected for Phase-E 1.0.

### Post-automation imperative use

A successful common invocation followed by imperative API usage and explicit save must remain supported.

## 21. Existing specialized APIs

The E3–E5 facades remain compatible:

```text
automateDependencies()
automateNativeObjectActions()
automateDocumentCapabilities()
```

E6 may reuse them internally if this preserves clean transaction semantics, or coordinate the existing executor services directly. Their semantics must not be duplicated.

## 22. Manual LibreOffice integration gate

E6 completion requires a manual LibreOffice regression exercising multiple Phase-E families together:

```text
dependency replacement
+
native structured replacement
+
frame image replacement
+
metadata
```

Required flow:

```text
automation
→ explicit save
→ LibreOffice open
→ no repair warning
→ inspect visible content/layout/image
→ inspect File Properties
→ save
→ close
→ reopen
→ verify again
```

Headless LibreOffice checks may supplement but do not replace this manual gate.

## 23. Automated preflight

After E6 implementation run at minimum:

- focused E6 atomicity tests;
- cross-family integration tests;
- E3 regression;
- E4 regression;
- E5 regression;
- mapping/resolution/concrete-preflight regression;
- metadata regression;
- `PublicSampleSmokeTest`;
- full `composer test`;
- PHP lint for `src/` and `tests/`;
- `composer validate --no-check-publish`;
- `git diff --check`;
- strict documentation build.

Package/ODT-integrity testing must include appropriate ZIP/save/reload coverage.

## 24. Completion record

Create:

`docs/architecture/TEMPLATE_AUTHORING_01E6_COMPLETION.md`

It must document at least:

- concrete orchestration architecture;
- snapshot/restore boundary;
- actually identified mutable state;
- chosen technical execution order and rationale;
- failure/rollback behavior;
- retry semantics;
- one-successful-invocation lifecycle;
- compatibility;
- tests and automated preflight;
- manual LibreOffice regression;
- remaining limitations.

## 25. Phase-E closure

E6 is also the closure slice for TEMPLATE-AUTHORING-01E.

On successful E6 completion, Phase-E documentation must be checked for factual status drift. Proven stale status text such as `E1 implementation in progress` in the parent contract or stale roadmap handoffs may be corrected.

This is status/documentation closure only. Accepted historical architecture decisions must not be silently rewritten.

## 26. Completion criterion

E6 is complete when:

```text
complete READY preflight
→ one bounded Phase-E automation invocation
→ E3 + E4 + E5 mutations
→ success: all retained
→ failure: all restored
→ prior imperative state preserved
→ save/reload stable
→ LibreOffice stable
```

without introducing:

```text
new mapping semantics
new capability semantics
repeated-render semantics
implicit render
save/finalization
generic transaction framework
Authoring UX implementation
```

With E6 COMPLETE:

**TEMPLATE-AUTHORING-01E — Mapping / Automation is COMPLETE.**

# STYLE-CONTEXT-01B — Ownership and Lifecycle Contract

## Purpose

STYLE-CONTEXT-01A established that style state currently follows two different ownership models: normal structured-element style requirements are already largely document-local, while explicit and legacy registration paths use process-wide mutable registries.

This slice defines the semantic contract for document-scoped style state before a `StyleContext` implementation is introduced.

This is an architecture decision, not an implementation slice. No public API, rendering behavior, style XML, or compatibility path changes here.

## Decision summary

Style state belongs to **one logical editable ODT document**.

The document lifetime is represented by `OdtDocumentContext`. Therefore future mutable style state must be owned by, or be a document-scoped collaborator owned through, `OdtDocumentContext`.

`OdtPackage` owns the physical package/workspace lifecycle. It is not the semantic owner of styles. `OdtTemplate` remains the public facade and orchestration boundary. Neither process-wide static registries nor individual elements are authoritative owners of document style state.

Conceptually:

```text
OdtTemplate                         public facade / orchestration
    ↓
OdtPackage                          physical ODT package lifetime
    ↓
OdtDocumentContext                  logical mutable document lifetime
    ├── content.xml DOM
    ├── styles.xml DOM
    ├── meta.xml DOM
    └── document-scoped style state   [future implementation]
```

The exact internal class shape for the final line is intentionally deferred to 01C.

## 1. What constitutes one style context?

One style context corresponds to one `OdtDocumentContext` instance and therefore one logical editable ODT document.

Two simultaneously existing `OdtTemplate` instances must have independent style state even when they are created from the same template file and used in the same PHP process.

Style state must not be keyed by:

- PHP process;
- template filename;
- output filename;
- static class state;
- renderer call;
- individual `OdtElement` instance.

The document is the ownership boundary.

## 2. Context owner

The semantic owner is `OdtDocumentContext`, not `OdtPackage`.

Rationale:

- `OdtDocumentContext` already owns mutable `content.xml`, `styles.xml`, and `meta.xml` state;
- its existing class documentation explicitly reserves the document lifetime for later document-scoped concerns;
- style definitions are document semantics, not ZIP/workspace mechanics;
- future document defaults are expected to consume the same document lifetime;
- keeping ownership at the document boundary avoids coupling style semantics to archive operations.

`OdtPackage` may construct, retain, reload, or otherwise coordinate the context because it owns the physical package. That does not make the package the semantic style registry.

## 3. Elements are producers, not owners

Externally constructed elements such as `RichText`, `Paragraph`, tables, images, and frames may describe or expose style requirements before they are attached to a document.

They do not own the authoritative registry for the target document.

The preferred semantic flow is:

```text
Element
  ↓ exposes style requirements
Document materialization boundary
  ↓ registers/merges requirements
Document-scoped style state
  ↓ finalization
current styles.xml/content.xml
```

Elements must not need a global singleton and should not need to know which document will eventually consume them.

This preserves the useful property that an element can be constructed independently and later materialized into a specific document.

## 4. Lifecycle semantics

### Construction

Creating a new `OdtTemplate` creates a new `OdtPackage` and a new `OdtDocumentContext`.

Its future style context starts from the styles already present in that document's loaded ODF DOMs plus no pending registrations originating from another document.

No process-wide style registrations may become implicit initial state of a newly created document once STYLE-CONTEXT-01 migration is complete.

### `load()`

Current public `OdtTemplate::load()` restores the workspace from the **original template** through `OdtPackage::resetFromTemplate()`.

Therefore `load()` is a document reset boundary.

Future document-scoped pending/generated style state must be reset to match the newly restored template state. Styles introduced only by edits made before `load()` must not survive the reset merely because they were previously registered in memory.

The `OdtDocumentContext` object itself may remain the same object while its core DOM documents are replaced; semantic state attached to that context must nevertheless be reset/reconciled as part of the same operation.

This distinction is important: context identity may survive, but document contents have been reset.

### `render()`

`render()` is not a style-context lifetime boundary.

Rendering may cause style requirements to be consumed or materialized, but it must not clear unrelated document-owned style state and must not create a new style context.

Repeated rendering must not create cross-document effects.

Existing render idempotency/compatibility behavior is outside the scope of 01B and must not be changed merely to simplify style ownership.

### `save()`

`save()` finalizes the **current document's** style requirements into the current document only.

Saving is not a context reset and not a transfer of ownership.

After `save()` the same `OdtTemplate` remains the same logical editable document unless an existing public lifecycle operation says otherwise.

A save must not consume or clear style state in a way that makes a second save of the same unchanged document semantically different from the first.

### Repeated `save()`

Repeated saves of an unchanged document must be style-idempotent:

- no duplicate style definitions caused solely by saving again;
- no missing styles because a process-wide "already written" cache suppressed them;
- no additional styles imported from another document saved between the two calls.

This contract applies to style semantics. Byte-for-byte ZIP identity is not required.

### `refresh()`

Current `OdtTemplate::refresh()` writes styles, persists the core DOMs, and then calls `load()`.

Because `load()` restores the original template, the current behavior is semantically surprising and may discard persisted working-state changes. STYLE-CONTEXT-01 must not silently redefine this public lifecycle behavior.

For this architecture block, `refresh()` inherits the lifecycle semantics of the operations it actually invokes: style finalization occurs before the subsequent reset, and the resulting style context must then match whatever document state the existing `load()` leaves active.

If `refresh()` behavior itself is considered incorrect, that belongs to explicit lifecycle characterization/change work, not an incidental StyleContext refactor.

## 5. Existing DOM styles versus pending style requirements

The future style context must distinguish at least conceptually between:

1. style definitions already present in the loaded ODF DOMs;
2. style requirements registered/generated during editing of the current document.

The loaded DOM remains the source of truth for styles authored in LibreOffice or already persisted in the template.

The document-scoped registry should not require copying every existing XML style definition into a second mutable object model merely to claim ownership. It may instead track pending/generated requirements and consult the current DOM for conflicts/existence during materialization.

This avoids unnecessary duplicated mutable state.

## 6. Named styles and generated automatic styles

Named styles and generated/automatic styles share the same **document ownership boundary**, but they need not share one undifferentiated internal registry.

Their ODF placement and collision semantics differ:

- named styles may live in `office:styles` and carry user/template meaning;
- generated automatic styles may live in `office:automatic-styles` and are implementation/materialization details;
- list, graphic, table, table-cell, and font-face definitions have additional ODF-specific placement rules.

Therefore 01C may model separate families/registries under one document-scoped context.

The architecture requirement is shared ownership, not forced storage uniformity.

## 7. Duplicate names and conflicting definitions

Within one document, duplicate registration of the same semantic style definition should be idempotent.

A style name that already exists with a **different** definition must not be silently overwritten merely because a later element registers the same name.

The safe default contract for explicit named styles is:

- same name + equivalent definition → reuse/idempotent;
- same name + conflicting definition → explicit conflict, unless an existing compatibility path has separately characterized overwrite semantics that must temporarily be preserved.

Generated automatic styles should preferentially derive deterministic names from their normalized definition. Equivalent generated definitions should converge on the same generated identity where current compatibility permits.

The exact normalization/equality algorithm and exception/API shape are implementation decisions for later slices.

## 8. Legacy static APIs

Existing static methods such as `StyleMapper::registerParagraphStyle()` are compatibility-sensitive entry points.

They may remain callable during migration, but they must not remain the authoritative owner of document state.

There is a fundamental semantic constraint: a static registration call made without any document reference cannot reliably know which of several simultaneously active documents should receive the style.

Therefore STYLE-CONTEXT-01 must not pretend that transparent document scoping can always be inferred from a context-free static call.

Migration rules:

- do not introduce constructor-time global resets as a substitute for ownership;
- do not use a process-global "current document" pointer;
- preserve protected/public compatibility facades where their reachability requires it;
- route document-aware internal paths to document-scoped registration;
- characterize truly context-free legacy calls before deciding whether they remain legacy-global, become deprecated, require explicit transfer/materialization, or can be safely narrowed.

Backward compatibility is a constraint, but ambiguous ownership must be made explicit rather than hidden behind another global singleton.

## 9. Finalization contract

Style finalization is a document operation.

A future finalizer must receive or derive the style state of the document being finalized. It must not discover authoritative pending styles by reading unrelated process-wide registries.

Conceptually:

```text
finalize(documentContext)
    ↓
read current document DOM
read current document pending style requirements
    ↓
merge idempotently according to ODF family/placement rules
    ↓
write current document DOM
```

`StyleWriter` may remain an implementation collaborator, but process-wide mutable caches cannot determine whether a style belongs to a document.

## 10. Cross-document isolation contract

The end-state invariant is:

> Operations performed on document A must not change the style output of document B unless data or an element is explicitly transferred from A to B by application code.

This includes interleaved use:

```text
construct A
construct B
edit A
edit B
save A
edit A
save B
save A again
```

B must contain only B's template-authored styles and B's own registered/materialized requirements. A later save of A must likewise be unaffected by B.

This invariant should become a central regression suite as implementation slices proceed.

## 11. Compatibility invariants

STYLE-CONTEXT-01 implementation must preserve unless explicitly changed by a separately approved contract:

- public `OdtTemplate` APIs;
- protected facade behavior relevant to subclasses;
- current ODF placement and serialization of styles;
- normal `RichText` / `Paragraph` output;
- image/frame/table/list behavior;
- processing of both `content.xml` and `styles.xml` where currently required;
- repeated `render()` / `save()` lifecycle behavior outside the ownership bug being addressed;
- LibreOffice compatibility.

The confirmed cross-document leak is characterized legacy behavior, but it is the behavior STYLE-CONTEXT-01 is specifically intended to eliminate through an explicit architecture change. Its characterization test will eventually need to be replaced or inverted when the migration reaches that path.

## 12. Non-goals

This contract does not define:

- document default styles (`DOCUMENT-DEFAULTS-01`);
- a new public style API (`STYLE-API-02`);
- frame/image layout redesign;
- table layout redesign;
- generic named-object replacement;
- renderer-independent style models;
- changes to `refresh()` semantics;
- a complete normalized representation for all ODF style families.

Those topics may consume the ownership model later but must not expand STYLE-CONTEXT-01.

## 13. Contract for STYLE-CONTEXT-01C

01C may introduce the smallest internal document-scoped style-state object necessary to prove this ownership model.

It should:

1. be owned through `OdtDocumentContext` or an equally explicit document-owned collaborator;
2. contain no process-global mutable state;
3. initially support only a narrowly characterized style family/path rather than migrate every registry at once;
4. distinguish existing DOM definitions from pending document requirements without duplicating the full styles DOM;
5. provide deterministic idempotent registration for equivalent definitions;
6. expose conflict detection for same-name/different-definition cases where that path is migrated;
7. have focused unit tests for document isolation and lifecycle reset/reconciliation;
8. leave legacy static entry points in place until their compatibility path is migrated deliberately.

No element-wide or StyleWriter-wide rewrite should occur in 01C.

## Architecture decision

The key decision of 01B is therefore:

> **Mutable style registration state is document state. `OdtDocumentContext` is its lifetime boundary. Elements declare requirements; document materialization registers them; document finalization writes them. Static registries may survive temporarily as compatibility mechanisms, but they are not the target owner.**

This contract should be treated as the semantic baseline for the remaining STYLE-CONTEXT-01 slices.

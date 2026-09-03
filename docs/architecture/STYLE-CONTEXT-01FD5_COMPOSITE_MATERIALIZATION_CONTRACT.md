# STYLE-CONTEXT-01F-D5 — Composite Materialization Change Contract

## 1. Status and scope

This document is the D5B Change Contract for transitive structured-element
propagation. It selects the ownership and traversal direction for later D5
implementation slices.

It defines semantics for:

* one authoritative logical ownership tree;
* transitive style and resource discovery;
* conflict-preserving requirement collection;
* native ODF materialization responsibilities; and
* the boundary between document style adoption and package resource
  preparation.

It does not implement the contract, add PHP APIs, or change element classes.
It does not implement ASSET-CONTEXT, FRAME-LAYOUT, STYLE-API-02, 01F-E table
work, or D6 legacy structured-value migration.

## 2. Source-of-truth evidence

The contract is based on:

* `STYLE-CONTEXT-01FD_GRAPHIC_IMAGE_CONTRACT.md`;
* `STYLE-CONTEXT-01FD5_COMPOSITE_MATERIALIZATION_AUDIT.md`;
* `StyleContextCompositeMaterializationCharacterizationTest`; and
* the current implementations of the composite elements, the structured
  materializer, `OdtTemplate`, `OdtPackage`, and `OdtDocumentContext`.

D5A established that the rendered child tree, requirement tree, and package
resource tree are currently different. In particular, children can render
while their styles or physical image resources are absent, and the existing
`array_merge()` collector can erase a same-name conflict before
`StyleContext` sees it.

The D5A results are evidence of current behavior. They are not behavior to
preserve where they contradict the inherited ownership contract.

## 3. Problem statement

Current composite classes use different storage models:

```text
Paragraph       -> embeddedElements
RichText        -> elements
DrawTextBox     -> paragraphs
ListElement     -> items
RichTable       -> rows / cells
RichTableCell   -> content
```

Each class can render its actual children, but requirement and asset discovery
does not follow the same logical tree. The result is partial propagation and
silent requirement loss at composite boundaries.

D5 must establish one semantic ownership view without forcing all classes into
one physical storage array or moving ODF-specific knowledge into
`OdtTemplate`.

## 4. Inherited invariants

The following decisions from STYLE-CONTEXT-01B and the D4 contract remain in
force:

1. Normal document requirements belong to the current
   `OdtDocumentContext`/`StyleContext`.
2. Physical image files and manifest entries belong to package/resource
   preparation, not to `StyleContext`.
3. An operation on document A must not affect document B without an explicit
   application-level transfer.
4. Unattached element construction and mutation changes no document.
5. Native ODF semantics remain associated with the element model.
6. `OdtTemplate` is an orchestration boundary, not a registry of concrete
   element knowledge.
7. Explicit context-free `StyleMapper`/`StyleWriter` compatibility behavior
   remains separate until its own migration.
8. No process-global current-document pointer or constructor reset is allowed.

## 5. D2 wording clarification and supersession

The D2 contract states the useful semantic rule:

```text
OdtElement -> toDomNode() -> native ODF/XML representation
```

D5B retains that rule as a semantic competence invariant: the element model or
an explicitly element-associated collaborator must remain the authoritative
place for knowledge of the element’s native ODF meaning.

D5A reopened the incidental implementation question of whether every concrete
class must permanently contain the final DOM-construction method. D5B
clarifies, without changing the D2 ownership decision, that the exact
placement of construction mechanics remains open for the A/B/C evaluation.
`toDomNode()` may remain on an element, delegate to an element-specific
collaborator, or use shared mechanics around element-local semantics. It must
not become an excuse to centralize all ODF semantics in `OdtTemplate` or a God
renderer.

This clarification supersedes only the literal implication that every final
DOM-construction implementation must permanently live directly in the
concrete element class. It does not supersede D2’s native-ODF competence,
document ownership, lifecycle, or compatibility decisions.

## 6. Alternatives evaluated

### A — Per-element native materialization plus unified ownership

Each element retains its native ODF construction responsibility, while every
composite exposes an authoritative semantic child view. A shared traversal can
then discover requirements and resources without inspecting private fields.

Strengths:

* smallest conceptual change to current native element semantics;
* strong fit for Paragraph, DrawTextBox, lists, and CircularImage geometry;
* incremental adoption is straightforward;
* no passive document DTO model is required.

Risks:

* repeated fragment and child-append mechanics remain unless extracted;
* element implementations still need careful lifecycle handling;
* table/cell adapters require an explicit ownership decision.

### B — Element-specific renderer/materializer collaborators

Each element delegates native construction to an associated collaborator while
retaining semantic ownership of its ODF meaning.

Strengths:

* can separate construction mechanics and element state;
* may improve isolated testing of complex ODF construction.

Risks:

* adds collaborator lifecycle and dispatch complexity before current ownership
  semantics are stable;
* can drift toward passive DTO elements or a renderer registry;
* would make the D5 migration larger without solving ownership by itself.

### C — Element-local semantics plus shared materialization mechanics

Element-specific code retains ODF meaning, while a small shared mechanism
handles repeated child insertion, fragment handling, requirement traversal, and
document-bound adoption.

Strengths:

* removes duplicated generic mechanics;
* keeps ODF semantics close to their native element types;
* provides one place to preserve requirement conflicts and lifecycle phases.

Risks:

* the shared mechanism must remain narrow;
* an overly broad abstraction could become a renderer or universal style
  framework;
* ownership still needs an explicit semantic protocol.

## 7. Decision: A + C hybrid

D5 selects a constrained hybrid of A and C:

1. Establish one internal semantic ownership capability for composites.
2. Keep each element’s ODF-specific semantics localized to the element model
   or an explicitly associated element-specific collaborator.
3. Introduce one narrow shared traversal/collection mechanism for the logical
   ownership tree and repeated DOM mechanics.
4. Keep package resource preparation separate from style requirement adoption.
5. Keep `OdtTemplate` responsible for orchestration and document ownership,
   not concrete-class traversal.

B is not selected as the default migration direction. An element-specific
   collaborator may still be introduced later for a proven isolated case, but
   it must preserve the same ownership and semantic-locality contract.

This decision is deliberately not a requirement that all classes use one
physical child array or that every class retain a literal `toDomNode()` body.

## 8. Semantic ownership model

Each composite must provide an authoritative logical view of the OdtElements it
owns. Conceptually this is an internal capability equivalent to:

```php
ownedElements(): iterable<OdtElement>
```

The name, visibility, and exact PHP signature are implementation decisions for
D5C and are not frozen here.

The protocol means native containment/ownership, not arbitrary references or
visual proximity. A leaf exposes an empty view. A composite maps its existing
natural storage into the view:

* Paragraph maps embedded elements;
* RichText maps `$elements`;
* DrawTextBox maps `$paragraphs`;
* ListElement maps item elements and nested lists;
* RichTable maps its logical cells through a table-specific adapter;
* RichTableCell maps its `content` when that content is an OdtElement.

Physical storage remains class-specific. A table row does not need to become
an `OdtElement` merely to participate in ownership traversal. The ownership
protocol may expose structural adapters for rows/cells, provided they do not
invent independent document semantics or conceal native table containment.

## 9. Traversal model

Requirement and resource walking belongs in one shared tree walker over the
semantic ownership view, rather than in independent recursive getters for each
family.

The walker must:

* visit each owned child in native logical order;
* obtain the current element’s own requirement providers;
* recurse into owned children;
* keep style requirements and physical resource requirements as separate
  result channels; and
* preserve individual requirement occurrences until registration/adoption.

The walker must not inspect concrete classes from `OdtTemplate`. Concrete
elements remain responsible for exposing their own requirements; composites
provide ownership, not facade-specific traversal code.

## 10. Requirement collection and conflicts

The collector must not return only a map keyed by style name while traversing.
That shape caused the D5A `array_merge()` conflict loss.

The semantic result is an ordered stream of requirement records containing at
least:

```text
family, name, definition, owning element/path
```

The exact record class or array shape is deferred. Records with the same
family and name remain separate until document-owned registration applies the
existing rules:

```text
same name + equivalent definition
    -> idempotent

same name + different definition
    -> explicit document-local conflict
```

A collector may provide grouped views after conflict-preserving collection,
but it must never silently choose the last definition. Conflict detection must
occur before the conflicting requirement is materialized as authoritative
document state.

This applies to paragraph, text, frame, image, and fill-image requirements
that participate in the active document path. Legacy global first/last-write
compatibility behavior remains outside this collector.

## 11. Physical resource discovery

The same ownership walk provides a complete discovery path for physical image
requirements, but styles and resources remain separate outputs:

```text
owned element tree
    ├── semantic style/declaration requirements -> StyleContext
    └── physical resource requirements          -> OdtPackage
```

`StyleContext` may retain a fill-image declaration that references a package
path or source descriptor needed for declaration materialization. It does not
copy files, own package paths, or write manifest entries.

`OdtPackage` remains responsible for preparing `Pictures/*` and synchronizing
`META-INF/manifest.xml`. Resource discovery must cover nested content depth,
including table-cell content and textbox children, once the ownership protocol
is implemented. This is propagation, not a new ASSET-CONTEXT architecture.

## 12. Native materialization responsibilities

Responsibilities are split as follows.

### Element or element-associated semantic component

Owns knowledge of native ODF meaning:

* Paragraph paragraphs, spans, lists, and embedded order;
* DrawTextBox frames, text boxes, attributes, and child placement;
* ListElement lists and list items;
* RichTable tables, rows, columns, cells, and cell content;
* ImageElement frames, images, anchors, dimensions, and wrap attributes;
* CircularImageElement custom-shape geometry and bitmap-fill references.

### Shared materialization mechanics

May own only repeated mechanics such as:

* walking owned children;
* inserting a child node into a parent;
* handling document fragments;
* cloning fragment children when the target DOM requires it; and
* coordinating requirement discovery around materialization.

It must not decide the meaning of a table, list, frame, or custom shape.

### Document boundary

`OdtTemplate`/`OdtDocumentContext` adopt requirements into the current logical
document, invoke package resource preparation, and coordinate finalization.
They must not contain concrete-element traversal branches.

### Finalizer

The finalizer writes only current-document requirements and uses the existing
ODF placement rules. It must not treat unrelated global registries as active
document state.

## 13. Materialization lifecycle and ordering

The implementation must support requirements that are not final until native
materialization resolves element state. Image alignment/wrap options and
circular-image style/fill names are current examples.

The semantic lifecycle is two-phase where needed:

```text
pre-materialization discovery
    -> prepare known resources / requirements
native subtree materialization
    -> element-local final state becomes available
post-materialization discovery
    -> adopt final requirements and prepare newly exposed resources
document finalization
```

An implementation may collapse a phase for elements whose requirements are
already final. It must not force artificial pre-registration that changes
native output.

The same logical subtree must not be materialized as a second visible subtree
just to discover requirements. Repeated materialization must be stable, and
repeated save must be style-idempotent.

## 14. RichTable and RichTableCell

`RichTable` owns its rows and cells as logical table content. `RichTableCell`
remains a table-specific wrapper rather than being promoted to a general
document element solely for traversal.

The ownership protocol must nevertheless expose a cell’s `Paragraph` or
`RichText` content to the shared walker. A narrow table/cell adapter is
allowed. It must preserve:

* table-native row/cell containment;
* cell-local table style semantics; and
* current ODF table materialization behavior.

The adapter must make nested text, paragraph, graphic, fill-image, and physical
resource requirements discoverable without reflection in `OdtTemplate`.
Table layout and table-style ownership remain outside D5B.

## 15. OdtTemplate boundary

`OdtTemplate` remains the public orchestration boundary. It may ask the root
element for its semantic requirements/owned subtree and adopt the result into
the current `OdtDocumentContext`.

It must not contain logic equivalent to:

```php
if ($element instanceof DrawTextBox) { ... }
if ($element instanceof RichTable) { ... }
```

It must not infer ownership from visual proximity, DOM adjacency, private
field reflection, or generated style names.

## 16. Legacy and public compatibility

The following remain compatibility boundaries and are not redesigned by D5B:

* public element construction and fluent APIs;
* `toDomNode()` callability where currently public;
* `assign()`, `render()`, and `setValuesInDom()` lifecycle;
* protected facade hooks;
* direct `StyleMapper` and `StyleWriter` behavior;
* existing save/reopen behavior.

The active `setElement()` path and legacy structured-value path may use
different adoption boundaries temporarily, but both must preserve native ODF
output and must not introduce a global current-document owner.

## 17. Required behavioral invariants

Later implementation slices must prove:

1. Every inserted owned subtree is traversed completely.
2. Rendered child content, style requirements, and physical resources do not
   silently diverge.
3. Same-name conflicting requirements reach explicit document-local conflict
   handling.
4. Equivalent requirements remain idempotent.
5. Document A cannot receive requirements or assets from document B.
6. Unattached element construction and mutation affect no document.
7. Native ODF semantic knowledge remains localized to the element model or an
   explicitly associated element-specific collaborator.
8. `OdtTemplate` has no concrete-type traversal cascade.
9. Physical assets remain package-owned and separate from `StyleContext`.
10. Post-materialization requirements are adopted before finalization.
11. Repeated save does not create semantic or serialized duplicates.
12. Existing legacy compatibility behavior remains explicit and bounded.

## 18. Explicit non-goals

D5B does not:

* implement an ownership protocol;
* add a `RequirementBag` or public requirement API;
* change `toDomNode()` implementations;
* migrate legacy structured values to document-local ownership;
* implement nested asset copying or a resource manager;
* redesign tables, table styles, lists, anchors, wrapping, or layout;
* fix CircularImage rendering;
* remove `StyleMapper` or legacy writer paths;
* implement named objects, FRAME-LAYOUT, STYLE-API-02, DOCUMENT-DEFAULTS, or
  01F-E.

## 19. Implementation slices after D5B

The following sequence separates structural refactoring from behavior changes
where practical:

### D5C — Ownership/composition capability

Expose the narrow internal semantic child view for Paragraph, RichText,
DrawTextBox, ListElement, RichTable, and RichTableCell adapters. Add tests for
ownership order without changing output.

### D5D — Conflict-preserving transitive collection

Implement one shared walker and ordered requirement records. Adopt styles into
the current `StyleContext` without map merging that can erase conflicts.

### D5E — Transitive physical resource preparation

Use the same ownership tree to discover nested image resources and prepare
package files/manifest entries through `OdtPackage`. Keep physical assets out of
`StyleContext`.

### D5F — Materialization lifecycle integration

Integrate pre-/post-materialization discovery for active `setElement()` paths,
including ImageElement and CircularImageElement final state. Preserve native
ODF output and repeated-save idempotency.

### D5G — Table/cell and compatibility closeout

Cover table/cell adapters and reconcile the active path with the legacy
structured-value path without silently implementing the full D6 migration.

The names are planning labels, not frozen class or method names.

## 20. Rejected or deferred alternatives

* **Uniform physical storage:** rejected because it would distort natural
  table/list/text-box models and provides no guarantee of correct semantics.
* **Independent recursive getters per family:** rejected as the primary model
  because D5A shows they diverge and repeat traversal logic.
* **Map-only collection:** rejected because it silently loses same-name
  conflicts before document registration.
* **Central OdtTemplate traversal:** rejected because it creates concrete-type
  coupling and a facade-level God object.
* **Immediate universal renderer extraction:** deferred because it increases
  abstraction cost before ownership semantics are proven.
* **Generic asset manager:** deferred and explicitly outside D5.
* **Mandatory permanent placement of all `toDomNode()` bodies:** not selected;
  native semantic competence is mandatory, literal method placement is open.

## 21. Open questions intentionally deferred

The following do not block the D5B decision but must be resolved during
implementation review:

* exact visibility and naming of the ownership capability;
* whether structural table adapters are lightweight views or dedicated
  internal objects;
* exact requirement-record representation;
* how to avoid duplicate native materialization when both content and styles
  DOMs are prepared;
* how legacy structured values can share the collector without changing D6
  lifecycle semantics;
* which repeated DOM mechanics justify extraction without becoming a renderer.

## 22. Decision summary

D5 selects an A+C hybrid: a uniform semantic ownership view and a narrow shared
traversal/materialization mechanism, with native ODF semantic competence kept
local to element types or their explicit element-specific collaborators.

No implementation is included in D5B, and no architecture option beyond this
bounded direction is selected for future unrelated milestones.

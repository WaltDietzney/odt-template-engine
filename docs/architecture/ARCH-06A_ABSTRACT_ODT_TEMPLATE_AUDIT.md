# ARCH-06A — AbstractOdtTemplate Responsibility and Inheritance Audit

## Status

Audit / research document.

This document records the evidence and architectural findings for ARCH-06A. It is not a Change Contract and does not authorize production-code changes.

ARCH-06 follows the completion of ARCH-02 through ARCH-05 and reassesses whether `AbstractOdtTemplate` still represents a coherent abstraction after major responsibilities were extracted into package-, document-, template-processing-, and structured-element services.

The central rule for this audit is:

> The name `AbstractOdtTemplate` is an architectural promise. If the class remains part of the library, its responsibility should be recognizable as the abstract basis of an ODT template rather than merely as a historical container for compatibility code.

## 1. Scope

ARCH-06A examines:

- the current inheritance model;
- state ownership and mirrored state;
- remaining responsibilities in `AbstractOdtTemplate`;
- responsibilities already extracted during ARCH-02 through ARCH-05;
- public and protected compatibility surfaces;
- repository-internal polymorphism;
- residual domain and legacy logic;
- whether the current class fulfills the semantic expectation created by its name.

ARCH-06A does not:

- remove `AbstractOdtTemplate`;
- introduce abstract methods;
- move production logic;
- repair legacy behavior;
- redesign styles;
- redesign template syntax;
- introduce named-object mutation or cloning APIs.

## 2. Current inheritance model

The repository currently has the effective inheritance chain:

```text
AbstractOdtTemplate
        ↓
    OdtTemplate
        ↓
PageLayoutOdtTemplate
```

`OdtTemplate` is the direct concrete production subclass of `AbstractOdtTemplate`. `PageLayoutOdtTemplate` specializes `OdtTemplate`, not `AbstractOdtTemplate` directly.

This matters because the abstract base class is not currently justified by multiple independent concrete implementations inside the repository.

More importantly, `AbstractOdtTemplate` is declared `abstract` but currently defines no abstract method contract. Its abstract nature therefore prevents direct instantiation but does not express which capabilities a subclass must provide.

That is the primary semantic finding of this audit.

## 3. Core finding

`AbstractOdtTemplate` is formally abstract but is not currently a clearly defined abstract template contract.

The class contains substantial shared implementation, but a subclass is not guided by an explicit set of abstract operations that establish a usable ODT-template lifecycle or document contract.

At the same time, the class does not own a complete standalone lifecycle. Important initialization and document/package ownership are provided by `OdtTemplate` and the services introduced by previous architecture milestones.

The result is a mismatch between name and current responsibility:

```text
Expected from the name:

AbstractOdtTemplate
    → common ODT-template contract
    → deliberate extension points
    → coherent shared template behavior

Current reality:

AbstractOdtTemplate
    → substantial historical shared implementation
    → compatibility/protected seams
    → residual ODF/style/template helpers
    → mirrored document state
    → no explicit abstract method contract
```

This mismatch is an architectural issue even if current behavior is correct and backward compatible.

## 4. Ownership after ARCH-02 through ARCH-05

Several responsibilities that historically belonged to the template hierarchy now have explicit owners.

### 4.1 Package and workspace ownership

`OdtPackage` owns package/workspace concerns.

It is responsible for package-level operations such as the extracted image-resource preparation introduced during ARCH-05H.

`AbstractOdtTemplate` should therefore not be treated as the owner of the package workspace merely because historical properties expose paths into it.

### 4.2 Document DOM ownership

`OdtDocumentContext` owns the document DOM state.

This includes the principal ODT XML DOMs used by the engine. The context is the architectural owner; legacy properties in the template hierarchy mirror access to that state.

### 4.3 Metadata and page-layout services

ARCH-03 extracted document services including:

- `MetadataManager`;
- `PageLayoutManager`.

`PageLayoutOdtTemplate` already demonstrates the newer model by delegating page-layout work to `PageLayoutManager`.

### 4.4 Template-language processing

ARCH-04 introduced the stateless `TemplateProcessor` for active template-language behavior such as placeholder normalization, variables, filters, conditions, and foreach processing.

Remaining methods in `AbstractOdtTemplate` that delegate to or overlap with template processing must therefore be classified as facade/compatibility behavior or residual logic rather than automatically treated as core ownership.

### 4.5 Structured materialization

ARCH-05 introduced `StructuredElementMaterializer`.

Constructed `OdtElement` content is materialized through that service. `setElement()` remains an important facade/orchestration operation, but the native structured-insertion semantics are no longer owned solely by the abstract base class.

### 4.6 Existing template-object resolution

ARCH-05 also introduced:

- `TemplateTargetResolver`;
- `TemplateTarget`.

Typed resolution of existing named template objects is therefore a separate concern from constructed structured materialization and from the abstract template hierarchy itself.

## 5. State ownership and compatibility mirroring

`AbstractOdtTemplate` still declares historical document-related state including properties such as:

- template path;
- temporary/workspace path;
- content DOM;
- styles DOM;
- debug state.

`OdtTemplate` initializes the modern package/context infrastructure and synchronizes package/context state into historical properties used by inherited implementation.

Conceptually, the current flow is:

```text
OdtPackage
    └── OdtDocumentContext
            │
            │ owns authoritative state
            ▼
OdtTemplate::synchronizePackageState()
            │
            │ mirrors compatibility state
            ▼
inherited AbstractOdtTemplate implementation
```

This mirrored state is compatibility-sensitive, but it is not the preferred architectural ownership model.

ARCH-06 must not silently convert mirrored state into a second source of truth.

Any future reduction of this mirroring requires characterization of lifecycle behavior, repeated render/save behavior, protected overrides, and document-region handling.

## 6. Remaining responsibility inventory

The current `AbstractOdtTemplate` contains several distinct responsibility clusters.

### 6.1 Style and ODF serialization/materialization

A substantial body of real domain logic remains around style and ODF DOM generation, including responsibilities represented by methods such as:

- namespace preparation;
- image-style injection;
- text-style creation;
- paragraph-style creation;
- automatic-style insertion;
- table-cell style-node handling;
- default list-style creation;
- default paragraph-style creation;
- style registration;
- bullet-indentation adjustment.

This is meaningful ODF behavior, but it is not automatically part of the semantic core of an abstract template base class.

It also overlaps conceptually with future style work, especially `STYLE-CONTEXT-01` and related style architecture. ARCH-06 must therefore avoid opportunistically redesigning this subsystem.

### 6.2 Structured-element facade/orchestration

Public structured insertion still enters through facade methods such as `setElement()`.

The facade coordinates compatibility-sensitive preparation such as styles and resources before delegating native DOM materialization to `StructuredElementMaterializer`.

This distinction is important:

```text
public/facade operation
        ↓
compatibility and preparation
        ↓
StructuredElementMaterializer
```

The existence of facade orchestration does not mean the base class should regain ownership of structured-materialization semantics.

### 6.3 Template-language compatibility and residual helpers

The class still contains template-related methods and helpers such as:

- DOM value replacement entry points;
- broken-placeholder repair wrappers;
- placeholder-node/text replacement helpers;
- template-variable extraction/introspection;
- legacy parsing helpers.

Some behavior delegates to `TemplateProcessor`; other behavior remains local.

These methods need classification during ARCH-06B and later characterization. They must not be moved merely because they appear old.

### 6.4 Protected DOM/compatibility seams

Protected methods in a public library are compatibility-sensitive because external subclasses may override them.

Repository-internal code already demonstrates real protected polymorphism: `PageLayoutOdtTemplate` overrides `adjustBulletIndentation()`.

Therefore protected dispatch is not a theoretical concern. Refactoring an implementation into a service must preserve facade dispatch where required, as previous architecture milestones already established.

### 6.5 Debugging

Debug-mode state and log access remain in the hierarchy.

This is valid functionality but does not by itself justify an abstract template base class. Its eventual architectural home is separate from the core ARCH-06 contract question.

## 7. Inheritance assessment

The current inheritance structure contains both legitimate compatibility value and historical coupling.

### 7.1 Legitimate value

The hierarchy provides:

- shared implementation;
- public facade behavior;
- protected extension seams;
- compatibility with existing subclasses;
- a place where common template behavior can be exposed consistently.

These are real reasons not to remove the class casually.

### 7.2 Historical coupling

`PageLayoutOdtTemplate` is a useful example.

Its page-layout responsibility now delegates to `PageLayoutManager`, which is consistent with composition-oriented architecture. However, the class also overrides `adjustBulletIndentation()` because of historical interactions between list indentation and page margins.

A page-layout specialization should not ideally need knowledge of a bullet-list correction hook. This is evidence of inheritance coupling caused by historical implementation details.

The behavior is compatibility-sensitive and must be characterized rather than repaired during the audit.

## 8. Name as architectural contract

A permanent architecture in which the class effectively means:

```php
abstract class AbstractOdtTemplate
{
    // historical compatibility implementation
}
```

is not considered a satisfactory target state.

If `AbstractOdtTemplate` remains part of the public architecture, a developer should be able to understand why it is the abstract basis of an ODT template.

That implies a future design should make at least the following concepts explicit:

- what common ODT-template capabilities exist;
- what a subclass must provide;
- what state the base class may legitimately own or access;
- what protected extension points are intentional;
- what common implementation genuinely belongs to all template implementations.

The exact API and abstract methods are not decided by ARCH-06A.

## 9. Candidate long-term outcomes

The audit leaves two legitimate architectural outcomes open.

### Outcome A — Real abstract template base class

`AbstractOdtTemplate` is retained and evolved into a coherent abstract contract with carefully selected common implementation and explicit extension points.

Conceptually:

```text
AbstractOdtTemplate
    ├── minimal common template contract
    ├── document/context access contract
    ├── deliberate protected extension points
    └── selected common facade behavior
             ↓
         OdtTemplate
             ↓
    specialized OdtTemplate variants
```

This is the direction currently favored by the audit because the inheritance model can be meaningful if the base-class contract becomes explicit.

### Outcome B — Remove the abstraction

If ARCH-06B cannot identify a coherent contract that justifies inheritance, the long-term architecture should prefer composition and eventually retire the abstract base class through a backward-compatible migration strategy.

The class must not be retained indefinitely solely because it already exists.

ARCH-06A does not select either outcome as a Change Contract.

## 10. Known legacy/debt findings

### 10.1 Compatibility state mirror

The template hierarchy mirrors state whose architectural owner is now `OdtPackage` / `OdtDocumentContext`.

This should be treated as compatibility infrastructure, not new state ownership.

### 10.2 Residual mixed responsibilities

Style serialization, template helpers, structured facade behavior, debugging, and DOM compatibility logic remain mixed in the base class.

Their presence is evidence for contract clarification, not authorization for a broad extraction rewrite.

### 10.3 Suspicious table-cell style helper

During the audit, `ensureTableCellStyleNodesExist(array $styleNodes)` was observed to reference `$styleMap` in its body rather than the parameter name suggested by its signature.

This may indicate a legacy bug, stale path, or code that is not currently exercised as expected.

ARCH-06A deliberately does not repair it.

Before any change to this path:

1. establish whether it is reachable;
2. add characterization if relevant;
3. document current behavior;
4. separate any bug fix from architecture refactoring.

## 11. Compatibility risks

ARCH-06 must treat the following as compatibility-sensitive:

- public methods inherited from `AbstractOdtTemplate`;
- protected methods that external subclasses may override;
- repository-internal protected polymorphism;
- package/document lifecycle behavior;
- repeated `render()` / `save()` behavior;
- processing of both `content.xml` and `styles.xml`;
- structured insertion;
- image/resource preparation;
- text-box paths;
- legacy replacement behavior;
- samples and documented APIs.

Removing or bypassing a protected facade can be a behavioral break even when the resulting XML appears equivalent.

## 12. Explicit non-goals and deferred work

ARCH-06A does not pull the following work into ARCH-06:

- `STYLE-CONTEXT-01`;
- `STYLE-API-02`;
- `TEMPLATE-FORMAT-PRESERVATION-01`;
- `TEMPLATE-AUTHORING-UX-01`;
- Exact Clone;
- Template Clone / Template Instance;
- Structural Clone;
- named text-box replacement;
- table-target mutation;
- generalized drawing-object operations;
- whole-object replacement;
- removal;
- generic public named-object APIs;
- generalized target capability frameworks.

## 13. Evidence map

| Finding | Primary repository evidence |
| --- | --- |
| Abstract class has no explicit abstract method contract | `src/AbstractOdtTemplate.php` |
| Direct production subclass | `src/OdtTemplate.php` |
| Specialized subclass and protected override | `src/PageLayoutOdtTemplate.php` |
| Package/workspace ownership | `src/OdtPackage.php` |
| Document DOM ownership | `src/OdtDocumentContext.php` |
| Template-language extraction | `src/Template/TemplateProcessor.php` |
| Structured materialization extraction | `src/Document/StructuredElementMaterializer.php` |
| Typed named-target resolution | `src/Document/TemplateTargetResolver.php`, `src/Document/TemplateTarget.php` |
| Page-layout extraction | `src/Document/PageLayoutManager.php` |
| Metadata extraction | `src/Document/MetadataManager.php` |
| Style responsibilities remaining in hierarchy | `src/AbstractOdtTemplate.php`, `src/Utils/StyleMapper.php`, `src/Utils/StyleWriter.php` |
| Compatibility expectations | API/integration/characterization tests under `tests/` and public samples |
| ARCH-05 structured-element decisions | `docs/architecture/ARCH-05A_STRUCTURED_ELEMENTS_AUDIT.md`, `ARCH-05B_ELEMENT_IDENTITY_AND_REPLACEMENT_SEMANTICS.md`, `ARCH-05C_CHANGE_CONTRACT.md` |

## 14. Questions for ARCH-06B

ARCH-06B should define the intended base-class contract before production changes begin.

It must answer at least:

1. What is the minimal semantic responsibility of an abstract ODT template?
2. Which capabilities must every concrete template implementation provide?
3. Should document-context access be part of the explicit base-class contract?
4. Which existing public methods are genuine common template operations?
5. Which protected methods are intentional extension points rather than accidental implementation details?
6. Which protected compatibility facades must remain even after internal delegation to services?
7. Which state may `AbstractOdtTemplate` legitimately own?
8. Which state must only be accessed through `OdtPackage` / `OdtDocumentContext`?
9. Which remaining methods are residual domain logic that should eventually move to dedicated services?
10. Which style-related responsibilities must be deferred to `STYLE-CONTEXT-01` rather than changed in ARCH-06?
11. Is `AbstractOdtTemplate → OdtTemplate → specialized variants` the intended public inheritance model?
12. If a coherent contract cannot be defined without artificial abstractions, should the long-term direction instead be removal of `AbstractOdtTemplate`?

## 15. Recommendation

Proceed to ARCH-06B as a design/change-contract phase, not an implementation phase.

The current audit favors attempting to define `AbstractOdtTemplate` as a real abstract ODT-template base class, but that preference is conditional: the contract must emerge from actual repository semantics and compatibility requirements rather than from the class name alone.

ARCH-06B should verify this audit against current code and tests, define the intended base-class responsibility, and explicitly reject speculative abstractions.

Semantics before implementation.

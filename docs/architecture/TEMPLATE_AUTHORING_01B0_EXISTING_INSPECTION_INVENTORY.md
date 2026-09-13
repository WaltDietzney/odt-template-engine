# TEMPLATE-AUTHORING-01B0 — Existing Inspection Inventory & Semantic Boundaries

Status: COMPLETE / CHARACTERIZATION CLOSED / NO API DECISION

## Purpose

TEMPLATE-AUTHORING-01B must not start by inventing a third inspection API.

The repository already contains two public inspection surfaces with different semantics:

```text
OdtTemplate::inspect()
    -> DocumentInspection

OdtTemplate::inspectTemplateStructure()
    -> TemplateStructureInspection
```

B0 characterizes those existing contracts, their lifecycle semantics, document-part coverage, diagnostic models, and blind spots before any unified template contract is designed.

## 1. Current public inspection surfaces

### 1.1 OdtTemplate::inspect()

Current signature:

```php
public function inspect(): DocumentInspection
```

Semantics:

- inspects the current mutable document context;
- reads live content.xml and styles.xml DOMs where supported;
- creates a fresh immutable snapshot on every call;
- does not expose DOM nodes;
- reports native named structures and structural diagnostics.

Current native object coverage:

- Sections;
- Bookmarks;
- Tables;
- Frames.

The descriptors expose bounded document semantics rather than mutable target handles.

### 1.2 OdtTemplate::inspectTemplateStructure()

Current signature:

```php
public function inspectTemplateStructure(): TemplateStructureInspection
```

Semantics:

- reads the original source archive through OdtPackage::sourceDom('content.xml');
- does not inspect the current mutable working DOM;
- is stable across render-time mutations;
- inspects visible template-language expressions;
- does not mutate or normalize the source DOM;
- currently inspects only source content.xml, not source styles.xml.

This is fundamentally different from inspect().

## 2. Lifecycle distinction

The repository currently contains two valid but different temporal models.

### Document inspection

```text
current working document state
    -> inspect()
    -> DocumentInspection
```

After programmatic mutations, structured insertion, rendering, or other document changes, a new inspect() call observes the live document state.

### Template structure inspection

```text
original archive state
    -> sourceDom(content.xml)
    -> inspectTemplateStructure()
    -> TemplateStructureInspection
```

A later render does not redefine the authored source-template contract.

A future unified template inspection must explicitly state whether it describes the authored source template contract, the current rendered document state, or both as distinct views. It must not silently merge source and live semantics.

## 3. DocumentInspection current model

DocumentInspection is an immutable snapshot containing:

```text
sections
bookmarks
tables
frames
diagnostics
```

It supports typed lists, first-by-name accessors, stable toArray() serialization, and machine-readable diagnostics.

### 3.1 SectionDescriptor

Current Section inspection exposes native name, document part, child summary, nested named native objects, and diagnostics.

The nested-object summary is already an important ownership primitive. It can report nested Sections, Bookmarks, Tables, and Frames. This makes Section inspection a strong basis for future template-scope modeling.

### 3.2 BookmarkDescriptor

Current Bookmark inspection distinguishes native topology:

- collapsed;
- inline;
- paragraph-spanning;
- list-spanning;
- table-spanning;
- mixed-block;
- malformed.

It also reports bookmark text where meaningful and diagnostics for malformed/duplicate markers. This is more than simple name discovery; it already captures structural risk.

### 3.3 TableDescriptor

Current Table inspection exposes native name, document part, row count, column count, and containing Section.

The containing-Section relationship is directly useful for future template ownership projection.

### 3.4 FrameDescriptor

Current Frame inspection exposes native name, document part, payload type, width, height, and containing Section.

Again, native containment is already available.

## 4. DocumentInspector diagnostic model

Current native diagnostics include at least:

- missing native name;
- duplicate native name;
- duplicate bookmark markers;
- unpaired bookmark markers.

Important current rule: names are unique per native object type, not globally across all object types.

A Section, Table, and Frame may therefore each carry the same textual name without a global collision diagnostic. This is an established semantic choice and should not be changed casually in unified inspection.

## 5. Current document-part coverage is asymmetric

B0 code review reveals an important asymmetry.

### Sections

Currently inspected only from content.xml.

### Bookmarks

Currently inspected only from content.xml.

### Tables

Currently inspected from content.xml and styles.xml.

### Frames

Currently inspected from content.xml and styles.xml.

This means page/master-style-owned content such as headers/footers can currently expose named Tables and Frames through inspect(), but named Sections and Bookmarks in styles.xml are not part of the current inspection result.

This may be intentional bounded scope or simply historical coverage. B0 does not decide. It must be explicit before a unified template contract claims cross-document-part discovery.

## 6. TemplateStructureInspection current model

TemplateStructureInspection is an immutable source-template snapshot containing logical template expressions and template topology diagnostics.

It supports all expressions, diagnostics, valid(), repairable items, unsafe items, lookup by variable, lookup by logical scope, and stable toArray(). It does not expose DOM nodes.

## 7. TemplateExpressionDescriptor current semantics

Each projected expression can report raw token text, expression kind, variable name, filter name, filter option, logical scope, fragment count, style names, intersecting bookmark names, semantic classification, physical-normalization classification, and expression diagnostics.

Current expression kinds include:

```text
SCALAR
FILTERED_SCALAR
SPECIAL
CONDITION_OPEN
CONDITION_ELSE
CONDITION_END
FOREACH_OPEN
FOREACH_END
UNSUPPORTED
```

The projector is aware of ODF text-flow boundaries including text:p, text:h, text:list-item, table cells, Sections, and draw text boxes. It can therefore identify expressions that are logically valid but physically unsafe to normalize.

## 8. TemplateStructureInspector diagnostic model

Current template diagnostics include at least malformed/unbalanced expressions, expression crossing a text-flow boundary, style conflict across expression fragments, bookmark intersection with an expression, and unsupported template syntax.

The model distinguishes VALID, REPAIRABLE, and UNSAFE, and separately tracks physical normalization risk. This separation is valuable and should survive any higher-level inspection model.

## 9. Inspection grammar and runtime grammar currently diverge

B0 identifies a concrete semantic mismatch.

The runtime condition evaluator supports expressions such as:

```text
gender=="female"
status!="inactive"
amount>=100
```

The current TemplateExpressionProjector recognizes visible condition controls only when the condition body is a simple word-like token:

```text
{{#if:active}}
{{#ifnot:photo}}
{{#elseif:other}}
```

A token such as:

```text
{{#if:gender=="female"}}
```

is currently projected as UNSUPPORTED / UNSAFE even though the runtime evaluator can process that condition syntax.

This means current template inspection is not yet a complete description of actual runtime-supported template semantics. Unified inspection must not claim completeness until grammar ownership is reconciled.

## 10. TemplateStructureInspection currently covers source content.xml only

The current facade explicitly calls sourceDom('content.xml'). Therefore visible template expressions authored in styles.xml are currently outside inspectTemplateStructure().

This is important because previous architecture milestones established that headers and footers are normal structured content domains under page/master styles.

A unified template contract intended to drive generic rendering cannot silently ignore template inputs declared in page-owned content.

Whether source styles.xml should be scanned by the same projector or a separate part-aware projection remains a B1 design question.

## 11. Source DOM versus normalized live DOM

The source template inspector intentionally reads the archive directly and bypasses live load-time normalization. This is valuable because it preserves authored physical topology for diagnostics.

The working document path, by contrast, runs template normalization during prepareLoadedTemplate().

```text
source inspection
    -> authored physical topology

live document state
    -> normalized/mutable processing topology
```

A future unified model must preserve this distinction where physical authoring diagnostics matter.

## 12. Current scope model is incomplete but promising

Current template-expression scope is represented as strings such as text:p, text:h, section:<name>, and similar text-flow scopes.

Current native object inspection separately reports containing Section relationships for Tables and Frames and nested object references for Sections.

These are two partially overlapping concepts:

```text
expression text-flow scope
native structural ownership
```

B1/B2 should not collapse them accidentally.

A future template contract likely needs explicit relationships between binding location, native owner, data scope, document part, and control declaration.

## 13. Diagnostics are currently split into two type systems

Native document inspection uses OdtTemplateEngine\Document\InspectionDiagnostic.

Template structure inspection uses OdtTemplateEngine\Template\TemplateStructureDiagnostic.

They differ in fields and classification semantics.

Document diagnostics focus on code, severity, target type, and target name.

Template diagnostics additionally model classification, repairability, expression, and scope.

A unified result must decide whether to compose both diagnostic families unchanged, introduce a higher-level diagnostic envelope, or normalize them into one shared diagnostic model.

B0 makes no decision.

## 14. The current public inspect() name is already occupied

The conceptual product target discussed for TEMPLATE-AUTHORING-01 used:

```php
$schema = $template->inspect();
```

But inspect() already exists publicly and returns DocumentInspection.

Changing its return type or semantic meaning would be a public API compatibility change.

Therefore B1 must treat the current method as reserved compatibility surface.

Possible future strategies include keeping inspect() as native-document inspection and introducing a distinct template-contract method, evolving DocumentInspection compatibly only if its public contract can be preserved, or adding a new facade that exposes both document and template views.

No strategy is approved in B0.

## 15. What unified inspection should not become

The evidence argues against a monolithic bag of names.

A future result should not flatten names, variables, controls, tables, frames, and native objects into one undifferentiated collection.

The engine already knows meaningful categories and relationships.

The unified contract should preserve distinctions such as:

```text
input dependency
template expression
control declaration
native object identity
native containment
document part
diagnostic
```

This is especially important for generic integrations that may build forms or mappings from the inspected contract.

## 16. B0 automated characterization

The active B0 characterization test is:

```text
tests/Integration/TemplateAuthoring01B0InspectionBoundaryCharacterizationTest.php
```

It freezes four important boundaries:

1. source template-structure inspection remains stable across render-time mutation;
2. source styles.xml expressions are currently invisible to inspectTemplateStructure();
3. comparison conditions supported by runtime evaluation are currently classified as unsupported by structure inspection;
4. native object document-part coverage differs by object type.

The tests characterize current behavior; they do not approve it as the future target.

## 17. B0 architecture conclusions

The repository already contains substantial inspection architecture.

The missing capability is not an inspector in the abstract. It is a coherent template contract model that composes existing evidence without erasing important distinctions.

The strongest reusable pieces are:

```text
DocumentInspector
    -> native object identity/topology/containment

TemplateExpressionProjector
    -> logical visible expression projection

TemplateStructureInspection
    -> authored expression topology + diagnostics

OdtPackage::sourceDom()
    -> immutable source-template view

OdtDocumentContext
    -> current mutable document view
```

The most important unresolved questions are source contract vs. live document state, content.xml vs. styles.xml coverage, runtime grammar vs. inspection grammar, text-flow scope vs. native ownership, two diagnostic models, and public compatibility of the already-existing inspect() method.

## 18. Next step

After the B0 characterization test is confirmed, proceed to:

```text
TEMPLATE-AUTHORING-01B1 — Unified Contract Model Design
```

B1 should remain semantics-first.

It should define the conceptual result model before approving a public method name or implementation class.

The first design question should be:

> What distinct concepts must a machine-readable ODT template contract represent so that a generic application can map data and understand supported template control without reconstructing document layout?

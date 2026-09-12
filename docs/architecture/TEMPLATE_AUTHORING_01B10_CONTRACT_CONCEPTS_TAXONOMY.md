# TEMPLATE-AUTHORING-01B1.0 — Contract Concepts & Taxonomy

Status: COMPLETE / DESIGN BASELINE ACCEPTED / NO PRODUCTION CHANGE

## Purpose

Define the semantic concepts that a machine-readable ODT template contract must represent before choosing public API names, result classes, serialization shapes, or implementation services.

The governing question is:

> What must a generic application know about an authored ODT template in order to map data and understand supported rendering semantics without reconstructing Writer layout in PHP?

B1.0 is a taxonomy pass.

It does not approve:

- a new public inspection method;
- a replacement for `OdtTemplate::inspect()`;
- concrete DTO/class names;
- declarative control execution;
- native field binding;
- a high-level render pipeline.

Those decisions belong to later B/C/D/E slices and a Change Contract.

## 1. Governing distinction: evidence, meaning, and requirement

A unified template contract must not flatten three different concepts.

### 1.1 Authored evidence

What physically exists in the source ODT:

- a `{{name}}` expression;
- a named Section;
- a Bookmark;
- a named Table;
- a named Frame;
- a native Writer field;
- a Section name that resembles a declarative control.

This is source-document evidence.

### 1.2 Template meaning

What supported engine semantics assign to that evidence:

- scalar binding;
- filtered scalar binding;
- condition dependency;
- repetition declaration;
- native addressable object;
- native field binding;
- structural control;
- unsupported/malformed declaration.

Meaning is not identical to physical XML representation.

### 1.3 Data requirement

What application data is required or referenced because of that meaning:

- scalar value;
- collection;
- boolean/truthy condition dependency;
- comparison operand dependency;
- nested row-local dependency;
- optional value.

A native object name alone is not a data requirement.

This yields the first core model:

```text
authored evidence
      ↓ interpretation
template meaning
      ↓ dependency projection
data requirements
```

The contract must preserve enough provenance to explain how a requirement was derived.

## 2. Root concept: Template Contract

The conceptual root is a read-only description of the **authored source template**, not the current rendered document state.

At taxonomy level it contains six distinct concept families:

```text
Template Contract
├── bindings
├── controls
├── native objects
├── data requirements
├── relationships / scopes
└── diagnostics
```

A seventh concern, provenance/location, is cross-cutting rather than a peer collection.

This is deliberately not yet a PHP class design.

## 3. Bindings

A binding is authored template semantics that requests or transforms a value at a content location.

### 3.1 Scalar binding

Example:

```text
{{name}}
```

Meaning:

```text
bind scalar dependency "name" here
```

### 3.2 Filtered scalar binding

Example:

```text
{{upper:name}}
{{date:birthdate|d.m.Y}}
```

Meaning:

```text
read a scalar dependency
apply a declared supported transformation
materialize the result at this location
```

The filter is binding semantics. The input dependency remains conceptually distinct.

### 3.3 Special/structural placeholder binding

Existing special expressions such as `nl2br`, list placeholders, or other bounded structural substitutions must remain distinguishable from ordinary scalar text binding where their output semantics differ.

B1.0 does not redesign those features.

### 3.4 Native field binding

Reserved taxonomy category for Phase C.

A supported Writer field may eventually represent a binding without visible `{{...}}` syntax.

It belongs in the unified contract only after Phase C defines which native field families have binding semantics.

## 4. Controls

A control changes whether or how a bounded template structure participates in rendering.

Controls are not bindings.

### 4.1 Conditional control

Conceptual forms:

```text
if
ifnot
elseif
else
```

A condition has:

- a condition expression;
- one or more data dependencies;
- an owned structural scope;
- branch semantics.

Classic paragraph markers and future native declarations may be different authored representations of the same high-level control concept, but B1.0 does not assert that their execution semantics are already equivalent.

### 4.2 Repetition control

Conceptual form:

```text
foreach
```

A repetition has:

- a collection dependency;
- an owned structural prototype/scope;
- a row-local data scope;
- nesting relationships;
- identity implications for repeated native objects.

### 4.3 Declarative native control

Reserved taxonomy category for Phase D.

Example research direction:

```text
Section name: #foreach:experience
Section name: #if:photo
```

B inspection may eventually recognize such declarations as template evidence before D executes them.

Recognition and execution are separate capabilities.

### 4.4 Classic control

Existing visible `{{#if:...}}` and `{{#foreach:...}}` controls remain a distinct authored representation because A1 proved compatibility-specific structural and lifecycle behavior.

Unified inspection must not erase that provenance.

## 5. Native objects

A native object is an addressable or structurally meaningful ODF/Writer object discovered in the authored source.

Current established categories include:

- Section;
- Bookmark;
- Table;
- Frame.

Future bounded categories may include supported native fields or other named Writer objects.

### 5.1 Native object is not automatically an input

Examples:

```text
Section name: Profile
Table name: ExperienceTable
Bookmark name: SignatureStart
Frame name: PortraitFrame
```

None of these names, by themselves, imply application data named `Profile`, `ExperienceTable`, `SignatureStart`, or `PortraitFrame`.

This rule is essential for generic integrations.

### 5.2 Native object may acquire template meaning

A native object can become relevant to the template contract through supported semantics.

Examples:

- a Section carries a declarative `#foreach:experience`;
- a supported field declares a value dependency;
- a named Frame is an explicit structured insertion target;
- a Bookmark participates in a supported target/binding declaration.

The contract must distinguish native identity from template role.

## 6. Data requirements

A data requirement is a logical dependency of the template on application data.

It is derived from supported bindings and controls, not from every native name.

### 6.1 Scalar requirement

Examples:

```text
name
birthdate
company
```

Possible sources:

- scalar placeholder;
- filtered scalar placeholder;
- supported native field;
- condition operand.

### 6.2 Collection requirement

Example:

```text
experience[]
```

Source:

- foreach/repetition control.

A collection requirement introduces a row-local scope.

### 6.3 Condition dependency

A condition may reference one or more values.

Simple example:

```text
#if:photo
    -> dependency: photo
```

Comparison example:

```text
#if:gender=="female"
    -> dependency: gender
    -> literal: "female"
```

A literal is not an application input.

### 6.4 Nested requirement

Inside:

```text
foreach experience
    company
    role
    if current
```

the logical requirements are not three unrelated global keys.

They are conceptually:

```text
experience[]
    ├── company
    ├── role
    └── current
```

This is why a flat `variables()` list is insufficient for the eventual high-level contract.

### 6.5 Required versus referenced

B1.0 deliberately does **not** equate "referenced" with "mandatory form field".

A template can reference data that is optional, conditionally relevant, defaultable, or only meaningful inside a collection row.

The contract therefore needs a dependency concept before it can safely claim validation-requiredness.

Form-generation metadata such as labels, widgets, validation rules, or human-friendly descriptions is outside B1.0 unless later authoring semantics explicitly provide it.

## 7. Relationships and scope

Relationships are first-class contract semantics.

The same named concepts are insufficient without knowing how they relate.

B1.0 distinguishes at least four scope dimensions.

### 7.1 Document-part scope

Where the evidence is authored:

```text
content.xml
styles.xml
```

Potentially refined later into body/header/footer/master-page ownership where reliable source semantics exist.

### 7.2 Physical/text-flow scope

Existing `TemplateExpressionProjector` scope:

- paragraph;
- heading;
- list item;
- table cell;
- Section;
- text box;
- other bounded text-flow container.

This is primarily about physical expression topology and normalization safety.

### 7.3 Native ownership scope

Which native object contains another object or expression.

Example:

```text
Section Experience
├── Table ExperienceTable
└── Frame CompanyLogo
```

This is structural ODF ownership.

### 7.4 Data scope

Which application-data environment resolves a dependency.

Example:

```text
global
└── foreach experience row
    ├── company
    └── if current
```

This is template-language semantics, not DOM containment.

These scope dimensions may correlate, but they are not interchangeable.

## 8. Provenance and location

Every higher-level contract concept should remain traceable to authored evidence.

Conceptual provenance may need to express:

- document part;
- authored representation kind;
- native owner;
- logical scope;
- raw expression/declaration;
- source identity/name;
- physical-fragment information where relevant.

The contract should be explainable:

> Why does the template claim that it needs `experience[].company`?

A useful answer is:

```text
because a scalar binding {{company}}
occurs inside the prototype owned by
the foreach declaration for experience
on this authored structure
```

B1.0 does not require XPath or DOM-node exposure in the public contract.

## 9. Diagnostics

Diagnostics are part of the contract, not an incidental logger output.

A template can be inspectable while containing unsupported or unsafe semantics.

The taxonomy needs diagnostics for at least:

- malformed visible expressions;
- unsupported expressions;
- unsafe physical expression topology;
- malformed native declarations;
- duplicate native identities;
- unsupported declaration/object combinations;
- ambiguous ownership;
- invalid nesting;
- unresolved/ambiguous data scope;
- document-part coverage limitations.

### 9.1 Diagnostic provenance

Diagnostics should identify the concept/evidence they concern where possible.

### 9.2 Diagnostic severity and semantic classification

B0 found two existing diagnostic systems with different semantics.

B1.0 does not merge them yet.

The unified contract needs a way to present both structural and template-semantic problems without losing useful existing classifications such as:

```text
VALID
REPAIRABLE
UNSAFE
```

and native severity.

The envelope/normalization decision belongs to a later B1 slice.

## 10. Derived contract versus raw inventory

The unified contract should contain both discovered facts and derived semantics, but it must distinguish them.

Example:

```text
Authored facts:
- Section named #foreach:experience
- scalar expression {{company}} inside that Section

Derived semantics, once supported:
- repetition control over collection experience
- row-local scalar dependency experience[].company
```

This distinction prevents inspection from pretending that a future syntax is already executable merely because it is recognizable.

## 11. Recognition states

A useful taxonomy requires more than "found/not found".

A piece of authored evidence can conceptually be:

```text
KNOWN_AND_SUPPORTED
KNOWN_BUT_NOT_YET_EXECUTABLE
MALFORMED
UNSUPPORTED
AMBIGUOUS
```

Names are provisional taxonomy labels, not approved enums.

This is especially important during TEMPLATE-AUTHORING-01 because B may discover declarations whose execution is only implemented in C or D.

## 12. Concept matrix

| Concept | Authored evidence | Implies data dependency? | Has native identity? | Can own scope? |
| --- | --- | --- | --- | --- |
| Scalar binding | `{{name}}` | Yes: scalar | No | No |
| Filtered binding | `{{upper:name}}` | Yes: scalar | No | No |
| Classic IF | visible control markers | Yes: condition refs | No | Yes, compatibility semantics |
| Classic FOREACH | visible control markers | Yes: collection | No | Yes, compatibility semantics |
| Native Section | `text:section` + name | No, by itself | Yes | Yes |
| Bookmark | bookmark markers + name | No, by itself | Yes | Bounded range |
| Named Table | `table:table` + name | No, by itself | Yes | Native subtree |
| Named Frame | `draw:frame` + name | No, by itself | Yes | Native subtree |
| Native field | Writer/ODF field | Only if C defines binding semantics | Depends on field family | Usually local content semantics |
| Declarative Section control | supported semantic Section name | Yes, by declaration | Yes: carrier Section | Yes |

The matrix is conceptual and will evolve as C/D define bounded semantics.

## 13. Example: professional CV template

Consider an authored template with:

```text
{{name}}
{{email}}

Section: #if:photo
    Frame: PortraitFrame

Section: #foreach:experience
    {{from}}
    {{to}}
    {{role}}
    {{company}}

    Section: #if:current
        Current position
```

A useful machine-readable contract should eventually be able to express conceptually:

```text
global dependencies
├── name : scalar
├── email : scalar
└── photo : condition dependency

controls
├── if photo
│   └── owns native Section #if:photo
└── foreach experience
    ├── collection dependency: experience
    ├── owns native Section #foreach:experience
    └── row scope
        ├── from : scalar
        ├── to : scalar
        ├── role : scalar
        ├── company : scalar
        └── current : condition dependency

native objects
├── Section #if:photo
│   └── Frame PortraitFrame
└── Section #foreach:experience
    └── Section #if:current
```

This is materially more useful to a generic integration than:

```text
variables = [name, email, from, to, role, company]
sections = [#if:photo, #foreach:experience, #if:current]
frames = [PortraitFrame]
```

The second representation loses data scope and semantic relationships.

## 14. What the contract must not infer

The contract must not silently infer application semantics from presentation or arbitrary names.

Examples of prohibited inference without explicit template semantics:

- `PortraitFrame` means there must be an input named `portrait`;
- a table named `ExperienceTable` means there is an `experience` collection;
- bold text means required;
- heading text becomes a form label;
- Bookmark name becomes a scalar field automatically;
- Section name `Profile` becomes a data object named `profile`;
- repeated-looking layout implies foreach.

The engine should report what the template declares, not guess what the author intended.

## 15. Implications for generic applications

The taxonomy supports the intended integration model:

```text
application data
    ↓
mapping layer
    ↓
template contract
    ↓
high-level render orchestration
```

A CMS plugin or form-driven application should be able to answer:

- Which logical data dependencies does this template declare?
- Which are global and which belong to collections?
- Which controls use those dependencies?
- Which native objects carry structural meaning?
- Are there unsupported or unsafe declarations?
- Which document parts contain relevant template semantics?

It should not need to understand ODF XML layout itself.

## 16. Boundary with form-schema generation

Unified template inspection is **not automatically a complete form schema**.

A form schema commonly needs information the current template does not necessarily declare:

- human-readable labels;
- input widget type;
- validation constraints;
- required/optional policy;
- choice lists;
- localization;
- help text;
- domain-specific data types.

The template contract can be a strong mapping/introspection source without pretending to contain those application concerns.

A later authoring feature could add explicit metadata if justified, but B1.0 does not invent it.

## 17. Boundary with render execution

Inspection describes semantics.

Rendering executes supported semantics.

B must therefore be able to describe concepts that later phases implement without causing inspection itself to mutate the document.

Conceptually:

```text
inspect source
    -> contract

map application data
    -> contract dependencies

render
    -> execute supported bindings/controls
```

This separation is important for validation, tooling, authoring diagnostics, and generic integrations.

## 18. Boundary with current DocumentInspection

`DocumentInspection` remains the current live native-document view.

The future template contract may reuse or compose its descriptors, but B1.0 does not redefine `inspect()`.

The source template contract and live document inspection remain distinct semantic views unless a later compatibility design explicitly composes them.

## 19. Candidate conceptual hierarchy

The following is a working taxonomy, not a class diagram:

```text
TemplateContract
│
├── Binding
│   ├── ScalarBinding
│   ├── FilteredBinding
│   ├── SpecialBinding
│   └── NativeFieldBinding          [Phase C]
│
├── Control
│   ├── ConditionalControl
│   └── RepetitionControl
│       authored as:
│       ├── ClassicControl
│       └── NativeDeclaration       [Phase D]
│
├── NativeObject
│   ├── Section
│   ├── Bookmark
│   ├── Table
│   ├── Frame
│   └── NativeField                [bounded in C]
│
├── DataRequirement
│   ├── ScalarDependency
│   ├── CollectionDependency
│   └── ConditionDependency
│
├── Relationship
│   ├── DocumentPart
│   ├── PhysicalScope
│   ├── NativeOwnership
│   └── DataScope
│
└── Diagnostic
```

The critical design rule is:

> Do not encode a concept as a subtype merely because two concepts happen to share a name or XML location.

For example, a Section is a native object; a foreach is a control; the Section may **carry** the foreach declaration. They are related concepts, not the same concept.

## 20. Open questions for B1.1

B1.0 deliberately leaves the following unresolved:

1. What is the exact root result model?
2. Should native object descriptors be embedded, referenced, or adapted from `DocumentInspection`?
3. How should source `content.xml` and `styles.xml` be represented?
4. What stable identity should contract items use without exposing DOM nodes?
5. How are logical dependencies deduplicated while preserving all declaration sites?
6. How should nested data paths/scopes be represented?
7. How should classic-control compatibility provenance be represented?
8. How should the two current diagnostic systems be composed?
9. What recognition/support-state model is required?
10. How can the model remain extensible for Phase C native fields and Phase D declarative controls without speculative abstractions?
11. What should be serialized by `toArray()` for tooling?
12. Which current descriptors can be reused unchanged without coupling source-template semantics to live-document inspection?

## 21. Proposed B1 sequence

After taxonomy review:

```text
B1.0  Contract Concepts & Taxonomy
  ↓
B1.1  Contract Graph / Ownership & Data-Scope Model
  ↓
B1.2  Source-Part Coverage & Provenance Model
  ↓
B1.3  Diagnostics / Support-State Composition
  ↓
B1.4  Compatibility & Public Surface Design
  ↓
B1     Design Synthesis / Change Contract readiness
```

This sequence intentionally postpones method naming and implementation until the semantic graph is understood.

## 22. B1.0 design thesis

The unified template contract should not be a larger inventory.

It should be a **typed, source-derived semantic graph** connecting:

```text
authored evidence
    ↕
bindings and controls
    ↕
native structural ownership
    ↕
data dependencies and scopes
    ↕
diagnostics
```

That model is what enables the long-term goal of a well-authored LibreOffice template carrying enough machine-readable contract information that a generic application can map data and invoke high-level rendering without rebuilding document layout in PHP.

No public API or production implementation is approved by B1.0.

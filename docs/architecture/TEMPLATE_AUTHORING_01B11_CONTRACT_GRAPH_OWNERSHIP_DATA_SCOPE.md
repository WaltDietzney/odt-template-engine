# TEMPLATE-AUTHORING-01B1.1 — Contract Graph / Ownership & Data-Scope Model

Status: ACTIVE DESIGN / NO PUBLIC API DECISION / NO PRODUCTION CHANGE

## Purpose

Define how the concepts identified in B1.0 relate to one another.

B1.0 established that unified template inspection must distinguish authored evidence, template meaning, native objects, data requirements, scopes, provenance, and diagnostics.

B1.1 answers the next question:

> What graph relationships are required to explain which authored structure owns template semantics and in which data environment each dependency resolves?

This is a semantic graph design. It is not a PHP object graph, DOM wrapper, execution plan, or approved serialization format.

## 1. Governing rule: one template, multiple overlapping graphs

A Writer template has several valid structures at the same time.

They must not be collapsed into one tree.

At minimum, unified inspection needs to represent:

```text
A. source/native containment graph
B. template-control ownership graph
C. data-scope graph
D. dependency/reference graph
```

These graphs overlap but answer different questions.

Example:

```text
Section #foreach:experience
    Table ExperienceTable
        {{company}}
```

Native containment says:

```text
Section
└── Table
    └── expression location
```

Control ownership says:

```text
foreach experience
└── owns repetition prototype Section
```

Data scope says:

```text
global
└── experience row
    └── company
```

Dependency projection says:

```text
foreach control -> experience[]
{{company}} binding -> experience[].company
```

No single parent/child relation can correctly represent all four meanings.

## 2. Graph node families

B1.1 uses the following conceptual node families.

### 2.1 Evidence node

Represents an authored declaration or expression site.

Examples:

- one visible `{{company}}` expression;
- one classic `{{#foreach:experience}}` marker;
- one native Section whose name is `#foreach:experience`;
- one Writer field.

Evidence nodes preserve declaration-site multiplicity.

If `{{name}}` appears five times, there are five evidence sites even though there may be only one logical data dependency `name`.

### 2.2 Native object node

Represents an authored native ODF object with structural identity.

Current categories:

- Section;
- Bookmark;
- Table;
- Frame.

A native object may contain evidence and other native objects.

### 2.3 Semantic binding node

Represents interpreted binding meaning.

Examples:

- scalar binding of `company`;
- filtered binding of `birthdate`;
- special structural binding.

A binding is tied to one authored evidence site.

### 2.4 Semantic control node

Represents interpreted control meaning.

Examples:

- conditional control;
- repetition control.

A control is tied to authored evidence and owns a control scope/prototype according to its representation semantics.

### 2.5 Data-scope node

Represents an environment in which data references resolve.

Minimum conceptual kinds:

```text
ROOT
COLLECTION_ITEM
```

Future semantics may justify additional kinds, but B1.1 does not invent them.

### 2.6 Logical dependency node

Represents one deduplicated data dependency in a particular data scope.

Examples:

```text
root:name
root:photo
experience[]:company
experience[]:role
experience[]:current
```

Logical dependency nodes are not declaration sites.

They aggregate references from one or more bindings/controls.

## 3. Edge families

The graph requires typed relationships.

Provisional edge vocabulary:

```text
CONTAINS
DECLARES
INTERPRETS_AS
OWNS_STRUCTURE
CREATES_DATA_SCOPE
RESOLVES_IN
REFERENCES
DERIVES_DEPENDENCY
NESTED_IN
CARRIED_BY
```

These labels are design vocabulary, not approved enum/API names.

### 3.1 CONTAINS

Native/source containment.

Example:

```text
Section Experience
    CONTAINS Table ExperienceTable
```

This must reflect authored ODF ownership, not data semantics.

### 3.2 DECLARES / INTERPRETS_AS

Connects source evidence to semantic meaning.

Example:

```text
Section-name evidence "#foreach:experience"
    INTERPRETS_AS repetition control
```

This edge may exist even while the semantic control is recognized but not yet executable.

### 3.3 CARRIED_BY

Connects semantic meaning to the native object carrying its declaration.

Example:

```text
foreach experience
    CARRIED_BY Section #foreach:experience
```

This preserves the distinction established in B1.0:

> The Section is not the foreach. The Section carries the foreach declaration.

### 3.4 OWNS_STRUCTURE

Connects a control to the authored structure whose participation it governs.

Example:

```text
foreach experience
    OWNS_STRUCTURE Section #foreach:experience
```

For a declarative Section control, `CARRIED_BY` and `OWNS_STRUCTURE` may point to the same native Section but express different semantics.

For classic marker controls, the owned structure may instead be a marker-bounded compatibility range rather than a native object.

That difference must remain visible.

### 3.5 CREATES_DATA_SCOPE

A repetition control creates a row-local collection-item scope.

Example:

```text
foreach experience
    CREATES_DATA_SCOPE experience-item
```

Conditional controls do not automatically create a new data scope. They normally resolve in the data scope in which they are owned.

### 3.6 RESOLVES_IN

Connects a binding or control expression to the data scope used for unqualified references.

Example:

```text
{{company}}
    RESOLVES_IN experience-item
```

### 3.7 REFERENCES

Connects a semantic binding/control to a logical dependency.

Example:

```text
binding {{company}}
    REFERENCES experience[].company
```

### 3.8 NESTED_IN

Represents scope nesting independent of native containment.

Example:

```text
experience-item
    NESTED_IN root
```

A future nested foreach may create:

```text
root
└── experience-item
    └── projects-item
```

## 4. Structural ownership is not data ownership

This distinction is mandatory.

Consider:

```text
Section #foreach:experience
    Table ExperienceTable
        {{company}}
```

The Table is structurally owned by the Section.

The binding `{{company}}` is physically inside the Table.

But its data owner is the `experience` collection-item scope created by the foreach control.

Therefore:

```text
native ownership:
Section -> Table -> binding location

data ownership:
experience-item -> company dependency
```

The Table does not become a data scope merely because it contains a binding.

Likewise, a Section named `Profile` does not create a `profile` data scope unless explicit supported template semantics say so.

## 5. Root data scope

Every template contract has one conceptual root data scope.

Example:

```text
ROOT
├── name
├── email
├── photo
└── experience[]
```

Bindings outside repetition scopes resolve against ROOT unless another explicit supported scoping rule applies.

The root scope is a semantic construct. It need not correspond to one XML element.

This is important for templates spanning body, header, and footer: all may refer to the same root application data even though they belong to different document parts.

## 6. Repetition scope

A repetition control introduces two related but distinct data concepts:

1. a collection dependency in the parent scope;
2. an item scope for content governed by the repetition.

Example:

```text
ROOT
└── experience[]                 collection dependency
    └── ITEM SCOPE
        ├── company
        ├── role
        └── current
```

Conceptually:

```text
foreach experience
    RESOLVES collection "experience" IN ROOT
    CREATES experience-item scope
```

Bindings governed by that repetition resolve in `experience-item`.

This directly fixes the conceptual weakness characterized in A1, where classic nested conditions currently do not use row-local condition values in the full-render path.

B1.1 defines desired contract semantics; it does not silently change classic runtime compatibility behavior.

## 7. Nested repetition scope

Nested repetition must compose rather than flatten.

Example:

```text
#foreach:experience
    {{company}}

    #foreach:projects
        {{project_name}}
```

Conceptual graph:

```text
ROOT
└── experience[] 
    └── experience-item
        ├── company
        └── projects[]
            └── project-item
                └── project_name
```

The unqualified dependency `projects` resolves in the `experience-item` scope.

The unqualified dependency `project_name` resolves in the `project-item` scope.

A generic mapper can therefore distinguish:

```text
experience[].company
experience[].projects[].project_name
```

without reconstructing DOM nesting.

## 8. Conditional controls and data scope

A condition normally does not create a child data scope.

Example:

```text
#foreach:experience
    #if:current
        Current position
```

Conceptually:

```text
foreach experience
└── experience-item scope
    └── if current
        REFERENCES experience[].current
```

The condition inherits the nearest governing data scope.

This is different from structural ownership:

```text
Section #foreach:experience
└── Section #if:current
```

The nested Section proves structural containment. The repetition control establishes the data scope.

## 9. Condition expressions

A condition expression may contain references and literals.

Example:

```text
#if:gender=="female"
```

Contract projection:

```text
condition expression
├── reference: gender
│   └── resolves in current data scope
└── literal: "female"
```

Only the reference produces a data dependency.

Future condition grammar work must derive dependency references from the same semantic parser/evaluator model used by runtime controls. B0 already proved that current inspection and runtime grammar diverge.

B1.1 therefore forbids a second independent dependency grammar as the final architecture.

## 10. Declaration-site multiplicity versus dependency deduplication

The contract must preserve both.

Example:

```text
Dear {{name}}

...

Kind regards,
{{name}}
```

There are:

```text
2 binding/evidence sites
1 logical dependency: root:name
```

This supports two different consumers:

- authoring diagnostics need both physical sites;
- application mapping normally needs one logical input dependency.

Deduplication key must therefore include data scope, not only variable name.

Example:

```text
ROOT.name
experience-item.name
```

are distinct logical dependencies even though both use the token `name`.

## 11. Dependency paths are derived views, not primary identity

Human/tool-friendly paths such as:

```text
name
experience[].company
experience[].projects[].project_name
```

are useful projections.

B1.1 does not make path strings the primary internal identity.

Reasons:

- scope relationships are richer than strings;
- future aliasing or explicit path syntax may exist;
- multiple declaration sites can reference the same dependency;
- diagnostics need stable source/evidence identity separately.

The graph should be primary; path notation can be derived for serialization/tooling.

## 12. Native ownership graph

Native containment should preserve actual ODF relationships where inspected.

Example:

```text
Document Part: content.xml
└── Section #foreach:experience
    ├── Table ExperienceTable
    │   └── Frame CompanyLogo
    └── Section #if:current
```

Existing `SectionDescriptor::nestedNamedObjects()` and containing-Section information on current descriptors are evidence that the engine already captures part of this graph.

B1.1 does not require reusing those DTOs unchanged. B1.2 must decide source-part/provenance representation and whether source-native descriptors should be adapted or independently projected.

## 13. Control ownership for native declarations

For the principal Phase D design direction:

```text
Section name: #foreach:experience
```

the graph should conceptually express:

```text
Native Section S
├── native identity/name: #foreach:experience
└── declaration evidence E
        INTERPRETS_AS Control C

Control C
├── kind: repetition
├── CARRIED_BY S
├── OWNS_STRUCTURE S
├── REFERENCES collection dependency ROOT.experience[]
└── CREATES_DATA_SCOPE experience-item
```

Bindings structurally contained by S inherit `experience-item` unless a nested supported control establishes another scope.

This is a design model, not approval of the exact Section-name grammar. Phase D still owns grammar and execution semantics.

## 14. Control ownership for classic marker controls

Classic controls cannot be modeled as though they were native Section declarations.

Example:

```text
{{#foreach:experience}}
...
{{#endforeach}}
```

Their authored ownership is marker/range based and A1 established compatibility-specific behavior.

Conceptually:

```text
open marker evidence
close marker evidence
    ↓
classic repetition control
    ↓
owns compatibility range/prototype
    ↓
creates experience-item semantic scope
```

The inspection contract may project intended row-local data semantics even while support/diagnostics report that current classic runtime nesting has compatibility limitations.

Provenance must allow tooling to distinguish:

```text
representation = classic_marker
representation = native_section_declaration
```

## 15. Ownership inheritance algorithm — conceptual

For a binding/control evidence site, the semantic owner can be reasoned about in this order:

1. identify authored physical location;
2. identify containing native objects;
3. identify governing supported control structures;
4. choose the nearest governing control according to that representation's ownership semantics;
5. inherit its current data scope;
6. if the evidence itself is a repetition control, resolve its collection dependency in the inherited scope and create a child item scope;
7. project referenced dependencies into the resulting scope.

This is a semantic algorithm only.

B1.1 does not approve implementation through DOM ancestor walking alone. Classic marker ranges are not expressible purely as XML ancestry.

## 16. Scope boundaries must be explicit

A binding must never become row-local merely because it appears visually near a repeated structure.

Likewise, a binding outside the owned structure of a repetition remains in its enclosing scope.

Example:

```text
{{document_title}}

Section #foreach:experience
    {{company}}

{{footer_note}}
```

Contract:

```text
ROOT.document_title
ROOT.experience[]
experience[].company
ROOT.footer_note
```

This rule is what makes generic data mapping deterministic.

## 17. Cross-document-part data scope

Document-part ownership and data scope are orthogonal.

Example:

```text
content.xml body:
    {{name}}

styles.xml header:
    {{name}}
```

Both binding sites may reference:

```text
ROOT.name
```

while provenance differs:

```text
site A -> content.xml
site B -> styles.xml/header
```

Therefore dependency deduplication must not include document part by default.

B1.2 will define how source-part provenance is represented and how header/footer ownership is discovered.

## 18. Native identity and semantic identity

The graph needs separate notions of identity.

### Native identity

Example:

```text
type = section
name = #foreach:experience
document part = content.xml
```

This identifies authored native structure subject to duplicate-name diagnostics and ODF rules.

### Semantic identity

Examples:

- one particular binding site;
- one particular control declaration;
- one data scope;
- one logical dependency.

Semantic identity must not rely solely on native names.

This matters because:

- classic controls have no native object identity;
- multiple `{{name}}` sites share one dependency;
- duplicate native names can exist in malformed templates;
- repeated names across different native object types are currently legal inspection facts.

Exact stable ID representation is deferred to B1.2/B1.4.

## 19. Graph validity and partial graphs

Inspection must be able to return useful partial information when some edges cannot be resolved.

Example:

```text
recognized foreach declaration
    but malformed nesting prevents ownership resolution
```

The contract should retain:

- the authored evidence;
- recognized semantic candidate;
- known native containment;
- unresolved ownership edge;
- diagnostic.

It should not discard the entire template contract.

This supports authoring tools and diagnostics.

## 20. Ambiguity is data, not permission to guess

When ownership or scope cannot be determined unambiguously, inspection must report ambiguity.

It must not choose a likely owner based on:

- visual proximity;
- naming similarity;
- formatting;
- arbitrary first-match behavior.

This follows the B1.0 rule that the engine reports declared semantics rather than inferred application intent.

## 21. Graph view for generic application mapping

A generic application does not necessarily need every graph edge.

It needs a derived dependency view such as:

```text
ROOT
├── name
├── email
├── photo
└── experience[]
    ├── from
    ├── to
    ├── role
    ├── company
    ├── current
    └── projects[]
        └── project_name
```

But this view must be derived from the richer graph so that the application can also ask:

- where was this dependency declared?
- which controls reference it?
- is it supported?
- which native structure owns the declaration?
- are there diagnostics?

This is why the dependency tree is a projection, not the whole contract.

## 22. Worked graph example

Authored conceptual template:

```text
{{name}}

Section #if:photo
    Frame PortraitFrame

Section #foreach:experience
    Table ExperienceTable
        {{from}}
        {{to}}
        {{role}}
        {{company}}

    Section #if:current
        Current position

    Section #foreach:projects
        {{project_name}}
```

### 22.1 Native graph

```text
content.xml
├── Section #if:photo
│   └── Frame PortraitFrame
└── Section #foreach:experience
    ├── Table ExperienceTable
    ├── Section #if:current
    └── Section #foreach:projects
```

### 22.2 Control graph

```text
if photo
└── owns Section #if:photo

foreach experience
├── owns Section #foreach:experience
├── if current
│   └── owns Section #if:current
└── foreach projects
    └── owns Section #foreach:projects
```

### 22.3 Data-scope graph

```text
ROOT
├── name
├── photo
└── experience[]
    └── experience-item
        ├── from
        ├── to
        ├── role
        ├── company
        ├── current
        └── projects[]
            └── project-item
                └── project_name
```

### 22.4 Selected dependency edges

```text
{{name}}
    -> ROOT.name

if photo
    -> ROOT.photo

foreach experience
    -> ROOT.experience[]

{{company}}
    -> experience[].company

if current
    -> experience[].current

foreach projects
    -> experience[].projects[]

{{project_name}}
    -> experience[].projects[].project_name
```

All four views describe the same authored template without conflating their semantics.

## 23. Interaction with existing imperative structured APIs

The contract graph does not replace imperative Section/target APIs.

Existing programmatic code may continue to:

- resolve native targets;
- instantiate Sections;
- bind local data;
- insert structured elements.

Declarative controls later become an authored frontend over established structural mechanics.

The graph's role is to describe what the template declares and provide deterministic semantics for high-level orchestration.

This preserves the project principle that the new template philosophy complements rather than replaces lower-level APIs.

## 24. Compatibility boundary

B1.1 defines the desired semantic contract graph.

It does not declare all current classic runtime behavior correct.

A1 characterized, among other things, row-local condition limitations in classic foreach processing.

Therefore the future contract may need to express both:

```text
semantic data scope:
    experience[].current

runtime support/provenance:
    classic control path with compatibility limitation
```

B1.3 will define how support state and diagnostics expose such distinctions.

Do not distort the semantic graph merely to reproduce an implementation defect.

## 25. Non-goals

B1.1 does not define:

- public PHP class names;
- array/JSON schema;
- stable ID syntax;
- XPath exposure;
- source-part locator format;
- condition grammar;
- Section declaration grammar;
- runtime processing order;
- validation-requiredness;
- form widgets/labels;
- native field families;
- mutation APIs;
- execution implementation.

## 26. Decisions established by B1.1

Subject to design review, B1.1 proposes these semantic decisions:

1. Unified inspection is graph-based, not a flat inventory.
2. Native containment, control ownership, data scope, and dependency reference are distinct relationship families.
3. Every template has a conceptual root data scope.
4. Repetition creates a collection dependency in its parent scope and a child item scope.
5. Conditional controls inherit their governing data scope and do not normally create a new data scope.
6. Logical dependencies deduplicate per data scope while declaration sites remain distinct.
7. Dependency paths such as `experience[].company` are derived projections, not primary identity.
8. Native object names do not create data scopes or dependencies by themselves.
9. Document-part provenance is orthogonal to data scope.
10. Native declarations and classic controls may represent similar high-level control concepts while retaining distinct provenance/ownership semantics.
11. Partial/ambiguous graphs remain inspectable and produce diagnostics rather than guesses.
12. Desired semantic scope must not be distorted to preserve known classic runtime defects.

## 27. Questions carried into B1.2

B1.2 — Source-Part Coverage & Provenance Model must decide:

1. How source `content.xml` and `styles.xml` participate in one authored template contract.
2. How body/header/footer/master-page location is represented.
3. What source locator/provenance is stable enough for tooling without exposing mutable DOM nodes.
4. How native objects receive source identities when duplicate names exist.
5. How expression/control evidence sites receive stable inspection identities.
6. Whether existing live `DocumentInspection` descriptors can be reused or require source-oriented counterparts/adapters.
7. How physical expression fragment/style/bookmark diagnostics attach to graph evidence.
8. How repeated references across document parts deduplicate to one logical dependency.
9. How unsupported source parts or constructs are surfaced without pretending to full-package coverage.

## 28. B1.1 design thesis

The contract's core is not a hierarchy of ODF elements and not a hierarchy of variables.

It is the relationship between **authored structure and data scope**.

A useful unified inspection must be able to explain both:

```text
Where is this declaration structurally owned?
```

and:

```text
Against which application-data scope does this reference resolve?
```

Only with both answers can a generic application safely derive mappings such as:

```text
experience[].projects[].project_name
```

while leaving Writer responsible for document layout and the engine responsible for declared template semantics.

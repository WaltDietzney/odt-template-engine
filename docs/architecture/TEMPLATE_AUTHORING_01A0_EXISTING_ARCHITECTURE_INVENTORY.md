# TEMPLATE-AUTHORING-01A0 — Existing Architecture & Historical Decision Inventory

Status: ACTIVE RESEARCH / NO API DECISION

## 1. Purpose

This document establishes the starting evidence for TEMPLATE-AUTHORING-01.

The milestone must not begin by inventing a new template API. The repository already contains several overlapping but distinct mechanisms:

- visible template-language expressions;
- structured native Section/Bookmark/Table/Frame addressing;
- template-language inspection;
- native document inspection;
- structured Section instantiation;
- Writer/ODF field and conditional research;
- compatibility behavior accumulated by the historic render pipeline.

The immediate goal is to identify ownership, lifecycle, compatibility, proven behavior, known weaknesses, and reusable architecture before any new public API or render pipeline is approved.

## 2. Product goal fixed for 1.0

TEMPLATE-AUTHORING-01 is additive.

It does **not** replace the current public APIs or the current visible template language.

The intended product model is:

```text
Simple Template Processing
    {{name}}
    {{upper:name}}
    {{#if:...}}
    {{#foreach:...}}

Structured / Native Template Processing
    User Fields
    named Sections
    Bookmarks
    named Tables / Frames
    declarative Section controls
        #foreach:experience
        #if:photo
        #ifnot:photo

                  ↓

          Unified inspection

                  ↓

        mapped application data

                  ↓

        high-level render orchestration
```

The conceptual target remains:

```php
$schema = $template->inspect();
$template->render($mappedData);
```

These method signatures are **not yet approved APIs**.

## 3. Existing public facade state

### 3.1 Current native inspection API

`OdtTemplate::inspect()` already exists and currently returns a `DocumentInspection`.

It inspects native named document structures through `DocumentInspector`:

- Sections;
- Bookmarks;
- Tables;
- Frames;
- diagnostics for missing/duplicate native names.

This is already a useful foundation for template introspection, but it is a **document-structure inspection**, not yet a complete template contract.

A future unified template inspection must therefore decide whether:

1. `inspect()` evolves compatibly into a broader result;
2. another method is introduced;
3. `DocumentInspection` becomes one composed part of a broader schema.

This is a compatibility-sensitive public API decision and must not be changed casually.

### 3.2 Current template-language inspection API

`OdtTemplate::inspectTemplateStructure()` returns `TemplateStructureInspection`.

`TemplateStructureInspector` delegates to `TemplateExpressionProjector` and is read-only.

This creates an important current split:

```text
inspect()
    -> native named document structures

inspectTemplateStructure()
    -> visible template-language expressions
```

TEMPLATE-AUTHORING-01B should reconcile these concepts rather than create a third competing inspection surface.

### 3.3 Current typed native target APIs

The facade already exposes:

```php
$template->section($name);
$template->bookmark($name);
$template->table($name);
$template->frame($name);
```

These resolve typed native targets and form an established structured-template capability.

Any new declarative control layer should orchestrate this architecture where possible instead of bypassing it.

## 4. Existing classic template-processing architecture

`TemplateProcessor` is stateless and currently owns the visible template-language mechanics supplied with DOM regions.

Relevant capabilities include:

- scalar replacement;
- scalar filters;
- variable-name discovery;
- subtree-local scalar replacement;
- `nl2br` transformation;
- `ul` / `ol` structural replacement;
- paragraph-based foreach processing;
- paragraph-based if / ifnot / elseif / else processing;
- existing condition-expression evaluation.

### 4.1 Existing condition semantics

`TemplateProcessor::evaluateCondition()` currently supports:

- truthy variable evaluation;
- `==`;
- `!=`;
- `>`;
- `<`;
- `>=`;
- `<=`.

This existing semantics is a strong candidate for reuse by future declarative controls.

A Section named:

```text
#if:gender=="female"
```

should not automatically introduce a second independent condition language if the established evaluator is sufficient.

### 4.2 Existing foreach implementation

The classic foreach path currently discovers marker paragraphs:

```text
{{#foreach:key}}
...
{{#endforeach}}
```

It then:

1. captures sibling template nodes;
2. removes marker paragraphs and captured original nodes;
3. clones the captured nodes for each row;
4. performs row-local placeholder replacement;
5. reinserts the cloned nodes.

This behavior is powerful but structurally invasive. It is therefore a primary subject for TEMPLATE-AUTHORING-01A characterization, especially for:

- paragraph styles;
- text spans;
- bookmarks;
- lists;
- tables;
- nested Sections;
- frames;
- automatic styles;
- mixed boundaries.

No assumption should be made that formatting is lost until characterized, but historic observations justify treating this path as high-risk.

### 4.3 Existing conditional implementation

The classic conditional path is paragraph-index based.

It discovers control marker paragraphs and removes all paragraphs outside the selected branch.

This naturally raises preservation questions when the selected branch contains or crosses:

- styled paragraphs;
- nested structures;
- tables/lists;
- bookmarks;
- Sections;
- frames;
- paragraph relationships that are not represented only by visible text.

This path is therefore another primary Phase-A characterization target.

## 5. Existing structure-preserving scalar baseline

The project has already completed substantial scalar-expression preservation work.

Current architecture includes:

- logical projection across fragmented text runs;
- non-mutating structure inspection;
- normalization where required;
- structure-preserving scalar replacement;
- bookmark preservation;
- authored ODF whitespace preservation;
- Section-local scalar binding.

This baseline must **not** be reopened during TEMPLATE-AUTHORING-01A unless new evidence demonstrates a regression.

The remaining concern is primarily structural control, not ordinary scalar replacement.

## 6. Existing structured Section architecture

The completed Section architecture already provides much of the mechanical basis required by declarative repetition.

Established Section capabilities include:

- typed Section resolution;
- exact native subtree cloning;
- deterministic identity rewriting;
- local scalar binding;
- repeated instantiation;
- nested Section resolution;
- collection lifecycle semantics;
- prototype removal;
- rollback behavior.

RESEARCH-01A demonstrated that LibreOffice preserves a Section name such as:

```text
#foreach:experience
```

unchanged, and that the existing Section resolver / `instantiateMany()` mechanics can address that Section.

This means declarative foreach should be treated as:

```text
declaration discovery
    ↓
collection resolution
    ↓
existing Section instantiation mechanics
```

not as a second foreach renderer.

## 7. Existing native document inspection

`DocumentInspector` already creates a read-only snapshot of:

- Sections;
- Bookmarks;
- Tables;
- Frames;
- nested named-object references;
- duplicate/missing-name diagnostics.

This is directly relevant to the future template schema.

A future inspection result should probably preserve the distinction between:

```text
input dependency
control declaration
addressable native object
diagnostic
```

rather than flattening everything into a single list of names.

Example:

```text
experience
    -> collection input dependency

#foreach:experience
    -> control declaration

ExperienceSection
    -> native Section identity

company_logo
    -> named frame/native object
```

The exact model remains undecided.

## 8. Existing RESEARCH-01A native-field evidence

RESEARCH-01A established useful Writer/ODF semantics but deliberately did not approve public APIs.

### 8.1 User Fields

Writer User Fields use a central authoritative declaration:

```xml
<text:user-field-decl
    office:value-type="string"
    office:string-value="Walter"
    text:name="customer"/>
```

References use:

```xml
<text:user-field-get text:name="customer">Walter</text:user-field-get>
```

Empirical Writer behavior showed that changing the declaration causes Writer to display the new value on open.

This makes User Fields a strong 1.0 candidate for semantic/native value binding.

### 8.2 Variable Set/Get

Writer variable set/get fields have document-flow semantics.

A get resolves the applicable preceding set for the same variable.

This is semantically different from centrally declared User Fields and must not be collapsed into the same API merely because both are called "variables" by users.

### 8.3 Conditional Text

Writer Conditional Text stores authoritative branch content in attributes.

Changing only currently visible character data is insufficient because Writer can recalculate and restore the authoritative branch values.

Any support for native Conditional Text must therefore process authoritative branch attributes, not just visible text nodes.

Conditional Text is binary value selection and is not equivalent to the engine's arbitrary structural if/elseif/else blocks.

### 8.4 Hidden Text / Hidden Paragraph / Conditional Sections

These remain valuable native mechanisms with distinct scope and interoperability behavior.

They should not automatically become 1.0 APIs simply because they exist.

TEMPLATE-AUTHORING-01C must select a bounded native-field/conditional subset based on actual authoring value and lifecycle semantics.

## 9. Existing public lifecycle

Current `OdtTemplate` exposes an assignment/render/save lifecycle with compatibility state including:

- `setValues()`;
- structured `setElement()`;
- legacy value/repeat stacks;
- render/save behavior;
- repeated lifecycle expectations;
- protected compatibility hooks.

The high-level `render($mappedData)` target must therefore be designed as an additive orchestration facade, not a replacement lifecycle.

Key compatibility questions include:

- whether render mutates the currently loaded document in-place;
- whether repeated render calls are supported;
- whether a second render overlays or resets data;
- whether native declarations are consumed/removed;
- when prototype Sections disappear;
- whether inspection observes source template state or current rendered state;
- whether finalization is required before save/export.

Some of these overlap with FINALIZATION-01 and must be deliberately separated.

## 10. Important architecture tension already present

There are currently two meanings close to "inspection":

```text
current mutable document state
    OdtTemplate::inspect()
    -> DocumentInspection

original/source template language structure
    OdtTemplate::inspectTemplateStructure()
    -> TemplateStructureInspection
```

A future `TemplateSchema` must explicitly define **what moment in the lifecycle it describes**.

The likely product need is inspection of the authored template contract, but this is not yet an approved semantic decision.

This must be resolved before a public schema API is designed.

## 11. Template-driven product model

The 1.0 product direction is that a template can carry part of application control.

A well-authored ODT may define:

- scalar inputs through placeholders and/or supported native fields;
- collection inputs through declarative repeat Sections;
- conditional dependencies through declarative Section names or supported native conditions;
- addressable Sections, bookmarks, tables, and frames;
- visual layout and styling directly in LibreOffice.

The application then supplies mapped data rather than restating document structure in PHP.

Conceptually:

```text
application/domain data
        ↓
      mapping
        ↓
 inspected template contract
        ↓
 render orchestration
        ↓
 native ODT result
```

This enables generic integrations such as CMS/WordPress plugins without making the core engine a form builder.

## 12. 1.0 documentation requirement

Template authoring documentation is part of the feature, not post-release polish.

The documentation must eventually explain:

- simple vs structured/native template processing;
- how to create placeholders safely;
- how to name Sections and other native objects;
- how declarative controls work;
- how native fields differ from placeholders;
- formatting-preservation rules;
- nesting rules;
- supported data shapes;
- inspection/schema semantics;
- diagnostics;
- generic mapping and high-level render usage;
- when lower-level imperative APIs are preferable.

A likely public document is:

```text
docs/TEMPLATE_AUTHORING.md
```

The exact location/title can be decided in Phase F.

## 13. Compatibility constraints

TEMPLATE-AUTHORING-01 must preserve:

- current placeholder/filter syntax;
- current public imperative APIs;
- protected facade hooks where externally overridable;
- existing Section APIs;
- existing bookmark/table/frame APIs;
- repeated render/save compatibility unless explicitly changed by a documented lifecycle decision;
- content.xml and styles.xml processing where relevant;
- source-template structure needed by existing applications.

New declarative naming must not silently reinterpret ordinary authored Section names.

Only explicit declaration syntax should carry control semantics.

## 14. A0 conclusions

The repository already contains most of the low-level building blocks required for the proposed 1.0 template philosophy.

The missing architecture is mainly **composition and orchestration**, not a wholesale new document engine.

The strongest reuse opportunities are:

```text
visible expressions
    -> TemplateStructureInspector / TemplateProcessor

native objects
    -> DocumentInspector / typed target resolvers

repeatable native blocks
    -> SectionTarget / instantiateMany()

conditions
    -> existing evaluateCondition() semantics

future schema
    -> composition of existing inspectors + native-field/control discovery

future render
    -> orchestration over existing lower-level capabilities
```

The most significant unresolved risk remains structural-format preservation in classic control paths and lifecycle ordering when classic syntax, declarative Sections, native fields, and structured insertion coexist.

## 15. Next evidence pass

The next step should be **TEMPLATE-AUTHORING-01A1 — Classic Control Format-Preservation Characterization**.

A1 should not implement fixes.

It should build representative ODT fixtures/tests for:

1. if/elseif/else around differently styled paragraphs;
2. foreach around differently styled paragraphs;
3. foreach containing a table;
4. foreach containing bookmarks;
5. foreach containing a named Section;
6. nested if inside foreach and foreach inside if where current syntax supports it;
7. control markers adjacent to styled spans/whitespace;
8. save/reopen stability.

For each case record:

- exact source ODF structure;
- current output structure;
- preserved/lost styles and identities;
- whether behavior is intentional compatibility or a defect candidate.

Only after A1 should we decide whether TEMPLATE-AUTHORING-01A requires implementation changes.

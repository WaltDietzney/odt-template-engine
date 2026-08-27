# SECTION-03A — Clone, Instantiate and Identity Rewrite Semantics

## A. Purpose

SECTION-03A defines the semantic contract for future native section cloning and
template instantiation.

This slice is documentation/design only. It does not add production APIs or
modify current template processing.

The goal is to make a named LibreOffice/ODF section usable as a repeatable
structured template object while preserving the native document structure that
already carries layout, styles, frames, custom shapes, resources and other ODF
semantics.

The primary practical benchmark is a variable-length professional CV.

## B. Evidence carried forward

The design builds on:

- PRODUCT-01C addressable document model;
- ADDRESSABLE-01/02 inspection and typed targets;
- NAMED-RANGE-01 bookmark replacement;
- SECTION-01 section inspection/read operations;
- SECTION-02A–D section content mutation and frame-host findings;
- FRAME-LAYOUT-01 preservation rules;
- FRAME-LAYOUT-01 template-suitability and Word-compatible authoring findings;
- existing TemplateProcessor variable/condition/foreach behavior;
- real LibreOffice and DOCX→ODT→DOCX CV structures.

Binding findings include:

1. `text:section` is a real subtree boundary and therefore the strongest native
   candidate for a repeatable template object.
2. Existing designer-authored ODF layout must be preserved structurally during
   clone operations. It must not be regenerated through simplified
   `ImageElement`/`DrawTextBox` defaults.
3. Native identities are type-specific (`text:name`, `table:name`, `draw:name`,
   bookmark names, technical IDs).
4. Existing template-language expressions inside a cloned section are part of
   the template instance semantics and cannot be ignored.
5. LibreOffice copy/paste naming is useful evidence but must not become the
   engine's deterministic identity contract.
6. Clone/instantiate must remain separate from current `foreach` processing,
   although both may later share lower-level local-scope machinery.

## C. Primary semantic distinction

SECTION-03 keeps two operations conceptually distinct:

```text
clone
    duplicate the native section subtree
    preserve designer-authored structure/layout
    allocate independent native/template identities
    do not bind application data yet

instantiate
    perform the clone semantics
    then bind/evaluate data in the newly created local instance
```

Illustrative API only:

```php
$clone = $template
    ->section('ExperienceEntry')
    ->clone();

$instance = $template
    ->section('ExperienceEntry')
    ->instantiate($job);
```

Exact public method names remain subject to implementation evidence.

The important rule is:

> An independent clone must be independently addressable and independently
> bindable.

Therefore cloning cannot simply duplicate the subtree byte-for-byte when the
subtree contains author-facing or template-facing identities.

## D. Four identity domains

Clone/instantiate must distinguish at least four identity domains.

### D.1 Native object identity

Examples:

```text
section  → text:name
bookmark → text:name
 table    → table:name
frame    → draw:name
```

These identities make native document objects addressable.

A clone must not create accidental same-type duplicate identities.

### D.2 Template identity

Visible template expressions are also identities from the perspective of data
binding.

Example source section:

```text
{{from}}
{{to}}
{{profession}}
{{company}}
```

If this section is cloned into independent instances, every clone needs an
independent binding namespace.

Illustratively:

```text
source
    {{from}}
    {{to}}
    {{profession}}

clone 1
    {{from_1}}
    {{to_1}}
    {{profession_1}}

clone 2
    {{from_2}}
    {{to_2}}
    {{profession_2}}
```

The exact textual suffix syntax is **not frozen by SECTION-03A**. `_1`, `_2`,
... is the preferred compatibility-friendly candidate and matches the expected
usage model, but the implementation must first characterize filters, conditions,
foreach expressions and variable parsing before freezing the rewrite grammar.

The semantic requirement is fixed:

> Independent clones must not accidentally share one placeholder identity.

### D.3 Technical identity

Examples include:

- `xml:id`;
- drawing/object IDs;
- reference targets;
- other ODF-internal identifiers discovered during characterization.

Technical IDs must be rewritten only where uniqueness/reference correctness
requires it.

They are not user-facing template names.

### D.4 Shared resource identity

Package resources such as:

```text
Pictures/photo.png
```

are different again.

A clone may legitimately share an immutable image resource when both cloned
objects intentionally display the same image.

Resource binary duplication is therefore **not** required merely because a
section is cloned.

Resource paths must only be rewritten when mutation or collision semantics
require an independent resource.

## E. Template-expression rewrite is a first-class clone concern

A section clone containing visible template syntax must remain independently
assignable.

This means clone processing must understand the template language sufficiently
to rewrite local references safely.

At minimum characterize:

- simple variables: `{{name}}`;
- filtered variables;
- date/number/filter arguments;
- conditions;
- elseif/else structures;
- foreach declarations and references;
- structural placeholder forms;
- malformed/broken variables normalized by existing processing;
- literal text that merely resembles a suffix and must not be rewritten.

A clone rewrite must operate on parsed template semantics, not naive global
string replacement.

For example, if the source contains:

```text
{{upper:profession}}
```

an independent clone should conceptually become:

```text
{{upper:profession_1}}
```

rather than rewriting filter syntax or unrelated text.

Likewise a condition such as:

```text
{{#if:company}}
...
{{#endif}}
```

must remain internally consistent within the clone's local namespace.

## F. Clone namespace / instance namespace

Every produced section clone needs a deterministic instance namespace.

Conceptually:

```text
ExperienceEntry             source/template instance 0
ExperienceEntry_1           clone 1
ExperienceEntry_2           clone 2
...
ExperienceEntry_N           clone N
```

Nested addressable/template identities inside each clone derive from the same
instance allocation.

Illustrative result:

```text
ExperienceEntry_1
├── Bookmark Position_1
├── Bookmark Company_1
├── Table Skills_1
├── Frame Logo_1
├── {{from_1}}
├── {{to_1}}
└── {{profession_1}}
```

The engine should not independently choose unrelated suffixes for each object.
The entire clone should have one coherent identity-allocation context.

This is particularly important for AI/programmatic inspection: given clone 3,
all local names should be predictably associated with clone 3.

## G. Preserve native structure, do not rematerialize it

Clone is fundamentally different from `replaceContent()`.

The source subtree may contain structures that the current construction API
cannot reproduce perfectly, including:

- designer-authored paragraph/text styles;
- tables and nested sections;
- `draw:frame` objects;
- `draw:custom-shape` objects;
- bitmap-filled shapes such as circular CV photos;
- Word-compatible shape structures;
- coordinates, anchoring, wrap, z-order and frame styles;
- custom automatic styles;
- LibreOffice extension attributes.

These must normally be copied as native DOM structure and selectively rewritten
for identity/reference correctness.

The clone path must **not** convert them into new `OdtElement` objects and render
them again through current defaults.

Conceptually:

```text
source native subtree
    ↓ deep structural copy
identity/reference analysis
    ↓ deterministic rewrite
validated cloned subtree
    ↓ insertion
```

not:

```text
source
    ↓ interpret
new Paragraph/Image/TextBox objects
    ↓ rematerialize
```

## H. Clone insertion position

The first clone implementation should use the least surprising deterministic
placement rule:

> Insert a clone immediately after the source section or after the last clone
> belonging to that source instance sequence.

Conceptually:

```text
ExperienceEntry
ExperienceEntry_1
ExperienceEntry_2
ExperienceEntry_3
next unrelated document content
```

This preserves natural document order and allows repeated calls to append new
instances to one template block.

Alternative arbitrary insertion positions should remain deferred.

## I. Source/template retention policy

A key practical question is whether the source section remains visible.

SECTION-03A recommends distinguishing template-authoring usage from final
instance count rather than silently deleting the source.

Initial clone semantics should preserve the source section.

Higher-level instantiation may later provide an explicit policy such as:

```text
keep template/source instance
replace source with first instance
remove source after generating instances
```

No implicit source deletion is approved in the first clone slice.

This keeps clone structurally predictable and avoids mixing duplication with
removal semantics.

## J. Nested repetition is required by the CV benchmark

The professional CV benchmark requires nested variable-length structures.

Example semantic document model:

```text
ExperienceEntry × N
├── from
├── to
├── profession
├── company
└── ActivityEntry × M
    └── activity
```

Rendered conceptually:

```text
from - to
profession

- activity
- activity
- activity

company

from - to
profession

- activity
- activity

company
```

This means the design must support **two independent clone scopes**:

```text
outer scope
    ExperienceEntry × N

inner scope per outer instance
    ActivityEntry × M
```

The inner repeated structure must belong to its outer instance.

It is not sufficient to allocate globally ambiguous names such as:

```text
activity_1
activity_2
```

without knowing which experience instance owns them.

Conceptually the engine needs hierarchical instance identity, for example:

```text
ExperienceEntry_1
    ActivityEntry_1_1
    ActivityEntry_1_2

ExperienceEntry_2
    ActivityEntry_2_1
    ActivityEntry_2_2
    ActivityEntry_2_3
```

The exact string form is not frozen. The semantic requirement is:

> nested clones must have deterministic parent-scoped identity.

## K. How nested CV templates should be authored

A practical ODT-native authoring direction is:

```text
Section ExperienceEntry
    paragraph: {{from}} - {{to}}
    paragraph: {{profession}}

    Section ActivityEntry
        list/paragraph: {{activity}}

    paragraph: {{company}}
```

Then the engine can conceptually perform:

```text
clone ExperienceEntry N times
    ↓
for each ExperienceEntry instance
    clone ActivityEntry M times
    ↓
rewrite local identities
    ↓
bind local values
```

The resulting document remains a normal Writer flow when the template itself
uses flow-oriented structures.

This is the preferred CV benchmark over rebuilding each station from PHP.

## L. Relationship to existing foreach

The nested CV use case overlaps conceptually with existing template-language
`foreach`, but the two mechanisms remain distinct.

Existing foreach:

```text
visible template-language repetition
TemplateProcessor owns the algorithm
```

Section clone/instantiate:

```text
native structured object repetition
Section mutation/clone service owns structural duplication
```

The implementation may later share:

- local data scopes;
- expression rewriting/evaluation;
- iteration indexing;
- validation helpers.

But SECTION-03 must not replace or silently reinterpret existing foreach
syntax.

A future author may choose either model depending on document complexity.

## M. Clone versus instantiate with placeholders

The proposed behavior is:

### clone()

Source:

```text
Section ExperienceEntry
    {{profession}}
```

After first independent clone:

```text
Section ExperienceEntry
    {{profession}}

Section ExperienceEntry_1
    {{profession_1}}
```

No concrete application value is inserted yet.

The caller may then assign independently:

```php
$template->assign('profession', 'Developer');
$template->assign('profession_1', 'Project Manager');
```

Illustrative only; actual assignment integration must be characterized.

### instantiate(data)

Conceptually:

```php
$template
    ->section('ExperienceEntry')
    ->instantiate([
        'from' => '2022',
        'to' => 'today',
        'profession' => 'Project Manager',
        'company' => 'Example GmbH',
    ]);
```

This performs the same independent identity allocation internally and then
binds the supplied local data to the newly created instance.

The caller does not need to know the generated suffix for ordinary
`instantiate()` usage.

This is the principal ergonomic difference between clone and instantiate.

## N. Instantiate should return the created instance

A future instantiate operation should return a typed handle/result for the
created section instance.

Conceptually:

```php
$instance = $template
    ->section('ExperienceEntry')
    ->instantiate($job);

$instance->name();
$instance->descriptor();
$instance->section('ActivityEntry'); // illustrative nested access only
```

The exact nested-target API remains deferred, but returning the created object
is important for:

- nested activity instantiation;
- diagnostics;
- AI workflows;
- later payload mutations such as instance-specific photos/logos;
- testing and validation.

## O. Nested binding and local scope

Data binding for an instance should be local by default.

Conceptually:

```php
$experience = $template
    ->section('ExperienceEntry')
    ->instantiate($job);

foreach ($job['activities'] as $activity) {
    $experience
        ->section('ActivityEntry')
        ->instantiate(['activity' => $activity]);
}
```

This illustrates the desired semantics, not a frozen API.

The important rule is that the engine must not require application code to
manually calculate global suffixes for every nested variable.

The suffix/identity system is an implementation detail available for explicit
clone-based workflows; `instantiate()` should provide local semantic binding.

## P. Conditions and foreach inside cloned sections

A cloned section may contain current template-language control structures.

SECTION-03A does not prohibit this.

However, before implementation the clone rewrite must characterize whether
control expressions reference local variables and how nested foreach variables
are scoped.

Required rule:

> A clone must never partially rewrite a control structure so that opening,
> closing or referenced expressions refer to inconsistent namespaces.

If safe rewriting of a template-language construct is not yet supported, clone
must either preserve a documented shared/global expression or reject that
specific clone case explicitly.

Silent semantic corruption is not acceptable.

## Q. Native nested target rewriting

The first identity-rewrite characterization must inspect at least:

- nested `text:section/@text:name`;
- bookmark start/end `text:name` pairs;
- `table:table/@table:name`;
- `draw:frame/@draw:name`;
- `draw:custom-shape/@draw:name` where present;
- `xml:id`;
- references to rewritten IDs/names;
- LibreOffice-specific technical attributes where relevant.

Paired identities must be rewritten coherently.

For bookmarks, start and end markers must receive the same new name.

For custom shapes, preservation of geometry/style/layout is mandatory while
only the required identity fields change.

## R. Styles

Styles must normally remain shared.

If source and clone both reference the same immutable style definition, the
clone should preserve that style reference rather than duplicate it.

Style duplication is required only if a later instance-specific mutation needs
a private style.

SECTION-03A therefore distinguishes:

```text
clone structural object identity      yes, rewrite
clone immutable style definition      normally no
clone style reference                  preserve
```

This keeps cloned document structure small and preserves designer-authored
formatting.

## S. Images and other package resources

Clone should normally preserve existing package references when the resource is
unchanged.

Example:

```text
ExperienceEntry
    CompanyLogo → Pictures/logo.png

ExperienceEntry_1
    CompanyLogo_1 → Pictures/logo.png
```

Both frames may point to the same immutable binary.

If the caller later replaces the logo in one instance, the existing bounded
resource mutation path must ensure that this instance can reference the new
asset without corrupting the other instance.

Resource garbage collection remains separate.

## T. Atomicity

Clone/instantiate is a compound structural mutation and must be atomic.

Before insertion, the implementation should complete as much work as possible
against detached/staged structure:

1. resolve source section;
2. deep-clone subtree;
3. inventory local native identities;
4. inventory template expressions;
5. allocate instance namespace;
6. rewrite native identities;
7. rewrite template identities;
8. rewrite technical references;
9. validate collisions;
10. validate resulting subtree;
11. prepare resources if instantiation mutates them;
12. insert clone;
13. bind/evaluate data for instantiate.

Failure must not leave a partial clone in the document.

If data binding fails after insertion, the new clone must be rolled back or the
entire operation must be staged before commit.

## U. Collision model

Identity allocation must inspect the current document, not assume sequential
suffix availability.

If these already exist:

```text
ExperienceEntry
ExperienceEntry_1
ExperienceEntry_3
```

then the allocator must choose an explicitly defined safe next identity rather
than blindly overwriting an existing name.

The exact gap/monotonic policy belongs to implementation characterization.

Requirements:

- deterministic;
- collision-free;
- same result for the same current document state;
- type-aware;
- parent-aware for nested instances;
- machine-readable diagnostics on failure.

## V. Repeated render/save lifecycle

Clone/instantiate semantics must survive:

```text
clone / instantiate
inspect
render
save
reopen
resolve clone identities
render/save again
```

Repeated `render()` must not create additional clones implicitly.

Clone/instantiate is an explicit structural mutation, not a hidden render side
effect.

Likewise `load()`/`refresh()` must preserve current identity-backed target
behavior: handles re-resolve current state or fail predictably if their identity
no longer exists.

## W. AI/developer ergonomics

The API should allow an AI or developer to perform the following workflow
without XPath knowledge:

```text
inspect document
    ↓
find ExperienceEntry section
    ↓
inspect nested template variables / ActivityEntry template
    ↓
instantiate experience with data
    ↓
instantiate N activities locally
    ↓
inspect resulting section
    ↓
save
```

Inspection should eventually expose enough clone-relevant information to answer:

- Which local template expressions exist?
- Which nested named sections can be instantiated?
- Which identities will be rewritten?
- Does the section contain resources/shapes?
- Are there unsupported control structures?
- Can this section be safely cloned/instantiated?

This capability report is preferable to best-effort mutation.

## X. CV benchmark

The acceptance benchmark for SECTION-03 is not merely that XML nodes can be
copied.

A successful implementation should make a CV workflow conceptually this small:

```php
$experienceTemplate = $template->section('ExperienceEntry');

foreach ($cv['experience'] as $job) {
    $experience = $experienceTemplate->instantiate($job);

    foreach ($job['activities'] as $activity) {
        $experience
            ->section('ActivityEntry')
            ->instantiate(['activity' => $activity]);
    }
}
```

The exact fluent syntax may change.

The semantic result must be:

```text
N experience stations
    each preserving designer-authored ODT layout/styles
    each with independent data bindings
    each containing M activity entries
    all remaining normal editable ODT content
```

This is the primary architecture benchmark for the future CV generator.

## Y. Implementation slices

Recommended sequence:

### SECTION-03B — Exact Native Section Clone Characterization / Implementation

- deep-copy section subtree;
- preserve native structure/layout/styles/resources;
- determine insertion position;
- no application-data binding;
- only minimum identity handling required to keep the document valid.

Before committing production behavior, characterize all identity fields found in
real fixtures.

### SECTION-03C — Deterministic Native Identity Rewrite

- section names;
- nested sections;
- bookmarks;
- tables;
- frames/custom shapes;
- technical IDs/references;
- collision-safe allocator;
- nested parent-scoped naming.

### SECTION-03D — Template Expression Rewrite

- `{{variable}}` → independent clone identity;
- filters;
- conditions;
- supported structural expressions;
- compatibility with existing TemplateProcessor;
- no naive string replacement.

### SECTION-03E — Section Instantiate / Local Binding

- clone + identity rewrite + local data binding;
- return created target;
- atomicity;
- nested instance access/binding.

### SECTION-03F — Nested CV Instance Benchmark

- `ExperienceEntry × N`;
- nested `ActivityEntry × M`;
- real LibreOffice-authored flow-oriented CV fixture;
- package/XML validation;
- repeated lifecycle tests;
- mandatory LibreOffice visual regression.

### SECTION-03G — Final Compatibility / Documentation Review

- compatibility with foreach/current template language;
- AI inspection ergonomics;
- public sample(s);
- architecture docs/roadmap update.

The slices may be adjusted if fixture evidence proves a simpler safe model.

## Z. Explicit non-goals

SECTION-03A does not implement:

- general table-row cloning;
- arbitrary DOM/XPath clone API;
- shape layout editing;
- frame layout redesign;
- Style Context;
- Asset Context;
- HTML import changes;
- DOCX conversion;
- automatic template suitability repair;
- source-section deletion policy;
- arbitrary clone insertion positions.

## AA. Final decision

SECTION-03 adopts the following semantic direction:

```text
native section template
    ↓
structural clone preserving designer-authored ODF
    ↓
coherent clone instance namespace
    ├── native object identities
    ├── technical identities/references
    └── template-variable identities
    ↓
optional local data binding (instantiate)
    ↓
nested structured instances
```

The user-visible requirement that a cloned `{{Variable}}` becomes an
independently assignable clone variable such as `{{Variable_1}}` ...
`{{Variable_N}}` is accepted as part of the clone contract. The exact suffix
syntax remains subject to parser/compatibility characterization before being
frozen publicly.

The nested CV structure `ExperienceEntry × N` containing `ActivityEntry × M`
is accepted as the primary real-world benchmark for SECTION-03.

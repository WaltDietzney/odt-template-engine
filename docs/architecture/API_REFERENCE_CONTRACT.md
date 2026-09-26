# API Reference Contract

## Status

**Accepted — controlling contract for FINALIZATION-01 F2 Public API 1.0**

This document defines the information architecture, navigation model, entry
shape, classification rules, option-contract rules, and evidence discipline
for the public 1.0 API reference.

It implements FINALIZATION-01 F2.1. It does not itself document the complete
API and does not introduce new runtime architecture.

## Purpose

The API reference is a practical end-programmer reference. It must answer both:

1. **What does this API do technically?**
2. **When is this the right API for the document structure I am working with?**

It is therefore not a generated PHP symbol index and not a second tutorial
manual. Guides explain workflows and concepts in depth; the reference provides
precise, locally sufficient contracts for supported application APIs.

The internal `PUBLIC_API_INVENTORY.md` remains the completeness and
classification audit. It is evidence for the public reference, not a substitute
for it.

## Reference information architecture

The public reference is grouped by user-facing responsibility rather than by
`src/` directory or PHP namespace.

The planned reference root is:

`docs/api-reference/`

The public navigation is:

```text
API Reference
├── Overview
├── Template & Document
│   ├── Lifecycle
│   ├── Values & Variables
│   ├── Repeating Data
│   ├── Metadata
│   └── Images
├── Structured Content
│   ├── RichText & Paragraph
│   ├── Lists
│   ├── Tables & Cells
│   ├── Images
│   └── Frames & Text Boxes
├── Writer-native Objects
│   ├── Inspection
│   ├── Bookmarks
│   ├── Sections
│   ├── Tables
│   ├── Frames
│   └── User Fields
├── Styles & Document Layout
│   ├── Text & Paragraph Styles
│   ├── Document Defaults
│   ├── Page & Flow
│   ├── Table Layout
│   └── Frame Layout
├── Mapping & Automation
│   ├── Template Contract
│   ├── Mapping
│   ├── Preflight
│   └── Automation
└── Advanced & Compatibility
    ├── Advanced API
    └── Compatibility & Deprecated API
```

The final physical page split may combine adjacent small topics where that
improves readability. The responsibility hierarchy above is binding; source
layout must not become the public navigation merely for implementation
convenience.

### Navigation relationship to guides

The API Reference is a top-level documentation destination alongside, not
inside, the existing learning guides.

A normal user journey remains:

```text
Introduction
    -> Getting Started
    -> task/concept guide
    -> API Reference for exact contract
```

Reference entries link to guides when a concept needs teaching. Guides link to
reference entries for exact signatures, options and lifecycle contracts.

The reference must not duplicate architecture-history documents.

### Navigation rollout

F2.1 records the reference architecture and representative entry. It does not
add empty navigation pages.

The actual `zensical.toml` API Reference navigation is introduced
incrementally with F2.2-F2.6 as real reference pages are created. F2.7 verifies
that the resulting navigation covers the intended public surface without dead
or placeholder pages.

## Public classification model

Normal reference users see three primary classifications.

### Recommended

The preferred supported 1.0 path for new application code.

Recommended entries are taught directly and may appear in Getting Started and
normal guides.

### Advanced

Supported public API for specialized document work, Writer/ODF-sensitive
operations, diagnostics, orchestration, or extension scenarios.

Advanced does not mean unstable. It means the caller needs more context than
the normal path.

### Compatibility

Retained public behavior primarily for existing applications or historical
workflows.

Compatibility entries must state the preferred replacement when one exists and
must describe material semantic differences. They must not be presented as an
equivalent recommended path for new code.

### Deprecated

Deprecated APIs are presented within the compatibility/migration area and are
visibly marked **Deprecated**. Where a replacement exists, the entry points to
it and explains the relevant migration difference.

Deprecation documentation does not authorize removal during FINALIZATION-01.

### Infrastructure / Hidden

Public PHP visibility does not automatically make a symbol end-programmer API.

Symbols classified Infrastructure / Hidden by the public API inventory receive
no normal API-reference entry. They may be mentioned only where necessary to
explain an observable Recommended/Advanced failure contract or a genuine
extension boundary.

## Reference entry contract

The reference uses a **practical API reference** format: concise usage context
first, exact technical contract immediately afterwards.

A full method/API entry uses the following fields:

```text
API name
Classification

Purpose / short explanation

Signature

When to use                 where applicable
Ownership                   where applicable

Parameters
Options                     where applicable
Return value

Lifecycle / side effects    where applicable
Exceptions / validation     where applicable
Limitations                 where applicable

Minimal example

See also
```

Not every field is mechanically required for trivial accessors. Omission is
allowed only when the field has no meaningful contract for that API, not
because evidence was not checked.

### Purpose

Open with one to three sentences answering what the API does and what kind of
problem it solves. Do not begin with internal implementation detail.

### Signature

Use the verified current PHP signature, including types and return type.

### When to use

Required when users could reasonably choose between different APIs that produce
similar visible output or participate in different lifecycles.

It should explain the decision, not repeat the Purpose paragraph.

### Ownership

Required where correct API choice depends on document ownership.

The central distinction is:

- **PHP-owned structure:** PHP constructs/materializes the structure.
- **Writer-owned structure:** LibreOffice/Writer authored the structure and PHP
  addresses, populates, or mutates that existing structure.

Simple placeholder processing may coexist with either model. Ownership is a
decision per piece of document structure, not a global engine mode.

### Parameters

For every parameter document:

- name;
- PHP type;
- accepted semantic form where type alone is insufficient;
- meaning.

### Return value

State the exact return behavior, including fluent behavior where present.
Never infer fluency from neighboring APIs.

### Lifecycle and side effects

Document lifecycle requirements and mutation timing whenever they affect
correct use.

Examples of material distinctions include:

- staged value assignment versus immediate Working Document mutation;
- whether `render()` is required;
- whether `save()` is implicit or explicit;
- whether inspection/mapping/preflight is non-mutating;
- bounded one-success-per-lifecycle behavior.

### Exceptions and validation

Document exceptions/validation that a normal caller can observe and reasonably
needs to handle or understand. Do not expose an internal exception taxonomy
merely because implementation services have public exception classes.

### Limitations

State verified limitations that materially affect correct use. Link to a guide
or future-work note only when useful; do not reproduce architecture history.

### Minimal example

Use the shortest realistic example that demonstrates the API's intended use.
Examples must use the documented classification/lifecycle correctly and must
not depend on undocumented options.

### See also

Link only to meaningful alternatives, ownership counterparts, supporting
elements, or exact related contracts. This section is not a symbol dump.

## Option contract

An `array $options` parameter is not considered documented merely because its
PHP type is shown.

Every user-facing options array accepted by a documented Recommended or
Advanced API must have a complete option contract or explicitly reference a
verified shared option contract.

A complete option contract records, for each supported key:

| Field | Required meaning |
| --- | --- |
| Key | Exact accepted option key |
| Type | Accepted value type |
| Values / format | Enum, ODF length, scalar format, object type, etc. |
| Default | Effective behavior when omitted |
| Meaning | Observable semantic effect |
| Validation / caveat | Only where materially relevant |

Unknown-key behavior must be documented where the implementation defines or
exposes it.

### Shared option contracts

A shared option contract may be introduced only when implementation and tests
show that the participating APIs genuinely accept the same option semantics.

Documentation convenience must not invent a common style or layout API.

If two APIs share most keys but differ in defaults, accepted values, validation,
or materialization behavior, document the difference explicitly rather than
silently linking both to one generic table.

## Aliases, compatibility and preferred replacements

Historical aliases and compatibility methods must:

1. identify their classification;
2. state the preferred API where one exists;
3. state any material lifecycle or mutation difference;
4. avoid examples that make the compatibility path look equally recommended.

A compatibility method is not described as an alias if its behavior differs
materially from the preferred API.

## Evidence rule

Reference content is derived in this order:

1. current implementation;
2. tests that characterize/verify current behavior;
3. accepted architecture decisions that constrain the 1.0 contract;
4. current canonical samples;
5. existing user documentation and historical samples as supporting/historical
   evidence.

Existing prose does not override current implementation semantics.

When evidence conflicts, the contradiction must be recorded and resolved
explicitly before the public contract is written. FINALIZATION-01 must not
silently choose the most convenient source.

If the conflict reveals a defect against already accepted 1.0 semantics, treat
it as a bounded finalization blocker: characterize first, then fix separately.
If it reveals a desired new capability, defer it to FUTURE_DEVELOPMENT.

## Representative reference entry

The following entry establishes the expected style and depth. It is a format
model for later reference work, not permission to copy surrounding API
semantics without verification.

### `setElement()`

**Recommended · Structured Content**

Inserts a PHP-built ODT element at a template placeholder.

Use `setElement()` when **PHP owns the structure of a dynamic document
region**. The LibreOffice template provides the insertion point, for example
`{{profile}}`, while PHP constructs the native ODT content using `RichText`,
`Paragraph`, tables, lists, images, or another supported `OdtElement`.

```php
public function setElement(
    string $placeholder,
    OdtElement $element
): void
```

#### When to use

Use `setElement()` when a template contains a placeholder but the content
replacing that placeholder is structured document content rather than a scalar
value.

For a template placeholder:

```text
{{profile}}
```

PHP can supply a structured region:

```php
$profile = new RichText();

$profile->addParagraph(
    (new Paragraph())->addText('Senior Project Manager', ['bold' => true])
);

$template->setElement('profile', $profile);
$template->save($outputPath);
```

#### Ownership

`setElement()` is part of the **PHP-owned structured-content model**. Writer
owns the surrounding template and insertion point; PHP builds the replacement
structure.

If the structure itself was authored in Writer, for example a named Section or
Writer table, prefer the corresponding Writer-native target API where that API
supports the required operation.

#### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `$placeholder` | `string` | Placeholder name without `{{` and `}}`. |
| `$element` | `OdtElement` | Structured ODT element used as replacement content. |

#### Return value

`void`.

`setElement()` is not fluent.

#### Lifecycle and side effects

`setElement()` belongs to the structured-element lifecycle and is distinct
from classic scalar staging with `assign()` / `setValues()` followed by
`render()`.

A normal structured-content flow is:

```php
$template = new OdtTemplate($templatePath);

$content = new RichText();
$content->addParagraph('Generated structured content');

$template->setElement('content', $content);
$template->save($outputPath);
```

Do not infer the classic `assign() -> render() -> save()` lifecycle for
`setElement()` merely because both mechanisms can use visible template
placeholders.

#### Styles and resources

Structured elements may require styles, fonts, images, or other document
resources. Their individual option contracts belong to the element APIs that
define them rather than to `setElement()` itself.

#### See also

- `RichText` and `Paragraph` for PHP-generated structured content.
- `assign()` / `setValues()` for classic scalar template values.
- Writer-native typed targets for structures authored and owned by the Writer
  template.

## F2 implementation rule

F2.2-F2.6 populate the reference under this contract. Each slice should prefer
small responsibility-oriented pages over one monolithic class dump, while
avoiding fragmentation into one page per trivial method.

F2.7 mechanically reconciles:

```text
public PHP surface
        <->
PUBLIC_API_INVENTORY
        <->
public API Reference
```

F2 is not complete until every Recommended and Advanced end-programmer surface
and every user-facing Recommended/Advanced option dictionary has the required
reference depth, and every remaining public symbol has a justified non-reference
disposition.

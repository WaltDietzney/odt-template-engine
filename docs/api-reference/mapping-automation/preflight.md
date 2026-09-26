# Concrete Preflight

Concrete Preflight is the complete **non-mutating dry run** immediately before mapped Automation.

It combines four things that earlier stages intentionally keep separate:

```text
TemplateContract
MappingDefinition
concrete application data
current Working Document inspection
        ↓
Concrete Preflight
        ↓
READY or ERROR
```

Its question is stronger than Mapping's:

> Can this complete automation invocation, with these concrete values and this current Working Document state, execute within the supported 1.0 contract?

## Run preflight

**Recommended for Mapping & Automation users**

```php
preflight(
    MappingDefinition $definition,
    TemplateContract $contract,
    array $data,
    DocumentInspection $workingDocument
): ConcretePreflightResult
```

Normal application code uses the default constructor:

```php
$contract = $template->inspectTemplate();
$working = $template->inspect();

$preflight = (new ConcreteMappingPreflight())->preflight(
    $mapping,
    $contract,
    $applicationData,
    $working
);

if (!$preflight->ready()) {
    foreach ($preflight->diagnostics() as $diagnostic) {
        // report or handle the concrete failure
    }
}
```

The optional validator/resolver constructor dependencies are Advanced test/extension injection seams, not the normal application API.

## Why both contract and Working Document?

`TemplateContract` describes the **original authored source semantics**.

`DocumentInspection` describes the supplied snapshot of the **current Working Document**.

They are not interchangeable.

The current 1.0 preflight particularly needs Working Document state for concrete named-frame image applicability. A frame can exist in the authored source contract while the current Working Document no longer presents the same safely applicable target.

Preflight does not automatically call `inspect()`; the caller supplies the snapshot being validated.

## Result

`ConcretePreflightResult` has two aggregate statuses:

```text
READY
ERROR
```

Public surface:

```php
status(): string
ready(): bool
mappingResolution(): MappingResolution
operations(): array
diagnostics(): array
```

The aggregate status is `ERROR` if any operation is `ERROR`; otherwise it is `READY`.

`diagnostics()` flattens the diagnostics from all operations in operation order.

The result also carries the `MappingResolution` that was validated. Preflight does not build a second, unrelated mapping graph.

## Operations

Every resolved dependency, explicit native-object action, and document-capability mapping becomes a `ConcretePreflightOperation`.

Stable statuses:

```text
READY
ERROR
```

Read surface:

```php
targetFamily(): string
targetIdentity(): string
resolution(): DependencyMappingResolution
    | NativeObjectActionResolution
    | DocumentCapabilityResolution
capabilityId(): ?string
payloadKind(): ?string
applicability(): ?string
status(): string
diagnostics(): array
```

Current target identities are:

| Family | Identity |
| --- | --- |
| `dependency` | TemplateContract dependency path |
| `native_action` | `<targetKind>:<targetName>` |
| `document_capability` | `<group>.<target>` |

This operation model makes a failed dry run inspectable at the same semantic level as the mapping.

## Diagnostics

`ConcretePreflightDiagnostic` exposes:

```php
code(): string
message(): string
targetFamily(): string
targetIdentity(): string
sourcePath(): ?string
context(): array
```

`context()` is machine-readable `array<string, scalar|null>`.

For nested collection failures it can contain an `item_path`: a dot-separated zero-based index path identifying the concrete failing item.

Diagnostic **codes, target identity, source path, and structured context** are the programmatic contract. Human-readable messages are explanatory text and should not be parsed as a protocol.

## Dependency validation

Preflight derives the required concrete payload from the dependency's actual TemplateContract consumers. There is deliberately no universal “all variables are strings” rule.

Current payload kinds include:

```text
NAMED_RECORD_COLLECTION
STRING
SCALAR
CONDITION_VALUE
DEPENDENCY_VALUE
```

The principal rules are:

- collection dependencies require collection-shaped application data;
- collection items used by foreach must be arrays with string keys only;
- an empty record `[]` is valid;
- an empty collection is valid for a collection dependency consumed by FOREACH;
- Writer User Field and SPECIAL consumers require strings;
- scalar and filtered-scalar consumers accept string, int, float, or bool;
- a dependency consumed only by IF/IFNOT accepts `null`;
- ordinary bindings and collections do not accept `null`;
- missing, null, empty collection, and wrong shape remain distinct states.

Nested validation retains item-index provenance instead of flattening failures.

Representative dependency diagnostic codes are:

```text
UNRESOLVED_APPLICATION_SOURCE
MISSING_APPLICATION_RESOLUTION
MISSING_SOURCE_VALUE
NULL_SOURCE_VALUE
WRONG_APPLICATION_SHAPE
INVALID_COLLECTION_ITEM_RECORD
INCOMPATIBLE_DEPENDENCY_PAYLOAD
```

The concrete dependency validator is infrastructure; these observable rules belong to the public Preflight contract.

## Section and Bookmark actions

Current explicit native-action payload boundaries are:

| Target/action | Required payload |
| --- | --- |
| `section:<name> / replace-content` | PRESENT scalar `OdtElement` |
| `bookmark:<name> / replace-text` | PRESENT scalar PHP string |

An incompatible value produces:

```text
INCOMPATIBLE_NATIVE_ACTION_PAYLOAD
```

After static capability validation, these two families are currently projected as applicable without a second Working Document structural probe.

That is different from Frame image replacement.

## Frame image replacement

The 1.0 mapped `replace-image` payload is deliberately bounded:

```php
[
    'source' => '/local/readable/image.png',
    'options' => [
        'width'  => '4cm',   // optional
        'height' => '25mm',  // optional
    ],
]
```

The complete accepted shape is:

- payload is an array;
- `source` is required and is a string;
- only `source` and optional `options` are accepted top-level keys;
- `options` is an array;
- only `width` and `height` are accepted options;
- dimensions are positive ODF-style lengths using `cm|mm|in|pt|pc|px`;
- the source is an existing readable local file;
- supported extensions are png, jpg/jpeg, gif, svg, bmp, and webp;
- raster extension/format must agree with detected MIME;
- SVG input must parse as an SVG document in the SVG namespace.

Values are not silently normalized into a different payload contract. For example, unsupported options such as `keepRatio`, invalid units, leading/trailing dimension whitespace, or non-string dimensions fail validation.

Representative failures include:

```text
INCOMPATIBLE_NATIVE_ACTION_PAYLOAD
INVALID_REPLACEMENT_OPTION
INVALID_IMAGE_SOURCE
ACTION_NOT_APPLICABLE
```

### Concrete frame applicability

Frame replacement additionally verifies the source-derived target against the supplied current `DocumentInspection`.

The frame must be resolvable exactly once in the corresponding document part and its current payload must be a direct image payload.

The concrete applicability values used here are:

```text
APPLICABLE
NOT_APPLICABLE
```

This is the clearest example of why source TemplateContract and current Working Document inspection are both inputs to Preflight.

## Metadata payloads

The current mapped document-capability family is metadata.

Preflight applies target-specific payload contracts:

| Metadata target | Payload |
| --- | --- |
| `title` | string |
| `subject` | string |
| `description` | string |
| `keywords` | PHP `list<string>` |
| `initial_creator` | string |
| `creator` | string |
| `language` | bounded hyphenated language tag, e.g. `en-US` |
| `creation_date` | bounded ISO-like date-time string |
| `date` | bounded ISO-like date-time string |
| `editing_cycles` | non-negative PHP int or digit string with optional leading `+` |
| `editing_duration` | bounded ISO-8601-style duration |
| `generator` | string |
| `coverage` | string |

Important boundaries:

- a comma-separated string is not a substitute for `keywords: list<string>`;
- `en_US` is not accepted as the language form;
- date-only values are not accepted for the date-time targets;
- floats and booleans are not accepted as editing-cycle integers;
- duration syntax must contain an actual date/time component.

Incompatible metadata values produce:

```text
INCOMPATIBLE_DOCUMENT_CAPABILITY_PAYLOAD
```

These payload rules are the observable Preflight contract; the underlying metadata validator is infrastructure.

## Capability and applicability

Preflight builds on the capability projection already used during Mapping.

Current native Phase-E capabilities are:

```text
section  / replace-content / ODT_ELEMENT
bookmark / replace-text    / STRING
frame    / replace-image   / IMAGE_REPLACEMENT
```

Dependency automation uses capability id:

```text
dependency.automation
```

The broader projection model can represent:

```text
APPLICABLE
NOT_APPLICABLE
UNKNOWN
```

while the concrete Frame validator resolves its bounded Working Document decision to `APPLICABLE` or `NOT_APPLICABLE`.

This is not a public action-registration DSL. Capability catalog/projector services remain infrastructure/advanced introspection.

## Non-mutation guarantee

Concrete Preflight performs no document operation.

Focused characterization verifies that it leaves unchanged:

- application data;
- the supplied `DocumentInspection`;
- image source files;
- the template/fixture file.

It does not call mapped Section/Bookmark/Frame mutation, dependency execution, metadata mutation, `render()`, or `save()`.

A READY result therefore means **validated readiness**, not “already executed”.

## Snapshot and lifecycle boundary

Preflight evaluates the exact objects supplied to it:

```text
MappingDefinition
TemplateContract
application data
DocumentInspection snapshot
```

It does not create a live watcher and it does not automatically re-inspect the Working Document.

If application code imperatively changes the Working Document after inspection/preflight, the preflight result still belongs to the earlier state.

The 1.0 API intentionally does not invent a general staleness-tracking token.

Automation reasserts critical runtime invariants where necessary, but that does not turn Preflight into a live object.

## READY is the execution gate

The recommended lifecycle is:

```php
$contract = $template->inspectTemplate();
$working = $template->inspect();

$preflight = (new ConcreteMappingPreflight())->preflight(
    $mapping,
    $contract,
    $applicationData,
    $working
);

if (!$preflight->ready()) {
    foreach ($preflight->diagnostics() as $diagnostic) {
        // Report diagnostics and do not execute.
    }

    return;
}

$template->automate($contract, $preflight);
$template->save($outputPath);
```

`automate()` independently enforces the READY precondition. Correctness does not rely only on application code remembering to check `ready()`.

## What Preflight does not do

Concrete Preflight does not:

- mutate the Working Document;
- render classic staged assignments;
- save/export the document;
- infer new mapping rules;
- coerce incompatible payloads;
- execute arbitrary actions;
- guarantee that no external/runtime invariant can change after the dry run;
- provide a live/staleness-tracking result.

Its job is narrower and more useful: **completely validate the current bounded mapped invocation before the mutation boundary is crossed.**

## See also

- [Template Contract](template-contract.md)
- [Mapping](mapping.md)
- Automation

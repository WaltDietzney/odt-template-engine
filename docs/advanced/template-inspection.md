# Template inspection

ODT Template Engine provides three inspection APIs with deliberately different lifecycle and semantic responsibilities.

## Which inspection API should I use?

### `inspect()`

Use `inspect()` when you need a snapshot of the **current mutable document**.

It reports native named structures such as Sections, Bookmarks, Tables, and Frames from the working document state. Mutations performed through the document APIs can therefore affect later `inspect()` results.

```php
$inspection = $template->inspect();
```

### `inspectTemplateStructure()`

Use `inspectTemplateStructure()` when you need the focused topology of the classic visible template language.

It exposes projected expressions and normalization diagnostics for constructs such as scalar placeholders, filters, classic conditions, and classic foreach markers.

```php
$structure = $template->inspectTemplateStructure();
```

This API remains a focused low-level view. It is not the unified application data contract.

### `inspectTemplate()`

Use `inspectTemplate()` when you need the semantic contract of the **original LibreOffice-authored template source**.

```php
use OdtTemplateEngine\Template\TemplateContract;

$contract = $template->inspectTemplate();

assert($contract instanceof TemplateContract);
```

Unlike `inspect()`, this contract is source-oriented. Rendering, saving, or mutating the working document does not redefine the authored template contract.

## TemplateContract

`TemplateContract` is immutable inspection metadata. It does not expose DOM nodes or live mutation handles.

The public projections are:

```php
$contract->bindings();
$contract->controls();
$contract->nativeObjects();
$contract->dependencies();
$contract->diagnostics();
$contract->coverage();
$contract->capabilities();
$contract->toArray();
```

### Bindings

`bindings()` returns authored binding evidence sites. Multiple occurrences of the same logical requirement remain visible as separate evidence.

For example, `{{name}}` in the body and `{{name}}` in a header are two binding sites.

### Dependencies

`dependencies()` returns deduplicated logical data requirements.

The contract is scope-aware. These are distinct dependencies:

```text
name
experience[].name
```

A foreach declaration creates a collection dependency and an item scope. Nested foreach declarations can therefore produce paths such as:

```text
experience[]
experience[].company
experience[].projects[]
experience[].projects[].project_name
```

Readable paths are derived projections; opaque contract identities remain the stable graph references.

### Controls

`controls()` includes classic template controls and recognized native Section declarations.

Phase B recognizes bounded native Section-name forms such as:

```text
#foreach:experience
#if:show_profile
#ifnot:hidden
```

Native declarative candidates currently use support state `RECOGNIZED`.

`RECOGNIZED` means the declaration is understood well enough for inspection and dependency mapping. It does **not** mean that native declarative execution is available. Declarative execution belongs to a later template-authoring phase.

### Native objects

`nativeObjects()` inventories source-oriented native ODT structures including Sections, Bookmarks, Tables, and Frames.

Duplicate names are preserved as distinct evidence nodes. Native containment is also distinct from data-scope nesting and semantic control ownership.

### Coverage

Contract version 1 inspects:

- `content.xml` document body content;
- page-owned header/footer content under Writer master pages in `styles.xml`.

Other package parts such as `meta.xml`, `settings.xml`, manifest data, and embedded objects are not semantic scan targets in contract version 1 and are reported as excluded coverage.

### Capabilities

Capability readiness is separate from diagnostics and semantic support state.

Contract version 1 exposes:

```text
inspection
dependency_mapping
```

Readiness values are:

```text
READY
LIMITED
BLOCKED
NOT_APPLICABLE
```

A template can therefore remain fully inspectable while dependency mapping is only partial.

### Diagnostics and partial contracts

Authoring problems normally produce a partial contract when meaningful inspection remains possible.

For example, an unsupported expression may yield:

```text
inspection          READY
dependency_mapping  LIMITED
```

while known bindings and native objects remain available.

There is intentionally no primary global `valid()` flag on `TemplateContract`. Consumers should use capability readiness together with diagnostics.

## Source provenance

Evidence records retain source provenance such as:

- package source part;
- authored region;
- master-page owner;
- header/footer carrier;
- representation kind;
- native owner chain;
- deterministic source order.

This allows a generic application to distinguish, for example, a body binding from the same logical dependency authored in a page header.

## Serialization

`TemplateContract::toArray()` provides the machine-readable tooling surface.

Contract format version 1 uses:

```php
[
    'contract_version' => 1,
    'coverage' => ...,
    'bindings' => ...,
    'controls' => ...,
    'native_objects' => ...,
    'dependencies' => ...,
    'capabilities' => ...,
    'diagnostics' => ...,
]
```

The contract format version is independent of the Composer package version.

Serialization is deterministic for unchanged authored source and does not contain DOM/XPath handles or process-local object identities.

## Example

The repository contains an executable Phase-B inspection example:

```bash
php samples/sample_28_inspectTemplateContract.php
```

It loads the Writer-authored reference fixture `TEMPLATE-AUTHORING-01B-inspection-contract.odt` and prints its coverage, bindings, controls, native objects, dependencies, capabilities, and diagnostics without rendering or mutating the document.

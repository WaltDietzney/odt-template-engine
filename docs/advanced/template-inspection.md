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

The canonical introduction is [L11 — Template Inspection](../../samples/sample_L11_template_inspection.php). It prints a compact machine-readable view of bindings, controls, native objects, dependencies, capabilities, and diagnostics. It does not render, mutate, or save an ODT. The historical L11 is retained as migration evidence, but is no longer needed for public learning.

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

Phase C also projects supported Writer string User Fields as native binding evidence. Declarations and references are separate evidence kinds:

```text
NATIVE_USER_FIELD_DECLARATION
NATIVE_USER_FIELD_REFERENCE
```

A declaration is the authoritative native value carrier. A `text:user-field-get` reference is authored occurrence/display evidence; its cached character data is not treated as the authoritative application value.

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

Supported Writer User Fields are document-global in Phase C v1 and therefore project into ROOT dependencies. Native containment inside a repeated Section does not localize them to the foreach item scope.

If a classic ROOT placeholder and a supported User Field share the same name, they contribute evidence to the same logical dependency.

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
native_field_binding
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



## Native Writer User Fields

Phase C v1 supports explicit binding of LibreOffice Writer **User Fields** with:

```text
office:value-type = string
```

Use:

```php
$template->setUserField('customer', 'Maria');
```

The operation updates all compatible authoritative `text:user-field-decl` sites for the logical field in the current working document, including matching declarations authored in supported page-header/footer regions.

It deliberately does **not** rewrite the cached character data of `text:user-field-get` references. LibreOffice Writer reevaluates the native field from the declaration value when the document is opened/rendered.

`setUserField()` is independent of classic placeholder assignment:

```php
$template->assign(['customer' => 'Maria']);
$template->render();
```

does not bind a same-named native User Field.

Phase C v1 does not support Set/Get Variable or non-string User Field types. Those remain deferred work.

The self-contained, Composer-distributed example is [L10 — Writer User Fields](../../samples/sample_L10_writer_user_fields.php). The older L10 remains repository-only as historical fixture-based regression evidence; it is not required to run L10.

Binding failures raise:

```php
OdtTemplateEngine\Template\UserFieldBindingException
```

with stable reason values:

```text
NOT_FOUND
UNSUPPORTED_TYPE
MALFORMED
AMBIGUOUS
```

## Optional Phase-E automation

Applications using the Phase-E mapping model can execute one complete READY
preflight through the common atomic invocation:

```php
use OdtTemplateEngine\Mapping\ConcreteMappingPreflight;

$contract = $template->inspectTemplate();
$workingDocument = $template->inspect();
$preflight = (new ConcreteMappingPreflight())->preflight($mapping, $contract, $data, $workingDocument);

if (!$preflight->ready()) {
    // Present $preflight->diagnostics() to the caller.
    return;
}

$template->automate($contract, $preflight);
$template->save($outputPath);
```

The caller supplies the already-inspected source `TemplateContract` because
dependency execution uses its scope and consumer evidence. The common method
does not inspect the template, resolve application data, render, or save. It
coordinates dependency, native-object, and metadata automation under one
rollback boundary. A failed invocation is restored and may be retried; after
one successful common invocation, a second common invocation is rejected for
that working-document lifecycle. Imperative operations remain available before
and after it. The older specialized automation methods remain available, but
their individually invoked calls do not claim the common E6 atomicity
guarantee.

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
php samples/sample_L11_template_inspection.php
```

It loads the Writer-authored reference fixture `TEMPLATE-AUTHORING-01B-inspection-contract.odt` and prints its coverage, bindings, controls, native objects, dependencies, capabilities, and diagnostics without rendering or mutating the document.

Phase C adds a Writer User Field binding example:

```bash
php samples/sample_L10_writer_user_fields.php
```

It binds one logical string User Field across body/header declarations and writes an ODT for manual LibreOffice reevaluation.

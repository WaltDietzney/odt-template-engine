# Advanced API

Advanced APIs are supported 1.0 surfaces, but they are not the default path for ordinary document generation.

Use them when their narrower responsibility is actually needed rather than as lower-level substitutes for the Recommended facade.

## Lifecycle and diagnostics

### `load()`

```php
public function load(): void
```

Resets the current Working Document from the original template source, resets legacy structured lifecycle state, resets the successful common Automation gate, and prepares the template again.

It is a deliberate reset boundary. Unsaved Working Document mutations are discarded.

Normal construction already loads the template, so application code does not need to call `load()` merely to begin processing.

### `cleanup()`

```php
public function cleanup(): void
```

Cleans the package's temporary working area. Cleanup is also registered for PHP shutdown.

Use explicit cleanup when application lifecycle/resource control requires it; it is not a document-content operation.

### Debug surface

```php
public function enableDebugMode(): void
public function getDebugLog(): array
```

The debug surface is diagnostic, not a machine-readable event API.

`enableDebugMode()` enables logging; there is no public disable/reset counterpart. `getDebugLog()` returns the accumulated messages.

No stable vocabulary of debug message strings is part of the 1.0 contract. Do not parse message text as an application protocol.

## Template structure inspection

```php
public function inspectTemplateStructure(): TemplateStructureInspection
```

This is the focused physical/template-language inspection described in [Writer-native Inspection](../writer-native/inspection.md).

It is useful for authoring tools and diagnostics concerned with split expressions, physical topology, and normalization safety. It is not a substitute for the semantic `inspectTemplate()` contract.

Current scope is original `content.xml`; it does not provide the semantic source coverage of `inspectTemplate()`.

## Direct declarative and specialized Automation facades

The Mapping & Automation reference documents these Advanced orchestration surfaces:

```php
executeDeclarative(TemplateContract $contract, array $values): void

automateDependencies(
    TemplateContract $contract,
    ConcretePreflightResult $preflight
): void

automateNativeObjectActions(
    TemplateContract $contract,
    ConcretePreflightResult $preflight
): void

automateDocumentCapabilities(
    ConcretePreflightResult $preflight
): void
```

Use the common `automate()` facade for the normal mapped workflow.

Calling the three specialized Automation families manually is **not equivalent** to `automate()`: it does not create the common invocation-wide snapshot/rollback boundary and does not participate in the common single-success gate.

See [Automation](../mapping-automation/automation.md) for their exact execution contracts.

## Page geometry

`PageLayoutOdtTemplate` and its page-layout convenience operations are Advanced.

They provide bounded mutation of existing Writer page-layout geometry; they do not form a general page/master-style authoring system. Writer remains the owner of page/master style identity, succession, headers/footers, composition, and physical pagination.

See the [Page Layout guide](../../advanced/page-layout.md) for the supported geometry contract.

## Custom structured-element extension surface

Normal applications should construct the documented structured elements and use friendly style/layout options.

Custom element implementations may need the established semantic ownership hooks, including:

- `ownedElements()` traversal;
- semantic `StyleRequirement` production;
- typed style/resource requirement hooks;
- materialization methods such as `toDomNode()` where implementing an element contract requires them.

These are Advanced extension contracts, not a second normal authoring model.

Internal collectors, registries, resolvers, materializers, `StyleContext`, and similar document infrastructure remain hidden from normal application documentation even where PHP visibility is public.

## HTML import

`HtmlImporter` is an Advanced conversion facility.

It translates a deliberately bounded HTML/CSS subset into the engine's structured ODT model. It is **not a browser layout engine** and does not promise arbitrary HTML/CSS fidelity.

Important boundaries include flattened semantics for several block/heading elements, limited nested style composition, partial nested-list behavior, no semantic table-header model, bounded image-source handling, and a restricted CSS-like style vocabulary.

Use the dedicated [HTML Import guide](../../advanced/html-import.md) for the supported subset rather than inferring browser behavior.

## Style and mapping support values

Some immutable DTOs/helpers are public because tooling, diagnostics, or extension code can legitimately inspect them. Examples include semantic contract/provenance values, mapping resolution values, and selected style requirement/option splitting helpers.

Their public visibility does not promote the underlying execution services to application API.

## Infrastructure is not Advanced API

Classes such as inspectors, target resolvers, mutation services, Automation executors, capability projectors, package state carriers, style/resource materializers, and DOM serialization helpers are implementation infrastructure.

Do not instantiate them merely because PHP allows it. The supported end-programmer entry point is the corresponding facade/value contract documented in the API Reference.
